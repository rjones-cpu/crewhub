<?php

namespace App\Models;

use App\Enums\ProjectStatus;
use App\Enums\Role;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class MajorProject extends CompanyModel
{
    use SoftDeletes;

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'status' => ProjectStatus::class,
            'client_approval_required' => 'boolean',
            'modules' => 'array',
            'latitude' => 'float',
            'longitude' => 'float',
        ];
    }

    /**
     * Replace the default tenant scope with active-membership visibility.
     * company_id alone is not enough — a Super Admin may have assigned the
     * project to a company that has not yet accepted its Owner invitation.
     */
    public static function bootBelongsToCompany(): void
    {
        static::creating(function ($model): void {
            if (! array_key_exists('company_id', $model->getAttributes()) && auth()->user()?->company_id) {
                $model->company_id = auth()->user()->company_id;
            }
        });
    }

    protected static function booted(): void
    {
        static::addGlobalScope('company_or_member', function (Builder $builder): void {
            $user = auth()->user();

            if (! $user || $user->role === Role::SuperAdmin) {
                return;
            }

            if (! $user->company_id) {
                $builder->whereRaw('0 = 1');

                return;
            }

            $companyId = $user->company_id;

            $builder->whereExists(function ($sub) use ($companyId, $builder): void {
                $sub->selectRaw('1')
                    ->from('company_project_memberships as cpm')
                    ->whereColumn('cpm.major_project_id', $builder->qualifyColumn('id'))
                    ->where('cpm.company_id', $companyId)
                    ->where('cpm.status', 'active');
            });
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function workers(): HasMany
    {
        return $this->hasMany(Worker::class, 'primary_project_id');
    }

    public function timesheets(): HasMany
    {
        return $this->hasMany(Timesheet::class);
    }

    public function scheduleDays(): HasMany
    {
        return $this->hasMany(WorkerScheduleDay::class);
    }

    public function campLink(): HasOne
    {
        return $this->hasOne(CampProjectLink::class);
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(ProjectInvitation::class);
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(CompanyProjectMembership::class);
    }

    public static function defaultModules(): array
    {
        return [
            'schedule' => true,
            'timesheets' => true,
            'lms' => true,
            'accommodations' => true,
            'journey_management' => true,
        ];
    }
}
