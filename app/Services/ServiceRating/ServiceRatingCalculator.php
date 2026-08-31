<?php

namespace App\Services\ServiceRating;

use App\Enums\ServiceRating\DataQualityStatus;
use App\Enums\ServiceRating\Grade;
use App\Models\ServiceRatingCriticalOverride;
use App\Models\ServiceRatingException;
use App\Services\ServiceRating\Evaluators\JourneyManagementEvaluator;
use App\Services\ServiceRating\Evaluators\LmsCertificationEvaluator;
use App\Services\ServiceRating\Evaluators\ScheduledArrivalEvaluator;
use App\Services\ServiceRating\Evaluators\WorkforceDeliveryEvaluator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Deterministic CH-11 calculator. AI must never call this to invent a grade —
 * it only explains published results.
 */
class ServiceRatingCalculator
{
    public function __construct(
        private readonly PolicyLoader $policies,
        private readonly WorkforceDeliveryEvaluator $workforce,
        private readonly ScheduledArrivalEvaluator $arrival,
        private readonly JourneyManagementEvaluator $journey,
        private readonly LmsCertificationEvaluator $lms,
    ) {
    }

    public function calculate(int $companyId, int $majorProjectId, ?Carbon $asOf = null): RatingResult
    {
        $asOf ??= now();
        $loaded = $this->policies->activeFor($companyId, $majorProjectId);
        $policy = $loaded['policy'];
        $timeZone = (string) (
            config('service_rating.default_time_zone')
            ?: data_get($policy, 'time_zone')
            ?: config('app.timezone')
        );
        $days = (int) data_get(
            $policy,
            'evaluation_window.days',
            config('service_rating.evaluation_window_days', 30),
        );

        $windowEnd = $asOf->copy()->timezone($timeZone);
        $windowStart = $windowEnd->copy()->subDays($days - 1)->startOfDay();

        $context = new RatingContext(
            companyId: $companyId,
            majorProjectId: $majorProjectId,
            windowStart: $windowStart,
            windowEnd: $windowEnd,
            evidenceCutoffAt: $windowEnd->copy(),
            correlationId: (string) Str::uuid(),
            timeZone: $timeZone,
        );

        $exceptions = ServiceRatingException::query()
            ->where('company_id', $companyId)
            ->where('major_project_id', $majorProjectId)
            ->where('status', 'approved')
            ->get()
            ->filter(fn (ServiceRatingException $exception) => $exception->isActiveAt($context->evidenceCutoffAt))
            ->values();

        $overrides = ServiceRatingCriticalOverride::query()
            ->where('company_id', $companyId)
            ->where('major_project_id', $majorProjectId)
            ->whereIn('status', ['approved', 'active'])
            ->get()
            ->filter(fn (ServiceRatingCriticalOverride $override) => $override->isActiveAt($context->evidenceCutoffAt))
            ->values();

        $criteria = [
            $this->workforce->evaluate($context, $policy, $exceptions),
            $this->arrival->evaluate($context, $policy, $exceptions),
            $this->journey->evaluate($context, $policy, $exceptions, $overrides),
            $this->lms->evaluate($context, $policy, $exceptions, $overrides),
        ];

        $applicable = array_values(array_filter(
            $criteria,
            fn (CriterionResult $result) => $result->isApplicable(),
        ));

        $activeOverrides = $overrides->map(fn (ServiceRatingCriticalOverride $override) => [
            'criterion' => $override->criterion_code->value,
            'rule' => $override->critical_rule_code,
        ])->all();

        $overall = $activeOverrides !== []
            ? Grade::D
            : Grade::worst(array_map(fn (CriterionResult $result) => $result->grade, $applicable));

        // Package rule: A requires every applicable criterion to be A.
        if (
            $overall === Grade::A
            && collect($applicable)->contains(fn (CriterionResult $result) => $result->grade !== Grade::A)
        ) {
            $overall = Grade::worst(array_map(fn (CriterionResult $result) => $result->grade, $applicable));
        }

        $fingerprint = hash('sha256', json_encode([
            'company_id' => $companyId,
            'major_project_id' => $majorProjectId,
            'window' => [$windowStart->toIso8601String(), $windowEnd->toIso8601String()],
            'criteria' => array_map(fn (CriterionResult $result) => $result->trace, $criteria),
        ], JSON_THROW_ON_ERROR));

        return new RatingResult(
            overallGrade: $overall,
            criteria: $criteria,
            policy: $policy,
            policyVersion: $loaded['version_label'],
            evidenceFingerprint: $fingerprint,
            trace: [
                'company_id' => $companyId,
                'major_project_id' => $majorProjectId,
                'policy_version' => $loaded['version_label'],
                'evaluation_window' => [
                    'start' => $windowStart->toIso8601String(),
                    'end' => $windowEnd->toIso8601String(),
                ],
                'overall_rule' => 'worst_applicable_criterion',
                'overall_grade' => $overall?->value,
                'critical_overrides' => $activeOverrides,
                'correlation_id' => $context->correlationId,
            ],
            dataQuality: DataQualityStatus::Sufficient,
            criticalOverrides: $activeOverrides,
        );
    }
}
