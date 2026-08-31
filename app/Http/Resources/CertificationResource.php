<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class CertificationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'certificate_number' => $this->certificate_number,
            'issuer' => $this->issuer,
            'issued_at' => $this->issued_at?->toDateString(),
            'expires_at' => $this->expires_at?->toDateString(),
            'status' => $this->status,
            'file_name' => $this->file_name,
            'file_size' => $this->file_size,
            'file_url' => $this->file_path ? Storage::disk('public')->url($this->file_path) : null,
            'uploaded_at' => $this->uploaded_at?->toIso8601String(),
            'uploaded_by' => $this->whenLoaded('uploader', fn () => $this->uploader?->name),
        ];
    }
}
