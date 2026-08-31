<?php

namespace Tests\Unit;

use App\Enums\JourneyRisk;
use App\Models\Journey;
use App\Services\Journeys\RiskEngine;
use Tests\TestCase;

class RiskEngineTest extends TestCase
{
    private function journey(float $distanceKm, string $departure): Journey
    {
        $journey = new Journey([
            'distance_km' => $distanceKm,
            'departure_at' => $departure,
        ]);

        // Keeps the engine off the database; the no-vehicle branch is exercised instead.
        $journey->setRelation('vehicle', null);

        return $journey;
    }

    public function test_ideal_conditions_score_low(): void
    {
        $result = (new RiskEngine)->assess($this->journey(30, '2026-05-16 12:00:00'), [
            'weather' => 'Clear',
            'road_conditions' => 'Good',
            'solo_travel' => false,
            'has_satellite' => true,
            'route_familiar' => true,
            'inspection_complete' => true,
            'rest_hours' => 8,
        ]);

        $this->assertSame(19, $result['score']);
        $this->assertSame(JourneyRisk::Low, $result['outcome']);
    }

    public function test_severe_conditions_score_high_and_recommend_mitigations(): void
    {
        $result = (new RiskEngine)->assess($this->journey(300, '2026-05-16 22:00:00'), [
            'weather' => 'Heavy Rain',
            'road_conditions' => 'Mud / Slippery',
            'solo_travel' => true,
            'has_satellite' => false,
            'route_familiar' => false,
            'inspection_complete' => false,
            'rest_hours' => 3,
        ]);

        $this->assertGreaterThanOrEqual(RiskEngine::HIGH_THRESHOLD, $result['score']);
        $this->assertSame(JourneyRisk::High, $result['outcome']);
        $this->assertContains('Delay departure until road conditions improve', $result['recommendations']);
        $this->assertContains('Ensure satellite communication device is active', $result['recommendations']);
        $this->assertContains('Use designated checkpoint call-ins', $result['recommendations']);
    }

    public function test_every_weighted_factor_is_reported(): void
    {
        $result = (new RiskEngine)->assess($this->journey(50, '2026-05-16 09:00:00'));

        $this->assertCount(9, $result['factors']);
        $this->assertEqualsWithDelta(
            1.0,
            array_sum(array_column($result['factors'], 'weight')),
            0.0001,
        );
    }

    public function test_medium_scores_require_approval(): void
    {
        $engine = new RiskEngine;

        $this->assertFalse($engine->requiresApproval(RiskEngine::MEDIUM_THRESHOLD - 1));
        $this->assertTrue($engine->requiresApproval(RiskEngine::MEDIUM_THRESHOLD));
        $this->assertSame(JourneyRisk::Medium, $engine->levelFor(55));
    }
}
