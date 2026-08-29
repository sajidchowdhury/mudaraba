<?php

namespace App\Models;

use App\Enums\InvestmentType;
use App\Traits\DueManager;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'investor_id', 'amount', 'type', 'transaction_month', 'transaction_date',
    'remarks', 'batch_uuid', 'created_by',
])]
class InvestmentTransaction extends Model
{
    use DueManager, HasFactory, SoftDeletes;

    protected $casts = [
        'amount' => 'decimal:2',
        'type' => InvestmentType::class,
        'transaction_month' => 'date',
        'transaction_date' => 'date',
        'batch_uuid' => 'string',
    ];

    /* -------------------------------------------------------
     * Relationships
     * ----------------------------------------------------- */
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

    /**
     * Scope: only transactions affecting the investor's balance up to (inclusive) a given month.
     * Mirrors the PHP Investments::InvestmentTillMonth() query.
     */
    public function scopeTillMonth($query, string $month)
    {
        return $query->where('transaction_month', '<=', $month);
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
     * Helpers
     * ----------------------------------------------------- */

    /**
     * Configuration for the DueManager trait — capital due ledgers.
     */
    protected function dueLedgerConfig(): array
    {
        return [
            'entity_column' => 'investor_id',
            'cumulative_model' => InvestorDueLedger::class,
            'monthly_model' => InvestorMonthlyDue::class,
        ];
    }

    /**
     * The signed effect of this transaction on the investor's balance.
     * Add = +amount, Withdraw = -amount.
     */
    public function signedAmount(): float
    {
        return (float) $this->amount * $this->type->sign();
    }
}
