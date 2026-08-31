<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CrewHubDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_view_dashboard(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Dashboard/Index')
                ->has('kpis')
                ->has('forecast')
                ->has('priorityActions')
                ->has('summaries')
                ->has('scorecard.criteria', 4)
                ->has('scorecardSummary.breakdown', 4)
                ->has('projectPerformance')
                ->has('mobilizations')
                ->where('kpis.company_grade', fn ($grade) => in_array($grade, ['A', 'B', 'C', 'D'], true)));
    }

    public function test_authenticated_user_can_view_workers_index(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('workers.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Workers/Index')
                ->has('workers')
                ->has('stats', fn ($stats) => $stats
                    ->has('total')
                    ->has('active')
                    ->has('inactive')
                    ->has('on_leave')
                    ->etc())
                ->has('filters')
                ->has('projects')
                ->has('company')
                ->has('featureSummary.schedule')
                ->has('featureSummary.timesheet')
                ->has('featureSummary.lms')
                ->has('featureSummary.journey'));
    }

    public function test_authenticated_user_can_view_readiness_index(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('readiness.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Readiness/Index')
                ->has('stats')
                ->has('overview')
                ->has('categories')
                ->has('attention.data')
                ->has('attention.links')
                ->has('attention.meta')
                ->has('criticalConcerns')
                ->has('upcomingExpiries')
                ->has('recentActivity')
                ->where('meta.period_label', fn ($value) => is_string($value) && $value !== '')
                ->where('meta.generated_at', fn ($value) => is_string($value) && $value !== ''));
    }
}
