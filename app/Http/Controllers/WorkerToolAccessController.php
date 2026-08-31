<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateWorkerToolsRequest;
use App\Models\Worker;
use App\Services\Workers\WorkerFeatureAccessService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class WorkerToolAccessController extends Controller
{
    public function __construct(private readonly WorkerFeatureAccessService $featureAccess)
    {
    }

    public function update(UpdateWorkerToolsRequest $request, Worker $worker): RedirectResponse
    {
        $attributes = $request->validated();
        $worker->loadMissing('primaryProject');

        foreach (WorkerFeatureAccessService::FEATURES as $feature => $definition) {
            $column = $definition['worker_column'];

            if (! ($attributes[$column] ?? false)) {
                continue;
            }

            if (! $this->featureAccess->companyModuleAllows($request->user()?->company_id, $feature)) {
                throw ValidationException::withMessages([
                    $column => 'This module is not active for your organization.',
                ]);
            }

            if (! $this->featureAccess->projectAllows($worker->primaryProject, $feature)) {
                throw ValidationException::withMessages([
                    $column => 'This feature is disabled for the worker’s primary project.',
                ]);
            }
        }

        $worker->update($attributes);

        return back()->with('success', 'Tool access updated.');
    }

    public function updateCompany(Request $request, string $feature): RedirectResponse
    {
        $this->authorize('create', Worker::class);

        $attributes = $request->validate([
            'enabled' => ['required', 'boolean'],
        ]);

        // Turning a feature off is always allowed; only enabling needs the module.
        if ($attributes['enabled'] && ! $this->featureAccess->companyModuleAllows($request->user()?->company_id, $feature)) {
            throw ValidationException::withMessages([
                'enabled' => 'This module is not active for your organization. Request activation first.',
            ]);
        }

        $this->featureAccess->setForCompany($feature, $attributes['enabled']);

        return back()->with(
            'success',
            $attributes['enabled']
                ? 'Worker feature enabled where allowed by project settings.'
                : 'Worker feature disabled for all company workers.',
        );
    }
}
