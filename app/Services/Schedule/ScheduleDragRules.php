<?php

namespace App\Services\Schedule;

use App\Enums\ScheduleDayType;
use Illuminate\Support\Carbon;

/**
 * Camp.site scheduling-dashboard drag rule chain (work / travel / off subset).
 *
 * Detection order matches the frozen coordinator mouseup chain. Crew Hub has no
 * blocked / sick / vacation cells, so those branches are omitted rather than
 * rewritten.
 *
 * @see D:\laragon\www\camp\.cursor\rules\scheduling-dashboard-drag-drop-frozen.mdc
 */
class ScheduleDragRules
{
    public const MODE_CELLS = 'cells';

    public const MODE_SELECTION = 'selection';

    public const MODE_REVERT = 'revert';

    /**
     * @param  array<string, string>  $types  Pre-drag day types keyed by Y-m-d
     * @return array{mode: string, cells: array<string, string>}
     */
    public function resolve(string $sourceDate, string $dropDate, array $types, bool $backDragRevert = false): array
    {
        if ($sourceDate === $dropDate) {
            return ['mode' => self::MODE_SELECTION, 'cells' => []];
        }

        $range = $this->rangeDates($sourceDate, $dropDate);
        $sourceType = $this->typeAt($types, $sourceDate);
        $dropType = $this->typeAt($types, $dropDate);
        $sourceIsTravel = $sourceType === ScheduleDayType::Travel->value;
        $forward = $sourceDate < $dropDate;
        $between = array_slice($range, 1, -1);
        $yellowsInRange = $this->countType($types, $range, ScheduleDayType::Travel->value);
        $leftOfSource = Carbon::parse($sourceDate)->subDay()->toDateString();
        $rightOfSource = Carbon::parse($sourceDate)->addDay()->toDateString();
        $leftIsWork = $this->typeAt($types, $leftOfSource) === ScheduleDayType::Work->value;
        $rightIsWork = $this->typeAt($types, $rightOfSource) === ScheduleDayType::Work->value;
        $leftIsTravel = $this->typeAt($types, $leftOfSource) === ScheduleDayType::Travel->value;
        $rightIsTravel = $this->typeAt($types, $rightOfSource) === ScheduleDayType::Travel->value;
        $nonSource = array_values(array_filter($range, fn (string $date) => $date !== $sourceDate));
        $nonSourceAllOff = $this->allType($types, $nonSource, ScheduleDayType::Off->value);
        $betweenAllWork = $between !== [] && $this->allType($types, $between, ScheduleDayType::Work->value);
        $betweenAllOff = $between !== [] && $this->allType($types, $between, ScheduleDayType::Off->value);
        $betweenHasOff = $this->hasType($types, $between, ScheduleDayType::Off->value);
        $dropIsTravel = $dropType === ScheduleDayType::Travel->value;
        $dropIsWork = $dropType === ScheduleDayType::Work->value;

        $sameShiftCollapse = $sourceIsTravel
            && $dropIsTravel
            && $betweenAllWork;

        if ($sameShiftCollapse) {
            return $this->fill($range, ScheduleDayType::Off->value);
        }

        if ($backDragRevert) {
            return ['mode' => self::MODE_REVERT, 'cells' => $this->slice($types, $range)];
        }

        $attachToBlueEnd = $sourceIsTravel
            && $yellowsInRange === 1
            && (($forward && $leftIsWork) || (! $forward && $rightIsWork));

        $dragToBlueOffDays = $sourceIsTravel
            && ! $attachToBlueEnd
            && $yellowsInRange === 1
            && $dropIsWork;

        $loneToLone = $sourceIsTravel
            && $dropIsTravel
            && $betweenAllOff
            && $this->isLoneTravel($types, $sourceDate)
            && $this->isLoneTravel($types, $dropDate);

        $combineShifts = $sourceIsTravel
            && $forward
            && $dropIsTravel
            && $betweenHasOff;

        $dropOnYellowAllWork = $sourceIsTravel
            && $dropIsTravel
            && ! $attachToBlueEnd
            && ! $dragToBlueOffDays;

        $departureShortenLeft = $sourceIsTravel
            && ! $forward
            && ! $attachToBlueEnd
            && ! $dragToBlueOffDays
            && ! $combineShifts
            && ! $dropOnYellowAllWork
            && ($leftIsWork)
            && $yellowsInRange === 1;

        $adjacentTravelMove = $sourceIsTravel
            && ! $attachToBlueEnd
            && ! $dragToBlueOffDays
            && ! $combineShifts
            && ! $dropOnYellowAllWork
            && ! $departureShortenLeft
            && ($leftIsTravel || $rightIsTravel);

        $whiteSelect = $sourceType === ScheduleDayType::Off->value && $nonSourceAllOff;
        $blueOverWhite = $sourceType === ScheduleDayType::Work->value && $nonSourceAllOff;
        $workPaint = $sourceType === ScheduleDayType::Work->value;

        if ($attachToBlueEnd) {
            return $this->dropAndRest($range, $dropDate, ScheduleDayType::Travel->value, ScheduleDayType::Work->value);
        }

        if ($dragToBlueOffDays) {
            return $this->dropAndRest($range, $dropDate, ScheduleDayType::Travel->value, ScheduleDayType::Off->value);
        }

        if ($loneToLone) {
            return $this->bookend($range);
        }

        if ($combineShifts) {
            return $this->dropAndRest($range, $dropDate, ScheduleDayType::Travel->value, ScheduleDayType::Work->value);
        }

        if ($dropOnYellowAllWork) {
            return $this->fill($range, ScheduleDayType::Work->value);
        }

        if ($departureShortenLeft) {
            return $this->dropAndRest($range, $dropDate, ScheduleDayType::Travel->value, ScheduleDayType::Off->value);
        }

        if ($adjacentTravelMove) {
            return $this->dropAndRest($range, $dropDate, ScheduleDayType::Travel->value, ScheduleDayType::Work->value);
        }

        if ($sourceIsTravel) {
            return $this->bookend($range);
        }

        if ($whiteSelect) {
            return ['mode' => self::MODE_SELECTION, 'cells' => []];
        }

        if ($blueOverWhite || $workPaint) {
            return $this->fill($range, ScheduleDayType::Work->value);
        }

        return ['mode' => self::MODE_SELECTION, 'cells' => []];
    }

