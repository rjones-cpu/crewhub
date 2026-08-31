<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends CompanyModel
{
    use HasUuids;

    public $incrementing = false;
    protected $keyType = 'string';

    protected function casts(): array { return ['data' => 'array', 'read_at' => 'datetime']; }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}
