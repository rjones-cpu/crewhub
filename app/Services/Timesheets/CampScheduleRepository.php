<?php

namespace App\Services\Timesheets;

use Carbon\CarbonInterface;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CampScheduleRepository
{
    protected function connection(): ConnectionInterface
    {
        return DB::connection(config('timesheets.camp_sync.connection', 'camp'));
    }

    public function isAvailable(): bool
    {
        try {
            $this->connection()->select('SELECT 1');

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Fetch the company tree a Camp coordinator dashboard shows for one camp/project.
     *
     * Camp owns companies per creating user, so scoping by the owner's role reproduces
     * exactly the prime and contractor tables rendered on /scheduling/coordinator.
     *
     * @return Collection<int, object>
     */
    public function companyTree(int $campId, int $projectId, string $ownerRole): Collection
    {
        $ownerIds = $this->connection()
            ->table('users')
            ->join('model_has_roles as assigned_roles', function ($join) {
                $join->on('assigned_roles.model_id', '=', 'users.id')
                    ->where('assigned_roles.model_type', '=', 'App\Models\User');
            })
            ->join('roles', 'roles.id', '=', 'assigned_roles.role_id')
            ->where('users.camp_id', $campId)
            ->where('users.project_id', $projectId)
            ->where('roles.name', $ownerRole)
            ->pluck('users.id');

        if ($ownerIds->isEmpty()) {
            return collect();
        }

        return $this->connection()
            ->table('user_companies')
            ->whereIn('user_id', $ownerIds)
            ->where('camp_id', $campId)
            ->where('archive', 0)
            ->orderBy('name')
            ->get(['id', 'name', 'hierarchy', 'linked', 'camp_id', 'project_id', 'is_client'])
            ->map(function ($row) {
                $row->parent_camp_company_id = $this->parentCompanyId($row->linked);

                return $row;
            });
    }

    /**
     * Resolve every Camp company that belongs to a single Crew Hub tenant.
     *
     * Unlike companyTree(), this is NOT limited to the owner role or to non-archived
     * rows: Camp frequently keeps duplicate company records (an archived one plus a
     * live one) and orphans active bookings onto the archived duplicate. A tenant must
     * still collect those workers, so we match by explicit link id or company name and
     * only scope by camp to stay inside the deployment's single client dashboard.
     *
     * @param  list<int>  $campCompanyIds  Already-linked Camp company ids.
     * @param  list<string>  $normalizedNames  Lower-cased, trimmed tenant names.
     * @return Collection<int, object>
     */
    public function companiesFor(int $campId, array $campCompanyIds, array $normalizedNames): Collection
    {
        if ($campCompanyIds === [] && $normalizedNames === []) {
            return collect();
        }

        return $this->connection()
            ->table('user_companies')
            ->where('camp_id', $campId)
            ->where(function ($query) use ($campCompanyIds, $normalizedNames) {
                // Seed a false predicate so the following orWhere clauses stay grouped.
                $query->whereRaw('0 = 1');

                if ($campCompanyIds !== []) {
                    $query->orWhereIn('id', $campCompanyIds);
                }

                if ($normalizedNames !== []) {
                    $query->orWhereIn(DB::raw('LOWER(TRIM(name))'), $normalizedNames);
                }
            })
            ->orderBy('name')
            ->get(['id', 'name', 'hierarchy', 'linked', 'camp_id', 'project_id', 'is_client'])
            ->map(function ($row) {
                $row->parent_camp_company_id = $this->parentCompanyId($row->linked);

                return $row;
            });
    }

    /**
     * Camp stores a company's parent as a JSON blob, or the string "0" when unparented.
     */
    protected function parentCompanyId(?string $linked): ?int
    {
        $decoded = json_decode((string) $linked, true);

        if (! is_array($decoded)) {
            return null;
        }

        $parentId = (int) ($decoded['parent_prime_company_id'] ?? 0);

        return $parentId > 0 ? $parentId : null;
    }

    /**
     * Fetch every reservation that makes up a tenant's workforce, ignoring the payroll
     * week entirely.
     *
     * Schedule dates only exist while a company is actively rotating people through the
     * camp, so a week-scoped read returns nothing for a company whose rotation has
     * finished. The bookings themselves outlive the schedule and are what identifies a
     * worker, so the roster is built from them.
     *
     * @param  list<int>  $campCompanyIds
     * @return Collection<int, object>
     */
    public function rosterBookings(int $campId, array $campCompanyIds, ?CarbonInterface $since = null): Collection
    {
        if ($campCompanyIds === []) {
            return collect();
        }

        $eligibleStatuses = config('timesheets.camp_sync.eligible_reservation_statuses', []);

        return $this->connection()
            ->table('bookings')
            ->join('user_companies as companies', 'companies.id', '=', 'bookings.company_id')
            ->leftJoin('positions', 'positions.id', '=', 'bookings.position_id')
            ->whereNull('bookings.deleted_at')
            ->whereIn('bookings.company_id', $campCompanyIds)
            ->where('bookings.camp_id', $campId)
            ->when(
                $eligibleStatuses !== [],
                fn ($query) => $query->whereIn(DB::raw('LOWER(bookings.reservation_status)'), $eligibleStatuses)
            )
            ->when($since, fn ($query) => $query->where(function ($inner) use ($since) {
                $inner->whereNull('bookings.check_out')
                    ->orWhere('bookings.check_out', '>=', $since->toDateString());
            }))
            ->orderBy('bookings.id')
            ->get([
                'bookings.id as camp_booking_id',
                'bookings.booking_code',
                'bookings.first_name',
                'bookings.last_name',
                'bookings.email',
                'bookings.company_id as camp_company_id',
                'companies.name as camp_company_name',
                'bookings.camp_id',
                'bookings.reservation_status',
                'bookings.check_in',
                'bookings.check_out',
                'positions.name as position_name',
            ]);
    }

    /**
     * Fetch published schedule dates intersecting a payroll week.
     *
     * This repository deliberately exposes reads only. Crew Hub must never
     * mutate the Camp reservation database.
     */
    public function scheduledRows(
        CarbonInterface $periodStart,
        CarbonInterface $periodEnd,
        ?array $campCompanyIds = null,
        ?int $campId = null,
    ): Collection {
        $eligibleStatuses = config('timesheets.camp_sync.eligible_reservation_statuses', []);

        return $this->connection()
            ->table('site_schedule_dates as schedule_dates')
            ->join('site_schedules as schedules', 'schedules.id', '=', 'schedule_dates.site_schedule_id')
            ->join('bookings', 'bookings.id', '=', 'schedules.reservation_id')
            ->join('user_companies as companies', 'companies.id', '=', 'bookings.company_id')
            ->join('site_schedule_types as day_types', 'day_types.id', '=', 'schedule_dates.site_schedule_type_id')
            ->leftJoin('projects', function ($join) {
                $join->on('projects.id', '=', DB::raw(
                    'COALESCE(NULLIF(bookings.project_id, 0), NULLIF(bookings.projects_id, 0))'
                ));
            })
            ->leftJoin('positions', 'positions.id', '=', 'bookings.position_id')
            ->leftJoin('shifts', 'shifts.id', '=', 'bookings.shift')
            ->leftJoin('users as supervisors', 'supervisors.id', '=', 'bookings.supervisor_id')
            ->whereNull('bookings.deleted_at')
            ->where(function ($query) {
                $query->where('schedules.draft_status', 0)->orWhereNull('schedules.draft_status');
            })
            ->where(function ($query) {
                $query->where('schedules.archive_it', 0)->orWhereNull('schedules.archive_it');
            })
            ->where(function ($query) {
                $query->where('schedule_dates.archive_it', 0)->orWhereNull('schedule_dates.archive_it');
            })
            ->when(
                $eligibleStatuses !== [],
                fn ($query) => $query->whereIn(DB::raw('LOWER(bookings.reservation_status)'), $eligibleStatuses)
            )
            ->when(
                $campCompanyIds !== null,
                fn ($query) => $query->whereIn('bookings.company_id', $campCompanyIds)
            )
            ->when($campId, fn ($query) => $query->where('bookings.camp_id', $campId))
            ->whereBetween('schedule_dates.date', [
                $periodStart->toDateString(),
                $periodEnd->toDateString(),
            ])
            ->orderBy('companies.name')
            ->orderBy('bookings.id')
            ->orderBy('schedule_dates.date')
            ->get([
                'schedule_dates.id as schedule_date_id',
                'schedule_dates.date as work_date',
                'schedule_dates.needs_room',
                'schedule_dates.updated_at as schedule_date_updated_at',
                'day_types.name as day_type',
                'schedules.id as camp_schedule_id',
                'schedules.updated_at as schedule_updated_at',
                'bookings.id as camp_booking_id',
                'bookings.booking_code',
                'bookings.first_name',
                'bookings.last_name',
                'bookings.email',
                'bookings.company_id as camp_company_id',
                'companies.name as camp_company_name',
                'companies.hierarchy as camp_company_hierarchy',
                'companies.linked as camp_company_linked',
                'bookings.camp_id',
                'bookings.reservation_status',
                'bookings.check_in',
                'bookings.check_out',
                'bookings.updated_at as booking_updated_at',
                'projects.id as camp_project_id',
                'projects.name as camp_project_name',
                'projects.project_number as camp_project_number',
                'positions.name as position_name',
                'shifts.shift_name',
                DB::raw(
                    "TRIM(COALESCE(NULLIF(supervisors.name, ''), CONCAT_WS(' ', supervisors.first_name, supervisors.last_name))) as supervisor_name"
                ),
            ]);
    }
}
