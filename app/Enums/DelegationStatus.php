<?php

namespace App\Enums;

enum DelegationStatus: string
{
    case Accepted = 'accepted';
    case Pending = 'pending';
    case NotDelegated = 'not_delegated';

    public function label(): string
    {
        return match ($this) {
            self::Accepted => 'Accepted',
            self::Pending => 'Pending',
            self::NotDelegated => 'Not delegated',
        };
    }
}
