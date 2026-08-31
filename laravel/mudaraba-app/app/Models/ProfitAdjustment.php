<?php

namespace App\Models;

use App\Enums\AdjustmentTarget;
use App\Enums\AdjustmentType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'type', 'target_type', 'investor_id', 'sector_id',
    'amount', 'transaction_date', 'profit_month',
    'remarks', 'batch_uuid', 'created_by',
])]
class ProfitAdjustment extends Model
{
    use HasFactory, SoftDeletes;

    protected $casts = [
        'type' => AdjustmentType::class,
        'target_type' => AdjustmentTarget::class,
        'amount' => 'decimal:2',
        'transaction_date' => 'date',
        'profit_month' => 'date',
        'batch_uuid' => 'string',
    ];

    /* -------------------------------------------------------
     * Relationships
     * ----------------------------------------------------- */
    public function investor(): BelongsTo
    {
        return $this->belongsTo(Investor::class);
    }

    public function sector(): BelongsTo
    {
        return $this->belongsTo(Sector::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /* -------------------------------------------------------
     * Scopes
     * ----------------------------------------------------- */
    public function scopeForType($query, AdjustmentType $type)
    {
        return $query->where('type', $type->value);
    }

    public function scopeForInvestor($query, int $investorId)
    {
        return $query->where('investor_id', $investorId);
    }

    public function scopeForSector($query, int $sectorId)
    {
        return $query->where('sector_id', $sectorId);
    }

    public function scopeInBatch($query, string $batchUuid)
    {
        return $query->where('batch_uuid', $batchUuid);
    }

    public function scopeForMonth($query, string $month)
    {
        return $query->where('profit_month', $month);
    }

    /* -------------------------------------------------------
     * Fund balance computation (replaces separate fund tables)
     *
     * Per PHP spec:
     *  - Fund A: balance = Σ(investor amounts) − Σ(sector amounts)
     *            (investors add to the fund, sectors deduct from it)
     *  - Fund B: balance = +Σ(sector amounts)
     *            (sector surplus INCREASES the fund; NO investor side)
     *  - Direct: no fund tracking (returns 0.0)
     *
     * Always computed on-the-fly from adjustment records,
     * so it can never drift from the actual transactions.
     * ----------------------------------------------------- */

    /**
     * Get the current balance for a fund type (Fund A or Fund B).
     */
    public static function fundBalance(AdjustmentType $type): float
    {
        if ($type === AdjustmentType::FundB) {
            // Fund B: sector surplus increases the fund (no investor side)
            return (float) self::forType($type)
                ->where('target_type', AdjustmentTarget::Sector)
                ->sum('amount');
        }

        if ($type === AdjustmentType::Direct) {
            return 0.0;
        }

        // Fund A: investor amounts add, sector amounts deduct
        $investorTotal = self::forType($type)
            ->where('target_type', AdjustmentTarget::Investor)
            ->sum('amount');

        $sectorTotal = self::forType($type)
            ->where('target_type', AdjustmentTarget::Sector)
            ->sum('amount');

        return (float) $investorTotal - (float) $sectorTotal;
    }

    /**
     * Get the fund balance as of a specific date.
     */
    public static function fundBalanceAt(AdjustmentType $type, string $date): float
    {
        if ($type === AdjustmentType::FundB) {
            return (float) self::forType($type)
                ->where('target_type', AdjustmentTarget::Sector)
                ->where('transaction_date', '<=', $date)
                ->sum('amount');
        }

        if ($type === AdjustmentType::Direct) {
            return 0.0;
        }

        $investorTotal = self::forType($type)
            ->where('target_type', AdjustmentTarget::Investor)
            ->where('transaction_date', '<=', $date)
            ->sum('amount');

        $sectorTotal = self::forType($type)
            ->where('target_type', AdjustmentTarget::Sector)
            ->where('transaction_date', '<=', $date)
            ->sum('amount');

        return (float) $investorTotal - (float) $sectorTotal;
    }

    /**
     * Get the fund balance for a specific date (that date only).
     */
    public static function fundBalanceForDate(AdjustmentType $type, string $date): float
    {
        if ($type === AdjustmentType::FundB) {
            return (float) self::forType($type)
                ->where('target_type', AdjustmentTarget::Sector)
                ->where('transaction_date', $date)
                ->sum('amount');
        }

        if ($type === AdjustmentType::Direct) {
            return 0.0;
        }

        $investorTotal = self::forType($type)
            ->where('target_type', AdjustmentTarget::Investor)
            ->where('transaction_date', $date)
            ->sum('amount');

        $sectorTotal = self::forType($type)
            ->where('target_type', AdjustmentTarget::Sector)
            ->where('transaction_date', $date)
            ->sum('amount');

        return (float) $investorTotal - (float) $sectorTotal;
    }

    /**
     * Total amount adjusted for a specific investor (all types).
     */
    public static function totalForInvestor(int $investorId): float
    {
        return (float) self::forInvestor($investorId)->sum('amount');
    }

    /**
     * Total amount adjusted for a specific sector (all types).
     */
    public static function totalForSector(int $sectorId): float
    {
        return (float) self::forSector($sectorId)->sum('amount');
    }

    /**
     * Resolve the target entity model (investor or sector).
     */
    public function target(): ?Model
    {
        return $this->investor ?? $this->sector;
    }

    /**
     * The target's name for display.
     */
    public function targetName(): string
    {
        return $this->investor?->name ?? $this->sector?->name ?? '—';
    }
}
