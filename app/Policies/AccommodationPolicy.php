<?php

namespace App\Policies;

use App\Enums\Role;

class AccommodationPolicy extends CompanyPolicy
{
    protected function managerRoles(): array
    {
        return [Role::CompanyAdmin, Role::ReservationManager, Role::LodgeManager];
    }
}
