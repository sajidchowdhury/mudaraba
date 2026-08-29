<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['investor_id', 'due_month', 'due'])]
class InvestorMonthlyDue extends Model
{
    public $incrementing = false;

    protected $table = 'investor_monthly_due';

    public $timestamps = false;

    protected $casts = [
        'due' => 'decimal:2',
    ];

    public function investor(): BelongsTo
    {
        return $this->belongsTo(Investor::class);
    }
}
