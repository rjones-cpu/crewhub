<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkerActivity extends CompanyModel
{
    protected function casts(): array { return ['metadata' => 'array']; }
    public function worker(): BelongsTo { return $this->belongsTo(Worker::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}
