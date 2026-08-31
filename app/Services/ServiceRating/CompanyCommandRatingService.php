<?php

namespace App\Services\ServiceRating;

use App\Enums\ServiceRating\CriterionCode;
use App\Enums\ServiceRating\Grade;
use App\Enums\ServiceRating\PublicationStatus;
use App\Models\CompanyProjectServiceRating;
use App\Models\MajorProject;
use App\Models\ServiceRatingSnapshot;
use App\Models\Worker;
use Illuminate\Support\Collection;

/**
 * Builds the Crew Hub Company Command scorecard payload from CH-11 ratings.
 *
 * All Projects overall = lowest active project grade (never an average).
 */
class CompanyCommandRatingService
{
    public function __construct(
        private readonly ServiceRatingCalculator $calculator,
        private readonly ServiceRatingSnapshotService $snapshots,
    ) {
    }

    public function overview(?int $projectId = null): array
    {
        $projects = MajorProject::query()
            ->when($projectId, fn ($query) => $query->where('id', $projectId))
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name', 'company_id', 'status']);

        $workerCounts = Worker::query()
            ->whereNotNull('primary_project_id')
            ->selectRaw('primary_project_id, count(*) as aggregate')
            ->groupBy('primary_project_id')
            ->pluck('aggregate', 'primary_project_id');

        $rows = $projects->map(function (MajorProject $project) use ($workerCounts) {
            $snapshot = $this->publishedOrCalculate($project);

            $criteria = collect($snapshot?->criterionResults ?? [])
                ->keyBy(fn ($result) => $result->criterion_code instanceof CriterionCode
                    ? $result->criterion_code->dashboardKey()
                    : CriterionCode::from($result->criterion_code)->dashboardKey());

            $grade = $snapshot?->overall_grade;

            return [
                'id' => $project->id,
                'name' => $project->name,
                'workers' => (int) ($workerCounts[$project->id] ?? 0),
                'grade' => $grade?->value ?? 'A',
                'status' => $grade?->label() ?? Grade::A->label(),
                'criteria' => [
                    'workforce' => $this->criterionGrade($criteria, 'workforce'),
                    'arrival' => $this->criterionGrade($criteria, 'arrival'),
                    'journey' => $this->criterionGrade($criteria, 'journey'),
                    'lms' => $this->criterionGrade($criteria, 'lms'),
                ],
                'criteria_detail' => [
                    'workforce' => $this->criterionDetail($criteria, 'workforce'),
                    'arrival' => $this->criterionDetail($criteria, 'arrival'),
                    'journey' => $this->criterionDetail($criteria, 'journey'),
                    'lms' => $this->criterionDetail($criteria, 'lms'),
                ],
                'trend' => $this->trendFor($project, $grade),
                'updated_at' => optional($snapshot?->published_at ?? $snapshot?->calculated_at)?->format('M d, g:i A')
                    ?? now()->format('M d, g:i A'),
                'policy_version' => $snapshot?->policyVersion?->version,
            ];
        })->values();

        return [
            'company' => $this->companyScorecard($rows),
            'summary' => $this->gradeSummary($rows),
            'projects' => $rows->all(),
        ];
    }

    private function publishedOrCalculate(MajorProject $project): ?ServiceRatingSnapshot
    {
        $rating = CompanyProjectServiceRating::query()
            ->where('major_project_id', $project->id)
            ->with(['currentPublishedSnapshot.criterionResults', 'currentPublishedSnapshot.policyVersion'])
            ->first();

        if ($rating?->currentPublishedSnapshot) {
            return $rating->currentPublishedSnapshot;
        }

        if (! config('service_rating.auto_publish_live_calculations', true)) {
            $live = $this->calculator->calculate((int) $project->company_id, (int) $project->id);

            return $this->syntheticSnapshot($live);
        }

        return $this->snapshots
            ->recalculateAndPublish((int) $project->company_id, (int) $project->id)
            ->load(['criterionResults', 'policyVersion']);
    }

