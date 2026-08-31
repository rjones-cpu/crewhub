<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PriorityActionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id, 'title' => $this->title, 'issue' => $this->issue,
            'affected_count' => $this->affected_count, 'owner_name' => $this->owner_name,
            'due_date' => $this->due_date, 'status' => $this->status, 'severity' => $this->severity,
            'major_project' => new MajorProjectResource($this->whenLoaded('majorProject')),
        ];
    }
}
