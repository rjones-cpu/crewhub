<?php

namespace App\Services\Workers;

use App\Models\Journey;
use App\Models\Position;
use App\Models\Timesheet;
use App\Models\User;
use App\Models\Worker;
use App\Models\WorkerActivity;
use App\Models\WorkerReadiness;
use App\Services\Modules\ModuleAccessService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class WorkerService
{
    public function __construct(
        private readonly WorkerFeatureAccessService $featureAccess,
        private readonly ModuleAccessService $moduleAccess,
    ) {}

    public function paginate(array $filters, int $perPage = 10): LengthAwarePaginator
    {
        return Worker::query()
            ->with(['primaryProject', 'readiness', 'company', 'activities' => fn ($q) => $q->latest()->limit(1)])
            ->filter($filters)
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function stats(?int $projectId = null): array
    {
        $workers = Worker::query()->when($projectId, fn ($q) => $q->where('primary_project_id', $projectId));
        $readiness = WorkerReadiness::query()->when(
            $projectId,
            fn ($q) => $q->whereHas('worker', fn ($q) => $q->where('primary_project_id', $projectId))
        );

        return [
            'total' => (clone $workers)->count(),
            'active' => (clone $workers)->where('status', 'active')->count(),
            'inactive' => (clone $workers)->where('status', 'inactive')->count(),
            'on_leave' => (clone $workers)->where('status', 'on_leave')->count(),
            'ready' => (clone $readiness)->where('overall_status', 'ready')->count(),
            'timesheets_pending' => Timesheet::query()
                ->when($projectId, fn ($q) => $q->where('major_project_id', $projectId))
                ->whereIn('status', ['submitted', 'manager_approved'])
                ->count(),
            'journeys_due' => Journey::query()
                ->when($projectId, fn ($q) => $q->where('major_project_id', $projectId))
                ->whereIn('status', ['pending', 'approved'])
                ->whereBetween('departure_at', [now(), now()->addDays(7)])
                ->count(),
        ];
    }

    public function featureSummary(?User $user = null): array
    {
        $workers = Worker::query()->with('primaryProject')->get();
        $total = $workers->count();

        return collect(WorkerFeatureAccessService::FEATURES)
            ->map(function (array $definition, string $feature) use ($workers, $total, $user): array {
                $eligible = $workers->filter(
                    fn (Worker $worker) => $this->featureAccess->projectAllows($worker->primaryProject, $feature)
                );
                $enabled = $eligible->filter(
                    fn (Worker $worker) => (bool) $worker->{$definition['worker_column']}
                )->count();

                return [
                    'enabled' => $eligible->isNotEmpty() && $enabled === $eligible->count(),
                    'enabled_count' => $enabled,
                    'eligible_count' => $eligible->count(),
                    'project_blocked_count' => $total - $eligible->count(),
                    'total' => $total,
                    ...$this->moduleLock($user, $feature),
                ];
            })
            ->all();
    }

    /**
     * Paid-module gate for a worker feature: what the UI needs to grey the
     * toggle out and offer activation instead.
     *
     * @return array{locked: bool, module: array{id: int, name: string}|null, activation_pending: bool, can_request_activation: bool}
     */
    private function moduleLock(?User $user, string $feature): array
    {
        $unlocked = [
            'locked' => false,
            'module' => null,
            'activation_pending' => false,
            'can_request_activation' => false,
        ];

        if (! $user || $this->featureAccess->companyModuleAllows($user->company_id, $feature)) {
            return $unlocked;
        }

        $module = $this->moduleAccess->findByKey(WorkerFeatureAccessService::FEATURES[$feature]['module_key']);

        if (! $module) {
            return $unlocked;
        }

        return [
            'locked' => true,
            'module' => ['id' => $module->id, 'name' => $module->name],
            'activation_pending' => $user->company_id
                ? $this->moduleAccess->pendingRequestFor($user->company_id, $module->id) !== null
                : false,
            'can_request_activation' => $user->can('requestActivation', $module),
        ];
    }

    public function recentActivity(int $limit = 8): array
    {
        return WorkerActivity::query()
            ->with('worker')
            ->latest()
            ->limit($limit)
            ->get()
            ->map(fn ($activity) => [
                'id' => $activity->id,
                'worker' => $activity->worker?->full_name,
                'employee_id' => $activity->worker?->employee_id,
                'type' => $activity->type,
                'description' => $activity->description,
                'created_at' => $activity->created_at?->diffForHumans(),
            ])
            ->all();
    }

    public function filterOptions(): array
    {
        return [
            'positions' => Position::query()->active()->ordered()->pluck('name')
                ->merge(Worker::query()->whereNotNull('position')->where('position', '!=', '')->distinct()->pluck('position'))
                ->unique()
                ->sort()
                ->values(),
            'locations' => Worker::query()->whereNotNull('location')->distinct()->orderBy('location')->pluck('location'),
            'statuses' => ['active', 'inactive', 'on_leave', 'mobilizing', 'demobilizing'],
        ];
    }
}
