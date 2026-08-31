<?php

namespace App\Services\Timesheets;

use App\Enums\TimesheetStatus;
use App\Models\MajorProject;
use App\Models\Timesheet;
use App\Models\User;
use App\Models\Worker;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TimesheetReportService
{
    public const REPORT_TYPES = [
        'all' => 'All Report Types',
        'summary' => 'Weekly Timesheet Summary',
        'approval-aging' => 'Approval Aging Report',
        'missing' => 'Missing Timesheets Report',
        'hours-by-position' => 'Hours by Position',
        'hours-by-worker' => 'Hours by Worker',
        'equipment-hours' => 'Equipment Hours Summary',
        'client-approval' => 'Client Approval Report',
        'ai-accommodations' => 'AI Accommodations Confirmation Report',
    ];

    public function __construct(
        protected AccommodationConfirmationService $accommodations,
    ) {}

    public function payload(?MajorProject $project, array $filters, User $user): array
    {
        $weeks = $this->availableWeeks($project);
        $week = $this->resolveWeek($filters['week'] ?? null, $weeks);
        $status = $filters['status'] ?? 'all';
        $reportType = $filters['report_type'] ?? 'all';
        $search = trim((string) ($filters['search'] ?? ''));

        $scope = $this->scopedQuery($project, $week, $status, $search);
        $sheets = (clone $scope)->with(['worker.company', 'managerApprover', 'clientApprover'])->get();
        $accommodation = $this->accommodations->statesFor($sheets);

        $total = $sheets->count();
        $fullyApproved = $sheets->where('status', TimesheetStatus::FullyApproved)->count();
        $pendingManager = $sheets->where('status', TimesheetStatus::Submitted)->count();
        $pendingClient = $sheets->where('status', TimesheetStatus::ManagerApproved)->count();
        $rejected = $sheets->where('status', TimesheetStatus::Rejected)->count();
        $aiConfirmed = $accommodation->filter(fn (array $state) => $state['state'] === 'confirmed')->count();
        $hours = round((float) $sheets->sum('hours'), 2);

        $weekLabel = $week
            ? $week->format('M j').' – '.$week->copy()->addDays(6)->format('M j, Y')
            : 'All weeks';

        return [
            'filters' => $this->filterState($project, $weeks, $week, $status, $reportType),
            'stats' => $this->stats(
                $total,
                $fullyApproved,
                $pendingManager,
                $pendingClient,
                $aiConfirmed,
                $hours,
            ),
            'submissionTrend' => $this->submissionTrend($project, $weeks),
            'hoursByPosition' => $this->hoursByPosition($sheets),
            'approvalBreakdown' => $this->approvalBreakdown(
                $total,
                $fullyApproved,
                $pendingManager,
                $pendingClient,
                $rejected,
                $aiConfirmed,
            ),
            'scheduledReports' => [],
            'reportLibrary' => $this->reportTypes()
                ->except('all')
                ->map(fn (string $name, string $id) => ['id' => $id, 'name' => $name])
                ->values()
                ->all(),
            'generatedReports' => $this->generatedReports($user, $weekLabel, $reportType),
            'keyExceptions' => $this->exceptions($project, $week, $sheets, $accommodation),
            'quickExports' => [
                ['id' => 'summary', 'name' => 'Current Week Timesheets (CSV)'],
                ['id' => 'approval-aging', 'name' => 'Approval Aging Report (CSV)'],
                ['id' => 'missing', 'name' => 'Missing Timesheets (CSV)'],
                ['id' => 'hours-by-position', 'name' => 'Hours by Position (CSV)'],
                ['id' => 'ai-accommodations', 'name' => 'AI Accommodations Summary (CSV)'],
            ],
            'footnote' => [
                'timezone' => config('app.timezone'),
                'updated_at' => now()->timezone(config('app.timezone'))->format('M j, Y g:i A'),
            ],
        ];
    }

    public function export(?MajorProject $project, array $filters): StreamedResponse
    {
        $weeks = $this->availableWeeks($project);
        $week = $this->resolveWeek($filters['week'] ?? null, $weeks);
        $type = $filters['type'] ?? $filters['report_type'] ?? 'summary';
        $status = $filters['status'] ?? 'all';
        $search = trim((string) ($filters['search'] ?? ''));

        $sheets = $this->scopedQuery($project, $week, $status, $search)
            ->with(['worker.company', 'managerApprover', 'clientApprover'])
            ->orderBy('id')
            ->get();

        [$filename, $headers, $rows] = match ($type) {
            'approval-aging' => $this->agingExport($sheets),
            'missing' => $this->missingExport($project, $week),
            'hours-by-position' => $this->hoursByPositionExport($sheets),
            'hours-by-worker' => $this->hoursByWorkerExport($sheets),
            'equipment-hours' => $this->equipmentExport($sheets),
            'client-approval' => $this->clientApprovalExport($sheets),
            'ai-accommodations' => $this->accommodationExport($sheets),
            default => $this->summaryExport($sheets),
        };

        return response()->streamDownload(function () use ($headers, $rows): void {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $headers);

            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    protected function scopedQuery(?MajorProject $project, ?Carbon $week, string $status, string $search): Builder
    {
        return Timesheet::query()
            ->when($project, fn (Builder $q) => $q->where('major_project_id', $project->id))
            ->when($week, fn (Builder $q) => $q->whereDate('period_start', $week->toDateString()))
            ->when($status !== 'all', fn (Builder $q) => $q->where('status', $status))
            ->when($search !== '', function (Builder $q) use ($search) {
                $q->whereHas('worker', function (Builder $worker) use ($search) {
                    $worker->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('employee_id', 'like', "%{$search}%")
                        ->orWhere('position', 'like', "%{$search}%");
                });
            });
    }

    protected function stats(
        int $total,
        int $fullyApproved,
        int $pendingManager,
        int $pendingClient,
        int $aiConfirmed,
        float $hours,
    ): array {
        return array_values(array_filter([
            [
                'key' => 'total',
                'label' => 'Total Timesheets',
                'value' => number_format($total),
                'hint' => 'View details',
                'href' => route('timesheets.index'),
                'icon' => 'FileSpreadsheet',
                'tone' => 'brand',
            ],
            [
                'key' => 'fully_approved',
                'label' => 'Fully Approved',
                'value' => number_format($fullyApproved),
                'hint' => $this->pctHint($fullyApproved, $total),
                'href' => route('timesheets.index', ['status' => TimesheetStatus::FullyApproved->value]),
                'icon' => 'CheckCircle2',
                'tone' => 'success',
            ],
            [
                'key' => 'pending_manager',
                'label' => 'Pending Manager Approval',
                'value' => number_format($pendingManager),
                'hint' => $this->pctHint($pendingManager, $total),
                'href' => route('timesheets.index', ['status' => TimesheetStatus::Submitted->value]),
                'icon' => 'Clock',
                'tone' => 'warning',
            ],
            $this->clientApprovalEnabled() ? [
                'key' => 'pending_client',
                'label' => 'Pending Client Approval',
                'value' => number_format($pendingClient),
                'hint' => $this->pctHint($pendingClient, $total),
                'href' => route('timesheets.index', ['status' => TimesheetStatus::ManagerApproved->value]),
                'icon' => 'Users',
                'tone' => 'journey',
            ] : null,
            [
                'key' => 'ai_confirmed',
                'label' => 'AI Confirmed',
                'value' => number_format($aiConfirmed),
                'hint' => $this->pctHint($aiConfirmed, $total),
                'icon' => 'Sparkles',
                'tone' => 'sky',
            ],
            [
                'key' => 'total_hours',
                'label' => 'Total Hours Reported',
                'value' => number_format($hours, 2),
                'hint' => 'This period',
                'icon' => 'Timer',
                'tone' => 'cyan',
            ],
        ]));
    }

    protected function clientApprovalEnabled(): bool
    {
        return (bool) config('timesheets.client_approval_enabled');
    }

    /** @return Collection<string, string> */
    protected function reportTypes(): Collection
    {
        return collect(self::REPORT_TYPES)
            ->when(! $this->clientApprovalEnabled(), fn (Collection $types) => $types->except('client-approval'));
    }

    protected function submissionTrend(?MajorProject $project, Collection $weeks): array
    {
        return $weeks
            ->take(5)
            ->reverse()
            ->map(function (Carbon $start) use ($project) {
                $end = $start->copy()->addDays(6);
                $period = Timesheet::query()
                    ->when($project, fn (Builder $q) => $q->where('major_project_id', $project->id))
                    ->whereDate('period_start', $start->toDateString());

                return [
                    'label' => $start->format('M j').' – '.$end->format('M j'),
                    'submitted' => (clone $period)->whereNotNull('submitted_at')->count(),
                    'manager_approved' => (clone $period)->whereNotNull('manager_approved_at')->count(),
                    'client_approved' => (clone $period)->whereNotNull('client_approved_at')->count(),
                ];
            })
            ->values()
            ->all();
    }

    protected function hoursByPosition(Collection $sheets): array
    {
        return $sheets
            ->groupBy(fn (Timesheet $sheet) => $sheet->worker?->position ?: 'Other')
            ->map(fn (Collection $group, string $position) => [
                'position' => $position,
                'hours' => round((float) $group->sum('hours'), 2),
            ])
            ->sortByDesc('hours')
            ->values()
            ->take(10)
            ->all();
    }

    protected function approvalBreakdown(
        int $total,
        int $fullyApproved,
        int $pendingManager,
        int $pendingClient,
        int $rejected,
        int $aiConfirmed,
    ): array {
        $segments = collect([
            ['name' => 'Fully Approved', 'value' => $fullyApproved, 'color' => '#16A34A'],
            ['name' => 'Pending Manager Approval', 'value' => $pendingManager, 'color' => '#EA580C'],
            ...$this->clientApprovalEnabled()
                ? [['name' => 'Pending Client Approval', 'value' => $pendingClient, 'color' => '#7C3AED']]
                : [],
            ['name' => 'Rejected', 'value' => $rejected, 'color' => '#2563EB'],
        ])->filter(fn (array $row) => $row['value'] > 0)->values();

        return [
            'total' => $total,
            'segments' => $segments->all(),
            'legend' => $segments
                ->map(fn (array $row) => [
                    ...$row,
                    'pct' => $this->pct($row['value'], $total),
                ])
                ->push([
                    'name' => 'AI Confirmed',
                    'value' => $aiConfirmed,
                    'pct' => $this->pct($aiConfirmed, $total),
                    'color' => '#2563EB',
                ])
                ->all(),
            'note' => 'AI Confirmed includes timesheets with every overlapping accommodation confirmed.',
        ];
    }

    protected function generatedReports(User $user, string $weekLabel, string $reportType): array
    {
        $now = now()->timezone(config('app.timezone'))->format('M j, Y g:i A');

        return $this->reportTypes()
            ->except('all')
            ->when(
                $reportType !== 'all',
                fn (Collection $types) => $types->only([$reportType])
            )
            ->map(fn (string $name, string $id) => [
                'id' => $id,
                'name' => $name,
                'range' => $weekLabel,
                'generated_by' => $user->name,
                'generated_on' => $now,
                'format' => 'CSV',
            ])
            ->values()
            ->all();
    }

    protected function exceptions(?MajorProject $project, ?Carbon $week, Collection $sheets, Collection $accommodation): array
    {
        $eligible = Worker::query()
            ->where('timesheet_access', true)
            ->when($project, fn (Builder $q) => $q->where('primary_project_id', $project->id))
            ->count();

        $started = $week
            ? Timesheet::query()
                ->when($project, fn (Builder $q) => $q->where('major_project_id', $project->id))
                ->whereDate('period_start', $week->toDateString())
                ->count()
            : $sheets->count();

        $cutoff = now()->subDays(3);

        return array_values(array_filter([
            [
                'id' => 'missing',
                'issue' => 'Missing Timesheets',
                'count' => max(0, $eligible - $started),
                'details' => 'Workers with no timesheet',
                'priority' => 'high',
                'href' => route('timesheets.entry', array_filter([
                    'week' => $week?->toDateString(),
                    'status' => 'missing',
                ])),
            ],
            [
                'id' => 'overdue-manager',
                'issue' => 'Overdue Manager Approval',
                'count' => $sheets
                    ->where('status', TimesheetStatus::Submitted)
                    ->filter(fn (Timesheet $sheet) => $sheet->submitted_at && $sheet->submitted_at->lt($cutoff))
                    ->count(),
                'details' => 'Older than 3 days',
                'priority' => 'high',
                'href' => route('timesheets.approval', ['status' => TimesheetStatus::Submitted->value]),
            ],
            $this->clientApprovalEnabled() ? [
                'id' => 'overdue-client',
                'issue' => 'Overdue Client Approval',
                'count' => $sheets
                    ->where('status', TimesheetStatus::ManagerApproved)
                    ->filter(fn (Timesheet $sheet) => $sheet->manager_approved_at && $sheet->manager_approved_at->lt($cutoff))
                    ->count(),
                'details' => 'Older than 3 days',
                'priority' => 'medium',
                'href' => route('timesheets.approval', ['status' => TimesheetStatus::ManagerApproved->value]),
            ] : null,
            [
                'id' => 'ai-mismatches',
                'issue' => 'AI Accommodation Mismatches',
                'count' => $accommodation->filter(fn (array $state) => $state['state'] === 'pending')->count(),
                'details' => 'Require review',
                'priority' => 'medium',
                'href' => route('timesheets.approval', ['accommodation' => 'pending']),
            ],
            [
                'id' => 'rejected',
                'issue' => 'Rejected Timesheets',
                'count' => $sheets->where('status', TimesheetStatus::Rejected)->count(),
                'details' => 'Needs correction',
                'priority' => 'low',
                'href' => route('timesheets.index', ['status' => TimesheetStatus::Rejected->value]),
            ],
        ]));
    }

    protected function filterState(
        ?MajorProject $project,
        Collection $weeks,
        ?Carbon $week,
        string $status,
        string $reportType,
    ): array {
        return [
            'majorProject' => [
                'selected' => $project?->id ? (string) $project->id : 'all',
                'options' => [],
            ],
            'dateRange' => [
                'selected' => $week?->toDateString() ?? 'all',
                'options' => $weeks
                    ->map(fn (Carbon $start) => [
                        'value' => $start->toDateString(),
                        'label' => $start->format('M j').' – '.$start->copy()->addDays(6)->format('M j, Y'),
                    ])
                    ->prepend(['value' => 'all', 'label' => 'All weeks'])
                    ->values()
                    ->all(),
            ],
            'reportType' => [
                'selected' => $reportType,
                'options' => $this->reportTypes()
                    ->map(fn (string $label, string $value) => compact('value', 'label'))
                    ->values()
                    ->all(),
            ],
            'status' => [
                'selected' => $status,
                'options' => array_values(array_filter([
                    ['value' => 'all', 'label' => 'All Statuses'],
                    ['value' => TimesheetStatus::Draft->value, 'label' => 'Draft'],
                    ['value' => TimesheetStatus::Submitted->value, 'label' => 'Pending Manager Approval'],
                    $this->clientApprovalEnabled()
                        ? ['value' => TimesheetStatus::ManagerApproved->value, 'label' => 'Pending Client Approval']
                        : null,
                    ['value' => TimesheetStatus::FullyApproved->value, 'label' => 'Fully Approved'],
                    ['value' => TimesheetStatus::Rejected->value, 'label' => 'Rejected'],
                ])),
            ],
        ];
    }

    /** @return Collection<int, Carbon> */
    protected function availableWeeks(?MajorProject $project): Collection
    {
        $current = now()->startOfWeek();

        $fromSheets = Timesheet::query()
            ->when($project, fn (Builder $q) => $q->where('major_project_id', $project->id))
            ->select('period_start')
            ->distinct()
            ->orderByDesc('period_start')
            ->limit(26)
            ->pluck('period_start')
            ->map(fn ($date) => Carbon::parse($date)->startOfWeek());

        return collect([$current])
            ->concat($fromSheets)
            ->unique(fn (Carbon $week) => $week->toDateString())
            ->sortByDesc(fn (Carbon $week) => $week->timestamp)
            ->values();
    }

    protected function resolveWeek(?string $requested, Collection $weeks): ?Carbon
    {
        if ($requested === 'all') {
            return null;
        }

        if ($requested) {
            $match = $weeks->first(fn (Carbon $week) => $week->toDateString() === $requested);

            return $match ?? Carbon::parse($requested)->startOfWeek();
        }

        return $weeks->first();
    }

    protected function summaryExport(Collection $sheets): array
    {
        return [
            'timesheet-summary.csv',
            ['Worker', 'Employee ID', 'Position', 'Week start', 'Hours', 'Status', 'Submitted at'],
            $sheets->map(fn (Timesheet $sheet) => [
                $sheet->worker?->full_name,
                $sheet->worker?->employee_id,
                $sheet->worker?->position,
                $sheet->period_start?->toDateString(),
                $sheet->hours,
                $sheet->status?->value,
                $sheet->submitted_at?->toDateTimeString(),
            ])->all(),
        ];
    }

    protected function agingExport(Collection $sheets): array
    {
        $pending = $sheets->filter(fn (Timesheet $sheet) => in_array($sheet->status, [
            TimesheetStatus::Submitted,
            TimesheetStatus::ManagerApproved,
        ], true));

        return [
            'approval-aging.csv',
            ['Worker', 'Status', 'Submitted at', 'Days waiting'],
            $pending->map(function (Timesheet $sheet) {
                $from = $sheet->status === TimesheetStatus::ManagerApproved
                    ? $sheet->manager_approved_at
                    : $sheet->submitted_at;

                return [
                    $sheet->worker?->full_name,
                    $sheet->status?->label(),
                    $from?->toDateTimeString(),
                    $from ? $from->diffInDays(now()) : null,
                ];
            })->all(),
        ];
    }

    protected function missingExport(?MajorProject $project, ?Carbon $week): array
    {
        $workers = Worker::query()
            ->with('primaryProject')
            ->where('timesheet_access', true)
            ->when($project, fn (Builder $q) => $q->where('primary_project_id', $project->id))
            ->when($week, function (Builder $q) use ($week) {
                $q->whereDoesntHave(
                    'timesheets',
                    fn (Builder $sheet) => $sheet->whereDate('period_start', $week->toDateString())
                );
            })
            ->orderBy('last_name')
            ->get();

        return [
            'missing-timesheets.csv',
            ['Worker', 'Employee ID', 'Position', 'Project'],
            $workers->map(fn (Worker $worker) => [
                $worker->full_name,
                $worker->employee_id,
                $worker->position,
                $worker->primaryProject?->name,
            ])->all(),
        ];
    }

    protected function hoursByPositionExport(Collection $sheets): array
    {
        return [
            'hours-by-position.csv',
            ['Position', 'Hours'],
            collect($this->hoursByPosition($sheets))
                ->map(fn (array $row) => [$row['position'], $row['hours']])
                ->all(),
        ];
    }

    protected function hoursByWorkerExport(Collection $sheets): array
    {
        return [
            'hours-by-worker.csv',
            ['Worker', 'Employee ID', 'Hours'],
            $sheets
                ->groupBy('worker_id')
                ->map(fn (Collection $group) => [
                    $group->first()?->worker?->full_name,
                    $group->first()?->worker?->employee_id,
                    round((float) $group->sum('hours'), 2),
                ])
                ->values()
                ->all(),
        ];
    }

    protected function equipmentExport(Collection $sheets): array
    {
        return [
            'equipment-hours.csv',
            ['Worker', 'Equipment hours'],
            $sheets->map(fn (Timesheet $sheet) => [
                $sheet->worker?->full_name,
                $sheet->equipment_hours,
            ])->all(),
        ];
    }

    protected function clientApprovalExport(Collection $sheets): array
    {
        return [
            'client-approval.csv',
            ['Worker', 'Client required', 'Client approved at', 'Approver', 'Status'],
            $sheets->map(fn (Timesheet $sheet) => [
                $sheet->worker?->full_name,
                $sheet->client_approval_required ? 'Yes' : 'No',
                $sheet->client_approved_at?->toDateTimeString(),
                $sheet->clientApprover?->name,
                $sheet->status?->label(),
            ])->all(),
        ];
    }

    protected function accommodationExport(Collection $sheets): array
    {
        $states = $this->accommodations->statesFor($sheets);

        return [
            'ai-accommodations.csv',
            ['Worker', 'Accommodation state', 'Checked at'],
            $sheets->map(fn (Timesheet $sheet) => [
                $sheet->worker?->full_name,
                $states[$sheet->id]['state'] ?? 'not_required',
                $states[$sheet->id]['at'] ?? null,
            ])->all(),
        ];
    }

    protected function pct(int $part, int $total): float
    {
        return $total > 0 ? round(($part / $total) * 100, 1) : 0.0;
    }

    protected function pctHint(int $part, int $total): string
    {
        return $this->pct($part, $total).'% of total';
    }
}
