<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Enums\TimesheetStatus;
use App\Models\Company;
use App\Models\MajorProject;
use App\Models\Timesheet;
use App\Models\User;
use App\Models\Worker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TimesheetEntryTest extends TestCase
{
    use RefreshDatabase;

    public function test_entry_tab_lists_eligible_workers_for_the_selected_week(): void
    {
        [$user, $project] = $this->companyWithProject();
        $worker = Worker::factory()->create([
            'company_id' => $user->company_id,
            'primary_project_id' => $project->id,
            'timesheet_access' => true,
        ]);
        Worker::factory()->create([
            'company_id' => $user->company_id,
            'primary_project_id' => $project->id,
            'timesheet_access' => false,
        ]);

        $this->actingAs($user)
            ->withSession(['current_project_id' => $project->id])
            ->get(route('timesheets.entry'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Timesheets/Entry')
                ->has('roster.rows', 1)
                ->where('roster.rows.0.worker_id', $worker->id)
                ->where('roster.rows.0.status', 'missing')
                ->where('roster.rows.0.can_create', true)
                ->where('canCreate', true)
                ->has('stats', 4)
                ->has('filters.options.weeks'));
    }

    public function test_entry_tab_can_filter_to_workers_without_a_timesheet(): void
    {
        [$user, $project] = $this->companyWithProject();
        $week = now()->startOfWeek();

        $withSheet = Worker::factory()->create([
            'company_id' => $user->company_id,
            'primary_project_id' => $project->id,
        ]);
        $withoutSheet = Worker::factory()->create([
            'company_id' => $user->company_id,
            'primary_project_id' => $project->id,
        ]);

        Timesheet::factory()->create([
            'company_id' => $user->company_id,
            'major_project_id' => $project->id,
            'worker_id' => $withSheet->id,
            'period_start' => $week,
            'period_end' => $week->copy()->endOfWeek(),
        ]);

        $this->actingAs($user)
            ->withSession(['current_project_id' => $project->id])
            ->get(route('timesheets.entry', ['week' => $week->toDateString(), 'status' => 'missing']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('roster.rows', 1)
                ->where('roster.rows.0.worker_id', $withoutSheet->id));
    }

    public function test_starting_a_timesheet_creates_a_draft_and_opens_it(): void
    {
        [$user, $project] = $this->companyWithProject();
        $worker = Worker::factory()->create([
            'company_id' => $user->company_id,
            'primary_project_id' => $project->id,
        ]);
        $week = now()->startOfWeek();

        $response = $this->actingAs($user)
            ->withSession(['current_project_id' => $project->id])
            ->post(route('timesheets.store'), [
                'worker_id' => $worker->id,
                'week' => $week->toDateString(),
            ]);

        $timesheet = Timesheet::query()->where('worker_id', $worker->id)->sole();

        $response->assertRedirect(route('timesheets.show', $timesheet));
        $this->assertSame(TimesheetStatus::Draft, $timesheet->status);
        $this->assertSame($project->id, $timesheet->major_project_id);
        $this->assertCount(7, $timesheet->day_entries);
        $this->assertFalse((bool) $timesheet->client_approval_required);
    }

    public function test_starting_a_timesheet_twice_reuses_the_existing_draft(): void
    {
        [$user, $project] = $this->companyWithProject();
        $worker = Worker::factory()->create([
            'company_id' => $user->company_id,
            'primary_project_id' => $project->id,
        ]);
        $payload = ['worker_id' => $worker->id, 'week' => now()->startOfWeek()->toDateString()];

        $this->actingAs($user)->withSession(['current_project_id' => $project->id])
            ->post(route('timesheets.store'), $payload)->assertRedirect();
        $this->actingAs($user)->withSession(['current_project_id' => $project->id])
            ->post(route('timesheets.store'), $payload)->assertRedirect();

        $this->assertSame(1, Timesheet::query()->where('worker_id', $worker->id)->count());
    }

    public function test_read_only_users_cannot_create_a_timesheet(): void
    {
        [$user, $project] = $this->companyWithProject(Role::ReadOnly);
        $worker = Worker::factory()->create([
            'company_id' => $user->company_id,
            'primary_project_id' => $project->id,
        ]);

        $this->actingAs($user)
            ->post(route('timesheets.store'), [
                'worker_id' => $worker->id,
                'week' => now()->startOfWeek()->toDateString(),
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('timesheets', 0);
    }

    /** @return array{0: User, 1: MajorProject} */
    private function companyWithProject(Role $role = Role::CompanyAdmin): array
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id, 'role' => $role]);
        $project = MajorProject::factory()->create(['company_id' => $company->id]);

        return [$user, $project];
    }
}
