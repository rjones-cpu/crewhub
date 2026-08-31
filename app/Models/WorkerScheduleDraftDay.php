<?php

namespace App\Models;

use App\Enums\ScheduleDayType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkerScheduleDraftDay extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'from_type' => ScheduleDayType::class,
            'to_type' => ScheduleDayType::class,
            'needs_room' => 'boolean',
        ];
    }

    public function draft(): BelongsTo
    {
        return $this->belongsTo(WorkerScheduleDraft::class, 'worker_schedule_draft_id');
    }
}
