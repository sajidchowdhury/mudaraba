<?php

namespace App\Enums;

/**
 * Lifecycle status of a monthly profit summary row.
 *
 * - Open:       M/Y can still edit sector profits and re-run reconciliation.
 * - Finalized:  Month-end reconciliation complete; ledgers updated; ready to lock.
 * - Locked:     No further edits permitted without admin override (audit-safe).
 */
enum MonthStatus: string
{
    case Open = 'open';
    case Finalized = 'finalized';
    case Locked = 'locked';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Open',
            self::Finalized => 'Finalized',
            self::Locked => 'Locked',
        };
    }

    public function badgeVariant(): string
    {
        return match ($this) {
            self::Open => 'warning',
            self::Finalized => 'success',
            self::Locked => 'default',
        };
    }

    /**
     * Can the M/Y still edit this month's data?
     */
    public function isEditable(): bool
    {
        return $this === self::Open;
    }

    /**
     * Can this month be deleted/rolled back?
     * Only Open months (and admin override on Finalized).
     */
    public function canRollback(bool $adminOverride = false): bool
    {
        return $this === self::Open || ($adminOverride && $this === self::Finalized);
    }
}
