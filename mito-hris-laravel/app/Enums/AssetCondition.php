<?php

namespace App\Enums;

enum AssetCondition: string
{
    case GOOD    = 'Good';
    case FAIR    = 'Fair';
    case POOR    = 'Poor';
    case DAMAGED = 'Damaged';

    public function label(): string
    {
        return match ($this) {
            self::GOOD    => 'Good',
            self::FAIR    => 'Fair',
            self::POOR    => 'Poor',
            self::DAMAGED => 'Damaged',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::GOOD    => 'bg-success text-white',
            self::FAIR    => 'bg-info text-white',
            self::POOR    => 'bg-warning text-dark',
            self::DAMAGED => 'bg-danger text-white',
        };
    }
}
