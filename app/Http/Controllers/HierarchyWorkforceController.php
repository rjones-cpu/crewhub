<?php

namespace App\Http\Controllers;

use App\Models\AccommodationAssignment;
use App\Models\MajorProject;
use App\Models\ProjectManagerLink;
use App\Models\Worker;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HierarchyWorkforceController extends Controller
{
    /** Feeds the Crew Hub Workforce modal on the hierarchy page. */
    public function __invoke(Request $request): JsonResponse
    {
        $data = $request->validate([
            'project_id' => ['required', 'integer', 'exists:major_projects,id'],
            'search' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', 'string', 'max:40'],
        ]);

        $project = MajorProject::findOrFail($data['project_id']);
        $this->authorize('view', $project);

        $reportsTo = ProjectManagerLink::query()
            ->with('manager')
            ->where('major_project_id', $project->id)
            ->orderByRaw("CASE relationship WHEN 'primary' THEN 1 ELSE 2 END")
            ->first();

        $workers = Worker::query()
            ->with('latestAccommodation.accommodation')
            ->where('primary_project_id', $project->id)
            ->filter(['search' => $data['search'] ?? null])
            ->when(
                $data['status'] ?? null,
                fn ($query, string $status) => $query->whereHas(
                    'accommodationAssignments',
                    fn ($assignment) => $assignment->where('status', $status)
                )
            )
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->paginate(10)
            ->withQueryString();

        return response()->json([
            'data' => collect($workers->items())->map(function (Worker $worker) use ($reportsTo) {
                $stay = $worker->latestAccommodation;

                return [
                    'id' => $worker->id,
                    'name' => $worker->full_name,
                    'employee_id' => $worker->employee_id,
                    'position' => $worker->position,
                    // Derived from the accommodation assignment; there is no separate booking record.
                    'booking_code' => $stay ? 'BK-'.str_pad((string) $stay->id, 5, '0', STR_PAD_LEFT) : null,
                    'arrival' => $stay?->check_in?->format('M j, Y'),
                    'departure' => $stay?->check_out?->format('M j, Y'),
                    'reservation_status' => $stay?->status,
                    'accommodation' => $stay?->accommodation?->name,
                    'manager' => $reportsTo?->manager?->name,
                ];
            }),
            'meta' => [
                'current_page' => $workers->currentPage(),
                'last_page' => $workers->lastPage(),
                'from' => $workers->firstItem(),
                'to' => $workers->lastItem(),
                'total' => $workers->total(),
            ],
            'statuses' => AccommodationAssignment::query()
                ->whereHas('worker', fn ($query) => $query->where('primary_project_id', $project->id))
                ->distinct()
                ->orderBy('status')
                ->pluck('status'),
        ]);
    }
}
