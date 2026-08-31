<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class JourneyRiskAssessmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $journey = $this->whenLoaded('journey');

        return [
            'id' => $this->id,
            'code' => $this->code,
            'score' => $this->score,
            'outcome' => $this->outcome?->value,
            'outcome_label' => $this->outcome?->label(),
            'factors' => $this->factors ?? [],
            'recommendations' => $this->recommendations ?? [],
            'weather' => $this->weather,
            'temperature_c' => $this->temperature_c,
            'road_conditions' => $this->road_conditions,
            'road_condition_quality' => $this->road_condition_quality,
            'engine_version' => $this->engine_version,
            'calculated_at' => $this->calculated_at?->toIso8601String(),
            'journey' => $this->whenLoaded('journey', fn () => [
                'id' => $journey->id,
                'code' => $journey->code,
                'origin' => $journey->origin,
                'destination' => $journey->destination,
                'hub' => $journey->hub,
                'departure_at' => $journey->departure_at?->toIso8601String(),
                'arrival_at' => $journey->arrival_at?->toIso8601String(),
                'status' => $journey->status?->value,
                'status_label' => $journey->status?->label(),
                'worker' => $journey->relationLoaded('worker') && $journey->worker
                    ? ['id' => $journey->worker->id, 'name' => $journey->worker->full_name]
                    : null,
                'vehicle' => $journey->relationLoaded('vehicle') && $journey->vehicle
                    ? [
                        'id' => $journey->vehicle->id,
                        'name' => $journey->vehicle->display_name,
                        'plate' => $journey->vehicle->license_plate,
                        'type_label' => $journey->vehicle->vehicle_type?->label(),
                    ]
                    : [
                        'name' => $journey->vehicle_model,
                        'plate' => $journey->vehicle_plate,
                        'type_label' => null,
                    ],
            ]),
        ];
    }
}
