<?php

namespace App\Support;

use App\Enums\JourneyQuestionType;

/**
 * Ready-made journey assessment questions a company can adopt instead of authoring
 * their own. Each entry declares the risk factor its answer feeds, which is how the
 * risk engine turns free-form answers into a score.
 */
class JourneyQuestionLibrary
{
    /**
     * @return list<array<string, mixed>>
     */
    public static function templates(): array
    {
        return [
            [
                'key' => 'route_familiarity',
                'type' => JourneyQuestionType::TrueFalse->value,
                'question' => 'Are you familiar with the route?',
                'description' => 'Drivers new to a route are more exposed on unmarked sections.',
                'risk_key' => 'driver_familiarity',
                'risk_weight' => 12,
                'options' => [],
                'is_required' => true,
            ],
            [
                'key' => 'satellite_comms',
                'type' => JourneyQuestionType::YesNo->value,
                'question' => 'Do you have satellite communication?',
                'description' => 'Confirms the journey can be reached outside cell coverage.',
                'risk_key' => 'communication_coverage',
                'risk_weight' => 12,
                'options' => [],
                'is_required' => true,
            ],
            [
                'key' => 'road_conditions',
                'type' => JourneyQuestionType::Dropdown->value,
                'question' => 'What type of road conditions will you encounter?',
                'description' => 'Surface quality across the worst section of the route.',
                'risk_key' => 'road_conditions',
                'risk_weight' => 15,
                'options' => ['Good', 'Gravel', 'Poor', 'Mud / Slippery'],
                'is_required' => true,
            ],
            [
                'key' => 'weather_forecast',
                'type' => JourneyQuestionType::Dropdown->value,
                'question' => 'What is the weather forecast for your route?',
                'description' => 'Expected conditions at the time of departure.',
                'risk_key' => 'weather',
                'risk_weight' => 15,
                'options' => ['Clear', 'Overcast', 'Fog', 'Light Rain', 'Heavy Rain'],
                'is_required' => true,
            ],
            [
                'key' => 'road_closures',
                'type' => JourneyQuestionType::YesNo->value,
                'question' => 'Have you checked for road closures or restrictions?',
                'description' => 'Confirms the driver reviewed current route restrictions.',
                'risk_key' => 'route_restrictions',
                'risk_weight' => 8,
                'options' => [],
                'is_required' => true,
            ],
            [
                'key' => 'solo_travel',
                'type' => JourneyQuestionType::TrueFalse->value,
                'question' => 'Will you be travelling alone?',
                'description' => 'Solo journeys carry a higher response time if something goes wrong.',
                'risk_key' => 'solo_travel',
                'risk_weight' => 12,
                'options' => [],
                'is_required' => true,
            ],
            [
                'key' => 'vehicle_inspection',
                'type' => JourneyQuestionType::YesNo->value,
                'question' => 'Is the vehicle pre-trip inspection complete?',
                'description' => 'Tyres, fluids, lights, and recovery equipment checked.',
                'risk_key' => 'vehicle_readiness',
                'risk_weight' => 10,
                'options' => [],
                'is_required' => true,
            ],
            [
                'key' => 'rest_hours',
                'type' => JourneyQuestionType::Number->value,
                'question' => 'How many hours have you slept in the last 24 hours?',
                'description' => 'Used to check the driver meets rest requirements.',
                'risk_key' => 'fatigue',
                'risk_weight' => 12,
                'options' => [],
                'is_required' => true,
            ],
            [
                'key' => 'eta',
                'type' => JourneyQuestionType::Time->value,
                'question' => 'Estimated time of arrival at destination?',
                'description' => 'Drives the overdue check-in alert.',
                'risk_key' => null,
                'risk_weight' => 0,
                'options' => [],
                'is_required' => true,
            ],
            [
                'key' => 'additional_notes',
                'type' => JourneyQuestionType::TextArea->value,
                'question' => 'Any additional notes or comments?',
                'description' => 'Optional context for the approving manager.',
                'risk_key' => null,
                'risk_weight' => 0,
                'options' => [],
                'is_required' => false,
            ],
        ];
    }
}
