<?php

namespace App\Enums;

enum CertType: string
{
    case SNI = 'SNI';
    case ISO = 'ISO';
    case K3  = 'K3';
    case FOOD_SAFETY = 'Food Safety';
    case PRODUCT_SAFETY = 'Product Safety';

    public function label(): string
    {
        return match ($this) {
            self::SNI => 'SNI',
            self::ISO => 'ISO',
            self::K3  => 'K3',
            self::FOOD_SAFETY => 'Food Safety',
            self::PRODUCT_SAFETY => 'Product Safety',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::SNI => 'Standar produk atau barang',
            self::ISO => 'Sistem manajemen perusahaan',
            self::K3  => 'Keselamatan dan kesehatan kerja',
            self::FOOD_SAFETY => 'Keamanan pangan dan material kontak makanan',
            self::PRODUCT_SAFETY => 'Keselamatan dan kepatuhan produk konsumen',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::SNI => 'cert-sni',
            self::ISO => 'cert-iso',
            self::K3  => 'cert-k3',
            self::FOOD_SAFETY => 'cert-food-safety',
            self::PRODUCT_SAFETY => 'cert-product-safety',
        };
    }

    public function prefix(): string
    {
        return 'SRT';
    }
}
