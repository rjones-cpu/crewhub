<?php

namespace App\Services\Schedule;

use App\Enums\ScheduleDayType;
use App\Enums\ScheduleDraftStatus;
use App\Models\MajorProject;
use App\Models\User;
use App\Models\Worker;
use App\Models\WorkerScheduleDay;
use App\Models\WorkerScheduleDraft;
use App\Services\Workers\WorkerFeatureAccessService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Builds the schedule board: project tabs, the date axis, one row per
 * worker with a cell per day, draft overlay, and the two footer totals.
 */
class ScheduleBoardService
{
    /** Columns rendered in one payload; the grid scrolls horizontally through them. */
    public const COLUMN_COUNT = 50;

    /** Days of history kept to the left of today so recent rotation is visible. */
    private const DAYS_BEFORE_TODAY = 4;

    public function __construct(
        private readonly WorkerFeatureAccessService $featureAccess,
        private readonly ScheduleEditService $edits,
    ) {
    }

    public function board(?int $projectId, ?User $user = null): array
    {
        $projects = MajorProject::query()
            ->withCount(['workers' => fn ($query) => $query->where('schedule_access', true)])
            ->orderBy('name')
            ->get(['id', 'name', 'project_number', 'code', 'modules'])
            ->filter(fn (MajorProject $project) => $this->featureAccess->projectAllows($project, 'schedule'))
            ->values();

        // An unknown or out-of-scope project id falls back to the All Projects view.
        $selectedProjectId = $projects->contains('id', $projectId) ? $projectId : null;

        $start = Carbon::today()->subDays(self::DAYS_BEFORE_TODAY);
        $end = $start->copy()->addDays(self::COLUMN_COUNT - 1);

        $days = $this->days($start, $end);
        $dates = $days->pluck('date');

        $projectIds = $selectedProjectId ? [$selectedProjectId] : $projects->pluck('id')->all();

        $workers = Worker::query()
            ->with(['latestAccommodation', 'primaryProject:id,name'])
            ->whereIn('primary_project_id', $projectIds)
            ->where('schedule_access', true)
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        $cellsByWorker = $this->cellsByWorker($workers->pluck('id'), $projectIds, $start, $end);
        $draftsByWorker = $this->draftsByWorker($workers->pluck('id'), $projectIds);
        $displayed = $this->overlayDrafts($cellsByWorker, $draftsByWorker);

        return [
            'projects' => $projects->map(fn (MajorProject $project) => [
                'id' => $project->id,
                'name' => $project->name,
                'reference' => $project->project_number ?: $project->code,
                'worker_count' => $project->workers_count,
            ])->values()->all(),
            'selectedProjectId' => $selectedProjectId,
            'totalWorkerCount' => $projects->sum('workers_count'),
            'days' => $days->all(),
            'rows' => $this->rows($workers, $displayed, $dates, $draftsByWorker),
            'totals' => $this->totals($displayed, $dates),
            'drafts' => $this->edits->draftSummaries($selectedProjectId),
            'requests' => $this->edits->requestSummaries($selectedProjectId),
            'canEdit' => $user?->isSuperAdmin() || (bool) $user?->role?->canManageWorkforce(),
        ];
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function days(Carbon $start, Carbon $end): Collection
    {
        $today = Carbon::today()->toDateString();
        $days = collect();

        for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
            $days->push([
                'date' => $date->toDateString(),
                // Split for the stacked column header: THU / AUG / 13.
                'weekday' => strtoupper($date->format('D')),
                'month' => strtoupper($date->format('M')),
                'day' => $date->format('j'),
                'label' => strtoupper($date->format('M j')),
                'is_today' => $date->toDateString() === $today,
                'is_weekend' => $date->isWeekend(),
            ]);
        }

        return $days;
    }

    /**
     * Day types keyed by worker then date. A worker scheduled on more than one
     * project on the same date keeps the most significant type, so the All Projects
     * view never blanks out a day that is worked somewhere.
     *
     * @param  Collection<int, int>  $workerIds
     * @param  list<int>  $projectIds
     * @return array<int, array<string, array{type: string, needs_room: bool}>>
     */
    private function cellsByWorker(Collection $workerIds, array $projectIds, Carbon $start, Carbon $end): array
    {
        if ($workerIds->isEmpty() || $projectIds === []) {
            return [];
        }

        $priority = [
            ScheduleDayType::Work->value => 3,
            ScheduleDayType::Travel->value => 2,
            ScheduleDayType::Off->value => 1,
        ];

        $cells = [];

        WorkerScheduleDay::query()
            ->whereIn('worker_id', $workerIds)
            ->whereIn('major_project_id', $projectIds)
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->orderBy('date')
            ->each(function (WorkerScheduleDay $day) use (&$cells, $priority): void {
                $date = $day->date->toDateString();
                $type = $day->day_type->value;
                $existing = $cells[$day->worker_id][$date] ?? null;

                if ($existing && $priority[$existing['type']] >= $priority[$type]) {
                    return;
                }

                $cells[$day->worker_id][$date] = [
                    'type' => $type,
                    'needs_room' => $day->needs_room,
                ];
            });

        return $cells;
    }

