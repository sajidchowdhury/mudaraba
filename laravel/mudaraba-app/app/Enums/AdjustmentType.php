<?php

namespace App\Enums;

/**
 * Type of profit adjustment.
 *
 * - FundA:   Batch adjustment — investors + sectors tracked in Fund A.
 *            Investor amounts ADD to the fund; sector amounts DEDUCT from it.
 *            Fund A balance = Σ(investor amounts) − Σ(sector amounts).
 * - FundB:   Sector-only surplus credited to Fund B reserve.
 *            NO investor side. Sector amounts INCREASE the fund.
 *            Fund B balance = +Σ(sector amounts).
 * - Direct:  Sector ↔ Investor direct transfer (no fund ledger).
 *            Two modes: investor_wise (single) and as_per_invest (bulk by ratio).
 */
enum AdjustmentType: string
{
    case FundA = 'fund_a';
    case FundB = 'fund_b';
    case Direct = 'direct';

    public function label(): string
    {
        return match ($this) {
            self::FundA => 'Fund A',
            self::FundB => 'Fund B',
            self::Direct => 'Direct',
        };
    }

    public function badgeVariant(): string
    {
        return match ($this) {
            self::FundA => 'info',
            self::FundB => 'warning',
            self::Direct => 'accent',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::FundA => 'Investor + sector adjustment in Fund A pool',
            self::FundB => 'Sector surplus credited to Fund B reserve',
            self::Direct => 'Sector ↔ Investor direct transfer (no fund)',
        };
    }

    /**
     * Whether this type tracks a fund balance.
     */
    public function hasFund(): bool
    {
        return $this !== self::Direct;
    }
}
