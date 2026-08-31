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

class TimesheetReportsTest extends TestCase
{
    use RefreshDatabase;

    public function test_reports_page_reports_live_totals_for_the_selected_week(): void
    {
        [$user, $project] = $this->companyWithProject();
        $week = now()->startOfWeek();

        $this->timesheet($project, $week, TimesheetStatus::FullyApproved, 40, 'Carpenter');
        $this->timesheet($project, $week, TimesheetStatus::Submitted, 20, 'Carpenter');
        $this->timesheet($project, $week->copy()->subWeek(), TimesheetStatus::FullyApproved, 99, 'Welder');

        $this->actingAs($user)
            ->withSession(['current_project_id' => $project->id])
            ->get(route('timesheets.reports', ['week' => $week->toDateString()]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Timesheets/Reports')
                ->where('stats.0.key', 'total')
                ->where('stats.0.value', '2')
                ->where('stats.1.key', 'fully_approved')
                ->where('stats.1.value', '1')
                ->where('hoursByPosition.0.position', 'Carpenter')
                ->where('hoursByPosition.0.hours', 60)
                ->has('submissionTrend')
                ->has('keyExceptions')
                ->has('footnote.updated_at'));
    }

    public function test_reports_page_omits_the_client_approval_stage(): void
    {
        [$user, $project] = $this->companyWithProject();
        $this->timesheet($project, now()->startOfWeek(), TimesheetStatus::Submitted, 10, 'Welder');

        $this->actingAs($user)
            ->withSession(['current_project_id' => $project->id])
            ->get(route('timesheets.reports'))
            ->assertOk()
            ->assertInertia(function ($page) {
                $keys = collect($page->toArray()['props']['stats'])->pluck('key');
                $exceptions = collect($page->toArray()['props']['keyExceptions'])->pluck('id');

                $this->assertFalse($keys->contains('pending_client'));
                $this->assertFalse($exceptions->contains('overdue-client'));
            });
    }

    public function test_reports_export_streams_a_csv_of_the_current_week(): void
    {
        [$user, $project] = $this->companyWithProject();
        $week = now()->startOfWeek();
        $timesheet = $this->timesheet($project, $week, TimesheetStatus::FullyApproved, 40, 'Carpenter');

        $response = $this->actingAs($user)
            ->withSession(['current_project_id' => $project->id])
            ->get(route('timesheets.reports.export', [
                'week' => $week->toDateString(),
                'type' => 'summary',
            ]));

        $response->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $csv = $response->streamedContent();
        $this->assertStringContainsString('Worker,"Employee ID",Position', $csv);
        $this->assertStringContainsString($timesheet->worker->employee_id, $csv);
    }

    public function test_reports_export_can_list_workers_without_a_timesheet(): void
    {
        [$user, $project] = $this->companyWithProject();
        $week = now()->startOfWeek();
        $worker = Worker::factory()->create([
            'company_id' => $user->company_id,
            'primary_project_id' => $project->id,
            'timesheet_access' => true,
        ]);

        $response = $this->actingAs($user)
            ->withSession(['current_project_id' => $project->id])
            ->get(route('timesheets.reports.export', [
                'week' => $week->toDateString(),
                'type' => 'missing',
            ]));

        $response->assertOk();
        $this->assertStringContainsString($worker->full_name, $response->streamedContent());
    }

    private function timesheet(
        MajorProject $project,
        $week,
        TimesheetStatus $status,
        float $hours,
        string $position,
    ): Timesheet {
        $worker = Worker::factory()->create([
            'company_id' => $project->company_id,
            'primary_project_id' => $project->id,
            'position' => $position,
        ]);

        return Timesheet::factory()->create([
            'company_id' => $project->company_id,
            'major_project_id' => $project->id,
            'worker_id' => $worker->id,
            'period_start' => $week,
            'period_end' => $week->copy()->endOfWeek(),
            'status' => $status,
            'hours' => $hours,
            'submitted_at' => now()->subDay(),
        ]);
    }

    /** @return array{0: User, 1: MajorProject} */
    private function companyWithProject(): array
    {
        $company = Company::factory()->create();
        $user = User::factory()->create([
            'company_id' => $company->id,
            'role' => Role::CompanyAdmin,
        ]);
        $project = MajorProject::factory()->create(['company_id' => $company->id]);

        return [$user, $project];
    }
}
