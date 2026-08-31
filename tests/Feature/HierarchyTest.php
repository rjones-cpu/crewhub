<?php

namespace Tests\Feature;

use App\Enums\DelegationStatus;
use App\Enums\ManagerRelationship;
use App\Enums\Role;
use App\Models\Company;
use App\Models\CompanyProjectMembership;
use App\Models\MajorProject;
use App\Models\ProjectManagerLink;
use App\Models\ResponsibilityDelegation;
use App\Models\User;
use App\Models\Worker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HierarchyTest extends TestCase
{
    use RefreshDatabase;

    private function companyUser(Role $role = Role::WorkforceManager): User
    {
        $company = Company::factory()->create();

        return User::factory()->create(['company_id' => $company->id, 'role' => $role]);
    }

    public function test_hierarchy_page_renders_with_connected_managers(): void
    {
        $user = $this->companyUser();
        $project = MajorProject::factory()->create([
            'company_id' => $user->company_id,
            'status' => 'active',
        ]);

        $manager = User::factory()->create(['company_id' => $user->company_id]);

        ProjectManagerLink::create([
            'company_id' => $user->company_id,
            'major_project_id' => $project->id,
            'user_id' => $manager->id,
            'title' => 'Major Project Manager',
            'relationship' => ManagerRelationship::Primary,
        ]);

        $this->actingAs($user)
            ->withSession(['current_project_id' => $project->id])
            ->get(route('hierarchy.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Hierarchy/Index')
                ->has('managers', 1)
                ->where('managers.0.name', $manager->name)
                ->where('managers.0.relationship', 'primary')
                ->has('delegations', 5)
                ->has('approvalPath', 4)
                ->has('accountability', 1));
    }

    public function test_switching_between_projects_loads_the_selected_project_hierarchy(): void
    {
        $user = $this->companyUser();
        $projectA = MajorProject::factory()->create([
            'company_id' => $user->company_id,
            'name' => 'Alpha Project',
            'status' => 'active',
        ]);
        $projectB = MajorProject::factory()->create([
            'company_id' => $user->company_id,
            'name' => 'Bravo Project',
            'status' => 'active',
        ]);
        $managerA = User::factory()->create([
            'company_id' => $user->company_id,
            'name' => 'Alpha Manager',
        ]);
        $managerB = User::factory()->create([
            'company_id' => $user->company_id,
            'name' => 'Bravo Manager',
        ]);

        ProjectManagerLink::create([
            'company_id' => $user->company_id,
            'major_project_id' => $projectA->id,
            'user_id' => $managerA->id,
            'relationship' => ManagerRelationship::Primary,
        ]);
        ProjectManagerLink::create([
            'company_id' => $user->company_id,
            'major_project_id' => $projectB->id,
            'user_id' => $managerB->id,
            'relationship' => ManagerRelationship::Primary,
        ]);

        $this->actingAs($user)
            ->from(route('hierarchy.index'))
            ->post(route('major-projects.switch', $projectB))
            ->assertRedirect(route('hierarchy.index'));

        $this->get(route('hierarchy.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Hierarchy/Index')
                ->has('majorProjects', 2)
                ->where('currentProject.id', $projectB->id)
                ->where('project.id', $projectB->id)
                ->has('managers', 1)
                ->where('managers.0.name', 'Bravo Manager'));
    }

    public function test_hierarchy_defaults_to_the_first_project_when_none_is_selected(): void
    {
        $user = $this->companyUser();
        MajorProject::factory()->create([
            'company_id' => $user->company_id,
            'name' => 'Zulu Project',
            'status' => 'active',
        ]);
        $first = MajorProject::factory()->create([
            'company_id' => $user->company_id,
            'name' => 'Alpha Project',
            'status' => 'active',
        ]);

        $this->actingAs($user)
            ->get(route('hierarchy.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('majorProjects', 2)
                ->where('currentProject.id', $first->id)
                ->where('project.id', $first->id));

        $this->assertSame($first->id, session('current_project_id'));
    }

    public function test_page_renders_without_a_connected_project(): void
    {
        $this->actingAs($this->companyUser())
            ->get(route('hierarchy.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Hierarchy/Index')
                ->where('project', null));
    }

    public function test_manager_can_be_connected_and_disconnected(): void
    {
        $user = $this->companyUser();
        $project = MajorProject::factory()->create([
            'company_id' => $user->company_id,
            'status' => 'active',
        ]);
        $manager = User::factory()->create(['company_id' => $user->company_id]);

        $this->actingAs($user)
            ->post(route('hierarchy.managers.store'), [
                'major_project_id' => $project->id,
                'user_id' => $manager->id,
                'title' => 'Deputy Project Manager',
                'relationship' => 'connected',
            ])
            ->assertRedirect();

        $link = ProjectManagerLink::query()->where('user_id', $manager->id)->firstOrFail();

        $this->actingAs($user)
            ->delete(route('hierarchy.managers.destroy', $link->id))
            ->assertRedirect();

        $this->assertDatabaseMissing('project_manager_links', ['id' => $link->id]);
    }

    public function test_second_layer_allows_multiple_managers(): void
    {
        $user = $this->companyUser();
        $project = MajorProject::factory()->create([
            'company_id' => $user->company_id,
            'status' => 'active',
        ]);
        $primary = User::factory()->create(['company_id' => $user->company_id, 'name' => 'Primary Mgr']);
        $connected = User::factory()->create(['company_id' => $user->company_id, 'name' => 'Connected Mgr']);

        $this->actingAs($user)
            ->post(route('hierarchy.managers.store'), [
                'major_project_id' => $project->id,
                'user_id' => $primary->id,
                'relationship' => 'primary',
            ])
            ->assertRedirect();

        $this->actingAs($user)
            ->post(route('hierarchy.managers.store'), [
                'major_project_id' => $project->id,
                'user_id' => $connected->id,
                'relationship' => 'connected',
            ])
            ->assertRedirect();

        $this->actingAs($user)
            ->withSession(['current_project_id' => $project->id])
            ->get(route('hierarchy.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Hierarchy/Index')
                ->has('managers', 2)
                ->where('managers.0.relationship', 'primary')
                ->where('managers.1.relationship', 'connected'));
    }

    public function test_member_company_hierarchy_records_belong_to_the_member_company(): void
    {
        $owner = Company::factory()->create();
        $member = Company::factory()->create();
        $user = User::factory()->create([
            'company_id' => $member->id,
            'role' => Role::WorkforceManager,
        ]);
        $manager = User::factory()->create(['company_id' => $member->id]);
        $project = MajorProject::factory()->create([
            'company_id' => $owner->id,
            'status' => 'active',
        ]);
        CompanyProjectMembership::query()->create([
            'company_id' => $member->id,
            'major_project_id' => $project->id,
            'role' => 'Contractor',
            'status' => 'active',
            'joined_at' => now(),
        ]);

        $this->actingAs($user)
            ->post(route('hierarchy.managers.store'), [
                'major_project_id' => $project->id,
                'user_id' => $manager->id,
                'relationship' => 'primary',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('project_manager_links', [
            'company_id' => $member->id,
            'major_project_id' => $project->id,
            'user_id' => $manager->id,
        ]);
    }

    public function test_delegating_a_responsibility_marks_it_accepted(): void
    {
        $user = $this->companyUser();
        $project = MajorProject::factory()->create([
            'company_id' => $user->company_id,
            'status' => 'active',
        ]);
        $manager = User::factory()->create(['company_id' => $user->company_id]);

        ProjectManagerLink::create([
            'company_id' => $user->company_id,
            'major_project_id' => $project->id,
            'user_id' => $manager->id,
            'relationship' => ManagerRelationship::Primary,
        ]);

        $this->actingAs($user)
            ->patch(route('hierarchy.delegations.update'), [
                'major_project_id' => $project->id,
                'area' => 'Time Sheets',
                'is_delegable' => true,
            ])
            ->assertRedirect();

        $delegation = ResponsibilityDelegation::query()->where('area', 'Time Sheets')->firstOrFail();

        $this->assertSame(DelegationStatus::Accepted, $delegation->status);
        $this->assertTrue($delegation->is_delegable);
    }

    public function test_workforce_endpoint_paginates_and_searches(): void
    {
        $user = $this->companyUser();
        $project = MajorProject::factory()->create([
            'company_id' => $user->company_id,
            'status' => 'active',
        ]);

        Worker::factory(12)->create([
            'company_id' => $user->company_id,
            'primary_project_id' => $project->id,
        ]);

        $target = Worker::factory()->create([
            'company_id' => $user->company_id,
            'primary_project_id' => $project->id,
            'first_name' => 'Zenobia',
            'last_name' => 'Quist',
        ]);

        $this->actingAs($user)
            ->getJson(route('hierarchy.workforce', ['project_id' => $project->id]))
            ->assertOk()
            ->assertJsonCount(10, 'data')
            ->assertJsonPath('meta.total', 13);

        $this->actingAs($user)
            ->getJson(route('hierarchy.workforce', [
                'project_id' => $project->id,
                'search' => 'Zenobia',
            ]))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', $target->full_name);
    }

    public function test_read_only_users_cannot_change_delegations(): void
    {
        $user = $this->companyUser(Role::ReadOnly);
        $project = MajorProject::factory()->create([
            'company_id' => $user->company_id,
            'status' => 'active',
        ]);

        $this->actingAs($user)
            ->patch(route('hierarchy.delegations.update'), [
                'major_project_id' => $project->id,
                'area' => 'Time Sheets',
                'is_delegable' => true,
            ])
            ->assertForbidden();
    }
}
