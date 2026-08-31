<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ServiceRatingPolicy extends CompanyModel
{
    public function majorProject(): BelongsTo
    {
        return $this->belongsTo(MajorProject::class);
    }

    public function versions(): HasMany
    {
        return $this->hasMany(ServiceRatingPolicyVersion::class);
    }

    public function currentVersion(): BelongsTo
    {
        return $this->belongsTo(ServiceRatingPolicyVersion::class, 'current_version_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
