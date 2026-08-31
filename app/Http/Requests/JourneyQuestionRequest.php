<?php

namespace App\Http\Requests;

use App\Enums\JourneyQuestionType;
use App\Models\JourneyQuestion;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class JourneyQuestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $question = $this->route('question');

        return $question
            ? $this->user()->can('update', $question)
            : $this->user()->can('create', JourneyQuestion::class);
    }

    public function rules(): array
    {
        $needsOptions = JourneyQuestionType::tryFrom((string) $this->input('type'))
            ?->hasCustomOptions() ?? false;

        return [
            'type' => ['required', Rule::enum(JourneyQuestionType::class)],
            'question' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:255'],
            'help_text' => ['nullable', 'string', 'max:255'],
            'options' => [$needsOptions ? 'required' : 'nullable', 'array', 'max:10'],
            'options.*' => ['required', 'string', 'max:120'],
            'max_characters' => ['nullable', 'integer', 'min:1', 'max:2000'],
            'risk_key' => ['nullable', 'string', 'max:40'],
            'risk_weight' => ['nullable', 'integer', 'min:0', 'max:30'],
            'is_required' => ['boolean'],
            'is_active' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'options.required' => 'Add at least one answer option for this question type.',
        ];
    }
}
