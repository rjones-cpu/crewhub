<?php

namespace App\Services\Schedule\Views;

use Illuminate\Support\Carbon;

/**
 * The filter bar state shared by every Schedule view. Held as one object so the
 * controller validates the query string once and each presenter reads the same
 * window, department, and shift selection.
 */
class ScheduleViewFilters
{
    public const VIEW_BOARD = 'board';
    public const VIEW_LIST = 'list';
    public const VIEW_CALENDAR = 'calendar';
    public const VIEW_REQUESTS = 'requests';

    public const ALL = 'all';

    public function __construct(
        public readonly string $view = self::VIEW_LIST,
        public readonly ?int $projectId = null,
        public readonly string $department = self::ALL,
        public readonly string $shift = self::ALL,
        public readonly string $status = self::ALL,
        public readonly string $requestType = self::ALL,
        public readonly string $search = '',
        public readonly ?Carbon $weekStart = null,
        public readonly int $page = 1,
        public readonly int $perPage = 15,
        public readonly ?string $selectedRequest = null,
    ) {
    }

    /**
     * @param  array<string, mixed>  $query
     */
    public static function fromQuery(array $query): self
    {
        $weekStart = isset($query['week'])
            ? Carbon::parse($query['week'])->startOfDay()
            : Carbon::today();

        return new self(
            view: $query['view'] ?? self::VIEW_LIST,
            projectId: isset($query['project_id']) ? (int) $query['project_id'] : null,
            department: $query['department'] ?? self::ALL,
            shift: $query['shift'] ?? self::ALL,
            status: $query['status'] ?? self::ALL,
            requestType: $query['request_type'] ?? self::ALL,
            search: trim((string) ($query['search'] ?? '')),
            weekStart: $weekStart,
            page: max(1, (int) ($query['page'] ?? 1)),
            perPage: (int) ($query['per_page'] ?? 15),
            selectedRequest: $query['request'] ?? null,
        );
    }

    /** First day of the seven-day list window. */
    public function listStart(): Carbon
    {
        return ($this->weekStart ?? Carbon::today())->copy()->startOfDay();
    }

    /** The calendar always opens on a Monday so its two weeks line up Mon–Sun. */
    public function calendarStart(): Carbon
    {
        return ($this->weekStart ?? Carbon::today())->copy()->startOfWeek(Carbon::MONDAY);
    }

    public function isAll(string $value): bool
    {
        return $value === self::ALL || $value === '';
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'view' => $this->view,
            'project_id' => $this->projectId,
            'department' => $this->department,
            'shift' => $this->shift,
            'status' => $this->status,
            'request_type' => $this->requestType,
            'search' => $this->search,
            'week' => $this->listStart()->toDateString(),
            'page' => $this->page,
            'per_page' => $this->perPage,
            'request' => $this->selectedRequest,
        ];
    }
}
