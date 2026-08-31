<?php

namespace App\Services\Dashboard;

use App\Models\Accommodation;
use App\Models\AccommodationAssignment;
use App\Models\Journey;
use App\Models\MajorProject;
use App\Models\PriorityAction;
use App\Models\ScheduleForecast;
use App\Models\Timesheet;
use App\Models\Worker;
use App\Models\WorkerActivity;
use App\Models\WorkerReadiness;
use App\Services\ServiceRating\CompanyCommandRatingService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class DashboardService
{
    public function __construct(private readonly CompanyCommandRatingService $scorecards)
    {
    }

    public function overview(?int $projectId = null): array
    {
        $workers = Worker::query()->when($projectId, fn ($q) => $q->where('primary_project_id', $projectId));
        $readiness = WorkerReadiness::query()->when(
            $projectId,
            fn ($q) => $q->whereHas('worker', fn ($q) => $q->where('primary_project_id', $projectId))
        );

        $totalWorkers = (clone $workers)->count();
        $activeWorkers = (clone $workers)->where('status', 'active')->count();
        $readyWorkers = (clone $readiness)->where('overall_status', 'ready')->count();
        $readyPct = $totalWorkers > 0 ? round(($readyWorkers / $totalWorkers) * 100) : 0;

        $forecastRows = ScheduleForecast::query()
            ->when($projectId, fn ($q) => $q->where('major_project_id', $projectId))
            ->whereBetween('forecast_date', [today(), today()->addDays(13)])
            ->orderBy('forecast_date')
            ->get()
            ->groupBy(fn ($row) => $row->forecast_date->toDateString())
            ->map(function ($rows, $date) {
                $required = (int) $rows->sum('required_workers');
                $scheduled = (int) $rows->sum('scheduled_workers');

                return [
                    'date' => Carbon::parse($date)->format('M d'),
                    'forecast_date' => $date,
                    'required' => $required,
                    'scheduled' => $scheduled,
                    'gap' => max($required - $scheduled, 0),
                ];
            })
            ->values();

        $required = (int) $forecastRows->sum('required');
        $scheduled = (int) $forecastRows->sum('scheduled');
        $coveragePct = $required > 0 ? round(($scheduled / $required) * 100) : 100;

        // Compare the back half of the forecast window against the front half so the
        // KPI trend reflects real data (no historical snapshots are stored).
        $coverageOf = function ($rows): ?int {
            $required = (int) $rows->sum('required');

            return $required > 0 ? (int) round(($rows->sum('scheduled') / $required) * 100) : null;
        };
        $earlyCoverage = $coverageOf($forecastRows->take(7));
        $lateCoverage = $coverageOf($forecastRows->slice(7));
        $coverageDelta = ($earlyCoverage !== null && $lateCoverage !== null)
            ? $lateCoverage - $earlyCoverage
            : null;

        $journeys = Journey::query()->when($projectId, fn ($q) => $q->where('major_project_id', $projectId));

        $journeysDue = (clone $journeys)
            ->whereIn('status', ['pending', 'approved'])
            ->whereBetween('departure_at', [now(), now()->addHours(48)])
            ->count();

        $journeysPrior48h = (clone $journeys)
            ->whereBetween('departure_at', [now()->subHours(48), now()])
            ->count();

        $journeysNext7d = (clone $journeys)
            ->whereIn('status', ['pending', 'approved'])
            ->whereBetween('departure_at', [now(), now()->addDays(7)])
            ->count();

        $journeysTraveling = (clone $journeys)->where('status', 'in_transit')->count();

        $accommodations = Accommodation::query()
            ->when($projectId, fn ($q) => $q->where('major_project_id', $projectId));
        $capacity = (int) (clone $accommodations)->sum('capacity');
        $occupied = (int) (clone $accommodations)->sum('occupied');
        $availableBeds = max($capacity - $occupied, 0);
        $occupancyPct = $capacity > 0 ? round(($occupied / $capacity) * 100) : 0;

        $pendingTimesheets = Timesheet::query()
            ->when($projectId, fn ($q) => $q->where('major_project_id', $projectId))
            ->whereIn('status', ['submitted', 'manager_approved'])
            ->count();

        $approvedTimesheets = Timesheet::query()
            ->when($projectId, fn ($q) => $q->where('major_project_id', $projectId))
            ->where('status', 'fully_approved')
            ->count();

        // Scoped by the period worked rather than `approved_at`, which is only set
        // when a timesheet is approved through the app.
        $approvedRecently = Timesheet::query()
            ->when($projectId, fn ($q) => $q->where('major_project_id', $projectId))
            ->where('status', 'fully_approved')
            ->where('period_end', '>=', now()->subDays(14))
            ->count();

        $rejectedTimesheets = Timesheet::query()
            ->when($projectId, fn ($q) => $q->where('major_project_id', $projectId))
            ->where('status', 'rejected')
            ->count();

        $reviewedTimesheets = $approvedTimesheets + $rejectedTimesheets + $pendingTimesheets;
        $approvalPct = $reviewedTimesheets > 0
            ? round(($approvedTimesheets / $reviewedTimesheets) * 100)
            : 0;

        $mobilizing = (clone $workers)->where('status', 'mobilizing')->count();
        $demobilizing = (clone $workers)->where('status', 'demobilizing')->count();

        // Reservation confidence, which is what the header KPI reports, is a
        // different question from bed occupancy computed above.
        $bookings = AccommodationAssignment::query()
            ->when($projectId, fn ($q) => $q->whereHas(
                'accommodation',
                fn ($q) => $q->where('major_project_id', $projectId)
            ))
            ->where(fn ($q) => $q->whereNull('check_out')->orWhere('check_out', '>=', today()));
        $totalBookings = (clone $bookings)->count();
        $confirmedBookings = (clone $bookings)->whereIn('status', ['reserved', 'checked_in'])->count();
        $confirmedPct = $totalBookings > 0 ? (int) round(($confirmedBookings / $totalBookings) * 100) : 100;

        $scorecard = $this->scorecards->overview($projectId);
        $projectRows = collect($scorecard['projects']);
        $projectsAtRisk = $projectRows
            ->filter(fn ($row) => in_array($row['grade'], ['C', 'D'], true)
                || in_array($row['trend'], ['declining', 'critical'], true))
            ->count();

        $activeProjects = MajorProject::query()
            ->when($projectId, fn ($q) => $q->where('id', $projectId))
            ->where('status', 'active')
            ->count();

        return [
            'meta' => [
                'timezone' => config('app.timezone'),
                'generated_at' => now()->format('g:i A'),
                'range_start' => today()->format('M j'),
                'range_end' => today()->addDays(13)->format('M j, Y'),
            ],
            'kpis' => [
                'company_grade' => $scorecard['company']['grade'],
                'company_grade_label' => $scorecard['company']['label'],
                'company_grade_status' => $scorecard['company']['status'],
                'major_projects' => $activeProjects,
                'projects_at_risk' => $projectsAtRisk,
                'accommodation_confirmed_pct' => $confirmedPct,
                'active_workers' => $activeWorkers,
                'total_workers' => $totalWorkers,
                'forecast_coverage' => $coveragePct,
                'forecast_coverage_delta' => $coverageDelta,
                'forecast_coverage_label' => "{$scheduled} / {$required} scheduled",
                'ready_workforce' => $readyWorkers,
                'ready_workforce_pct' => $readyPct,
                'journeys_due_48h' => $journeysDue,
                'journeys_due_48h_delta' => $journeysDue - $journeysPrior48h,
                'accommodation_occupied' => $occupied,
                'accommodation_capacity' => $capacity,
                'accommodation_available' => $availableBeds,
                'accommodation_pct' => $occupancyPct,
                'timesheets_pending' => $pendingTimesheets,
                'timesheets_approved' => $approvedTimesheets,
                'timesheets_rejected' => $rejectedTimesheets,
                'timesheets_approval_pct' => $approvalPct,
            ],
            'forecast' => $forecastRows,
            'scorecard' => $scorecard['company'],
            'scorecardSummary' => $scorecard['summary'],
            'projectPerformance' => $scorecard['projects'],
            'mobilizations' => $this->upcomingMobilizations($projectId),
            'priorityActions' => PriorityAction::query()
                ->with('majorProject')
                ->when($projectId, fn ($q) => $q->where('major_project_id', $projectId))
                ->whereNot('status', 'resolved')
                ->orderByRaw("CASE severity WHEN 'critical' THEN 1 WHEN 'high' THEN 2 WHEN 'medium' THEN 3 WHEN 'low' THEN 4 ELSE 5 END")
                ->limit(5)
                ->get()
                ->map(fn ($action) => [
                    'id' => $action->id,
                    'issue' => $action->issue ?: $action->title,
                    'title' => $action->title,
                    'affected' => $action->affected_count,
                    'affected_label' => $this->affectedLabel($action),
                    'owner' => $action->owner_name,
                    'due_date' => optional($action->due_date)?->format('M d, Y'),
                    'status' => $action->status,
                    'severity' => $action->severity,
                    'project' => $action->majorProject?->name,
                ]),
            'summaries' => [
                'mobilization' => [
                    'title' => 'Mobilization & Demobilization',
                    'items' => [
                        ['label' => 'Upcoming (7 days)', 'value' => $journeysNext7d],
                        ['label' => 'Mobilizing', 'value' => $mobilizing],
                        ['label' => 'Demobilizing', 'value' => $demobilizing],
                    ],
                ],
                'readiness' => [
                    'title' => 'Readiness & Journey',
                    'items' => [
                        ['label' => 'Ready to travel', 'value' => $readyWorkers],
                        ['label' => 'Journeys due (48h)', 'value' => $journeysDue, 'tone' => 'danger'],
                        ['label' => 'Traveling', 'value' => $journeysTraveling, 'tone' => 'success'],
                    ],
                ],
                'accommodation' => [
                    'title' => 'Accommodation',
                    'items' => [
                        ['label' => 'Site capacity', 'value' => $capacity],
                        ['label' => 'Occupied', 'value' => $occupied],
                        [
                            'label' => 'Availability',
                            'value' => $availableBeds.' ('.max(100 - $occupancyPct, 0).'%)',
                            'tone' => 'success',
                        ],
                    ],
                ],
                'timesheets' => [
                    'title' => 'Timesheets & Approvals',
                    'items' => [
                        ['label' => 'Pending approval', 'value' => $pendingTimesheets, 'tone' => 'danger'],
                        ['label' => 'Rejected', 'value' => $rejectedTimesheets, 'tone' => 'danger'],
                        ['label' => 'Approved (14 days)', 'value' => $approvedRecently, 'tone' => 'success'],
                    ],
                ],
                'system' => [
                    'title' => 'System Health',
                    'items' => [
                        ['label' => 'Platform status', 'value' => 'Operational', 'tone' => 'success'],
                        ['label' => 'Open actions', 'value' => PriorityAction::query()->whereNot('status', 'resolved')->count()],
                        ['label' => 'Last sync', 'value' => now()->format('H:i')],
                    ],
                ],
            ],
            'recentActivity' => WorkerActivity::query()
                ->with('worker')
                ->latest()
                ->limit(6)
                ->get()
                ->map(fn ($activity) => [
                    'id' => $activity->id,
                    'worker' => $activity->worker?->full_name,
                    'type' => $activity->type,
                    'description' => $activity->description,
                    'created_at' => $activity->created_at?->diffForHumans(),
                ]),
        ];
    }

    /**
     * Priority actions carry a bare count, so the unit is inferred from the wording
     * to keep the dashboard readable ("326 timesheets" rather than "326").
     */
    private function affectedLabel(PriorityAction $action): string
    {
        $count = number_format($action->affected_count);
        $text = strtolower($action->issue.' '.$action->title);

        $unit = match (true) {
            str_contains($text, 'timesheet') => 'timesheets',
            str_contains($text, 'shift'), str_contains($text, 'schedule gap') => 'shifts',
            str_contains($text, 'journey') => 'journeys',
            str_contains($text, 'bed'), str_contains($text, 'accommodation') => 'beds',
            default => 'workers',
        };

        return "{$count} {$unit}";
    }

    /**
     * Mobilisation journeys collapsed into one row per project per departure day,
     * which is how crews are actually moved and how the dashboard lists them.
     */
    private function upcomingMobilizations(?int $projectId, int $limit = 5): Collection
    {
        return Journey::query()
            ->with('majorProject')
            ->where('type', 'mobilization')
            ->whereNot('status', 'cancelled')
            ->whereBetween('departure_at', [now(), now()->addDays(30)])
            ->when($projectId, fn ($q) => $q->where('major_project_id', $projectId))
            ->orderBy('departure_at')
            ->get()
            ->groupBy(fn (Journey $journey) => $journey->departure_at->toDateString()
                .'-'.$journey->major_project_id)
            ->map(fn (Collection $group) => [
                'id' => $group->first()->id,
                'date' => $group->first()->departure_at->format('M j'),
                'project' => $group->first()->majorProject?->name ?? 'Unassigned',
                'workers' => $group->count(),
                'status' => 'Mobilizing',
            ])
            ->values()
            ->take($limit);
    }
}
