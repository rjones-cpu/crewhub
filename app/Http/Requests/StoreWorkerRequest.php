<?php

namespace App\Http\Requests;

use App\Enums\WorkerStatus;
use App\Models\Worker;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreWorkerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Worker::class);
    }

    public function rules(): array
    {
        return [
            'employee_id' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('workers')->where('company_id', $this->user()->company_id),
            ],
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'gender' => ['nullable', Rule::in(['female', 'male', 'non_binary', 'prefer_not_to_say'])],
            'position' => ['nullable', 'string', 'max:150'],
            'trade' => ['nullable', 'string', 'max:150'],
            'location' => ['nullable', 'string', 'max:150'],
            'status' => ['required', Rule::enum(WorkerStatus::class)],
            'avatar' => ['nullable', 'string', 'max:255'],
            'on_site' => ['boolean'],
            'primary_project_id' => [
                'nullable',
                Rule::exists('major_projects', 'id')->where('company_id', $this->user()->company_id),
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
        $this->merge([
            'employee_id' => $this->filled('employee_id') ? trim((string) $this->input('employee_id')) : null,
            'primary_project_id' => $this->filled('primary_project_id')
                ? $this->input('primary_project_id')
                : null,
        ]);
    }
}
