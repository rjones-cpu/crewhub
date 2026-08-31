<?php

namespace App\Services\Notifications;

use App\Enums\Role;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Collection;

class NotificationService
{
    /**
     * Persist a database notification for a single user (Crew Hub inbox).
     */
    public function notifyUser(
        User $user,
        string $type,
        string $title,
        string $message,
        array $data = [],
        ?int $companyId = null,
    ): Notification {
        return Notification::query()->create([
            'company_id' => $companyId ?? $user->company_id,
            'user_id' => $user->id,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'data' => $data,
        ]);
    }

    /**
     * Notify every active Super Admin via the Crew Hub notification inbox.
     *
     * @return Collection<int, Notification>
     */
    public function notifySuperAdmins(
        string $type,
        string $title,
        string $message,
        array $data = [],
    ): Collection {
        $admins = User::query()
            ->where('role', Role::SuperAdmin)
            ->where('is_active', true)
            ->get();

        return $admins->map(
            fn (User $admin) => $this->notifyUser($admin, $type, $title, $message, $data, null),
        );
    }
}
