<?php

namespace App\Services\Schedule\Views;

use App\Models\Worker;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * The Calendar View: a two-week coverage grid (day shift, night shift, coverage
 * verdict per day) plus the context rail that explains where the numbers on the
 * grid come from — staffing matrix, arrivals/departures, turnover, and alerts.
 */
class ScheduleCalendarPresenter
{
    private const WEEKS = 2;

    /** Coverage below this share of the roster is called out as a gap. */
    private const COVERAGE_GAP_THRESHOLD = 85;

    /**
     * Rotations run fourteen days on, so a run past twelve days is the point the
     * lodge manager has to sign off the extra hours.
     */
    private const OVERTIME_AFTER_CONSECUTIVE_DAYS = 12;

    /** Overtime accrued per worker for each day worked beyond that run. */
    private const OVERTIME_HOURS_PER_WORKER = 0.5;

    public const SHIFT_ROWS = [
        ScheduleWorkforceProfile::SHIFT_DAY => ['label' => 'Day Shift', 'time' => '7a – 3p'],
        ScheduleWorkforceProfile::SHIFT_NIGHT => ['label' => 'Night Shift', 'time' => '3p – 11p'],
    ];

    public function __construct(private readonly ScheduleWorkforceProfile $profile)
    {
    }

    /**
     * @param  Collection<int, Worker>  $workers
     * @param  array<int, array<string, bool>>  $rostered  Rostered dates, padded either
     *                                                     side of the window so runs and
     *                                                     movements are not clipped.
     * @return array<string, mixed>
     */
    public function present(Collection $workers, ScheduleViewFilters $filters, array $rostered, int $specialRequests = 0, ?string $projectName = null): array
    {
        $start = $filters->calendarStart();
        $end = $start->copy()->addDays(self::WEEKS * 7 - 1);

        $roster = $this->roster($workers, $filters);
        $daily = $this->dailyCoverage($workers, $roster, $rostered, $start, $end);

        return [
            'range_label' => $this->rangeLabel($start, $end),
            'weeks' => $this->weeks($start, $daily),
            'rail' => [
                'title' => $this->rangeLabel($start, $end),
                'positions' => $this->staffingMatrix($workers, $rostered, $start, $end),
                'arrivals' => array_sum(array_column($daily, 'arrivals')),
                'departures' => array_sum(array_column($daily, 'departures')),
                'turnover' => $this->turnover($workers, $daily),
                'special_requests' => $specialRequests,
                'notes' => $projectName
                    ? "{$projectName} training in the afternoon."
                    : 'No notes recorded for this period.',
                'alerts' => $this->alerts($daily, $workers, $rostered, $start, $end),
            ],
        ];
    }

    public function rangeLabel(Carbon $start, Carbon $end): string
    {
        return $start->format('M j').' – '.$end->format('M j, Y');
    }

    /**
     * Roster size per shift: the workers who would be on that shift if every one
     * of them turned up. Coverage is measured against it.
     *
     * @param  Collection<int, Worker>  $workers
     * @return array<string, int>
     */
    private function roster(Collection $workers, ScheduleViewFilters $filters): array
    {
        $roster = array_fill_keys(array_keys(self::SHIFT_ROWS), 0);

        foreach ($workers as $worker) {
            if (! $filters->isAll($filters->department)
                && $this->profile->department($worker) !== $filters->department) {
                continue;
            }

            $shift = $this->profile->shift($worker);
            // On-call crews back up the day shift rather than forming their own row.
            $shift = $shift === ScheduleWorkforceProfile::SHIFT_ON_CALL
                ? ScheduleWorkforceProfile::SHIFT_DAY
                : $shift;

            $roster[$shift]++;
        }

        return $roster;
    }

