<?php



namespace App\Http\Controllers;



use App\Models\MajorProject;

use App\Models\ScheduleModificationRequest;

use App\Models\Worker;

use App\Services\Schedule\ScheduleBoardService;

use App\Services\Schedule\ScheduleEditService;

use App\Services\Schedule\Views\ScheduleViewFilters;

use App\Services\Schedule\Views\ScheduleViewPresenter;

use Illuminate\Http\JsonResponse;

use Illuminate\Http\RedirectResponse;

use Illuminate\Http\Request;

use Illuminate\Validation\Rule;

use Inertia\Inertia;

use Inertia\Response;

use InvalidArgumentException;



class ScheduleController extends Controller

{

    public function __construct(

        private readonly ScheduleBoardService $board,

        private readonly ScheduleEditService $edits,

        private readonly ScheduleViewPresenter $views,

    ) {

    }



    public function index(Request $request): Response

    {

        $data = $request->validate([

            'project_id' => ['nullable', 'integer'],

            'view' => ['nullable', Rule::in(['board', 'list', 'calendar', 'requests'])],

            'week' => ['nullable', 'date_format:Y-m-d'],

            'department' => ['nullable', 'string', 'max:100'],

            'shift' => ['nullable', 'string', 'max:50'],

            'status' => ['nullable', 'string', 'max:50'],

            'request_type' => ['nullable', 'string', 'max:50'],

            'request' => ['nullable', 'string', 'max:50'],

            'search' => ['nullable', 'string', 'max:100'],

            'page' => ['nullable', 'integer', 'min:1'],

            'per_page' => ['nullable', 'integer', 'in:10,15,25,50'],

        ]);



        $filters = ScheduleViewFilters::fromQuery($data);



        return Inertia::render('Schedule/Index', [

            ...$this->board->board($data['project_id'] ?? null, $request->user()),

            ...$this->views->present($filters, $request->user()),

            'canAddProject' => $request->user()->can('attemptCreate', MajorProject::class),

        ]);

    }



    public function updateDays(Request $request): JsonResponse

    {

        $data = $request->validate([

            'worker_id' => ['required', 'integer', 'exists:workers,id'],

            'project_id' => ['required', 'integer', 'exists:major_projects,id'],

            'source_date' => ['required', 'date_format:Y-m-d'],

            'drop_date' => ['required', 'date_format:Y-m-d'],

            'row_types' => ['required', 'array'],

            'row_types.*' => ['required', 'string', Rule::in(['work', 'travel', 'off'])],

            'back_drag_revert' => ['sometimes', 'boolean'],

        ]);



        try {

            $result = $this->edits->applyDrag(

                $request->user(),

                Worker::query()->findOrFail($data['worker_id']),

                MajorProject::query()->findOrFail($data['project_id']),

                $data['source_date'],

                $data['drop_date'],

                $data['row_types'],

                (bool) ($data['back_drag_revert'] ?? false),

            );

        } catch (InvalidArgumentException $exception) {

            return response()->json(['message' => $exception->getMessage()], 422);

        }



        return response()->json([

            'status' => 'success',

            'data' => $result,

        ]);

    }



    public function paintDays(Request $request): JsonResponse

    {

        $data = $request->validate([

            'worker_id' => ['required', 'integer', 'exists:workers,id'],

            'project_id' => ['required', 'integer', 'exists:major_projects,id'],

            'dates' => ['required', 'array', 'min:1'],

            'dates.*' => ['required', 'date_format:Y-m-d'],

            'type' => ['required', 'string', Rule::in(['work', 'travel', 'off'])],

            'needs_room' => ['sometimes', 'array'],

            'needs_room.*' => ['boolean'],

        ]);



        try {

            $result = $this->edits->applyPaint(

                $request->user(),

                Worker::query()->findOrFail($data['worker_id']),

                MajorProject::query()->findOrFail($data['project_id']),

                $data['dates'],

                $data['type'],

                $data['needs_room'] ?? [],

            );

        } catch (InvalidArgumentException $exception) {

            return response()->json(['message' => $exception->getMessage()], 422);

        }



        return response()->json([

            'status' => 'success',

            'data' => $result,

        ]);

    }



    public function publish(Request $request): RedirectResponse

    {

        $data = $request->validate([

            'project_id' => ['nullable', 'integer'],

        ]);



        try {

            $result = $this->edits->publishAll($request->user(), $data['project_id'] ?? null);

        } catch (InvalidArgumentException $exception) {

            return back()->with('error', $exception->getMessage());

        }



        $message = $result['published'] === 0

            ? 'No draft changes to publish.'

            : "Published {$result['published']} schedule draft(s) and synced lodge reservations.";



        return back()->with('success', $message);

    }



    public function reset(Request $request): RedirectResponse

    {

        $data = $request->validate([

            'project_id' => ['nullable', 'integer'],

        ]);



        try {

            $result = $this->edits->resetAll($request->user(), $data['project_id'] ?? null);

        } catch (InvalidArgumentException $exception) {

            return back()->with('error', $exception->getMessage());

        }



        return back()->with('success', "Reset {$result['cleared']} draft(s).");

    }



    public function acknowledge(Request $request, ScheduleModificationRequest $modificationRequest): RedirectResponse

    {

        try {

            $this->edits->acknowledge($request->user(), $modificationRequest);

        } catch (InvalidArgumentException $exception) {

            return back()->with('error', $exception->getMessage());

        }



        return back()->with('success', 'Reservation change acknowledged.');

    }

}

