<?php

namespace App\Enums;

enum ReadinessStatus: string
{
    case Ready = 'ready';
    case AtRisk = 'at_risk';
    case NotReady = 'not_ready';
    case PendingReview = 'pending_review';
}
