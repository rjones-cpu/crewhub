<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTimesheetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('timesheet')) ?? false;
    }

    public function rules(): array
    {
        return [
            'day_entries' => ['sometimes', 'array'],
            'day_entries.*.date' => ['nullable', 'date'],
            'day_entries.*.day_label' => ['nullable', 'string', 'max:10'],
            'day_entries.*.shift' => ['nullable', 'string', 'max:20'],
            'day_entries.*.start_time' => ['nullable', 'string', 'max:10'],
            'day_entries.*.end_time' => ['nullable', 'string', 'max:10'],
            'day_entries.*.break_hours' => ['nullable', 'numeric', 'min:0'],
            'day_entries.*.regular_hours' => ['nullable', 'numeric', 'min:0'],
            'day_entries.*.overtime_hours' => ['nullable', 'numeric', 'min:0'],
            'day_entries.*.double_time_hours' => ['nullable', 'numeric', 'min:0'],
            'day_entries.*.travel_hours' => ['nullable', 'numeric', 'min:0'],
            'day_entries.*.standby_hours' => ['nullable', 'numeric', 'min:0'],
            'day_entries.*.total_hours' => ['nullable', 'numeric', 'min:0'],
            'day_entries.*.work_location' => ['nullable', 'string', 'max:120'],
            'day_entries.*.task' => ['nullable', 'string', 'max:120'],
            'day_entries.*.work_code' => ['nullable', 'string', 'max:120'],
            'day_entries.*.hourly_rate' => ['nullable', 'numeric', 'min:0'],
            'day_entries.*.notes' => ['nullable', 'string', 'max:500'],
            'equipment_entries' => ['sometimes', 'array'],
            'equipment_entries.*.id' => ['nullable', 'string', 'max:50'],
            'equipment_entries.*.day' => ['nullable', 'string', 'max:10'],
            'equipment_entries.*.date' => ['nullable', 'date'],
            'equipment_entries.*.equipment_type' => ['nullable', 'string', 'max:80'],
            'equipment_entries.*.unit_id' => ['nullable', 'string', 'max:50'],
            'equipment_entries.*.start_time' => ['nullable', 'string', 'max:10'],
            'equipment_entries.*.end_time' => ['nullable', 'string', 'max:10'],
            'equipment_entries.*.hours' => ['nullable', 'numeric', 'min:0'],
            'equipment_entries.*.cost_code' => ['nullable', 'string', 'max:50'],
            'equipment_entries.*.work_activity' => ['nullable', 'string', 'max:120'],
            'equipment_entries.*.work_code' => ['nullable', 'string', 'max:120'],
            'equipment_entries.*.notes' => ['nullable', 'string', 'max:255'],
            'equipment_entries.*.fuel_notes' => ['nullable', 'string', 'max:255'],
            'equipment_entries.*.manager' => ['nullable', 'string', 'max:120'],
            'compliance' => ['sometimes', 'array'],
            'compliance.requirements' => ['sometimes', 'array'],
            'compliance.requirements.*' => ['sometimes', 'boolean'],
            'compliance.approval_settings' => ['sometimes', 'array'],
            'compliance.approval_settings.*' => ['sometimes', 'boolean'],
            'compliance.safety_meeting' => ['sometimes', 'boolean'],
            'compliance.toolbox_talk' => ['sometimes', 'boolean'],
            'compliance.incident_report' => ['sometimes', 'boolean'],
            'compliance.attachments' => ['sometimes', 'boolean'],
            'compliance.signature' => ['sometimes', 'boolean'],
            'compliance.worker_declaration' => ['sometimes', 'boolean'],
            'worker_comment' => ['nullable', 'string', 'max:2000'],
            'manager_comment' => ['nullable', 'string', 'max:2000'],
            'client_comment' => ['nullable', 'string', 'max:2000'],
            'worker_signature' => ['nullable', 'string', 'max:255'],
            'client_approval_required' => ['sometimes', 'boolean'],
            'supervisor_name' => ['nullable', 'string', 'max:120'],
        ];
    }
}
