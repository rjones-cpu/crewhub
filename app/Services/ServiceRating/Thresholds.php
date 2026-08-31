<?php

namespace App\Services\ServiceRating;

use App\Enums\ServiceRating\Grade;

/**
 * Threshold helpers that apply package boundary rules before display rounding.
 * Exactly 20.0 is B; 20.0001 is C.
 */
final class Thresholds
{
    public static function workforceVarianceGrade(float $absoluteVariancePercent, array $thresholds): Grade
    {
        $aMax = (float) data_get($thresholds, 'A.max_absolute_variance_percent', 5);
        $bMax = (float) data_get($thresholds, 'B.max_inclusive_absolute_variance_percent', 10);
        $cMax = (float) data_get($thresholds, 'C.max_inclusive_absolute_variance_percent', 25);

        if ($absoluteVariancePercent <= $aMax) {
            return Grade::A;
        }

        if ($absoluteVariancePercent <= $bMax) {
            return Grade::B;
        }

        if ($absoluteVariancePercent <= $cMax) {
            return Grade::C;
        }

        return Grade::D;
    }

    public static function arrivalLatenessGrade(int $calendarDaysLate, bool $noShow = false): Grade
    {
        if ($noShow || $calendarDaysLate > 3) {
            return Grade::D;
        }

        return match (true) {
            $calendarDaysLate <= 0 => Grade::A,
            $calendarDaysLate === 1 => Grade::B,
            $calendarDaysLate <= 3 => Grade::C,
            default => Grade::D,
        };
    }

    public static function percentBandGrade(
        float $percent,
        float $aMaxInclusive,
        float $bMaxInclusive,
        float $cMaxInclusive,
    ): Grade {
        if ($percent <= $aMaxInclusive) {
            return Grade::A;
        }

        if ($percent <= $bMaxInclusive) {
            return Grade::B;
        }

        if ($percent <= $cMaxInclusive) {
            return Grade::C;
        }

        return Grade::D;
    }
}
