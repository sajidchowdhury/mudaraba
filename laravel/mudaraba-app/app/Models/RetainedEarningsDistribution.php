<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'profit_month', 'investor_id', 'investment_ratio', 'amount', 'batch_uuid',
])]
class RetainedEarningsDistribution extends Model
{
    public const UPDATED_AT = null; // append-only snapshot per month

    protected $casts = [
        'investment_ratio' => 'decimal:6',
        'amount' => 'decimal:2',
        'batch_uuid' => 'string',
    ];

    public function investor(): BelongsTo
    {
        return $this->belongsTo(Investor::class);
    }

    public function retainedEarnings(): BelongsTo
    {
        return $this->belongsTo(RetainedEarnings::class, 'profit_month', 'profit_month');
    }

    public function scopeForMonth($query, string $month)
    {
        return $query->where('profit_month', $month);
    }

    public function scopeForInvestor($query, int $investorId)
    {
        return $query->where('investor_id', $investorId);
    }

    public function scopeInBatch($query, string $batchUuid)
    {
        return $query->where('batch_uuid', $batchUuid);
    }
}
