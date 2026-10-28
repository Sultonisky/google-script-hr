<?php

namespace App\Enums;

/**
 * Tingkat Surat Peringatan. Nomor memakai urutan SP sendiri, terpisah dari SK,
 * dengan kode SP-1, SP-2, SP-3, atau SP-F. Satu karyawan memakai angka SP yang sama
 * untuk setiap tingkat. Kolom Reference tetap menyimpan SP1, SP2, SP3, atau SP1T.
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

    /**
     * Kode di nomor surat. Angka depannya tetap nomor SK karyawan.
     */
    public function numberCode(): string
    {
        return match ($this) {
            self::SP1 => 'SP-1',
            self::SP2 => 'SP-2',
            self::SP3 => 'SP-3',
            self::SP1_FINAL => 'SP-F',
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
     * Penanda redaksi SP-1 & Terakhir di surat. Form semua tingkat memakai rincian pelanggaran.
     */
    public function usesViolationDetails(): bool
    {
        return $this === self::SP1_FINAL;
    }

    /**
     * Tingkat dari kolom Reference, atau dari judul/nomor surat lama yang belum memakai kode SP1.
     */
    public static function resolve(string $reference, string $docType = '', string $nomor = ''): ?self
    {
        $direct = self::tryFrom(strtoupper(trim($reference)));
        if ($direct !== null) {
            return $direct;
        }

        $text = mb_strtolower(trim($reference.' '.$docType.' '.$nomor));
        if ($text === '') {
            return null;
        }

        if (str_contains($text, 'sp1t') || str_contains($text, 'sp-f') || str_contains($text, 'terakhir')) {
            return self::SP1_FINAL;
        }
        if (str_contains($text, 'sp-3') || str_contains($text, 'sp3') || str_contains($text, 'ketiga')) {
            return self::SP3;
        }
        if (str_contains($text, 'sp-2') || str_contains($text, 'sp2') || str_contains($text, 'kedua')) {
            return self::SP2;
        }
        if (str_contains($text, 'sp-1') || str_contains($text, 'sp1') || str_contains($text, 'pertama')) {
            return self::SP1;
        }

        return null;
    }
}
