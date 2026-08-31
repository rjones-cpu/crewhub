<?php

namespace App\Enums;

enum ModuleAccessStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
    case Trial = 'trial';
    case Expired = 'expired';

    public function isUsable(): bool
    {
        return in_array($this, [self::Active, self::Trial], true);
    }
}
