<?php

namespace App\Services\Workers;

use App\Models\MajorProject;
use App\Models\Module;
use App\Models\Worker;
use App\Services\Modules\ModuleAccessService;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class WorkerFeatureAccessService
{
    /**
     * `module_key` names the paid platform module that gates the feature; null
     * means the feature is free for every company.
     *
     * @var array<string, array{worker_column: string, project_module: string, module_key: string|null}>
     */
    public const FEATURES = [
        'schedule' => [
            'worker_column' => 'schedule_access',
            'project_module' => 'schedule',
            'module_key' => null,
        ],
        'timesheet' => [
            'worker_column' => 'timesheet_access',
            'project_module' => 'timesheets',
            'module_key' => null,
        ],
        'lms' => [
            'worker_column' => 'lms_access',
            'project_module' => 'lms',
            'module_key' => Module::KEY_LMS,
        ],
        'journey' => [
            'worker_column' => 'journey_access',
            'project_module' => 'journey_management',
            'module_key' => null,
        ],
    ];

    public function __construct(private readonly ModuleAccessService $moduleAccess) {}

    public function allows(Worker $worker, string $feature): bool
    {
        $definition = $this->definition($feature);

        return (bool) $worker->{$definition['worker_column']}
            && $this->projectAllows($worker->primaryProject, $feature);
    }

    /**
     * Whether the company owns the paid module behind the feature. Features
     * without a module key are always allowed.
     */
    public function companyModuleAllows(?int $companyId, string $feature): bool
    {
        $moduleKey = $this->definition($feature)['module_key'];

        if (! $moduleKey) {
            return true;
        }

        // Without an active catalog row the module is not being sold yet, so the
        // feature stays free rather than becoming unusable for everyone.
        if (! $this->moduleAccess->findByKey($moduleKey)) {
            return true;
        }

        return $this->moduleAccess->companyHasActiveAccess($companyId, $moduleKey);
    }

    public function projectAllows(?MajorProject $project, string $feature): bool
    {
        if (! $project) {
            return true;
        }

        $definition = $this->definition($feature);
        $modules = array_merge(MajorProject::defaultModules(), $project->modules ?? []);

        return (bool) $modules[$definition['project_module']];
    }

    /**
     * Set access for the company roster while respecting each worker's project.
     */
    public function setForCompany(string $feature, bool $enabled): void
    {
        $definition = $this->definition($feature);

        Worker::query()
            ->with('primaryProject')
            ->get()
            ->each(function (Worker $worker) use ($definition, $enabled, $feature): void {
                $worker->update([
                    $definition['worker_column'] => $enabled && $this->projectAllows($worker->primaryProject, $feature),
                ]);
            });
    }

    /**
     * @param  Collection<int, Worker>  $workers
     * @return Collection<int, Worker>
     */
    public function eligibleWorkers(Collection $workers, string $feature): Collection
    {
        return $workers->filter(fn (Worker $worker) => $this->allows($worker, $feature))->values();
    }

    /**
     * @return array{worker_column: string, project_module: string, module_key: string|null}
     */
    public function definition(string $feature): array
    {
        if (! isset(self::FEATURES[$feature])) {
            throw new InvalidArgumentException("Unsupported worker feature [{$feature}].");
        }

        return self::FEATURES[$feature];
    }
}
