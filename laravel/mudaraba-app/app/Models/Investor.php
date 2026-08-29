<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'name', 'reference', 'mobile', 'address',
    'deed_ratio', 'start_profit_month', 'end_profit_month', 'status',
])]
class Investor extends Model
{
    use HasFactory, SoftDeletes;

    protected $casts = [
        'deed_ratio' => 'string',   // stored as enum string for portability
        'start_profit_month' => 'date',
        'end_profit_month' => 'date',
    ];

    /**
     * Convenience: deed ratio as a float (1.0 / 0.8 / 0.6) for profit calcs.
     */
    public function deedRatioFloat(): float
    {
        return ((float) $this->deed_ratio) / 100;
    }
}
