<?php

namespace App\Enums;

enum ActionStatus: string
{
    case Open = 'open';
    case InProgress = 'in_progress';
    case Resolved = 'resolved';
    case Overdue = 'overdue';
}
