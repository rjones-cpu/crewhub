<?php

namespace App\Http\Requests;

use App\Models\Journey;
use App\Models\MajorProject;
use App\Models\Worker;
use App\Services\Workers\WorkerFeatureAccessService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreJourneyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Journey::class);
    }

    public function rules(): array
    {
        $companyId = $this->user()->company_id;

        return [
            'worker_id' => [
                'required',
                'integer',
                Rule::exists('workers', 'id')->where('company_id', $companyId),
            ],
            'major_project_id' => [
                'nullable',
                'integer',
                Rule::exists('major_projects', 'id')->where('company_id', $companyId),
            ],
            'type' => ['nullable', 'string', 'max:50'],
            'origin' => ['required', 'string', 'max:150'],
            'destination' => ['required', 'string', 'max:150'],
            'vehicle_plate' => ['nullable', 'string', 'max:40'],
            'vehicle_model' => ['nullable', 'string', 'max:80'],
            'hub' => ['nullable', 'string', 'max:120'],
            'distance_km' => ['nullable', 'numeric', 'min:0', 'max:99999'],
            'departure_at' => ['required', 'date'],
            'arrival_at' => ['nullable', 'date', 'after:departure_at'],
            'emergency_contact_name' => ['nullable', 'string', 'max:150'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:40'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $featureAccess = app(WorkerFeatureAccessService::class);
            $worker = Worker::query()
                ->with('primaryProject')
                ->whereKey($this->integer('worker_id'))
                ->first();

            if ($worker && ! $featureAccess->allows($worker, 'journey')) {
                $validator->errors()->add(
                    'worker_id',
                    'Journey Management is disabled for this worker or their primary project.',
                );
            }

            if ($this->filled('major_project_id')) {
                $project = MajorProject::query()->find($this->integer('major_project_id'));

                if ($project && ! $featureAccess->projectAllows($project, 'journey')) {
                    $validator->errors()->add(
                        'major_project_id',
                        'Journey Management is disabled for this project.',
                    );
                }
            }
        });
    }
}
