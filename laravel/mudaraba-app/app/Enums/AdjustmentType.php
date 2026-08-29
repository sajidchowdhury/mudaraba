<?php

namespace App\Enums;

/**
 * Type of advance profit adjustment.
 *
 * - TypeA: Per-date single-amount adjustment into fund type A (unique by date).
 * - TypeB: Per-date single-amount adjustment into fund type B (unique by date).
 * - TypeC: General adjustment that can target either a sector or an investor.
 */
enum AdjustmentType: string
{
    case TypeA = 'type_a';
    case TypeB = 'type_b';
    case TypeC = 'type_c';

    public function label(): string
    {
        return match ($this) {
            self::TypeA => 'Type A',
            self::TypeB => 'Type B',
            self::TypeC => 'Type C (General)',
        };
    }

    public function badgeVariant(): string
    {
        return match ($this) {
            self::TypeA => 'info',
            self::TypeB => 'warning',
            self::TypeC => 'accent',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::TypeA => 'Daily single-amount adjustment into fund A',
            self::TypeB => 'Daily single-amount adjustment into fund B',
            self::TypeC => 'General adjustment targeting a sector or investor',
        };
    }
}
