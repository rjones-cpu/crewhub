<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\Module;
use App\Models\ModuleActivationRequest;
use App\Models\User;
use App\Services\Modules\ModuleAccessService;

class ModulePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_active && $user->isSuperAdmin();
    }

    public function update(User $user, Module $module): bool
    {
        return $this->viewAny($user);
    }

    public function manageAccess(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function requestActivation(User $user, Module $module): bool
    {
        if (! $user->is_active || $user->isSuperAdmin() || ! $user->company_id) {
            return false;
        }

        if (! $module->is_paid || ! $module->is_active) {
            return false;
        }

        return in_array($user->role, [Role::CompanyAdmin, Role::WorkforceManager], true);
    }

    public function review(User $user, ModuleActivationRequest $request): bool
    {
        return $this->viewAny($user);
    }
}
