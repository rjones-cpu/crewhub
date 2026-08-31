<?php

namespace App\Http\Requests;

use App\Enums\ProjectStatus;
use App\Models\Company;
use App\Models\MajorProject;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreMajorProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', MajorProject::class);
    }

    public function rules(): array
    {
        $companyId = $this->resolvedCompanyId();

        return [
            'company_id' => ['nullable', 'integer', 'exists:companies,id'],
            'name' => ['required', 'string', 'max:150'],
            'code' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('major_projects')->where('company_id', $companyId),
            ],
            'project_number' => [
                'required',
                'string',
                'max:100',
                Rule::unique('major_projects')->where('company_id', $companyId),
            ],
            'po_number' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'comments' => ['nullable', 'string', 'max:500'],
            'location' => ['nullable', 'string', 'max:150'],
            'address' => ['nullable', 'string', 'max:500'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'project_type' => ['nullable', 'string', 'max:100'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'status' => ['nullable', Rule::enum(ProjectStatus::class)],
            'icon' => ['nullable', 'string', 'max:100'],
            'manager_id' => ['nullable', 'integer', 'exists:users,id'],
            'modules' => ['nullable', 'array'],
            'modules.schedule' => ['nullable', 'boolean'],
            'modules.timesheets' => ['nullable', 'boolean'],
            'modules.lms' => ['nullable', 'boolean'],
            'modules.accommodations' => ['nullable', 'boolean'],
            'modules.journey_management' => ['nullable', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $companyId = $this->resolvedCompanyId();
            $name = trim((string) $this->input('name', ''));

            if (! $companyId || $name === '') {
                return;
            }

            $companyName = Company::query()->whereKey($companyId)->value('name');

            if ($companyName && strcasecmp(trim($companyName), $name) === 0) {
                $validator->errors()->add(
                    'name',
                    'The project name must be different from your organization name.',
                );
            }
        });
    }

    protected function prepareForValidation(): void
    {
        $modules = $this->input('modules', []);

        if (is_array($modules)) {
            $normalized = [];
            foreach (array_keys(MajorProject::defaultModules()) as $key) {
                $normalized[$key] = filter_var($modules[$key] ?? false, FILTER_VALIDATE_BOOLEAN);
            }
            $this->merge(['modules' => $normalized]);
        }

        if (! $this->filled('status')) {
            $this->merge(['status' => ProjectStatus::Active->value]);
        }

        if ($this->filled('project_number') && ! $this->filled('code')) {
            $this->merge(['code' => trim((string) $this->input('project_number'))]);
        }

        $this->merge(['company_id' => $this->user()->company_id]);
    }

    public function resolvedCompanyId(): ?int
    {
        return $this->user()->company_id ? (int) $this->user()->company_id : null;
    }
}
