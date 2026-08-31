<?php

namespace App\Policies;

use App\Enums\InvitationStatus;
use App\Enums\Role;
use App\Models\ProjectInvitation;
use App\Models\User;

class ProjectInvitationPolicy extends CompanyPolicy
{
    protected function managerRoles(): array
    {
        return [Role::CompanyAdmin, Role::WorkforceManager];
    }

    public function viewAny(User $user): bool
    {
        return $user->is_active && ! $user->isSuperAdmin() && $user->company_id;
    }

    public function view(User $user, object $model): bool
    {
        return $user->is_active
            && (int) $user->company_id === (int) $model->company_id;
    }

    public function respond(User $user, ProjectInvitation $invitation): bool
    {
        return $this->view($user, $invitation)
            && $this->canManage($user)
            && $invitation->status === InvitationStatus::Pending;
    }
}
