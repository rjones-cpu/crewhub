<?php

namespace App\Services\Timesheets;

use App\Enums\TimesheetStatus;
use App\Models\Timesheet;
use App\Models\User;
use App\Models\WorkerActivity;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

class TimesheetWorkflowService
{
    public function __construct(
        protected AccommodationConfirmationService $accommodations,
    ) {}

    /** Whether the optional second (client) approval gate is switched on for this deployment. */
    public function clientApprovalEnabled(): bool
    {
        return (bool) config('timesheets.client_approval_enabled');
    }

    /** Whether a given timesheet actually has to pass a client approval gate. */
    public function clientGateApplies(Timesheet $timesheet): bool
    {
        return $timesheet->requiresClientApproval();
    }

    public function createDraft(\App\Models\Worker $worker, \Carbon\Carbon $week, ?\App\Models\MajorProject $project = null): Timesheet
    {
        $periodStart = $week->copy()->startOfWeek();
        $periodEnd = $periodStart->copy()->endOfWeek();
        $clientRequired = $this->clientApprovalEnabled() && (bool) $project?->client_approval_required;

        $existing = Timesheet::query()
            ->where('worker_id', $worker->id)
            ->whereDate('period_start', $periodStart->toDateString())
            ->whereDate('period_end', $periodEnd->toDateString())
            ->first();

        if ($existing) {
            return $existing;
        }

        $dayEntries = $this->blankWeek($periodStart);

        return Timesheet::query()->create([
            'worker_id' => $worker->id,
            'company_id' => $worker->company_id,
            'major_project_id' => $project?->id ?? $worker->primary_project_id,
            'period_start' => $periodStart->toDateString(),
            'period_end' => $periodEnd->toDateString(),
            'due_date' => $periodEnd->copy()->addDays(2)->toDateString(),
            'status' => TimesheetStatus::Draft,
            'client_approval_required' => $clientRequired,
            'day_entries' => $dayEntries,
            'equipment_entries' => [],
            'compliance' => $this->defaultCompliance(),
            'status_history' => [[
                'status' => TimesheetStatus::Draft->value,
                'label' => TimesheetStatus::Draft->label(),
                'at' => now()->toIso8601String(),
                'by' => null,
                'note' => 'Draft created from timesheet entry',
                'current' => true,
            ]],
            ...$this->calculateTotals($dayEntries, []),
        ]);
    }

    public function updateDraft(Timesheet $timesheet, array $data): Timesheet
    {
        if (! $timesheet->isEditable()) {
            throw ValidationException::withMessages([
                'status' => 'This timesheet is locked and cannot be edited.',
            ]);
        }

        $dayEntries = $data['day_entries'] ?? $timesheet->day_entries ?? [];
        $equipmentEntries = $data['equipment_entries'] ?? $timesheet->equipment_entries ?? [];
        $totals = $this->calculateTotals($dayEntries, $equipmentEntries);

        $timesheet->fill([
            'day_entries' => $dayEntries,
            'equipment_entries' => $equipmentEntries,
            'compliance' => $data['compliance'] ?? $timesheet->compliance,
            'worker_comment' => $data['worker_comment'] ?? $timesheet->worker_comment,
            'manager_comment' => array_key_exists('manager_comment', $data)
                ? $data['manager_comment']
                : $timesheet->manager_comment,
            'client_comment' => array_key_exists('client_comment', $data)
                ? $data['client_comment']
                : $timesheet->client_comment,
            'worker_signature' => $data['worker_signature'] ?? $timesheet->worker_signature,
            'client_approval_required' => array_key_exists('client_approval_required', $data)
                ? (bool) $data['client_approval_required']
                : $timesheet->client_approval_required,
            'supervisor_name' => $data['supervisor_name'] ?? $timesheet->supervisor_name,
            ...$totals,
        ]);

        $timesheet->save();

        return $timesheet->fresh();
    }

