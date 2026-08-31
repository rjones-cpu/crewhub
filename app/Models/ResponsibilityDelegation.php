<?php

namespace App\Models;

use App\Enums\DelegationStatus;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResponsibilityDelegation extends CompanyModel
{
    protected function casts(): array
    {
        return [
            'status' => DelegationStatus::class,
            'is_delegable' => 'boolean',
        ];
    }

    public function managerLink(): BelongsTo
    {
        return $this->belongsTo(ProjectManagerLink::class, 'project_manager_link_id');
    }

    public function majorProject(): BelongsTo
    {
        return $this->belongsTo(MajorProject::class);
    }
}
