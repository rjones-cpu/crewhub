<?php

namespace App\Services\ServiceRating\Evaluators;

use App\Enums\ServiceRating\CriterionCode;
use App\Enums\ServiceRating\DataQualityStatus;
use App\Enums\ServiceRating\Grade;
use App\Models\ScheduleForecast;
use App\Models\ServiceRatingException;
use App\Services\ServiceRating\CriterionResult;
use App\Services\ServiceRating\RatingContext;
use App\Services\ServiceRating\Thresholds;
use Illuminate\Support\Collection;

class WorkforceDeliveryEvaluator
{
    /**
     * @param  array<string, mixed>  $policy
     * @param  Collection<int, ServiceRatingException>  $exceptions
     */
    public function evaluate(RatingContext $context, array $policy, Collection $exceptions): CriterionResult
    {
        $criterion = CriterionCode::WorkforceDelivery;
        $thresholds = data_get($policy, 'criteria.workforce_delivery.thresholds', []);

        $rows = ScheduleForecast::query()
            ->where('company_id', $context->companyId)
            ->where('major_project_id', $context->majorProjectId)
            ->whereBetween('forecast_date', [
                $context->windowStart->toDateString(),
                $context->windowEnd->toDateString(),
            ])
            ->get(['required_workers', 'scheduled_workers', 'forecast_date']);

        if ($rows->isEmpty() || (int) $rows->sum('required_workers') <= 0) {
            return new CriterionResult(
                criterion: $criterion,
                applicable: true,
                grade: Grade::A,
                numerator: 0,
                denominator: 0,
                measuredValue: 0.0,
                measuredUnit: 'absolute_variance_percent',
                driverSummary: 'No demand units in evaluation window',
                trace: [
                    'criterion' => $criterion->value,
                    'required_units' => 0,
                    'valid_provided_units' => 0,
                    'absolute_variance_rate' => 0,
                    'note' => 'zero_denominator_treated_as_a',
                ],
                dataQuality: DataQualityStatus::Sufficient,
                thresholdCode: 'A.max_absolute_variance_percent',
            );
        }

        $required = (int) $rows->sum('required_workers');
        $provided = (int) $rows->sum('scheduled_workers');
        $shortfall = max($required - $provided, 0);
        $variance = abs($provided - $required) / $required * 100;
        $grade = Thresholds::workforceVarianceGrade($variance, $thresholds);

        return new CriterionResult(
            criterion: $criterion,
            applicable: true,
            grade: $grade,
            numerator: (float) $provided,
            denominator: (float) $required,
            measuredValue: round($variance, 6),
            measuredUnit: 'absolute_variance_percent',
            driverSummary: sprintf('%s%% variance · %s of %s scheduled', round($variance), $provided, $required),
            trace: [
                'criterion' => $criterion->value,
                'required_units' => $required,
                'valid_provided_units' => $provided,
                'shortfall_units' => $shortfall,
                'absolute_variance_rate' => $variance,
                'exceptions_applied' => $exceptions->where('criterion_code', $criterion)->count(),
                'grade' => $grade->value,
            ],
            exceptionCount: $exceptions->where('criterion_code', $criterion)->count(),
            thresholdCode: 'absolute_variance_percent',
        );
    }
}
