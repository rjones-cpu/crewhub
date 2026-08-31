<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Services\Timesheets\CampScheduleRepository;
use App\Services\Timesheets\CampTimesheetSyncService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SyncCampTimesheets extends Command
{
    protected $signature = 'timesheets:sync-camp
        {--week= : Any date in the payroll week (defaults to today)}
        {--camp-company= : Restrict to one Camp user_companies ID}
        {--crew-company= : Restrict to one existing Crew Hub company ID}
        {--camp= : Restrict to one Camp ID}
        {--no-create-companies : Do not create Crew Hub companies for unmapped Camp companies}
        {--dry-run : Read and count schedule rows without changing Crew Hub}';

    protected $description = 'Generate weekly draft timesheets from published Camp schedules';

    public function handle(
        CampTimesheetSyncService $sync,
        CampScheduleRepository $camp,
    ): int {
        $week = Carbon::parse($this->option('week') ?: now())->startOfWeek();
        $campCompanyId = $this->option('camp-company')
            ? (int) $this->option('camp-company')
            : null;
        $campId = $this->option('camp') ? (int) $this->option('camp') : null;
        $crewCompany = null;

        if ($this->option('crew-company')) {
            $crewCompany = Company::query()->find($this->option('crew-company'));

            if (! $crewCompany) {
                $this->error('Crew Hub company was not found.');

                return self::FAILURE;
            }
        }

        if ($this->option('dry-run')) {
            if (! $camp->isAvailable()) {
                $this->error('Camp schedule database is unavailable.');

                return self::FAILURE;
            }

            $rows = $camp->scheduledRows(
                $week,
                $week->copy()->endOfWeek(),
                $campCompanyId ? [$campCompanyId] : null,
                $campId,
            );

            $this->info(sprintf(
                'Dry run: %d schedule days, %d reservations, %d companies for %s – %s.',
                $rows->count(),
                $rows->pluck('camp_booking_id')->unique()->count(),
                $rows->pluck('camp_company_id')->unique()->count(),
                $week->toDateString(),
                $week->copy()->endOfWeek()->toDateString(),
            ));

            return self::SUCCESS;
        }

        $result = $sync->syncWeek(
            week: $week,
            targetCompany: $crewCompany,
            campCompanyId: $campCompanyId,
            createCompanies: ! $this->option('no-create-companies') && ! $crewCompany,
            campId: $campId,
        );

        $this->table(
            ['Metric', 'Count'],
            [
                ['Crew Hub company', $result['company'] ?? '—'],
                ['Schedule days', $result['schedule_days']],
                ['Camp companies synced', $result['companies_synced']],
                ['Camp companies skipped', $result['companies_skipped']],
                ['Workers created', $result['workers_created']],
                ['Workers matched', $result['workers_matched']],
                ['Ambiguous workers', $result['workers_ambiguous']],
                ['Timesheets created', $result['timesheets_created']],
                ['Drafts updated', $result['timesheets_updated']],
                ['Locked sheets preserved', $result['timesheets_locked']],
            ],
        );

        foreach (array_slice($result['warnings'], 0, 20) as $warning) {
            $this->warn($warning);
        }

        foreach ($result['errors'] as $error) {
            $this->error($error);
        }

        return $result['errors'] === [] ? self::SUCCESS : self::FAILURE;
    }
}