    /**
     * @param  Collection<int, Worker>  $workers
     * @param  array<string, int>  $roster
     * @param  array<int, array<string, bool>>  $rostered
     * @return array<string, array<string, mixed>>
     */
    private function dailyCoverage(Collection $workers, array $roster, array $rostered, Carbon $start, Carbon $end): array
    {
        $daily = [];

        for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
            $key = $date->toDateString();
            $scheduled = array_fill_keys(array_keys(self::SHIFT_ROWS), 0);
            $bookedOff = 0;
            $arrivals = 0;
            $departures = 0;
            $overtimeHours = 0.0;

            foreach ($workers as $worker) {
                $onShift = $rostered[$worker->id][$key] ?? false;

                if (! $onShift) {
                    $cell = $this->profile->dayCell($worker, null, $key);
                    $bookedOff += $cell['status'] === ScheduleWorkforceProfile::STATUS_BOOKED_OFF ? 1 : 0;

                    continue;
                }

                $shift = $this->profile->shift($worker);
                $shift = $shift === ScheduleWorkforceProfile::SHIFT_ON_CALL
                    ? ScheduleWorkforceProfile::SHIFT_DAY
                    : $shift;
                $scheduled[$shift]++;

                $previous = $date->copy()->subDay()->toDateString();
                $next = $date->copy()->addDay()->toDateString();
                $arrivals += ($rostered[$worker->id][$previous] ?? false) ? 0 : 1;
                $departures += ($rostered[$worker->id][$next] ?? false) ? 0 : 1;

                if ($this->consecutiveDays($rostered[$worker->id] ?? [], $date) > self::OVERTIME_AFTER_CONSECUTIVE_DAYS) {
                    $overtimeHours += self::OVERTIME_HOURS_PER_WORKER;
                }
            }

            $daily[$key] = [
                'date' => $key,
                'shifts' => $this->shiftCells($scheduled, $roster),
                'booked_off' => $bookedOff,
                'arrivals' => $arrivals,
                'departures' => $departures,
                'overtime_hours' => round($overtimeHours, 1),
            ];

            $daily[$key]['coverage'] = $this->coverage($daily[$key]);
        }

