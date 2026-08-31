<?php

namespace App\Console\Commands;

use App\Models\CampCompanyLink;
use App\Models\Company;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PurgeCampTimesheetData extends Command
{
    protected $signature = 'timesheets:purge-camp
        {--all : Also delete seeded demo companies that never came from Camp}
        {--force : Skip the confirmation prompt}';

    protected $description = 'Delete Camp-synced companies, projects, workers and timesheets so the sync can rebuild them';

    /**
     * Every tenant-owned table carries company_id, so deleting by company clears a
     * tenant completely. These are ordered so child rows go before their parents;
     * relying on cascade settings would make the result depend on how each foreign
     * key happened to be declared.
     *
     * @var list<string>
     */
    protected array $tenantTables = [
        'accommodation_assignments',
        'assignment_activities',
        'certifications',
        'journeys',
        'medical_records',
        'notifications',
        'priority_actions',
        'project_manager_links',
        'responsibility_delegations',
        'schedule_forecasts',
        'timesheets',
        'training_records',
        'worker_activities',
        'worker_assignments',
        'worker_readiness',
        'accommodations',
        'workers',
        'major_projects',
    ];

    public function handle(): int
    {
        $campCompanyIds = CampCompanyLink::query()->pluck('company_id')->unique();

        $companyIds = $this->option('all')
            ? Company::withTrashed()->pluck('id')
            : $campCompanyIds;

        if ($companyIds->isEmpty()) {
            $this->info('Nothing to purge.');

            return self::SUCCESS;
        }

        $names = Company::withTrashed()->whereIn('id', $companyIds)->pluck('name');

        $this->warn(sprintf('About to delete %d companies and everything under them:', $names->count()));
        $this->line('  '.$names->implode(', '));

        if (! $this->option('force') && ! $this->confirm('Continue?')) {
            return self::SUCCESS;
        }

        DB::transaction(function () use ($companyIds) {
            // Camp link tables first: they point at timesheets, workers and projects.
            DB::table('camp_timesheet_sources')->delete();
            DB::table('camp_booking_worker_links')->delete();
            DB::table('camp_project_links')->delete();
            DB::table('camp_company_links')->whereIn('company_id', $companyIds)->delete();

            foreach ($this->tenantTables as $table) {
                DB::table($table)->whereIn('company_id', $companyIds)->delete();
            }

            // Users outlive a Camp sync, so only clear them on a full reset.
            if ($this->option('all')) {
                DB::table('users')->whereIn('company_id', $companyIds)->delete();
            }

            DB::table('companies')->whereIn('id', $companyIds)->delete();
        });

        $this->info('Purge complete. Run timesheets:sync-camp to rebuild from Camp.');

        return self::SUCCESS;
    }
}
