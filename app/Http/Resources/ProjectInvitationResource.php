<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProjectInvitationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $project = $this->whenLoaded('majorProject') ? $this->majorProject : null;

        return [
            'id' => $this->id,
            'status' => $this->status,
            'role' => $this->role,
            'invited_at' => $this->invited_at?->toIso8601String(),
            'responded_at' => $this->responded_at?->toIso8601String(),
            'inviter' => $this->whenLoaded('inviter', fn () => $this->inviter ? [
                'id' => $this->inviter->id,
                'name' => $this->inviter->name,
                'email' => $this->inviter->email,
                'avatar' => $this->inviter->avatar,
            ] : null),
            'company' => $this->whenLoaded('company', fn () => [
                'id' => $this->company->id,
                'name' => $this->company->name,
                'code' => $this->company->code,
            ]),
            'project' => $project ? [
                'id' => $project->id,
                'name' => $project->name,
                'code' => $project->code,
                'icon' => $project->icon,
                'po_number' => $project->po_number,
                'project_number' => $project->project_number,
                'project_type' => $project->project_type,
                'address' => $project->address ?: $project->location,
                'comments' => $project->comments,
                'description' => $project->description,
                'start_date' => $project->start_date?->toDateString(),
                'end_date' => $project->end_date?->toDateString(),
                'modules' => $project->modules ?? [],
                'status' => $project->status,
                'company' => $project->relationLoaded('company') && $project->company ? [
                    'id' => $project->company->id,
                    'name' => $project->company->name,
                    'code' => $project->company->code,
                ] : null,
                'manager' => $project->relationLoaded('manager') && $project->manager ? [
                    'id' => $project->manager->id,
                    'name' => $project->manager->name,
                ] : null,
                'workers_count' => $project->workers_count ?? 0,
            ] : null,
        ];
    }
}
