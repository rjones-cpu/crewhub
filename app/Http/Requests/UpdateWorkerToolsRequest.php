<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateWorkerToolsRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()->can('update', $this->route('worker')); }

    public function rules(): array
    {
        return [
            'module_access' => ['sometimes', 'boolean'],
            'schedule_access' => ['sometimes', 'boolean'],
            'timesheet_access' => ['sometimes', 'boolean'],
            'lms_access' => ['sometimes', 'boolean'],
            'journey_access' => ['sometimes', 'boolean'],
        ];
    }
}
