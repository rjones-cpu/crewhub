<?php

namespace App\Services\Timesheets;

use App\Enums\TimesheetStatus;
use App\Models\MajorProject;
use App\Models\Timesheet;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Backs the Timesheets approval queue: the KPI strip, the paginated queue table,
 * and the detail panel for the currently selected timesheet.
 */
class TimesheetApprovalQueueService
{
    public function __construct(
        protected AccommodationConfirmationService $accommodations,
    ) {}

    public function overview(?MajorProject $project, array $filters = []): array
    {
        $weeks = $this->availableWeeks($project);
        $week = $this->resolveWeek($filters['week'] ?? null, $weeks);
        $perPage = $this->resolvePerPage($filters['per_page'] ?? null);

        $scope = fn () => $this->scopedQuery($project, $week);

        $paginator = $this->filteredQuery($scope(), $filters, $week)
            ->with(['worker.company', 'managerApprover', 'clientApprover', 'returnedByUser'])
            ->orderBy('status')
            ->orderByDesc('updated_at')
            ->paginate($perPage)
            ->withQueryString();

        $accommodation = $this->accommodations->statesFor(collect($paginator->items()));
        $rows = collect($paginator->items())
            ->map(fn (Timesheet $sheet) => $this->row($sheet, $accommodation))
            ->all();

        return [
            'stats' => $this->stats($scope(), $week),
            'queue' => [
                'rows' => $rows,
                'links' => $paginator->linkCollection()->all(),
                'meta' => [
                    'from' => $paginator->firstItem(),
                    'to' => $paginator->lastItem(),
                    'total' => $paginator->total(),
                    'per_page' => $paginator->perPage(),
                    'current_page' => $paginator->currentPage(),
                    'last_page' => $paginator->lastPage(),
                ],
            ],
            'selected' => $this->selected($scope(), $filters['selected'] ?? null, $rows),
            'filters' => $this->filterState($filters, $weeks, $week, $perPage),
        ];
    }

    protected function scopedQuery(?MajorProject $project, ?Carbon $week): Builder
    {
        return Timesheet::query()
            ->when($project, fn (Builder $q) => $q->where('major_project_id', $project->id))
            ->when($week, fn (Builder $q) => $q->whereDate('period_start', $week->toDateString()));
    }

    protected function filteredQuery(Builder $query, array $filters, ?Carbon $week): Builder
    {
        $search = trim((string) ($filters['search'] ?? ''));
        $status = $filters['status'] ?? 'all';
        $approverRole = $filters['approver_role'] ?? 'all';
        $accommodation = $filters['accommodation'] ?? 'all';

        return $query
            ->when(
                $accommodation === 'pending',
                fn (Builder $q) => $this->accommodations->scopePending($q, $week)
            )
            ->when($search !== '', function (Builder $q) use ($search) {
                $q->where(function (Builder $inner) use ($search) {
                    $inner->whereHas('worker', function (Builder $worker) use ($search) {
                        $worker->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhere('employee_id', 'like', "%{$search}%");
                    });

                    if (ctype_digit($search)) {
                        $inner->orWhere('id', (int) $search);
                    }
                });
            })
            ->when($status !== 'all', fn (Builder $q) => $q->where('status', $status))
            ->when($approverRole !== 'all', function (Builder $q) use ($approverRole) {
                $q->whereIn('status', $this->statusesAwaiting($approverRole));
            });
    }

    /** Statuses where the named role is the one holding up the timesheet. */
    protected function statusesAwaiting(string $role): array
    {
        return match ($role) {
            'worker' => [TimesheetStatus::Draft->value, TimesheetStatus::Returned->value],
            'manager' => $this->clientApprovalEnabled()
                ? [TimesheetStatus::Submitted->value]
                : [TimesheetStatus::Submitted->value, TimesheetStatus::ManagerApproved->value],
            'client' => [TimesheetStatus::ManagerApproved->value],
            default => array_column(TimesheetStatus::cases(), 'value'),
        };
    }

