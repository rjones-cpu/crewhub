<?php

namespace App\Http\Controllers;

use App\Enums\JourneyStatus;
use App\Enums\WorkerStatus;
use App\Http\Requests\StoreJourneyRequest;
use App\Http\Requests\UpdateJourneyStatusRequest;
use App\Http\Resources\JourneyResource;
use App\Models\Journey;
use App\Models\Worker;
use App\Services\Journeys\JourneyService;
use App\Services\Workers\WorkerFeatureAccessService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class JourneyController extends Controller
{
    public function __construct(
        private JourneyService $journeys,
        private WorkerFeatureAccessService $featureAccess,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Journey::class);

        $filters = $request->only(['search', 'status', 'risk', 'from', 'to', 'per_page']);
        $user = $request->user();

        return Inertia::render('Journeys/Index', [
            'journeys' => JourneyResource::collection($this->journeys->paginate($filters)),
            'stats' => $this->journeys->stats(),
            'filters' => [
                'search' => $filters['search'] ?? '',
                'status' => $filters['status'] ?? '',
                'risk' => $filters['risk'] ?? '',
                'from' => $filters['from'] ?? '',
                'to' => $filters['to'] ?? '',
                'per_page' => (int) ($filters['per_page'] ?? JourneyService::PER_PAGE_OPTIONS[0]),
            ],
            'workers' => $user->can('create', Journey::class) && $user->company_id
                ? Worker::query()
                    ->with('primaryProject')
                    ->where('status', WorkerStatus::Active)
                    ->where('journey_access', true)
                    ->orderBy('first_name')
                    ->orderBy('last_name')
                    ->get(['id', 'primary_project_id', 'first_name', 'last_name', 'journey_access'])
                    ->filter(fn (Worker $worker) => $this->featureAccess->allows($worker, 'journey'))
                    ->map(fn (Worker $worker) => [
                        'id' => $worker->id,
                        'name' => $worker->full_name,
                    ])
                : [],
            'canCreate' => $user->can('create', Journey::class) && (bool) $user->company_id,
            'canManage' => $user->can('create', Journey::class) && (bool) $user->company_id,
        ]);
    }

    public function store(StoreJourneyRequest $request): RedirectResponse
    {
        $this->journeys->create($request->validated(), $request->user());

        return to_route('journeys.index')->with('success', 'Journey created.');
    }

    public function show(Journey $journey): Response
    {
        $this->authorize('view', $journey);

        return Inertia::render('Journeys/Show', [
            'journey' => new JourneyResource($journey->load(['worker', 'majorProject', 'approver'])),
            'canManage' => request()->user()->can('update', $journey),
        ]);
    }

    public function updateStatus(UpdateJourneyStatusRequest $request, Journey $journey): RedirectResponse
    {
        $this->journeys->updateStatus($journey, $request->enum('status', JourneyStatus::class));

        return back()->with('success', 'Journey status updated.');
    }

    public function export(Request $request): StreamedResponse
    {
        $this->authorize('viewAny', Journey::class);

        return $this->journeys->export($request->only(['search', 'status', 'risk', 'from', 'to']));
    }
}