        return $daily;
    }

    /**
     * @param  array<string, int>  $scheduled
     * @param  array<string, int>  $roster
     * @return array<string, array<string, mixed>>
     */
    private function shiftCells(array $scheduled, array $roster): array
    {
        $cells = [];

        foreach (self::SHIFT_ROWS as $shift => $meta) {
            $required = $roster[$shift] ?? 0;
            $count = $scheduled[$shift] ?? 0;
            $cells[$shift] = [
                'scheduled' => $count,
                'required' => $required,
                'percent' => $required > 0 ? (int) round(($count / $required) * 100) : 0,
            ];
        }

        return $cells;
    }

    /**
     * The single verdict shown on the coverage row. Gaps outrank overtime, which
     * outranks a heavy departure day; an unremarkable day just reads Good.
     *
     * @param  array<string, mixed>  $day
     * @return array{tone: string, label: string}
     */
    private function coverage(array $day): array
    {
        $worstPercent = min(array_column($day['shifts'], 'percent') ?: [100]);

        if ($worstPercent < self::COVERAGE_GAP_THRESHOLD) {
            return ['tone' => 'gap', 'label' => 'Coverage Gap'];
        }

        if ($day['overtime_hours'] > 0) {
            return ['tone' => 'overtime', 'label' => 'OT '.$this->hours($day['overtime_hours'])];
        }

        if ($day['departures'] >= 3) {
            return ['tone' => 'departures', 'label' => "Departures ({$day['departures']})"];
        }

        return ['tone' => 'good', 'label' => 'Good'];
    }

    /**
     * @param  array<string, array<string, mixed>>  $daily
     * @return list<array<string, mixed>>
     */
    private function weeks(Carbon $start, array $daily): array
    {
        $weeks = [];

        for ($week = 0; $week < self::WEEKS; $week++) {
            $days = [];

            for ($offset = 0; $offset < 7; $offset++) {
                $date = $start->copy()->addDays($week * 7 + $offset);
                $key = $date->toDateString();

                $days[] = [
                    'date' => $key,
                    'weekday' => $date->format('D'),
                    'day_label' => $date->format('M j'),
                    'is_today' => $date->isToday(),
                    'shifts' => $daily[$key]['shifts'] ?? [],
                    'booked_off' => $daily[$key]['booked_off'] ?? 0,
                    'coverage' => $daily[$key]['coverage'] ?? ['tone' => 'good', 'label' => 'Good'],
                ];
            }

            $weeks[] = ['days' => $days];
        }

        return $weeks;
    }

    /**
     * Required vs scheduled per position, averaged across the window — the same
     * shape the Staffing Matrix module publishes.
     *
     * @param  Collection<int, Worker>  $workers
     * @param  array<int, array<string, bool>>  $rostered
     * @return array<string, mixed>
     */
    private function staffingMatrix(Collection $workers, array $rostered, Carbon $start, Carbon $end): array
    {
        $days = max(1, (int) $start->diffInDays($end) + 1);
        $window = [];

        for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
            $window[$date->toDateString()] = true;
        }

        $rows = $workers
            ->groupBy(fn (Worker $worker) => $worker->position ?: 'Unassigned')
            ->map(function (Collection $group, string $position) use ($rostered, $days, $window) {
                $required = $group->count();
                $shifts = $group->sum(fn (Worker $worker) => count(array_intersect_key(
                    array_filter($rostered[$worker->id] ?? []),
                    $window,
                )));
                $scheduled = min($required, (int) round($shifts / $days));

                return [
                    'position' => $position,
                    'required' => $required,
                    'scheduled' => $scheduled,
                    'shortage' => max(0, $required - $scheduled),
                ];
            })
            ->sortByDesc('required')
            ->values()
            ->all();

        return [
            'rows' => $rows,
            'total' => [
                'required' => array_sum(array_column($rows, 'required')),
                'scheduled' => array_sum(array_column($rows, 'scheduled')),
                'shortage' => array_sum(array_column($rows, 'shortage')),
            ],
        ];
    }

    /**
     * Housekeeping turnover: rooms changing hands drive the extra housekeepers a
     * day needs, so a heavy arrival/departure day is flagged here.
     *
     * @param  Collection<int, Worker>  $workers
     * @param  array<string, array<string, mixed>>  $daily
     * @return array<string, mixed>
     */
    private function turnover(Collection $workers, array $daily): array
    {
        $housekeepers = $workers
            ->filter(fn (Worker $worker) => $this->profile->department($worker) === 'Housekeeping')
            ->count();
        $peakTurnover = max(array_map(
            fn (array $day) => $day['arrivals'] + $day['departures'],
            $daily,
        ) ?: [0]);

        // One housekeeper absorbs roughly four room turnovers in a shift.
        $impact = (int) ceil($peakTurnover / 4);

        return [
            'level' => $impact > 0 && $impact >= max(1, (int) round($housekeepers * 0.1))
                ? 'High turnover expected'
                : 'Turnover within normal range',
            'impact' => $impact > 0 ? "Impact: -{$impact} Housekeepers" : 'No additional cover required',
            'is_high' => $impact > 0,
        ];
    }

    /**
     * @param  array<string, array<string, mixed>>  $daily
     * @param  Collection<int, Worker>  $workers
     * @param  array<int, array<string, bool>>  $rostered
     * @return list<array<string, string>>
     */
    private function alerts(array $daily, Collection $workers, array $rostered, Carbon $start, Carbon $end): array
    {
        $gaps = count(array_filter($daily, fn (array $day) => $day['coverage']['tone'] === 'gap'));
        $overtime = round(array_sum(array_column($daily, 'overtime_hours')), 1);
        $openShifts = array_sum(array_column($this->staffingMatrix($workers, $rostered, $start, $end)['rows'], 'shortage'));

        $alerts = [];

        if ($gaps > 0) {
            $alerts[] = ['tone' => 'danger', 'label' => $gaps.' coverage '.($gaps === 1 ? 'gap' : 'gaps')];
        }

        if ($openShifts > 0) {
            $alerts[] = ['tone' => 'info', 'label' => $openShifts.' open '.($openShifts === 1 ? 'shift' : 'shifts')];
        }

        if ($overtime > 0) {
            $alerts[] = [
                'tone' => 'warning',
                'label' => 'Overtime ('.$this->hours($overtime).') requires Lodge Manager approval',
            ];
        }

        return $alerts;
    }

    /**
     * @param  array<string, bool>  $dates
     */
    private function consecutiveDays(array $dates, Carbon $date): int
    {
        $run = 0;
        $cursor = $date->copy();

        while (($dates[$cursor->toDateString()] ?? false) && $run < 30) {
            $run++;
            $cursor->subDay();
        }

        return $run;
    }

    private function hours(float $hours): string
    {
        return rtrim(rtrim(number_format($hours, 1), '0'), '.').'h';
    }
}
