<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CompanyProjectServiceRating extends CompanyModel
{
    protected function casts(): array
    {
        return [
            'activated_at' => 'datetime',
            'ended_at' => 'datetime',
        ];
    }

    public function majorProject(): BelongsTo
    {
        return $this->belongsTo(MajorProject::class);
    }

    public function snapshots(): HasMany
    {
        return $this->hasMany(ServiceRatingSnapshot::class);
    }

    public function currentPublishedSnapshot(): BelongsTo
    {
        return $this->belongsTo(ServiceRatingSnapshot::class, 'current_published_snapshot_id');
    }
}
