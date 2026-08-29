<?php

namespace App\Enums;

/**
 * Status of a single sector's monthly profit entry.
 *
 * - Draft:     Estimated profit entered, actuals not yet known.
 * - Finalized: Actual profit entered and reconciled.
 */
enum SectorProfitStatus: string
{
    case Draft = 'draft';
    case Finalized = 'finalized';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Finalized => 'Finalized',
        };
    }

    public function badgeVariant(): string
    {
        return match ($this) {
            self::Draft => 'warning',
            self::Finalized => 'success',
        };
    }
}
