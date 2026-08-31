<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\Position;
use App\Models\User;

class PositionPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->canManage($user);
    }

    public function create(User $user): bool
    {
        return $this->canManage($user);
    }

    public function update(User $user, Position $position): bool
    {
        return $this->canManage($user);
    }

    public function delete(User $user, Position $position): bool
    {
        return $this->canManage($user);
    }

    public function import(User $user): bool
    {
        return $this->canManage($user);
    }

    private function canManage(User $user): bool
    {
        return $user->is_active && (
            $user->isSuperAdmin()
            || in_array($user->role, [Role::CompanyAdmin, Role::WorkforceManager], true)
        );
    }
}
