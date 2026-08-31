<?php

namespace App\Enums;

enum Role: string
{
    case SuperAdmin = 'super_admin';
    case CompanyAdmin = 'company_admin';
    case WorkforceManager = 'workforce_manager';
    case ReservationManager = 'reservation_manager';
    case LodgeManager = 'lodge_manager';
    case ReadOnly = 'read_only';

    public function canManageWorkforce(): bool
    {
        return in_array($this, [self::SuperAdmin, self::CompanyAdmin, self::WorkforceManager], true);
    }

    public function canManageReservations(): bool
    {
        return in_array($this, [self::SuperAdmin, self::CompanyAdmin, self::ReservationManager, self::LodgeManager], true);
    }
}
