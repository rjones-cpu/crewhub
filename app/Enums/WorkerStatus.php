<?php

namespace App\Enums;

enum WorkerStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
    case OnLeave = 'on_leave';
    case Mobilizing = 'mobilizing';
    case Demobilizing = 'demobilizing';
}
