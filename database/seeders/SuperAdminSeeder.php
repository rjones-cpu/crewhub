<?php

namespace Database\Seeders;

use App\Enums\Role;
use Database\Seeders\Concerns\SeedsAdministrators;
use Illuminate\Database\Seeder;

/**
 * Seeds the single platform-wide super admin.
 *
 * It deliberately has no company_id: that is what lets CompanyScope skip tenant
 * filtering so this account can support every company.
 */
class SuperAdminSeeder extends Seeder
{
    use SeedsAdministrators;

    public const EMAIL = 'admin@crewhub.test';

    public const PASSWORD = 'password';

    public function run(): void
    {
        $this->seedAdministrator(self::EMAIL, [
            'company_id' => null,
            'name' => 'Crew Hub Super Admin',
            'role' => Role::SuperAdmin,
            'is_active' => true,
        ], self::PASSWORD);

        $this->command?->info('Super admin: '.self::EMAIL.' / '.self::PASSWORD);
    }
}
