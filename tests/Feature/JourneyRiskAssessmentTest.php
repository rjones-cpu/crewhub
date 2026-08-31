<?php

namespace Tests\Feature;

use App\Enums\JourneyRisk;
use App\Enums\JourneyStatus;
use App\Enums\Role;
use App\Enums\VehicleType;
use App\Models\Company;
use App\Models\Journey;
use App\Models\JourneyAnswer;
use App\Models\JourneyRiskAssessment;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\Worker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JourneyRiskAssessmentTest extends TestCase
{
    use RefreshDatabase;

    private function companyAdmin(?Company $company = null): User
    {
        $company ??= Company::factory()->create();

        return User::factory()->create([
            'company_id' => $company->id,
            'role' => Role::CompanyAdmin,
        ]);
    }

    private function journeyFor(User $user, array $overrides = []): Journey
    {
        $worker = Worker::factory()->create(['company_id' => $user->company_id]);

        return Journey::factory()->create(array_merge([
            'company_id' => $user->company_id,
            'worker_id' => $worker->id,
            'origin' => 'Rustenburg Mine',
            'destination' => 'Main Plant',
            'distance_km' => 60,
            'departure_at' => now()->addDay()->setTime(6, 0),
        ], $overrides));
    }

    public function test_calculate_risk_page_reports_the_level_breakdown(): void
    {
        $user = $this->companyAdmin();
        $journey = $this->journeyFor($user);

        foreach ([JourneyRisk::Low, JourneyRisk::High, JourneyRisk::High] as $index => $outcome) {
            JourneyRiskAssessment::query()->create([
                'company_id' => $user->company_id,
                'journey_id' => $journey->id,
                'code' => sprintf('RISK-2026-000%d', $index + 1),
                'score' => $outcome === JourneyRisk::High ? 80 : 20,
                'outcome' => $outcome,
                'factors' => [],
                'calculated_at' => now(),
            ]);
        }

        $this->actingAs($user)
            ->get(route('journeys.risk'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Journeys/Risk/Index')
                ->has('assessments.data', 3)
                ->where('stats.total', 3)
                ->where('stats.low', 1)
                ->where('stats.high', 2)
                ->where('canManage', true));
    }

    public function test_assessment_scores_the_journey_and_writes_back_to_it(): void
    {
        $user = $this->companyAdmin();
        $vehicle = Vehicle::factory()->create(['company_id' => $user->company_id]);
        $journey = $this->journeyFor($user, [
            'vehicle_id' => $vehicle->id,
            'distance_km' => 280,
            'departure_at' => now()->addDay()->setTime(22, 0),
        ]);

        $this->actingAs($user)
            ->post(route('journeys.risk.store'), [
                'journey_id' => $journey->id,
                'weather' => 'Heavy Rain',
                'temperature_c' => 12,
                'road_conditions' => 'Mud / Slippery',
            ])
            ->assertRedirect();

        $assessment = JourneyRiskAssessment::query()->firstOrFail();
        $journey->refresh();

        $this->assertSame('RISK-'.now()->year.'-0001', $assessment->code);
        $this->assertSame(JourneyRisk::High, $assessment->outcome);
        $this->assertSame('Heavy Rain', $assessment->weather);
        $this->assertCount(9, $assessment->factors);
        $this->assertNotEmpty($assessment->recommendations);

        $this->assertSame($assessment->score, $journey->risk_score);
        $this->assertSame(JourneyRisk::High, $journey->risk_level);
        $this->assertTrue($journey->requires_approval);
    }

    public function test_recalculation_carries_the_captured_conditions_forward(): void
    {
        $user = $this->companyAdmin();
        $journey = $this->journeyFor($user);

        $this->actingAs($user)->post(route('journeys.risk.store'), [
            'journey_id' => $journey->id,
            'weather' => 'Clear',
            'road_conditions' => 'Good',
        ]);

        $original = JourneyRiskAssessment::query()->firstOrFail();

        $this->actingAs($user)
            ->post(route('journeys.risk.recalculate', $original))
            ->assertRedirect();

        $latest = JourneyRiskAssessment::query()->latest('id')->firstOrFail();

        $this->assertNotSame($original->id, $latest->id);
        $this->assertSame('Clear', $latest->weather);
        $this->assertSame('Good', $latest->road_conditions);
        $this->assertSame($original->score, $latest->score);
    }

    /**
     * @param  array<string, string>  $answers
     */
    private function answer(Journey $journey, array $answers): void
    {
        foreach ($answers as $key => $value) {
            JourneyAnswer::query()->create([
                'journey_id' => $journey->id,
                'question_key' => $key,
                'question' => $key,
                'answer' => $value,
            ]);
        }
    }

    public function test_low_risk_assessment_auto_approves_a_pending_journey(): void
    {
        $user = $this->companyAdmin();
        $vehicle = Vehicle::factory()->create([
            'company_id' => $user->company_id,
            'vehicle_type' => VehicleType::Suv,
        ]);
        $journey = $this->journeyFor($user, [
            'vehicle_id' => $vehicle->id,
            'distance_km' => 30,
            'departure_at' => now()->addDay()->setTime(9, 0),
            'status' => JourneyStatus::Pending,
        ]);

        $this->answer($journey, [
            'weather_forecast' => 'Clear',
            'road_conditions' => 'Good',
            'solo_travel' => 'No',
            'satellite_comms' => 'Yes',
            'route_familiarity' => 'Yes',
            'vehicle_inspection' => 'Yes',
            'rest_hours' => '8',
        ]);

        $this->actingAs($user)
            ->post(route('journeys.risk.store'), ['journey_id' => $journey->id])
            ->assertRedirect();

        $journey->refresh();

        $this->assertSame(JourneyRisk::Low, $journey->risk_level);
        $this->assertFalse($journey->requires_approval);
        $this->assertSame(JourneyStatus::Approved, $journey->status);
        // Auto-approval comes from the engine, so no human is recorded as approver.
        $this->assertNull($journey->approved_by);
    }

    public function test_high_risk_assessment_leaves_the_journey_pending(): void
    {
        $user = $this->companyAdmin();
        $journey = $this->journeyFor($user, [
            'distance_km' => 280,
            'departure_at' => now()->addDay()->setTime(22, 0),
            'status' => JourneyStatus::Pending,
        ]);

        $this->answer($journey, [
            'weather_forecast' => 'Heavy Rain',
            'road_conditions' => 'Mud / Slippery',
            'solo_travel' => 'Yes',
            'satellite_comms' => 'No',
            'rest_hours' => '3',
        ]);

        $this->actingAs($user)
            ->post(route('journeys.risk.store'), ['journey_id' => $journey->id])
            ->assertRedirect();

        $journey->refresh();

        $this->assertSame(JourneyRisk::High, $journey->risk_level);
        $this->assertTrue($journey->requires_approval);
        $this->assertSame(JourneyStatus::Pending, $journey->status);
    }

    public function test_export_downloads_csv(): void
    {
        $user = $this->companyAdmin();
        $journey = $this->journeyFor($user);
        $this->actingAs($user)->post(route('journeys.risk.store'), ['journey_id' => $journey->id]);

        $response = $this->actingAs($user)->get(route('journeys.risk.export'));

        $response->assertOk();
        $this->assertStringContainsString('RISK-'.now()->year, $response->streamedContent());
    }

    public function test_assessments_are_scoped_to_the_owning_company(): void
    {
        $owner = $this->companyAdmin();
        $outsider = $this->companyAdmin(Company::factory()->create(['name' => 'Other Co']));
        $journey = $this->journeyFor($owner);
        $this->actingAs($owner)->post(route('journeys.risk.store'), ['journey_id' => $journey->id]);

        $this->actingAs($outsider)
            ->get(route('journeys.risk'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->has('assessments.data', 0));
    }
}
