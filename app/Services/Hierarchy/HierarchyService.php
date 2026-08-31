<?php

namespace App\Services\Hierarchy;

use App\Enums\DelegationStatus;
use App\Enums\ManagerRelationship;
use App\Enums\Role;
use App\Models\AssignmentActivity;
use App\Models\Company;
use App\Models\MajorProject;
use App\Models\ProjectManagerLink;
use App\Models\ResponsibilityDelegation;
use App\Models\User;
use App\Models\Worker;

class HierarchyService
{
    /** Responsibility areas a Crew Hub can delegate to a connected manager. */
    public const AREAS = [
        'Time Sheets',
        'Equipment & Materials',
        'Safety & Compliance',
        'Training',
        'Journey Management',
    ];

    public function overview(?MajorProject $project, User $user): array
    {
        // The company-level node is the contractor Crew Hub connected to the project.
        // A Super Admin has no contractor company of their own, so fall back to the
        // project owner instead of showing the platform "Crew Hub" account.
        $company = $this->resolveCompany($project, $user);
        $contact = $this->companyContact($company);

        if (! $project) {
            return [
                'project' => null,
                'company' => $this->companyPayload($company),
                'contact' => $this->contactPayload($contact),
                'managers' => [],
                'delegations' => [],
                'accountability' => [],
                'activity' => [],
                'approvalPath' => [],
                'availableManagers' => [],
                'workforceCount' => 0,
            ];
        }

        $links = ProjectManagerLink::query()
            ->with('manager')
            ->where('major_project_id', $project->id)
            ->orderByRaw("CASE relationship WHEN 'primary' THEN 1 ELSE 2 END")
            ->orderBy('id')
            ->get();

        $delegations = $this->delegations($project, $links);
        $workforceCount = Worker::query()->where('primary_project_id', $project->id)->count();

        return [
            'project' => [
                'id' => $project->id,
                'name' => $project->name,
                'code' => $project->code,
                'location' => $project->location,
            ],
            'company' => $this->companyPayload($company),
            'contact' => $this->contactPayload($contact),
            'managers' => $links->map(fn (ProjectManagerLink $link) => [
                'id' => $link->id,
                'user_id' => $link->user_id,
                'name' => $link->manager?->name ?? 'Unknown',
                'title' => $link->title ?: 'Project Manager',
                'relationship' => $link->relationship->value,
                'relationship_label' => $link->relationship->label(),
            ])->values(),
            'delegations' => $delegations,
            'accountability' => $this->accountability($company, $delegations, $workforceCount),
            'activity' => AssignmentActivity::query()
                ->with('actor')
                ->where('major_project_id', $project->id)
                ->latest()
                ->limit(6)
                ->get()
                ->map(fn (AssignmentActivity $entry) => [
                    'id' => $entry->id,
                    'actor' => $entry->actor?->name ?? $entry->actor_name ?? 'Crew Hub',
                    'action' => $entry->action,
                    'details' => $entry->details,
                    'occurred_at' => $entry->created_at?->format('M j, Y g:i A'),
                ])
                ->values(),
            'approvalPath' => $this->approvalPath($company, $project, $links),
            'availableManagers' => User::query()
                ->where('company_id', $user->company_id)
                ->whereNotIn('id', $links->pluck('user_id'))
                ->orderBy('name')
                ->get(['id', 'name', 'role'])
                ->map(fn (User $candidate) => [
                    'id' => $candidate->id,
                    'name' => $candidate->name,
                    'role' => $candidate->role?->value,
                ])
                ->values(),
            'workforceCount' => $workforceCount,
        ];
    }

    /**
     * The contractor company shown at the "Crew Hub / company level" node.
     * Company users see their own company; a Super Admin (no contractor company)
     * sees the project owner instead of the platform account.
     */
    private function resolveCompany(?MajorProject $project, User $user): ?Company
    {
        if ($user->role !== Role::SuperAdmin && $user->company) {
            return $user->company;
        }

        return $project?->company;
    }

    /** The company-level manager (a real person), not the viewing Super Admin. */
    private function companyContact(?Company $company): ?User
    {
        if (! $company) {
            return null;
        }

        return User::query()
            ->where('company_id', $company->id)
            ->where('is_active', true)
            ->orderByRaw("CASE role WHEN 'company_admin' THEN 1 WHEN 'workforce_manager' THEN 2 ELSE 3 END")
            ->orderBy('name')
            ->first();
    }

    private function companyPayload(?Company $company): array
    {
        return [
            'id' => $company?->id,
            'name' => $company?->name ?? 'Crew Hub',
            'industry' => $company?->industry,
        ];
    }

    private function contactPayload(?User $contact): ?array
    {
        if (! $contact) {
            return null;
        }

        return [
            'name' => $contact->name,
            'role' => $contact->role?->value,
        ];
    }

    /**
     * Merges stored delegations with the full area list so every responsibility
     * area renders, even when it has never been delegated.
     */
    private function delegations(MajorProject $project, $links): array
    {
        $stored = ResponsibilityDelegation::query()
            ->with('managerLink.manager')
            ->where('major_project_id', $project->id)
            ->get()
            ->keyBy('area');

        return collect(self::AREAS)->map(function (string $area) use ($stored) {
            $delegation = $stored->get($area);
            $link = $delegation?->managerLink;
            $status = $delegation?->status ?? DelegationStatus::NotDelegated;

            return [
                'id' => $delegation?->id,
                'area' => $area,
                'manager_name' => $link?->manager?->name,
                'manager_relationship' => $link?->relationship->label(),
                'status' => $status->value,
                'status_label' => $status->label(),
                'is_delegable' => (bool) $delegation?->is_delegable,
            ];
        })->values()->all();
    }

    private function accountability(?Company $company, array $delegations, int $workforceCount): array
    {
        $accepted = collect($delegations)
            ->filter(fn (array $row) => $row['status'] === DelegationStatus::Accepted->value)
            ->pluck('area')
            ->all();

        return [[
            'company' => $company?->name ?? 'Crew Hub',
            'workers' => $workforceCount,
            'areas' => collect(self::AREAS)
                ->mapWithKeys(fn (string $area) => [$area => in_array($area, $accepted, true)])
                ->all(),
            'status' => count($accepted) === count(self::AREAS) ? 'Accepted' : 'Partial',
        ]];
    }

    private function approvalPath(?Company $company, MajorProject $project, $links): array
    {
        $companyName = $company?->name ?? 'Crew Hub';
        $primary = $links->firstWhere('relationship', ManagerRelationship::Primary);

        return [
            ['step' => 1, 'title' => 'Worker submits timesheet', 'subtitle' => 'To Crew Hub'],
            ['step' => 2, 'title' => "{$companyName} approval", 'subtitle' => 'Company manager'],
            ['step' => 3, 'title' => "{$project->name} approval", 'subtitle' => $primary?->manager?->name ?? 'Connected manager'],
            ['step' => 4, 'title' => 'Payroll audit if required', 'subtitle' => $companyName],
        ];
    }
}
