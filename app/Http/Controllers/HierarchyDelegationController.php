<?php

namespace App\Http\Controllers;

use App\Enums\DelegationStatus;
use App\Models\AssignmentActivity;
use App\Models\MajorProject;
use App\Models\ProjectManagerLink;
use App\Models\ResponsibilityDelegation;
use App\Services\Hierarchy\HierarchyService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class HierarchyDelegationController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->role?->canManageWorkforce(), 403);

        $data = $request->validate([
            'major_project_id' => ['required', 'integer', 'exists:major_projects,id'],
            'area' => ['required', 'string', Rule::in(HierarchyService::AREAS)],
            'is_delegable' => ['required', 'boolean'],
        ]);

        // Scoped find, so a user can never delegate on another company's project.
        $project = MajorProject::findOrFail($data['major_project_id']);
        $this->authorize('view', $project);
        $companyId = $request->user()->company_id ?: $project->company_id;

        $delegation = ResponsibilityDelegation::query()
            ->where('major_project_id', $data['major_project_id'])
            ->where('area', $data['area'])
            ->first();

        // Turning a responsibility on falls back to the primary manager when the
        // area has never been delegated before.
        $link = $delegation?->managerLink ?? ProjectManagerLink::query()
            ->where('major_project_id', $data['major_project_id'])
            ->orderByRaw("CASE relationship WHEN 'primary' THEN 1 ELSE 2 END")
            ->first();

        $attributes = [
            'is_delegable' => $data['is_delegable'],
            'status' => $data['is_delegable'] ? DelegationStatus::Accepted : DelegationStatus::NotDelegated,
            'project_manager_link_id' => $data['is_delegable'] ? $link?->id : null,
        ];

        if ($delegation) {
            $delegation->update($attributes);
        } else {
            $delegation = ResponsibilityDelegation::create([
                ...$attributes,
                'company_id' => $companyId,
                'major_project_id' => $data['major_project_id'],
                'area' => $data['area'],
            ]);
        }

        AssignmentActivity::create([
            'company_id' => $companyId,
            'major_project_id' => $data['major_project_id'],
            'user_id' => $request->user()->id,
            'action' => $data['is_delegable'] ? 'Responsibility delegated' : 'Responsibility withdrawn',
            'details' => $data['area'].($link?->manager?->name ? ' · '.$link->manager->name : ''),
        ]);

        return back()->with('success', 'Delegation updated.');
    }
}
