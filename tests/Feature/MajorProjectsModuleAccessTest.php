<?php

namespace Tests\Feature;

use App\Enums\InvitationStatus;
use App\Enums\ModuleAccessStatus;
use App\Enums\ModuleActivationRequestStatus;
use App\Enums\ModuleActivationSource;
use App\Enums\Role;
use App\Models\Company;
use App\Models\CompanyModule;
use App\Models\MajorProject;
use App\Models\Module;
use App\Models\ModuleActivationRequest;
use App\Models\Notification;
use App\Models\ProjectInvitation;
use App\Models\User;
use Database\Seeders\ModuleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MajorProjectsModuleAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(ModuleSeeder::class);
    }

    private function companyAdmin(?Company $company = null): User
    {
        $company ??= Company::factory()->create(['name' => 'Baker Hughes']);

        return User::factory()->create([
            'company_id' => $company->id,
            'role' => Role::CompanyAdmin,
        ]);
    }

    private function superAdmin(): User
    {
        return User::factory()->create([
            'company_id' => null,
            'role' => Role::SuperAdmin,
        ]);
    }

    private function grantMajorProjects(Company $company, User $actor): void
    {
        $module = Module::query()->where('key', Module::KEY_MAJOR_PROJECTS)->firstOrFail();

        CompanyModule::query()->create([
            'company_id' => $company->id,
            'module_id' => $module->id,
            'status' => ModuleAccessStatus::Active,
            'activation_source' => ModuleActivationSource::Manual,
            'activated_by' => $actor->id,
            'activated_at' => now(),
        ]);
    }

    public function test_company_with_no_projects_sees_empty_list(): void
    {
        $user = $this->companyAdmin();

        $this->actingAs($user)
            ->get(route('major-projects.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('MajorProjects/Index')
                ->has('projects.data', 0));
    }

    public function test_unrelated_organization_cannot_view_another_company_project(): void
    {
        $owner = $this->companyAdmin();
        $outsider = $this->companyAdmin(Company::factory()->create(['name' => 'Other Co']));

        $project = MajorProject::factory()->create([
            'company_id' => $owner->company_id,
            'name' => 'Blue Ridge Expansion',
        ]);

        $this->actingAs($outsider)
            ->get(route('major-projects.show', $project))
            ->assertNotFound();
    }

    public function test_company_name_is_not_used_as_automatic_project_name(): void
    {
        $admin = $this->superAdmin();
        $company = Company::factory()->create(['name' => 'Baker Hughes']);
        $this->grantMajorProjects($company, $admin);
        $user = $this->companyAdmin($company);

        $this->actingAs($user)
            ->get(route('major-projects.create'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('MajorProjects/Create')
                ->where('hasMajorProjectsModule', true)
                ->where('organizationName', 'Baker Hughes'));

        $this->assertDatabaseMissing('major_projects', [
            'company_id' => $company->id,
            'name' => 'Baker Hughes',
        ]);
    }

    public function test_project_name_identical_to_organization_name_is_rejected(): void
    {
        $admin = $this->superAdmin();
        $company = Company::factory()->create(['name' => 'Baker Hughes']);
        $this->grantMajorProjects($company, $admin);
        $user = $this->companyAdmin($company);

        $this->actingAs($user)
            ->post(route('major-projects.store'), [
                'name' => ' baker hughes ',
                'project_number' => 'PRJ-001',
                'status' => 'active',
            ])
            ->assertSessionHasErrors('name');
    }

    public function test_company_without_activation_cannot_access_or_submit_creation(): void
    {
        $user = $this->companyAdmin();

        $this->actingAs($user)
            ->get(route('major-projects.create'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('MajorProjects/Create')
                ->where('hasMajorProjectsModule', false)
                ->where('canCreate', false));

        $this->actingAs($user)
            ->post(route('major-projects.store'), [
                'name' => 'Blue Ridge Expansion',
                'project_number' => 'PRJ-002',
                'status' => 'active',
            ])
            ->assertForbidden();
    }

    public function test_company_with_activation_can_create_major_project(): void
    {
        $admin = $this->superAdmin();
        $company = Company::factory()->create(['name' => 'Baker Hughes']);
        $this->grantMajorProjects($company, $admin);
        $user = $this->companyAdmin($company);

        $this->actingAs($user)
            ->post(route('major-projects.store'), [
                'name' => 'Blue Ridge Expansion',
                'project_number' => 'BR-2026-001',
                'status' => 'active',
                'address' => 'Edson, Alberta',
                'latitude' => 53.5817000,
                'longitude' => -116.4347000,
            ])
            ->assertRedirect(route('major-projects.index'));

        $project = MajorProject::query()
            ->where('company_id', $company->id)
            ->where('name', 'Blue Ridge Expansion')
            ->firstOrFail();

        $this->assertSame('BR-2026-001', $project->project_number);
        $this->assertSame('BR-2026-001', $project->code);
        $this->assertSame('Edson, Alberta', $project->address);
        $this->assertEqualsWithDelta(53.5817, (float) $project->latitude, 0.0001);
        $this->assertEqualsWithDelta(-116.4347, (float) $project->longitude, 0.0001);

        $this->assertDatabaseHas('company_project_memberships', [
            'company_id' => $company->id,
            'major_project_id' => $project->id,
            'role' => 'Owner',
            'status' => 'active',
        ]);

        $this->assertDatabaseMissing('project_invitations', [
            'company_id' => $company->id,
            'major_project_id' => $project->id,
        ]);
    }

    public function test_super_admin_cannot_create_major_projects(): void
    {
        $super = $this->superAdmin();
        $company = Company::factory()->create(['name' => 'Baker Hughes']);

        $this->actingAs($super)
            ->get(route('major-projects.create'))
            ->assertForbidden();

        $this->actingAs($super)
            ->post(route('major-projects.store'), [
                'company_id' => $company->id,
                'name' => 'Edson Compressor Station',
                'project_number' => 'ECS-001',
                'status' => 'active',
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('major_projects', [
            'name' => 'Edson Compressor Station',
        ]);
    }

    public function test_super_admin_index_hides_create_capability(): void
    {
        $super = $this->superAdmin();

        $this->actingAs($super)
            ->get(route('major-projects.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('MajorProjects/Index')
                ->where('canCreate', false)
                ->where('canAttemptCreate', false)
                ->where('isSuperAdmin', true));
    }

    public function test_super_admin_can_invite_a_company_to_an_existing_project(): void
    {
        $super = $this->superAdmin();
        $owner = Company::factory()->create(['name' => 'Project Owner']);
        $inviteeCompany = Company::factory()->create(['name' => 'Invited Contractor']);
        $invitee = $this->companyAdmin($inviteeCompany);
        $project = MajorProject::factory()->create([
            'company_id' => $owner->id,
            'name' => 'Existing Project',
        ]);

        $this->actingAs($super)
            ->post(route('major-projects.invitations.store', $project), [
                'company_ids' => [$inviteeCompany->id],
            ])
            ->assertRedirect()
            ->assertSessionHas('success', 'Company invitation sent.');

        $this->assertDatabaseHas('project_invitations', [
            'major_project_id' => $project->id,
            'company_id' => $inviteeCompany->id,
            'role' => 'Contractor',
            'status' => InvitationStatus::Pending->value,
        ]);

        $this->actingAs($invitee)
            ->get(route('major-projects.join'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('MajorProjects/Join')
                ->has('invitations.data', 1)
                ->where('invitations.data.0.project.name', 'Existing Project'));
    }

    public function test_company_admin_cannot_invite_companies_to_an_existing_project(): void
    {
        $owner = Company::factory()->create();
        $admin = $this->companyAdmin($owner);
        $otherCompany = Company::factory()->create();
        $project = MajorProject::factory()->create(['company_id' => $owner->id]);

        $this->actingAs($admin)
            ->post(route('major-projects.invitations.store', $project), [
                'company_ids' => [$otherCompany->id],
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('project_invitations', [
            'major_project_id' => $project->id,
            'company_id' => $otherCompany->id,
        ]);
    }

    public function test_project_number_is_required_and_unique_per_company(): void
    {
        $admin = $this->superAdmin();
        $company = Company::factory()->create(['name' => 'Baker Hughes', 'code' => 'BKRH']);
        $this->grantMajorProjects($company, $admin);
        $user = $this->companyAdmin($company);

        $this->actingAs($user)
            ->post(route('major-projects.store'), [
                'name' => 'Project One',
                'status' => 'active',
            ])
            ->assertSessionHasErrors('project_number');

        $this->actingAs($user)
            ->post(route('major-projects.store'), [
                'name' => 'Project One',
                'project_number' => 'BH-100',
                'status' => 'active',
            ])
            ->assertRedirect();

        $this->actingAs($user)
            ->post(route('major-projects.store'), [
                'name' => 'Project Two',
                'project_number' => 'BH-100',
                'status' => 'active',
            ])
            ->assertSessionHasErrors('project_number');

        $this->actingAs($user)
            ->post(route('major-projects.store'), [
                'name' => 'Project Two',
                'project_number' => 'BH-200',
                'status' => 'active',
            ])
            ->assertRedirect();

        $numbers = MajorProject::query()
            ->where('company_id', $company->id)
            ->orderBy('id')
            ->pluck('project_number')
            ->all();

        $this->assertSame(['BH-100', 'BH-200'], $numbers);
    }

    public function test_invited_organization_can_see_invited_project(): void
    {
        $ownerCompany = Company::factory()->create(['name' => 'Owner Co']);
        $inviteeCompany = Company::factory()->create(['name' => 'Baker Hughes']);
        $invitee = $this->companyAdmin($inviteeCompany);
        $super = $this->superAdmin();

        $project = MajorProject::factory()->create([
            'company_id' => $ownerCompany->id,
            'name' => 'Edgewater Pipeline',
        ]);

        // The factory already gives the owning company its Owner membership.
        $invitation = ProjectInvitation::query()->create([
            'major_project_id' => $project->id,
            'company_id' => $inviteeCompany->id,
            'invited_by' => $super->id,
            'role' => 'Contractor',
            'status' => InvitationStatus::Pending,
            'invited_at' => now(),
        ]);

        $this->actingAs($invitee)
            ->post(route('major-projects.invitations.accept', $invitation))
            ->assertRedirect(route('major-projects.index'));

        $this->assertDatabaseHas('company_project_memberships', [
            'company_id' => $inviteeCompany->id,
            'major_project_id' => $project->id,
            'status' => 'active',
        ]);

        $this->actingAs($invitee)
            ->get(route('major-projects.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('MajorProjects/Index')
                ->has('projects.data', 1)
                ->where('projects.data.0.name', 'Edgewater Pipeline'));
    }

    public function test_normal_user_cannot_access_super_admin_module_settings(): void
    {
        $user = $this->companyAdmin();

        $this->actingAs($user)
            ->get(route('settings.modules.index'))
            ->assertForbidden();
    }

    public function test_activation_request_creates_record_and_notification(): void
    {
        $super = $this->superAdmin();
        $user = $this->companyAdmin();
        $module = Module::query()->where('key', Module::KEY_MAJOR_PROJECTS)->firstOrFail();

        $this->actingAs($user)
            ->post(route('modules.request-activation', $module))
            ->assertRedirect();

        $this->assertDatabaseHas('module_activation_requests', [
            'company_id' => $user->company_id,
            'module_id' => $module->id,
            'requested_by' => $user->id,
            'status' => ModuleActivationRequestStatus::Pending->value,
        ]);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $super->id,
            'type' => 'module_activation_request',
            'title' => 'Module activation requested',
            'message' => $user->company->name.' requested activation of the Major Projects module.',
        ]);

        $this->actingAs($super)
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Notifications/Index')
                ->has('notifications.data', 1)
                ->where('notifications.data.0.title', 'Module activation requested')
                ->where('notifications.data.0.data.company_name', $user->company->name)
                ->where('notifications.data.0.data.module_name', 'Major Projects')
                ->where('notifications.data.0.request_status', 'pending'));

        $this->assertSame(1, ModuleActivationRequest::query()->count());
        $this->assertSame(
            1,
            Notification::withoutGlobalScopes()->where('type', 'module_activation_request')->count(),
        );
    }

    public function test_repeated_activation_requests_do_not_duplicate_pending(): void
    {
        $this->superAdmin();
        $user = $this->companyAdmin();
        $module = Module::query()->where('key', Module::KEY_MAJOR_PROJECTS)->firstOrFail();

        $this->actingAs($user)
            ->post(route('modules.request-activation', $module))
            ->assertRedirect();

        $this->actingAs($user)
            ->from(route('major-projects.create'))
            ->post(route('modules.request-activation', $module))
            ->assertSessionHasErrors('module');

        $this->assertSame(1, ModuleActivationRequest::query()->count());
    }

    public function test_approving_request_grants_access_and_updates_status(): void
    {
        $super = $this->superAdmin();
        $user = $this->companyAdmin();
        $module = Module::query()->where('key', Module::KEY_MAJOR_PROJECTS)->firstOrFail();

        $request = ModuleActivationRequest::query()->create([
            'company_id' => $user->company_id,
            'module_id' => $module->id,
            'requested_by' => $user->id,
            'status' => ModuleActivationRequestStatus::Pending,
        ]);

        $this->actingAs($super)
            ->post(route('notifications.activation-requests.approve', $request))
            ->assertRedirect();

        $this->assertDatabaseHas('module_activation_requests', [
            'id' => $request->id,
            'status' => ModuleActivationRequestStatus::Approved->value,
            'reviewed_by' => $super->id,
        ]);

        $this->assertDatabaseHas('company_modules', [
            'company_id' => $user->company_id,
            'module_id' => $module->id,
            'status' => ModuleAccessStatus::Active->value,
        ]);

        $this->actingAs($user)
            ->get(route('major-projects.create'))
            ->assertInertia(fn ($page) => $page->where('hasMajorProjectsModule', true));
    }

    public function test_rejecting_request_does_not_grant_access(): void
    {
        $super = $this->superAdmin();
        $user = $this->companyAdmin();
        $module = Module::query()->where('key', Module::KEY_MAJOR_PROJECTS)->firstOrFail();

        $request = ModuleActivationRequest::query()->create([
            'company_id' => $user->company_id,
            'module_id' => $module->id,
            'requested_by' => $user->id,
            'status' => ModuleActivationRequestStatus::Pending,
        ]);

        $this->actingAs($super)
            ->post(route('notifications.activation-requests.reject', $request), [
                'rejection_reason' => 'Not in current plan',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('module_activation_requests', [
            'id' => $request->id,
            'status' => ModuleActivationRequestStatus::Rejected->value,
            'rejection_reason' => 'Not in current plan',
        ]);

        $this->assertDatabaseMissing('company_modules', [
            'company_id' => $user->company_id,
            'module_id' => $module->id,
            'status' => ModuleAccessStatus::Active->value,
        ]);
    }

    public function test_major_projects_module_is_paid_by_default(): void
    {
        $module = Module::query()->where('key', Module::KEY_MAJOR_PROJECTS)->firstOrFail();

        $this->assertTrue($module->is_paid);
    }

    public function test_existing_modules_are_not_unintentionally_disabled(): void
    {
        $modules = Module::query()->get();

        $this->assertTrue($modules->isNotEmpty());
        $this->assertTrue($modules->every(fn (Module $module) => $module->is_active));

        $freeModules = $modules->whereNotIn('key', [Module::KEY_MAJOR_PROJECTS, Module::KEY_LMS]);
        $this->assertTrue($freeModules->every(fn (Module $module) => $module->is_paid === false));
    }
}
