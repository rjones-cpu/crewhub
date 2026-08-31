<?php

namespace App\Http\Requests;

use App\Models\JourneyHub;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class JourneyHubRequest extends FormRequest
{
    public function authorize(): bool
    {
        $hub = $this->route('hub');

        return $hub
            ? $this->user()->can('update', $hub)
            : $this->user()->can('create', JourneyHub::class);
    }

    public function rules(): array
    {
        $hub = $this->route('hub');

        return [
            'name' => ['required', 'string', 'max:120'],
            'code' => [
                'required',
                'string',
                'max:20',
                Rule::unique('journey_hubs', 'code')
                    ->where('company_id', $this->user()->company_id)
                    ->whereNull('deleted_at')
                    ->ignore($hub),
            ],
            'location' => ['nullable', 'string', 'max:180'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'radius_km' => ['nullable', 'integer', 'min:1', 'max:2000'],
            'contact_name' => ['nullable', 'string', 'max:120'],
            'contact_phone' => ['nullable', 'string', 'max:40'],
            'contact_email' => ['nullable', 'email', 'max:150'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['boolean'],
        ];
    }
}
