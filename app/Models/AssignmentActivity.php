<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssignmentActivity extends CompanyModel
{
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function majorProject(): BelongsTo
    {
        return $this->belongsTo(MajorProject::class);
    }
}
