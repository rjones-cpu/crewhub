<?php

namespace Tests\Feature\ServiceRating;

use App\Enums\ServiceRating\Grade;
use App\Models\Company;
use App\Models\MajorProject;
use App\Models\ScheduleForecast;
use App\Models\User;
use App\Models\Worker;
use App\Services\ServiceRating\ServiceRatingCalculator;
use App\Services\ServiceRating\ServiceRatingSnapshotService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceRatingCalculatorTest extends TestCase
{
    use RefreshDatabase;

    public function test_worst_applicable_criterion_wins_and_snapshot_is_immutable(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        $this->actingAs($user);

        $project = MajorProject::factory()->create([
            'company_id' => $company->id,
            'status' => 'active',
        ]);

        // Seed inside the live evaluation window (policy TZ may differ from app TZ).
        ScheduleForecast::factory()->create([
            'company_id' => $company->id,
            'major_project_id' => $project->id,
            'forecast_date' => now()->timezone(config('app.timezone'))->toDateString(),
            'required_workers' => 100,
            'scheduled_workers' => 88,
        ]);

        Worker::factory()->count(3)->create([
            'company_id' => $company->id,
            'primary_project_id' => $project->id,
        ]);

        $result = app(ServiceRatingCalculator::class)->calculate($company->id, $project->id);

        $this->assertSame('C', $result->overallGrade?->value);
        $this->assertSame(
            'C',
            $result->criterion(\App\Enums\ServiceRating\CriterionCode::WorkforceDelivery)?->grade?->value,
        );

        $snapshot = app(ServiceRatingSnapshotService::class)
            ->recalculateAndPublish($company->id, $project->id);

        $this->assertSame('C', $snapshot->overall_grade->value);
        $this->assertGreaterThan(0, $snapshot->criterionResults()->count());

        $this->expectException(\RuntimeException::class);
        $snapshot->update(['overall_grade' => 'A']);
    }

    public function test_dashboard_exposes_service_rating_props(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Dashboard/Index')
                ->has('scorecard')
                ->has('scorecardSummary')
                ->has('projectPerformance'));
    }
}
