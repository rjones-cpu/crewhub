<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MajorProjectResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'company_id' => $this->company_id,
            'name' => $this->name,
            'code' => $this->code,
            'po_number' => $this->po_number,
            'project_number' => $this->project_number,
            'description' => $this->description,
            'comments' => $this->comments,
            'location' => $this->location,
            'address' => $this->address,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'project_type' => $this->project_type,
            'start_date' => $this->start_date?->toDateString(),
            'end_date' => $this->end_date?->toDateString(),
            'status' => $this->status,
            'icon' => $this->icon,
            'modules' => $this->modules ?? [],
            'manager_id' => $this->manager_id,
            'workers_count' => $this->whenCounted('workers'),
            'company' => $this->whenLoaded('company', fn () => [
                'id' => $this->company->id,
                'name' => $this->company->name,
                'code' => $this->company->code,
            ]),
            'manager' => $this->whenLoaded('manager', fn () => $this->manager ? [
                'id' => $this->manager->id,
                'name' => $this->manager->name,
                'email' => $this->manager->email,
            ] : null),
            'membership_role' => $this->when(
                isset($this->membership_role),
                $this->membership_role,
            ),
        ];
    }
}
