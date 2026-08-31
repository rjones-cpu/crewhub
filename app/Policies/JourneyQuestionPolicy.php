<?php

namespace App\Policies;

use App\Enums\Role;

class JourneyQuestionPolicy extends CompanyPolicy
{
    protected function managerRoles(): array
    {
        return [Role::CompanyAdmin, Role::WorkforceManager, Role::ReservationManager];
    }
}
