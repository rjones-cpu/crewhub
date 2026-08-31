<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Http\Requests\StoreMajorProjectRequest;
use App\Http\Requests\StoreProjectInvitationsRequest;
use App\Http\Requests\UpdateMajorProjectRequest;
use App\Http\Resources\MajorProjectResource;
use App\Http\Resources\ProjectInvitationResource;
use App\Models\Company;
use App\Models\MajorProject;
use App\Models\ProjectInvitation;
use App\Models\User;
use App\Services\MajorProjects\MajorProjectService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MajorProjectController extends Controller
{
    /**
     * Page sizes offered by the Current Projects listing.
     *
     * @var list<int>
     */
    private const PER_PAGE_OPTIONS = [10, 25, 50, 100];

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', MajorProject::class);

        $user = $request->user();
        $search = trim((string) $request->query('search', ''));
        $status = $request->query('status');
        $clientId = $request->query('client_id');
        $direction = $request->query('direction') === 'desc' ? 'desc' : 'asc';
        $perPage = (int) $request->query('per_page', self::PER_PAGE_OPTIONS[0]);

        if (! in_array($perPage, self::PER_PAGE_OPTIONS, true)) {
            $perPage = self::PER_PAGE_OPTIONS[0];
        }

        $query = MajorProject::query()
            ->with(['company', 'manager'])
            ->withCount('workers')
            ->orderBy('name', $direction);

        if ($search !== '') {
            $query->where(function ($q) use ($search): void {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('project_number', 'like', "%{$search}%");
            });
        }

        if ($status) {
            $query->where('status', $status);
        }

        if ($clientId && $user->isSuperAdmin()) {
            $query->where('company_id', $clientId);
        }

        $projects = $query->paginate($perPage)->withQueryString();

        // Attach the viewing company's membership role when available.
        if ($user->company_id) {
            $projectIds = $projects->getCollection()->pluck('id');
            $roles = \App\Models\CompanyProjectMembership::query()
                ->where('company_id', $user->company_id)
                ->whereIn('major_project_id', $projectIds)
                ->pluck('role', 'major_project_id');

            $projects->getCollection()->transform(function (MajorProject $project) use ($roles) {
                $project->membership_role = $roles[$project->id] ?? null;

                return $project;
            });
        }

        return Inertia::render('MajorProjects/Index', [
            'projects' => MajorProjectResource::collection($projects),
            'filters' => [
                'search' => $search,
                'status' => $status,
                'client_id' => $clientId,
                'direction' => $direction,
                'per_page' => $perPage,
            ],
            'clients' => $user->isSuperAdmin()
                ? Company::query()->orderBy('name')->get(['id', 'name', 'code'])
                : [],
            'canCreate' => $user->can('create', MajorProject::class),
            'canAttemptCreate' => $user->can('attemptCreate', MajorProject::class),
            'canJoin' => $user->can('viewAny', ProjectInvitation::class),
            'isSuperAdmin' => $user->isSuperAdmin(),
            'hasMajorProjectsModule' => $user->isSuperAdmin()
                || app(\App\Services\Modules\ModuleAccessService::class)
                    ->companyHasActiveAccess($user->company_id, \App\Models\Module::KEY_MAJOR_PROJECTS),
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('attemptCreate', MajorProject::class);

        $user = $request->user();
        $moduleService = app(\App\Services\Modules\ModuleAccessService::class);
        $module = $moduleService->findByKey(\App\Models\Module::KEY_MAJOR_PROJECTS);
        $hasAccess = $user->can('create', MajorProject::class);
        $pendingRequest = ($user->company_id && $module)
            ? $moduleService->pendingRequestFor($user->company_id, $module->id)
            : null;

        return Inertia::render('MajorProjects/Create', [
            'companies' => Company::query()
                ->whereKey($user->company_id)
                ->get(['id', 'name', 'code']),
            'managers' => User::query()
                ->where('company_id', $user->company_id)
                ->whereIn('role', [
                    Role::CompanyAdmin->value,
                    Role::WorkforceManager->value,
                ])
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'email', 'company_id']),
            'defaultModules' => MajorProject::defaultModules(),
            'canCreate' => $hasAccess,
            'canAttemptCreate' => true,
            'canJoin' => $user->can('viewAny', ProjectInvitation::class),
            'hasMajorProjectsModule' => $hasAccess,
            'module' => $module ? [
                'id' => $module->id,
                'key' => $module->key,
                'name' => $module->name,
            ] : null,
            'pendingActivationRequest' => $pendingRequest ? [
                'id' => $pendingRequest->id,
                'status' => $pendingRequest->status,
                'created_at' => $pendingRequest->created_at?->toIso8601String(),
            ] : null,
            'organizationName' => $user->company?->name,
        ]);
    }

    public function store(StoreMajorProjectRequest $request, MajorProjectService $service): RedirectResponse
    {
        $service->create($request->validated(), $request->user());

        return to_route('major-projects.index')->with('success', 'Project created.');
    }

    public function join(Request $request): Response
    {
        $this->authorize('viewAny', ProjectInvitation::class);

        $search = trim((string) $request->query('search', ''));
        $status = $request->query('status', 'all');
        $companyId = $request->query('company_id');
        $invitedOn = $request->query('invited_on');
        $sort = $request->query('sort') === 'oldest' ? 'oldest' : 'newest';
        $perPage = (int) $request->query('per_page', self::PER_PAGE_OPTIONS[0]);

        if (! in_array($perPage, self::PER_PAGE_OPTIONS, true)) {
            $perPage = self::PER_PAGE_OPTIONS[0];
        }

        $query = ProjectInvitation::query()
            ->with([
                'inviter',
                'majorProject' => fn ($q) => $q->with(['company', 'manager'])->withCount('workers'),
            ])
            ->orderBy('invited_at', $sort === 'oldest' ? 'asc' : 'desc');

        if ($status && $status !== 'all') {
            $query->where('status', $status);
        }

        if ($companyId) {
            $query->whereHas('majorProject', function ($q) use ($companyId): void {
                $q->where('company_id', $companyId);
            });
        }

        if ($invitedOn) {
            $query->whereDate('invited_at', $invitedOn);
        }

        if ($search !== '') {
            $query->whereHas('majorProject', function ($q) use ($search): void {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('project_number', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        $invitations = $query->paginate($perPage)->withQueryString();

        $ownerCompanies = Company::query()
            ->whereIn('id', function ($sub) use ($request): void {
                $sub->select('major_projects.company_id')
                    ->from('project_invitations')
                    ->join('major_projects', 'major_projects.id', '=', 'project_invitations.major_project_id')
                    ->where('project_invitations.company_id', $request->user()->company_id)
                    ->whereNull('major_projects.deleted_at');
            })
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('MajorProjects/Join', [
            'invitations' => ProjectInvitationResource::collection($invitations),
            'filters' => [
                'search' => $search,
                'status' => $status,
                'company_id' => $companyId,
                'invited_on' => $invitedOn,
                'sort' => $sort,
                'per_page' => $perPage,
            ],
            'companies' => $ownerCompanies,
            'canCreate' => $request->user()->can('create', MajorProject::class),
            'canAttemptCreate' => $request->user()->can('attemptCreate', MajorProject::class),
            'canJoin' => true,
        ]);
    }

    public function acceptInvitation(
        ProjectInvitation $invitation,
        MajorProjectService $service,
    ): RedirectResponse {
        $this->authorize('respond', $invitation);
        $project = $service->acceptInvitation($invitation, request()->user());

        return to_route('major-projects.index')
            ->with('success', "Joined {$project->name}.");
    }

    public function declineInvitation(
        ProjectInvitation $invitation,
        MajorProjectService $service,
    ): RedirectResponse {
        $this->authorize('respond', $invitation);
        $service->declineInvitation($invitation);

        return back()->with('success', 'Invitation declined.');
    }

    public function storeInvitations(
        StoreProjectInvitationsRequest $request,
        MajorProject $majorProject,
        MajorProjectService $service,
    ): RedirectResponse {
        $count = $service->inviteCompanies(
            $majorProject,
            $request->validated('company_ids'),
            $request->user(),
        );

        $message = $count === 1
            ? 'Company invitation sent.'
            : "{$count} company invitations sent.";

        return back()->with('success', $message);
    }

    public function show(MajorProject $majorProject): Response
    {
        $this->authorize('view', $majorProject);

        return Inertia::render('MajorProjects/Show', [
            'project' => new MajorProjectResource(
                $majorProject->load(['company', 'manager'])->loadCount('workers'),
            ),
        ]);
    }

    public function edit(MajorProject $majorProject): Response
    {
        $this->authorize('update', $majorProject);

        return Inertia::render('MajorProjects/Edit', [
            'project' => new MajorProjectResource($majorProject->load(['company', 'manager'])),
        ]);
    }

    public function update(
        UpdateMajorProjectRequest $request,
        MajorProject $majorProject,
        MajorProjectService $service,
    ): RedirectResponse {
        $service->update($majorProject, $request->validated());

        return back()->with('success', 'Project updated.');
    }

    public function destroy(MajorProject $majorProject): RedirectResponse
    {
        $this->authorize('delete', $majorProject);
        $majorProject->delete();

        return to_route('major-projects.index')->with('success', 'Project archived.');
    }

    public function switch(
        Request $request,
        MajorProject $majorProject,
        MajorProjectService $service,
    ): RedirectResponse {
        $this->authorize('view', $majorProject);
        $service->switch($request, $majorProject);

        return back()->with('success', "Switched to {$majorProject->name}.");
    }

    public function clearSelection(Request $request, MajorProjectService $service): RedirectResponse
    {
        $service->clearSelection($request);

        return back()->with('success', 'Showing all projects.');
    }
}
