<?php

namespace App\Http\Middleware;

use App\Models\MajorProject;
use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class SetCurrentProject
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->filled('project_id')) {
            $request->session()->put('current_project_id', $request->integer('project_id'));
        }

        $projectId = $request->session()->get('current_project_id');
        $project = $projectId ? MajorProject::query()->find($projectId) : null;

        if ($projectId && ! $project) {
            $request->session()->forget('current_project_id');
        }

        $request->attributes->set('currentProject', $project);
        Inertia::share('currentProject', fn () => $project);

        return $next($request);
    }
}
