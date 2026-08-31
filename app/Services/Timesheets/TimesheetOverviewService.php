<?php

namespace App\Services\Timesheets;

use App\Enums\TimesheetStatus;
use App\Models\MajorProject;
use App\Models\ProjectManagerLink;
use App\Models\Timesheet;
use App\Models\Worker;
use App\Models\WorkerActivity;
use App\Services\Workers\WorkerFeatureAccessService;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class TimesheetOverviewService
{
    public function __construct(private readonly WorkerFeatureAccessService $featureAccess)
    {
    }

    public function overview(?MajorProject $project = null, ?Carbon $from = null, ?Carbon $to = null): array
    {
        $from ??= now()->subDays(13)->startOfDay();
        $to ??= now()->endOfDay();
        $clientRequired = (bool) config('timesheets.client_approval_enabled')
            && (bool) $project?->client_approval_required;

        $base = Timesheet::query()
            ->when($project, fn ($q) => $q->where('major_project_id', $project->id));

        $period = (clone $base)->whereBetween('period_start', [$from->toDateString(), $to->toDateString()]);

        $expectedWorkers = $this->featureAccess->eligibleWorkers(
            Worker::query()
            ->with('primaryProject')
            ->where('timesheet_access', true)
            ->when($project, fn ($q) => $q->where('primary_project_id', $project->id))
            ->get(),
            'timesheet',
        )->count();

        // Rough expected count: one timesheet per eligible worker in the window weeks.
        $weeks = max(1, (int) ceil($from->diffInDays($to) / 7));
        $expected = max($expectedWorkers * $weeks, (clone $period)->count());

        $submitted = (clone $period)->whereNotIn('status', [
            TimesheetStatus::Draft->value,
        ])->count();

        $pendingApproval = (clone $period)->whereIn('status', TimesheetStatus::pendingApprovalValues())->count();
        $missing = max(0, $expected - $submitted);
        $fullyApproved = (clone $period)->where('status', TimesheetStatus::FullyApproved->value)->count();
        $rejected = (clone $period)->where('status', TimesheetStatus::Rejected->value)->count();
        $reviewed = $fullyApproved + $rejected + $pendingApproval;
        $approvalRate = $reviewed > 0 ? round(($fullyApproved / $reviewed) * 100) : 0;

        return [
            'stats' => [
                'client_approval_required' => $clientRequired,
                'client_approval_label' => $clientRequired ? 'Required' : 'Not Required',
                'expected' => $expected,
                'submitted' => $submitted,
                'submitted_pct' => $expected > 0 ? round(($submitted / $expected) * 100) : 0,
                'pending_approval' => $pendingApproval,
                'pending_pct' => $expected > 0 ? round(($pendingApproval / $expected) * 100) : 0,
                'missing' => $missing,
                'missing_pct' => $expected > 0 ? round(($missing / $expected) * 100) : 0,
                'approval_rate' => $approvalRate,
                'bottlenecks' => $this->bottleneckCount($project),
                'compliance_pct' => $approvalRate,
                'compliance_target' => 95,
            ],
            'submissionTrend' => $this->submissionTrend($project, $from, $to, $expectedWorkers),
            'managerQueue' => $this->managerQueue($project),
            'clientQueueEnabled' => $clientRequired,
            'attention' => $this->attention($project),
            'missingByCompany' => $this->missingByCompany($project, $missing),
            'approvalFlow' => $this->approvalFlow($clientRequired),
            'recentActivity' => $this->recentActivity($project),
            'compliance' => $this->complianceBreakdown($period, $expected, $submitted, $missing),
            'period' => [
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
                'label' => $from->format('M j').' – '.$to->format('M j, Y'),
            ],
        ];
    }

    protected function submissionTrend(?MajorProject $project, Carbon $from, Carbon $to, int $expectedWorkers): array
    {
        $points = [];
        $cursor = $from->copy()->startOfDay();

        while ($cursor->lte($to)) {
            $dayEnd = $cursor->copy()->endOfDay();
            $submitted = Timesheet::query()
                ->when($project, fn ($q) => $q->where('major_project_id', $project->id))
                ->whereNotNull('submitted_at')
                ->whereBetween('submitted_at', [$cursor, $dayEnd])
                ->count();

            $points[] = [
                'date' => $cursor->toDateString(),
                'label' => $cursor->format('M j'),
                'expected' => $expectedWorkers > 0 ? max(1, (int) round($expectedWorkers / 7)) : 0,
                'submitted' => $submitted,
            ];

            $cursor->addDay();
        }

        return $points;
    }

    protected function managerQueue(?MajorProject $project): array
    {
        $links = ProjectManagerLink::query()
            ->with('manager')
            ->when($project, fn ($q) => $q->where('major_project_id', $project->id))
            ->orderBy('id')
            ->limit(8)
            ->get();

        if ($links->isEmpty()) {
            // Fall back to grouping submitted timesheets without named approvers.
            $pending = Timesheet::query()
                ->when($project, fn ($q) => $q->where('major_project_id', $project->id))
                ->where('status', TimesheetStatus::Submitted->value)
                ->count();

            if ($pending === 0) {
                return [];
            }

            return [[
                'id' => 0,
                'name' => 'Unassigned queue',
                'role' => 'Workforce Manager',
                'pending' => $pending,
                'overdue' => 0,
                'oldest_pending' => null,
            ]];
        }

        return $links->map(function (ProjectManagerLink $link) use ($project) {
            $pendingQuery = Timesheet::query()
                ->where('status', TimesheetStatus::Submitted->value)
                ->when($project, fn ($q) => $q->where('major_project_id', $project->id),
                    fn ($q) => $q->where('major_project_id', $link->major_project_id));

            $pending = (clone $pendingQuery)->count();
            $overdue = (clone $pendingQuery)
                ->whereNotNull('due_date')
                ->where('due_date', '<', now()->toDateString())
                ->count();
            $oldest = (clone $pendingQuery)->orderBy('submitted_at')->value('submitted_at');

            return [
                'id' => $link->id,
                'name' => $link->manager?->name ?? 'Unknown',
                'role' => $link->title ?: 'Project Manager',
                'pending' => $pending,
                'overdue' => $overdue,
                'oldest_pending' => $oldest?->toDateString(),
            ];
        })->values()->all();
    }

    protected function attention(?MajorProject $project): array
    {
        return Timesheet::query()
            ->with(['worker.company', 'majorProject'])
            ->when($project, fn ($q) => $q->where('major_project_id', $project->id))
            ->whereIn('status', [
                TimesheetStatus::Draft->value,
                TimesheetStatus::Submitted->value,
                TimesheetStatus::Returned->value,
                TimesheetStatus::Rejected->value,
                TimesheetStatus::ManagerApproved->value,
            ])
            ->latest('updated_at')
            ->limit(10)
            ->get()
            ->map(function (Timesheet $sheet) {
                [$issue, $issueStatus] = $this->issueFor($sheet);

                return [
                    'id' => $sheet->id,
                    'worker_name' => $sheet->worker?->full_name ?? 'Unknown',
                    'worker_id' => $sheet->worker?->employee_id,
                    'company' => $sheet->worker?->company?->name ?? '—',
                    'project' => $sheet->majorProject?->name ?? '—',
                    'issue' => $issue,
                    'due_date' => $sheet->due_date?->toDateString(),
                    'status' => $issueStatus,
                    'timesheet_status' => $sheet->status?->value,
                ];
            })->values()->all();
    }

    protected function issueFor(Timesheet $sheet): array
    {
        return match ($sheet->status) {
            TimesheetStatus::Draft => ['Missing timesheet', 'missing'],
            TimesheetStatus::Returned => ['Returned for correction', 'action_required'],
            TimesheetStatus::Rejected => ['Rejected', 'rejected'],
            TimesheetStatus::ManagerApproved => ['Awaiting client approval', 'pending'],
            TimesheetStatus::Submitted => (
                $sheet->due_date && $sheet->due_date->isPast()
                    ? ['Overdue approval', 'overdue']
                    : ['Pending manager approval', 'pending']
            ),
            default => ['Needs attention', 'pending'],
        };
    }

    protected function missingByCompany(?MajorProject $project, int $missing): array
    {
        $weekStart = now()->startOfWeek()->toDateString();
        $weekEnd = now()->endOfWeek()->toDateString();

        $workers = Worker::query()
            ->with('company')
            ->where('timesheet_access', true)
            ->when($project, fn ($q) => $q->where('primary_project_id', $project->id))
            ->whereDoesntHave('timesheets', function ($q) use ($weekStart, $weekEnd) {
                $q->where('period_start', $weekStart)
                    ->where('period_end', $weekEnd)
                    ->whereNotIn('status', [TimesheetStatus::Draft->value]);
            })
            ->get();

        if ($workers->isEmpty() && $missing > 0) {
            return [
                ['company' => 'All companies', 'count' => $missing, 'pct' => 100],
            ];
        }

        $grouped = $workers->groupBy(fn (Worker $w) => $w->company?->name ?? 'Unknown');
        $total = max(1, $grouped->sum(fn (Collection $rows) => $rows->count()));

        return $grouped->map(fn (Collection $rows, string $name) => [
            'company' => $name,
            'count' => $rows->count(),
            'pct' => round(($rows->count() / $total) * 100),
        ])->sortByDesc('count')->values()->take(6)->all();
    }

    protected function approvalFlow(bool $clientRequired): array
    {
        return [
            [
                'key' => 'worker',
                'title' => 'Worker Submission',
                'state' => 'completed',
                'label' => 'Completed',
            ],
            [
                'key' => 'manager',
                'title' => 'Manager Approval',
                'state' => 'in_progress',
                'label' => 'In Progress',
            ],
            [
                'key' => 'client',
                'title' => 'Client Approval',
                'state' => $clientRequired ? 'pending' : 'disabled',
                'label' => $clientRequired ? 'Pending' : 'Optional / Disabled',
            ],
            [
                'key' => 'final',
                'title' => 'Final Approved',
                'state' => 'pending',
                'label' => 'Pending',
            ],
        ];
    }

    protected function recentActivity(?MajorProject $project): array
    {
        $fromSheets = Timesheet::query()
            ->with('worker')
            ->when($project, fn ($q) => $q->where('major_project_id', $project->id))
            ->whereNotNull('submitted_at')
            ->latest('updated_at')
            ->limit(8)
            ->get()
            ->map(function (Timesheet $sheet) {
                $status = $sheet->status?->label() ?? 'Updated';

                return [
                    'id' => 'ts-'.$sheet->id,
                    'description' => ($sheet->worker?->full_name ?? 'Worker')." — {$status}",
                    'at' => ($sheet->updated_at ?? $sheet->submitted_at)?->toIso8601String(),
                    'tone' => match ($sheet->status) {
                        TimesheetStatus::FullyApproved => 'success',
                        TimesheetStatus::Rejected, TimesheetStatus::Returned => 'danger',
                        TimesheetStatus::Submitted, TimesheetStatus::ManagerApproved => 'warning',
                        default => 'slate',
                    },
                ];
            });

        $fromActivities = WorkerActivity::query()
            ->when($project, fn ($q) => $q->whereHas(
                'worker',
                fn ($q) => $q->where('primary_project_id', $project->id)
            ))
            ->where('type', 'like', '%timesheet%')
            ->latest()
            ->limit(4)
            ->get()
            ->map(fn (WorkerActivity $activity) => [
                'id' => 'act-'.$activity->id,
                'description' => $activity->description,
                'at' => $activity->created_at?->toIso8601String(),
                'tone' => 'brand',
            ]);

        return $fromSheets->concat($fromActivities)
            ->sortByDesc('at')
            ->take(8)
            ->values()
            ->all();
    }

    protected function complianceBreakdown($periodQuery, int $expected, int $submitted, int $missing): array
    {
        $onTime = (clone $periodQuery)
            ->where('status', TimesheetStatus::FullyApproved->value)
            ->count();

        $late = (clone $periodQuery)
            ->whereIn('status', [
                TimesheetStatus::Submitted->value,
                TimesheetStatus::ManagerApproved->value,
                TimesheetStatus::Returned->value,
            ])
            ->count();

        return [
            ['name' => 'On Time', 'value' => $onTime, 'color' => '#16A34A'],
            ['name' => 'Late', 'value' => $late, 'color' => '#EA580C'],
            ['name' => 'Missing', 'value' => $missing, 'color' => '#DC2626'],
        ];
    }

    protected function bottleneckCount(?MajorProject $project): int
    {
        $managersWithPending = ProjectManagerLink::query()
            ->when($project, fn ($q) => $q->where('major_project_id', $project->id))
            ->whereHas('majorProject', function ($q) {
                $q->whereHas('timesheets', fn ($q) => $q->where('status', TimesheetStatus::Submitted->value));
            })
            ->count();

        return $managersWithPending ?: ProjectManagerLink::query()
            ->when($project, fn ($q) => $q->where('major_project_id', $project->id))
            ->count();
    }
}
