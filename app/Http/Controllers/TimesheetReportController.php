<?php

namespace App\Http\Controllers;

use App\Models\Timesheet;
use App\Services\Timesheets\TimesheetReportService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TimesheetReportController extends Controller
{
    public function __construct(
        protected TimesheetReportService $reports,
    ) {}

    public function __invoke(Request $request): Response
    {
        $this->authorize('viewAny', Timesheet::class);

        return Inertia::render(
            'Timesheets/Reports',
            $this->reports->payload(
                $request->attributes->get('currentProject'),
                $request->only(['week', 'status', 'report_type', 'search']),
                $request->user(),
            ),
        );
    }

    public function export(Request $request): StreamedResponse
    {
        $this->authorize('viewAny', Timesheet::class);

        return $this->reports->export(
            $request->attributes->get('currentProject'),
            $request->only(['week', 'status', 'report_type', 'type', 'search']),
        );
    }
}
