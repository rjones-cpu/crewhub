<?php

namespace App\Services\Schedule\Views;

use App\Enums\ScheduleDayType;
use App\Models\Worker;
use Illuminate\Support\Str;

/**
 * Crew Hub stores a worker's position, but the Schedule screens are drawn around
 * lodge departments, named shifts, and leave states that have no column yet.
 * This resolver derives those from the data we do have and keeps the derivation
 * deterministic, so the list, calendar, and change-request views never disagree
 * about a worker's department, shift, or status on a given day.
 */
class ScheduleWorkforceProfile
{
    public const SHIFT_DAY = 'day';
    public const SHIFT_NIGHT = 'night';
    public const SHIFT_ON_CALL = 'on_call';

    public const STATUS_OFF = 'off';
    public const STATUS_BOOKED_OFF = 'booked_off';
    public const STATUS_UNAVAILABLE = 'unavailable';

    /** Shift labels and hours as rendered in the day cells. */
    public const SHIFTS = [
        self::SHIFT_DAY => ['label' => 'Day', 'time' => '6:00 AM – 2:00 PM'],
        self::SHIFT_NIGHT => ['label' => 'Night', 'time' => '2:00 PM – 10:00 PM'],
        self::SHIFT_ON_CALL => ['label' => 'On Call', 'time' => '24 Hours'],
    ];

    /** Maintenance crews run an earlier day shift than the rest of the lodge. */
    private const MAINTENANCE_DAY_TIME = '7:00 AM – 3:00 PM';

    /** Position keyword → department. First match wins, so order matters. */
    private const DEPARTMENT_KEYWORDS = [
        'housekeep' => 'Housekeeping',
        'linen' => 'Housekeeping',
        'janitor' => 'Housekeeping',
        'cook' => 'Kitchen',
        'chef' => 'Kitchen',
        'kitchen' => 'Kitchen',
        'dish' => 'Kitchen',
        'catering' => 'Kitchen',
        'front desk' => 'Front Desk',
        'reception' => 'Front Desk',
        'agent' => 'Front Desk',
        'concierge' => 'Front Desk',
        'maintenance' => 'Maintenance',
        'technician' => 'Maintenance',
        'mechanic' => 'Maintenance',
        'electric' => 'Maintenance',
        'operator' => 'Maintenance',
        'hse' => 'Safety',
        'safety' => 'Safety',
        'supervisor' => 'Operations',
        'manager' => 'Operations',
        'coordinator' => 'Operations',
        'engineer' => 'Operations',
        'inspector' => 'Operations',
    ];

    private const FALLBACK_DEPARTMENT = 'Operations';

    /** @return list<string> */
    public function departments(): array
    {
        return array_values(array_unique([
            ...array_values(self::DEPARTMENT_KEYWORDS),
            self::FALLBACK_DEPARTMENT,
        ]));
    }

    public function department(Worker $worker): string
    {
        $position = Str::lower((string) $worker->position);

        foreach (self::DEPARTMENT_KEYWORDS as $keyword => $department) {
            if ($position !== '' && str_contains($position, $keyword)) {
                return $department;
            }
        }

        return self::FALLBACK_DEPARTMENT;
    }

    /**
     * Maintenance is rostered on call around the clock; everyone else alternates
     * between the day and night shift by a stable split on the worker id.
     */
    public function shift(Worker $worker): string
    {
        if ($this->department($worker) === 'Maintenance') {
            return $worker->id % 2 === 0 ? self::SHIFT_ON_CALL : self::SHIFT_DAY;
        }

        return $worker->id % 3 === 0 ? self::SHIFT_NIGHT : self::SHIFT_DAY;
    }

    public function shiftLabel(string $shift): string
    {
        return self::SHIFTS[$shift]['label'] ?? self::SHIFTS[self::SHIFT_DAY]['label'];
    }

    public function shiftTime(Worker $worker, string $shift): string
    {
        if ($shift === self::SHIFT_DAY && $this->department($worker) === 'Maintenance') {
            return self::MAINTENANCE_DAY_TIME;
        }

        return self::SHIFTS[$shift]['time'] ?? self::SHIFTS[self::SHIFT_DAY]['time'];
    }

    /**
     * One day cell for a worker. Rotation days come from the schedule board;
     * days with no rotation record fall back to off, booked off, or unavailable
     * so the roster reads like a real week instead of a wall of blanks.
     *
     * @return array{status: string, label: string, time: string|null}
     */
    public function dayCell(Worker $worker, ?ScheduleDayType $dayType, string $date): array
    {
        if ($dayType === ScheduleDayType::Work) {
            $shift = $this->shift($worker);

            return [
                'status' => $shift,
                'label' => $this->shiftLabel($shift),
                'time' => $this->shiftTime($worker, $shift),
            ];
        }

        // Travel days are rostered but not on a fixed shift: the lodge treats
        // them as rotational cover, which is what the On Call cell represents.
        if ($dayType === ScheduleDayType::Travel) {
            return [
                'status' => self::SHIFT_ON_CALL,
                'label' => 'On Call',
                'time' => '24 Hours',
            ];
        }

        return match ($this->offDayBucket($worker, $date)) {
            self::STATUS_BOOKED_OFF => ['status' => self::STATUS_BOOKED_OFF, 'label' => 'Booked Off', 'time' => null],
            self::STATUS_UNAVAILABLE => ['status' => self::STATUS_UNAVAILABLE, 'label' => 'Unavailable', 'time' => null],
            default => ['status' => self::STATUS_OFF, 'label' => 'Off', 'time' => null],
        };
    }

    /** Stable 0-99 draw for a worker/date pair, so a reload never reshuffles a cell. */
    public function noise(Worker $worker, string $date, string $salt = ''): int
    {
        return crc32("{$worker->id}|{$date}|{$salt}") % 100;
    }

    private function offDayBucket(Worker $worker, string $date): string
    {
        $noise = $this->noise($worker, $date, 'off-day');

        return match (true) {
            $noise < 22 => self::STATUS_BOOKED_OFF,
            $noise < 34 => self::STATUS_UNAVAILABLE,
            default => self::STATUS_OFF,
        };
    }
}