    protected function stats(Builder $scope, ?Carbon $week): array
    {
        $countBy = fn (string $status) => (clone $scope)->where('status', $status)->count();

        return array_values(array_filter([
            [
                'key' => 'pending_manager',
                'label' => 'Pending Manager Approval',
                'value' => $countBy(TimesheetStatus::Submitted->value),
                'icon' => 'Clock',
                'tone' => 'warning',
                'filter' => ['status' => TimesheetStatus::Submitted->value, 'accommodation' => 'all'],
            ],
            $this->clientApprovalEnabled() ? [
                'key' => 'pending_client',
                'label' => 'Pending Client Approval',
                'value' => $countBy(TimesheetStatus::ManagerApproved->value),
                'icon' => 'Users',
                'tone' => 'journey',
                'filter' => ['status' => TimesheetStatus::ManagerApproved->value, 'accommodation' => 'all'],
            ] : null,
            [
                'key' => 'accommodation_pending',
                'label' => 'AI Accommodations Review Pending',
                'value' => $this->accommodationPendingCount($scope, $week),
                'icon' => 'Sparkles',
                'tone' => 'sky',
                'filter' => ['status' => 'all', 'accommodation' => 'pending'],
            ],
            [
                'key' => 'returned',
                'label' => 'Returned for Correction',
                'value' => $countBy(TimesheetStatus::Returned->value),
                'icon' => 'Undo2',
                'tone' => 'danger',
                'filter' => ['status' => TimesheetStatus::Returned->value, 'accommodation' => 'all'],
            ],
            [
                'key' => 'fully_approved',
                'label' => 'Fully Approved',
                'value' => $countBy(TimesheetStatus::FullyApproved->value),
                'icon' => 'CheckCircle2',
                'tone' => 'success',
                'filter' => ['status' => TimesheetStatus::FullyApproved->value, 'accommodation' => 'all'],
            ],
        ]));
    }

    protected function clientApprovalEnabled(): bool
    {
        return (bool) config('timesheets.client_approval_enabled');
    }

    protected function accommodationPendingCount(Builder $scope, ?Carbon $week): int
    {
        return $this->accommodations->scopePending(clone $scope, $week)->count();
    }

    protected function row(Timesheet $sheet, Collection $accommodation): array
    {
        $status = $sheet->status;

        return [
            'id' => $sheet->id,
            'reference' => $this->reference($sheet),
            'worker_name' => $sheet->worker?->full_name ?? 'Unknown worker',
            'employee_id' => $sheet->worker?->employee_id ?? '—',
            'avatar' => $sheet->worker?->avatar,
            'company' => $sheet->worker?->company?->name ?? '—',
            'position' => $sheet->worker?->position ?? '—',
            'week' => $this->weekLabel($sheet),
            'total_hours' => number_format((float) $sheet->hours, 2),
            'worker_approval' => $this->workerApprovalState($sheet),
            'accommodation' => $accommodation[$sheet->id] ?? ['state' => 'not_required', 'at' => null],
            'manager_approval' => $this->managerApprovalState($sheet),
            'client_approval' => $this->clientApprovalState($sheet),
            'current_stage' => $this->stageLabel($status),
            'last_updated' => $this->timestamp($sheet->updated_at),
            'actionable' => $sheet->awaitsManagerApproval() || $sheet->awaitsClientApproval(),
        ];
    }

    protected function workerApprovalState(Timesheet $sheet): array
    {
        if ($sheet->status === TimesheetStatus::Returned) {
            return ['state' => 'returned', 'at' => $this->timestamp($sheet->returned_at)];
        }

        $signedAt = $sheet->worker_signed_at ?? $sheet->submitted_at;

        return $signedAt
            ? ['state' => 'approved', 'at' => $this->timestamp($signedAt)]
            : ['state' => 'pending', 'at' => null];
    }

    protected function managerApprovalState(Timesheet $sheet): array
    {
        if ($sheet->status === TimesheetStatus::Returned || $sheet->status === TimesheetStatus::Rejected) {
            return ['state' => 'returned', 'at' => $this->timestamp($sheet->returned_at)];
        }

        return $sheet->manager_approved_at
            ? ['state' => 'approved', 'at' => $this->timestamp($sheet->manager_approved_at)]
            : ['state' => 'pending', 'at' => null];
    }

    protected function clientApprovalState(Timesheet $sheet): array
    {
        if (! $sheet->requiresClientApproval()) {
            return ['state' => 'not_required', 'at' => null];
        }

        return $sheet->client_approved_at
            ? ['state' => 'approved', 'at' => $this->timestamp($sheet->client_approved_at)]
            : ['state' => 'pending', 'at' => null];
    }

