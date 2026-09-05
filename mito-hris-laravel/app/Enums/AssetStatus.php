<?php

namespace App\Enums;

enum AssetStatus: string
{
    case AVAILABLE   = 'Available';
    case ASSIGNED    = 'Assigned';
    case MAINTENANCE = 'Maintenance';
    case DAMAGED     = 'Damaged';
    case LOST        = 'Lost';
    case DISPOSED    = 'Disposed';

    public function label(): string
    {
        return match ($this) {
            self::AVAILABLE   => 'Available',
            self::ASSIGNED    => 'Assigned',
            self::MAINTENANCE => 'Maintenance',
            self::DAMAGED     => 'Damaged',
            self::LOST        => 'Lost',
            self::DISPOSED    => 'Disposed',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::AVAILABLE   => 'bg-success text-white',
            self::ASSIGNED    => 'bg-primary text-white',
            self::MAINTENANCE => 'bg-warning text-dark',
            self::DAMAGED     => 'bg-danger text-white',
            self::LOST        => 'bg-dark text-white',
            self::DISPOSED    => 'bg-secondary text-white',
        };
    }

    /** Can this asset still be assigned? */
    public function isAssignable(): bool
    {
        return $this === self::AVAILABLE;
    }

    /** Can this asset be returned? */
    public function isReturnable(): bool
    {
        return $this === self::ASSIGNED;
    }
}
