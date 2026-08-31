<?php

namespace App\Models;

use App\Enums\JourneyRisk;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JourneyRiskAssessment extends CompanyModel
{
    protected function casts(): array
    {
        return [
            'score' => 'integer',
            'outcome' => JourneyRisk::class,
            'factors' => 'array',
            'recommendations' => 'array',
            'temperature_c' => 'integer',
            'calculated_at' => 'datetime',
        ];
    }

    public function journey(): BelongsTo
    {
        return $this->belongsTo(Journey::class);
    }

    public function calculatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'calculated_by');
    }
}