    protected function stageLabel(?TimesheetStatus $status): string
    {
        return match ($status) {
            TimesheetStatus::Draft => 'Draft',
            TimesheetStatus::Submitted => 'Manager Approval',
            TimesheetStatus::ManagerApproved => 'Client Approval',
            TimesheetStatus::FullyApproved => 'Fully Approved',
            TimesheetStatus::Returned => 'Returned for Correction',
            TimesheetStatus::Rejected => 'Rejected',
            default => '—',
        };
    }

    protected function selected(Builder $scope, mixed $selectedId, array $rows): ?array
    {
        $id = $selectedId ?: ($rows[0]['id'] ?? null);

        if (! $id) {
            return null;
        }

        $sheet = (clone $scope)
            ->with(['worker.company', 'managerApprover', 'clientApprover', 'returnedByUser'])
            ->find($id);

        if (! $sheet) {
            return null;
        }

        $accommodation = $this->accommodations->stateFor($sheet);
        $status = $sheet->status;

        return [
            'id' => $sheet->id,
            'reference' => $this->reference($sheet),
            'stage' => $this->stageLabel($status),
            'status' => $status?->value,
            'worker' => [
                'name' => $sheet->worker?->full_name ?? 'Unknown worker',
                'employee_id' => $sheet->worker?->employee_id,
                'position' => $sheet->worker?->position,
                'company' => $sheet->worker?->company?->name,
                'avatar' => $sheet->worker?->avatar,
            ],
            'week' => $this->weekLabel($sheet),
            'total_hours' => number_format((float) $sheet->hours, 2),
            'submitted_at' => $this->timestamp($sheet->submitted_at, true),
            'approval_record' => $this->approvalRecord($sheet, $accommodation),
            'notes' => $this->notes($sheet),
            'attachments' => [],
            'can' => [
                'approve_manager' => $sheet->awaitsManagerApproval(),
                'approve_client' => $sheet->awaitsClientApproval(),
                'return' => in_array($status, [
                    TimesheetStatus::Submitted,
                    TimesheetStatus::ManagerApproved,
                ], true),
            ],
        ];
    }

    protected function approvalRecord(Timesheet $sheet, array $accommodation): array
    {
        $worker = $this->workerApprovalState($sheet);
        $manager = $this->managerApprovalState($sheet);
        $client = $this->clientApprovalState($sheet);

        return array_values(array_filter([
            [
                'key' => 'worker',
                'title' => 'Worker Approval',
                'actor' => $sheet->worker?->full_name,
                'at' => $this->timestamp($sheet->worker_signed_at ?? $sheet->submitted_at, true),
                'state' => $worker['state'],
            ],
            [
                'key' => 'accommodation',
                'title' => 'Accommodations Confirmed',
                'actor' => 'AI Accommodations Check',
                'at' => $accommodation['at'],
                'state' => $accommodation['state'],
            ],
            [
                'key' => 'manager',
                'title' => 'Approved by Manager',
                'actor' => $sheet->managerApprover?->name ?? 'Awaiting manager',
                'at' => $this->timestamp($sheet->manager_approved_at, true),
                'state' => $manager['state'],
            ],
            $this->clientApprovalEnabled() ? [
                'key' => 'client',
                'title' => 'Approved by Client',
                'actor' => $sheet->clientApprover?->name ?? 'Client approval',
                'at' => $this->timestamp($sheet->client_approved_at, true),
                'state' => $client['state'],
            ] : null,
        ]));
    }

    protected function notes(Timesheet $sheet): array
    {
        return collect([
            ['author' => $sheet->worker?->full_name, 'body' => $sheet->worker_comment, 'at' => $sheet->submitted_at],
            ['author' => $sheet->managerApprover?->name, 'body' => $sheet->manager_comment, 'at' => $sheet->manager_approved_at],
            ['author' => $sheet->clientApprover?->name, 'body' => $sheet->client_comment, 'at' => $sheet->client_approved_at],
            ['author' => $sheet->returnedByUser?->name, 'body' => $sheet->return_reason, 'at' => $sheet->returned_at],
        ])
            ->filter(fn (array $note) => filled($note['body']))
            ->map(fn (array $note, int $index) => [
                'id' => $index,
                'author' => $note['author'] ?? 'System',
                'body' => $note['body'],
                'at' => $this->timestamp($note['at'], true),
            ])
            ->values()
            ->all();
    }

