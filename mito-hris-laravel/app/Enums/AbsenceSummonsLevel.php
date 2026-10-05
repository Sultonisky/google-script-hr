<?php

namespace App\Enums;

enum AbsenceSummonsLevel: string
{
    case FIRST = 'SPM1';
    case SECOND = 'SPM2';

    public function label(): string
    {
        return match ($this) {
            self::FIRST => 'Panggilan Kerja I',
            self::SECOND => 'Panggilan Kerja II (Terakhir)',
        };
    }

    public function documentLabel(): string
    {
        return match ($this) {
            self::FIRST => 'Surat Penggilan Mangkir',
            self::SECOND => 'Surat Penggilan Mangkir II',
        };
    }

    public function reference(): string
    {
        return match ($this) {
            self::FIRST => 'Panggilan Kerja I',
            self::SECOND => 'Panggilan Kerja II',
        };
    }
}
