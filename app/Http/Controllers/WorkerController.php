<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreWorkerRequest;
use App\Http\Requests\UpdateWorkerRequest;
use App\Http\Resources\TrainingRecordResource;
use App\Http\Resources\WorkerResource;
use App\Models\MajorProject;
use App\Models\Position;
use App\Models\TrainingRecord;
use App\Models\Worker;
use App\Services\Training\TrainingComplianceService;
use App\Services\Workers\WorkerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class WorkerController extends Controller
{
    public function index(Request $request, WorkerService $service): Response
    {
        $this->authorize('viewAny', Worker::class);

        $filters = $request->only(['search', 'status', 'position', 'location', 'project_id', 'on_site', 'per_page']);
        $perPage = min(max($request->integer('per_page', 5), 5), 50);

        return Inertia::render('Workers/Index', [
            'workers' => WorkerResource::collection($service->paginate($filters, $perPage)),
            // Headline counts match the company-wide roster (same as the unfiltered
            // table). Do not scope to the session project; that undercounts vs listing.
            'stats' => $service->stats(),
            'filters' => $filters,
            'filterOptions' => $service->filterOptions(),
            'projects' => MajorProject::orderBy('name')->get(['id', 'name', 'code']),
            'company' => [
                'id' => $request->user()->company?->id,
                'name' => $request->user()->company?->name,
            ],
            'featureSummary' => $service->featureSummary($request->user()),
            'recentActivity' => $service->recentActivity(),
            'positions' => $this->positionOptions(),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Worker::class);

        return Inertia::render('Workers/Create', [
            'projects' => MajorProject::orderBy('name')->get(['id', 'name', 'code']),
            'positions' => $this->positionOptions(),
        ]);
    }

    public function store(StoreWorkerRequest $request): RedirectResponse
    {
        $attributes = $request->validated();
        unset($attributes['documents']);
        $attributes['documents'] = $this->storeDocuments($request);

        Worker::create([
            ...$attributes,
            'company_id' => $request->user()->company_id,
        ]);

        return to_route('workers.index')->with('success', 'Worker created.');
    }

    public function show(Request $request, Worker $worker, TrainingComplianceService $compliance): Response
    {
        $this->authorize('view', $worker);

        $worker->load([
            'primaryProject',
            'readiness',
            'company',
            'assignments.majorProject',
            'activities' => fn ($q) => $q->latest()->limit(10),
            'trainingRecords.certification.uploader',
        ]);

        $filters = [
            'scope' => in_array($request->query('scope'), ['required', 'elective'], true)
                ? $request->query('scope')
                : 'all',
            'status' => $request->string('training_status')->toString() ?: null,
            'search' => $request->string('training_search')->toString() ?: null,
        ];

        // Summary and tab counts always describe the whole record set, never the
        // filtered page, so the headline numbers stay stable while filtering.
        $all = $worker->trainingRecords;
        $visible = $this->filterTrainingRecords($all, $filters);
        $perPage = 8;
        $page = max($request->integer('training_page', 1), 1);

        return Inertia::render('Workers/Show', [
            'worker' => new WorkerResource($worker),
            'tab' => $request->query('tab') === 'readiness' ? 'readiness' : 'training',
            'training' => [
                'records' => TrainingRecordResource::collection(
                    $visible->forPage($page, $perPage)->values()
                ),
                'summary' => $compliance->summarizeRecords($all),
                'counts' => [
                    'all' => $all->count(),
                    'required' => $all->where('is_required', true)->count(),
                    'elective' => $all->where('is_required', false)->count(),
                ],
                'statuses' => $all->pluck('status')->filter()->unique()->sort()->values(),
                'filters' => $filters,
                'page' => $page,
                'per_page' => $perPage,
                'total' => $visible->count(),
            ],
        ]);
    }

    /**
     * @param  Collection<int, TrainingRecord>  $records
     * @param  array{scope: string, status: ?string, search: ?string}  $filters
     * @return Collection<int, TrainingRecord>
     */
    protected function filterTrainingRecords(Collection $records, array $filters): Collection
    {
        return $records
            ->when(
                $filters['scope'] !== 'all',
                fn ($items) => $items->where('is_required', $filters['scope'] === 'required'),
            )
            ->when(
                $filters['status'],
                fn ($items, $status) => $items->where('status', $status),
            )
            ->when(
                $filters['search'],
                fn ($items, $search) => $items->filter(
                    fn ($record) => str_contains(Str::lower($record->course_name), Str::lower($search))
                        || str_contains(Str::lower((string) $record->category), Str::lower($search)),
                ),
            )
            ->values();
    }

    public function edit(Worker $worker): Response
    {
        $this->authorize('update', $worker);

        return Inertia::render('Workers/Edit', [
            'worker' => new WorkerResource($worker->load(['primaryProject', 'company'])),
            'projects' => MajorProject::orderBy('name')->get(['id', 'name', 'code']),
            'positions' => $this->positionOptions(),
        ]);
    }

    public function update(UpdateWorkerRequest $request, Worker $worker): RedirectResponse
    {
        $attributes = $request->validated();
        unset($attributes['documents']);

        if ($request->hasFile('documents')) {
            $attributes['documents'] = [
                ...($worker->documents ?? []),
                ...$this->storeDocuments($request),
            ];
        }

        $worker->update($attributes);

        return back()->with('success', 'Worker updated.');
    }

    public function destroy(Worker $worker): RedirectResponse
    {
        $this->authorize('delete', $worker);
        $worker->delete();

        return to_route('workers.index')->with('success', 'Worker archived.');
    }

    /**
     * @return list<array{name: string, path: string, size: int, mime: string|null}>
     */
    protected function storeDocuments(Request $request): array
    {
        return collect($request->file('documents', []))
            ->map(function ($file) {
                return [
                    'name' => $file->getClientOriginalName(),
                    'path' => $file->store('worker-documents', 'public'),
                    'size' => $file->getSize(),
                    'mime' => $file->getClientMimeType(),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return list<string>
     */
    protected function positionOptions(): array
    {
        return Position::query()->active()->ordered()->pluck('name')->all();
    }
}
