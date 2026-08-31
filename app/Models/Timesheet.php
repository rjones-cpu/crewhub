<?php

namespace App\Models;

use App\Enums\TimesheetStatus;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Timesheet extends CompanyModel
{
    protected function casts(): array
    {
        return [
            'period_start' => 'date',
            'period_end' => 'date',
            'due_date' => 'date',
            'hours' => 'decimal:2',
            'regular_hours' => 'decimal:2',
            'overtime_hours' => 'decimal:2',
            'double_time_hours' => 'decimal:2',
            'travel_hours' => 'decimal:2',
            'standby_hours' => 'decimal:2',
            'break_hours' => 'decimal:2',
            'equipment_hours' => 'decimal:2',
            'status' => TimesheetStatus::class,
            'client_approval_required' => 'boolean',
            'day_entries' => 'array',
            'equipment_entries' => 'array',
            'compliance' => 'array',
            'status_history' => 'array',
            'submitted_at' => 'datetime',
            'approved_at' => 'datetime',
            'manager_approved_at' => 'datetime',
            'client_approved_at' => 'datetime',
            'returned_at' => 'datetime',
            'worker_signed_at' => 'datetime',
        ];
    }

    public function worker(): BelongsTo
    {
        return $this->belongsTo(Worker::class);
    }

    public function majorProject(): BelongsTo
    {
        return $this->belongsTo(MajorProject::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function managerApprover(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_approved_by');
    }

    public function clientApprover(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_approved_by');
    }

    public function returnedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'returned_by');
    }

    public function campSources(): HasMany
    {
        return $this->hasMany(CampTimesheetSource::class);
    }

    public function isEditable(): bool
    {
        return $this->status?->isEditable() ?? false;
    }

    /** Whether this sheet has to pass the optional client approval gate. */
    public function requiresClientApproval(): bool
    {
        return (bool) config('timesheets.client_approval_enabled')
            && (bool) $this->client_approval_required;
    }

    /**
     * Manager approval is the only human gate. Sheets parked at manager_approved by
     * the older two-step flow are drained by the same manager action once the client
     * gate is switched off, so they cannot sit in the queue forever.
     */
    public function awaitsManagerApproval(): bool
    {
        if ($this->status?->isPendingManager()) {
            return true;
        }

        return ! config('timesheets.client_approval_enabled')
            && ($this->status?->isPendingClient() ?? false);
    }

    public function awaitsClientApproval(): bool
    {
        return $this->requiresClientApproval() && ($this->status?->isPendingClient() ?? false);
    }
}
