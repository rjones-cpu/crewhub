<?php

namespace App\Services\Schedule\Views;

use Illuminate\Support\Carbon;

/**
 * The five headline cards above the List and Calendar views.
 */
class ScheduleKpiPresenter
{
    public function __construct(private readonly ScheduleRosterRepository $roster)
    {
    }

    /**
     * @param  array<int, array<string, bool>>  $rostered  Keyed worker → date → rostered.
     * @return list<array<string, mixed>>
     */
    public function present(array $rostered, Carbon $start, Carbon $end, int $issues): array
    {
        $today = Carbon::today();
        $yesterday = $today->copy()->subDay()->toDateString();

        $scheduled = $this->countOnDates($rostered, $start, $end);
        $onsiteToday = $this->countOnDate($rostered, $today->toDateString());
        $onsiteYesterday = $this->countOnDate($rostered, $yesterday);

        $arrivals = $this->roster->arrivalsByDate($rostered, $start, $end);
        $departures = $this->roster->departuresByDate($rostered, $start, $end);

        return [
            [
                'key' => 'workers_scheduled',
                'label' => 'Workers Scheduled',
                'value' => $scheduled,
                'hint' => $this->delta($onsiteToday - $onsiteYesterday, 'vs yesterday'),
                'tone' => 'brand',
                'icon' => 'Users',
            ],
            [
                'key' => 'onsite_today',
                'label' => 'Onsite Today',
                'value' => $onsiteToday,
                'hint' => $scheduled > 0
                    ? number_format(($onsiteToday / $scheduled) * 100, 1).'% of scheduled'
                    : 'No workers scheduled',
                'tone' => 'success',
                'icon' => 'Clock',
            ],
            [
                'key' => 'arrivals',
                'label' => 'Arrivals (7 Days)',
                'value' => array_sum($arrivals),
                'hint' => $this->nextMovement($arrivals),
                'tone' => 'brand',
                'icon' => 'PlaneLanding',
            ],
            [
                'key' => 'departures',
                'label' => 'Departures (7 Days)',
                'value' => array_sum($departures),
                'hint' => $this->nextMovement($departures),
                'tone' => 'journey',
                'icon' => 'PlaneTakeoff',
            ],
            [
                'key' => 'issues',
                'label' => 'Schedule Issues',
                'value' => $issues,
                'hint' => $issues > 0 ? 'View issues' : 'No open issues',
                'hint_is_link' => $issues > 0,
                'tone' => 'danger',
                'icon' => 'TriangleAlert',
            ],
        ];
    }

    /**
     * @param  array<int, array<string, bool>>  $rostered
     */
    private function countOnDate(array $rostered, string $date): int
    {
        $count = 0;

        foreach ($rostered as $dates) {
            $count += ($dates[$date] ?? false) ? 1 : 0;
        }

        return $count;
    }

    /**
     * Distinct workers rostered at least once inside the window.
     *
     * @param  array<int, array<string, bool>>  $rostered
     */
    private function countOnDates(array $rostered, Carbon $start, Carbon $end): int
    {
        $count = 0;

        foreach ($rostered as $dates) {
            foreach ($dates as $date => $isRostered) {
                if ($isRostered && $date >= $start->toDateString() && $date <= $end->toDateString()) {
                    $count++;

                    break;
                }
            }
        }

        return $count;
    }

    /**
     * @param  array<string, int>  $byDate
     */
    private function nextMovement(array $byDate): string
    {
        foreach ($byDate as $date => $count) {
            if ($count > 0) {
                return 'Next: '.$count.' on '.Carbon::parse($date)->format('M j');
            }
        }

        return 'None in this window';
    }

    private function delta(int $delta, string $suffix): string
    {
        if ($delta === 0) {
            return "No change {$suffix}";
        }

        return ($delta > 0 ? '+' : '').$delta." {$suffix}";
    }
}
