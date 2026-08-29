<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'profit_month', 'transaction_date', 'investor_id',
    'investment', 'investment_ratio',
    'primary_profit_share', 'actual_profit_at_full',
    'deed_ratio', 'actual_profit_due',
    'advance_difference', 'retained_earnings_credit', 'net_settlement',
    'batch_uuid', 'created_by',
])]
class InvestorMonthlyProfitDetail extends Model
{
    use HasFactory;

    // No soft deletes — these are computed snapshot rows; rollback = delete
    public const UPDATED_AT = null; // append-only snapshot per month

    protected $casts = [
        'profit_month' => 'date',
        'transaction_date' => 'date',
        'investment' => 'decimal:2',
        'investment_ratio' => 'decimal:6',
        'primary_profit_share' => 'decimal:2',
        'actual_profit_at_full' => 'decimal:2',
        'deed_ratio' => 'decimal:2',
        'actual_profit_due' => 'decimal:2',
        'advance_difference' => 'decimal:2',
        'retained_earnings_credit' => 'decimal:2',
        'net_settlement' => 'decimal:2',
        'batch_uuid' => 'string',
    ];

    public function investor(): BelongsTo
    {
        return $this->belongsTo(Investor::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /* -------------------------------------------------------
     * Scopes
     * ----------------------------------------------------- */
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

    /* -------------------------------------------------------
     * Helpers — sign conventions per plan §5.1
     * ----------------------------------------------------- */

    /**
     * Was the investor over-paid in advance? (positive advance_difference)
     * Excel AH > 0 — investor owes M/Y the difference.
     */
    public function wasOverpaid(): bool
    {
        return (float) $this->advance_difference > 0;
    }

    /**
     * Was the investor under-paid? (negative advance_difference)
     * Excel AH < 0 — M/Y owes investor the difference.
     */
    public function wasUnderpaid(): bool
    {
        return (float) $this->advance_difference < 0;
    }

    /**
     * Final receivable (+) / payable (-) by investor to M/Y after retained credit.
     * Excel AK column.
     */
    public function settlementDirection(): string
    {
        $net = (float) $this->net_settlement;

        return match (true) {
            $net > 0 => 'receivable',   // investor owes M/Y
            $net < 0 => 'payable',      // M/Y owes investor
            default => 'settled',
        };
    }
}
