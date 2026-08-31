<?php

namespace App\Services\Timesheets;

use App\Enums\TimesheetStatus;
use App\Enums\WorkerStatus;
use App\Models\CampBookingWorkerLink;
use App\Models\CampCompanyLink;
use App\Models\CampTimesheetSource;
use App\Models\Company;
use App\Models\Timesheet;
use App\Models\User;
use App\Models\Worker;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CampTimesheetSyncService
{
    public function __construct(
        protected CampScheduleRepository $camp,
        protected TimesheetWorkflowService $workflow,
    ) {}

    /**
     * Sync the current payroll week for the authenticated user.
     *
     * Company admins (and other tenant users) only pull Camp companies that match
     * their Crew Hub company. Super admins still import the full coordinator tree.
     *
     * @return array<string, mixed>
     */
    public function syncForUser(User $user, CarbonInterface|string|null $week = null): array
    {
        if ($user->isSuperAdmin()) {
            return $this->syncWeek(
                week: $week ?? now(),
                createCompanies: true,
            );
        }

        if (! $user->company) {
            $periodStart = Carbon::parse($week ?? now())->startOfWeek();

            return [
                ...$this->emptyResult($periodStart, $periodStart->copy()->endOfWeek()),
                'errors' => ['User is not assigned to a Crew Hub company.'],
            ];
        }

        return $this->syncWeek(
            week: $week ?? now(),
            targetCompany: $user->company,
            createCompanies: false,
        );
    }

    /**
     * Generate or refresh weekly draft timesheets from published Camp schedules.
     *
     * @return array<string, mixed>
     */
    public function syncWeek(
        CarbonInterface|string $week,
        ?Company $targetCompany = null,
        ?int $campCompanyId = null,
        bool $createCompanies = false,
        ?int $campId = null,
    ): array {
        $periodStart = Carbon::parse($week)->startOfWeek();
        $periodEnd = $periodStart->copy()->endOfWeek();
        $result = $this->emptyResult($periodStart, $periodEnd);

        if (! $this->camp->isAvailable()) {
            $result['errors'][] = 'Camp schedule database is unavailable.';

            return $result;
        }

        $campId ??= (int) config('timesheets.camp_sync.camp_id');
        $projectId = (int) config('timesheets.camp_sync.project_id');
        $ownerRole = (string) config('timesheets.camp_sync.owner_role');

        $rootHierarchies = config('timesheets.camp_sync.root_hierarchies', []);

        if ($targetCompany) {
            $company = $targetCompany;
            $treeToProcess = $this->campCompaniesForTenant($company, $campId);

            if ($campCompanyId) {
                $treeToProcess = $treeToProcess
                    ->filter(fn ($campCompany) => (int) $campCompany->id === $campCompanyId)
                    ->values();
            }

            if ($treeToProcess->isEmpty()) {
                $result['errors'][] = "No Camp company in reservations_staging matches Crew Hub company {$company->name}.";

                return $result;
            }

            // Parent fallback stays inside this tenant's Camp companies.
            $root = $treeToProcess->first(
                fn ($campCompany) => $campCompany->parent_camp_company_id === null
            ) ?? $treeToProcess->first();
        } else {
            $tree = $this->camp->companyTree($campId, $projectId, $ownerRole);

            if ($tree->isEmpty()) {
                $result['errors'][] = "No Camp companies are visible for camp {$campId} / project {$projectId} under the {$ownerRole} role.";

                return $result;
            }

            // The unparented client/prime owns the whole coordinator dashboard; it becomes
            // the single Crew Hub tenant every project and worker hangs off.
            $root = $tree->first(fn ($c) => in_array($c->hierarchy, $rootHierarchies, true)
                    && $c->parent_camp_company_id === null)
                ?? $tree->first(fn ($c) => in_array($c->hierarchy, $rootHierarchies, true))
                ?? $tree->first();

            $company = $this->resolveTenantCompany($root, $campId, $createCompanies);
            $treeToProcess = $tree;
        }

        if (! $company) {
            $result['errors'][] = "No Crew Hub company matches Camp client {$root->name}. Re-run without --no-create-companies to create it.";

            return $result;
        }

        $result['company'] = $company->name;

        // Camp supplies workforce data only. Workers belong to the Crew Hub company,
        // never to an auto-generated major project, so the sync just links companies.
        $companyLinks = [];

        foreach ($treeToProcess as $campCompany) {
            $companyLinks[(int) $campCompany->id] = $this->linkCampCompany($company, $campCompany, $campId);
        }

        $inScopeIds = $campCompanyId
            ? [$campCompanyId]
            : $treeToProcess->pluck('id')->map(fn ($id) => (int) $id)->all();

        // The roster is imported first and independently of the week so a company whose
        // Camp rotation has already ended still gets its people on the first sign-in.
        $this->importRoster($companyLinks, $inScopeIds, $campId, $result);

        $rows = $this->camp->scheduledRows($periodStart, $periodEnd, $inScopeIds, $campId);
        $result['schedule_days'] = $rows->count();

        foreach ($rows->groupBy('camp_company_id') as $externalCompanyId => $companyRows) {
            $externalId = (int) $externalCompanyId;
            $companyLink = $companyLinks[$externalId] ?? null;

            if (! $companyLink) {
                $result['companies_skipped']++;
                $result['warnings'][] = "Skipped out-of-scope Camp company {$companyRows->first()->camp_company_name} ({$externalId}).";
                continue;
            }

            $result['companies_synced']++;

            foreach ($companyRows->groupBy('camp_booking_id') as $bookingRows) {
                $this->syncBookingWeek(
                    $bookingRows,
                    $companyLink,
                    $periodStart,
                    $periodEnd,
                    $result,
                );
            }

            $companyLink->update(['last_synced_at' => now()]);
        }

        return $result;
    }

    /**
     * Create or match a Crew Hub worker for every eligible Camp reservation the tenant
     * owns. Timesheets stay week-scoped; only the workforce is imported in full.
     *
     * @param  array<int, CampCompanyLink>  $companyLinks  Keyed by Camp company id.
     * @param  list<int>  $inScopeIds
     */
    protected function importRoster(array $companyLinks, array $inScopeIds, int $campId, array &$result): void
    {
        if ($inScopeIds === []) {
            return;
        }

        $lookbackDays = (int) config('timesheets.camp_sync.roster_lookback_days', 365);
        $since = $lookbackDays > 0 ? now()->subDays($lookbackDays) : null;

        foreach ($this->camp->rosterBookings($campId, $inScopeIds, $since) as $booking) {
            $companyLink = $companyLinks[(int) $booking->camp_company_id] ?? null;

            if (! $companyLink) {
                continue;
            }

            $this->resolveWorker($booking, $companyLink, $result);
        }
    }

    /**
     * Camp companies that belong to a Crew Hub tenant: existing links plus every
     * reservations_staging company sharing the tenant name, including archived
     * duplicates that Camp may have orphaned live bookings onto.
     *
     * @return Collection<int, object>
     */
    protected function campCompaniesForTenant(Company $company, int $campId): Collection
    {
        $linkedIds = CampCompanyLink::query()
            ->where('company_id', $company->id)
            ->pluck('camp_company_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        return $this->camp->companiesFor($campId, $linkedIds, [$this->normalize($company->name)]);
    }

    /**
     * Find or create the single Crew Hub company representing the Camp client.
     */
    protected function resolveTenantCompany(object $root, int $campId, bool $createCompanies): ?Company
    {
        $link = CampCompanyLink::query()->where('camp_company_id', (int) $root->id)->first();

        if ($link && $link->company) {
            return $link->company;
        }

        $company = Company::query()
            ->whereRaw('LOWER(TRIM(name)) = ?', [$this->normalize($root->name)])
            ->first();

        if (! $company && $createCompanies) {
            $company = Company::query()->create([
                'name' => $root->name,
                'code' => $this->uniqueCompanyCode((int) $root->id),
                'industry' => 'Camp reservation client',
                'status' => 'active',
            ]);
        }

        return $company;
    }

    /**
     * Every in-scope Camp company points at the same Crew Hub tenant; the link table
     * keeps the Camp side addressable for later runs.
     */
    protected function linkCampCompany(Company $company, object $campCompany, int $campId): CampCompanyLink
    {
        return CampCompanyLink::query()->updateOrCreate(
            ['camp_company_id' => (int) $campCompany->id],
            [
                'company_id' => $company->id,
                'camp_id' => $campCompany->camp_id ?: $campId,
                'camp_company_name' => $campCompany->name,
                'last_synced_at' => now(),
            ],
        );
    }

    protected function syncBookingWeek(
        Collection $rows,
        CampCompanyLink $companyLink,
        Carbon $periodStart,
        Carbon $periodEnd,
        array &$result,
    ): void {
        $source = $rows->first();
        $workerLink = $this->resolveWorker($source, $companyLink, $result);

        if (! $workerLink) {
            return;
        }

        $worker = $workerLink->worker;
        $entries = $this->buildDayEntries($rows, $periodStart);
        $fingerprint = $this->fingerprint($rows);
        $totals = $this->workflow->calculateTotals($entries, []);

        DB::transaction(function () use (
            $rows,
            $source,
            $companyLink,
            $workerLink,
            $worker,
            $periodStart,
            $periodEnd,
            $entries,
            $fingerprint,
            $totals,
            &$result,
        ) {
            $timesheet = Timesheet::query()
                ->where('worker_id', $worker->id)
                ->whereDate('period_start', $periodStart)
                ->whereDate('period_end', $periodEnd)
                ->first();

            if ($timesheet && $timesheet->status !== TimesheetStatus::Draft) {
                $result['timesheets_locked']++;

                return;
            }

            // major_project_id is left alone: Camp knows nothing about major projects,
            // so any assignment made inside Crew Hub must survive a re-sync.
            $attributes = [
                'company_id' => $companyLink->company_id,
                'period_start' => $periodStart->toDateString(),
                'period_end' => $periodEnd->toDateString(),
                'due_date' => $periodEnd->copy()->addDays(2)->toDateString(),
                'day_entries' => $entries,
                'supervisor_name' => $source->supervisor_name ?: null,
                'status' => TimesheetStatus::Draft,
                ...$totals,
            ];

            if (! $timesheet) {
                $timesheet = Timesheet::query()->create([
                    'worker_id' => $worker->id,
                    'equipment_entries' => [],
                    'compliance' => $this->defaultCompliance(),
                    'status_history' => [[
                        'status' => TimesheetStatus::Draft->value,
                        'label' => TimesheetStatus::Draft->label(),
                        'at' => now()->toIso8601String(),
                        'by' => 'Camp schedule sync',
                        'note' => 'Draft generated from published Camp schedule',
                        'current' => true,
                    ]],
                    ...$attributes,
                ]);
                $result['timesheets_created']++;
            } else {
                $timesheet->update($attributes);
                $result['timesheets_updated']++;
            }

            foreach ($rows->pluck('camp_schedule_id')->unique() as $campScheduleId) {
                CampTimesheetSource::query()->updateOrCreate(
                    [
                        'camp_booking_worker_link_id' => $workerLink->id,
                        'camp_schedule_id' => $campScheduleId,
                        'period_start' => $periodStart->toDateString(),
                    ],
                    [
                        'timesheet_id' => $timesheet->id,
                        'period_end' => $periodEnd->toDateString(),
                        'schedule_fingerprint' => $fingerprint,
                        'last_synced_at' => now(),
                    ],
                );
            }
        });
    }

    protected function resolveWorker(
        object $source,
        CampCompanyLink $companyLink,
        array &$result,
    ): ?CampBookingWorkerLink {
        $link = CampBookingWorkerLink::query()
            ->where('camp_booking_id', $source->camp_booking_id)
            ->first();

        if ($link) {
            $link->update([
                'booking_code' => $source->booking_code,
                'source_email' => $source->email,
                'last_synced_at' => now(),
            ]);
            $link->worker->update([
                'position' => $source->position_name ?: $link->worker->position,
                'employer_name' => $source->camp_company_name,
                'on_site' => in_array($source->reservation_status, ['check_in', 'in_house'], true),
            ]);

            return $link;
        }

        $workers = Worker::query()->where('company_id', $companyLink->company_id);
        $worker = null;

        if ($source->email) {
            $emailMatches = (clone $workers)
                ->whereRaw('LOWER(email) = ?', [Str::lower(trim($source->email))])
                ->get();

            if ($emailMatches->count() > 1) {
                $result['workers_ambiguous']++;
                $result['warnings'][] = "Ambiguous email match for Camp booking {$source->camp_booking_id}.";

                return null;
            }

            $worker = $emailMatches->first();
        }

        if (! $worker && $source->booking_code) {
            $worker = (clone $workers)->where('employee_id', $source->booking_code)->first();
        }

        if (! $worker) {
            $nameMatches = (clone $workers)
                ->whereRaw('LOWER(TRIM(first_name)) = ?', [$this->normalize($source->first_name)])
                ->whereRaw('LOWER(TRIM(last_name)) = ?', [$this->normalize($source->last_name)])
                ->get();

            if ($nameMatches->count() === 1) {
                $candidate = $nameMatches->first();
                $emailsConflict = $source->email
                    && $candidate->email
                    && Str::lower(trim($source->email)) !== Str::lower(trim($candidate->email));

                if (! $emailsConflict) {
                    $worker = $candidate;
                }
            } elseif ($nameMatches->count() > 1) {
                $result['workers_ambiguous']++;
                $result['warnings'][] = "Ambiguous name match for Camp booking {$source->camp_booking_id}.";

                return null;
            }
        }

        if (! $worker) {
            $worker = Worker::query()->create([
                'company_id' => $companyLink->company_id,
                'employee_id' => $source->booking_code ?: "CAMP-{$source->camp_booking_id}",
                'first_name' => $source->first_name ?: 'Camp',
                'last_name' => $source->last_name ?: "Guest {$source->camp_booking_id}",
                'email' => $source->email ?: null,
                'position' => $source->position_name ?: null,
                'employer_name' => $source->camp_company_name,
                'location' => $source->camp_id ? "Camp {$source->camp_id}" : null,
                'status' => WorkerStatus::Active,
                'on_site' => in_array($source->reservation_status, ['check_in', 'in_house'], true),
                'module_access' => true,
                'schedule_access' => true,
                'timesheet_access' => true,
                'lms_access' => true,
                'journey_access' => true,
            ]);
            $result['workers_created']++;
        } else {
            $result['workers_matched']++;
        }

        return CampBookingWorkerLink::query()->create([
            'worker_id' => $worker->id,
            'camp_company_link_id' => $companyLink->id,
            'camp_booking_id' => $source->camp_booking_id,
            'booking_code' => $source->booking_code,
            'source_email' => $source->email,
            'last_synced_at' => now(),
        ]);
    }

    protected function buildDayEntries(Collection $rows, Carbon $periodStart): array
    {
        $latestByDate = $rows
            ->sortByDesc(fn ($row) => $row->schedule_date_updated_at ?? $row->schedule_date_id)
            ->unique(fn ($row) => Carbon::parse($row->work_date)->toDateString())
            ->keyBy(fn ($row) => Carbon::parse($row->work_date)->toDateString());

        $entries = [];

        for ($offset = 0; $offset < 7; $offset++) {
            $date = $periodStart->copy()->addDays($offset);
            $source = $latestByDate->get($date->toDateString());
            $entries[] = $this->dayEntry($date, $source);
        }

        return $entries;
    }

    protected function dayEntry(Carbon $date, ?object $source): array
    {
        $type = Str::lower(trim($source?->day_type ?? 'off'));
        $isWork = in_array($type, config('timesheets.camp_sync.work_day_types', []), true);
        $isTravel = in_array($type, config('timesheets.camp_sync.travel_day_types', []), true);
        $isStandby = in_array($type, config('timesheets.camp_sync.standby_day_types', []), true);
        $regular = $isWork ? (float) config('timesheets.camp_sync.regular_hours_per_day', 8) : 0;
        $travel = $isTravel ? (float) config('timesheets.camp_sync.travel_hours_per_day', 1) : 0;
        $standby = $isStandby ? (float) config('timesheets.camp_sync.standby_hours_per_day', 8) : 0;
        $break = $isWork ? (float) config('timesheets.camp_sync.break_hours_per_work_day', 0.5) : 0;

        return [
            'date' => $date->toDateString(),
            'day_label' => $date->format('D'),
            'shift' => $source?->shift_name ?: ($isTravel ? 'Travel' : ($isWork ? 'Day' : Str::title($type))),
            'start_time' => $isWork ? '07:00' : null,
            'end_time' => $isWork ? '15:30' : null,
            'break_hours' => $break,
            'regular_hours' => $regular,
            'overtime_hours' => 0,
            'double_time_hours' => 0,
            'travel_hours' => $travel,
            'standby_hours' => $standby,
            'total_hours' => $regular + $travel + $standby,
            'work_location' => $source?->camp_id ? "Camp {$source->camp_id}" : null,
            'task' => $source?->day_type ?: 'Off',
            'notes' => $source ? 'Imported from published Camp schedule' : '',
            'camp_schedule_date_id' => $source?->schedule_date_id,
            'needs_room' => (bool) ($source?->needs_room ?? false),
        ];
    }

    protected function fingerprint(Collection $rows): string
    {
        return hash('sha256', $rows
            ->sortBy('schedule_date_id')
            ->map(fn ($row) => [
                $row->schedule_date_id,
                $row->work_date,
                $row->day_type,
                $row->needs_room,
                $row->schedule_date_updated_at,
            ])
            ->values()
            ->toJson());
    }

    protected function defaultCompliance(): array
    {
        return [
            'safety_meeting' => false,
            'toolbox_talk' => false,
            'incident_report' => false,
            'attachments' => false,
            'signature' => false,
            'worker_declaration' => false,
        ];
    }

    protected function normalize(?string $value): string
    {
        return Str::lower(trim((string) $value));
    }

    protected function uniqueCompanyCode(int $campCompanyId): string
    {
        $base = "CAMP-{$campCompanyId}";
        $code = $base;
        $suffix = 1;

        while (Company::withTrashed()->where('code', $code)->exists()) {
            $code = "{$base}-{$suffix}";
            $suffix++;
        }

        return $code;
    }

    protected function emptyResult(Carbon $periodStart, Carbon $periodEnd): array
    {
        return [
            'period_start' => $periodStart->toDateString(),
            'period_end' => $periodEnd->toDateString(),
            'company' => null,
            'schedule_days' => 0,
            'companies_synced' => 0,
            'companies_skipped' => 0,
            'workers_created' => 0,
            'workers_matched' => 0,
            'workers_ambiguous' => 0,
            'timesheets_created' => 0,
            'timesheets_updated' => 0,
            'timesheets_locked' => 0,
            'warnings' => [],
            'errors' => [],
        ];
    }
}
