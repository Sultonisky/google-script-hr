<?php

namespace App\Enums;

enum EmployeeStatus: string
{
    case PKWT       = 'PKWT';
    case PKWTT      = 'PKWTT';
    case PROBATION  = 'Probation';
    case OUTSOURCE  = 'Outsource';
    case RESIGNED   = 'Resigned';
    case TERMINATED = 'Terminated';

    public function badgeClass(): string
    {
        return match($this) {
            self::PKWTT      => 'bg-success text-white',
            self::PKWT       => 'bg-primary text-white',
            self::PROBATION  => 'bg-warning text-dark',
            self::OUTSOURCE  => 'bg-info text-white',
            self::RESIGNED,
            self::TERMINATED => 'bg-danger text-white',
        };
    }
}
