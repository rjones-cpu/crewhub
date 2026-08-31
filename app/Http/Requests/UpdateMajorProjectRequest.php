<?php

namespace App\Http\Requests;

use App\Enums\ProjectStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMajorProjectRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()->can('update', $this->route('major_project')); }

    public function rules(): array
    {
        $project = $this->route('major_project');

        return [
            'name' => ['sometimes', 'required', 'string', 'max:150'],
            'code' => ['sometimes', 'required', 'string', 'max:50', Rule::unique('major_projects')->where('company_id', $project->company_id)->ignore($project)],
            'description' => ['nullable', 'string'],
            'location' => ['nullable', 'string', 'max:150'],
            'project_type' => ['nullable', 'string', 'max:100'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'status' => ['sometimes', Rule::enum(ProjectStatus::class)],
            'icon' => ['nullable', 'string', 'max:100'],
        ];
    }
}
