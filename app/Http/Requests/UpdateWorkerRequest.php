<?php

namespace App\Http\Requests;

use App\Enums\WorkerStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateWorkerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('worker'));
    }

    public function rules(): array
    {
        $worker = $this->route('worker');

        return [
            'employee_id' => [
                'sometimes',
                'nullable',
                'string',
                'max:50',
                Rule::unique('workers')->where('company_id', $worker->company_id)->ignore($worker),
            ],
            'first_name' => ['sometimes', 'required', 'string', 'max:100'],
            'last_name' => ['sometimes', 'required', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'gender' => ['nullable', Rule::in(['female', 'male', 'non_binary', 'prefer_not_to_say'])],
            'position' => ['nullable', 'string', 'max:150'],
            'trade' => ['nullable', 'string', 'max:150'],
            'location' => ['nullable', 'string', 'max:150'],
            'status' => ['sometimes', Rule::enum(WorkerStatus::class)],
            'avatar' => ['nullable', 'string', 'max:255'],
            'on_site' => ['boolean'],
            'primary_project_id' => [
                'nullable',
                Rule::exists('major_projects', 'id')->where('company_id', $worker->company_id),
            ],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'documents' => ['nullable', 'array', 'max:10'],
            'documents.*' => ['file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->exists('employee_id')) {
            $this->merge([
                'employee_id' => $this->filled('employee_id')
                    ? trim((string) $this->input('employee_id'))
                    : null,
            ]);
        }

        if ($this->exists('primary_project_id')) {
            $this->merge([
                'primary_project_id' => $this->filled('primary_project_id')
                    ? $this->input('primary_project_id')
                    : null,
            ]);
        }
    }
}
