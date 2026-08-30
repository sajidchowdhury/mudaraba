<?php

namespace App\Enums;

/**
 * Type of profit adjustment.
 *
 * - FundA:   Batch adjustment (investors + sectors) tracked in Fund A.
 *            Fund A balance = Σ(investor amounts) − Σ(sector amounts).
 * - FundB:   Identical to Fund A but tracked in Fund B (separate balancing pool).
 * - Direct:  Single-investor adjustment with no sector side (was Type C).
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
            self::FundA => 'Batch adjustment tracked in Fund A balancing pool',
            self::FundB => 'Batch adjustment tracked in Fund B balancing pool',
            self::Direct => 'Single investor adjustment (no sector side, no fund tracking)',
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
