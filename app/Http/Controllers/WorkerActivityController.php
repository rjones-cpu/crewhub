<?php

namespace App\Http\Controllers;

use App\Models\Worker;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WorkerActivityController extends Controller
{
    public function index(Request $request, Worker $worker): Response
    {
        $this->authorize('view', $worker);

        return Inertia::render('Workers/Activity', [
            'worker' => $worker,
            'activities' => $worker->activities()->latest()->paginate($request->integer('per_page', 25)),
        ]);
    }
}
