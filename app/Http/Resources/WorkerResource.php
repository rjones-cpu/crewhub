<?php

namespace App\Http\Resources;

use App\Services\Workers\WorkerFeatureAccessService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WorkerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $latestActivity = $this->relationLoaded('activities') ? $this->activities->first() : null;
        $lastActiveAt = $latestActivity?->created_at ?? $this->updated_at;
        $featureAccess = app(WorkerFeatureAccessService::class);

        return [
            'id' => $this->id,
            'employee_id' => $this->employee_id,
            'full_name' => $this->full_name,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'date_of_birth' => $this->date_of_birth?->toDateString(),
            'gender' => $this->gender,
            'position' => $this->position,
            'trade' => $this->trade,
            'location' => $this->location,
            'status' => $this->status?->value ?? $this->status,
            'avatar' => $this->avatar,
            'on_site' => $this->on_site,
            'start_date' => $this->start_date?->toDateString(),
            'end_date' => $this->end_date?->toDateString(),
            'notes' => $this->notes,
            'documents' => $this->documents ?? [],
            'company' => $this->whenLoaded('company', fn () => [
                'id' => $this->company?->id,
                'name' => $this->company?->name,
                'code' => $this->company?->code,
            ]),
            'primary_project' => $this->whenLoaded('primaryProject', fn () => $this->primaryProject ? [
                'id' => $this->primaryProject->id,
                'name' => $this->primaryProject->name,
                'code' => $this->primaryProject->code,
            ] : null),
            'readiness' => $this->whenLoaded('readiness', fn () => $this->readiness ? [
                'overall_status' => $this->readiness->overall_status?->value ?? $this->readiness->overall_status,
                'medical_status' => $this->readiness->medical_status?->value ?? $this->readiness->medical_status,
                'certification_status' => $this->readiness->certification_status?->value ?? $this->readiness->certification_status,
                'training_status' => $this->readiness->training_status?->value ?? $this->readiness->training_status,
                'journey_status' => $this->readiness->journey_status?->value ?? $this->readiness->journey_status,
                'accommodation_status' => $this->readiness->accommodation_status?->value ?? $this->readiness->accommodation_status,
                'site_access_status' => $this->readiness->site_access_status?->value ?? $this->readiness->site_access_status,
                'last_checked_at' => $this->readiness->last_checked_at,
            ] : null),
            'tool_access' => [
                'module' => (bool) $this->module_access,
                'schedule' => $featureAccess->allows($this->resource, 'schedule'),
                'timesheet' => $featureAccess->allows($this->resource, 'timesheet'),
                'lms' => $featureAccess->allows($this->resource, 'lms'),
                'journey' => $featureAccess->allows($this->resource, 'journey'),
            ],
            'project_feature_access' => [
                'schedule' => $featureAccess->projectAllows($this->primaryProject, 'schedule'),
                'timesheet' => $featureAccess->projectAllows($this->primaryProject, 'timesheet'),
                'lms' => $featureAccess->projectAllows($this->primaryProject, 'lms'),
                'journey' => $featureAccess->projectAllows($this->primaryProject, 'journey'),
            ],
            'assignments' => $this->whenLoaded('assignments', fn () => $this->assignments->map(fn ($assignment) => [
                'id' => $assignment->id,
                'role' => $assignment->role,
                'is_primary' => $assignment->is_primary,
                'status' => $assignment->status,
                'project' => $assignment->majorProject?->name,
                'project_code' => $assignment->majorProject?->code,
            ])),
            'last_activity' => $latestActivity ? [
                'description' => $latestActivity->description,
                'type' => $latestActivity->type,
                'created_at' => $latestActivity->created_at?->diffForHumans(),
                'date' => $lastActiveAt?->format('M j, Y'),
                'time' => $lastActiveAt?->format('g:i A'),
            ] : [
                'description' => null,
                'type' => null,
                'created_at' => $lastActiveAt?->diffForHumans(),
                'date' => $lastActiveAt?->format('M j, Y'),
                'time' => $lastActiveAt?->format('g:i A'),
            ],
            'activities' => $this->whenLoaded('activities', fn () => $this->activities->map(fn ($activity) => [
                'id' => $activity->id,
                'type' => $activity->type,
                'description' => $activity->description,
                'created_at' => $activity->created_at?->diffForHumans(),
            ])),
        ];
    }
}
