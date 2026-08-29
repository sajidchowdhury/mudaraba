<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
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

    public function investmentTransactions(): HasMany
    {
        return $this->hasMany(InvestmentTransaction::class)->orderBy('transaction_date');
    }

    public function monthlyProfitDetails(): HasMany
    {
        return $this->hasMany(InvestorMonthlyProfitDetail::class)->orderBy('profit_month', 'desc');
    }

    /* -------------------------------------------------------
     * Due ledgers
     * ----------------------------------------------------- */
    public function dueLedger(): HasOne
    {
        return $this->hasOne(InvestorDueLedger::class);
    }

    public function profitDueLedger(): HasOne
    {
        return $this->hasOne(InvestorProfitDueLedger::class);
    }

    /* -------------------------------------------------------
     * Retained earnings
     * ----------------------------------------------------- */
    public function retainedEarningsDistributions(): HasMany
    {
        return $this->hasMany(RetainedEarningsDistribution::class)->orderBy('profit_month', 'desc');
    }

    /* -------------------------------------------------------
     * Advance profit adjustments (Type C targeting this investor)
     * ----------------------------------------------------- */
    public function advanceProfitAdjustments(): HasMany
    {
        return $this->hasMany(AdvanceProfitAdjustment::class)->orderBy('transaction_date', 'desc');
    }

    /**
     * Convenience: deed ratio as a float (1.0 / 0.8 / 0.6) for profit calcs.
     */
    public function deedRatioFloat(): float
    {
        return ((float) $this->deed_ratio) / 100;
    }
}
