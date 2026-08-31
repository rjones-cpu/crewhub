<?php

namespace App\Services\Readiness;

use App\Models\Certification;
use App\Models\Journey;
use App\Models\MedicalRecord;
use App\Models\PriorityAction;
use App\Models\Worker;
use App\Models\WorkerActivity;
use App\Models\WorkerReadiness;

class ReadinessOverviewService
{
    public function overview(?int $projectId = null, int $attentionPage = 1): array
    {
        $workers = Worker::query()->when($projectId, fn ($q) => $q->where('primary_project_id', $projectId));
        $readiness = WorkerReadiness::query()->when(
            $projectId,
            fn ($q) => $q->whereHas('worker', fn ($q) => $q->where('primary_project_id', $projectId))
        );

        $total = (clone $workers)->count();
        $ready = (clone $readiness)->where('overall_status', 'ready')->count();
        $atRisk = (clone $readiness)->where('overall_status', 'at_risk')->count();
        $notReady = (clone $readiness)->where('overall_status', 'not_ready')->count();
        $pending = (clone $readiness)->where('overall_status', 'pending_review')->count();

        $expiringCerts = Certification::query()
            ->whereBetween('expires_at', [now(), now()->addDays(30)])
            ->when($projectId, fn ($q) => $q->whereHas('worker', fn ($q) => $q->where('primary_project_id', $projectId)))
            ->count();

        $journeysPending = Journey::query()
            ->when($projectId, fn ($q) => $q->where('major_project_id', $projectId))
            ->where('status', 'pending')
            ->count();

        return [
            'stats' => [
                'total' => $total,
                'ready' => $ready,
                'at_risk' => $atRisk,
                'not_ready' => $notReady,
                'pending_review' => $pending,
                'certs_expiring' => $expiringCerts,
                'journeys_pending' => $journeysPending,
                'ready_pct' => $total > 0 ? round(($ready / $total) * 100) : 0,
            ],
            'overview' => [
                ['name' => 'Ready', 'value' => $ready, 'color' => '#22c55e'],
                ['name' => 'At Risk', 'value' => $atRisk, 'color' => '#f59e0b'],
                ['name' => 'Not Ready', 'value' => $notReady, 'color' => '#ef4444'],
                ['name' => 'Pending Review', 'value' => $pending, 'color' => '#8b5cf6'],
            ],
            'categories' => $this->categories($readiness),
            'attention' => $this->workersRequiringAttention($projectId, $attentionPage),
            'criticalConcerns' => $this->criticalConcerns($projectId),
            'upcomingExpiries' => $this->upcomingExpiries($projectId),
            'recentActivity' => $this->recentActivity($projectId),
            'meta' => [
                'period_label' => now()->startOfWeek()->format('M j').' – '.now()->endOfWeek()->addWeek()->format('M j, Y'),
                'generated_at' => now()->format('g:i A'),
            ],
        ];
    }

    protected function categories($readinessQuery): array
    {
        $fields = [
            'medical_status' => 'Medical',
            'certification_status' => 'Certifications',
            'training_status' => 'Training / LMS',
            'journey_status' => 'Journey Approval',
            'accommodation_status' => 'Accommodation Assigned',
            'site_access_status' => 'Site Access',
        ];

        $rows = [];
        foreach ($fields as $field => $label) {
            $ready = (clone $readinessQuery)->where($field, 'ready')->count();
            $atRisk = (clone $readinessQuery)->where($field, 'at_risk')->count();
            $notReady = (clone $readinessQuery)->whereIn($field, ['not_ready', 'pending', 'pending_review'])->count();
            $total = max($ready + $atRisk + $notReady, 1);

            $rows[] = [
                'name' => $label,
                'ready' => $ready,
                'at_risk' => $atRisk,
                'not_ready' => $notReady,
                'ready_pct' => round(($ready / $total) * 100),
                'at_risk_pct' => round(($atRisk / $total) * 100),
                'not_ready_pct' => round(($notReady / $total) * 100),
            ];
        }

        return $rows;
    }

