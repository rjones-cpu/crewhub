<?php

namespace App\Services\Journeys;

use App\Enums\JourneyRisk;
use App\Models\Journey;

/**
 * Turns journey conditions into a 0-100 risk score using fixed, auditable rules.
 *
 * Every factor is scored 0-100 on its own scale and then combined with the weights
 * in FACTORS. Keeping the maths deterministic means an approval decision can always
 * be replayed and explained, which an opaque model could not guarantee.
 */
class RiskEngine
{
    public const VERSION = 'rules-v1';

    /** Score at or above which a journey is treated as high risk. */
    public const HIGH_THRESHOLD = 70;

    /** Score at or above which a journey needs manager approval. */
    public const MEDIUM_THRESHOLD = 40;

    /**
     * Factor key => [display label, weight]. Weights total 1.0.
     */
    private const FACTORS = [
        'weather' => ['Weather', 0.15],
        'road_conditions' => ['Road Conditions', 0.15],
        'distance' => ['Distance', 0.12],
        'time_of_day' => ['Time of Day', 0.10],
        'solo_travel' => ['Solo Travel', 0.12],
        'communication_coverage' => ['Communication Coverage', 0.10],
        'driver_familiarity' => ['Driver Familiarity', 0.09],
        'vehicle_readiness' => ['Vehicle Readiness', 0.09],
        'fatigue' => ['Fatigue', 0.08],
    ];

    private const WEATHER_SCORES = [
        'clear' => 10,
        'overcast' => 30,
        'windy' => 40,
        'light rain' => 55,
        'fog' => 75,
        'dusty' => 60,
        'heavy rain' => 85,
        'snow' => 90,
    ];

    private const ROAD_SCORES = [
        'good' => 15,
        'dry' => 20,
        'gravel' => 45,
        'wet' => 60,
        'fair' => 50,
        'poor' => 75,
        'mud / slippery' => 90,
        'slippery' => 90,
    ];

    /**
     * @param  array<string, mixed>  $context  Answers and conditions gathered for the journey.
     * @return array{score: int, outcome: JourneyRisk, factors: list<array<string, mixed>>, recommendations: list<string>}
     */
    public function assess(Journey $journey, array $context = []): array
    {
        $scores = [
            'weather' => $this->lookup(self::WEATHER_SCORES, $context['weather'] ?? null, 30),
            'road_conditions' => $this->lookup(self::ROAD_SCORES, $context['road_conditions'] ?? null, 40),
            'distance' => $this->distanceScore((float) ($journey->distance_km ?? 0)),
            'time_of_day' => $this->timeOfDayScore($journey),
            'solo_travel' => $this->boolScore($context['solo_travel'] ?? true, 80, 25),
            'communication_coverage' => $this->boolScore($context['has_satellite'] ?? false, 20, 75),
            'driver_familiarity' => $this->boolScore($context['route_familiar'] ?? false, 25, 70),
            'vehicle_readiness' => $this->vehicleReadinessScore($journey, $context),
            'fatigue' => $this->fatigueScore($context['rest_hours'] ?? null),
        ];

        $total = 0.0;
        $factors = [];

        foreach (self::FACTORS as $key => [$label, $weight]) {
            $score = $scores[$key];
            $total += $score * $weight;

            $factors[] = [
                'key' => $key,
                'label' => $label,
                'score' => $score,
                'weight' => $weight,
                'level' => $this->levelFor($score)->value,
            ];
        }

        $score = (int) round($total);
        $outcome = $this->levelFor($score);

        return [
            'score' => $score,
            'outcome' => $outcome,
            'factors' => $factors,
            'recommendations' => $this->recommendations($factors, $outcome),
        ];
    }

    /**
     * A journey may only depart unattended when the engine rates it low risk.
     */
    public function requiresApproval(int $score): bool
    {
        return $score >= self::MEDIUM_THRESHOLD;
    }

    public function levelFor(int $score): JourneyRisk
    {
        return match (true) {
            $score >= self::HIGH_THRESHOLD => JourneyRisk::High,
            $score >= self::MEDIUM_THRESHOLD => JourneyRisk::Medium,
            default => JourneyRisk::Low,
        };
    }

    /**
     * @param  array<string, int>  $table
     */
    private function lookup(array $table, ?string $value, int $default): int
    {
        return $table[strtolower(trim((string) $value))] ?? $default;
    }

    /**
     * Exposure grows with distance and plateaus once a trip is long enough that
     * every additional kilometre no longer changes the response plan.
     */
    private function distanceScore(float $km): int
    {
        return (int) min(100, round($km / 3));
    }

    private function timeOfDayScore(Journey $journey): int
    {
        $hour = (int) ($journey->departure_at?->hour ?? 12);

        return match (true) {
            $hour >= 21 || $hour < 4 => 85,
            $hour >= 18 || $hour < 6 => 60,
            $hour >= 16 || $hour < 8 => 40,
            default => 20,
        };
    }

    private function boolScore(mixed $value, int $whenTrue, int $whenFalse): int
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN) ? $whenTrue : $whenFalse;
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function vehicleReadinessScore(Journey $journey, array $context): int
    {
        $score = filter_var($context['inspection_complete'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 20 : 55;
        $vehicle = $journey->relationLoaded('vehicle') ? $journey->vehicle : $journey->vehicle()->first();

        if (! $vehicle) {
            return min(100, $score + 15);
        }

        $score += (int) round($vehicle->vehicle_type?->riskPoints() / 2);

        if (! $vehicle->insurance_valid) {
            $score += 25;
        }

        if (! $vehicle->availability?->isRoadworthy()) {
            $score += 20;
        }

        return (int) min(100, $score);
    }

    private function fatigueScore(mixed $restHours): int
    {
        if ($restHours === null || $restHours === '') {
            return 50;
        }

        $hours = (float) $restHours;

        return match (true) {
            $hours >= 8 => 20,
            $hours >= 6 => 45,
            $hours >= 4 => 70,
            default => 90,
        };
    }

    /**
     * @param  list<array<string, mixed>>  $factors
     * @return list<string>
     */
    private function recommendations(array $factors, JourneyRisk $outcome): array
    {
        $byKey = array_column($factors, 'score', 'key');
        $recommendations = [];

        if ($byKey['road_conditions'] >= 60 || $byKey['weather'] >= 60) {
            $recommendations[] = 'Delay departure until road conditions improve';
        }

        if ($byKey['communication_coverage'] >= 60) {
            $recommendations[] = 'Ensure satellite communication device is active';
        }

        if ($byKey['fatigue'] >= 45) {
            $recommendations[] = 'Confirm driver rest requirements are met';
        }

        if ($byKey['solo_travel'] >= 60) {
            $recommendations[] = 'Assign a second occupant or a convoy partner';
        }

        if ($byKey['driver_familiarity'] >= 60) {
            $recommendations[] = 'Brief the driver on the route before departure';
        }

        if ($byKey['vehicle_readiness'] >= 55) {
            $recommendations[] = 'Complete vehicle pre-trip inspection and verify insurance';
        }

        if ($byKey['time_of_day'] >= 60) {
            $recommendations[] = 'Reschedule departure to daylight hours';
        }

        if ($outcome !== JourneyRisk::Low) {
            $recommendations[] = 'Use designated checkpoint call-ins';
        }

        return array_values(array_unique($recommendations));
    }
}
