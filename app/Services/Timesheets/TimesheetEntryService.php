<?php

namespace App\Services\Timesheets;

use App\Enums\TimesheetStatus;
use App\Models\MajorProject;
use App\Models\Timesheet;
use App\Models\Worker;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Roster for the Timesheet Entry tab: eligible workers for the selected week,
 * with create-draft for anyone still missing a sheet.
 */
class TimesheetEntryService
{
    public function __construct(
        protected TimesheetWorkflowService $workflow,
    ) {}

    public function roster(?MajorProject $project, array $filters = []): array
    {
        $weeks = $this->availableWeeks();
        $week = $this->resolveWeek($filters['week'] ?? null, $weeks);
        $perPage = in_array((int) ($filters['per_page'] ?? 0), [10, 25, 50], true)
            ? (int) $filters['per_page']
            : 10;

        $paginator = $this->filteredWorkers($project, $week, $filters)
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->paginate($perPage)
            ->withQueryString();

        $sheets = $this->sheetsFor($paginator, $week);

        return [
            'stats' => $this->stats($project, $week),
            'roster' => [
                'rows' => collect($paginator->items())
                    ->map(fn (Worker $worker) => $this->row($worker, $sheets->get($worker->id), $week))
                    ->all(),
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
            'filters' => [
                'week' => $week->toDateString(),
                'search' => $filters['search'] ?? '',
                'status' => $filters['status'] ?? 'all',
                'per_page' => $perPage,
                'options' => [
                    'weeks' => $weeks
                        ->map(fn (Carbon $start) => [
                            'value' => $start->toDateString(),
                            'label' => $start->format('M j').' – '.$start->copy()->addDays(6)->format('M j, Y'),
                        ])
                        ->values()
                        ->all(),
                    'statuses' => [
                        ['value' => 'all', 'label' => 'All'],
                        ['value' => 'missing', 'label' => 'Not started'],
                        ['value' => TimesheetStatus::Draft->value, 'label' => 'Draft'],
                        ['value' => TimesheetStatus::Returned->value, 'label' => 'Returned'],
                        ['value' => TimesheetStatus::Submitted->value, 'label' => 'Submitted'],
                        ['value' => TimesheetStatus::ManagerApproved->value, 'label' => 'Pending client'],
                        ['value' => TimesheetStatus::FullyApproved->value, 'label' => 'Fully approved'],
                        ['value' => TimesheetStatus::Rejected->value, 'label' => 'Rejected'],
                    ],
                    'perPage' => [10, 25, 50],
                ],
            ],
            'canCreate' => true,
        ];
    }

    public function createDraft(Worker $worker, ?string $week, ?MajorProject $project): Timesheet
    {
        $start = $week
            ? Carbon::parse($week)->startOfWeek()
            : now()->startOfWeek();

        return $this->workflow->createDraft($worker, $start, $project);
    }

    protected function filteredWorkers(?MajorProject $project, Carbon $week, array $filters): Builder
    {
        $search = trim((string) ($filters['search'] ?? ''));
        $status = $filters['status'] ?? 'all';

        return Worker::query()
            ->with(['company', 'primaryProject'])
            ->where('timesheet_access', true)
            ->when($project, fn (Builder $q) => $q->where('primary_project_id', $project->id))
            ->when($search !== '', function (Builder $q) use ($search) {
                $q->where(function (Builder $inner) use ($search) {
                    $inner->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('employee_id', 'like', "%{$search}%");
                });
            })
            ->when($status === 'missing', function (Builder $q) use ($week) {
                $q->whereDoesntHave('timesheets', fn (Builder $sheet) => $this->forWeek($sheet, $week));
            })
            ->when(
                $status !== 'all' && $status !== 'missing',
                function (Builder $q) use ($week, $status) {
                    $q->whereHas('timesheets', fn (Builder $sheet) => $this->forWeek($sheet, $week)
                        ->where('status', $status));
                }
            );
    }

    protected function forWeek(Builder $query, Carbon $week): Builder
    {
        return $query->whereDate('period_start', $week->toDateString());
    }

    protected function sheetsFor(LengthAwarePaginator $paginator, Carbon $week): Collection
    {
        $ids = collect($paginator->items())->pluck('id');

        if ($ids->isEmpty()) {
            return collect();
        }

        return Timesheet::query()
            ->whereIn('worker_id', $ids)
            ->whereDate('period_start', $week->toDateString())
            ->get()
            ->keyBy('worker_id');
    }

    protected function stats(?MajorProject $project, Carbon $week): array
    {
        $workers = Worker::query()
            ->where('timesheet_access', true)
            ->when($project, fn (Builder $q) => $q->where('primary_project_id', $project->id));

        $eligible = (clone $workers)->count();
        $started = (clone $workers)
            ->whereHas('timesheets', fn (Builder $sheet) => $this->forWeek($sheet, $week))
            ->count();

        $countStatus = fn (string $status) => Timesheet::query()
            ->when($project, fn (Builder $q) => $q->where('major_project_id', $project->id))
            ->whereDate('period_start', $week->toDateString())
            ->where('status', $status)
            ->count();

        return [
            [
                'key' => 'missing',
                'label' => 'Not started',
                'value' => max(0, $eligible - $started),
                'filter' => ['status' => 'missing'],
            ],
            [
                'key' => 'draft',
                'label' => 'Drafts',
                'value' => $countStatus(TimesheetStatus::Draft->value),
                'filter' => ['status' => TimesheetStatus::Draft->value],
            ],
            [
                'key' => 'returned',
                'label' => 'Returned',
                'value' => $countStatus(TimesheetStatus::Returned->value),
                'filter' => ['status' => TimesheetStatus::Returned->value],
            ],
            [
                'key' => 'submitted',
                'label' => 'In approval',
                'value' => $countStatus(TimesheetStatus::Submitted->value)
                    + $countStatus(TimesheetStatus::ManagerApproved->value),
                'filter' => ['status' => TimesheetStatus::Submitted->value],
            ],
        ];
    }

    protected function row(Worker $worker, ?Timesheet $sheet, Carbon $week): array
    {
        $status = $sheet?->status;
        $editable = $sheet?->isEditable() ?? false;

        return [
            'worker_id' => $worker->id,
            'timesheet_id' => $sheet?->id,
            'name' => $worker->full_name,
            'employee_id' => $worker->employee_id ?? '—',
            'avatar' => $worker->avatar,
            'position' => $worker->position ?? '—',
            'company' => $worker->company?->name ?? '—',
            'week' => $week->format('M j').' – '.$week->copy()->addDays(6)->format('M j, Y'),
            'total_hours' => $sheet ? number_format((float) $sheet->hours, 2) : '0.00',
            'status' => $status?->value ?? 'missing',
            'status_label' => $status?->label() ?? 'Not started',
            'can_create' => $sheet === null,
            'can_edit' => $editable,
        ];
    }

    /** @return Collection<int, Carbon> */
    protected function availableWeeks(): Collection
    {
        $current = now()->startOfWeek();

        $fromSheets = Timesheet::query()
            ->select('period_start')
            ->distinct()
            ->orderByDesc('period_start')
            ->limit(16)
            ->pluck('period_start')
            ->map(fn ($date) => Carbon::parse($date)->startOfWeek());

        return collect(range(0, 7))
            ->map(fn (int $ago) => $current->copy()->subWeeks($ago))
            ->concat($fromSheets)
            ->unique(fn (Carbon $week) => $week->toDateString())
            ->sortByDesc(fn (Carbon $week) => $week->timestamp)
            ->values();
    }

    protected function resolveWeek(?string $requested, Collection $weeks): Carbon
    {
        if ($requested) {
            $match = $weeks->first(fn (Carbon $week) => $week->toDateString() === $requested);

            if ($match) {
                return $match;
            }

            return Carbon::parse($requested)->startOfWeek();
        }

        return $weeks->first() ?? now()->startOfWeek();
    }
}
