<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ModuleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'key' => $this->key,
            'description' => $this->description,
            'is_paid' => (bool) $this->is_paid,
            'is_active' => (bool) $this->is_active,
            'sort_order' => $this->sort_order,
            'companies_with_access' => $this->when(
                isset($this->companies_with_access),
                $this->companies_with_access,
            ),
            'pending_requests_count' => $this->when(
                isset($this->pending_requests_count),
                $this->pending_requests_count,
            ),
        ];
    }
}
