<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VehicleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'make' => $this->make,
            'model' => $this->model,
            'display_name' => $this->display_name,
            'year' => $this->year,
            'vehicle_type' => $this->vehicle_type?->value,
            'vehicle_type_label' => $this->vehicle_type?->label(),
            'vin' => $this->vin,
            'license_plate' => $this->license_plate,
            'assigned_driver' => $this->whenLoaded('assignedDriver', fn () => $this->assignedDriver ? [
                'id' => $this->assignedDriver->id,
                'name' => $this->assignedDriver->full_name,
            ] : null),

            'insurance_provider' => $this->insurance_provider,
            'policy_number' => $this->policy_number,
            'coverage_type' => $this->coverage_type,
            'coverage_amount' => $this->coverage_amount,
            'policy_start_date' => $this->policy_start_date?->toDateString(),
            'policy_end_date' => $this->policy_end_date?->toDateString(),
            'insurance_valid' => $this->insurance_valid,
            'insurance_expiring_soon' => $this->insurance_expiring_soon,
            'insurance_status' => $this->insurance_status?->value,
            'insurance_status_label' => $this->insurance_status?->label(),
            'insurance_verified_at' => $this->insurance_verified_at?->toIso8601String(),
            'insurance_verification_notes' => $this->insurance_verification_notes,
            'insurance_verifier' => $this->whenLoaded('insuranceVerifier', fn () => $this->insuranceVerifier
                ? ['id' => $this->insuranceVerifier->id, 'name' => $this->insuranceVerifier->name]
                : null),
            'insurance_document_url' => $this->insurance_document_path
                ? asset('storage/'.$this->insurance_document_path)
                : null,
            'has_attachments' => $this->has_attachments,

            'base_location' => $this->base_location,
            'purpose' => $this->purpose,
            'additional_notes' => $this->additional_notes,
            'additional_details' => $this->additional_details,

            'availability' => $this->availability?->value,
            'availability_label' => $this->availability?->label(),
            'transmission' => $this->transmission,
            'odometer_km' => $this->odometer_km,
            'known_issues' => $this->known_issues,

            'equipment' => $this->equipment ?? [],
            'maintenance_notes' => $this->maintenance_notes,
            'last_service_at' => $this->last_service_at?->toDateString(),
            'next_service_due_at' => $this->next_service_due_at?->toDateString(),
            'is_active' => $this->is_active,
        ];
    }
}
