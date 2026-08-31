<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceRatingPolicyVersion extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'policy_json' => 'array',
            'effective_from' => 'datetime',
            'effective_to' => 'datetime',
            'approved_at' => 'datetime',
        ];
    }

    public function policy(): BelongsTo
    {
        return $this->belongsTo(ServiceRatingPolicy::class, 'service_rating_policy_id');
    }

    /**
     * Active policy versions are immutable — corrections create a new version.
     */
    protected static function booted(): void
    {
        static::updating(function (self $version): void {
            if (in_array($version->getOriginal('status'), ['active', 'published'], true)) {
                throw new \RuntimeException('Active service rating policy versions are immutable.');
            }
        });

        static::deleting(function (self $version): void {
            if (in_array($version->status, ['active', 'published'], true)) {
                throw new \RuntimeException('Active service rating policy versions cannot be deleted.');
            }
        });
    }
}
