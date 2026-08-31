<?php

namespace App\Services\Timesheets;

use App\Models\AccommodationAssignment;
use App\Models\Timesheet;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Resolves whether a worker's accommodation was confirmed for a timesheet period.
 *
 * Shared by the approval queue and the timesheet detail page so both read the
 * same rule: every overlapping stay must be confirmed for the check to pass.
 */
class AccommodationConfirmationService
{
    /** Assignment statuses that count as a confirmed stay. */
    public const CONFIRMED_STATUSES = ['checked_in', 'checked_out', 'confirmed'];

    /**
     * Resolve the state for a set of timesheets in one query rather than one per row.
     *
     * @param  Collection<int, Timesheet>  $sheets
     * @return Collection<int, array{state: string, at: ?string}>  keyed by timesheet id
     */
    public function statesFor(Collection $sheets): Collection
    {
        if ($sheets->isEmpty()) {
            return collect();
        }

        $assignments = AccommodationAssignment::query()
            ->whereIn('worker_id', $sheets->pluck('worker_id')->filter()->unique())
            ->get()
            ->groupBy('worker_id');

        return $sheets->mapWithKeys(fn (Timesheet $sheet) => [
            $sheet->id => $this->resolve($sheet, $assignments[$sheet->worker_id] ?? collect()),
        ]);
    }

    /** @return array{state: string, at: ?string} */
    public function stateFor(Timesheet $sheet): array
    {
        return $this->statesFor(collect([$sheet]))[$sheet->id];
    }

    /**
     * Constrain a timesheet query to rows whose worker has a stay over the period
     * that has not been confirmed yet.
     */
    public function scopePending(Builder $query, ?Carbon $week): Builder
    {
        return $query->whereHas('worker.accommodationAssignments', function (Builder $q) use ($week) {
            $q->whereNotIn('status', self::CONFIRMED_STATUSES);

            if ($week) {
                $q->whereDate('check_in', '<=', $week->copy()->addDays(6)->toDateString())
                    ->where(function (Builder $inner) use ($week) {
                        $inner->whereNull('check_out')
                            ->orWhereDate('check_out', '>=', $week->toDateString());
                    });
            }
        });
    }

    /**
     * @param  Collection<int, AccommodationAssignment>  $assignments
     * @return array{state: string, at: ?string}
     */
    protected function resolve(Timesheet $sheet, Collection $assignments): array
    {
        $stays = $assignments->filter(fn (AccommodationAssignment $stay) => $this->overlaps($stay, $sheet));

        if ($stays->isEmpty()) {
            return ['state' => 'not_required', 'at' => null];
        }

        $confirmed = $stays->every(
            fn (AccommodationAssignment $stay) => in_array($stay->status, self::CONFIRMED_STATUSES, true)
        );

        $at = $stays->max('check_in');

        return [
            'state' => $confirmed ? 'confirmed' : 'pending',
            'at' => $at ? Carbon::parse($at)->format('M j, g:i A') : null,
        ];
    }

    protected function overlaps(AccommodationAssignment $stay, Timesheet $sheet): bool
    {
        if (! $sheet->period_start || ! $sheet->period_end) {
            return false;
        }

        $startsAfterPeriod = $stay->check_in && $stay->check_in->gt($sheet->period_end);
        $endsBeforePeriod = $stay->check_out && $stay->check_out->lt($sheet->period_start);

        return ! $startsAfterPeriod && ! $endsBeforePeriod;
    }
}
