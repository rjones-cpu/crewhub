<?php

namespace Tests\Feature;

use App\Enums\ModuleAccessStatus;
use App\Enums\ModuleActivationSource;
use App\Enums\Role;
use App\Models\Company;
use App\Models\CompanyModule;
use App\Models\Module;
use App\Models\User;
use App\Models\Worker;
use Database\Seeders\ModuleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LmsModuleAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(ModuleSeeder::class);
    }

    private function companyAdmin(): User
    {
        return User::factory()->create([
            'company_id' => Company::factory()->create(['name' => 'Baker Hughes'])->id,
            'role' => Role::CompanyAdmin,
        ]);
    }

    private function grantLms(User $user): void
    {
        CompanyModule::query()->create([
            'company_id' => $user->company_id,
            'module_id' => Module::query()->where('key', Module::KEY_LMS)->firstOrFail()->id,
            'status' => ModuleAccessStatus::Active,
            'activation_source' => ModuleActivationSource::Manual,
            'activated_by' => $user->id,
            'activated_at' => now(),
        ]);
    }

    public function test_lms_module_is_paid_by_default(): void
    {
        $this->assertTrue(Module::query()->where('key', Module::KEY_LMS)->firstOrFail()->is_paid);
    }

    public function test_lms_worker_feature_is_locked_without_an_entitlement(): void
    {
        $user = $this->companyAdmin();
        Worker::factory()->create(['company_id' => $user->company_id, 'lms_access' => true]);

        $this->actingAs($user)
            ->get(route('workers.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('featureSummary.lms.locked', true)
                ->where('featureSummary.lms.can_request_activation', true)
                ->where('featureSummary.lms.activation_pending', false)
                ->has('featureSummary.lms.module')
                ->where('featureSummary.schedule.locked', false));
    }

    public function test_lms_worker_feature_unlocks_once_the_company_owns_the_module(): void
    {
        $user = $this->companyAdmin();
        $this->grantLms($user);

        $this->actingAs($user)
            ->get(route('workers.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('featureSummary.lms.locked', false));
    }

    public function test_locked_lms_module_cannot_be_enabled_for_the_company(): void
    {
        $user = $this->companyAdmin();
        $worker = Worker::factory()->create(['company_id' => $user->company_id, 'lms_access' => false]);

        $this->actingAs($user)
            ->patch(route('workers.features.update', 'lms'), ['enabled' => true])
            ->assertSessionHasErrors('enabled');

        $this->assertFalse($worker->fresh()->lms_access);
    }

    public function test_locked_lms_module_cannot_be_enabled_for_a_single_worker(): void
    {
        $user = $this->companyAdmin();
        $worker = Worker::factory()->create(['company_id' => $user->company_id, 'lms_access' => false]);

        $this->actingAs($user)
            ->patch(route('workers.tools.update', $worker), ['lms_access' => true])
            ->assertSessionHasErrors('lms_access');

        $this->assertFalse($worker->fresh()->lms_access);
    }

    public function test_owned_lms_module_can_be_toggled_for_the_company(): void
    {
        $user = $this->companyAdmin();
        $this->grantLms($user);
        $worker = Worker::factory()->create(['company_id' => $user->company_id, 'lms_access' => false]);

        $this->actingAs($user)
            ->patch(route('workers.features.update', 'lms'), ['enabled' => true])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertTrue($worker->fresh()->lms_access);
    }

    public function test_locked_lms_module_can_still_be_disabled(): void
    {
        $user = $this->companyAdmin();
        $worker = Worker::factory()->create(['company_id' => $user->company_id, 'lms_access' => true]);

        $this->actingAs($user)
            ->patch(route('workers.features.update', 'lms'), ['enabled' => false])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertFalse($worker->fresh()->lms_access);
    }
}
