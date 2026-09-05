<?php

namespace App\Enums;

enum CertStatus: string
{
    case ACTIVE   = 'Active';
    case EXPIRED  = 'Expired';
    case REVOKED  = 'Revoked';
    case SUSPENDED = 'Suspended';

    public function label(): string
    {
        return match ($this) {
            self::ACTIVE   => 'Active',
            self::EXPIRED  => 'Expired',
            self::REVOKED  => 'Revoked',
            self::SUSPENDED => 'Suspended',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::ACTIVE   => 'bg-success text-white',
            self::EXPIRED  => 'bg-danger text-white',
            self::REVOKED  => 'bg-dark text-white',
            self::SUSPENDED => 'bg-warning text-dark',
        };
    }
}
