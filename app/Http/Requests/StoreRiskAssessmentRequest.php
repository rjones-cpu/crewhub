<?php

namespace App\Http\Requests;

use App\Models\JourneyRiskAssessment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRiskAssessmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', JourneyRiskAssessment::class);
    }

    public function rules(): array
    {
        return [
            'journey_id' => [
                'required',
                'integer',
                Rule::exists('journeys', 'id')->where('company_id', $this->user()->company_id),
            ],
            'weather' => ['nullable', 'string', 'max:40'],
            'temperature_c' => ['nullable', 'integer', 'min:-60', 'max:70'],
            'road_conditions' => ['nullable', 'string', 'max:40'],
            'road_condition_quality' => ['nullable', 'string', 'max:20'],
        ];
    }
}