    private function syntheticSnapshot(RatingResult $result): ServiceRatingSnapshot
    {
        $snapshot = new ServiceRatingSnapshot([
            'overall_grade' => $result->overallGrade,
            'publication_status' => PublicationStatus::Draft,
            'calculated_at' => now(),
            'published_at' => now(),
            'calculation_trace' => $result->trace,
        ]);

        $snapshot->setRelation(
            'criterionResults',
            collect($result->criteria)->map(fn (CriterionResult $criterion) => (object) [
                'criterion_code' => $criterion->criterion,
                'applicable' => $criterion->applicable,
                'grade' => $criterion->grade,
                'driver_summary' => $criterion->driverSummary,
            ]),
        );

        return $snapshot;
    }

    private function companyScorecard(Collection $rows): array
    {
        $grades = $rows->pluck('grade')->map(fn ($grade) => Grade::tryFrom($grade))->filter();
        $overall = Grade::worst($grades) ?? Grade::A;

        $detailFor = function (string $key) use ($rows): array {
            $projectCriteria = $rows->map(fn ($row) => [
                'grade' => Grade::tryFrom($row['criteria'][$key] ?? null),
                'detail' => $row['criteria_detail'][$key] ?? null,
            ]);

            $worst = Grade::worst($projectCriteria->pluck('grade')->filter()) ?? Grade::A;
            $driver = $projectCriteria
                ->first(fn ($item) => $item['grade'] === $worst)['detail']
                ?? $worst->label();

            return [
                'name' => match ($key) {
                    'workforce' => CriterionCode::WorkforceDelivery->label(),
                    'arrival' => CriterionCode::ScheduledArrival->label(),
                    'journey' => CriterionCode::JourneyManagement->label(),
                    'lms' => CriterionCode::LmsCertification->label(),
                    default => $key,
                },
                'detail' => $driver,
                'grade' => $worst->value,
            ];
        };

        return [
            'grade' => $overall->value,
            'label' => $overall->shortLabel(),
            'status' => $overall->label(),
            'score' => null,
            'next_review' => now()->addDays(7)->format('M j, Y'),
            'criteria' => [
                $detailFor('workforce'),
                $detailFor('arrival'),
                $detailFor('journey'),
                $detailFor('lms'),
            ],
        ];
    }

    private function gradeSummary(Collection $rows): array
    {
        $total = $rows->count();

        $breakdown = collect([Grade::A, Grade::B, Grade::C, Grade::D])->map(function (Grade $grade) use ($rows, $total) {
            $count = $rows->where('grade', $grade->value)->count();

            return [
                'grade' => $grade->value,
                'status' => $grade->label(),
                'count' => $count,
                'percent' => $total > 0 ? (int) round(($count / $total) * 100) : 0,
            ];
        });

        return [
            'total' => $total,
            'breakdown' => $breakdown->all(),
        ];
    }

    private function criterionGrade(Collection $criteria, string $key): string
    {
        $result = $criteria->get($key);

        if (! $result || ! ($result->applicable ?? true) || $result->grade === null) {
            return '—';
        }

        $grade = $result->grade;

        return $grade instanceof Grade ? $grade->value : (string) $grade;
    }

    private function criterionDetail(Collection $criteria, string $key): string
    {
        $result = $criteria->get($key);

        if (! $result) {
            return 'N/A';
        }

        if (! ($result->applicable ?? true)) {
            return 'Not applicable';
        }

        return $result->driver_summary
            ?? ($result->grade instanceof Grade ? $result->grade->label() : '—');
    }

    private function trendFor(MajorProject $project, ?Grade $grade): string
    {
        if ($grade === Grade::D) {
            return 'critical';
        }

        $history = ServiceRatingSnapshot::query()
            ->whereHas('companyProjectRating', fn ($query) => $query->where('major_project_id', $project->id))
            ->where('publication_status', PublicationStatus::Published)
            ->orderByDesc('sequence_no')
            ->limit(2)
            ->get(['overall_grade']);

        if ($history->count() < 2) {
            return 'stable';
        }

        $current = $history[0]->overall_grade;
        $prior = $history[1]->overall_grade;

        if (! $current instanceof Grade || ! $prior instanceof Grade) {
            return 'stable';
        }

        return match (true) {
            $current->severity() > $prior->severity() => 'declining',
            $current->severity() < $prior->severity() => 'improving',
            default => 'stable',
        };
    }
}
