<?php

namespace App\Http\Controllers;

use App\Enums\ModuleAccessStatus;
use App\Enums\ModuleActivationRequestStatus;
use App\Enums\ModuleActivationSource;
use App\Http\Resources\ModuleResource;
use App\Models\Company;
use App\Models\CompanyModule;
use App\Models\Module;
use App\Models\ModuleActivationRequest;
use App\Services\Modules\ModuleAccessService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ModuleController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Module::class);

        $modules = Module::query()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(function (Module $module) {
                $accessRows = CompanyModule::query()
                    ->with('company:id,name,code')
                    ->where('module_id', $module->id)
                    ->get();

                $module->companies_with_access = $accessRows->map(fn (CompanyModule $row) => [
                    'id' => $row->id,
                    'company_id' => $row->company_id,
                    'company_name' => $row->company?->name,
                    'company_code' => $row->company?->code,
                    'status' => $row->status,
                    'activation_source' => $row->activation_source,
                    'activated_at' => $row->activated_at?->toIso8601String(),
                    'is_usable' => $row->isUsable(),
                ])->values();

                $module->pending_requests_count = ModuleActivationRequest::query()
                    ->where('module_id', $module->id)
                    ->where('status', ModuleActivationRequestStatus::Pending)
                    ->count();

                return $module;
            });

        return Inertia::render('Settings/Modules', [
            'modules' => ModuleResource::collection($modules),
            'companies' => Company::query()->orderBy('name')->get(['id', 'name', 'code']),
        ]);
    }

    public function updatePaid(Request $request, Module $module, ModuleAccessService $service): RedirectResponse
    {
        $this->authorize('update', $module);

        $validated = $request->validate([
            'is_paid' => ['required', 'boolean'],
        ]);

        $service->setPaid($module, (bool) $validated['is_paid']);

        return back()->with('success', "{$module->name} marked as ".($validated['is_paid'] ? 'paid' : 'free').'.');
    }

    public function grant(Request $request, Module $module, ModuleAccessService $service): RedirectResponse
    {
        $this->authorize('manageAccess', Module::class);

        $validated = $request->validate([
            'company_id' => ['required', 'integer', 'exists:companies,id'],
            'status' => ['nullable', Rule::enum(ModuleAccessStatus::class)],
        ]);

        $company = Company::query()->findOrFail($validated['company_id']);
        $status = isset($validated['status'])
            ? ModuleAccessStatus::from($validated['status'])
            : ModuleAccessStatus::Active;

        $service->grantAccess(
            $company,
            $module,
            $request->user(),
            ModuleActivationSource::Manual,
            $status,
        );

        return back()->with('success', "Access granted to {$company->name} for {$module->name}.");
    }

    public function revoke(Request $request, Module $module, ModuleAccessService $service): RedirectResponse
    {
        $this->authorize('manageAccess', Module::class);

        $validated = $request->validate([
            'company_id' => ['required', 'integer', 'exists:companies,id'],
        ]);

        $company = Company::query()->findOrFail($validated['company_id']);
        $service->revokeAccess($company, $module, $request->user());

        return back()->with('success', "Access revoked for {$company->name} on {$module->name}.");
    }

    public function approveRequest(
        ModuleActivationRequest $activationRequest,
        ModuleAccessService $service,
    ): RedirectResponse {
        $this->authorize('manageAccess', Module::class);
        $service->approveRequest($activationRequest, request()->user());

        return back()->with('success', 'Activation request approved.');
    }

    public function rejectRequest(
        Request $request,
        ModuleActivationRequest $activationRequest,
        ModuleAccessService $service,
    ): RedirectResponse {
        $this->authorize('manageAccess', Module::class);

        $validated = $request->validate([
            'rejection_reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $service->rejectRequest(
            $activationRequest,
            $request->user(),
            $validated['rejection_reason'] ?? null,
        );

        return back()->with('success', 'Activation request rejected.');
    }
}
