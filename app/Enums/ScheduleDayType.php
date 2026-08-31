<?php

namespace App\Enums;

/**
 * One calendar day of a worker's rotation on a major project. The schedule board
 * paints a coloured cell per day, so the type doubles as the cell legend.
 */
enum ScheduleDayType: string
{
    case Work = 'work';
    case Travel = 'travel';
    case Off = 'off';

    public function label(): string
    {
        return match ($this) {
            self::Work => 'Work',
            self::Travel => 'Travel',
            self::Off => 'Off',
        };
    }

    /** Off days leave the worker out of both footer totals. */
    public function isScheduled(): bool
    {
        return $this !== self::Off;
    }
}