    /**
     * @param  Collection<int, Worker>  $workers
     * @param  array<int, array<string, array{type: string, needs_room: bool, pending?: bool}>>  $cellsByWorker
     * @param  array<int, array<string, array{type: string, needs_room: bool}>>  $draftsByWorker
     * @param  Collection<int, string>  $dates
     * @return list<array<string, mixed>>
     */
    private function rows(Collection $workers, array $cellsByWorker, Collection $dates, array $draftsByWorker = []): array
    {
        return $workers->map(function (Worker $worker) use ($cellsByWorker, $dates, $draftsByWorker) {
            $cells = $cellsByWorker[$worker->id] ?? [];

            return [
                'id' => $worker->id,
                'project_id' => $worker->primary_project_id,
                'first_name' => $worker->first_name,
                'last_name' => $worker->last_name,
                'full_name' => $worker->full_name,
                'position' => $worker->position,
                'company' => $worker->primaryProject?->name ?? 'Unassigned',
                'app_status' => $worker->email ? 'connected' : 'not_connected',
                'accommodation' => $this->accommodationStatus($worker),
                // Aligned with the `days` axis so the grid can render by index.
                'cells' => $dates
                    ->map(fn (string $date) => $cells[$date]['type'] ?? ScheduleDayType::Off->value)
                    ->all(),
                'pending' => $dates
                    ->map(fn (string $date) => (bool) ($cells[$date]['pending'] ?? isset($draftsByWorker[$worker->id][$date])))
                    ->all(),
                'needs_room' => $dates
                    ->map(fn (string $date) => (bool) ($cells[$date]['needs_room'] ?? false))
                    ->all(),
            ];
        })->values()->all();
    }

    /**
     * Reservation status shown in the Accommodation Status column.
     *
     * @return array{value: string, label: string}
     */
    private function accommodationStatus(Worker $worker): array
    {
        $reference = $worker->latestAccommodation
            ? 'BK'.str_pad((string) $worker->latestAccommodation->id, 8, '0', STR_PAD_LEFT)
            : null;

        return match ($worker->latestAccommodation?->status) {
            'checked_in' => ['value' => 'in_house', 'label' => 'In House', 'reference' => $reference],
            'reserved' => ['value' => 'arriving', 'label' => 'Arrivals', 'reference' => $reference],
            'checked_out' => ['value' => 'check_out', 'label' => 'Check Out', 'reference' => $reference],
            default => ['value' => 'not_booked', 'label' => 'Not Booked', 'reference' => null],
        };
    }

    /**
     * Distinct worker counts per date: beds occupied, and everyone on the project.
     *
     * @param  array<int, array<string, array{type: string, needs_room: bool}>>  $cellsByWorker
     * @param  Collection<int, string>  $dates
     * @return array{in_lodge: list<int>, project_personnel: list<int>}
     */
    private function totals(array $cellsByWorker, Collection $dates): array
    {
        $inLodge = array_fill_keys($dates->all(), 0);
        $personnel = $inLodge;

        foreach ($cellsByWorker as $cells) {
            foreach ($cells as $date => $cell) {
                if (! array_key_exists($date, $personnel)) {
                    continue;
                }

                if ($cell['type'] === ScheduleDayType::Off->value) {
                    continue;
                }

                $personnel[$date]++;

                if ($cell['needs_room']) {
                    $inLodge[$date]++;
                }
            }
        }

        return [
            'in_lodge' => array_values($inLodge),
            'project_personnel' => array_values($personnel),
        ];
    }

    /**
     * @param  Collection<int, int>  $workerIds
     * @param  list<int>  $projectIds
     * @return array<int, array<string, array{type: string, needs_room: bool}>>
     */
    private function draftsByWorker(Collection $workerIds, array $projectIds): array
    {
        if ($workerIds->isEmpty() || $projectIds === []) {
            return [];
        }

        $drafts = [];

        WorkerScheduleDraft::query()
            ->with('days')
            ->whereIn('worker_id', $workerIds)
            ->whereIn('major_project_id', $projectIds)
            ->where('status', ScheduleDraftStatus::Pending)
            ->each(function (WorkerScheduleDraft $draft) use (&$drafts): void {
                foreach ($draft->days as $day) {
                    $drafts[$draft->worker_id][$day->date->toDateString()] = [
                        'type' => $day->to_type->value,
                        'needs_room' => $day->needs_room,
                    ];
                }
            });

        return $drafts;
    }

    /**
     * @param  array<int, array<string, array{type: string, needs_room: bool}>>  $cellsByWorker
     * @param  array<int, array<string, array{type: string, needs_room: bool}>>  $draftsByWorker
     * @return array<int, array<string, array{type: string, needs_room: bool, pending: bool}>>
     */
    private function overlayDrafts(array $cellsByWorker, array $draftsByWorker): array
    {
        foreach ($draftsByWorker as $workerId => $days) {
            foreach ($days as $date => $cell) {
                $cellsByWorker[$workerId][$date] = [
                    'type' => $cell['type'],
                    'needs_room' => $cell['needs_room'],
                    'pending' => true,
                ];
            }
        }

        return $cellsByWorker;
    }
}
