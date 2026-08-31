<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class JourneyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $status = $this->status;
        $risk = $this->risk_level;

        return [
            'id' => $this->id,
            'code' => $this->code,
            'type' => $this->type,
            'origin' => $this->origin,
            'destination' => $this->destination,
            'vehicle_plate' => $this->vehicle_plate,
            'vehicle_model' => $this->vehicle_model,
            'hub' => $this->hub,
            'risk_level' => $risk?->value ?? $risk,
            'risk_label' => $risk?->label(),
            'risk_segments' => $risk?->segments() ?? 0,
            'distance_km' => $this->distance_km,
            'departure_at' => $this->departure_at?->toIso8601String(),
            'arrival_at' => $this->arrival_at?->toIso8601String(),
            'status' => $status?->value ?? $status,
            'status_label' => $status?->label(),
            'emergency_contact_name' => $this->emergency_contact_name,
            'emergency_contact_phone' => $this->emergency_contact_phone,
            'checkpoints' => $this->checkpoints ?? [],
            'worker' => $this->whenLoaded('worker', fn () => $this->worker ? [
                'id' => $this->worker->id,
                'name' => $this->worker->full_name,
            ] : null),
            'major_project' => $this->whenLoaded('majorProject', fn () => $this->majorProject ? [
                'id' => $this->majorProject->id,
                'name' => $this->majorProject->name,
            ] : null),
            'approver' => $this->whenLoaded('approver', fn () => $this->approver ? [
                'id' => $this->approver->id,
                'name' => $this->approver->name,
            ] : null),
        ];
    }
}
