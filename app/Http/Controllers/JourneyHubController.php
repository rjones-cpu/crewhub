<?php

namespace App\Http\Controllers;

use App\Http\Requests\JourneyHubRequest;
use App\Http\Resources\JourneyHubResource;
use App\Http\Resources\JourneyResource;
use App\Models\Journey;
use App\Models\JourneyHub;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class JourneyHubController extends Controller
{
    /**
     * @var list<int>
     */
    private const PER_PAGE_OPTIONS = [10, 25, 50, 100];

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', JourneyHub::class);

        $search = trim((string) $request->query('search', ''));
        $status = (string) $request->query('status', '');
        $perPage = (int) $request->query('per_page', self::PER_PAGE_OPTIONS[0]);

        if (! in_array($perPage, self::PER_PAGE_OPTIONS, true)) {
            $perPage = self::PER_PAGE_OPTIONS[0];
        }

        $query = JourneyHub::query()->withCount('journeys')->orderBy('name');

        if ($search !== '') {
            $query->where(function (Builder $inner) use ($search): void {
                $inner->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('location', 'like', "%{$search}%");
            });
        }

        if ($status === 'active') {
            $query->where('is_active', true);
        } elseif ($status === 'inactive') {
            $query->where('is_active', false);
        }

        return Inertia::render('Journeys/Hubs/Index', [
            'hubs' => JourneyHubResource::collection($query->paginate($perPage)->withQueryString()),
            'undesignated' => JourneyResource::collection(
                Journey::query()
                    ->with('worker')
                    ->whereNull('journey_hub_id')
                    ->latest('departure_at')
                    ->limit(25)
                    ->get()
            ),
            'stats' => $this->stats(),
            'filters' => [
                'search' => $search,
                'status' => $status,
                'per_page' => $perPage,
            ],
            'canManage' => $request->user()->can('create', JourneyHub::class),
        ]);
    }

    public function store(JourneyHubRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['company_id'] = $request->user()->company_id;

        JourneyHub::query()->create($data);

        return back()->with('success', 'Journey hub created.');
    }

    public function update(JourneyHubRequest $request, JourneyHub $hub): RedirectResponse
    {
        $hub->update($request->validated());

        return back()->with('success', 'Journey hub updated.');
    }

    public function destroy(JourneyHub $hub): RedirectResponse
    {
        $this->authorize('delete', $hub);

        $hub->delete();

        return back()->with('success', 'Journey hub removed.');
    }

    /**
     * Designate journeys to a hub. The free-text `hub` column is kept in step so the
     * journey list and CSV export keep showing a hub name.
     */
    public function designate(Request $request, JourneyHub $hub): RedirectResponse
    {
        $this->authorize('update', $hub);

        $validated = $request->validate([
            'journey_ids' => ['required', 'array'],
            'journey_ids.*' => [
                'integer',
                Rule::exists('journeys', 'id')->where('company_id', $request->user()->company_id),
            ],
        ]);

        Journey::query()
            ->whereIn('id', $validated['journey_ids'])
            ->update([
                'journey_hub_id' => $hub->id,
                'hub' => $hub->name,
            ]);

        return back()->with('success', 'Journeys designated to '.$hub->name.'.');
    }

    /**
     * @return array{total: int, active: int, designated: int, undesignated: int}
     */
    private function stats(): array
    {
        return [
            'total' => JourneyHub::query()->count(),
            'active' => JourneyHub::query()->where('is_active', true)->count(),
            'designated' => Journey::query()->whereNotNull('journey_hub_id')->count(),
            'undesignated' => Journey::query()->whereNull('journey_hub_id')->count(),
        ];
    }
}
