<?php

namespace App\Services\Journeys;

use App\Enums\JourneyRisk;
use App\Enums\JourneyStatus;
use App\Models\Journey;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpFoundation\StreamedResponse;

class JourneyService
{
    /**
     * @var list<int>
     */
    public const PER_PAGE_OPTIONS = [10, 25, 50, 100];

    public function __construct(private RiskAssessmentService $assessments)
    {
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginate(array $filters): LengthAwarePaginator
    {
        return $this->filteredQuery($filters)
            ->with(['worker', 'majorProject'])
            ->latest('departure_at')
            ->paginate($this->perPage($filters['per_page'] ?? null))
            ->withQueryString();
    }

    /**
     * Company-wide totals for the KPI cards (not limited to the current page).
     *
     * @return array{total: int, planned: int, en_route: int, high_risk: int}
     */
    public function stats(): array
    {
        $base = Journey::query();

        return [
            'total' => (clone $base)->count(),
            'planned' => (clone $base)->where('status', JourneyStatus::Approved)->count(),
            'en_route' => (clone $base)->where('status', JourneyStatus::InTransit)->count(),
            'high_risk' => (clone $base)->where('risk_level', JourneyRisk::High)->count(),
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function create(array $payload, User $user): Journey
    {
        $origin = $payload['origin'];
        $destination = $payload['destination'];

        // Journeys enter the workflow unapproved. Scoring them immediately lets the
        // risk engine release the low-risk ones without waiting on a manager.
        $status = JourneyStatus::Pending;

        $journey = Journey::query()->create([
            'company_id' => $user->company_id,
            'worker_id' => $payload['worker_id'],
            'major_project_id' => $payload['major_project_id'] ?? null,
            'code' => $this->nextCode((int) $user->company_id),
            'type' => $payload['type'] ?? 'transfer',
            'origin' => $origin,
            'destination' => $destination,
            'vehicle_plate' => $payload['vehicle_plate'] ?? null,
            'vehicle_model' => $payload['vehicle_model'] ?? null,
            'hub' => $payload['hub'] ?? null,
            'distance_km' => $payload['distance_km'] ?? null,
            'departure_at' => $payload['departure_at'],
            'arrival_at' => $payload['arrival_at'] ?? null,
            'status' => $status,
            'emergency_contact_name' => $payload['emergency_contact_name'] ?? null,
            'emergency_contact_phone' => $payload['emergency_contact_phone'] ?? null,
            'checkpoints' => $this->checkpointsFor($origin, $destination, $status),
        ]);

        $this->assessments->assess($journey, $user);

        return $journey->fresh(['worker', 'majorProject']);
    }

    public function updateStatus(Journey $journey, JourneyStatus $status): Journey
    {
        $journey->status = $status;

        if ($status === JourneyStatus::Approved && ! $journey->approved_by) {
            $journey->approved_by = auth()->id();
        }

        $journey->checkpoints = $this->checkpointsFor(
            $journey->origin,
            $journey->destination,
            $status,
            $journey->departure_at?->toIso8601String(),
            $journey->arrival_at?->toIso8601String(),
        );
        $journey->save();

        return $journey->fresh(['worker', 'majorProject', 'approver']);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function export(array $filters): StreamedResponse
    {
        $rows = $this->filteredQuery($filters)
            ->with('worker')
            ->latest('departure_at')
            ->get();

        return response()->streamDownload(function () use ($rows): void {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'Journey ID',
                'Driver',
                'Vehicle',
                'Origin',
                'Destination',
                'Departure',
                'ETA',
                'Risk',
                'Status',
                'Hub',
            ]);

            foreach ($rows as $journey) {
                fputcsv($handle, [
                    $journey->code,
                    $journey->worker?->full_name,
                    trim($journey->vehicle_plate.' '.$journey->vehicle_model),
                    $journey->origin,
                    $journey->destination,
                    $journey->departure_at?->toDateTimeString(),
                    $journey->arrival_at?->toDateTimeString(),
                    $journey->risk_level?->label(),
                    $journey->status?->label(),
                    $journey->hub,
                ]);
            }

            fclose($handle);
        }, 'journeys.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function filteredQuery(array $filters): Builder
    {
        $query = Journey::query();

        $search = trim((string) ($filters['search'] ?? ''));
        $status = $filters['status'] ?? null;
        $risk = $filters['risk'] ?? null;
        $from = $filters['from'] ?? null;
        $to = $filters['to'] ?? null;

        if ($search !== '') {
            $query->where(function (Builder $inner) use ($search): void {
                $inner->where('code', 'like', "%{$search}%")
                    ->orWhere('origin', 'like', "%{$search}%")
                    ->orWhere('destination', 'like', "%{$search}%")
                    ->orWhere('vehicle_plate', 'like', "%{$search}%")
                    ->orWhere('hub', 'like', "%{$search}%")
                    ->orWhereHas('worker', function (Builder $worker) use ($search): void {
                        $worker->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%");
                    });
            });
        }

        if ($status) {
            $query->where('status', $status);
        }

        if ($risk) {
            $query->where('risk_level', $risk);
        }

        if ($from) {
            $query->whereDate('departure_at', '>=', $from);
        }

        if ($to) {
            $query->whereDate('departure_at', '<=', $to);
        }

        return $query;
    }

    private function perPage(mixed $value): int
    {
        $perPage = (int) $value;

        return in_array($perPage, self::PER_PAGE_OPTIONS, true)
            ? $perPage
            : self::PER_PAGE_OPTIONS[0];
    }

    private function nextCode(int $companyId): string
    {
        $year = now()->year;
        $sequence = Journey::withTrashed()
            ->where('company_id', $companyId)
            ->whereYear('created_at', $year)
            ->count() + 1;

        return sprintf('JRN-%d-%04d', $year, $sequence);
    }

    /**
     * @return list<array{name: string, status: string, occurred_at: string|null}>
     */
    private function checkpointsFor(
        string $origin,
        string $destination,
        JourneyStatus $status,
        ?string $departedAt = null,
        ?string $arrivedAt = null,
    ): array {
        $mid = 'En route';

        return match ($status) {
            JourneyStatus::Completed => [
                ['name' => $origin, 'status' => 'completed', 'occurred_at' => $departedAt],
                ['name' => $mid, 'status' => 'completed', 'occurred_at' => $departedAt],
                ['name' => $destination, 'status' => 'completed', 'occurred_at' => $arrivedAt],
            ],
            JourneyStatus::InTransit => [
                ['name' => $origin, 'status' => 'completed', 'occurred_at' => $departedAt],
                ['name' => $mid, 'status' => 'in_progress', 'occurred_at' => null],
                ['name' => $destination, 'status' => 'pending', 'occurred_at' => null],
            ],
            default => [
                ['name' => $origin, 'status' => 'pending', 'occurred_at' => null],
                ['name' => $mid, 'status' => 'pending', 'occurred_at' => null],
                ['name' => $destination, 'status' => 'pending', 'occurred_at' => null],
            ],
        };
    }
}
