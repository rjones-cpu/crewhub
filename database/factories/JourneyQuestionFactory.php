<?php

namespace Database\Factories;

use App\Enums\JourneyQuestionType;
use App\Models\Company;
use App\Models\JourneyQuestion;
use Illuminate\Database\Eloquent\Factories\Factory;

class JourneyQuestionFactory extends Factory
{
    protected $model = JourneyQuestion::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'type' => JourneyQuestionType::YesNo,
            'question' => rtrim(fake()->sentence(), '.').'?',
            'description' => fake()->sentence(),
            'options' => [],
            'risk_weight' => 0,
            'is_required' => true,
            'is_active' => true,
            'sort_order' => fake()->numberBetween(1, 50),
        ];
    }

    public function dropdown(array $options = ['Good', 'Poor']): static
    {
        return $this->state(fn () => [
            'type' => JourneyQuestionType::Dropdown,
            'options' => $options,
        ]);
    }
}
