<?php

namespace App\Enums;

/**
 * Tingkat Surat Peringatan. Semua tingkat memakai kode dokumen SP;
 * tingkat disimpan di kolom Reference Employee_Documents.
 */
enum WarningLetterLevel: string
{
    case SP1 = 'SP1';
    case SP1_FINAL = 'SP1T';
    case SP2 = 'SP2';
    case SP3 = 'SP3';

    public function label(): string
    {
        return match ($this) {
            self::SP1 => 'Surat Peringatan Pertama (SP-1)',
            self::SP1_FINAL => 'Surat Peringatan Pertama dan Terakhir (SP-1 & Terakhir)',
            self::SP2 => 'Surat Peringatan Kedua (SP-2)',
            self::SP3 => 'Surat Peringatan Ketiga (SP-3)',
        };
    }

    public function shortLabel(): string
    {
        return match ($this) {
            self::SP1 => 'SP-1',
            self::SP1_FINAL => 'SP-1 & Terakhir',
            self::SP2 => 'SP-2',
            self::SP3 => 'SP-3',
        };
    }

    public function ordinal(): string
    {
        return match ($this) {
            self::SP1 => 'Pertama',
            self::SP1_FINAL => 'Pertama dan Terakhir',
            self::SP2 => 'Kedua',
            self::SP3 => 'Ketiga',
        };
    }

    public function next(): ?self
    {
        return match ($this) {
            self::SP1 => self::SP2,
            self::SP2 => self::SP3,
            self::SP3, self::SP1_FINAL => null,
        };
    }

    /**
     * Masa berlaku statis per tingkat (tidak dipilih di form).
     */
    public function validityMonths(): int
    {
        return match ($this) {
            self::SP1 => 6,
            self::SP1_FINAL, self::SP2, self::SP3 => 12,
        };
    }

    /**
     * SP-1, SP-2, dan SP-3 memakai format yang sama (pasal tanpa jenis peraturan + uraian);
     * SP-1 & Terakhir memakai rincian pelanggaran dengan kutipan pasal.
     */
    public function usesViolationDetails(): bool
    {
        return $this === self::SP1_FINAL;
    }
}
