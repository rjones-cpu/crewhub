<?php

namespace App\Enums;

enum ModuleActivationRequestStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
}
