<?php

namespace App\Http\Controllers\Auth;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\Timesheets\CampTimesheetSyncService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => session('status'),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     *
     * Company administrators immediately pull their workers and reservation-backed
     * draft timesheets from reservations_staging so the dashboard is populated
     * on first landing. Sync failures never block login.
     */
    public function store(LoginRequest $request, CampTimesheetSyncService $campSync): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        $this->syncCompanyReservations($request, $campSync);

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }

    protected function syncCompanyReservations(Request $request, CampTimesheetSyncService $campSync): void
    {
        $user = $request->user();

        if (! $user || $user->role !== Role::CompanyAdmin || ! $user->company_id) {
            return;
        }

        try {
            $result = $campSync->syncForUser($user);

            if ($result['errors'] !== []) {
                $request->session()->flash(
                    'error',
                    'Signed in, but Camp sync failed: '.implode(' ', $result['errors']),
                );

                return;
            }

            $request->session()->flash(
                'success',
                sprintf(
                    'Camp sync complete: %d worker(s) created, %d matched, %d draft timesheet(s) created, %d updated.',
                    $result['workers_created'],
                    $result['workers_matched'],
                    $result['timesheets_created'],
                    $result['timesheets_updated'],
                ),
            );
        } catch (Throwable $exception) {
            report($exception);

            $request->session()->flash(
                'error',
                'Signed in, but Camp sync could not reach reservations_staging.',
            );
        }
    }
}
