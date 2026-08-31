<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CampCompanyLink extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['last_synced_at' => 'datetime'];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function projects(): HasMany
    {
        return $this->hasMany(CampProjectLink::class);
    }

    public function bookingWorkers(): HasMany
    {
        return $this->hasMany(CampBookingWorkerLink::class);
    }
}