    public function submit(Timesheet $timesheet, User $actor): Timesheet
    {
        if (! $timesheet->isEditable()) {
            throw ValidationException::withMessages([
                'status' => 'Only draft or returned timesheets can be submitted.',
            ]);
        }

        $compliance = $timesheet->compliance ?? [];
        if (empty($compliance['signature']) || empty($compliance['worker_declaration'])) {
            throw ValidationException::withMessages([
                'compliance' => 'Signature and worker declaration are required before submit.',
            ]);
        }

        $timesheet->status = TimesheetStatus::Submitted;
        $timesheet->submitted_at = now();
        $timesheet->returned_at = null;
        $timesheet->returned_by = null;
        $timesheet->return_reason = null;
        $timesheet->worker_signed_at = $timesheet->worker_signed_at ?? now();
        $timesheet->status_history = $this->pushHistory(
            $timesheet,
            TimesheetStatus::Submitted,
            $actor->name,
            'Worker submitted timesheet'
        );
        $timesheet->save();

        $this->logActivity($timesheet, $actor, 'timesheet_submitted', "{$actor->name} submitted timesheet for period {$timesheet->period_start->toDateString()}.");

        return $timesheet->fresh();
    }

    public function approveAsManager(Timesheet $timesheet, User $actor, ?string $comment = null): Timesheet
    {
        if (! $timesheet->awaitsManagerApproval()) {
            throw ValidationException::withMessages([
                'status' => 'Timesheet is not awaiting manager approval.',
            ]);
        }

        if ($comment !== null) {
            $timesheet->manager_comment = $comment;
        }

        $timesheet->manager_approved_by = $actor->id;
        $timesheet->manager_approved_at = now();
        $timesheet->approved_by = $actor->id;

        if ($this->clientGateApplies($timesheet)) {
            $timesheet->status = TimesheetStatus::ManagerApproved;
            $note = 'Manager approved — awaiting client approval';
        } else {
            $timesheet->status = TimesheetStatus::FullyApproved;
            $timesheet->approved_at = now();
            $note = 'Manager approved — fully approved (client approval not required)';
        }

        $timesheet->status_history = $this->pushHistory($timesheet, $timesheet->status, $actor->name, $note);
        $timesheet->save();

        $this->logActivity($timesheet, $actor, 'timesheet_manager_approved', "{$actor->name} approved timesheet.");

        return $timesheet->fresh();
    }

    public function approveAsClient(Timesheet $timesheet, User $actor, ?string $comment = null): Timesheet
    {
        if (! $timesheet->awaitsClientApproval()) {
            throw ValidationException::withMessages([
                'status' => 'Timesheet is not awaiting client approval.',
            ]);
        }

        if ($comment !== null) {
            $timesheet->client_comment = $comment;
        }

        $timesheet->status = TimesheetStatus::FullyApproved;
        $timesheet->client_approved_by = $actor->id;
        $timesheet->client_approved_at = now();
        $timesheet->approved_by = $actor->id;
        $timesheet->approved_at = now();
        $timesheet->status_history = $this->pushHistory(
            $timesheet,
            TimesheetStatus::FullyApproved,
            $actor->name,
            'Client approved — fully approved'
        );
        $timesheet->save();

        $this->logActivity($timesheet, $actor, 'timesheet_client_approved', "{$actor->name} client-approved timesheet.");

        return $timesheet->fresh();
    }

    public function returnForCorrection(Timesheet $timesheet, User $actor, ?string $reason = null, ?string $comment = null): Timesheet
    {
        if (! in_array($timesheet->status, [TimesheetStatus::Submitted, TimesheetStatus::ManagerApproved], true)) {
            throw ValidationException::withMessages([
                'status' => 'Only submitted or manager-approved timesheets can be returned.',
            ]);
        }

        if ($timesheet->status === TimesheetStatus::Submitted && $comment !== null) {
            $timesheet->manager_comment = $comment;
        }

        if ($timesheet->status === TimesheetStatus::ManagerApproved && $comment !== null) {
            $timesheet->client_comment = $comment;
        }

        $timesheet->status = TimesheetStatus::Returned;
        $timesheet->returned_by = $actor->id;
        $timesheet->returned_at = now();
        $timesheet->return_reason = $reason ?? $comment;
        $timesheet->status_history = $this->pushHistory(
            $timesheet,
            TimesheetStatus::Returned,
            $actor->name,
            $reason ?? $comment ?? 'Returned for correction'
        );
        $timesheet->save();

        $this->logActivity($timesheet, $actor, 'timesheet_returned', "{$actor->name} returned timesheet for correction.");

        return $timesheet->fresh();
    }

