<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScheduleForecast extends CompanyModel
{
    protected function casts(): array { return ['forecast_date' => 'date']; }
    public function majorProject(): BelongsTo { return $this->belongsTo(MajorProject::class); }
}
