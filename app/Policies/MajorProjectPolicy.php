<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\MajorProject;
use App\Models\Module;
use App\Models\User;
use App\Services\Modules\ModuleAccessService;

class MajorProjectPolicy extends CompanyPolicy
{
    protected function managerRoles(): array
    {
        return [Role::CompanyAdmin, Role::WorkforceManager];
    }

    /**
     * Who may open the Create Project page (form or locked activation notice).
     * Super Admin cannot create projects — only company managers can.
     */
    public function attemptCreate(User $user): bool
    {
        if (! $user->is_active || $user->isSuperAdmin()) {
            return false;
        }

        return $user->company_id && $this->canManage($user);
    }

    /**
     * Who may actually create a Major Project (paid-module gate for companies).
     * Super Admin never creates; they approve module activation instead.
     */
    public function create(User $user): bool
    {
        if (! $this->attemptCreate($user)) {
            return false;
        }

        return app(ModuleAccessService::class)
            ->companyHasActiveAccess($user->company_id, Module::KEY_MAJOR_PROJECTS);
    }

    public function view(User $user, object $model): bool
    {
        if (! $user->is_active) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        if (! $model instanceof MajorProject || ! $user->company_id) {
            return false;
        }

        return $model->memberships()
            ->where('company_id', $user->company_id)
            ->where('status', 'active')
            ->exists();
    }

    public function update(User $user, object $model): bool
    {
        return $this->view($user, $model) && $this->canManage($user);
    }

    public function invite(User $user, MajorProject $project): bool
    {
        return $user->is_active && $user->isSuperAdmin();
    }

    public function delete(User $user, object $model): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if (! $model instanceof MajorProject || ! $user->company_id || ! $this->canManage($user)) {
            return false;
        }

        return $model->memberships()
            ->where('company_id', $user->company_id)
            ->where('role', 'Owner')
            ->where('status', 'active')
            ->exists();
    }
}
