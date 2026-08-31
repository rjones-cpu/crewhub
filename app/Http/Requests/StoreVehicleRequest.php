<?php

namespace App\Http\Requests;

use App\Enums\VehicleAvailability;
use App\Enums\VehicleType;
use App\Models\Vehicle;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreVehicleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Vehicle::class);
    }

    public function rules(): array
    {
        $companyId = $this->user()->company_id;

        // "Save Draft" stores a partial record, so only identity fields stay mandatory.
        $required = $this->boolean('is_draft') ? 'nullable' : 'required';

        return [
            'is_draft' => ['boolean'],

            'make' => ['required', 'string', 'max:60'],
            'model' => ['required', 'string', 'max:60'],
            'year' => ['required', 'integer', 'min:1950', 'max:'.(now()->year + 1)],
            'vehicle_type' => ['required', Rule::enum(VehicleType::class)],
            'vin' => [
                'required',
                'string',
                'max:40',
                Rule::unique('vehicles', 'vin')->where('company_id', $companyId)->whereNull('deleted_at'),
            ],
            'license_plate' => [
                'required',
                'string',
                'max:20',
                Rule::unique('vehicles', 'license_plate')->where('company_id', $companyId)->whereNull('deleted_at'),
            ],
            'assigned_driver_id' => [
                $required,
                'integer',
                Rule::exists('workers', 'id')->where('company_id', $companyId),
            ],

            'has_attachments' => ['boolean'],
            'insurance_document' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'insurance_provider' => [$required, 'string', 'max:120'],
            'policy_number' => [$required, 'string', 'max:60'],
            'coverage_type' => [$required, 'string', 'max:40'],
            'coverage_amount' => [$required, 'numeric', 'min:0', 'max:99999999.99'],
            'policy_start_date' => [$required, 'date'],
            'policy_end_date' => [$required, 'date', 'after:policy_start_date'],

            'base_location' => ['nullable', 'string', 'max:120'],
            'purpose' => ['nullable', 'string', 'max:60'],
            'additional_notes' => ['nullable', 'string', 'max:2000'],
            'additional_details' => ['nullable', 'string', 'max:1000'],

            'availability' => ['nullable', Rule::enum(VehicleAvailability::class)],
            'transmission' => ['nullable', 'string', 'max:20'],
            'odometer_km' => ['nullable', 'integer', 'min:0', 'max:9999999'],
            'known_issues' => ['nullable', 'string', 'max:2000'],

            'equipment' => ['nullable', 'array'],
            'equipment.*' => ['string', 'max:60'],
            'maintenance_notes' => ['nullable', 'string', 'max:2000'],
            'last_service_at' => ['nullable', 'date'],
            'next_service_due_at' => ['nullable', 'date'],
        ];
    }

    public function attributes(): array
    {
        return [
            'vin' => 'VIN number',
            'assigned_driver_id' => 'assigned driver',
            'insurance_document' => 'insurance / registration document',
        ];
    }
}
