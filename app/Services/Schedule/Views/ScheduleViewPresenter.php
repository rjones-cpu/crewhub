<?php

namespace App\Services\Schedule\Views;

use App\Models\MajorProject;
use App\Models\ScheduleModificationRequest;
use App\Models\User;
use App\Models\Worker;
use App\Services\Workers\WorkerFeatureAccessService;
use Illuminate\Support\Collection;

/**
 * Assembles everything the Schedule page renders around the board: the KPI
 * strip, the filter options, and the List, Calendar, and Change Request views.
 */
class ScheduleViewPresenter
{
    /** Shift options offered in the filter bar. */
    private const SHIFT_OPTIONS = [
        ScheduleWorkforceProfile::SHIFT_DAY => 'Day Shift',
        ScheduleWorkforceProfile::SHIFT_NIGHT => 'Night Shift',
        ScheduleWorkforceProfile::SHIFT_ON_CALL => 'On Call / Rotational',
    ];

    /** Worker status options offered in the calendar filter bar. */
    private const STATUS_OPTIONS = [
        ScheduleWorkforceProfile::SHIFT_DAY => 'Day Shift',
        ScheduleWorkforceProfile::SHIFT_NIGHT => 'Night Shift',
        ScheduleWorkforceProfile::SHIFT_ON_CALL => 'On Call / Rotational',
        ScheduleWorkforceProfile::STATUS_BOOKED_OFF => 'Booked Off',
        ScheduleWorkforceProfile::STATUS_UNAVAILABLE => 'Unavailable',
        ScheduleWorkforceProfile::STATUS_OFF => 'Off',
    ];

    public function __construct(
        private readonly ScheduleWorkforceProfile $profile,
        private readonly ScheduleRosterRepository $roster,
        private readonly ScheduleKpiPresenter $kpis,
        private readonly ScheduleListPresenter $list,
        private readonly ScheduleCalendarPresenter $calendar,
        private readonly ScheduleChangeRequestPresenter $requests,
        private readonly WorkerFeatureAccessService $featureAccess,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function present(ScheduleViewFilters $filters, ?User $user = null): array
    {
        $workers = $this->workers($filters);
        $project = $filters->projectId ? MajorProject::query()->find($filters->projectId) : null;

        $listStart = $filters->listStart();
        $listEnd = $listStart->copy()->addDays(6);
        $calendarStart = $filters->calendarStart();
        $calendarEnd = $calendarStart->copy()->addDays(13);

        // One read covering both windows, padded for run and movement detection.
        $rangeStart = ($listStart->lt($calendarStart) ? $listStart : $calendarStart)->copy()->subDays(7);
        $rangeEnd = ($listEnd->gt($calendarEnd) ? $listEnd : $calendarEnd)->copy()->addDay();
        $rostered = $this->roster->rosteredDates($workers->pluck('id'), $rangeStart, $rangeEnd);

        $calendar = $this->calendar->present(
            $workers,
            $filters,
            $rostered,
            $this->pendingModifications($filters),
            $project?->name,
        );

        return [
            'view' => $filters->view,
            'filters' => $filters->toArray(),
            'filterOptions' => $this->filterOptions($workers),
            'timezoneLabel' => $this->timezoneLabel(),
            'kpis' => $this->kpis->present(
                $rostered,
                $listStart,
                $listEnd,
                $this->issueCount($calendar),
            ),
            'listView' => $this->list->present($workers, $filters),
            'calendarView' => $calendar,
            'changeRequests' => $this->requests->present($workers, $filters, $user?->name),
        ];
    }

    /**
     * Workers on the schedule for the selected project, or every project the
     * viewer can see when no project is selected.
     *
     * @return Collection<int, Worker>
     */
    private function workers(ScheduleViewFilters $filters): Collection
    {
        $projectIds = MajorProject::query()
            ->orderBy('name')
            ->get(['id', 'name', 'modules'])
            ->filter(fn (MajorProject $project) => $this->featureAccess->projectAllows($project, 'schedule'))
            ->pluck('id');

        $selected = $filters->projectId && $projectIds->contains($filters->projectId)
            ? [$filters->projectId]
            : $projectIds->all();

        return Worker::query()
            ->with('primaryProject:id,name')
            ->whereIn('primary_project_id', $selected)
            ->where('schedule_access', true)
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();
    }

    /**
     * @param  Collection<int, Worker>  $workers
     * @return array<string, list<array{value: string, label: string}>>
     */
    private function filterOptions(Collection $workers): array
    {
        $departments = $workers
            ->map(fn (Worker $worker) => $this->profile->department($worker))
            ->unique()
            ->sort()
            ->values()
            ->map(fn (string $department) => ['value' => $department, 'label' => $department])
            ->all();

        return [
            'departments' => $departments,
            'shifts' => $this->options(self::SHIFT_OPTIONS),
            'statuses' => $this->options(self::STATUS_OPTIONS),
            'requestStatuses' => [
                ['value' => ScheduleChangeRequestPresenter::STATUS_PENDING, 'label' => 'Pending Approval'],
                ['value' => ScheduleChangeRequestPresenter::STATUS_OVERTIME_PENDING, 'label' => 'Overtime Pending'],
                ['value' => ScheduleChangeRequestPresenter::STATUS_APPROVED, 'label' => 'Approved'],
                ['value' => ScheduleChangeRequestPresenter::STATUS_REJECTED, 'label' => 'Rejected'],
            ],
            'requestTypes' => $this->requests->typeOptions(),
        ];
    }

    /**
     * @param  array<string, string>  $map
     * @return list<array{value: string, label: string}>
     */
    private function options(array $map): array
    {
        return collect($map)
            ->map(fn (string $label, string $value) => ['value' => $value, 'label' => $label])
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $calendar
     */
    private function issueCount(array $calendar): int
    {
        $gaps = collect($calendar['weeks'])
            ->flatMap(fn (array $week) => $week['days'])
            ->where('coverage.tone', 'gap')
            ->count();

        return $gaps + (int) ($calendar['rail']['positions']['total']['shortage'] ?? 0);
    }

    private function pendingModifications(ScheduleViewFilters $filters): int
    {
        return ScheduleModificationRequest::query()
            ->when($filters->projectId, fn ($query) => $query->where('major_project_id', $filters->projectId))
            ->where('status', 'pending')
            ->count();
    }

    private function timezoneLabel(): string
    {
        $timezone = config('app.timezone', 'UTC');
        $abbreviation = now()->timezone($timezone)->format('T');

        return "{$timezone} ({$abbreviation})";
    }
}
