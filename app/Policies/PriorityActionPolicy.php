<?php

namespace App\Policies;

use App\Enums\Role;

class PriorityActionPolicy extends CompanyPolicy
{
    protected function managerRoles(): array
    {
        return [Role::CompanyAdmin, Role::WorkforceManager, Role::ReservationManager, Role::LodgeManager];
    }
}
