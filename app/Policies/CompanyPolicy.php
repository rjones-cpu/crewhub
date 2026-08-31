<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\User;

abstract class CompanyPolicy
{
    protected function sameCompany(User $user, object $model): bool
    {
        return $user->isSuperAdmin() || (int) $user->company_id === (int) $model->company_id;
    }

    protected function canManage(User $user): bool
    {
        return $user->isSuperAdmin() || in_array($user->role, $this->managerRoles(), true);
    }

    protected function managerRoles(): array
    {
        return [Role::CompanyAdmin];
    }

    public function viewAny(User $user): bool { return $user->is_active; }
    public function view(User $user, object $model): bool { return $user->is_active && $this->sameCompany($user, $model); }
    public function create(User $user): bool { return $user->is_active && $this->canManage($user); }
    public function update(User $user, object $model): bool { return $this->sameCompany($user, $model) && $this->canManage($user); }
    public function delete(User $user, object $model): bool { return $this->sameCompany($user, $model) && $this->canManage($user); }
    public function restore(User $user, object $model): bool { return $this->delete($user, $model); }
    public function forceDelete(User $user, object $model): bool { return $user->isSuperAdmin(); }
}
