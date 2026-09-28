<?php

namespace App\Enums;

/**
 * Official HRIS document / SK type codes.
 * Sequence (001) is fixed per employee; only the code changes per document.
 * Format: {seq}/{CODE}/{ENTITY}/{ROMAN}/{YEAR}, e.g. 001/SKPR/MSI/IX/2026.
 * Kontrak PKWT TAD (outsource) also uses PKWT.
 * SURAT_BPJS is tracked in Employee_Documents without a number (Nomor kosong).
 */
enum SkDocumentType: string
{
    case PKWT = 'PKWT';
    case PENGANGKATAN = 'SKP';
    case MUTASI = 'SKM';
    case DEMOSI = 'SKD';
    case PROMOSI = 'SKPR';
    case OFFBOARDING = 'SKO';
    case PAKLARING = 'SPAK';
    case SURAT_BPJS = 'BPJS';
    // Surat Peringatan (SP) — reserved; feature not implemented yet.

    public function label(): string
    {
        return match ($this) {
            self::PKWT => 'Kontrak PKWT',
            self::PENGANGKATAN => 'SK Pengangkatan',
            self::MUTASI => 'SK Mutasi',
            self::DEMOSI => 'SK Demosi',
            self::PROMOSI => 'SK Promosi',
            self::OFFBOARDING => 'SK Offboarding',
            self::PAKLARING => 'Paklaring',
            self::SURAT_BPJS => 'Surat Keterangan BPJS',
        };
    }

    public function isNumbered(): bool
    {
        return $this !== self::SURAT_BPJS;
    }

    public function isContract(): bool
    {
        return $this === self::PKWT;
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
