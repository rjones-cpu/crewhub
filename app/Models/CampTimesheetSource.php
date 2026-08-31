<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CampTimesheetSource extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'period_start' => 'date',
            'period_end' => 'date',
            'last_synced_at' => 'datetime',
        ];
    }

    public function timesheet(): BelongsTo
    {
        return $this->belongsTo(Timesheet::class);
    }

    public function bookingWorkerLink(): BelongsTo
    {
        return $this->belongsTo(CampBookingWorkerLink::class, 'camp_booking_worker_link_id');
    }
}
