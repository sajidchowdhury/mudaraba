<?php

namespace App\Enums;

/**
 * Direction of a director (M/Y) capital movement.
 *
 * - Withdraw: M/Y takes money OUT of the pool (reduces director due)
 * - Return:   M/Y puts money back INTO the pool (increases director due)
 */
enum DirectorTransactionType: string
{
    case Withdraw = 'withdraw';
    case Return = 'return';

    /**
     * Signed multiplier for arithmetic.
     * Withdraw decreases the M/Y's payable; Return increases it.
     */
    public function sign(): int
    {
        return match ($this) {
            self::Withdraw => -1,
            self::Return => 1,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Withdraw => 'Withdraw',
            self::Return => 'Return',
        };
    }

    public function badgeVariant(): string
    {
        return match ($this) {
            self::Withdraw => 'warning',
            self::Return => 'success',
        };
    }
}
