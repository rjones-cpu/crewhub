<?php

namespace App\Http\Controllers;

use App\Enums\DelegationStatus;
use App\Enums\ManagerRelationship;
use App\Models\AssignmentActivity;
use App\Models\MajorProject;
use App\Models\ProjectManagerLink;
use App\Models\ResponsibilityDelegation;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class HierarchyManagerController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $this->authorizeWorkforce($request);

        $data = $request->validate([
            'major_project_id' => ['required', 'integer', 'exists:major_projects,id'],
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'title' => ['nullable', 'string', 'max:120'],
            'relationship' => ['required', Rule::enum(ManagerRelationship::class)],
        ]);

        // Scoped find, so a user can never link managers onto another company's project.
        $project = MajorProject::findOrFail($data['major_project_id']);
        $this->authorize('view', $project);
        $companyId = $request->user()->company_id ?: $project->company_id;

        $exists = ProjectManagerLink::query()
            ->where('major_project_id', $data['major_project_id'])
            ->where('user_id', $data['user_id'])
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'user_id' => 'That manager is already connected to this project.',
            ]);
        }

        $relationship = ManagerRelationship::from($data['relationship']);

        // Only one primary manager per project — demote any existing primary.
        if ($relationship === ManagerRelationship::Primary) {
            ProjectManagerLink::query()
                ->where('major_project_id', $data['major_project_id'])
                ->where('relationship', ManagerRelationship::Primary)
                ->update(['relationship' => ManagerRelationship::Connected]);
        }

        $link = ProjectManagerLink::create([
            'company_id' => $companyId,
            'major_project_id' => $data['major_project_id'],
            'user_id' => $data['user_id'],
            'title' => ($data['title'] ?? null) ?: 'Project Manager',
            'relationship' => $relationship,
            'created_by' => $request->user()->id,
        ]);

        AssignmentActivity::create([
            'company_id' => $companyId,
            'major_project_id' => $link->major_project_id,
            'user_id' => $request->user()->id,
            'action' => "Manager connected ({$relationship->label()})",
            'details' => User::find($data['user_id'])?->name.' added as '.$relationship->label().' manager',
        ]);

        return back()->with('success', 'Manager connected.');
    }

    public function destroy(Request $request, ProjectManagerLink $link): RedirectResponse
    {
        $this->authorizeWorkforce($request);
        $this->authorize('view', $link->majorProject);

        // Delegations point at the link; clear them so nothing references a gone manager.
        ResponsibilityDelegation::query()
            ->where('project_manager_link_id', $link->id)
            ->update([
                'project_manager_link_id' => null,
                'status' => DelegationStatus::NotDelegated,
                'is_delegable' => false,
            ]);

        AssignmentActivity::create([
            'company_id' => $link->company_id,
            'major_project_id' => $link->major_project_id,
            'user_id' => $request->user()->id,
            'action' => 'Manager disconnected',
            'details' => $link->manager?->name.' removed from '.$link->majorProject?->name,
        ]);

        $link->delete();

        return back()->with('success', 'Manager disconnected.');
    }

    private function authorizeWorkforce(Request $request): void
    {
        abort_unless($request->user()?->role?->canManageWorkforce(), 403);
    }
}
