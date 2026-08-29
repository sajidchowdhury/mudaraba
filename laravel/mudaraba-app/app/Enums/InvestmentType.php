<?php

namespace App\Enums;

/**
 * Direction of an investment capital movement.
 *
 * - Add:      investor/sector puts money INTO the pool (balance increases)
 * - Withdraw: investor/sector takes money OUT of the pool (balance decreases)
 */
enum InvestmentType: string
{
    case Add = 'add';
    case Withdraw = 'withdraw';

    /**
     * The signed multiplier for arithmetic. Add = +1, Withdraw = -1.
     * Used by balance computation: SUM(amount * sign()).
     */
    public function sign(): int
    {
        return match ($this) {
            self::Add => 1,
            self::Withdraw => -1,
        };
    }

    /**
     * Human-readable label for UI.
     */
    public function label(): string
    {
        return match ($this) {
            self::Add => 'Add Investment',
            self::Withdraw => 'Withdraw Investment',
        };
    }

    /**
     * Tailwind/Badge variant class for UI display.
     */
    public function badgeVariant(): string
    {
        return match ($this) {
            self::Add => 'success',
            self::Withdraw => 'danger',
        };
    }

    /**
     * Parse a value with graceful fallback.
     */
    public static function tryFromStrict(string $value): self
    {
        return self::from($value);
    }
}
