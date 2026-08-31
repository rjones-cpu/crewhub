<?php

namespace App\Enums;

enum JourneyStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case InTransit = 'in_transit';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending Approval',
            self::Approved => 'Planned',
            self::InTransit => 'En Route',
            self::Completed => 'Completed',
            self::Cancelled => 'Cancelled',
        };
    }
}
