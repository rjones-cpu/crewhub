<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Enums\ScheduleDayType;
use App\Models\Company;
use App\Models\MajorProject;
use App\Models\User;
use App\Models\Worker;
use App\Models\WorkerScheduleDay;
use App\Services\Schedule\ScheduleBoardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ScheduleBoardTest extends TestCase
{
    use RefreshDatabase;

    private function companyUser(): User
    {
        $company = Company::factory()->create();

        return User::factory()->create([
            'company_id' => $company->id,
            'role' => Role::WorkforceManager,
        ]);
    }

    private function projectFor(User $user, string $name = 'Blue Ridge'): MajorProject
    {
        // The factory attaches the owning company's active membership, which is what
        // makes the project visible to the board.
        return MajorProject::factory()->create([
            'company_id' => $user->company_id,
            'name' => $name,
            'status' => 'active',
        ]);
    }

    private function scheduleDay(Worker $worker, MajorProject $project, string $date, ScheduleDayType $type, bool $needsRoom = true): void
    {
        WorkerScheduleDay::query()->create([
            'company_id' => $worker->company_id,
            'worker_id' => $worker->id,
            'major_project_id' => $project->id,
            'date' => $date,
            'day_type' => $type,
            'needs_room' => $needsRoom,
        ]);
    }

    public function test_board_renders_with_project_tabs_and_worker_rows(): void
    {
        $user = $this->companyUser();
        $project = $this->projectFor($user);

        Worker::factory()->create([
            'company_id' => $user->company_id,
            'primary_project_id' => $project->id,
            'first_name' => 'Dylan',
            'last_name' => 'Puk',
            'position' => 'Heavy Equipment Operator',
        ]);

        $this->actingAs($user)
            ->get(route('schedule.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Schedule/Index')
                ->has('projects', 1)
                ->where('projects.0.name', 'Blue Ridge')
                ->where('projects.0.worker_count', 1)
                ->where('selectedProjectId', null)
                ->has('days', ScheduleBoardService::COLUMN_COUNT)
                ->has('rows', 1)
                ->where('rows.0.last_name', 'Puk')
                ->where('rows.0.position', 'Heavy Equipment Operator')
                ->where('canEdit', true));
    }

    public function test_day_cells_align_with_the_date_axis(): void
    {
        $user = $this->companyUser();
        $project = $this->projectFor($user);
        $worker = Worker::factory()->create([
            'company_id' => $user->company_id,
            'primary_project_id' => $project->id,
        ]);

        $today = Carbon::today();
        $this->scheduleDay($worker, $project, $today->toDateString(), ScheduleDayType::Travel);
        $this->scheduleDay($worker, $project, $today->copy()->addDay()->toDateString(), ScheduleDayType::Work);

        $board = app(ScheduleBoardService::class)->board(null);
        $index = collect($board['days'])->search(fn (array $day) => $day['is_today']);

        $this->assertSame('travel', $board['rows'][0]['cells'][$index]);
        $this->assertSame('work', $board['rows'][0]['cells'][$index + 1]);
        // Days without a row fall back to off, which paints an empty cell.
        $this->assertSame('off', $board['rows'][0]['cells'][$index + 2]);
    }

    public function test_date_columns_expose_the_stacked_header_parts(): void
    {
        $board = app(ScheduleBoardService::class)->board(null);
        $today = Carbon::today();
        $column = collect($board['days'])->firstWhere('is_today', true);

        $this->assertSame(strtoupper($today->format('D')), $column['weekday']);
        $this->assertSame(strtoupper($today->format('M')), $column['month']);
        $this->assertSame($today->format('j'), $column['day']);
    }

    public function test_footer_totals_separate_beds_from_headcount(): void
    {
        $user = $this->companyUser();
        $project = $this->projectFor($user);
        $today = Carbon::today()->toDateString();

        $staying = Worker::factory()->create([
            'company_id' => $user->company_id,
            'primary_project_id' => $project->id,
        ]);
        $leaving = Worker::factory()->create([
            'company_id' => $user->company_id,
            'primary_project_id' => $project->id,
        ]);

        $this->scheduleDay($staying, $project, $today, ScheduleDayType::Work);
        // A departure day still counts as project personnel but frees the bed.
        $this->scheduleDay($leaving, $project, $today, ScheduleDayType::Travel, needsRoom: false);

        $board = app(ScheduleBoardService::class)->board(null);
        $index = collect($board['days'])->search(fn (array $day) => $day['is_today']);

        $this->assertSame(1, $board['totals']['in_lodge'][$index]);
        $this->assertSame(2, $board['totals']['project_personnel'][$index]);
    }

    public function test_selecting_a_project_limits_rows_to_that_project(): void
    {
        $user = $this->companyUser();
        $blueRidge = $this->projectFor($user, 'Blue Ridge');
        $crossCreek = $this->projectFor($user, 'Cross Creek');

        Worker::factory()->create([
            'company_id' => $user->company_id,
            'primary_project_id' => $blueRidge->id,
            'last_name' => 'Puk',
        ]);
        Worker::factory()->create([
            'company_id' => $user->company_id,
            'primary_project_id' => $crossCreek->id,
            'last_name' => 'Wylie',
        ]);

        $this->actingAs($user)
            ->get(route('schedule.index', ['project_id' => $crossCreek->id]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('selectedProjectId', $crossCreek->id)
                ->has('rows', 1)
                ->where('rows.0.last_name', 'Wylie'));
    }

    public function test_another_companys_project_is_not_selectable(): void
    {
        $user = $this->companyUser();
        $this->projectFor($user);
        $outsiderProject = MajorProject::factory()->create([
            'company_id' => Company::factory()->create()->id,
        ]);

        // Falls back to All Projects rather than leaking the other tenant's board.
        $this->actingAs($user)
            ->get(route('schedule.index', ['project_id' => $outsiderProject->id]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('selectedProjectId', null)
                ->has('projects', 1));
    }

    public function test_work_paint_stages_a_draft_without_writing_published_days(): void
    {
        $user = $this->companyUser();
        $project = $this->projectFor($user);
        $worker = Worker::factory()->create([
            'company_id' => $user->company_id,
            'primary_project_id' => $project->id,
        ]);
        $date = Carbon::today()->toDateString();

        $this->actingAs($user)
            ->postJson(route('schedule.paint'), [
                'worker_id' => $worker->id,
                'project_id' => $project->id,
                'dates' => [$date],
                'type' => 'work',
            ])
            ->assertOk()
            ->assertJsonPath('data.cells.'.$date, 'work');

        $this->assertDatabaseMissing('worker_schedule_days', [
            'worker_id' => $worker->id,
            'date' => $date,
        ]);
        $this->assertTrue(
            \App\Models\WorkerScheduleDraftDay::query()
                ->whereDate('date', $date)
                ->where('to_type', 'work')
                ->exists()
        );
    }

    public function test_publish_writes_days_and_syncs_a_lodge_reservation(): void
    {
        $user = $this->companyUser();
        $project = $this->projectFor($user);
        $worker = Worker::factory()->create([
            'company_id' => $user->company_id,
            'primary_project_id' => $project->id,
        ]);
        $today = Carbon::today()->toDateString();
        $tomorrow = Carbon::today()->addDay()->toDateString();
        $departure = Carbon::today()->addDays(2)->toDateString();

        $this->actingAs($user)
            ->postJson(route('schedule.paint'), [
                'worker_id' => $worker->id,
                'project_id' => $project->id,
                'dates' => [$today, $tomorrow, $departure],
                'type' => 'travel',
            ])
            ->assertOk();

        $this->actingAs($user)
            ->post(route('schedule.publish'), ['project_id' => $project->id])
            ->assertRedirect();

        $this->assertTrue(
            WorkerScheduleDay::query()
                ->where('worker_id', $worker->id)
                ->whereDate('date', $today)
                ->where('day_type', 'travel')
                ->exists()
        );
        $this->assertTrue(
            \App\Models\AccommodationAssignment::query()
                ->where('worker_id', $worker->id)
                ->whereDate('check_in', $today)
                ->whereDate('check_out', $departure)
                ->exists()
        );
        $this->assertDatabaseHas('schedule_modification_requests', [
            'worker_id' => $worker->id,
            'status' => 'pending',
        ]);
        $this->assertDatabaseCount('worker_schedule_drafts', 0);
    }

    public function test_yellow_bookend_drag_matches_camp_rules(): void
    {
        $user = $this->companyUser();
        $project = $this->projectFor($user);
        $worker = Worker::factory()->create([
            'company_id' => $user->company_id,
            'primary_project_id' => $project->id,
        ]);
        $start = Carbon::today()->toDateString();
        $mid = Carbon::today()->addDay()->toDateString();
        $end = Carbon::today()->addDays(2)->toDateString();

        $this->scheduleDay($worker, $project, $start, ScheduleDayType::Travel);

        $this->actingAs($user)
            ->postJson(route('schedule.days'), [
                'worker_id' => $worker->id,
                'project_id' => $project->id,
                'source_date' => $start,
                'drop_date' => $end,
                'row_types' => [
                    $start => 'travel',
                    $mid => 'off',
                    $end => 'off',
                ],
            ])
            ->assertOk()
            ->assertJsonPath('data.cells.'.$start, 'travel')
            ->assertJsonPath('data.cells.'.$mid, 'work')
            ->assertJsonPath('data.cells.'.$end, 'travel');
    }

    public function test_reset_discards_drafts(): void
    {
        $user = $this->companyUser();
        $project = $this->projectFor($user);
        $worker = Worker::factory()->create([
            'company_id' => $user->company_id,
            'primary_project_id' => $project->id,
        ]);

        $this->actingAs($user)
            ->postJson(route('schedule.paint'), [
                'worker_id' => $worker->id,
                'project_id' => $project->id,
                'dates' => [Carbon::today()->toDateString()],
                'type' => 'work',
            ])
            ->assertOk();

        $this->actingAs($user)
            ->post(route('schedule.reset'), ['project_id' => $project->id])
            ->assertRedirect();

        $this->assertDatabaseCount('worker_schedule_drafts', 0);
    }

    public function test_read_only_users_cannot_edit(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create([
            'company_id' => $company->id,
            'role' => Role::ReadOnly,
        ]);
        $project = $this->projectFor($user);
        $worker = Worker::factory()->create([
            'company_id' => $company->id,
            'primary_project_id' => $project->id,
        ]);

        $this->actingAs($user)
            ->postJson(route('schedule.paint'), [
                'worker_id' => $worker->id,
                'project_id' => $project->id,
                'dates' => [Carbon::today()->toDateString()],
                'type' => 'work',
            ])
            ->assertStatus(422);
    }
}
