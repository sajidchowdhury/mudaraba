<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'profit_month', 'total_amount',
    'investor_portion_pct', 'my_portion_pct',
    'remarks', 'created_by',
])]
class RetainedEarnings extends Model
{
    protected $primaryKey = 'profit_month';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $table = 'retained_earnings';

    protected $casts = [
        'total_amount' => 'decimal:2',
        'investor_portion_pct' => 'decimal:2',
        'my_portion_pct' => 'decimal:2',
    ];

    /* -------------------------------------------------------
     * Relationships
     * ----------------------------------------------------- */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function distributions(): HasMany
    {
        return $this->hasMany(RetainedEarningsDistribution::class, 'profit_month', 'profit_month');
    }

    /* -------------------------------------------------------
     * Computed accessors (Excel AI3/AJ4/AK4)
     * ----------------------------------------------------- */

    /**
     * Excel AJ4 — total investor portion = total_amount × investor_portion_pct / 100.
     * Default: 200,000 × 71% = 142,000.
     */
    public function getInvestorPortionAmountAttribute(): float
    {
        return round((float) $this->total_amount * (float) $this->investor_portion_pct / 100, 2);
    }

    /**
     * Excel AK4 — M/Y portion = total_amount × my_portion_pct / 100.
     * Default: 200,000 × 29% = 58,000.
     */
    public function getMyPortionAmountAttribute(): float
    {
        return round((float) $this->total_amount * (float) $this->my_portion_pct / 100, 2);
    }

    /**
     * Convenience: does this month's retained earnings have distributions?
     */
    public function isDistributed(): bool
    {
        return $this->distributions()->exists();
    }

    /* -------------------------------------------------------
     * Scopes
     * ----------------------------------------------------- */
    public function scopeForMonth($query, string $month)
    {
        return $query->where('profit_month', $month);
    }
}
