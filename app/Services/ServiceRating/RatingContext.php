<?php

namespace App\Services\ServiceRating;

use Illuminate\Support\Carbon;

final class RatingContext
{
    public function __construct(
        public readonly int $companyId,
        public readonly int $majorProjectId,
        public readonly Carbon $windowStart,
        public readonly Carbon $windowEnd,
        public readonly Carbon $evidenceCutoffAt,
        public readonly string $correlationId,
        public readonly string $timeZone,
    ) {
    }
}
