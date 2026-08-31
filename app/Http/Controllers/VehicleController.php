<?php

namespace App\Http\Controllers;

use App\Enums\VehicleAvailability;
use App\Enums\VehicleType;
use App\Enums\WorkerStatus;
use App\Http\Requests\StoreVehicleRequest;
use App\Http\Resources\VehicleResource;
use App\Models\Vehicle;
use App\Models\Worker;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class VehicleController extends Controller
{
    /**
     * @var list<int>
     */
    private const PER_PAGE_OPTIONS = [10, 25, 50, 100];

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Vehicle::class);

        $search = trim((string) $request->query('search', ''));
        $type = $request->query('type');
        $availability = $request->query('availability');
        $perPage = (int) $request->query('per_page', self::PER_PAGE_OPTIONS[0]);

        if (! in_array($perPage, self::PER_PAGE_OPTIONS, true)) {
            $perPage = self::PER_PAGE_OPTIONS[0];
        }

        $query = Vehicle::query()->with('assignedDriver')->latest('id');

        if ($search !== '') {
            $query->where(function (Builder $inner) use ($search): void {
                $inner->where('make', 'like', "%{$search}%")
                    ->orWhere('model', 'like', "%{$search}%")
                    ->orWhere('vin', 'like', "%{$search}%")
                    ->orWhere('license_plate', 'like', "%{$search}%")
                    ->orWhere('base_location', 'like', "%{$search}%");
            });
        }

        if ($type) {
            $query->where('vehicle_type', $type);
        }

        if ($availability) {
            $query->where('availability', $availability);
        }

        $vehicles = $query->paginate($perPage)->withQueryString();

        return Inertia::render('Journeys/Vehicles/Index', [
            'vehicles' => VehicleResource::collection($vehicles),
            'stats' => $this->stats(),
            'filters' => [
                'search' => $search,
                'type' => $type ?? '',
                'availability' => $availability ?? '',
                'per_page' => $perPage,
            ],
            'canManage' => $request->user()->can('create', Vehicle::class),
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', Vehicle::class);

        return Inertia::render('Journeys/Vehicles/Create', [
            'drivers' => $this->drivers(),
            'vehicleTypes' => $this->options(VehicleType::cases()),
            'availabilityOptions' => $this->options(VehicleAvailability::cases()),
        ]);
    }

    public function store(StoreVehicleRequest $request): RedirectResponse
    {
        $data = $request->safe()->except(['insurance_document', 'is_draft']);
        $isDraft = $request->boolean('is_draft');

        if ($request->hasFile('insurance_document')) {
            $data['insurance_document_path'] = $request
                ->file('insurance_document')
                ->store('vehicles/insurance', 'public');
        }

        $data['company_id'] = $request->user()->company_id;
        $data['availability'] ??= VehicleAvailability::Available;
        $data['is_active'] = ! $isDraft;

        Vehicle::query()->create($data);

        return to_route('journeys.vehicles')->with(
            'success',
            $isDraft ? 'Vehicle draft saved.' : 'Vehicle registered.',
        );
    }

    /**
     * @return array{total: int, available: int, maintenance: int, insurance_expiring: int}
     */
    private function stats(): array
    {
        $base = Vehicle::query();

        return [
            'total' => (clone $base)->count(),
            'available' => (clone $base)->where('availability', VehicleAvailability::Available)->count(),
            'maintenance' => (clone $base)->where('availability', VehicleAvailability::Maintenance)->count(),
            'insurance_expiring' => (clone $base)
                ->whereNotNull('policy_end_date')
                ->whereBetween('policy_end_date', [now()->toDateString(), now()->addDays(30)->toDateString()])
                ->count(),
        ];
    }

    /**
     * @return list<array{id: int, name: string}>
     */
    private function drivers(): array
    {
        return Worker::query()
            ->where('status', WorkerStatus::Active)
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get(['id', 'first_name', 'last_name'])
            ->map(fn (Worker $worker) => [
                'id' => $worker->id,
                'name' => $worker->full_name,
            ])
            ->all();
    }

    /**
     * @param  list<VehicleType|VehicleAvailability>  $cases
     * @return list<array{value: string, label: string}>
     */
    private function options(array $cases): array
    {
        return array_map(
            fn ($case) => ['value' => $case->value, 'label' => $case->label()],
            $cases,
        );
    }
}
