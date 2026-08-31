<?php

namespace App\Http\Controllers;

use App\Services\Readiness\ReadinessOverviewService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReadinessController extends Controller
{
    public function index(Request $request, ReadinessOverviewService $service): Response
    {
        $project = $request->attributes->get('currentProject');

        return Inertia::render('Readiness/Index', [
            ...$service->overview(
                $project?->id,
                $request->integer('attention_page', 1),
            ),
            'filters' => $request->only('status'),
        ]);
    }
}
