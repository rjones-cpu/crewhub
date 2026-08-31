<?php

namespace App\Enums\ServiceRating;

enum DataQualityStatus: string
{
    case Sufficient = 'sufficient';
    case SufficientWithManualEvidence = 'sufficient_with_manual_evidence';
    case Stale = 'stale';
    case Conflicting = 'conflicting';
    case Insufficient = 'insufficient';
    case IntegrationFailed = 'integration_failed';
}
