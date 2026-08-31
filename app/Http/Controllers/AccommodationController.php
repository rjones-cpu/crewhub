<?php

namespace App\Http\Controllers;

use App\Models\Accommodation;
use App\Models\AccommodationAssignment;
use App\Models\CompanyProjectMembership;
use App\Models\MajorProject;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AccommodationController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Accommodation::class);

        $project = $request->attributes->get('currentProject')
            ?? MajorProject::query()->orderBy('name')->first();

        $accommodations = Accommodation::query()
            ->with('majorProject')
            ->withCount('assignments')
            ->when($project, fn ($query) => $query->where('major_project_id', $project->id))
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        $overview = Accommodation::query()
            ->when($project, fn ($query) => $query->where('major_project_id', $project->id))
            ->selectRaw('COALESCE(SUM(capacity), 0) as total_capacity')
            ->selectRaw('COALESCE(SUM(occupied), 0) as rooms_used')
            ->first();

        $upcomingArrivals = AccommodationAssignment::query()
            ->when($project, fn ($query) => $query->whereHas(
                'accommodation',
                fn ($accommodationQuery) => $accommodationQuery->where('major_project_id', $project->id),
            ))
            ->whereBetween('check_in', [now()->toDateString(), now()->addDays(7)->toDateString()])
            ->whereNotIn('status', ['cancelled', 'checked_out'])
            ->count();

        $membership = $project
            ? CompanyProjectMembership::query()
                ->where('major_project_id', $project->id)
                ->where('company_id', $request->user()->company_id)
                ->first()
            : null;

        return Inertia::render('Accommodations/Index', [
            'accommodations' => $accommodations,
            'linkedProject' => $project ? [
                'id' => $project->id,
                'name' => $project->name,
                'code' => $project->code,
                'location' => $project->location,
                'icon' => $project->icon,
                'status' => $project->status->value ?? $project->status,
                'company_name' => $project->company?->name ?? $request->user()->company?->name,
                'joined_at' => $membership?->joined_at?->toDateString(),
            ] : null,
            'overview' => [
                'primary_lodge' => $accommodations->getCollection()->first()?->name,
                'facility_count' => $accommodations->total(),
                'total_rooms' => (int) ($overview?->total_capacity ?? 0),
                'rooms_used' => (int) ($overview?->rooms_used ?? 0),
                'upcoming_arrivals' => $upcomingArrivals,
            ],
        ]);
    }

    public function show(Accommodation $accommodation): Response
    {
        $this->authorize('view', $accommodation);
        return Inertia::render('Accommodations/Show', ['accommodation' => $accommodation->load('assignments.worker')]);
    }
}
