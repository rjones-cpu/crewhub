<?php

namespace App\Services\MajorProjects;

use App\Enums\InvitationStatus;
use App\Models\CompanyProjectMembership;
use App\Models\MajorProject;
use App\Models\ProjectInvitation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MajorProjectService
{
    public function create(array $data, User $user): MajorProject
    {
        $data['modules'] = array_merge(
            MajorProject::defaultModules(),
            $data['modules'] ?? [],
        );

        $data['created_by'] = $user->id;
        $data['company_id'] = $user->company_id;

        $data['project_number'] = trim((string) ($data['project_number'] ?? ''));
        $data['code'] = trim((string) ($data['code'] ?? '')) ?: $data['project_number'];

        if (empty($data['location']) && ! empty($data['address'])) {
            $data['location'] = $data['address'];
        }

        return DB::transaction(function () use ($data) {
            $project = MajorProject::query()->create($data);

            // Company managers create for their own tenant and join as Owner immediately.
            CompanyProjectMembership::query()->create([
                'company_id' => $project->company_id,
                'major_project_id' => $project->id,
                'role' => 'Owner',
                'status' => 'active',
                'joined_at' => now(),
            ]);

            return $project->fresh(['company', 'manager', 'memberships']);
        });
    }

    public function update(MajorProject $project, array $data): MajorProject
    {
        if (isset($data['modules'])) {
            $data['modules'] = array_merge(
                MajorProject::defaultModules(),
                $data['modules'],
            );
        }

        $project->update($data);

        return $project->refresh();
    }

    /**
     * Invite companies to an existing project.
     *
     * Existing members are left untouched. A previous pending or declined invitation
     * is refreshed instead of inserting a duplicate of the database's unique pair.
     */
    public function inviteCompanies(MajorProject $project, array $companyIds, User $user): int
    {
        $companyIds = collect($companyIds)
            ->map(fn ($companyId) => (int) $companyId)
            ->filter(fn (int $companyId) => $companyId !== (int) $project->company_id)
            ->unique()
            ->values();

        $memberCompanyIds = CompanyProjectMembership::withoutGlobalScopes()
            ->where('major_project_id', $project->id)
            ->where('status', 'active')
            ->whereIn('company_id', $companyIds)
            ->pluck('company_id');

        $companyIds = $companyIds->diff($memberCompanyIds);

        return DB::transaction(function () use ($project, $companyIds, $user): int {
            foreach ($companyIds as $companyId) {
                ProjectInvitation::withoutGlobalScopes()->updateOrCreate(
                    [
                        'major_project_id' => $project->id,
                        'company_id' => $companyId,
                    ],
                    [
                        'invited_by' => $user->id,
                        'role' => 'Contractor',
                        'status' => InvitationStatus::Pending,
                        'invited_at' => now(),
                        'responded_at' => null,
                    ],
                );
            }

            return $companyIds->count();
        });
    }

    public function acceptInvitation(ProjectInvitation $invitation, User $user): MajorProject
    {
        return DB::transaction(function () use ($invitation, $user) {
            $invitation->update([
                'status' => InvitationStatus::Accepted,
                'responded_at' => now(),
            ]);

            CompanyProjectMembership::query()->updateOrCreate(
                [
                    'company_id' => $invitation->company_id,
                    'major_project_id' => $invitation->major_project_id,
                ],
                [
                    'role' => $invitation->role ?: 'Contractor',
                    'status' => 'active',
                    'joined_at' => now(),
                ],
            );

            // Invited companies are not members until this commit finishes; load without visibility scopes.
            return MajorProject::withoutGlobalScopes()->findOrFail($invitation->major_project_id);
        });
    }

    public function declineInvitation(ProjectInvitation $invitation): ProjectInvitation
    {
        $invitation->update([
            'status' => InvitationStatus::Declined,
            'responded_at' => now(),
        ]);

        return $invitation->refresh();
    }

    public function switch(Request $request, MajorProject $project): void
    {
        $request->session()->put('current_project_id', $project->id);
    }

    public function clearSelection(Request $request): void
    {
        $request->session()->forget('current_project_id');
    }

}