    /**
     * @param  list<string>  $range
     * @return array{mode: string, cells: array<string, string>}
     */
    public function bookend(array $range): array
    {
        $cells = [];
        $last = count($range) - 1;

        foreach ($range as $index => $date) {
            $cells[$date] = $index === 0 || $index === $last
                ? ScheduleDayType::Travel->value
                : ScheduleDayType::Work->value;
        }

        return ['mode' => self::MODE_CELLS, 'cells' => $cells];
    }

    /**
     * Paint every date in the range to one type (right-click menu / uniform fill).
     *
     * @param  list<string>  $dates
     * @return array{mode: string, cells: array<string, string>}
     */
    public function paint(array $dates, string $type): array
    {
        return $this->fill($dates, $type);
    }

    /**
     * @return list<string>
     */
    public function rangeDates(string $a, string $b): array
    {
        $start = Carbon::parse($a)->startOfDay();
        $end = Carbon::parse($b)->startOfDay();

        if ($start->gt($end)) {
            [$start, $end] = [$end->copy(), $start->copy()];
        }

        $dates = [];

        for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
            $dates[] = $date->toDateString();
        }

        return $dates;
    }

    /**
     * @param  array<string, string>  $types
     */
    public function typeAt(array $types, string $date): string
    {
        return $types[$date] ?? ScheduleDayType::Off->value;
    }

    /**
     * @param  list<string>  $dates
     * @return array{mode: string, cells: array<string, string>}
     */
    private function fill(array $dates, string $type): array
    {
        $cells = [];

        foreach ($dates as $date) {
            $cells[$date] = $type;
        }

        return ['mode' => self::MODE_CELLS, 'cells' => $cells];
    }

    /**
     * @param  list<string>  $range
     * @return array{mode: string, cells: array<string, string>}
     */
    private function dropAndRest(array $range, string $dropDate, string $dropType, string $restType): array
    {
        $cells = [];

        foreach ($range as $date) {
            $cells[$date] = $date === $dropDate ? $dropType : $restType;
        }

        return ['mode' => self::MODE_CELLS, 'cells' => $cells];
    }

    /**
     * @param  array<string, string>  $types
     * @param  list<string>  $dates
     * @return array<string, string>
     */
    private function slice(array $types, array $dates): array
    {
        $cells = [];

        foreach ($dates as $date) {
            $cells[$date] = $this->typeAt($types, $date);
        }

        return $cells;
    }

    /**
     * @param  array<string, string>  $types
     * @param  list<string>  $dates
     */
    private function countType(array $types, array $dates, string $type): int
    {
        $count = 0;

        foreach ($dates as $date) {
            if ($this->typeAt($types, $date) === $type) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * @param  array<string, string>  $types
     * @param  list<string>  $dates
     */
    private function allType(array $types, array $dates, string $type): bool
    {
        if ($dates === []) {
            return true;
        }

        foreach ($dates as $date) {
            if ($this->typeAt($types, $date) !== $type) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  array<string, string>  $types
     * @param  list<string>  $dates
     */
    private function hasType(array $types, array $dates, string $type): bool
    {
        foreach ($dates as $date) {
            if ($this->typeAt($types, $date) === $type) {
                return true;
            }
        }

        return false;
    }

    /**
     * Outer neighbor of a travel marker is off (or missing) so it is not already
     * attached to another shift.
     *
     * @param  array<string, string>  $types
     */
    private function isLoneTravel(array $types, string $date): bool
    {
        $left = Carbon::parse($date)->subDay()->toDateString();
        $right = Carbon::parse($date)->addDay()->toDateString();

        return $this->typeAt($types, $left) === ScheduleDayType::Off->value
            && $this->typeAt($types, $right) === ScheduleDayType::Off->value;
    }
}