    public function reject(Timesheet $timesheet, User $actor, ?string $reason = null, ?string $comment = null): Timesheet
    {
        if (! in_array($timesheet->status, [TimesheetStatus::Submitted, TimesheetStatus::ManagerApproved], true)) {
            throw ValidationException::withMessages([
                'status' => 'Only submitted or manager-approved timesheets can be rejected.',
            ]);
        }

        if ($comment !== null) {
            if ($timesheet->status === TimesheetStatus::Submitted) {
                $timesheet->manager_comment = $comment;
            } else {
                $timesheet->client_comment = $comment;
            }
        }

        $timesheet->status = TimesheetStatus::Rejected;
        $timesheet->returned_by = $actor->id;
        $timesheet->returned_at = now();
        $timesheet->return_reason = $reason ?? $comment;
        $timesheet->approved_by = $actor->id;
        $timesheet->status_history = $this->pushHistory(
            $timesheet,
            TimesheetStatus::Rejected,
            $actor->name,
            $reason ?? $comment ?? 'Rejected'
        );
        $timesheet->save();

        $this->logActivity($timesheet, $actor, 'timesheet_rejected', "{$actor->name} rejected timesheet.");

        return $timesheet->fresh();
    }

    public function calculateTotals(array $dayEntries, array $equipmentEntries): array
    {
        $regular = collect($dayEntries)->sum(fn ($row) => (float) Arr::get($row, 'regular_hours', 0));
        $overtime = collect($dayEntries)->sum(fn ($row) => (float) Arr::get($row, 'overtime_hours', 0));
        $double = collect($dayEntries)->sum(fn ($row) => (float) Arr::get($row, 'double_time_hours', 0));
        $travel = collect($dayEntries)->sum(fn ($row) => (float) Arr::get($row, 'travel_hours', 0));
        $standby = collect($dayEntries)->sum(fn ($row) => (float) Arr::get($row, 'standby_hours', 0));
        $break = collect($dayEntries)->sum(fn ($row) => (float) Arr::get($row, 'break_hours', 0));
        $total = collect($dayEntries)->sum(function ($row) {
            if (isset($row['total_hours'])) {
                return (float) $row['total_hours'];
            }

            return (float) Arr::get($row, 'regular_hours', 0)
                + (float) Arr::get($row, 'overtime_hours', 0)
                + (float) Arr::get($row, 'double_time_hours', 0)
                + (float) Arr::get($row, 'travel_hours', 0)
                + (float) Arr::get($row, 'standby_hours', 0);
        });
        $equipment = collect($equipmentEntries)->sum(fn ($row) => (float) Arr::get($row, 'hours', 0));

        return [
            'regular_hours' => round($regular, 2),
            'overtime_hours' => round($overtime, 2),
            'double_time_hours' => round($double, 2),
            'travel_hours' => round($travel, 2),
            'standby_hours' => round($standby, 2),
            'break_hours' => round($break, 2),
            'hours' => round($total, 2),
            'equipment_hours' => round($equipment, 2),
        ];
    }

