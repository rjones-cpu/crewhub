<?php

namespace App\Services\Schedule;

use App\Enums\ScheduleDayType;
use App\Enums\ScheduleDraftStatus;
use App\Enums\ScheduleModificationStatus;
use App\Models\Accommodation;
use App\Models\AccommodationAssignment;
use App\Models\MajorProject;
use App\Models\ScheduleModificationRequest;
use App\Models\User;
use App\Models\Worker;
use App\Models\WorkerScheduleDay;
use App\Models\WorkerScheduleDraft;
use App\Models\WorkerScheduleDraftDay;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ScheduleEditService
{
    public function __construct(
        private readonly ScheduleDragRules $rules,
        private readonly ScheduleReservationSyncService $reservations,
    ) {
    }

    /**
     * @param  array<string, string>  $rowTypes
     * @return array{mode: string, draft_id: int|null, cells: array<string, string>, pending: list<string>}
     */
    public function applyDrag(
        User $user,
        Worker $worker,
        MajorProject $project,
        string $sourceDate,
        string $dropDate,
        array $rowTypes,
        bool $backDragRevert = false,
    ): array {
        $this->assertEditable($user, $worker, $project);

        $outcome = $this->rules->resolve($sourceDate, $dropDate, $rowTypes, $backDragRevert);

        if ($outcome['mode'] === ScheduleDragRules::MODE_SELECTION) {
            return [
                'mode' => $outcome['mode'],
                'draft_id' => $this->pendingDraft($worker, $project)?->id,
                'cells' => [],
                'pending' => $this->pendingDates($worker, $project),
            ];
        }

        return $this->commitCells($user, $worker, $project, $outcome['cells'], $backDragRevert);
    }

    /**
     * @param  list<string>  $dates
     * @return array{mode: string, draft_id: int|null, cells: array<string, string>, pending: list<string>}
     */
    public function applyPaint(
        User $user,
        Worker $worker,
        MajorProject $project,
        array $dates,
        string $type,
        array $needsRoomByDate = [],
    ): array {
        $this->assertEditable($user, $worker, $project);

        $dayType = ScheduleDayType::from($type);
        $outcome = $this->rules->paint($dates, $dayType->value);

        return $this->commitCells(
            $user,
            $worker,
            $project,
            $outcome['cells'],
            backDragRevert: false,
            needsRoomByDate: $needsRoomByDate,
        );
    }

    /**
     * @return array{cleared: int}
     */
    public function resetAll(User $user, ?int $projectId): array
    {
        $this->assertCanEdit($user);

        $query = WorkerScheduleDraft::query()->where('status', ScheduleDraftStatus::Pending);

        if ($user->company_id) {
            $query->where('company_id', $user->company_id);
        }

        if ($projectId) {
            $query->where('major_project_id', $projectId);
        }

        $count = $query->count();
        $query->delete();

        return ['cleared' => $count];
    }

    /**
     * @return array{published: int, reservations: int}
     */
    public function publishAll(User $user, ?int $projectId): array
    {
        $this->assertCanEdit($user);

        $drafts = WorkerScheduleDraft::query()
            ->with(['days', 'worker', 'majorProject'])
            ->where('status', ScheduleDraftStatus::Pending)
            ->where('company_id', $user->company_id)
            ->when($projectId, fn ($query) => $query->where('major_project_id', $projectId))
            ->get();

        $published = 0;
        $reservations = 0;

        DB::transaction(function () use ($user, $drafts, &$published, &$reservations): void {
            foreach ($drafts as $draft) {
                $this->publishDraft($user, $draft);
                $published++;
                $reservations++;
            }
        });

        return ['published' => $published, 'reservations' => $reservations];
    }

    public function acknowledge(User $user, ScheduleModificationRequest $request): void
    {
        if (! $user->isSuperAdmin() && (int) $request->company_id !== (int) $user->company_id) {
            throw new InvalidArgumentException('That modification request is not in your company.');
        }

        if (! $user->isSuperAdmin() && ! $user->role?->canManageReservations() && ! $user->role?->canManageWorkforce()) {
            throw new InvalidArgumentException('You cannot acknowledge reservation changes.');
        }

        $request->update([
            'status' => ScheduleModificationStatus::Acknowledged,
            'acknowledged_at' => now(),
        ]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function draftSummaries(?int $projectId): array
    {
        return WorkerScheduleDraft::query()
            ->with(['worker', 'majorProject', 'days'])
            ->where('status', ScheduleDraftStatus::Pending)
            ->when($projectId, fn ($query) => $query->where('major_project_id', $projectId))
            ->latest('updated_at')
            ->get()
            ->map(fn (WorkerScheduleDraft $draft) => [
                'id' => $draft->id,
                'worker_id' => $draft->worker_id,
                'worker_name' => $draft->worker?->full_name,
                'project_id' => $draft->major_project_id,
                'project_name' => $draft->majorProject?->name,
                'change_count' => $draft->days->count(),
                'summary' => $this->draftSummary($draft),
                'updated_at' => $draft->updated_at?->toIso8601String(),
            ])
            ->values()
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function requestSummaries(?int $projectId): array
    {
        return ScheduleModificationRequest::query()
            ->with(['worker', 'majorProject'])
            ->when($projectId, fn ($query) => $query->where('major_project_id', $projectId))
            ->latest()
            ->limit(50)
            ->get()
            ->map(fn (ScheduleModificationRequest $request) => [
                'id' => $request->id,
                'worker_name' => $request->worker?->full_name,
                'project_name' => $request->majorProject?->name,
                'check_in' => $request->check_in?->toDateString(),
                'check_out' => $request->check_out?->toDateString(),
                'previous_check_in' => $request->previous_check_in?->toDateString(),
                'previous_check_out' => $request->previous_check_out?->toDateString(),
                'change_count' => $request->change_count,
                'status' => $request->status->value,
                'created_at' => $request->created_at?->toIso8601String(),
            ])
            ->all();
    }

    /**
     * @param  array<string, string>  $cells
     * @param  array<string, bool>  $needsRoomByDate
     * @return array{mode: string, draft_id: int|null, cells: array<string, string>, pending: list<string>}
     */
    private function commitCells(
        User $user,
        Worker $worker,
        MajorProject $project,
        array $cells,
        bool $backDragRevert,
        array $needsRoomByDate = [],
    ): array {
        $published = $this->publishedTypes($worker, $project, array_keys($cells));

        $draft = WorkerScheduleDraft::query()->firstOrCreate(
            [
                'worker_id' => $worker->id,
                'major_project_id' => $project->id,
            ],
            [
                'company_id' => $worker->company_id,
                'user_id' => $user->id,
                'status' => ScheduleDraftStatus::Pending,
            ],
        );

        $draft->forceFill([
            'user_id' => $user->id,
            'status' => ScheduleDraftStatus::Pending,
        ])->save();

        foreach ($cells as $date => $type) {
            $from = $published[$date] ?? ScheduleDayType::Off->value;
            $needsRoom = array_key_exists($date, $needsRoomByDate)
                ? (bool) $needsRoomByDate[$date]
                : $type !== ScheduleDayType::Off->value;

            if ($type === $from) {
                WorkerScheduleDraftDay::query()
                    ->where('worker_schedule_draft_id', $draft->id)
                    ->where('date', $date)
                    ->delete();

                continue;
            }

            WorkerScheduleDraftDay::query()->updateOrCreate(
                [
                    'worker_schedule_draft_id' => $draft->id,
                    'date' => $date,
                ],
                [
                    'from_type' => $from,
                    'to_type' => $type,
                    'needs_room' => $needsRoom,
                ],
            );
        }

        if ($backDragRevert) {
            $this->dropNoOpDays($draft);
        }

        $draft->refresh()->load('days');

        if ($draft->days->isEmpty()) {
            $draft->delete();

            return [
                'mode' => ScheduleDragRules::MODE_CELLS,
                'draft_id' => null,
                'cells' => $cells,
                'pending' => [],
                'draft_became_empty' => true,
            ];
        }

        $draft->touch();

        return [
            'mode' => ScheduleDragRules::MODE_CELLS,
            'draft_id' => $draft->id,
            'cells' => $cells,
            'pending' => $draft->days->map(fn (WorkerScheduleDraftDay $day) => $day->date->toDateString())->all(),
            'draft_became_empty' => false,
        ];
    }

    private function publishDraft(User $user, WorkerScheduleDraft $draft): void
    {
        $assignment = $this->reservations->currentAssignment($draft->worker, $draft->majorProject);

        foreach ($draft->days as $day) {
            $date = $day->date->toDateString();
            $type = $day->to_type instanceof ScheduleDayType ? $day->to_type->value : (string) $day->to_type;

            if ($type === ScheduleDayType::Off->value) {
                WorkerScheduleDay::query()
                    ->where('worker_id', $draft->worker_id)
                    ->where('major_project_id', $draft->major_project_id)
                    ->where('date', $date)
                    ->delete();

                continue;
            }

            WorkerScheduleDay::query()->updateOrCreate(
                [
                    'worker_id' => $draft->worker_id,
                    'major_project_id' => $draft->major_project_id,
                    'date' => $date,
                ],
                [
                    'company_id' => $draft->company_id,
                    'day_type' => $type,
                    'needs_room' => $day->needs_room,
                ],
            );
        }

        $this->reservations->refineNeedsRoom($draft->worker, $draft->majorProject);
        $stay = $this->reservations->sync($draft->worker, $draft->majorProject);

        ScheduleModificationRequest::query()->create([
            'company_id' => $draft->company_id,
            'worker_id' => $draft->worker_id,
            'major_project_id' => $draft->major_project_id,
            'requested_by' => $user->id,
            'check_in' => $stay['check_in'],
            'check_out' => $stay['check_out'],
            'previous_check_in' => $assignment?->check_in?->toDateString(),
            'previous_check_out' => $assignment?->check_out?->toDateString(),
            'change_count' => $draft->days->count(),
            'status' => ScheduleModificationStatus::Pending,
        ]);

        $draft->delete();
    }

    private function dropNoOpDays(WorkerScheduleDraft $draft): void
    {
        $published = $this->publishedTypes(
            $draft->worker,
            $draft->majorProject,
            $draft->days->map(fn (WorkerScheduleDraftDay $day) => $day->date->toDateString())->all(),
        );

        foreach ($draft->days as $day) {
            $date = $day->date->toDateString();
            $to = $day->to_type instanceof ScheduleDayType ? $day->to_type->value : (string) $day->to_type;

            if ($to === ($published[$date] ?? ScheduleDayType::Off->value)) {
                $day->delete();
            }
        }
    }

    /**
     * @param  list<string>  $dates
     * @return array<string, string>
     */
    private function publishedTypes(Worker $worker, MajorProject $project, array $dates): array
    {
        if ($dates === []) {
            return [];
        }

        $types = array_fill_keys($dates, ScheduleDayType::Off->value);

        WorkerScheduleDay::query()
            ->where('worker_id', $worker->id)
            ->where('major_project_id', $project->id)
            ->whereIn('date', $dates)
            ->get()
            ->each(function (WorkerScheduleDay $day) use (&$types): void {
                $types[$day->date->toDateString()] = $day->day_type->value;
            });

        return $types;
    }

    /**
     * @return list<string>
     */
    private function pendingDates(Worker $worker, MajorProject $project): array
    {
        $draft = $this->pendingDraft($worker, $project);

        if (! $draft) {
            return [];
        }

        return $draft->days()
            ->pluck('date')
            ->map(fn ($date) => Carbon::parse($date)->toDateString())
            ->all();
    }

    private function pendingDraft(Worker $worker, MajorProject $project): ?WorkerScheduleDraft
    {
        return WorkerScheduleDraft::query()
            ->where('worker_id', $worker->id)
            ->where('major_project_id', $project->id)
            ->where('status', ScheduleDraftStatus::Pending)
            ->first();
    }

    private function draftSummary(WorkerScheduleDraft $draft): string
    {
        $count = $draft->days->count();
        $name = $draft->worker?->full_name ?? 'Worker';

        return "{$name}: {$count} day".($count === 1 ? '' : 's').' pending publish';
    }

    private function assertEditable(User $user, Worker $worker, MajorProject $project): void
    {
        $this->assertCanEdit($user);

        if ((int) $worker->company_id !== (int) $user->company_id && ! $user->isSuperAdmin()) {
            throw new InvalidArgumentException('That worker is not in your company.');
        }

        if ((int) $worker->primary_project_id !== (int) $project->id) {
            throw new InvalidArgumentException('Schedule edits must target the worker’s assigned project.');
        }

        if (! $worker->schedule_access) {
            throw new InvalidArgumentException('This worker does not have schedule access.');
        }
    }

    private function assertCanEdit(User $user): void
    {
        if ($user->isSuperAdmin() || $user->role?->canManageWorkforce()) {
            return;
        }

        throw new InvalidArgumentException('You cannot edit the schedule.');
    }
}
