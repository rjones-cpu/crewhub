<?php

namespace App\Services\Schedule\Views;

use App\Enums\ScheduleDayType;
use App\Models\WorkerScheduleDay;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Reads rostered days once per request and hands the same map to the KPI strip,
 * the calendar grid, and the arrival/departure counters.
 */
class ScheduleRosterRepository
{
    /**
     * Rostered (non-off) dates keyed by worker then date.
     *
     * @param  Collection<int, int>  $workerIds
     * @return array<int, array<string, bool>>
     */
    public function rosteredDates(Collection $workerIds, Carbon $start, Carbon $end): array
    {
        if ($workerIds->isEmpty()) {
            return [];
        }

        $rostered = [];

        WorkerScheduleDay::query()
            ->whereIn('worker_id', $workerIds)
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->where('day_type', '!=', ScheduleDayType::Off->value)
            ->get(['worker_id', 'date'])
            ->each(function (WorkerScheduleDay $day) use (&$rostered): void {
                $rostered[$day->worker_id][$day->date->toDateString()] = true;
            });

        return $rostered;
    }

    /**
     * Workers whose run of rostered days starts on each date in the window.
     *
     * @param  array<int, array<string, bool>>  $rostered
     * @return array<string, int>
     */
    public function arrivalsByDate(array $rostered, Carbon $start, Carbon $end): array
    {
        return $this->transitionsByDate($rostered, $start, $end, -1);
    }

    /**
     * Workers whose run of rostered days ends on each date in the window.
     *
     * @param  array<int, array<string, bool>>  $rostered
     * @return array<string, int>
     */
    public function departuresByDate(array $rostered, Carbon $start, Carbon $end): array
    {
        return $this->transitionsByDate($rostered, $start, $end, 1);
    }

    /**
     * @param  array<int, array<string, bool>>  $rostered
     * @param  int  $direction  -1 looks at the previous day (arrivals), 1 at the next (departures).
     * @return array<string, int>
     */
    private function transitionsByDate(array $rostered, Carbon $start, Carbon $end, int $direction): array
    {
        $counts = [];

        for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
            $key = $date->toDateString();
            $neighbour = $date->copy()->addDays($direction)->toDateString();
            $counts[$key] = 0;

            foreach ($rostered as $dates) {
                if (($dates[$key] ?? false) && ! ($dates[$neighbour] ?? false)) {
                    $counts[$key]++;
                }
            }
        }

        return $counts;
    }
}
