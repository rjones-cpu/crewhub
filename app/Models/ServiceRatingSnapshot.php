<?php

namespace App\Models;

use App\Enums\ServiceRating\CalculationStatus;
use App\Enums\ServiceRating\DataQualityStatus;
use App\Enums\ServiceRating\Grade;
use App\Enums\ServiceRating\PublicationStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ServiceRatingSnapshot extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'calculation_trace' => 'array',
            'evaluation_window_start' => 'datetime',
            'evaluation_window_end' => 'datetime',
            'evidence_cutoff_at' => 'datetime',
            'calculated_at' => 'datetime',
            'published_at' => 'datetime',
            'created_at' => 'datetime',
            'overall_grade' => Grade::class,
            'calculation_status' => CalculationStatus::class,
            'publication_status' => PublicationStatus::class,
            'data_quality_status' => DataQualityStatus::class,
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function companyProjectRating(): BelongsTo
    {
        return $this->belongsTo(CompanyProjectServiceRating::class, 'company_project_service_rating_id');
    }

    public function policyVersion(): BelongsTo
    {
        return $this->belongsTo(ServiceRatingPolicyVersion::class, 'policy_version_id');
    }

    public function criterionResults(): HasMany
    {
        return $this->hasMany(ServiceRatingCriterionResult::class, 'snapshot_id');
    }

    public function priorSnapshot(): BelongsTo
    {
        return $this->belongsTo(self::class, 'prior_snapshot_id');
    }

    /**
     * Snapshots are append-only. Source corrections create a new row.
     */
    protected static function booted(): void
    {
        static::updating(function (): void {
            throw new \RuntimeException('Service rating snapshots are immutable.');
        });

        static::deleting(function (): void {
            throw new \RuntimeException('Service rating snapshots cannot be deleted.');
        });
    }
}
