<?php

namespace App\Models;

use App\Enums\ScheduleDraftStatus;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkerScheduleDraft extends CompanyModel
{
    protected function casts(): array
    {
        return [
            'status' => ScheduleDraftStatus::class,
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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function days(): HasMany
    {
        return $this->hasMany(WorkerScheduleDraftDay::class);
    }
}
