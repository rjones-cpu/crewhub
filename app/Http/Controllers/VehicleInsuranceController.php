<?php

namespace App\Http\Controllers;

use App\Enums\InsuranceStatus;
use App\Http\Resources\VehicleResource;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class VehicleInsuranceController extends Controller
{
    /**
     * @var list<int>
     */
    private const PER_PAGE_OPTIONS = [10, 25, 50, 100];

    /** Policies inside this window are treated as expiring rather than valid. */
    private const EXPIRING_DAYS = 30;

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Vehicle::class);

        $search = trim((string) $request->query('search', ''));
        $status = (string) $request->query('status', '');
        $cover = (string) $request->query('cover', '');
        $perPage = (int) $request->query('per_page', self::PER_PAGE_OPTIONS[0]);

        if (! in_array($perPage, self::PER_PAGE_OPTIONS, true)) {
            $perPage = self::PER_PAGE_OPTIONS[0];
        }

        $query = Vehicle::query()
            ->with(['assignedDriver', 'insuranceVerifier'])
            ->orderByRaw('policy_end_date is null desc')
            ->orderBy('policy_end_date');

        if ($search !== '') {
            $query->where(function (Builder $inner) use ($search): void {
                $inner->where('make', 'like', "%{$search}%")
                    ->orWhere('model', 'like', "%{$search}%")
                    ->orWhere('license_plate', 'like', "%{$search}%")
                    ->orWhere('insurance_provider', 'like', "%{$search}%")
                    ->orWhere('policy_number', 'like', "%{$search}%");
            });
        }

        if ($status !== '') {
            $query->where('insurance_status', $status);
        }

        $this->applyCoverFilter($query, $cover);

        return Inertia::render('Journeys/Insurance/Index', [
            'vehicles' => VehicleResource::collection($query->paginate($perPage)->withQueryString()),
            'stats' => $this->stats(),
            'filters' => [
                'search' => $search,
                'status' => $status,
                'cover' => $cover,
                'per_page' => $perPage,
            ],
            'canManage' => $request->user()->can('create', Vehicle::class),
        ]);
    }

    public function confirm(Request $request, Vehicle $vehicle): RedirectResponse
    {
        $this->authorize('update', $vehicle);

        $validated = $request->validate([
            'status' => ['required', Rule::enum(InsuranceStatus::class)],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        $vehicle->update([
            'insurance_status' => $validated['status'],
            'insurance_verification_notes' => $validated['notes'] ?? null,
            'insurance_verified_at' => now(),
            'insurance_verified_by' => $request->user()->id,
        ]);

        return back()->with(
            'success',
            $validated['status'] === InsuranceStatus::Confirmed->value
                ? 'Insurance confirmed.'
                : 'Insurance flagged for follow-up.',
        );
    }

    private function applyCoverFilter(Builder $query, string $cover): void
    {
        $today = now()->toDateString();
        $horizon = now()->addDays(self::EXPIRING_DAYS)->toDateString();

        match ($cover) {
            'expired' => $query->whereNotNull('policy_end_date')->whereDate('policy_end_date', '<', $today),
            'expiring' => $query->whereBetween('policy_end_date', [$today, $horizon]),
            'valid' => $query->whereDate('policy_end_date', '>', $horizon),
            'missing' => $query->whereNull('policy_end_date'),
            default => null,
        };
    }

    /**
     * @return array{total: int, confirmed: int, awaiting: int, expiring: int, expired: int}
     */
    private function stats(): array
    {
        $base = Vehicle::query();
        $today = now()->toDateString();

        return [
            'total' => (clone $base)->count(),
            'confirmed' => (clone $base)->where('insurance_status', InsuranceStatus::Confirmed)->count(),
            'awaiting' => (clone $base)->where('insurance_status', InsuranceStatus::Unverified)->count(),
            'expiring' => (clone $base)
                ->whereBetween('policy_end_date', [$today, now()->addDays(self::EXPIRING_DAYS)->toDateString()])
                ->count(),
            'expired' => (clone $base)
                ->whereNotNull('policy_end_date')
                ->whereDate('policy_end_date', '<', $today)
                ->count(),
        ];
    }
}
