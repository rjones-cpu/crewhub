<?php

namespace App\Http\Controllers;

use App\Services\Dashboard\DashboardService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request, DashboardService $service): Response
    {
        $project = $request->attributes->get('currentProject');

        return Inertia::render('Dashboard/Index', $service->overview($project?->id));
    }
}
