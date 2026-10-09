<?php

namespace App\Enums;

/**
 * Official HRIS document / SK type codes.
 * Sequence (001) is fixed per employee; only the code changes per document.
 * Format: {seq}/{CODE}/{ENTITY}/{ROMAN}/{YEAR}, e.g. 001/SKPR/MSI/IX/2026.
 * Kontrak PKWT TAD (outsource) also uses PKWT.
 * SURAT_BPJS is tracked in Employee_Documents without a number (Nomor kosong).
 * Disciplinary letters (SP-1/2/3 and SPM) are numbered but never replace Employee.Nomor SK.
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
    case SURAT_PERINGATAN = 'SP';
    case SURAT_PEMANGGILAN_MANGKIR = 'SPM';

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
            self::SURAT_PERINGATAN => 'Surat Peringatan',
            self::SURAT_PEMANGGILAN_MANGKIR => 'Surat Penggilan Mangkir',
        };
    }

    public function isNumbered(): bool
    {
        return $this !== self::SURAT_BPJS;
    }

    /**
     * Employee.Nomor SK holds the latest employment decree (SK/contract);
     * disciplinary letters are tracked only in Employee_Documents.
     */
    public function updatesEmployeeNomorSk(): bool
    {
        return !in_array($this, [self::SURAT_PERINGATAN, self::SURAT_PEMANGGILAN_MANGKIR], true);
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
