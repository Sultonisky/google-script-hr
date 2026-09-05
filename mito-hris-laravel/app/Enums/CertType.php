<?php

namespace App\Enums;

enum CertType: string
{
    case PROFESSIONAL     = 'Professional';
    case LICENSE          = 'License';
    case INTERNAL_TRAINING = 'Internal Training';
    case EXTERNAL_TRAINING = 'External Training';
    case COMPLIANCE       = 'Compliance';
    case OTHER            = 'Other';

    public function label(): string
    {
        return match ($this) {
            self::PROFESSIONAL      => 'Professional',
            self::LICENSE           => 'License',
            self::INTERNAL_TRAINING => 'Internal Training',
            self::EXTERNAL_TRAINING => 'External Training',
            self::COMPLIANCE        => 'Compliance',
            self::OTHER             => 'Other',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::PROFESSIONAL      => 'bg-primary text-white',
            self::LICENSE           => 'bg-success text-white',
            self::INTERNAL_TRAINING => 'bg-info text-white',
            self::EXTERNAL_TRAINING => 'bg-info text-white',
            self::COMPLIANCE        => 'bg-warning text-dark',
            self::OTHER             => 'bg-secondary text-white',
        };
    }

    public function prefix(): string
    {
        return 'SRT';
    }
}
