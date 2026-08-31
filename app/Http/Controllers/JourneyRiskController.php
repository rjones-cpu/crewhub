<?php

namespace App\Http\Controllers;

use App\Enums\JourneyRisk;
use App\Http\Requests\StoreRiskAssessmentRequest;
use App\Http\Resources\JourneyRiskAssessmentResource;
use App\Models\Journey;
use App\Models\JourneyRiskAssessment;
use App\Services\Journeys\RiskAssessmentService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class JourneyRiskController extends Controller
{
    /**
     * @var list<int>
     */
    private const PER_PAGE_OPTIONS = [10, 25, 50, 100];

    public function __construct(private RiskAssessmentService $assessments)
    {
    }

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', JourneyRiskAssessment::class);

        $filters = $this->filters($request);
        $assessments = $this->filteredQuery($filters)
            ->with(['journey.worker', 'journey.vehicle'])
            ->latest('calculated_at')
            ->paginate($filters['per_page'])
            ->withQueryString();

        return Inertia::render('Journeys/Risk/Index', [
            'assessments' => JourneyRiskAssessmentResource::collection($assessments),
            'stats' => $this->stats(),
            'routes' => $this->routes(),
            'journeys' => $this->assessableJourneys(),
            'filters' => $filters,
            'canManage' => $request->user()->can('create', JourneyRiskAssessment::class),
        ]);
    }

    public function store(StoreRiskAssessmentRequest $request): RedirectResponse
    {
        $journey = Journey::query()->findOrFail($request->validated('journey_id'));

        $this->assessments->assess($journey, $request->user(), $request->safe()->except('journey_id'));

        return back()->with('success', 'Risk assessment calculated.');
    }

    public function recalculate(Request $request, JourneyRiskAssessment $assessment): RedirectResponse
    {
        $this->authorize('update', $assessment);

        $this->assessments->recalculate($assessment->load('journey'), $request->user());

        return back()->with('success', 'Risk recalculated.');
    }

    public function export(Request $request): StreamedResponse
    {
        $this->authorize('viewAny', JourneyRiskAssessment::class);

        $rows = $this->filteredQuery($this->filters($request))
            ->with(['journey.worker'])
            ->latest('calculated_at')
            ->get();

        return response()->streamDownload(function () use ($rows): void {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'Assessment ID',
                'Journey ID',
                'Driver',
                'Route',
                'Weather',
                'Road Conditions',
                'Risk Score',
                'Risk Level',
                'Calculated At',
            ]);

            foreach ($rows as $assessment) {
                fputcsv($handle, [
                    $assessment->code,
                    $assessment->journey?->code,
                    $assessment->journey?->worker?->full_name,
                    trim(($assessment->journey?->origin ?? '').' - '.($assessment->journey?->destination ?? '')),
                    $assessment->weather,
                    $assessment->road_conditions,
                    $assessment->score,
                    $assessment->outcome?->label(),
                    $assessment->calculated_at?->toDateTimeString(),
                ]);
            }

            fclose($handle);
        }, 'risk-assessments.csv', ['Content-Type' => 'text/csv']);
    }

    /**
     * @return array<string, mixed>
     */
    private function filters(Request $request): array
    {
        $perPage = (int) $request->query('per_page', self::PER_PAGE_OPTIONS[0]);

        return [
            'search' => trim((string) $request->query('search', '')),
            'status' => (string) $request->query('status', ''),
            'route' => (string) $request->query('route', ''),
            'from' => (string) $request->query('from', ''),
            'to' => (string) $request->query('to', ''),
            'per_page' => in_array($perPage, self::PER_PAGE_OPTIONS, true)
                ? $perPage
                : self::PER_PAGE_OPTIONS[0],
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function filteredQuery(array $filters): Builder
    {
        $query = JourneyRiskAssessment::query();

        if ($filters['search'] !== '') {
            $search = $filters['search'];
            $query->where(function (Builder $inner) use ($search): void {
                $inner->where('code', 'like', "%{$search}%")
                    ->orWhereHas('journey', function (Builder $journey) use ($search): void {
                        $journey->where('code', 'like', "%{$search}%")
                            ->orWhere('origin', 'like', "%{$search}%")
                            ->orWhere('destination', 'like', "%{$search}%")
                            ->orWhereHas('worker', function (Builder $worker) use ($search): void {
                                $worker->where('first_name', 'like', "%{$search}%")
                                    ->orWhere('last_name', 'like', "%{$search}%");
                            });
                    });
            });
        }

        if ($filters['status'] !== '') {
            $query->whereHas('journey', fn (Builder $journey) => $journey->where('status', $filters['status']));
        }

        if ($filters['route'] !== '') {
            [$origin, $destination] = array_pad(explode('|', $filters['route'], 2), 2, null);
            $query->whereHas('journey', function (Builder $journey) use ($origin, $destination): void {
                $journey->where('origin', $origin)->where('destination', $destination);
            });
        }

        if ($filters['from'] !== '') {
            $query->whereDate('calculated_at', '>=', $filters['from']);
        }

        if ($filters['to'] !== '') {
            $query->whereDate('calculated_at', '<=', $filters['to']);
        }

        return $query;
    }

    /**
     * @return array{total: int, low: int, medium: int, high: int}
     */
    private function stats(): array
    {
        $base = JourneyRiskAssessment::query();

        return [
            'total' => (clone $base)->count(),
            'low' => (clone $base)->where('outcome', JourneyRisk::Low)->count(),
            'medium' => (clone $base)->where('outcome', JourneyRisk::Medium)->count(),
            'high' => (clone $base)->where('outcome', JourneyRisk::High)->count(),
        ];
    }

    /**
     * Distinct origin/destination pairs, used by the Route filter.
     *
     * @return list<array{value: string, label: string}>
     */
    private function routes(): array
    {
        return Journey::query()
            ->select('origin', 'destination')
            ->distinct()
            ->orderBy('origin')
            ->get()
            ->map(fn (Journey $journey) => [
                'value' => "{$journey->origin}|{$journey->destination}",
                'label' => "{$journey->origin} - {$journey->destination}",
            ])
            ->all();
    }

    /**
     * @return list<array{id: int, label: string}>
     */
    private function assessableJourneys(): array
    {
        return Journey::query()
            ->with('worker')
            ->latest('departure_at')
            ->limit(100)
            ->get()
            ->map(fn (Journey $journey) => [
                'id' => $journey->id,
                'label' => trim(sprintf(
                    '%s - %s (%s to %s)',
                    $journey->code,
                    $journey->worker?->full_name ?? 'Unassigned',
                    $journey->origin,
                    $journey->destination,
                )),
            ])
            ->all();
    }
}
