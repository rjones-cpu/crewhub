<?php

namespace App\Services\ServiceRating\Evaluators;

use App\Enums\ServiceRating\CriterionCode;
use App\Enums\ServiceRating\Grade;
use App\Models\ServiceRatingCriticalOverride;
use App\Models\ServiceRatingException;
use App\Models\Worker;
use App\Services\ServiceRating\CriterionResult;
use App\Services\ServiceRating\RatingContext;
use App\Services\ServiceRating\Thresholds;
use Illuminate\Support\Collection;

class LmsCertificationEvaluator
{
    /**
     * @param  array<string, mixed>  $policy
     * @param  Collection<int, ServiceRatingException>  $exceptions
     * @param  Collection<int, ServiceRatingCriticalOverride>  $overrides
     */
    public function evaluate(
        RatingContext $context,
        array $policy,
        Collection $exceptions,
        Collection $overrides,
    ): CriterionResult {
        $criterion = CriterionCode::LmsCertification;

        if (! (bool) data_get($policy, 'criteria.lms_certification.enabled', true)) {
            return CriterionResult::notApplicable(
                $criterion,
                'policy_disabled',
                'LMS & Certification not required by policy',
            );
        }

        $workers = Worker::query()
            ->where('company_id', $context->companyId)
            ->where('primary_project_id', $context->majorProjectId)
            ->with(['certifications' => fn ($query) => $query->select('id', 'worker_id', 'status', 'expires_at')])
            ->get(['id']);

        if ($workers->isEmpty()) {
            return CriterionResult::notApplicable(
                $criterion,
                'no_applicable_workers',
                'No assigned workers with LMS scope',
            );
        }

        // Package: applicable population is workers with one or more project-required
        // certifications. If none are recorded yet, the criterion is N/A — not a D.
        $hasAnyRequirements = $workers->contains(fn (Worker $worker) => $worker->certifications->isNotEmpty());

        if (! $hasAnyRequirements) {
            return CriterionResult::notApplicable(
                $criterion,
                'no_applicable_requirements',
                'No LMS/certification requirements recorded',
            );
        }

        $cutoff = $context->evidenceCutoffAt->copy()->startOfDay();

        $affected = $workers->filter(function (Worker $worker) use ($cutoff) {
            $certs = $worker->certifications;

            if ($certs->isEmpty()) {
                return false;
            }

            return $certs->contains(function ($cert) use ($cutoff) {
                if (($cert->status ?? '') !== 'valid') {
                    return true;
                }

                return $cert->expires_at !== null && $cert->expires_at->lte($cutoff);
            });
        });

        $applicable = $workers->filter(fn (Worker $worker) => $worker->certifications->isNotEmpty())->count();
        $affectedCount = $affected->count();
        $gap = $applicable > 0 ? ($affectedCount / $applicable) * 100 : 0.0;
        $grade = Thresholds::percentBandGrade($gap, 0, 20, 40);

        $criticalMissing = $affected->contains(function (Worker $worker) {
            return $worker->certifications->contains(
                fn ($cert) => ($cert->status ?? '') === 'revoked'
            );
        });

        $finalGrade = $criticalMissing ? Grade::D : $grade;

        $result = new CriterionResult(
            criterion: $criterion,
            applicable: true,
            grade: $finalGrade,
            numerator: (float) $affectedCount,
            denominator: (float) $applicable,
            measuredValue: round($gap, 6),
            measuredUnit: 'compliance_gap_percent',
            driverSummary: sprintf(
                '%s%% gap · %s of %s workers',
                round($gap),
                $affectedCount,
                $applicable,
            ),
            trace: [
                'criterion' => $criterion->value,
                'applicable_workers' => $applicable,
                'affected_workers' => $affectedCount,
                'compliance_gap_rate' => $gap,
                'critical_certification_missing' => $criticalMissing,
                'grade' => $finalGrade->value,
            ],
            exceptionCount: $exceptions->where('criterion_code', $criterion)->count(),
            thresholdCode: 'compliance_gap_percent',
        );

        $critical = $overrides->first(
            fn (ServiceRatingCriticalOverride $override) => $override->criterion_code === $criterion
                && $override->isActiveAt($context->evidenceCutoffAt)
        );

        return $critical
            ? $result->withCriticalOverride($critical->critical_rule_code)
            : $result;
    }
}
