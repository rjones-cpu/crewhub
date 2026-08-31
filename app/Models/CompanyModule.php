<?php

namespace App\Models;

use App\Enums\ModuleAccessStatus;
use App\Enums\ModuleActivationSource;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyModule extends CompanyModel
{
    protected function casts(): array
    {
        return [
            'status' => ModuleAccessStatus::class,
            'activation_source' => ModuleActivationSource::class,
            'activated_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }

    public function activator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'activated_by');
    }

    public function isUsable(): bool
    {
        if (! $this->status?->isUsable()) {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        return true;
    }
}
