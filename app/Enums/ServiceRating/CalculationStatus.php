<?php

namespace App\Enums\ServiceRating;

enum CalculationStatus: string
{
    case Calculated = 'calculated';
    case PendingData = 'pending_data';
    case Failed = 'failed';
}
