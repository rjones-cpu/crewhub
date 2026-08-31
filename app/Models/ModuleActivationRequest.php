<?php

namespace App\Models;

use App\Enums\ModuleActivationRequestStatus;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ModuleActivationRequest extends CompanyModel
{
    protected function casts(): array
    {
        return [
            'status' => ModuleActivationRequestStatus::class,
            'reviewed_at' => 'datetime',
        ];
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
