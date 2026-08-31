<?php

namespace App\Http\Requests;

use App\Models\MajorProject;
use Illuminate\Foundation\Http\FormRequest;

class StoreProjectInvitationsRequest extends FormRequest
{
    public function authorize(): bool
    {
        $project = $this->route('major_project');

        return $project instanceof MajorProject
            && $this->user()->can('invite', $project);
    }

    public function rules(): array
    {
        return [
            'company_ids' => ['required', 'array', 'min:1'],
            'company_ids.*' => ['required', 'integer', 'distinct', 'exists:companies,id'],
        ];
    }
}
