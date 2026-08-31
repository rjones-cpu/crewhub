<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CampBookingWorkerLink extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['last_synced_at' => 'datetime'];
    }

    public function worker(): BelongsTo
    {
        return $this->belongsTo(Worker::class);
    }

    public function campCompanyLink(): BelongsTo
    {
        return $this->belongsTo(CampCompanyLink::class);
    }

    public function timesheetSources(): HasMany
    {
        return $this->hasMany(CampTimesheetSource::class);
    }
}