    public function detailPayload(Timesheet $timesheet): array
    {
        $timesheet->loadMissing([
            'worker.company',
            'majorProject',
            'managerApprover',
            'clientApprover',
            'returnedByUser',
        ]);

        if (empty($timesheet->day_entries) && $timesheet->period_start && $timesheet->period_end) {
            $timesheet->day_entries = $this->blankWeek($timesheet->period_start);
        }

        $status = $timesheet->status;
        $clientRequired = $this->clientGateApplies($timesheet);

        return [
            'timesheet' => [
                'id' => $timesheet->id,
                'status' => $status?->value,
                'status_label' => $status?->label(),
                'period_start' => $timesheet->period_start?->toDateString(),
                'period_end' => $timesheet->period_end?->toDateString(),
                'due_date' => $timesheet->due_date?->toDateString(),
                'client_approval_required' => $clientRequired,
                'hours' => (float) $timesheet->hours,
                'regular_hours' => (float) $timesheet->regular_hours,
                'overtime_hours' => (float) $timesheet->overtime_hours,
                'double_time_hours' => (float) $timesheet->double_time_hours,
                'travel_hours' => (float) $timesheet->travel_hours,
                'standby_hours' => (float) $timesheet->standby_hours,
                'break_hours' => (float) $timesheet->break_hours,
                'equipment_hours' => (float) $timesheet->equipment_hours,
                'day_entries' => $timesheet->day_entries ?? [],
                'equipment_entries' => $timesheet->equipment_entries ?? [],
                'compliance' => $timesheet->compliance ?? $this->defaultCompliance(),
                'status_history' => $this->normalizeHistory($timesheet),
                'supervisor_name' => $timesheet->supervisor_name,
                'worker_comment' => $timesheet->worker_comment,
                'manager_comment' => $timesheet->manager_comment,
                'client_comment' => $timesheet->client_comment,
                'worker_signature' => $timesheet->worker_signature,
                'worker_signed_at' => $timesheet->worker_signed_at?->toIso8601String(),
                'submitted_at' => $timesheet->submitted_at?->toIso8601String(),
                'manager_approved_at' => $timesheet->manager_approved_at?->toIso8601String(),
                'manager_approver_name' => $timesheet->managerApprover?->name,
                'client_approved_at' => $timesheet->client_approved_at?->toIso8601String(),
                'client_approver_name' => $timesheet->clientApprover?->name,
                'approved_at' => $timesheet->approved_at?->toIso8601String(),
                'return_reason' => $timesheet->return_reason,
                'editable' => $timesheet->isEditable(),
                'week_number' => $timesheet->period_start?->isoWeek(),
                'requirements' => $this->requirements($timesheet),
                'approval_settings' => $this->approvalSettings($timesheet),
                'worker' => [
                    'id' => $timesheet->worker?->id,
                    'full_name' => $timesheet->worker?->full_name,
                    'employee_id' => $timesheet->worker?->employee_id,
                    'position' => $timesheet->worker?->position,
                    'status' => $timesheet->worker?->status?->value ?? $timesheet->worker?->status,
                    'company' => $timesheet->worker?->company?->name,
                    'avatar' => $timesheet->worker?->avatar,
                    'location' => $timesheet->worker?->location,
                ],
                'project' => [
                    'id' => $timesheet->majorProject?->id,
                    'name' => $timesheet->majorProject?->name,
                    'code' => $timesheet->majorProject?->code,
                ],
            ],
            'approvalSteps' => $this->approvalSteps($timesheet),
            'approvalRecord' => $this->approvalRecord($timesheet),
            'clientApprovalEnabled' => $this->clientApprovalEnabled(),
            'can' => [
                'update' => $timesheet->isEditable(),
                'submit' => $timesheet->isEditable(),
                'approve_manager' => $timesheet->awaitsManagerApproval(),
                'approve_client' => $timesheet->awaitsClientApproval(),
                'return' => in_array($status, [TimesheetStatus::Submitted, TimesheetStatus::ManagerApproved], true),
                'reject' => in_array($status, [TimesheetStatus::Submitted, TimesheetStatus::ManagerApproved], true),
            ],
        ];
    }

    protected function blankWeek($periodStart): array
    {
        $start = $periodStart instanceof \Carbon\Carbon
            ? $periodStart->copy()
            : \Carbon\Carbon::parse($periodStart);
        $entries = [];

        for ($i = 0; $i < 7; $i++) {
            $date = $start->copy()->addDays($i);
            $entries[] = [
                'date' => $date->toDateString(),
                'day_label' => $date->format('D'),
                'shift' => $date->isWeekend() ? 'Off' : 'Day',
                'start_time' => $date->isWeekend() ? null : '07:00',
                'end_time' => $date->isWeekend() ? null : '17:00',
                'break_hours' => 0,
                'regular_hours' => 0,
                'overtime_hours' => 0,
                'double_time_hours' => 0,
                'travel_hours' => 0,
                'standby_hours' => 0,
                'total_hours' => 0,
                'work_location' => null,
                'task' => null,
                'notes' => '',
            ];
        }

        return $entries;
    }

