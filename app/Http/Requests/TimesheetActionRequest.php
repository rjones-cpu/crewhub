<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TimesheetActionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $timesheet = $this->route('timesheet');
        $action = $this->route()->getName();

        return match ($action) {
            'timesheets.submit' => $this->user()?->can('submit', $timesheet) ?? false,
            'timesheets.approve-manager', 'timesheets.approve-client' => $this->user()?->can('approve', $timesheet) ?? false,
            'timesheets.return' => $this->user()?->can('returnTimesheet', $timesheet) ?? false,
            'timesheets.reject' => $this->user()?->can('reject', $timesheet) ?? false,
            default => false,
        };
    }

    public function rules(): array
    {
        return [
            'comment' => ['nullable', 'string', 'max:2000'],
            'reason' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
