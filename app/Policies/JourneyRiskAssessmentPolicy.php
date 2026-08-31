<?php

namespace App\Policies;

use App\Enums\Role;

class JourneyRiskAssessmentPolicy extends CompanyPolicy
{
    protected function managerRoles(): array
    {
        return [Role::CompanyAdmin, Role::WorkforceManager, Role::ReservationManager];
    }
}
