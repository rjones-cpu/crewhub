<?php

namespace App\Http\Requests;

use App\Enums\JourneyStatus;
use App\Models\Journey;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateJourneyStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Journey $journey */
        $journey = $this->route('journey');

        return $this->user()->can('update', $journey);
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(JourneyStatus::class)],
        ];
    }
}
