<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class MedicalRecord extends CompanyModel
{
    use SoftDeletes;

    protected function casts(): array { return ['examined_at' => 'date', 'expires_at' => 'date']; }
    public function worker(): BelongsTo { return $this->belongsTo(Worker::class); }
}
