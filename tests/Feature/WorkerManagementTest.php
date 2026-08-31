<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\MajorProject;
use App\Models\User;
use App\Models\Worker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class WorkerManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_workers_index_returns_reference_design_data(): void
    {
        $user = User::factory()->create(['role' => Role::CompanyAdmin]);
        Worker::factory()->create([
            'company_id' => $user->company_id,
            'status' => 'inactive',
            'schedule_access' => false,
        ]);

        $this->actingAs($user)
            ->get(route('workers.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('stats.total', 1)
                ->where('stats.inactive', 1)
                ->where('stats.on_leave', 0)
                ->where('featureSummary.schedule.enabled', false)
                ->where('featureSummary.timesheet.enabled', true)
                ->has('projects')
                ->has('company'));
    }

    public function test_worker_stats_count_all_company_workers_not_the_session_project(): void
    {
        $user = User::factory()->create(['role' => Role::CompanyAdmin]);
        $currentProject = MajorProject::factory()->create(['company_id' => $user->company_id]);
        $otherProject = MajorProject::factory()->create(['company_id' => $user->company_id]);

        Worker::factory()->create([
            'company_id' => $user->company_id,
            'primary_project_id' => $currentProject->id,
            'status' => 'active',
        ]);
        Worker::factory()->create([
            'company_id' => $user->company_id,
            'primary_project_id' => $otherProject->id,
            'status' => 'active',
        ]);
        Worker::factory()->create([
            'company_id' => $user->company_id,
            'primary_project_id' => null,
            'status' => 'inactive',
        ]);

        $this->actingAs($user)
            ->withSession(['current_project_id' => $currentProject->id])
            ->get(route('workers.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('stats.total', 3)
                ->where('stats.active', 2)
                ->where('stats.inactive', 1)
                ->where('workers.meta.total', 3));
    }

    public function test_company_feature_switch_updates_all_company_workers(): void
    {
        $user = User::factory()->create(['role' => Role::CompanyAdmin]);
        $workers = Worker::factory()->count(2)->create([
            'company_id' => $user->company_id,
            'schedule_access' => true,
        ]);
        $otherCompanyWorker = Worker::factory()->create(['schedule_access' => true]);

        $this->actingAs($user)
            ->patch(route('workers.features.update', 'schedule'), ['enabled' => false])
            ->assertRedirect();

        $workers->each(
            fn (Worker $worker) => $this->assertFalse($worker->fresh()->schedule_access)
        );
        $this->assertTrue($otherCompanyWorker->fresh()->schedule_access);
    }

    public function test_project_modules_cap_worker_feature_access(): void
    {
        $user = User::factory()->create(['role' => Role::CompanyAdmin]);
        $project = MajorProject::factory()->create([
            'company_id' => $user->company_id,
            'modules' => [
                ...MajorProject::defaultModules(),
                'schedule' => false,
            ],
        ]);
        $worker = Worker::factory()->create([
            'company_id' => $user->company_id,
            'primary_project_id' => $project->id,
            'schedule_access' => true,
        ]);

        $this->actingAs($user)
            ->get(route('workers.index'))
            ->assertInertia(fn ($page) => $page
                ->where('featureSummary.schedule.enabled', false)
                ->where('featureSummary.schedule.enabled_count', 0)
                ->where('featureSummary.schedule.project_blocked_count', 1)
                ->where('workers.data.0.tool_access.schedule', false));

        $this->patch(route('workers.features.update', 'schedule'), ['enabled' => true])
            ->assertRedirect();

        $this->assertFalse($worker->fresh()->schedule_access);

        $this->patch(route('workers.tools.update', $worker), ['schedule_access' => true])
            ->assertSessionHasErrors('schedule_access');
    }

    public function test_company_admin_can_create_worker_from_drawer_with_documents(): void
    {
        Storage::fake('public');

        $user = User::factory()->create(['role' => Role::CompanyAdmin]);
        $project = MajorProject::factory()->create(['company_id' => $user->company_id]);

        $response = $this->actingAs($user)->post(route('workers.store'), [
            'employee_id' => 'WRK-1001',
            'first_name' => 'Alex',
            'last_name' => 'Morgan',
            'email' => 'alex@example.test',
            'phone' => '+1 250 555 0100',
            'gender' => 'prefer_not_to_say',
            'position' => 'Electrician',
            'trade' => 'Electrical',
            'status' => 'active',
            'primary_project_id' => $project->id,
            'start_date' => '2026-08-10',
            'notes' => 'Night shift.',
            'documents' => [
                UploadedFile::fake()->create('orientation.pdf', 100, 'application/pdf'),
            ],
        ]);

        $response->assertRedirect(route('workers.index'));

        $worker = Worker::query()->where('employee_id', 'WRK-1001')->firstOrFail();

        $this->assertSame($user->company_id, $worker->company_id);
        $this->assertSame('Electrical', $worker->trade);
        $this->assertSame('orientation.pdf', $worker->documents[0]['name']);
        Storage::disk('public')->assertExists($worker->documents[0]['path']);
    }

    public function test_employee_id_and_primary_project_are_optional_when_creating_a_worker(): void
    {
        $user = User::factory()->create(['role' => Role::CompanyAdmin]);

        $this->actingAs($user)
            ->post(route('workers.store'), [
                'first_name' => 'Jordan',
                'last_name' => 'Lee',
                'email' => 'jordan@example.test',
                'status' => 'active',
            ])
            ->assertRedirect(route('workers.index'));

        $worker = Worker::query()
            ->where('company_id', $user->company_id)
            ->where('first_name', 'Jordan')
            ->firstOrFail();

        $this->assertNull($worker->employee_id);
        $this->assertNull($worker->primary_project_id);
    }
}
