<?php

namespace App\Models;

use App\Enums\JourneyQuestionType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;

class JourneyQuestion extends CompanyModel
{
    use SoftDeletes;

    protected function casts(): array
    {
        return [
            'type' => JourneyQuestionType::class,
            'options' => 'array',
            'max_characters' => 'integer',
            'risk_weight' => 'integer',
            'is_required' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    /**
     * Answer choices shown to the driver. Fixed-choice types have implicit options
     * so authors never have to retype "Yes / No".
     */
    public function answerOptions(): array
    {
        return match ($this->type) {
            JourneyQuestionType::YesNo => ['Yes', 'No'],
            JourneyQuestionType::TrueFalse => ['True', 'False'],
            default => $this->options ?? [],
        };
    }
}
