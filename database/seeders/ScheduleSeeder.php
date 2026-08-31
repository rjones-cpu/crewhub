<?php

namespace Database\Seeders;

use App\Enums\ScheduleDayType;
use App\Models\Accommodation;
use App\Models\AccommodationAssignment;
use App\Models\MajorProject;
use App\Models\Worker;
use App\Models\WorkerScheduleDay;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Builds rotation coverage for the read-only schedule board.
 *
 * No-op until workers exist. Safe to re-run: a worker's generated days are
 * replaced rather than appended, so rotations never double up.
 */
class ScheduleSeeder extends Seeder
{
    private const DAYS_BACK = 30;

    private const DAYS_FORWARD = 80;

    /** [work days on site, days off] rotations mirrored from Camp. */
    private const ROTATIONS = [[21, 7], [14, 14], [20, 8], [14, 7]];

    public function run(): void
    {
        $workers = Worker::query()
            ->whereNotNull('primary_project_id')
            ->orderBy('id')
            ->get();

        if ($workers->isEmpty()) {
            return;
        }

        $start = Carbon::today()->subDays(self::DAYS_BACK);
        $end = Carbon::today()->addDays(self::DAYS_FORWARD);
        $today = Carbon::today()->toDateString();
        $lodges = $this->lodgesByProject($workers->pluck('primary_project_id')->unique());

        // Regenerate from scratch so re-runs stay idempotent.
        WorkerScheduleDay::query()->whereIn('worker_id', $workers->pluck('id'))->delete();

        $rows = [];
        $timestamp = now();

        foreach ($workers->values() as $index => $worker) {
            [$onDays, $offDays] = self::ROTATIONS[$index % count(self::ROTATIONS)];
            $cycle = $onDays + $offDays;
            // Stagger each worker so the board shows overlapping rotations rather
            // than every bar starting on the same date.
            $offset = ($index * 5) % $cycle;
            $todayType = null;

            // Counted rather than derived from diffInDays(), which is signed in
            // Carbon 3 and silently pushed every position negative.
            $dayNumber = 0;

            for ($date = $start->copy(); $date->lte($end); $date->addDay(), $dayNumber++) {
                $position = ($dayNumber + $offset) % $cycle;

                // Off days carry no row at all, so the board leaves them white.
                if ($position >= $onDays) {
                    continue;
                }

                $isArrival = $position === 0;
                $isDeparture = $position === $onDays - 1;
                $type = $isArrival || $isDeparture ? ScheduleDayType::Travel : ScheduleDayType::Work;

                if ($date->toDateString() === $today) {
                    $todayType = $isDeparture ? 'departing' : ($isArrival ? 'arriving' : 'working');
                }

                $rows[] = [
                    'company_id' => $worker->company_id,
                    'worker_id' => $worker->id,
                    'major_project_id' => $worker->primary_project_id,
                    'date' => $date->toDateString(),
                    'day_type' => $type->value,
                    // A departure day frees the bed that night.
                    'needs_room' => ! $isDeparture,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ];
            }

            $this->syncAccommodation($worker, $lodges[$worker->primary_project_id] ?? null, $todayType);
        }

        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table('worker_schedule_days')->insert($chunk);
        }
    }

    /**
     * One lodge per project so the Accommodation Status column has a real stay to
     * read from.
     *
     * @param  \Illuminate\Support\Collection<int, int>  $projectIds
     * @return array<int, Accommodation>
     */
    private function lodgesByProject($projectIds): array
    {
        $lodges = [];

        foreach (MajorProject::query()->whereIn('id', $projectIds)->get() as $project) {
            $lodges[$project->id] = Accommodation::query()->firstOrCreate(
                [
                    'company_id' => $project->company_id,
                    'major_project_id' => $project->id,
                    'name' => "{$project->name} Lodge",
                ],
                [
                    'location' => $project->location ?: 'Site camp',
                    'capacity' => 120,
                    'status' => 'active',
                ],
            );
        }

        return $lodges;
    }

    private function syncAccommodation(Worker $worker, ?Accommodation $lodge, ?string $todayType): void
    {
        if (! $lodge) {
            return;
        }

        $status = match ($todayType) {
            'arriving' => 'reserved',
            'working' => 'checked_in',
            default => 'checked_out',
        };

        AccommodationAssignment::query()->updateOrCreate(
            [
                'worker_id' => $worker->id,
                'accommodation_id' => $lodge->id,
            ],
            [
                'company_id' => $worker->company_id,
                'room_number' => str_pad((string) (($worker->id % 120) + 1), 3, '0', STR_PAD_LEFT),
                'check_in' => Carbon::today()->subDays(7)->toDateString(),
                'check_out' => Carbon::today()->addDays(14)->toDateString(),
                'status' => $status,
            ],
        );
    }
}
