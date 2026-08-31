<?php

namespace App\Models;

use App\Enums\JourneyRisk;
use App\Enums\JourneyStatus;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Journey extends CompanyModel
{
    use SoftDeletes;

    protected function casts(): array
    {
        return [
            'departure_at' => 'datetime',
            'arrival_at' => 'datetime',
            'status' => JourneyStatus::class,
            'risk_level' => JourneyRisk::class,
            'distance_km' => 'float',
            'source_payload' => 'array',
            'risk_factors' => 'array',
            'insurance_verified' => 'boolean',
            'requires_approval' => 'boolean',
            'detected_at' => 'datetime',
            'escalated_at' => 'datetime',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'checkpoints' => 'array',
        ];
    }

    public function worker(): BelongsTo
    {
        return $this->belongsTo(Worker::class);
    }

    public function majorProject(): BelongsTo
    {
        return $this->belongsTo(MajorProject::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function journeyHub(): BelongsTo
    {
        return $this->belongsTo(JourneyHub::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function duplicateOf(): BelongsTo
    {
        return $this->belongsTo(self::class, 'duplicate_of_id');
    }

    public function participants(): HasMany
    {
        return $this->hasMany(JourneyParticipant::class);
    }

    public function answers(): HasMany
    {
        return $this->hasMany(JourneyAnswer::class);
    }

    public function riskAssessments(): HasMany
    {
        return $this->hasMany(JourneyRiskAssessment::class);
    }

    public function checkpointEvents(): HasMany
    {
        return $this->hasMany(JourneyCheckpoint::class)->orderBy('sequence');
    }
}