    protected function workersRequiringAttention(?int $projectId, int $page): array
    {
        $paginator = WorkerReadiness::query()
            ->with(['worker.primaryProject', 'worker.company'])
            ->whereIn('overall_status', ['at_risk', 'not_ready', 'pending_review'])
            ->when($projectId, fn ($q) => $q->whereHas('worker', fn ($q) => $q->where('primary_project_id', $projectId)))
            ->latest('updated_at')
            ->paginate(5, ['*'], 'attention_page', max($page, 1));

        $rows = collect($paginator->items())->map(function ($row) {
            $dueDate = $row->last_checked_at?->copy()->addDays(7);

            return [
                'id' => $row->id,
                'worker_id' => $row->worker_id,
                'worker' => $row->worker?->full_name ?? 'Unknown worker',
                'employee_id' => $row->worker?->employee_id,
                'avatar' => $row->worker?->avatar,
                'company' => $row->worker?->company?->name,
                'primary_project' => $row->worker?->primaryProject?->name,
                'issue' => $this->primaryIssue($row),
                'due_date' => $dueDate?->format('M d, Y'),
                'due_relative' => $dueDate?->diffForHumans(),
                'status' => $row->overall_status,
            ];
        })->all();

        return [
            'data' => $rows,
            'links' => $paginator->linkCollection()->toArray(),
            'meta' => [
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
                'total' => $paginator->total(),
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
            ],
        ];
    }

    protected function primaryIssue(WorkerReadiness $row): string
    {
        $map = [
            'medical_status' => 'Medical clearance required',
            'certification_status' => 'Certification expiring / invalid',
            'training_status' => 'Training incomplete',
            'journey_status' => 'Journey approval pending',
            'accommodation_status' => 'Accommodation not assigned',
            'site_access_status' => 'Site access restricted',
        ];

        foreach ($map as $field => $label) {
            $value = $row->{$field};
            $value = is_object($value) ? $value->value : $value;
            if (in_array($value, ['at_risk', 'not_ready', 'pending', 'pending_review'], true)) {
                return $label;
            }
        }

        return $row->notes ?: 'Readiness review required';
    }

    protected function criticalConcerns(?int $projectId): array
    {
        return PriorityAction::query()
            ->when($projectId, fn ($q) => $q->where('major_project_id', $projectId))
            ->whereIn('severity', ['critical', 'high'])
            ->whereNot('status', 'resolved')
            ->orderByRaw("CASE severity WHEN 'critical' THEN 1 WHEN 'high' THEN 2 ELSE 3 END")
            ->limit(5)
            ->get()
            ->map(fn ($action) => [
                'id' => $action->id,
                'title' => $action->title,
                'issue' => $action->issue,
                'severity' => $action->severity,
                'affected' => $action->affected_count,
                'due_date' => optional($action->due_date)?->format('M d, Y'),
            ])
            ->all();
    }

    protected function upcomingExpiries(?int $projectId): array
    {
        $certs = Certification::query()
            ->with('worker')
            ->whereBetween('expires_at', [now(), now()->addDays(45)])
            ->when($projectId, fn ($q) => $q->whereHas('worker', fn ($q) => $q->where('primary_project_id', $projectId)))
            ->orderBy('expires_at')
            ->limit(5)
            ->get()
            ->map(fn ($cert) => [
                'id' => 'cert-'.$cert->id,
                'type' => 'Certification',
                'name' => $cert->name,
                'worker' => $cert->worker?->full_name,
                'expires_at' => optional($cert->expires_at)?->format('M d, Y'),
                'days_left' => now()->diffInDays($cert->expires_at),
            ]);

        $medical = MedicalRecord::query()
            ->with('worker')
            ->whereBetween('expires_at', [now(), now()->addDays(45)])
            ->when($projectId, fn ($q) => $q->whereHas('worker', fn ($q) => $q->where('primary_project_id', $projectId)))
            ->orderBy('expires_at')
            ->limit(5)
            ->get()
            ->map(fn ($record) => [
                'id' => 'med-'.$record->id,
                'type' => 'Medical',
                'name' => $record->exam_type,
                'worker' => $record->worker?->full_name,
                'expires_at' => optional($record->expires_at)?->format('M d, Y'),
                'days_left' => now()->diffInDays($record->expires_at),
            ]);

        return $certs->concat($medical)->sortBy('days_left')->take(8)->values()->all();
    }

    protected function recentActivity(?int $projectId): array
    {
        return WorkerActivity::query()
            ->with('worker')
            ->when($projectId, fn ($q) => $q->whereHas('worker', fn ($q) => $q->where('primary_project_id', $projectId)))
            ->where(function ($query) {
                $query->where('type', 'like', '%readiness%')
                    ->orWhere('description', 'like', '%readiness%')
                    ->orWhere('description', 'like', '%cert%')
                    ->orWhere('description', 'like', '%medical%');
            })
            ->latest()
            ->limit(8)
            ->get()
            ->map(fn ($activity) => [
                'id' => $activity->id,
                'worker' => $activity->worker?->full_name,
                'description' => $activity->description,
                'type' => $activity->type,
                'created_at' => $activity->created_at?->diffForHumans(),
            ])
            ->all();
    }
}
