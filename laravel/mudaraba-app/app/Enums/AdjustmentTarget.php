<?php

namespace App\Enums;

/**
 * Who is being adjusted by a profit adjustment entry.
 */
enum AdjustmentTarget: string
{
    case Investor = 'investor';
    case Sector = 'sector';

    public function label(): string
    {
        return match ($this) {
            self::Investor => 'Investor',
            self::Sector => 'Sector',
        };
    }
}
