<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReadinessOverviewResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'overall_status' => $this->overall_status,
            'medical_status' => $this->medical_status,
            'certification_status' => $this->certification_status,
            'training_status' => $this->training_status,
            'journey_status' => $this->journey_status,
            'accommodation_status' => $this->accommodation_status,
            'site_access_status' => $this->site_access_status,
            'last_checked_at' => $this->last_checked_at,
            'notes' => $this->notes,
        ];
    }
}