    protected function filterState(array $filters, Collection $weeks, ?Carbon $week, int $perPage): array
    {
        return [
            'week' => $week?->toDateString() ?? 'all',
            'search' => $filters['search'] ?? '',
            'status' => $filters['status'] ?? 'all',
            'approver_role' => $filters['approver_role'] ?? 'all',
            'accommodation' => $filters['accommodation'] ?? 'all',
            'per_page' => $perPage,
            'options' => [
                'weeks' => $weeks
                    ->map(fn (Carbon $start) => [
                        'value' => $start->toDateString(),
                        'label' => $start->format('M j').' – '.$start->copy()->addDays(6)->format('M j, Y'),
                    ])
                    ->prepend(['value' => 'all', 'label' => 'All weeks'])
                    ->values()
                    ->all(),
                'statuses' => array_values(array_filter([
                    ['value' => 'all', 'label' => 'All Statuses'],
                    ['value' => TimesheetStatus::Draft->value, 'label' => 'Draft'],
                    ['value' => TimesheetStatus::Submitted->value, 'label' => 'Pending Manager Approval'],
                    $this->clientApprovalEnabled()
                        ? ['value' => TimesheetStatus::ManagerApproved->value, 'label' => 'Pending Client Approval']
                        : null,
                    ['value' => TimesheetStatus::Returned->value, 'label' => 'Returned for Correction'],
                    ['value' => TimesheetStatus::FullyApproved->value, 'label' => 'Fully Approved'],
                    ['value' => TimesheetStatus::Rejected->value, 'label' => 'Rejected'],
                ])),
                'approverRoles' => array_values(array_filter([
                    ['value' => 'all', 'label' => 'All Roles'],
                    ['value' => 'worker', 'label' => 'Worker'],
                    ['value' => 'manager', 'label' => 'Manager'],
                    $this->clientApprovalEnabled() ? ['value' => 'client', 'label' => 'Client'] : null,
                ])),
                'perPage' => [10, 25, 50],
            ],
        ];
    }

    /** @return Collection<int, Carbon> */
    protected function availableWeeks(?MajorProject $project): Collection
    {
        return Timesheet::query()
            ->when($project, fn (Builder $q) => $q->where('major_project_id', $project->id))
            ->select('period_start')
            ->distinct()
            ->orderByDesc('period_start')
            ->limit(26)
            ->pluck('period_start')
            ->map(fn ($date) => Carbon::parse($date))
            ->values();
    }

    protected function resolveWeek(?string $requested, Collection $weeks): ?Carbon
    {
        if ($requested === 'all') {
            return null;
        }

        if ($requested) {
            $match = $weeks->first(fn (Carbon $week) => $week->toDateString() === $requested);

            if ($match) {
                return $match;
            }
        }

        return $weeks->first();
    }

    protected function resolvePerPage(mixed $requested): int
    {
        return in_array((int) $requested, [10, 25, 50], true) ? (int) $requested : 10;
    }

    protected function weekLabel(Timesheet $sheet): string
    {
        if (! $sheet->period_start || ! $sheet->period_end) {
            return '—';
        }

        return $sheet->period_start->format('M j').' – '.$sheet->period_end->format('M j, Y');
    }

    /** Human-readable timesheet reference, e.g. TS-2026-W22-10234. */
    protected function reference(Timesheet $sheet): string
    {
        if (! $sheet->period_start) {
            return 'TS-'.$sheet->id;
        }

        return sprintf(
            'TS-%s-W%s-%d',
            $sheet->period_start->format('Y'),
            $sheet->period_start->isoWeek(),
            $sheet->id,
        );
    }

    protected function timestamp($value, bool $withYear = false): ?string
    {
        if (! $value) {
            return null;
        }

        $date = $value instanceof Carbon ? $value : Carbon::parse($value);

        return $date->format($withYear ? 'M j, Y g:i A' : 'M j, g:i A');
    }
}
