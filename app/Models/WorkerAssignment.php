<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkerAssignment extends CompanyModel
{
    protected function casts(): array
    {
        return ['start_date' => 'date', 'end_date' => 'date', 'is_primary' => 'boolean'];
    }

    public function worker(): BelongsTo { return $this->belongsTo(Worker::class); }
    public function majorProject(): BelongsTo { return $this->belongsTo(MajorProject::class); }
}
