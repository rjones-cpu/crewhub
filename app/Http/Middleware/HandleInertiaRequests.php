<?php

namespace App\Http\Middleware;

use App\Models\MajorProject;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),
            'auth' => [
                'user' => fn () => $user?->loadMissing('company'),
            ],
            'company' => fn () => $user?->company,
            'majorProjects' => fn () => $user ? MajorProject::query()->where('status', 'active')->orderBy('name')->get() : [],
            'currentProject' => fn () => $request->attributes->get('currentProject'),
            'notificationsCount' => fn () => $user?->notifications()->whereNull('read_at')->count() ?? 0,
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
        ];
    }
}
