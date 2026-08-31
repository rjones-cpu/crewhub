<?php

namespace App\Http\Controllers;

use App\Models\MajorProject;
use App\Services\Hierarchy\HierarchyService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HierarchyController extends Controller
{
    public function __invoke(Request $request, HierarchyService $service): Response
    {
        $project = $request->attributes->get('currentProject') ?? $this->defaultProject($request);

        return Inertia::render('Hierarchy/Index', $service->overview($project, $request->user()));
    }

    /**
     * A hierarchy is always per project, so land on the first project instead of an
     * empty page. The choice is stored in the session and re-shared so the project
     * tabs and the sidebar selector agree with what is rendered.
     */
    private function defaultProject(Request $request): ?MajorProject
    {
        $project = MajorProject::query()
            ->where('status', 'active')
            ->orderBy('name')
            ->first();

        if (! $project) {
            return null;
        }

        $request->session()->put('current_project_id', $project->id);
        $request->attributes->set('currentProject', $project);
        Inertia::share('currentProject', fn () => $project);

        return $project;
    }
}
