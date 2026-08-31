<?php

namespace App\Models;

use App\Enums\ServiceRating\CriterionCode;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceRatingException extends CompanyModel
{
    protected function casts(): array
    {
        return [
            'scope_json' => 'array',
            'evidence_json' => 'array',
            'effective_from' => 'datetime',
            'effective_to' => 'datetime',
            'approved_at' => 'datetime',
            'revoked_at' => 'datetime',
            'criterion_code' => CriterionCode::class,
        ];
    }

    public function majorProject(): BelongsTo
    {
        return $this->belongsTo(MajorProject::class);
    }

    public function isActiveAt(\DateTimeInterface $at): bool
    {
        if ($this->status !== 'approved') {
            return false;
        }

        return $this->effective_from <= $at
            && $this->effective_to >= $at
            && $this->revoked_at === null;
    }
}
