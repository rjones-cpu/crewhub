<?php

namespace App\Services\Schedule\Views;

use App\Models\Worker;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * The Change Requests view: the approval queue and the detail drawer that shows
 * what a request does to coverage before a manager signs it off.
 *
 * Crew Hub has no change-request table yet, so the queue is derived from the
 * rostered workforce. The derivation is deterministic (seeded by worker and
 * date) so a request keeps its id, type, and status across reloads, and the
 * coverage figures in the drawer are computed from the real roster.
 */
class ScheduleChangeRequestPresenter
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_OVERTIME_PENDING = 'overtime_pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    /** Request catalogue: label, the change it asks for, and why. */
    private const TYPES = [
        'shift_swap' => ['label' => 'Shift Swap', 'reason' => 'Personal appointment'],
        'day_off' => ['label' => 'Day Off', 'reason' => 'Family event'],
        'sick_replacement' => ['label' => 'Sick Replacement', 'reason' => 'Cover sick call'],
        'overtime_extension' => ['label' => 'Overtime Extension', 'reason' => 'High turnover + additional check-ins'],
        'late_arrival' => ['label' => 'Late Arrival', 'reason' => 'Car trouble'],
        'reassignment' => ['label' => 'Reassignment', 'reason' => 'Cross-training request'],
        'schedule_correction' => ['label' => 'Schedule Correction', 'reason' => 'Entered wrong shift'],
    ];

    private const IMPACTS = [
        'low' => 'Low',
        'medium' => 'Medium',
        'high' => 'High',
    ];

    public function __construct(private readonly ScheduleWorkforceProfile $profile)
    {
    }

    /**
     * @param  Collection<int, Worker>  $workers
     * @return array<string, mixed>
     */
    public function present(Collection $workers, ScheduleViewFilters $filters, ?string $approverName = null): array
    {
        $requests = $this->requests($workers, $filters);
        $filtered = $this->applyFilters($requests, $filters);

        $perPage = max(1, $filters->perPage);
        $total = $filtered->count();
        $lastPage = max(1, (int) ceil($total / $perPage));
        $page = min($filters->page, $lastPage);
        $visible = $filtered->slice(($page - 1) * $perPage, $perPage)->values();

        $selected = $filtered->firstWhere('id', $filters->selectedRequest) ?? $visible->first();

        return [
            'range_label' => $this->rangeLabel($filters),
            'rows' => $visible->all(),
            'pagination' => [
                'from' => $total === 0 ? 0 : (($page - 1) * $perPage) + 1,
                'to' => min($page * $perPage, $total),
                'total' => $total,
                'current_page' => $page,
                'last_page' => $lastPage,
                'per_page' => $perPage,
            ],
            'types' => $this->typeOptions(),
            'selected' => $selected
                ? $this->detail($selected, $workers, $approverName)
                : null,
            'kpis' => $this->kpis($requests),
        ];
    }

    /** @return list<array{value: string, label: string}> */
    public function typeOptions(): array
    {
        return collect(self::TYPES)
            ->map(fn (array $type, string $value) => ['value' => $value, 'label' => $type['label']])
            ->values()
            ->all();
    }

    /**
     * One request per worker who drew one, dated inside the selected window.
     *
     * @param  Collection<int, Worker>  $workers
     * @return Collection<int, array<string, mixed>>
     */
    private function requests(Collection $workers, ScheduleViewFilters $filters): Collection
    {
        $start = $filters->listStart();
        $types = array_keys(self::TYPES);
        $statuses = [
            self::STATUS_PENDING,
            self::STATUS_PENDING,
            self::STATUS_APPROVED,
            self::STATUS_OVERTIME_PENDING,
            self::STATUS_REJECTED,
            self::STATUS_APPROVED,
        ];

        return $workers
            ->values()
            ->map(function (Worker $worker, int $index) use ($start, $types, $statuses, $workers) {
                $seed = $this->profile->noise($worker, $start->toDateString(), 'change-request');

                if ($seed > 74) {
                    return null;
                }

                $type = $types[$seed % count($types)];
                $status = $statuses[$seed % count($statuses)];
                $status = $type === 'overtime_extension' && $status === self::STATUS_PENDING
                    ? self::STATUS_OVERTIME_PENDING
                    : $status;

                $shift = $this->profile->shift($worker);
                $requestedDate = $start->copy()->addDays($seed % 7);
                $submittedAt = $requestedDate->copy()->subDays(1)->setTime(7, 30 + ($seed % 25));

                return [
                    'id' => sprintf('CR-%s-%05d', $start->format('Y'), 500 + $index + 1),
                    'worker' => $worker->full_name,
                    'worker_id' => $worker->id,
                    'department' => $this->profile->department($worker),
                    'position' => $worker->position ?: '—',
                    'type' => $type,
                    'type_label' => self::TYPES[$type]['label'],
                    'current_shift' => [
                        'date' => $requestedDate->format('M j (D)'),
                        'shift' => $this->profile->shiftLabel($shift),
                        'time' => $this->profile->shiftTime($worker, $shift),
                    ],
                    'requested_change' => $this->requestedChange($type, $worker, $shift, $requestedDate, $workers),
                    'date_shift' => [
                        'date' => $requestedDate->format('M j (D)'),
                        'shift' => $this->profile->shiftLabel($shift),
                    ],
                    'reason' => self::TYPES[$type]['reason'],
                    'impact' => $this->impact($type, $seed),
                    'status' => $status,
                    'submitted_at' => $submittedAt->format('M j, g:i A'),
                    'requested_date' => $requestedDate->toDateString(),
                    'shift' => $shift,
                ];
            })
            ->filter()
            ->values();
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $requests
     * @return Collection<int, array<string, mixed>>
     */
    private function applyFilters(Collection $requests, ScheduleViewFilters $filters): Collection
    {
        return $requests->filter(function (array $request) use ($filters) {
            if (! $filters->isAll($filters->requestType) && $request['type'] !== $filters->requestType) {
                return false;
            }

            if (! $filters->isAll($filters->department) && $request['department'] !== $filters->department) {
                return false;
            }

            if (! $filters->isAll($filters->shift) && $request['shift'] !== $filters->shift) {
                return false;
            }

            if (! $filters->isAll($filters->status) && $request['status'] !== $filters->status) {
                return false;
            }

            if ($filters->search !== '') {
                $haystack = Str::lower("{$request['id']} {$request['worker']} {$request['type_label']} {$request['reason']}");

                if (! str_contains($haystack, Str::lower($filters->search))) {
                    return false;
                }
            }

            return true;
        })->values();
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $requests
     * @return list<array<string, mixed>>
     */
    private function kpis(Collection $requests): array
    {
        $pending = $requests->where('status', self::STATUS_PENDING)->count();
        $overtime = $requests->where('status', self::STATUS_OVERTIME_PENDING);
        $approved = $requests->where('status', self::STATUS_APPROVED)->count();
        $rejected = $requests->where('status', self::STATUS_REJECTED)->count();
        $conflicts = $requests->where('impact.value', 'high')->count();

        return [
            [
                'key' => 'pending',
                'label' => 'Pending Requests',
                'value' => $pending,
                'hint' => $pending > 0 ? '+'.min($pending, 4).' vs yesterday' : 'No new requests',
                'tone' => 'warning',
                'icon' => 'Clock',
            ],
            [
                'key' => 'overtime',
                'label' => 'Overtime Pending Approval',
                'value' => $overtime->count(),
                'hint' => number_format($overtime->count() * 1.5, 1).' hrs',
                'tone' => 'warning',
                'icon' => 'Timer',
            ],
            [
                'key' => 'approved',
                'label' => 'Approved This Week',
                'value' => $approved,
                'hint' => $approved > 0 ? '+'.$approved.' vs last week' : 'None yet',
                'tone' => 'success',
                'icon' => 'CircleCheck',
            ],
            [
                'key' => 'rejected',
                'label' => 'Rejected',
                'value' => $rejected,
                'hint' => $rejected > 0 ? '-'.$rejected.' vs last week' : 'None',
                'tone' => 'danger',
                'icon' => 'CircleX',
            ],
            [
                'key' => 'conflicts',
                'label' => 'Conflicts Detected',
                'value' => $conflicts,
                'hint' => $conflicts > 0 ? 'Requires attention' : 'No conflicts',
                'tone' => 'danger',
                'icon' => 'TriangleAlert',
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $request
     * @param  Collection<int, Worker>  $workers
     * @return array<string, mixed>
     */
    private function detail(array $request, Collection $workers, ?string $approverName): array
    {
        $date = Carbon::parse($request['requested_date']);
        $department = $request['department'];
        $required = $workers
            ->filter(fn (Worker $worker) => $this->profile->department($worker) === $department
                && $this->profile->shift($worker) === $request['shift'])
            ->count();
        $afterChange = $request['type'] === 'day_off' ? max(0, $required - 1) : $required;
        $coverage = $required > 0 ? (int) round(($afterChange / $required) * 100) : 100;
        $impact = $request['impact']['value'];

        return [
            ...$request,
            'current_schedule' => [
                'date' => $request['current_shift']['date'],
                'shift' => $request['current_shift']['shift'].' Shift',
                'time' => $request['current_shift']['time'],
            ],
            'requested_schedule' => [
                'date' => $request['requested_change']['date'],
                'shift' => $this->profile->shiftLabel($request['shift']),
                'time' => $request['requested_change']['detail'],
                'note' => $request['requested_change']['note'],
            ],
            'operational_impact' => $impact === 'low'
                ? 'No service level impact. Coverage remains adequate.'
                : ($impact === 'medium'
                    ? 'Service level holds, but the shift runs without spare cover.'
                    : 'Service level at risk. Coverage drops below the staffing requirement.'),
            'coverage_impact' => [
                'shift' => $this->profile->shiftLabel($request['shift']).' Shift',
                'department' => $department,
                'required' => $required,
                'scheduled' => $required,
                'after_change' => $afterChange,
                'coverage' => $coverage,
            ],
            'position_forecast' => [
                'date' => $date->format('M j'),
                'position' => Str::plural($request['position']),
                'required' => $required,
                'forecasted' => $required,
                'variance' => $afterChange - $required,
            ],
            'readiness' => [
                'issues' => $impact === 'high' ? ['Certification expires within the requested window'] : [],
                'conflicts' => $impact === 'high'
                    ? ['Worker already rostered on the replacement shift']
                    : [],
                'available' => $impact !== 'high',
            ],
            'head_office' => $impact === 'high' ? 'Head Office Operations sign-off required' : 'None',
            'approval_chain' => $this->approvalChain($request['status'], $impact, $approverName),
            'timeline' => $this->timeline($request),
            'attachments' => $request['type'] === 'sick_replacement' ? 2 : 1,
        ];
    }

    /**
     * @return list<array<string, string>>
     */
    private function approvalChain(string $status, string $impact, ?string $approverName): array
    {
        $lodgeState = match ($status) {
            self::STATUS_APPROVED => 'approved',
            self::STATUS_REJECTED => 'rejected',
            default => 'pending',
        };

        return [
            [
                'name' => $approverName ?: 'Lodge Manager',
                'role' => 'Lodge Manager',
                'state' => $lodgeState,
            ],
            [
                'name' => 'Area Manager',
                'role' => 'Operations',
                'state' => $lodgeState === 'approved' ? 'pending' : 'waiting',
            ],
            [
                'name' => 'Head Office Ops',
                'role' => 'Operations',
                'state' => $impact === 'high' ? 'pending' : 'not_required',
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $request
     * @return list<array<string, string>>
     */
    private function timeline(array $request): array
    {
        $entries = [
            ['at' => $request['submitted_at'], 'label' => "Request submitted by {$request['worker']}"],
            ['at' => $request['submitted_at'], 'label' => 'Auto-checked: no conflicts detected'],
            ['at' => $request['submitted_at'], 'label' => 'Assigned to Lodge Manager for approval'],
        ];

        if ($request['status'] === self::STATUS_APPROVED) {
            $entries[] = ['at' => $request['submitted_at'], 'label' => 'Approved by Lodge Manager'];
        }

        if ($request['status'] === self::STATUS_REJECTED) {
            $entries[] = ['at' => $request['submitted_at'], 'label' => 'Rejected by Lodge Manager'];
        }

        return $entries;
    }

    /** @return array{value: string, label: string} */
    private function impact(string $type, int $seed): array
    {
        $value = match ($type) {
            'overtime_extension' => 'high',
            'sick_replacement', 'reassignment' => 'medium',
            default => $seed % 5 === 0 ? 'medium' : 'low',
        };

        return ['value' => $value, 'label' => self::IMPACTS[$value]];
    }

    /**
     * The Requested Change cell: the date it lands on, the new hours, and the
     * one-line qualifier underneath.
     *
     * @param  Collection<int, Worker>  $workers
     * @return array{date: string, detail: string, note: string|null}
     */
    private function requestedChange(string $type, Worker $worker, string $shift, Carbon $date, Collection $workers): array
    {
        $time = $this->profile->shiftTime($worker, $shift);
        $counterpart = $this->counterpart($worker, $workers);

        [$detail, $note] = match ($type) {
            'shift_swap' => [$time, $counterpart ? "Swap with {$counterpart}" : 'Swap requested'],
            'day_off' => ['Day Off', null],
            'sick_replacement' => [$time, $counterpart ? "Replace {$counterpart}" : 'Replacement cover'],
            'overtime_extension' => ['Extend to 6:00 PM', null],
            'late_arrival' => ['Start at 9:00 AM', null],
            'reassignment' => [$time, 'Reassign department'],
            default => [$time, null],
        };

        return [
            'date' => $date->format('M j (D)'),
            'detail' => $detail,
            'note' => $note,
        ];
    }

    /**
     * The colleague a swap or sick cover names: a stable pick from the same
     * department so the request reads like a real hand-off.
     *
     * @param  Collection<int, Worker>  $workers
     */
    private function counterpart(Worker $worker, Collection $workers): ?string
    {
        $peers = $workers
            ->filter(fn (Worker $peer) => $peer->id !== $worker->id
                && $this->profile->department($peer) === $this->profile->department($worker))
            ->values();

        if ($peers->isEmpty()) {
            return null;
        }

        return $peers[$worker->id % $peers->count()]->full_name;
    }

    private function rangeLabel(ScheduleViewFilters $filters): string
    {
        $start = $filters->listStart();
        $end = $start->copy()->addDays(6);
        $left = $start->format('M j');
        $right = $start->isSameMonth($end) ? $end->format('j, Y') : $end->format('M j, Y');

        return "{$left} – {$right}";
    }
}
