<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class JourneyQuestionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type?->value,
            'type_label' => $this->type?->label(),
            'question' => $this->question,
            'description' => $this->description,
            'help_text' => $this->help_text,
            'options' => $this->options ?? [],
            'answer_options' => $this->answerOptions(),
            'max_characters' => $this->max_characters,
            'risk_key' => $this->risk_key,
            'risk_weight' => $this->risk_weight,
            'is_required' => $this->is_required,
            'is_active' => $this->is_active,
            'sort_order' => $this->sort_order,
        ];
    }
}
