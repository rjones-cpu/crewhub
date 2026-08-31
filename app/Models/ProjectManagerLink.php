<?php

namespace App\Models;

use App\Enums\ManagerRelationship;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProjectManagerLink extends CompanyModel
{
    protected function casts(): array
    {
        return ['relationship' => ManagerRelationship::class];
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function majorProject(): BelongsTo
    {
        return $this->belongsTo(MajorProject::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function delegations(): HasMany
    {
        return $this->hasMany(ResponsibilityDelegation::class);
    }
}
