<?php

namespace App\Models;

use App\Enums\InsuranceStatus;
use App\Enums\VehicleAvailability;
use App\Enums\VehicleType;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Vehicle extends CompanyModel
{
    use SoftDeletes;

    /**
     * Mirrors the column default so a freshly made vehicle reports its confirmation
     * state without needing a round trip to the database.
     */
    protected $attributes = [
        'insurance_status' => InsuranceStatus::Unverified->value,
    ];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'vehicle_type' => VehicleType::class,
            'availability' => VehicleAvailability::class,
            'insurance_status' => InsuranceStatus::class,
            'insurance_verified_at' => 'datetime',
            'has_attachments' => 'boolean',
            'is_active' => 'boolean',
            'coverage_amount' => 'decimal:2',
            'odometer_km' => 'integer',
            'equipment' => 'array',
            'policy_start_date' => 'date',
            'policy_end_date' => 'date',
            'last_service_at' => 'date',
            'next_service_due_at' => 'date',
        ];
    }

    public function assignedDriver(): BelongsTo
    {
        return $this->belongsTo(Worker::class, 'assigned_driver_id');
    }

    public function journeys(): HasMany
    {
        return $this->hasMany(Journey::class);
    }

    public function insuranceVerifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'insurance_verified_by');
    }

    protected function displayName(): Attribute
    {
        return Attribute::get(fn (): string => trim("{$this->make} {$this->model}"));
    }

    protected function insuranceValid(): Attribute
    {
        return Attribute::get(fn (): bool => $this->policy_end_date !== null
            && $this->policy_end_date->endOfDay()->isFuture());
    }

    /**
     * Insurance about to lapse still counts as valid, but the journey screens flag it.
     */
    protected function insuranceExpiringSoon(): Attribute
    {
        return Attribute::get(fn (): bool => $this->insurance_valid
            && $this->policy_end_date->lessThanOrEqualTo(now()->addDays(30)));
    }
}
