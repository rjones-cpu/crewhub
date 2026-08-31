<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Certification extends CompanyModel
{
    use SoftDeletes;

    protected function casts(): array
    {
        return [
            'issued_at' => 'date',
            'expires_at' => 'date',
            'uploaded_at' => 'datetime',
            'file_size' => 'integer',
        ];
    }

    public function worker(): BelongsTo { return $this->belongsTo(Worker::class); }

    public function uploader(): BelongsTo { return $this->belongsTo(User::class, 'uploaded_by'); }

    public function trainingRecord(): HasOne { return $this->hasOne(TrainingRecord::class); }
}
