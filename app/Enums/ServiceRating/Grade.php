<?php

namespace App\Enums\ServiceRating;

/**
 * CH-11 overall and criterion grades. Severity rises with the numeric value so
 * the worst applicable criterion is always max(severity).
 */
enum Grade: string
{
    case A = 'A';
    case B = 'B';
    case C = 'C';
    case D = 'D';

    public function severity(): int
    {
        return match ($this) {
            self::A => 1,
            self::B => 2,
            self::C => 3,
            self::D => 4,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::A => 'Compliant',
            self::B => 'On Watch',
            self::C => 'Action Required',
            self::D => 'Critical',
        };
    }

    /** Short display word used next to the letter on KPI tiles. */
    public function shortLabel(): string
    {
        return match ($this) {
            self::A => 'Excellent',
            self::B => 'Good',
            self::C => 'Needs Work',
            self::D => 'Critical',
        };
    }

    public function colorName(): string
    {
        return match ($this) {
            self::A => 'green',
            self::B => 'yellow',
            self::C => 'orange',
            self::D => 'red',
        };
    }

    public static function worst(iterable $grades): ?self
    {
        $worst = null;

        foreach ($grades as $grade) {
            if (! $grade instanceof self) {
                continue;
            }

            if ($worst === null || $grade->severity() > $worst->severity()) {
                $worst = $grade;
            }
        }

        return $worst;
    }

    public static function tryFromNullable(?string $value): ?self
    {
        return $value === null ? null : self::tryFrom($value);
    }
}
