<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['name', 'mobile', 'address', 'is_my'])]
class Director extends Model
{
    use HasFactory, SoftDeletes;

    protected $casts = [
        'is_my' => 'boolean',
    ];
}
