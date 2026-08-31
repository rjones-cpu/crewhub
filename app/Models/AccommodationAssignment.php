<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccommodationAssignment extends CompanyModel
{
    protected function casts(): array { return ['check_in' => 'date', 'check_out' => 'date']; }
    public function accommodation(): BelongsTo { return $this->belongsTo(Accommodation::class); }
    public function worker(): BelongsTo { return $this->belongsTo(Worker::class); }
}
