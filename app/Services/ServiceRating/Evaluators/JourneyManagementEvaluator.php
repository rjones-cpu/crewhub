<?php

namespace App\Services\ServiceRating\Evaluators;

use App\Enums\ServiceRating\CriterionCode;
use App\Models\Journey;
use App\Models\ServiceRatingCriticalOverride;
use App\Models\ServiceRatingException;
use App\Services\ServiceRating\CriterionResult;
use App\Services\ServiceRating\RatingContext;
use App\Services\ServiceRating\Thresholds;
use Illuminate\Support\Collection;

class JourneyManagementEvaluator
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
        $criterion = CriterionCode::JourneyManagement;

        if (! (bool) data_get($policy, 'criteria.journey_management.enabled', true)) {
            return CriterionResult::notApplicable(
                $criterion,
                'policy_disabled',
                'Journey Management not required by policy',
            );
        }

        $journeys = Journey::query()
            ->where('company_id', $context->companyId)
            ->where('major_project_id', $context->majorProjectId)
            ->whereBetween('departure_at', [$context->windowStart, $context->windowEnd])
            ->whereNot('status', 'cancelled')
            ->get(['id', 'status']);

        if ($journeys->isEmpty()) {
            return CriterionResult::notApplicable(
                $criterion,
                'no_required_journeys',
                'No required journeys in evaluation window',
            );
        }

        $required = $journeys->count();
        $noncompliant = $journeys->filter(function (Journey $journey) {
            $status = $journey->status->value ?? (string) $journey->status;

            return $status === 'pending';
        })->count();

        $rate = ($noncompliant / $required) * 100;
        $grade = Thresholds::percentBandGrade($rate, 0, 20, 40);

        $result = new CriterionResult(
            criterion: $criterion,
            applicable: true,
            grade: $grade,
            numerator: (float) $noncompliant,
            denominator: (float) $required,
            measuredValue: round($rate, 6),
            measuredUnit: 'noncompliance_percent',
            driverSummary: sprintf('%s%% noncompliant · %s of %s journeys', round($rate), $noncompliant, $required),
            trace: [
                'criterion' => $criterion->value,
                'required_journeys' => $required,
                'noncompliant_journeys' => $noncompliant,
                'noncompliance_rate' => $rate,
                'grade' => $grade->value,
            ],
            exceptionCount: $exceptions->where('criterion_code', $criterion)->count(),
            thresholdCode: 'noncompliance_percent',
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
