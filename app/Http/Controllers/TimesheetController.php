<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTimesheetDraftRequest;
use App\Http\Requests\TimesheetActionRequest;
use App\Http\Requests\UpdateTimesheetRequest;
use App\Models\Timesheet;
use App\Models\Worker;
use App\Services\Timesheets\TimesheetApprovalQueueService;
use App\Services\Timesheets\CampTimesheetSyncService;
use App\Services\Timesheets\TimesheetEntryService;
use App\Services\Timesheets\TimesheetWorkflowService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TimesheetController extends Controller
{
    public function __construct(
        protected TimesheetWorkflowService $workflow,
        protected CampTimesheetSyncService $campSync,
    ) {}

    public function index(Request $request, TimesheetApprovalQueueService $queue): Response
    {
        $this->authorize('viewAny', Timesheet::class);

        $project = $request->attributes->get('currentProject');
        $data = $queue->overview($project, $request->only([
            'week',
            'search',
            'status',
            'approver_role',
            'accommodation',
            'per_page',
            'selected',
        ]));

        return Inertia::render('Timesheets/Index', [
            ...$data,
            'clientApprovalEnabled' => $this->workflow->clientApprovalEnabled(),
        ]);
    }

    public function entry(Request $request, TimesheetEntryService $entry): Response
    {
        $this->authorize('viewAny', Timesheet::class);

        $project = $request->attributes->get('currentProject');
        $data = $entry->roster($project, $request->only([
            'week',
            'search',
            'status',
            'per_page',
        ]));

        $data['canCreate'] = $request->user()->can('create', Timesheet::class);

        return Inertia::render('Timesheets/Entry', $data);
    }

    public function store(StoreTimesheetDraftRequest $request, TimesheetEntryService $entry): RedirectResponse
    {
        $worker = Worker::query()->findOrFail($request->integer('worker_id'));
        $timesheet = $entry->createDraft(
            $worker,
            $request->string('week')->toString(),
            $request->attributes->get('currentProject'),
        );

        return to_route('timesheets.show', $timesheet)->with('success', 'Timesheet draft ready.');
    }

    public function approval(Request $request, TimesheetApprovalQueueService $queue): Response
    {
        $this->authorize('viewAny', Timesheet::class);

        $project = $request->attributes->get('currentProject');
        $data = $queue->overview($project, $request->only([
            'week',
            'search',
            'status',
            'approver_role',
            'accommodation',
            'per_page',
            'selected',
        ]));

        return Inertia::render('Timesheets/Approval', [
            ...$data,
            'clientApprovalEnabled' => $this->workflow->clientApprovalEnabled(),
        ]);
    }

    public function show(Timesheet $timesheet): Response
    {
        $this->authorize('view', $timesheet);

        return Inertia::render('Timesheets/Show', $this->workflow->detailPayload($timesheet));
    }

    public function update(UpdateTimesheetRequest $request, Timesheet $timesheet): RedirectResponse
    {
        $this->workflow->updateDraft($timesheet, $request->validated());

        return back()->with('success', 'Timesheet draft saved.');
    }

    public function submit(TimesheetActionRequest $request, Timesheet $timesheet): RedirectResponse
    {
        // Persist latest form payload before submit when provided.
        if ($request->hasAny(['day_entries', 'equipment_entries', 'compliance', 'worker_signature'])) {
            $this->workflow->updateDraft($timesheet, $request->all());
            $timesheet->refresh();
        }

        $this->workflow->submit($timesheet, $request->user());

        return back()->with('success', 'Timesheet submitted for manager approval.');
    }

    public function approveManager(TimesheetActionRequest $request, Timesheet $timesheet): RedirectResponse
    {
        $this->workflow->approveAsManager(
            $timesheet,
            $request->user(),
            $request->validated('comment')
        );

        return back()->with('success', 'Timesheet approved by manager.');
    }

    public function approveClient(TimesheetActionRequest $request, Timesheet $timesheet): RedirectResponse
    {
        $this->workflow->approveAsClient(
            $timesheet,
            $request->user(),
            $request->validated('comment')
        );

        return back()->with('success', 'Timesheet fully approved.');
    }

    public function returnTimesheet(TimesheetActionRequest $request, Timesheet $timesheet): RedirectResponse
    {
        $this->workflow->returnForCorrection(
            $timesheet,
            $request->user(),
            $request->validated('reason'),
            $request->validated('comment')
        );

        return back()->with('success', 'Timesheet returned for correction.');
    }

    public function reject(TimesheetActionRequest $request, Timesheet $timesheet): RedirectResponse
    {
        $this->workflow->reject(
            $timesheet,
            $request->user(),
            $request->validated('reason'),
            $request->validated('comment')
        );

        return back()->with('success', 'Timesheet rejected.');
    }

    public function runCheck(Request $request): RedirectResponse
    {
        $this->authorize('create', Timesheet::class);

        $result = $this->campSync->syncForUser(
            $request->user(),
            $request->date('week') ?? now(),
        );

        if ($result['errors'] !== []) {
            return back()->with('error', implode(' ', $result['errors']));
        }

        $message = sprintf(
            'Camp schedule check complete: %d draft(s) created, %d updated, %d locked sheet(s) preserved.',
            $result['timesheets_created'],
            $result['timesheets_updated'],
            $result['timesheets_locked'],
        );

        if ($result['warnings'] !== []) {
            $message .= ' '.count($result['warnings']).' mapping warning(s) require review.';
        }

        return back()->with('success', $message);
    }
}
