<?php

namespace App\Models;

use App\Enums\SectorProfitStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'sector_id', 'profit_month', 'transaction_date',
    'estimated_profit', 'actual_profit', 'profit_adjustment',
    'is_estimate', 'status', 'created_by', 'finalized_by', 'finalized_at',
])]
class MonthlySectorProfit extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'monthly_sector_profit';

    protected $casts = [
        'profit_month' => 'date',
        'transaction_date' => 'date',
        'estimated_profit' => 'decimal:2',
        'actual_profit' => 'decimal:2',
        'profit_adjustment' => 'decimal:2',
        'is_estimate' => 'boolean',
        'status' => SectorProfitStatus::class,
        'finalized_at' => 'datetime',
    ];

    /* -------------------------------------------------------
     * Relationships
     * ----------------------------------------------------- */
    public function sector(): BelongsTo
    {
        return $this->belongsTo(Sector::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function finalizer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'finalized_by');
    }

    /* -------------------------------------------------------
     * Scopes
     * ----------------------------------------------------- */
    public function scopeForMonth($query, string $month)
    {
        return $query->where('profit_month', $month);
    }

    public function scopeForSector($query, int $sectorId)
    {
        return $query->where('sector_id', $sectorId);
    }

    public function scopeFinalized($query)
    {
        return $query->where('status', SectorProfitStatus::Finalized);
    }

    /* -------------------------------------------------------
     * Helpers
     * ----------------------------------------------------- */

    /**
     * Excel Y column — sector advance difference (estimated - actual).
     * Positive = investors were over-paid in advance, must return the diff.
     */
    public function advanceDifference(): float
    {
        return (float) $this->estimated_profit - (float) $this->actual_profit;
    }

    /**
     * Convenience: has actual profit been entered yet?
     */
    public function hasActuals(): bool
    {
        return $this->status === SectorProfitStatus::Finalized
            || (float) $this->actual_profit > 0;
    }
}
