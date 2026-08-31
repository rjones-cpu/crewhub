<?php

namespace Database\Seeders\Concerns;

use App\Models\User;

trait SeedsAdministrators
{
    /**
     * Create or repair an administrator, keyed on its unique email address.
     *
     * Trashed rows keep occupying the unique email index, so a soft-deleted
     * administrator is revived instead of inserted again. The password is only
     * written on the first insert so re-seeding never overwrites a password that
     * was changed since.
     *
     * @param  array<string, mixed>  $attributes
     */
    protected function seedAdministrator(string $email, array $attributes, string $password): User
    {
        $admin = User::withTrashed()->firstWhere('email', $email) ?? new User;

        $admin->fill($attributes);
        $admin->email = $email;
        $admin->deleted_at = null;

        // Protected app routes sit behind the `verified` middleware.
        if (! $admin->email_verified_at) {
            $admin->email_verified_at = now();
        }

        if (! $admin->exists) {
            $admin->password = $password;
        }

        $admin->save();

        return $admin;
    }
}