    /**
     * Which optional trackers are switched on for this timesheet. Stored inside the
     * existing `compliance` JSON column so no schema change is needed.
     */
    protected function requirements(Timesheet $timesheet): array
    {
        $stored = $timesheet->compliance['requirements'] ?? [];

        return [
            'mileage' => (bool) ($stored['mileage'] ?? true),
            'equipment' => (bool) ($stored['equipment'] ?? true),
            'materials' => (bool) ($stored['materials'] ?? false),
            'hourly_rate' => (bool) ($stored['hourly_rate'] ?? true),
            'day_rate' => (bool) ($stored['day_rate'] ?? false),
        ];
    }

    /**
     * Which approval steps apply. Only client approval has a dedicated column; the
     * rest live in `compliance` until they are promoted to project settings.
     */
    protected function approvalSettings(Timesheet $timesheet): array
    {
        $stored = $timesheet->compliance['approval_settings'] ?? [];

        return [
            'worker' => (bool) ($stored['worker'] ?? true),
            'manager' => (bool) ($stored['manager'] ?? true),
            'client' => $this->clientGateApplies($timesheet),
            'ai_accommodations' => (bool) ($stored['ai_accommodations'] ?? true),
        ];
    }

    /** The numbered approval history shown alongside the timesheet. */
    protected function approvalRecord(Timesheet $timesheet): array
    {
        $status = $timesheet->status;
        $clientRequired = $this->clientGateApplies($timesheet);
        $accommodation = $this->accommodations->stateFor($timesheet);
        $workerSignedAt = $timesheet->worker_signed_at ?? $timesheet->submitted_at;

        $accommodationState = match ($accommodation['state']) {
            'confirmed' => 'confirmed',
            'pending' => 'in_progress',
            default => 'not_required',
        };

        return array_values(array_filter([
            [
                'key' => 'worker',
                'title' => 'Worker Approved',
                'actor' => $workerSignedAt ? 'Approved by: '.($timesheet->worker?->full_name ?? 'Worker') : null,
                'detail' => $workerSignedAt ? null : 'Awaiting worker submission',
                'at' => $this->recordTimestamp($workerSignedAt),
                'state' => $workerSignedAt ? 'completed' : 'pending',
            ],
            [
                'key' => 'accommodation',
                'title' => 'AI Accommodations Confirmation',
                'actor' => 'AI System',
                'detail' => match ($accommodation['state']) {
                    'confirmed' => 'All accommodations confirmed',
                    'pending' => 'Awaiting accommodation confirmation',
                    default => 'No accommodation booked for this period',
                },
                'at' => $accommodation['at'],
                'state' => $accommodationState,
            ],
            [
                'key' => 'manager',
                'title' => 'Manager Approved',
                'actor' => $timesheet->manager_approved_at
                    ? 'Approved by: '.($timesheet->managerApprover?->name ?? 'Manager')
                    : null,
                'detail' => $timesheet->manager_approved_at ? null : 'Pending approval from manager',
                'at' => $this->recordTimestamp($timesheet->manager_approved_at),
                'state' => match (true) {
                    (bool) $timesheet->manager_approved_at => 'completed',
                    $status === TimesheetStatus::Submitted => 'in_progress',
                    default => 'pending',
                },
            ],
            $this->clientApprovalEnabled() ? [
                'key' => 'client',
                'title' => 'Client Approved',
                'actor' => $timesheet->client_approved_at
                    ? 'Approved by: '.($timesheet->clientApprover?->name ?? 'Client')
                    : null,
                'detail' => match (true) {
                    ! $clientRequired => 'Client approval not required',
                    (bool) $timesheet->client_approved_at => null,
                    default => 'Pending approval from client',
                },
                'at' => $this->recordTimestamp($timesheet->client_approved_at),
                'state' => match (true) {
                    ! $clientRequired => 'not_required',
                    (bool) $timesheet->client_approved_at => 'completed',
                    $status === TimesheetStatus::ManagerApproved => 'in_progress',
                    default => 'pending',
                },
            ] : null,
        ]));
    }

