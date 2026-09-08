<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['sector_id', 'due_month', 'due'])]
class SectorMonthlyDue extends Model
{
    public $incrementing = false;

    protected $table = 'sector_monthly_due';

    public $timestamps = false;

    protected $casts = [
        'due' => 'decimal:2',
    ];

    public function sector(): BelongsTo
    {
        return $this->belongsTo(Sector::class);
    }
}
