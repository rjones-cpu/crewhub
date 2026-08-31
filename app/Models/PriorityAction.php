<?php

namespace App\Models;

use App\Enums\ActionSeverity;
use App\Enums\ActionStatus;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PriorityAction extends CompanyModel
{
    use SoftDeletes;

    protected function casts(): array
    {
        return ['due_date' => 'date', 'status' => ActionStatus::class, 'severity' => ActionSeverity::class];
    }

    public function majorProject(): BelongsTo { return $this->belongsTo(MajorProject::class); }
    public function owner(): BelongsTo { return $this->belongsTo(User::class, 'owner_id'); }
}
