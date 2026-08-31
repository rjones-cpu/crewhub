<?php

namespace Tests\Feature;

use App\Enums\DelegationStatus;
use App\Enums\ManagerRelationship;
use App\Enums\Role;
use App\Enums\TimesheetStatus;
use App\Models\Company;
use App\Models\MajorProject;
use App\Models\ProjectManagerLink;
use App\Models\ResponsibilityDelegation;
use App\Models\Timesheet;
use App\Models\User;
use App\Models\Worker;
use App\Policies\TimesheetPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TimesheetApprovalWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_approval_fully_approves_without_a_client_gate(): void
    {
        [$user, $timesheet] = $this->submittedTimesheet();

        $this->actingAs($user)
            ->post(route('timesheets.approve-manager', $timesheet))
            ->assertRedirect();

        $timesheet->refresh();
        $this->assertSame(TimesheetStatus::FullyApproved, $timesheet->status);
        $this->assertNotNull($timesheet->manager_approved_at);
        $this->assertNotNull($timesheet->approved_at);
        $this->assertNull($timesheet->client_approved_at);
    }

    public function test_client_approval_flag_on_the_sheet_does_not_add_a_second_gate(): void
    {
        [$user, $timesheet] = $this->submittedTimesheet(['client_approval_required' => true]);

        $this->actingAs($user)
            ->post(route('timesheets.approve-manager', $timesheet))
            ->assertRedirect();

        $this->assertSame(TimesheetStatus::FullyApproved, $timesheet->refresh()->status);
    }

    public function test_a_sheet_left_at_manager_approved_is_finalised_by_the_manager(): void
    {
        [$user, $timesheet] = $this->submittedTimesheet([
            'status' => TimesheetStatus::ManagerApproved,
            'client_approval_required' => true,
        ]);

        $this->actingAs($user)
            ->post(route('timesheets.approve-manager', $timesheet))
            ->assertRedirect();

        $this->assertSame(TimesheetStatus::FullyApproved, $timesheet->refresh()->status);
    }

    public function test_only_the_delegated_timesheet_manager_can_approve(): void
    {
        [$user, $timesheet, $project] = $this->submittedTimesheet();
        $delegate = User::factory()->create([
            'company_id' => $user->company_id,
            'role' => Role::WorkforceManager,
        ]);

        $this->delegateTimesheets($project, $delegate);

        $this->actingAs($user)
            ->post(route('timesheets.approve-manager', $timesheet))
            ->assertForbidden();

        $this->assertSame(TimesheetStatus::Submitted, $timesheet->refresh()->status);

        $this->actingAs($delegate)
            ->post(route('timesheets.approve-manager', $timesheet))
            ->assertRedirect();

        $this->assertSame(TimesheetStatus::FullyApproved, $timesheet->refresh()->status);
    }

    public function test_approval_falls_back_to_manager_roles_when_no_delegation_exists(): void
    {
        [$user, $timesheet] = $this->submittedTimesheet();

        $this->assertTrue($user->can('approve', $timesheet));
    }

    public function test_the_approval_tab_renders_the_live_queue_without_a_client_stage(): void
    {
        [$user, $timesheet] = $this->submittedTimesheet();

        $this->actingAs($user)
            ->get(route('timesheets.approval'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Timesheets/Approval')
                ->where('clientApprovalEnabled', false)
                ->has('queue.rows', 1)
                ->where('queue.rows.0.id', $timesheet->id)
                ->where('selected.can.approve_manager', true)
                ->where('selected.can.approve_client', false)
                ->has('stats')
                ->has('filters.options.weeks'));
    }

    /** @return array{0: User, 1: Timesheet, 2: MajorProject} */
    private function submittedTimesheet(array $attributes = []): array
    {
        $company = Company::factory()->create();
        $user = User::factory()->create([
            'company_id' => $company->id,
            'role' => Role::CompanyAdmin,
        ]);
        $project = MajorProject::factory()->create(['company_id' => $company->id]);
        $worker = Worker::factory()->create([
            'company_id' => $company->id,
            'primary_project_id' => $project->id,
        ]);

        $timesheet = Timesheet::factory()->create([
            'company_id' => $company->id,
            'major_project_id' => $project->id,
            'worker_id' => $worker->id,
            'status' => TimesheetStatus::Submitted,
            'client_approval_required' => false,
            'submitted_at' => now()->subDay(),
            ...$attributes,
        ]);

        return [$user, $timesheet, $project];
    }

    private function delegateTimesheets(MajorProject $project, User $manager): void
    {
        $link = ProjectManagerLink::create([
            'company_id' => $project->company_id,
            'major_project_id' => $project->id,
            'user_id' => $manager->id,
            'title' => 'Timesheet Manager',
            'relationship' => ManagerRelationship::Primary,
        ]);

        ResponsibilityDelegation::create([
            'company_id' => $project->company_id,
            'major_project_id' => $project->id,
            'project_manager_link_id' => $link->id,
            'area' => TimesheetPolicy::APPROVAL_AREA,
            'status' => DelegationStatus::Accepted,
            'is_delegable' => true,
        ]);
    }
}
