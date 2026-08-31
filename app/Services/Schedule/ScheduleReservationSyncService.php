<?php

namespace App\Services\Schedule;

use App\Enums\ScheduleDayType;
use App\Models\Accommodation;
use App\Models\AccommodationAssignment;
use App\Models\MajorProject;
use App\Models\Worker;
use App\Models\WorkerScheduleDay;
use Illuminate\Support\Carbon;

/**
 * Crew Hub stand-in for camp.site reservations: publish writes an
 * AccommodationAssignment against the project lodge, using the live rotation
 * as arrival / departure.
 */
class ScheduleReservationSyncService
{
    public function currentAssignment(Worker $worker, MajorProject $project): ?AccommodationAssignment
    {
        $lodge = $this->lodgeFor($project);

        if (! $lodge) {
            return $worker->latestAccommodation;
        }

        return AccommodationAssignment::query()
            ->where('worker_id', $worker->id)
            ->where('accommodation_id', $lodge->id)
            ->latest('check_in')
            ->first();
    }

    /**
     * @return array{check_in: string|null, check_out: string|null, status: string}
     */
    public function sync(Worker $worker, MajorProject $project): array
    {
        $lodge = $this->lodgeFor($project);
        $span = $this->staySpan($worker, $project);

        if (! $lodge || $span['check_in'] === null) {
            $assignment = $this->currentAssignment($worker, $project);

            if ($assignment) {
                $assignment->update(['status' => 'checked_out']);
            }

            return [
                'check_in' => $assignment?->check_in?->toDateString(),
                'check_out' => $assignment?->check_out?->toDateString() ?? Carbon::today()->toDateString(),
                'status' => 'checked_out',
            ];
        }

        $assignment = AccommodationAssignment::query()->updateOrCreate(
            [
                'worker_id' => $worker->id,
                'accommodation_id' => $lodge->id,
            ],
            [
                'company_id' => $worker->company_id,
                'room_number' => $this->currentAssignment($worker, $project)?->room_number
                    ?? str_pad((string) (($worker->id % 120) + 1), 3, '0', STR_PAD_LEFT),
                'check_in' => $span['check_in'],
                'check_out' => $span['check_out'],
                'status' => $span['status'],
            ],
        );

        $this->refreshOccupied($lodge);

        return [
            'check_in' => $assignment->check_in->toDateString(),
            'check_out' => $assignment->check_out?->toDateString(),
            'status' => $assignment->status,
        ];
    }

    public function refineNeedsRoom(Worker $worker, MajorProject $project): void
    {
        $days = WorkerScheduleDay::query()
            ->where('worker_id', $worker->id)
            ->where('major_project_id', $project->id)
            ->orderBy('date')
            ->get()
            ->keyBy(fn (WorkerScheduleDay $day) => $day->date->toDateString());

        foreach ($days as $date => $day) {
            if ($day->day_type !== ScheduleDayType::Travel) {
                $day->needs_room = $day->day_type !== ScheduleDayType::Off;
                $day->save();

                continue;
            }

            $next = Carbon::parse($date)->addDay()->toDateString();
            $previous = Carbon::parse($date)->subDay()->toDateString();
            $nextType = $days[$next]->day_type ?? null;
            $previousType = $days[$previous]->day_type ?? null;

            // Departure travel (work behind, nothing scheduled ahead) frees the bed.
            $isDeparture = $previousType === ScheduleDayType::Work
                && ($nextType === null || $nextType === ScheduleDayType::Off);

            $day->needs_room = ! $isDeparture;
            $day->save();
        }
    }

    /**
     * @return array{check_in: string|null, check_out: string|null, status: string}
     */
    public function staySpan(Worker $worker, MajorProject $project): array
    {
        $days = WorkerScheduleDay::query()
            ->where('worker_id', $worker->id)
            ->where('major_project_id', $project->id)
            ->whereIn('day_type', [ScheduleDayType::Work, ScheduleDayType::Travel])
            ->orderBy('date')
            ->get();

        if ($days->isEmpty()) {
            return ['check_in' => null, 'check_out' => null, 'status' => 'checked_out'];
        }

        $checkIn = $days->first()->date->toDateString();
        $checkOut = $days->last()->date->toDateString();
        $today = Carbon::today()->toDateString();
        $todayRow = $days->first(fn (WorkerScheduleDay $day) => $day->date->toDateString() === $today);

        $status = match (true) {
            $today < $checkIn => 'reserved',
            $today > $checkOut => 'checked_out',
            $todayRow?->day_type === ScheduleDayType::Travel && $today === $checkIn => 'reserved',
            default => 'checked_in',
        };

        return [
            'check_in' => $checkIn,
            'check_out' => $checkOut,
            'status' => $status,
        ];
    }

    public function lodgeFor(MajorProject $project): ?Accommodation
    {
        return Accommodation::query()
            ->where('major_project_id', $project->id)
            ->orderBy('id')
            ->first()
            ?? Accommodation::query()->firstOrCreate(
                [
                    'company_id' => $project->company_id,
                    'major_project_id' => $project->id,
                    'name' => "{$project->name} Lodge",
                ],
                [
                    'location' => $project->location ?: 'Site camp',
                    'capacity' => 120,
                    'occupied' => 0,
                    'status' => 'active',
                ],
            );
    }

    private function refreshOccupied(Accommodation $lodge): void
    {
        $lodge->update([
            'occupied' => AccommodationAssignment::query()
                ->where('accommodation_id', $lodge->id)
                ->whereIn('status', ['reserved', 'checked_in'])
                ->count(),
        ]);
    }
}
