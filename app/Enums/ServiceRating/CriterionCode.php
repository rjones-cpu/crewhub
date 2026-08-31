<?php

namespace App\Enums\ServiceRating;

enum CriterionCode: string
{
    case WorkforceDelivery = 'workforce_delivery';
    case ScheduledArrival = 'scheduled_arrival';
    case JourneyManagement = 'journey_management';
    case LmsCertification = 'lms_certification';

    public function label(): string
    {
        return match ($this) {
            self::WorkforceDelivery => 'Required Workforce Provided',
            self::ScheduledArrival => 'Workforce Arrival',
            self::JourneyManagement => 'Journey Management',
            self::LmsCertification => 'LMS & Certification',
        };
    }

    /** Dashboard / table short keys used by existing React props. */
    public function dashboardKey(): string
    {
        return match ($this) {
            self::WorkforceDelivery => 'workforce',
            self::ScheduledArrival => 'arrival',
            self::JourneyManagement => 'journey',
            self::LmsCertification => 'lms',
        };
    }
}
