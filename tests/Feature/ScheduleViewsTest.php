<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Enums\ScheduleDayType;
use App\Models\Company;
use App\Models\MajorProject;
use App\Models\User;
use App\Models\Worker;
use App\Models\WorkerScheduleDay;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Covers the List, Calendar, and Change Request views that sit alongside the
 * schedule board.
 */
class ScheduleViewsTest extends TestCase
{
    use RefreshDatabase;

    private function managerUser(): User
    {
        $company = Company::factory()->create();

        return User::factory()->create([
            'company_id' => $company->id,
            'role' => Role::WorkforceManager,
        ]);
    }

    private function rosterWeek(Worker $worker, MajorProject $project, Carbon $start, int $days = 7): void
    {
        for ($offset = 0; $offset < $days; $offset++) {
            WorkerScheduleDay::query()->create([
                'company_id' => $worker->company_id,
                'worker_id' => $worker->id,
                'major_project_id' => $project->id,
                'date' => $start->copy()->addDays($offset)->toDateString(),
                'day_type' => $offset === 0 ? ScheduleDayType::Travel : ScheduleDayType::Work,
                'needs_room' => true,
            ]);
        }
    }

    public function test_list_view_returns_a_seven_day_window_per_worker(): void
    {
        $user = $this->managerUser();
        $project = MajorProject::factory()->create([
            'company_id' => $user->company_id,
            'status' => 'active',
        ]);

        $worker = Worker::factory()->create([
            'company_id' => $user->company_id,
            'primary_project_id' => $project->id,
            'first_name' => 'Alicia',
            'last_name' => 'Peters',
            'position' => 'Housekeeper',
        ]);

        $this->rosterWeek($worker, $project, Carbon::today());

        $this->actingAs($user)
            ->get(route('schedule.index', ['view' => 'list']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('view', 'list')
                ->has('listView.days', 7)
                ->has('listView.rows', 1)
                ->where('listView.rows.0.name', 'Alicia Peters')
                ->where('listView.rows.0.department', 'Housekeeping')
                ->has('listView.rows.0.cells', 7)
                // The first rostered day is a travel day, which reads as on-call cover.
                ->where('listView.rows.0.cells.0.status', 'on_call')
                ->where('listView.rows.0.cells.1.status', 'day')
                ->where('listView.pagination.total', 1)
                ->has('kpis', 5)
            );
    }

    public function test_department_filter_narrows_the_list(): void
    {
        $user = $this->managerUser();
        $project = MajorProject::factory()->create([
            'company_id' => $user->company_id,
            'status' => 'active',
        ]);

        Worker::factory()->create([
            'company_id' => $user->company_id,
            'primary_project_id' => $project->id,
            'position' => 'Housekeeper',
        ]);

        Worker::factory()->create([
            'company_id' => $user->company_id,
            'primary_project_id' => $project->id,
            'position' => 'Cook',
        ]);

        $this->actingAs($user)
            ->get(route('schedule.index', ['view' => 'list', 'department' => 'Kitchen']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('listView.rows', 1)
                ->where('listView.rows.0.department', 'Kitchen')
            );
    }

    public function test_calendar_view_returns_two_weeks_and_a_context_rail(): void
    {
        $user = $this->managerUser();
        $project = MajorProject::factory()->create([
            'company_id' => $user->company_id,
            'status' => 'active',
        ]);

        $worker = Worker::factory()->create([
            'company_id' => $user->company_id,
            'primary_project_id' => $project->id,
            'position' => 'Housekeeper',
        ]);

        $this->rosterWeek($worker, $project, Carbon::today()->startOfWeek(Carbon::MONDAY), 14);

        $this->actingAs($user)
            ->get(route('schedule.index', ['view' => 'calendar']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('calendarView.weeks', 2)
                ->has('calendarView.weeks.0.days', 7)
                ->has('calendarView.weeks.0.days.0.shifts.day')
                ->has('calendarView.weeks.0.days.0.coverage.tone')
                ->has('calendarView.rail.positions.rows')
                ->where('calendarView.rail.positions.total.required', 1)
            );
    }

    public function test_change_requests_carry_a_selected_detail(): void
    {
        $user = $this->managerUser();
        $project = MajorProject::factory()->create([
            'company_id' => $user->company_id,
            'status' => 'active',
        ]);

        Worker::factory()->count(12)->create([
            'company_id' => $user->company_id,
            'primary_project_id' => $project->id,
            'position' => 'Housekeeper',
        ]);

        $this->actingAs($user)
            ->get(route('schedule.index', ['view' => 'requests']))
            ->assertOk()
            ->assertInertia(function ($page) {
                $page->has('changeRequests.kpis', 5)
                    ->has('changeRequests.types', 7);

                $selected = $page->toArray()['props']['changeRequests']['selected'] ?? null;

                if ($selected !== null) {
                    $this->assertArrayHasKey('approval_chain', $selected);
                    $this->assertArrayHasKey('coverage_impact', $selected);
                    $this->assertArrayHasKey('timeline', $selected);
                }

                return $page;
            });
    }

    public function test_week_parameter_moves_the_window(): void
    {
        $user = $this->managerUser();
        $week = Carbon::today()->addDays(14)->toDateString();

        $this->actingAs($user)
            ->get(route('schedule.index', ['view' => 'list', 'week' => $week]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('filters.week', $week)
                ->where('listView.days.0.date', $week)
            );
    }
}
