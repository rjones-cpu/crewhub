<?php

namespace App\Models;

use App\Enums\InvitationStatus;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectInvitation extends CompanyModel
{
    protected function casts(): array
    {
        return [
            'status' => InvitationStatus::class,
            'invited_at' => 'datetime',
            'responded_at' => 'datetime',
        ];
    }

    public function majorProject(): BelongsTo
    {
        // Invited companies are not owners/members yet, so skip visibility scope.
        return $this->belongsTo(MajorProject::class)->withoutGlobalScope('company_or_member');
    }

    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }
}
