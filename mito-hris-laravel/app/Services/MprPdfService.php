<?php

namespace App\Services;

use App\DTOs\MprData;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as DomPdfInstance;

class MprPdfService
{
    /**
     * Resolve company profile array from entity code OR company full name.
     * Supports both new (entity code: 'MSI', 'SPI', 'PII', 'MEP')
     * and legacy (full company name) inputs.
     */
    public function resolveCompany(string $entityOrName = ''): array
    {
        $b = strtolower(trim($entityOrName));

        // Match by entity code first (new format)
        if ($b === 'spi' || str_contains($b, 'stein')) {
            return [
                'name'    => 'PT STEIN PERKASA INTERNASIONAL',
                'address' => 'Rukan Mangga Dua Square Blok H No. 18-21, Jl. Gunung Sahari Raya Nomor 1, Kel. Ancol, Kec. Pademangan, Kota Jakarta Utara, DKI Jakarta - 14430',
                'city'    => 'Jakarta',
                'brand'   => 'STEIN',
                'code'    => 'SPI',
            ];
        }

        if ($b === 'pii' || str_contains($b, 'injeksi')) {
            return [
                'name'    => 'PT PERKASA INJEKSI INDONESIA',
                'address' => 'Jl. Gajah Tunggal, Kp. Gembor, RT.004/RW.001, Kel. Pasir Jaya, Kec. Jatiuwung, Kota Tangerang, Banten 15135',
                'city'    => 'Tangerang',
                'brand'   => 'PERKASA INJEKSI',
                'code'    => 'PII',
            ];
        }

        if ($b === 'mep' || str_contains($b, 'mitra') || str_contains($b, 'elektro')) {
            return [
                'name'    => 'PT MITRA ELEKTRO PERKASA',
                'address' => 'Rukan Mangga Dua Square Blok H No. 18-21, Jln. Gunung Sahari Raya Nomor 1, Kel. Ancol/Kec. Pademangan, Kota Jakarta Utara, DKI Jakarta',
                'city'    => 'Jakarta',
                'brand'   => 'MITRA ELEKTRO',
                'code'    => 'MEP',
            ];
        }

        // Default: PT Mahakarya Sukses Indonesia (MSI)
        return [
            'name'    => 'PT MAHAKARYA SUKSES INDONESIA',
            'address' => 'Jl. Gajah Tunggal, Kp. Gembor, RT.004/RW.001, Kel. Pasir Jaya, Kec. Jatiuwung, Kota Tangerang, Banten 15135',
            'city'    => 'Tangerang',
            'brand'   => 'MITO',
            'code'    => 'MSI',
        ];
    }

    /**
     * Generate official MPR PDF from saved MprData.
     */
    public function generate(MprData $mpr): DomPdfInstance
    {
        // Resolve company from entity code (new) or legacy company field
        $company = $this->resolveCompany($mpr->entity ?? $mpr->company ?? '');

        return Pdf::loadView('pdf.mpr', compact('mpr', 'company'))
            ->setPaper('a4', 'portrait');
    }
}
