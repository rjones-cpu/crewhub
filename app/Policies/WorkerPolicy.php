<?php

namespace App\Policies;

use App\Enums\Role;

class WorkerPolicy extends CompanyPolicy
{
    protected function managerRoles(): array
    {
        return [Role::CompanyAdmin, Role::WorkforceManager];
    }
}
