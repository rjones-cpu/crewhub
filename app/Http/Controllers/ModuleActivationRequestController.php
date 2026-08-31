<?php

namespace App\Http\Controllers;

use App\Models\Module;
use App\Services\Modules\ModuleAccessService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ModuleActivationRequestController extends Controller
{
    public function store(Request $request, Module $module, ModuleAccessService $service): RedirectResponse
    {
        $this->authorize('requestActivation', $module);

        $service->requestActivation($request->user(), $module);

        return back()->with('success', 'Your activation request has been sent to the Super Admin.');
    }
}
