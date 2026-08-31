<?php

namespace App\Models;

use App\Enums\ScheduleModificationStatus;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScheduleModificationRequest extends CompanyModel
{
    protected function casts(): array
    {
        return [
            'check_in' => 'date',
            'check_out' => 'date',
            'previous_check_in' => 'date',
            'previous_check_out' => 'date',
            'acknowledged_at' => 'datetime',
            'status' => ScheduleModificationStatus::class,
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

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }
}
