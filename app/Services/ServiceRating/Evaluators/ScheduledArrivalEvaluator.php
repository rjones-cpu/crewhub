<?php

namespace App\Services\ServiceRating\Evaluators;

use App\Enums\ServiceRating\CriterionCode;
use App\Enums\ServiceRating\Grade;
use App\Models\Journey;
use App\Models\ServiceRatingException;
use App\Services\ServiceRating\CriterionResult;
use App\Services\ServiceRating\RatingContext;
use App\Services\ServiceRating\Thresholds;
use Illuminate\Support\Collection;

class ScheduledArrivalEvaluator
{
    /**
     * @param  array<string, mixed>  $policy
     * @param  Collection<int, ServiceRatingException>  $exceptions
     */
    public function evaluate(RatingContext $context, array $policy, Collection $exceptions): CriterionResult
    {
        $criterion = CriterionCode::ScheduledArrival;

        $journeys = Journey::query()
            ->where('company_id', $context->companyId)
            ->where('major_project_id', $context->majorProjectId)
            ->whereBetween('departure_at', [$context->windowStart, $context->windowEnd])
            ->whereNot('status', 'cancelled')
            ->get(['id', 'departure_at', 'arrival_at', 'status']);

        if ($journeys->isEmpty()) {
            return CriterionResult::notApplicable(
                $criterion,
                'no_arrival_events',
                'No scheduled arrivals in evaluation window',
            );
        }

        $eventGrades = [];
        $lateWorkers = 0;
        $maxLateDays = 0;

        foreach ($journeys as $journey) {
            $status = $journey->status->value ?? (string) $journey->status;
            $noShow = $status === 'pending'
                && $journey->departure_at->lt(now()->subDay())
                && $journey->arrival_at === null;

            if ($journey->arrival_at === null && ! $noShow) {
                continue;
            }

            $scheduledDay = $journey->departure_at->timezone($context->timeZone)->startOfDay();
            $daysLate = $noShow
                ? 99
                : max((int) $scheduledDay->diffInDays(
                    $journey->arrival_at->timezone($context->timeZone)->startOfDay(),
                    false,
                ), 0);

            $grade = Thresholds::arrivalLatenessGrade($daysLate, $noShow);
            $eventGrades[] = $grade;

            if ($grade !== Grade::A) {
                $lateWorkers++;
                $maxLateDays = max($maxLateDays, $daysLate === 99 ? 4 : $daysLate);
            }
        }

        if ($eventGrades === []) {
            return CriterionResult::notApplicable(
                $criterion,
                'no_completed_arrivals',
                'No completed arrivals to grade yet',
            );
        }

        $grade = Grade::worst($eventGrades) ?? Grade::A;

        return new CriterionResult(
            criterion: $criterion,
            applicable: true,
            grade: $grade,
            numerator: (float) $lateWorkers,
            denominator: (float) count($eventGrades),
            measuredValue: (float) $maxLateDays,
            measuredUnit: 'max_calendar_days_late',
            driverSummary: $lateWorkers > 0
                ? sprintf('%d workers arrived late (max %d days)', $lateWorkers, $maxLateDays)
                : '100% on time',
            trace: [
                'criterion' => $criterion->value,
                'events_graded' => count($eventGrades),
                'late_workers' => $lateWorkers,
                'max_calendar_days_late' => $maxLateDays,
                'aggregation' => 'worst_unexcepted_event',
                'grade' => $grade->value,
            ],
            exceptionCount: $exceptions->where('criterion_code', $criterion)->count(),
            thresholdCode: 'worst_unexcepted_event',
        );
    }
}
