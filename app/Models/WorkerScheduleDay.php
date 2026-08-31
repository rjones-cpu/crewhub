<?php

namespace App\Models;

use App\Enums\ScheduleDayType;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkerScheduleDay extends CompanyModel
{
    protected function casts(): array
    {
        return [
            'date' => 'date',
            'day_type' => ScheduleDayType::class,
            'needs_room' => 'boolean',
        ];
    }

    public function worker(): BelongsTo
    {
        return $this->belongsTo(Worker::class);
    }

    public function majorProject(): BelongsTo
    {
        return $this->belongsTo(MajorProject::class);
    }
}
