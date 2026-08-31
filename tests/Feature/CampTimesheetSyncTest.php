<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Enums\TimesheetStatus;
use App\Models\CampCompanyLink;
use App\Models\Company;
use App\Models\MajorProject;
use App\Models\Timesheet;
use App\Models\User;
use App\Models\Worker;
use App\Services\Timesheets\CampScheduleRepository;
use App\Services\Timesheets\CampTimesheetSyncService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

class CampTimesheetSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_generates_a_draft_timesheet_from_a_published_camp_schedule(): void
    {
        $rows = $this->scheduleRows();
        $repository = $this->mock(CampScheduleRepository::class);
        $repository->shouldReceive('isAvailable')->once()->andReturnTrue();
        $repository->shouldReceive('companyTree')->once()->andReturn($this->companyTree());
        $repository->shouldReceive('rosterBookings')->once()->andReturn(collect());
        $repository->shouldReceive('scheduledRows')->once()->andReturn($rows);

        $result = app(CampTimesheetSyncService::class)->syncWeek(
            week: '2026-08-03',
            createCompanies: true,
        );

        $this->assertSame(1, $result['companies_synced']);
        $this->assertSame(1, $result['workers_created']);
        $this->assertSame(1, $result['timesheets_created']);

        $company = Company::query()->where('code', 'CAMP-101')->firstOrFail();
        $this->assertDatabaseHas('camp_company_links', [
            'company_id' => $company->id,
            'camp_company_id' => 101,
        ]);

        $worker = Worker::query()->where('company_id', $company->id)->firstOrFail();
        $this->assertSame('BK-500', $worker->employee_id);

        $timesheet = Timesheet::query()->where('worker_id', $worker->id)->firstOrFail();
        $this->assertSame(TimesheetStatus::Draft, $timesheet->status);
        $this->assertCount(7, $timesheet->day_entries);
        $this->assertSame(40.0, (float) $timesheet->regular_hours);
        $this->assertSame(1.0, (float) $timesheet->travel_hours);
        $this->assertDatabaseHas('camp_timesheet_sources', [
            'timesheet_id' => $timesheet->id,
            'camp_schedule_id' => 700,
        ]);
    }

    public function test_it_never_overwrites_a_submitted_timesheet(): void
    {
        $company = Company::query()->create([
            'name' => 'Camp Company',
            'code' => 'CAMP-101',
            'status' => 'active',
        ]);
        CampCompanyLink::query()->create([
            'company_id' => $company->id,
            'camp_company_id' => 101,
            'camp_id' => 10,
            'camp_company_name' => 'Camp Company',
        ]);

        $rows = $this->scheduleRows();
        $repository = $this->mock(CampScheduleRepository::class);
        $repository->shouldReceive('isAvailable')->twice()->andReturnTrue();
        $repository->shouldReceive('companiesFor')->twice()->andReturn($this->companyTree());
        $repository->shouldReceive('rosterBookings')->twice()->andReturn(collect());
        $repository->shouldReceive('scheduledRows')->twice()->andReturn($rows);
        $service = app(CampTimesheetSyncService::class);

        $service->syncWeek('2026-08-03', targetCompany: $company);
        $timesheet = Timesheet::query()->firstOrFail();
        $timesheet->update([
            'status' => TimesheetStatus::Submitted,
            'regular_hours' => 99,
        ]);

        $result = $service->syncWeek('2026-08-03', targetCompany: $company);

        $this->assertSame(1, $result['timesheets_locked']);
        $this->assertSame(99.0, (float) $timesheet->fresh()->regular_hours);
    }

    public function test_target_company_only_syncs_matching_camp_company(): void
    {
        $bakerHughes = Company::query()->create([
            'name' => 'Baker Hughes',
            'code' => 'BKRH',
            'status' => 'active',
        ]);

        $repository = $this->mock(CampScheduleRepository::class);
        $repository->shouldReceive('isAvailable')->once()->andReturnTrue();
        // companiesFor already scopes to the tenant, so it only returns Baker Hughes.
        $repository->shouldReceive('companiesFor')
            ->once()
            ->with(28, [], ['baker hughes'])
            ->andReturn(collect([
                (object) [
                    'id' => 202,
                    'name' => 'Baker Hughes',
                    'hierarchy' => 'prime',
                    'linked' => json_encode(['parent_prime_company_id' => 101]),
                    'camp_id' => 10,
                    'project_id' => 200,
                    'is_client' => 0,
                    'parent_camp_company_id' => 101,
                ],
            ]));
        $repository->shouldReceive('rosterBookings')->once()->andReturn(collect());
        $repository->shouldReceive('scheduledRows')
            ->once()
            ->withArgs(fn ($start, $end, $ids) => $ids === [202])
            ->andReturn($this->scheduleRows(campCompanyId: 202, campCompanyName: 'Baker Hughes'));

        $result = app(CampTimesheetSyncService::class)->syncWeek(
            week: '2026-08-03',
            targetCompany: $bakerHughes,
        );

        $this->assertSame([], $result['errors']);
        $this->assertSame(1, $result['companies_synced']);
        $this->assertSame(1, $result['workers_created']);
        $this->assertDatabaseHas('camp_company_links', [
            'company_id' => $bakerHughes->id,
            'camp_company_id' => 202,
        ]);
        $this->assertSame(1, Worker::query()->where('company_id', $bakerHughes->id)->count());
    }

    public function test_target_company_includes_archived_duplicate_camp_companies(): void
    {
        $bakerHughes = Company::query()->create([
            'name' => 'Baker Hughes',
            'code' => 'BKRH',
            'status' => 'active',
        ]);

        $repository = $this->mock(CampScheduleRepository::class);
        $repository->shouldReceive('isAvailable')->once()->andReturnTrue();
        // Live (309) and archived duplicate (282) both resolve for the tenant.
        $repository->shouldReceive('companiesFor')
            ->once()
            ->andReturn(collect([
                (object) [
                    'id' => 309, 'name' => 'Baker Hughes', 'hierarchy' => 'prime_sub',
                    'linked' => null, 'camp_id' => 28, 'project_id' => 14, 'is_client' => 0,
                    'parent_camp_company_id' => null,
                ],
                (object) [
                    'id' => 282, 'name' => 'Baker Hughes', 'hierarchy' => null,
                    'linked' => null, 'camp_id' => 28, 'project_id' => 14, 'is_client' => 0,
                    'parent_camp_company_id' => null,
                ],
            ]));
        $repository->shouldReceive('rosterBookings')->once()->andReturn(collect());
        $repository->shouldReceive('scheduledRows')
            ->once()
            ->withArgs(fn ($start, $end, $ids) => $ids === [309, 282])
            ->andReturn(
                $this->scheduleRows(campCompanyId: 309, campCompanyName: 'Baker Hughes', bookingId: 500, email: 'ball@example.test')
                    ->concat($this->scheduleRows(campCompanyId: 282, campCompanyName: 'Baker Hughes', bookingId: 600, email: 'gargiulo@example.test'))
            );

        $result = app(CampTimesheetSyncService::class)->syncWeek(
            week: '2026-08-03',
            targetCompany: $bakerHughes,
        );

        $this->assertSame([], $result['errors']);
        $this->assertSame(2, $result['companies_synced']);
        $this->assertSame(2, $result['workers_created']);
        $this->assertSame(2, Worker::query()->where('company_id', $bakerHughes->id)->count());

        // Both Camp duplicates collapse onto the one tenant without inventing projects.
        $this->assertSame(0, MajorProject::query()->where('company_id', $bakerHughes->id)->count());
        $this->assertSame(
            [null],
            Worker::query()->where('company_id', $bakerHughes->id)->pluck('primary_project_id')->unique()->all(),
        );
    }

    public function test_camp_sync_never_creates_a_major_project(): void
    {
        $company = Company::query()->create([
            'name' => 'Baker Hughes',
            'code' => 'BKRH',
            'status' => 'active',
        ]);

        $repository = $this->mock(CampScheduleRepository::class);
        $repository->shouldReceive('isAvailable')->once()->andReturnTrue();
        $repository->shouldReceive('companiesFor')->once()->andReturn($this->companyTree());
        $repository->shouldReceive('rosterBookings')->once()->andReturn(collect());
        $repository->shouldReceive('scheduledRows')->once()->andReturn($this->scheduleRows());

        $result = app(CampTimesheetSyncService::class)->syncWeek(
            week: '2026-08-03',
            targetCompany: $company,
        );

        $this->assertSame([], $result['errors']);
        $this->assertSame(1, $result['workers_created']);
        $this->assertSame(0, MajorProject::withoutGlobalScopes()->count());
        $this->assertDatabaseCount('camp_project_links', 0);

        $worker = Worker::query()->where('company_id', $company->id)->firstOrFail();
        $this->assertNull($worker->primary_project_id);
        $this->assertNull(Timesheet::query()->firstOrFail()->major_project_id);
    }

    public function test_it_imports_the_roster_when_the_week_has_no_schedule_rows(): void
    {
        $company = Company::query()->create([
            'name' => 'Belair',
            'code' => 'BLAI',
            'status' => 'active',
        ]);

        $repository = $this->mock(CampScheduleRepository::class);
        $repository->shouldReceive('isAvailable')->once()->andReturnTrue();
        $repository->shouldReceive('companiesFor')->once()->andReturn($this->companyTree());
        $repository->shouldReceive('rosterBookings')
            ->once()
            ->withArgs(fn ($campId, $ids) => $ids === [101])
            ->andReturn($this->rosterBookings());
        // The rotation finished before this payroll week, so Camp has nothing scheduled.
        $repository->shouldReceive('scheduledRows')->once()->andReturn(collect());

        $result = app(CampTimesheetSyncService::class)->syncWeek(
            week: '2026-08-10',
            targetCompany: $company,
        );

        $this->assertSame([], $result['errors']);
        $this->assertSame(2, $result['workers_created']);
        $this->assertSame(0, $result['timesheets_created']);
        $this->assertSame(2, Worker::query()->where('company_id', $company->id)->count());
        $this->assertDatabaseHas('workers', [
            'company_id' => $company->id,
            'employee_id' => 'BK-800',
            'employer_name' => 'Camp Company',
        ]);
    }

    public function test_roster_import_does_not_duplicate_workers_on_a_second_sync(): void
    {
        $company = Company::query()->create([
            'name' => 'Belair',
            'code' => 'BLAI',
            'status' => 'active',
        ]);

        $repository = $this->mock(CampScheduleRepository::class);
        $repository->shouldReceive('isAvailable')->twice()->andReturnTrue();
        $repository->shouldReceive('companiesFor')->twice()->andReturn($this->companyTree());
        $repository->shouldReceive('rosterBookings')->twice()->andReturn($this->rosterBookings());
        $repository->shouldReceive('scheduledRows')->twice()->andReturn(collect());

        $service = app(CampTimesheetSyncService::class);
        $service->syncWeek('2026-08-10', targetCompany: $company);
        $result = $service->syncWeek('2026-08-10', targetCompany: $company);

        $this->assertSame(0, $result['workers_created']);
        $this->assertSame(2, Worker::query()->where('company_id', $company->id)->count());
    }

    public function test_company_admin_login_syncs_workers_and_timesheets(): void
    {
        $company = Company::query()->create([
            'name' => 'Camp Company',
            'code' => 'CAMP-101',
            'status' => 'active',
        ]);

        $admin = User::factory()->create([
            'company_id' => $company->id,
            'email' => 'admin@campcompany.test',
            'password' => 'password',
            'role' => Role::CompanyAdmin,
        ]);

        $repository = $this->mock(CampScheduleRepository::class);
        $repository->shouldReceive('isAvailable')->once()->andReturnTrue();
        $repository->shouldReceive('companiesFor')->once()->andReturn($this->companyTree());
        $repository->shouldReceive('rosterBookings')->once()->andReturn(collect());
        $repository->shouldReceive('scheduledRows')->once()->andReturn($this->scheduleRows());

        $response = $this->post('/login', [
            'email' => $admin->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($admin);
        $response->assertRedirect(route('dashboard', absolute: false));
        $this->assertSame(1, Worker::query()->where('company_id', $company->id)->count());
        $this->assertSame(1, Timesheet::query()->count());
    }

    public function test_super_admin_login_does_not_auto_sync(): void
    {
        $admin = User::factory()->create([
            'company_id' => null,
            'email' => 'admin@crewhub.test',
            'password' => 'password',
            'role' => Role::SuperAdmin,
        ]);

        $this->mock(CampScheduleRepository::class)->shouldNotReceive('isAvailable');

        $response = $this->post('/login', [
            'email' => $admin->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($admin);
        $response->assertRedirect(route('dashboard', absolute: false));
        $this->assertSame(0, Worker::query()->count());
    }

    protected function companyTree(): Collection
    {
        return collect([
            (object) [
                'id' => 101,
                'name' => 'Camp Company',
                'hierarchy' => 'prime',
                'linked' => null,
                'camp_id' => 10,
                'project_id' => 200,
                'is_client' => 1,
                'parent_camp_company_id' => null,
            ],
        ]);
    }

    /**
     * Reservations with no schedule dates left in any upcoming payroll week.
     */
    protected function rosterBookings(int $campCompanyId = 101, string $campCompanyName = 'Camp Company'): Collection
    {
        return collect([800, 801])->map(fn (int $bookingId) => (object) [
            'camp_booking_id' => $bookingId,
            'booking_code' => 'BK-'.$bookingId,
            'first_name' => 'Riley',
            'last_name' => 'Rotation '.$bookingId,
            'email' => "riley{$bookingId}@example.test",
            'camp_company_id' => $campCompanyId,
            'camp_company_name' => $campCompanyName,
            'camp_id' => 10,
            'reservation_status' => 'check_out',
            'check_in' => '2026-07-20',
            'check_out' => '2026-07-31',
            'position_name' => 'Millwright',
        ]);
    }

    protected function scheduleRows(
        int $campCompanyId = 101,
        string $campCompanyName = 'Camp Company',
        int $bookingId = 500,
        string $email = 'alex@example.test',
    ): Collection {
        $rows = collect();
        $start = Carbon::parse('2026-08-03');

        for ($offset = 0; $offset < 6; $offset++) {
            $rows->push((object) [
                'schedule_date_id' => ($bookingId * 10) + $offset,
                'work_date' => $start->copy()->addDays($offset)->toDateString(),
                'needs_room' => true,
                'schedule_date_updated_at' => '2026-08-01 10:00:00',
                'day_type' => $offset === 5 ? 'Travel Day' : 'Work Day',
                'camp_schedule_id' => $bookingId + 200,
                'schedule_updated_at' => '2026-08-01 10:00:00',
                'camp_booking_id' => $bookingId,
                'booking_code' => 'BK-'.$bookingId,
                'first_name' => 'Alex',
                'last_name' => 'Worker '.$bookingId,
                'email' => $email,
                'camp_company_id' => $campCompanyId,
                'camp_company_name' => $campCompanyName,
                'camp_id' => 10,
                'reservation_status' => 'in_house',
                'check_in' => '2026-08-03',
                'check_out' => '2026-08-09',
                'booking_updated_at' => '2026-08-01 10:00:00',
                'camp_project_id' => 200,
                'camp_project_name' => 'North Project',
                'camp_project_number' => 'NP-200',
                'position_name' => 'Operator',
                'shift_name' => 'Day',
                'supervisor_name' => 'Sam Supervisor',
            ]);
        }

        return $rows;
    }
}
