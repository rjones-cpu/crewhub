<?php

namespace App\Models;

use App\Enums\ServiceRating\CriterionCode;
use App\Enums\ServiceRating\DataQualityStatus;
use App\Enums\ServiceRating\Grade;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceRatingCriterionResult extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'applicable' => 'boolean',
            'threshold_json' => 'array',
            'result_trace' => 'array',
            'critical_override_applied' => 'boolean',
            'created_at' => 'datetime',
            'criterion_code' => CriterionCode::class,
            'grade' => Grade::class,
            'data_quality_status' => DataQualityStatus::class,
            'numerator' => 'float',
            'denominator' => 'float',
            'measured_value' => 'float',
        ];
    }

    public function snapshot(): BelongsTo
    {
        return $this->belongsTo(ServiceRatingSnapshot::class, 'snapshot_id');
    }
}
