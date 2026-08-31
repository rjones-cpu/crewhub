<?php

namespace App\Services\Schedule\Views;

use App\Enums\ScheduleDayType;
use App\Models\Worker;
use App\Models\WorkerScheduleDay;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * The List View: one row per worker, one cell per day of a seven-day window,
 * each cell showing the shift and hours the worker is rostered for.
 */
class ScheduleListPresenter
{
    public function __construct(private readonly ScheduleWorkforceProfile $profile)
    {
    }

    /**
     * @param  Collection<int, Worker>  $workers  Already scoped to the selected project.
     * @return array<string, mixed>
     */
    public function present(Collection $workers, ScheduleViewFilters $filters): array
    {
        $start = $filters->listStart();
        $end = $start->copy()->addDays(6);
        $days = $this->days($start, $end);

        $matching = $workers
            ->filter(fn (Worker $worker) => $this->matchesFilters($worker, $filters))
            ->values();

        $total = $matching->count();
        $lastPage = max(1, (int) ceil($total / $filters->perPage));
        $page = min($filters->page, $lastPage);
        $visible = $matching->slice(($page - 1) * $filters->perPage, $filters->perPage)->values();

        $dayTypes = $this->dayTypes($visible->pluck('id'), $start, $end);

        return [
            'range_label' => $this->rangeLabel($start, $end),
            'days' => $days,
            'rows' => $visible
                ->map(fn (Worker $worker) => $this->row($worker, $days, $dayTypes[$worker->id] ?? []))
                ->all(),
            'pagination' => [
                'from' => $total === 0 ? 0 : (($page - 1) * $filters->perPage) + 1,
                'to' => min($page * $filters->perPage, $total),
                'total' => $total,
                'current_page' => $page,
                'last_page' => $lastPage,
            ],
        ];
    }

    public function rangeLabel(Carbon $start, Carbon $end): string
    {
        $left = $start->format('M j');
        $right = $start->isSameMonth($end) ? $end->format('j, Y') : $end->format('M j, Y');

        return "{$left} – {$right}";
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function days(Carbon $start, Carbon $end): array
    {
        $today = Carbon::today()->toDateString();
        $days = [];

        for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
            $days[] = [
                'date' => $date->toDateString(),
                'label' => $date->format('D j'),
                'weekday' => $date->format('D'),
                'day' => $date->format('j'),
                'is_today' => $date->toDateString() === $today,
                'is_weekend' => $date->isWeekend(),
            ];
        }

        return $days;
    }

    /**
     * @param  list<array<string, mixed>>  $days
     * @param  array<string, ScheduleDayType>  $dayTypes
     * @return array<string, mixed>
     */
    private function row(Worker $worker, array $days, array $dayTypes): array
    {
        return [
            'id' => $worker->id,
            'name' => $worker->full_name,
            'position' => $worker->position ?: '—',
            'department' => $this->profile->department($worker),
            'project' => $worker->primaryProject?->name ?? 'Unassigned',
            'cells' => array_map(
                fn (array $day) => [
                    'date' => $day['date'],
                    ...$this->profile->dayCell($worker, $dayTypes[$day['date']] ?? null, $day['date']),
                ],
                $days,
            ),
        ];
    }

    private function matchesFilters(Worker $worker, ScheduleViewFilters $filters): bool
    {
        if (! $filters->isAll($filters->department)
            && $this->profile->department($worker) !== $filters->department) {
            return false;
        }

        if (! $filters->isAll($filters->shift)
            && $this->profile->shift($worker) !== $filters->shift) {
            return false;
        }

        return true;
    }

    /**
     * @param  Collection<int, int>  $workerIds
     * @return array<int, array<string, ScheduleDayType>>
     */
    private function dayTypes(Collection $workerIds, Carbon $start, Carbon $end): array
    {
        if ($workerIds->isEmpty()) {
            return [];
        }

        $types = [];

        WorkerScheduleDay::query()
            ->whereIn('worker_id', $workerIds)
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->get(['worker_id', 'date', 'day_type'])
            ->each(function (WorkerScheduleDay $day) use (&$types): void {
                $date = $day->date->toDateString();
                $existing = $types[$day->worker_id][$date] ?? null;

                // A worker on two projects the same day keeps the working day.
                if ($existing === ScheduleDayType::Work) {
                    return;
                }

                $types[$day->worker_id][$date] = $day->day_type;
            });

        return $types;
    }
}
