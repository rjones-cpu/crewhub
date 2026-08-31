<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrainingRecord extends CompanyModel
{
    protected function casts(): array
    {
        return [
            'completed_at' => 'date',
            'expires_at' => 'date',
            'score' => 'decimal:2',
            'is_required' => 'boolean',
        ];
    }

    public function worker(): BelongsTo { return $this->belongsTo(Worker::class); }

    public function certification(): BelongsTo { return $this->belongsTo(Certification::class); }

    /**
     * A record counts as compliant when it is complete and not past its expiry.
     */
    public function isCompliant(): bool
    {
        if ($this->status !== 'completed') {
            return false;
        }

        return $this->expires_at === null || $this->expires_at->isFuture();
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function expiresWithinDays(int $days): bool
    {
        return $this->expires_at !== null
            && $this->expires_at->isFuture()
            && $this->expires_at->diffInDays(now()) <= $days;
    }
}
