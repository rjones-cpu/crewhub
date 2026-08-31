<?php

namespace App\Models;

use App\Enums\ServiceRating\CriterionCode;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceRatingCriticalOverride extends CompanyModel
{
    protected function casts(): array
    {
        return [
            'scope_json' => 'array',
            'evidence_json' => 'array',
            'effective_from' => 'datetime',
            'effective_to' => 'datetime',
            'approved_at' => 'datetime',
            'resolved_at' => 'datetime',
            'criterion_code' => CriterionCode::class,
        ];
    }

    public function majorProject(): BelongsTo
    {
        return $this->belongsTo(MajorProject::class);
    }

    public function isActiveAt(\DateTimeInterface $at): bool
    {
        if (! in_array($this->status, ['approved', 'active'], true)) {
            return false;
        }

        if ($this->resolved_at !== null) {
            return false;
        }

        if ($this->effective_from > $at) {
            return false;
        }

        return $this->effective_to === null || $this->effective_to >= $at;
    }
}
