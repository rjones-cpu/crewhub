<?php

namespace App\Models;

use App\Enums\ReadinessStatus;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkerReadiness extends CompanyModel
{
    protected $table = 'worker_readiness';

    protected function casts(): array
    {
        return ['overall_status' => ReadinessStatus::class, 'last_checked_at' => 'datetime'];
    }

    public function worker(): BelongsTo { return $this->belongsTo(Worker::class); }
}
