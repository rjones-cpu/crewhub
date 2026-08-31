<?php

namespace App\Models;

use App\Enums\WorkerStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Worker extends CompanyModel
{
    use SoftDeletes;

    protected $appends = ['full_name'];

    protected function casts(): array
    {
        return [
            'status' => WorkerStatus::class,
            'date_of_birth' => 'date',
            'start_date' => 'date',
            'end_date' => 'date',
            'documents' => 'array',
            'on_site' => 'boolean',
            'module_access' => 'boolean',
            'schedule_access' => 'boolean',
            'timesheet_access' => 'boolean',
            'lms_access' => 'boolean',
            'journey_access' => 'boolean',
        ];
    }

    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    public function primaryProject(): BelongsTo
    {
        return $this->belongsTo(MajorProject::class, 'primary_project_id');
    }

    public function readiness(): HasOne
    {
        return $this->hasOne(WorkerReadiness::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(WorkerAssignment::class);
    }

    public function scheduleDays(): HasMany
    {
        return $this->hasMany(WorkerScheduleDay::class);
    }

    public function certifications(): HasMany
    {
        return $this->hasMany(Certification::class);
    }

    public function medicalRecords(): HasMany
    {
        return $this->hasMany(MedicalRecord::class);
    }

    public function trainingRecords(): HasMany
    {
        return $this->hasMany(TrainingRecord::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(WorkerActivity::class);
    }

    public function timesheets(): HasMany
    {
        return $this->hasMany(Timesheet::class);
    }

    public function campBookingLinks(): HasMany
    {
        return $this->hasMany(CampBookingWorkerLink::class);
    }

    public function accommodationAssignments(): HasMany
    {
        return $this->hasMany(AccommodationAssignment::class);
    }

    public function latestAccommodation(): HasOne
    {
        return $this->hasOne(AccommodationAssignment::class)->latestOfMany('check_in');
    }

    public function scopeFilter(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['search'] ?? null, fn (Builder $query, string $search) => $query->where(
                fn (Builder $query) => $query->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('employee_id', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
            ))
            ->when($filters['status'] ?? null, fn (Builder $query, string $status) => $query->where('status', $status))
            ->when($filters['position'] ?? null, fn (Builder $query, string $position) => $query->where('position', $position))
            ->when($filters['location'] ?? null, fn (Builder $query, string $location) => $query->where('location', $location))
            ->when($filters['project_id'] ?? null, fn (Builder $query, $projectId) => $query->where('primary_project_id', $projectId))
            ->when(isset($filters['on_site']) && $filters['on_site'] !== '', fn (Builder $query) => $query->where('on_site', filter_var($filters['on_site'], FILTER_VALIDATE_BOOL)));
    }
}
