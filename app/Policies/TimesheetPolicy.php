<?php

namespace App\Policies;

use App\Enums\DelegationStatus;
use App\Enums\Role;
use App\Models\ResponsibilityDelegation;
use App\Models\Timesheet;
use App\Models\User;

class TimesheetPolicy extends CompanyPolicy
{
    /** Hierarchy Chart responsibility area that carries timesheet approval. */
    public const APPROVAL_AREA = 'Time Sheets';

    protected function managerRoles(): array
    {
        return [Role::CompanyAdmin, Role::WorkforceManager];
    }

    public function submit(User $user, Timesheet $timesheet): bool
    {
        return $this->update($user, $timesheet);
    }

    /**
     * Manager approval is the single human gate in the timesheet workflow.
     *
     * When the timesheet's project delegates the Time Sheets responsibility in the
     * Hierarchy Chart, only that manager may approve. Projects with no accepted
     * delegation fall back to the company manager roles so approvals are never
     * blocked by an unconfigured hierarchy.
     */
    public function approve(User $user, Timesheet $timesheet): bool
    {
        if (! $user->is_active || ! $this->sameCompany($user, $timesheet)) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        $delegatedApprovers = $this->delegatedApprovers($timesheet);

        if ($delegatedApprovers !== []) {
            return in_array($user->id, $delegatedApprovers, true);
        }

        return $this->canManage($user);
    }

    public function returnTimesheet(User $user, Timesheet $timesheet): bool
    {
        return $this->approve($user, $timesheet);
    }

    public function reject(User $user, Timesheet $timesheet): bool
    {
        return $this->approve($user, $timesheet);
    }

    /**
     * User ids holding the accepted Time Sheets delegation for this timesheet's project.
     *
     * @return array<int, int>
     */
    protected function delegatedApprovers(Timesheet $timesheet): array
    {
        if (! $timesheet->major_project_id) {
            return [];
        }

        return ResponsibilityDelegation::query()
            ->with('managerLink')
            ->where('major_project_id', $timesheet->major_project_id)
            ->where('area', self::APPROVAL_AREA)
            ->where('status', DelegationStatus::Accepted)
            ->get()
            ->map(fn (ResponsibilityDelegation $delegation) => $delegation->managerLink?->user_id)
            ->filter()
            ->unique()
            ->values()
            ->all();
    }
}