    protected function recordTimestamp($value): ?string
    {
        return $value ? $value->format('M j, Y g:i A') : null;
    }

    protected function approvalSteps(Timesheet $timesheet): array
    {
        $status = $timesheet->status;
        $clientRequired = $this->clientGateApplies($timesheet);

        $workerState = match ($status) {
            TimesheetStatus::Draft, TimesheetStatus::Returned => 'pending',
            default => 'completed',
        };

        $managerState = match ($status) {
            TimesheetStatus::Submitted => 'in_progress',
            TimesheetStatus::ManagerApproved, TimesheetStatus::FullyApproved => 'completed',
            TimesheetStatus::Rejected => 'rejected',
            default => 'pending',
        };

        $clientState = ! $clientRequired
            ? 'disabled'
            : match ($status) {
                TimesheetStatus::ManagerApproved => 'in_progress',
                TimesheetStatus::FullyApproved => 'completed',
                TimesheetStatus::Rejected => 'rejected',
                default => 'pending',
            };

        return array_values(array_filter([
            [
                'key' => 'worker',
                'title' => 'Worker Submission',
                'state' => $workerState,
                'label' => match ($workerState) {
                    'completed' => 'Submitted',
                    default => 'Not submitted',
                },
            ],
            [
                'key' => 'manager',
                'title' => 'Manager Approval',
                'state' => $managerState,
                'label' => match ($managerState) {
                    'completed' => 'Approved',
                    'in_progress' => 'Pending',
                    'rejected' => 'Rejected',
                    default => 'Pending',
                },
            ],
            $this->clientApprovalEnabled() ? [
                'key' => 'client',
                'title' => 'Client Approval',
                'state' => $clientState,
                'label' => match ($clientState) {
                    'disabled' => 'Not required',
                    'completed' => 'Approved',
                    'in_progress' => 'Pending',
                    'rejected' => 'Rejected',
                    default => 'Pending',
                },
            ] : null,
        ]));
    }

    protected function pushHistory(Timesheet $timesheet, TimesheetStatus $status, ?string $by, ?string $note): array
    {
        $history = collect($timesheet->status_history ?? [])
            ->map(function ($row) {
                $row['current'] = false;

                return $row;
            })
            ->values()
            ->all();

        $history[] = [
            'status' => $status->value,
            'label' => $status->label(),
            'at' => now()->toIso8601String(),
            'by' => $by,
            'note' => $note,
            'current' => true,
        ];

        return $history;
    }

    protected function normalizeHistory(Timesheet $timesheet): array
    {
        $history = $timesheet->status_history ?? [];

        if ($history !== []) {
            return $history;
        }

        return [[
            'status' => $timesheet->status?->value ?? TimesheetStatus::Draft->value,
            'label' => $timesheet->status?->label() ?? 'Draft',
            'at' => $timesheet->created_at?->toIso8601String(),
            'by' => null,
            'note' => null,
            'current' => true,
        ]];
    }

    protected function defaultCompliance(): array
    {
        return [
            'safety_meeting' => false,
            'toolbox_talk' => false,
            'incident_report' => false,
            'attachments' => false,
            'signature' => false,
            'worker_declaration' => false,
        ];
    }

    protected function logActivity(Timesheet $timesheet, User $actor, string $type, string $description): void
    {
        if (! $timesheet->worker_id) {
            return;
        }

        WorkerActivity::query()->create([
            'worker_id' => $timesheet->worker_id,
            'company_id' => $timesheet->company_id,
            'user_id' => $actor->id,
            'type' => $type,
            'description' => $description,
            'metadata' => ['timesheet_id' => $timesheet->id],
        ]);
    }
}
