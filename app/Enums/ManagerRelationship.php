<?php

namespace App\Enums;

enum ManagerRelationship: string
{
    case Primary = 'primary';
    case Connected = 'connected';

    public function label(): string
    {
        return match ($this) {
            self::Primary => 'Primary',
            self::Connected => 'Connected',
        };
    }
}
