<?php

namespace App\Enums;

enum InsuranceStatus: string
{
    case Unverified = 'unverified';
    case Confirmed = 'confirmed';
    case Flagged = 'flagged';

    public function label(): string
    {
        return match ($this) {
            self::Unverified => 'Awaiting Confirmation',
            self::Confirmed => 'Confirmed',
            self::Flagged => 'Flagged',
        };
    }

    /**
     * Only confirmed cover lets a vehicle carry an approved journey.
     */
    public function clearsForJourneys(): bool
    {
        return $this === self::Confirmed;
    }
}
