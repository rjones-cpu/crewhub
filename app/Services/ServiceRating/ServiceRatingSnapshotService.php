<?php

namespace App\Services\ServiceRating;

use App\Enums\ServiceRating\CalculationStatus;
use App\Enums\ServiceRating\PublicationStatus;
use App\Models\CompanyProjectServiceRating;
use App\Models\ServiceRatingCriterionResult;
use App\Models\ServiceRatingPolicy;
use App\Models\ServiceRatingPolicyVersion;
use App\Models\ServiceRatingSnapshot;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ServiceRatingSnapshotService
{
    public function __construct(
        private readonly ServiceRatingCalculator $calculator,
        private readonly PolicyLoader $policies,
    ) {
    }

    /**
     * Calculate and publish an immutable snapshot for a company/project pair.
     * Identical evidence fingerprints are idempotent and reuse the current row.
     */
    public function recalculateAndPublish(int $companyId, int $majorProjectId): ServiceRatingSnapshot
    {
        $result = $this->calculator->calculate($companyId, $majorProjectId);

        return DB::transaction(function () use ($companyId, $majorProjectId, $result) {
            $rating = CompanyProjectServiceRating::query()->firstOrCreate(
                [
                    'company_id' => $companyId,
                    'major_project_id' => $majorProjectId,
                ],
                [
                    'status' => 'active',
                    'activated_at' => now(),
                ],
            );

            $policyVersion = $this->ensurePolicyVersion($companyId, $majorProjectId, $result);

            if (
                $rating->currentPublishedSnapshot
                && $rating->currentPublishedSnapshot->evidence_fingerprint === $result->evidenceFingerprint
                && $rating->currentPublishedSnapshot->publication_status === PublicationStatus::Published
            ) {
                return $rating->currentPublishedSnapshot;
            }

            $sequence = (int) ServiceRatingSnapshot::query()
                ->where('company_project_service_rating_id', $rating->id)
                ->max('sequence_no') + 1;

            $calculationKey = hash('sha256', implode('|', [
                $companyId,
                $majorProjectId,
                $result->evidenceFingerprint,
                $result->policyVersion,
                $sequence,
            ]));

            $priorId = $rating->current_published_snapshot_id;
            $window = $result->trace['evaluation_window'];

            $snapshot = new ServiceRatingSnapshot([
                'company_id' => $companyId,
                'company_project_service_rating_id' => $rating->id,
                'policy_version_id' => $policyVersion->id,
                'sequence_no' => $sequence,
                'evaluation_window_start' => $window['start'],
                'evaluation_window_end' => $window['end'],
                'evidence_cutoff_at' => $window['end'],
                'overall_grade' => $result->overallGrade,
                'calculation_status' => CalculationStatus::Calculated,
                'publication_status' => PublicationStatus::Published,
                'data_quality_status' => $result->dataQuality,
                'evidence_fingerprint' => $result->evidenceFingerprint,
                'calculation_key' => $calculationKey,
                'calculation_trace' => $result->trace,
                'prior_snapshot_id' => $priorId,
                'calculated_by_type' => 'system',
                'calculated_at' => now(),
                'published_at' => now(),
                'correlation_id' => $result->trace['correlation_id'] ?? (string) Str::uuid(),
                'created_at' => now(),
            ]);

            // Bypass the immutability guard on create.
            ServiceRatingSnapshot::withoutEvents(fn () => $snapshot->save());

            foreach ($result->criteria as $criterion) {
                ServiceRatingCriterionResult::query()->create([
                    'snapshot_id' => $snapshot->id,
                    'criterion_code' => $criterion->criterion,
                    'applicable' => $criterion->applicable,
                    'applicability_reason_code' => $criterion->applicabilityReasonCode,
                    'grade' => $criterion->grade,
                    'numerator' => $criterion->numerator,
                    'denominator' => $criterion->denominator,
                    'measured_value' => $criterion->measuredValue,
                    'measured_unit' => $criterion->measuredUnit,
                    'threshold_code' => $criterion->thresholdCode,
                    'driver_summary' => $criterion->driverSummary,
                    'result_trace' => $criterion->trace,
                    'data_quality_status' => $criterion->dataQuality,
                    'exception_count' => $criterion->exceptionCount,
                    'critical_override_applied' => $criterion->criticalOverrideApplied,
                    'created_at' => now(),
                ]);
            }

            if ($priorId) {
                ServiceRatingSnapshot::withoutEvents(function () use ($priorId, $snapshot) {
                    ServiceRatingSnapshot::query()->whereKey($priorId)->update([
                        'publication_status' => PublicationStatus::Superseded->value,
                        'superseded_by_snapshot_id' => $snapshot->id,
                    ]);
                });
            }

            $rating->forceFill(['current_published_snapshot_id' => $snapshot->id])->save();

            return $snapshot->load('criterionResults');
        });
    }

    private function ensurePolicyVersion(int $companyId, int $majorProjectId, RatingResult $result): ServiceRatingPolicyVersion
    {
        $loaded = $this->policies->activeFor($companyId, $majorProjectId);

        if ($loaded['version'] instanceof ServiceRatingPolicyVersion) {
            return $loaded['version'];
        }

        $policy = ServiceRatingPolicy::query()->firstOrCreate(
            [
                'company_id' => $companyId,
                'major_project_id' => null,
                'policy_code' => config('service_rating.default_policy_code'),
            ],
            [
                'name' => (string) data_get($result->policy, 'policy_name', 'CH-11 Working Default'),
                'status' => 'active',
                'created_by' => auth()->id(),
            ],
        );

        $version = ServiceRatingPolicyVersion::query()->firstOrCreate(
            [
                'service_rating_policy_id' => $policy->id,
                'version' => $result->policyVersion,
            ],
            [
                'status' => 'active',
                'effective_from' => now(),
                'time_zone' => (string) data_get($result->policy, 'time_zone', config('service_rating.default_time_zone')),
                'policy_json' => $result->policy,
                'policy_hash' => $this->policies->hash($result->policy),
                'created_by' => auth()->id(),
                'approved_at' => now(),
            ],
        );

        if ($policy->current_version_id !== $version->id) {
            $policy->forceFill([
                'current_version_id' => $version->id,
                'status' => 'active',
            ])->save();
        }

        return $version;
    }
}
