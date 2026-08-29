<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['director_id', 'due_month', 'due'])]
class DirectorMonthlyDue extends Model
{
    public $incrementing = false;

    protected $table = 'director_monthly_due';

    public $timestamps = false;

    protected $casts = [
        'due' => 'decimal:2',
    ];

    public function director(): BelongsTo
    {
        return $this->belongsTo(Director::class);
    }
}
