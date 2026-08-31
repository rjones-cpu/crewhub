<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Company;
use App\Models\User;
use Database\Seeders\CompanySeeder;
use Database\Seeders\SuperAdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanySeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_each_company_is_seeded_with_exactly_one_administrator(): void
    {
        $this->seed(CompanySeeder::class);

        $this->assertSame(count(CompanySeeder::COMPANIES), Company::count());

        foreach (CompanySeeder::COMPANIES as $definition) {
            $company = Company::query()->where('code', $definition['code'])->sole();

            $this->assertSame($definition['name'], $company->name);
            $this->assertSame('active', $company->status);

            $admin = User::query()->where('company_id', $company->id)->sole();

            $this->assertSame($definition['admin_email'], $admin->email);
            $this->assertSame(Role::CompanyAdmin, $admin->role);
            $this->assertTrue($admin->is_active);
            $this->assertNotNull($admin->email_verified_at);
        }
    }

    public function test_re_running_the_seeder_does_not_duplicate_records(): void
    {
        $this->seed(CompanySeeder::class);
        $this->seed(CompanySeeder::class);

        $this->assertSame(count(CompanySeeder::COMPANIES), Company::count());
        $this->assertSame(count(CompanySeeder::COMPANIES), User::count());
    }

    public function test_every_company_administrator_can_log_in(): void
    {
        $this->seed(CompanySeeder::class);

        foreach (CompanySeeder::COMPANIES as $definition) {
            $response = $this->post('/login', [
                'email' => $definition['admin_email'],
                'password' => CompanySeeder::ADMIN_PASSWORD,
            ]);

            $this->assertAuthenticated();
            $response->assertRedirect(route('dashboard', absolute: false));

            $this->post('/logout');
        }
    }

    public function test_super_admin_is_seeded_without_a_company(): void
    {
        $this->seed(SuperAdminSeeder::class);

        $admin = User::query()->where('email', SuperAdminSeeder::EMAIL)->sole();

        $this->assertNull($admin->company_id);
        $this->assertSame(Role::SuperAdmin, $admin->role);
        $this->assertTrue($admin->isSuperAdmin());
    }
}
