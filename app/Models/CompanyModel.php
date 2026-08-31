<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

abstract class CompanyModel extends Model
{
    use BelongsToCompany, HasFactory;

    protected $guarded = [];
}
