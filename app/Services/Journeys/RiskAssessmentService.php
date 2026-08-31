<?php

namespace App\Services\Journeys;

use App\Enums\JourneyStatus;
use App\Models\Journey;
use App\Models\JourneyRiskAssessment;
use App\Models\User;

class RiskAssessmentService
{
    public function __construct(private RiskEngine $engine)
    {
    }

    /**
     * Score a journey and store the result as a new, immutable assessment record.
     *
     * @param  array<string, mixed>  $overrides  Conditions captured on the assessment form.
     */
    public function assess(Journey $journey, User $user, array $overrides = []): JourneyRiskAssessment
    {
        $journey->loadMissing(['vehicle', 'answers', 'participants']);
        $context = array_merge($this->contextFor($journey), array_filter(
            $overrides,
            fn ($value) => $value !== null && $value !== '',
        ));

        $result = $this->engine->assess($journey, $context);

        $assessment = JourneyRiskAssessment::query()->create([
            'company_id' => $journey->company_id,
            'journey_id' => $journey->id,
            'code' => $this->nextCode((int) $journey->company_id),
            'score' => $result['score'],
            'outcome' => $result['outcome'],
            'factors' => $result['factors'],
            'recommendations' => $result['recommendations'],
            'weather' => $context['weather'] ?? null,
            'temperature_c' => $context['temperature_c'] ?? null,
            'road_conditions' => $context['road_conditions'] ?? null,
            'road_condition_quality' => $context['road_condition_quality'] ?? null,
            'engine_version' => RiskEngine::VERSION,
            'calculated_by' => $user->id,
            'calculated_at' => now(),
        ]);

        $requiresApproval = $this->engine->requiresApproval($result['score']);

        $journey->forceFill([
            'risk_score' => $result['score'],
            'risk_level' => $result['outcome'],
            'risk_factors' => $result['factors'],
            'requires_approval' => $requiresApproval,
        ]);

        // A low score clears the journey to plan on its own. Anything else stays
        // pending for a manager, so approved_by is left empty on the engine's calls.
        if ($journey->status === JourneyStatus::Pending && ! $requiresApproval) {
            $journey->status = JourneyStatus::Approved;
        }

        $journey->save();

        return $assessment->load(['journey.worker', 'journey.vehicle']);
    }

    /**
     * Re-score the journey behind an existing assessment, carrying its captured
     * conditions forward so only the underlying journey data can move the number.
     */
    public function recalculate(JourneyRiskAssessment $assessment, User $user): JourneyRiskAssessment
    {
        return $this->assess($assessment->journey, $user, [
            'weather' => $assessment->weather,
            'temperature_c' => $assessment->temperature_c,
            'road_conditions' => $assessment->road_conditions,
            'road_condition_quality' => $assessment->road_condition_quality,
        ]);
    }

    /**
     * Translate the driver's stored answers into engine inputs.
     *
     * @return array<string, mixed>
     */
    private function contextFor(Journey $journey): array
    {
        $answers = $journey->answers->keyBy('question_key');
        $answer = fn (string $key) => $answers->get($key)?->answer;

        return array_filter([
            'weather' => $answer('weather_forecast'),
            'road_conditions' => $answer('road_conditions'),
            'solo_travel' => $this->truthy($answer('solo_travel'), $journey->participants->count() <= 1),
            'has_satellite' => $this->truthy($answer('satellite_comms'), false),
            'route_familiar' => $this->truthy($answer('route_familiarity'), false),
            'inspection_complete' => $this->truthy($answer('vehicle_inspection'), false),
            'rest_hours' => $answer('rest_hours'),
        ], fn ($value) => $value !== null);
    }

    private function truthy(?string $answer, bool $default): bool
    {
        if ($answer === null || $answer === '') {
            return $default;
        }

        return in_array(strtolower($answer), ['yes', 'true', '1'], true);
    }

    private function nextCode(int $companyId): string
    {
        $year = now()->year;
        $sequence = JourneyRiskAssessment::query()
            ->where('company_id', $companyId)
            ->whereYear('created_at', $year)
            ->count() + 1;

        return sprintf('RISK-%d-%04d', $year, $sequence);
    }
}
