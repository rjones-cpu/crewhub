<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Module extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'is_paid' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function companyModules(): HasMany
    {
        return $this->hasMany(CompanyModule::class);
    }

    public function activationRequests(): HasMany
    {
        return $this->hasMany(ModuleActivationRequest::class);
    }

    public const KEY_MAJOR_PROJECTS = 'major_projects';

    public const KEY_LMS = 'lms';
}
