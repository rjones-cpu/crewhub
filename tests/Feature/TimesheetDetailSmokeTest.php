<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Timesheet;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TimesheetDetailSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_timesheet_detail_page_renders(): void
    {
        $timesheet = Timesheet::factory()->create();
        $user = User::factory()->create([
            'company_id' => $timesheet->company_id,
            'role' => Role::SuperAdmin,
        ]);

        $this->actingAs($user)
            ->get(route('timesheets.show', $timesheet))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Timesheets/Show')
                ->has('timesheet.requirements.mileage')
                ->has('timesheet.approval_settings.worker')
                ->has('timesheet.week_number')
                // Worker, accommodation and manager: the client step is off by default.
                ->has('approvalRecord', 3)
                ->has('approvalRecord.0.title')
                ->has('approvalRecord.0.state'));
    }
}
