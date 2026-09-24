<?php

namespace App\Enums;

/**
 * Official HRIS document / SK type codes.
 * Sequence (001) is fixed per employee; only the code changes per document.
 */
enum SkDocumentType: string
{
    case PKWT = 'PKWT';
    case PKTAD = 'PKTAD';
    case PENGANGKATAN = 'SKP';
    case MUTASI = 'SKM';
    case DEMOSI = 'SKD';
    case PROMOSI = 'SKPR';
    case OFFBOARDING = 'SKO';
    case PAKLARING = 'SPAK';
    // Surat Peringatan (SP) — reserved; feature not implemented yet.

    public function label(): string
    {
        return match ($this) {
            self::PKWT => 'Kontrak PKWT',
            self::PKTAD => 'Kontrak PKWT TAD',
            self::PENGANGKATAN => 'SK Pengangkatan',
            self::MUTASI => 'SK Mutasi',
            self::DEMOSI => 'SK Demosi',
            self::PROMOSI => 'SK Promosi',
            self::OFFBOARDING => 'SK Offboarding',
            self::PAKLARING => 'Paklaring',
        };
    }

    public function isContract(): bool
    {
        return $this === self::PKWT || $this === self::PKTAD;
    }

    public static function fromRotationType(string $rotationType): self
    {
        return match (ucfirst(strtolower(trim($rotationType)))) {
            'Promosi' => self::PROMOSI,
            'Demosi' => self::DEMOSI,
            'Mutasi' => self::MUTASI,
            default => self::MUTASI,
        };
    }
}
