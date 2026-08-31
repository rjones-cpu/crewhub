<?php

namespace App\Enums;

enum ScheduleDraftStatus: string
{
    case Pending = 'pending';
    case Cancelled = 'cancelled';
}
