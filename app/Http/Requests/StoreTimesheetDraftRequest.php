<?php

namespace App\Http\Requests;

use App\Models\Timesheet;
use App\Models\Worker;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTimesheetDraftRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Timesheet::class) ?? false;
    }

    public function rules(): array
    {
        $user = $this->user();
        $worker = Rule::exists(Worker::class, 'id');

        if ($user && ! $user->isSuperAdmin() && $user->company_id) {
            $worker->where('company_id', $user->company_id);
        }

        return [
            'worker_id' => ['required', 'integer', $worker],
            'week' => ['required', 'date'],
        ];
    }
}
