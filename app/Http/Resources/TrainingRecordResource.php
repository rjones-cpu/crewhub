<?php

namespace App\Http\Resources;

use App\Services\Training\TrainingComplianceService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TrainingRecordResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'course_name' => $this->course_name,
            'provider' => $this->provider,
            'category' => $this->category,
            'is_required' => (bool) $this->is_required,
            'status' => $this->status,
            'completed_at' => $this->completed_at?->toDateString(),
            'expires_at' => $this->expires_at?->toDateString(),
            'score' => $this->score,
            'is_expired' => $this->isExpired(),
            'is_expiring_soon' => $this->expiresWithinDays(TrainingComplianceService::EXPIRING_SOON_DAYS),
            'certificate' => $this->whenLoaded(
                'certification',
                fn () => $this->certification ? new CertificationResource($this->certification) : null,
            ),
        ];
    }
}
