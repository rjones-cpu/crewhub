<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Accommodation extends CompanyModel
{
    public function majorProject(): BelongsTo { return $this->belongsTo(MajorProject::class); }
    public function assignments(): HasMany { return $this->hasMany(AccommodationAssignment::class); }
}
