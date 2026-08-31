<?php

namespace App\Http\Controllers;

use App\Models\Worker;
use App\Services\Readiness\ReadinessCalculationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ReadinessCheckController extends Controller
{
    public function store(Request $request, ReadinessCalculationService $service): RedirectResponse
    {
        $query = Worker::query()->when($request->integer('worker_id'), fn ($query, $id) => $query->whereKey($id));
        $query->each(fn (Worker $worker) => $service->calculate($worker));

        return back()->with('success', 'Readiness checks completed.');
    }
}
