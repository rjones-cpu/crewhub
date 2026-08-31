<?php

namespace App\Enums;

enum VehicleAvailability: string
{
    case Available = 'available';
    case InUse = 'in_use';
    case Maintenance = 'maintenance';
    case OutOfService = 'out_of_service';

    public function label(): string
    {
        return match ($this) {
            self::Available => 'Available',
            self::InUse => 'In Use',
            self::Maintenance => 'In Maintenance',
            self::OutOfService => 'Out of Service',
        };
    }

    public function isRoadworthy(): bool
    {
        return $this === self::Available || $this === self::InUse;
    }
}
