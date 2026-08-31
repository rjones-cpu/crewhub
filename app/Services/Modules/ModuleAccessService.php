<?php

namespace App\Services\Modules;

use App\Enums\ModuleAccessStatus;
use App\Enums\ModuleActivationRequestStatus;
use App\Enums\ModuleActivationSource;
use App\Models\Company;
use App\Models\CompanyModule;
use App\Models\Module;
use App\Models\ModuleActivationRequest;
use App\Models\Notification;
use App\Models\User;
use App\Services\Notifications\NotificationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ModuleAccessService
{
    public function __construct(
        private readonly NotificationService $notifications,
    ) {}

    public function findByKey(string $key): ?Module
    {
        return Module::query()->where('key', $key)->where('is_active', true)->first();
    }

    public function companyHasActiveAccess(?int $companyId, string $moduleKey): bool
    {
        if (! $companyId) {
            return false;
        }

        $module = $this->findByKey($moduleKey);

        if (! $module) {
            return false;
        }

        // Free modules are available to every company without an entitlement row.
        if (! $module->is_paid) {
            return true;
        }

        $entitlement = CompanyModule::query()
            ->where('company_id', $companyId)
            ->where('module_id', $module->id)
            ->first();

        return $entitlement?->isUsable() ?? false;
    }

    public function pendingRequestFor(int $companyId, int $moduleId): ?ModuleActivationRequest
    {
        return ModuleActivationRequest::query()
            ->where('company_id', $companyId)
            ->where('module_id', $moduleId)
            ->where('status', ModuleActivationRequestStatus::Pending)
            ->first();
    }

    public function requestActivation(User $user, Module $module): ModuleActivationRequest
    {
        if (! $user->company_id) {
            throw ValidationException::withMessages([
                'module' => 'Only organization users can request module activation.',
            ]);
        }

        if (! $module->is_paid) {
            throw ValidationException::withMessages([
                'module' => 'This module does not require activation.',
            ]);
        }

        if ($this->companyHasActiveAccess($user->company_id, $module->key)) {
            throw ValidationException::withMessages([
                'module' => 'Your organization already has access to this module.',
            ]);
        }

        if ($existing = $this->pendingRequestFor($user->company_id, $module->id)) {
            throw ValidationException::withMessages([
                'module' => 'Your activation request is already pending.',
            ]);
        }

        $company = Company::query()->findOrFail($user->company_id);

        return DB::transaction(function () use ($user, $module, $company) {
            $request = ModuleActivationRequest::query()->create([
                'company_id' => $company->id,
                'module_id' => $module->id,
                'requested_by' => $user->id,
                'status' => ModuleActivationRequestStatus::Pending,
            ]);

            $this->notifications->notifySuperAdmins(
                type: 'module_activation_request',
                title: 'Module activation requested',
                message: "{$company->name} requested activation of the {$module->name} module.",
                data: [
                    'request_id' => $request->id,
                    'company_id' => $company->id,
                    'company_name' => $company->name,
                    'module_id' => $module->id,
                    'module_key' => $module->key,
                    'module_name' => $module->name,
                    'requested_by_id' => $user->id,
                    'requested_by_name' => $user->name,
                    'requested_at' => now()->toIso8601String(),
                    'action_url' => route('notifications.index'),
                ],
            );

            return $request;
        });
    }

    public function grantAccess(
        Company $company,
        Module $module,
        User $actor,
        ModuleActivationSource $source = ModuleActivationSource::Manual,
        ?ModuleAccessStatus $status = null,
    ): CompanyModule {
        return CompanyModule::query()->updateOrCreate(
            [
                'company_id' => $company->id,
                'module_id' => $module->id,
            ],
            [
                'status' => $status ?? ModuleAccessStatus::Active,
                'activation_source' => $source,
                'activated_by' => $actor->id,
                'activated_at' => now(),
                'expires_at' => null,
            ],
        );
    }

    public function revokeAccess(Company $company, Module $module, User $actor): CompanyModule
    {
        return CompanyModule::query()->updateOrCreate(
            [
                'company_id' => $company->id,
                'module_id' => $module->id,
            ],
            [
                'status' => ModuleAccessStatus::Inactive,
                'activation_source' => ModuleActivationSource::Manual,
                'activated_by' => $actor->id,
                'activated_at' => now(),
            ],
        );
    }

    public function approveRequest(ModuleActivationRequest $request, User $reviewer): ModuleActivationRequest
    {
        if ($request->status !== ModuleActivationRequestStatus::Pending) {
            throw ValidationException::withMessages([
                'request' => 'This activation request has already been processed.',
            ]);
        }

        return DB::transaction(function () use ($request, $reviewer) {
            $request->loadMissing(['company', 'module']);

            $this->grantAccess(
                $request->company,
                $request->module,
                $reviewer,
                ModuleActivationSource::Manual,
            );

            $request->update([
                'status' => ModuleActivationRequestStatus::Approved,
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
                'rejection_reason' => null,
            ]);
            $this->markRequestNotificationsRead($request);

            return $request->fresh(['company', 'module', 'requester', 'reviewer']);
        });
    }

    public function rejectRequest(
        ModuleActivationRequest $request,
        User $reviewer,
        ?string $reason = null,
    ): ModuleActivationRequest {
        if ($request->status !== ModuleActivationRequestStatus::Pending) {
            throw ValidationException::withMessages([
                'request' => 'This activation request has already been processed.',
            ]);
        }

        $request->update([
            'status' => ModuleActivationRequestStatus::Rejected,
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
            'rejection_reason' => $reason,
        ]);
        $this->markRequestNotificationsRead($request);

        return $request->fresh(['company', 'module', 'requester', 'reviewer']);
    }

    private function markRequestNotificationsRead(ModuleActivationRequest $request): void
    {
        Notification::withoutGlobalScopes()
            ->where('type', 'module_activation_request')
            ->where('data->request_id', $request->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    public function setPaid(Module $module, bool $isPaid): Module
    {
        $module->update(['is_paid' => $isPaid]);

        return $module->refresh();
    }
}
