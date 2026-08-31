<?php

namespace App\Enums;

enum TimesheetStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case Returned = 'returned';
    case ManagerApproved = 'manager_approved';
    case FullyApproved = 'fully_approved';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Submitted => 'Submitted',
            self::Returned => 'Returned',
            self::ManagerApproved => 'Manager Approved',
            self::FullyApproved => 'Fully Approved',
            self::Rejected => 'Rejected',
        };
    }

    public function isEditable(): bool
    {
        return in_array($this, [self::Draft, self::Returned], true);
    }

    public function isPendingManager(): bool
    {
        return $this === self::Submitted;
    }

    public function isPendingClient(): bool
    {
        return $this === self::ManagerApproved;
    }

    /** Statuses that count as awaiting some approval action. */
    public static function pendingApprovalValues(): array
    {
        return [self::Submitted->value, self::ManagerApproved->value];
    }

    /** Statuses that count as successfully approved end-states. */
    public static function approvedValues(): array
    {
        return [self::FullyApproved->value];
    }
}
