<?php

namespace App\Enums;

enum ScheduleModificationStatus: string
{
    case Pending = 'pending';
    case Acknowledged = 'acknowledged';
}
