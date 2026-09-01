<?php

namespace App\Services;

use App\DTOs\MprData;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as DomPdfInstance;

class MprPdfService
{
    private MarkdownRenderer $markdownRenderer;

    public function __construct(MarkdownRenderer $markdownRenderer)
    {
        $this->markdownRenderer = $markdownRenderer;
    }

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

        // Pre-render Markdown fields to safe HTML for DomPDF
        $requirementsHtml   = $this->markdownRenderer->render($mpr->requirements ?? null);
        $jobDescriptionHtml = $this->markdownRenderer->render($mpr->jobDescription ?? null);
        $notesHtml          = $this->markdownRenderer->render($mpr->notes ?? null);
        $skillsHtml         = $this->markdownRenderer->render($mpr->skillsCompetencies ?? null);
        $languagesHtml      = $this->markdownRenderer->render($mpr->languages ?? null);
        $industryHtml       = $this->markdownRenderer->render($mpr->industryReference ?? null);
        $keyResultsHtml     = $this->markdownRenderer->render($mpr->keyResultsTargets ?? null);
        $specialNotesHtml   = $this->markdownRenderer->render($mpr->specialNotes ?? null);

        return Pdf::loadView('pdf.mpr', compact(
            'mpr',
            'company',
            'requirementsHtml',
            'jobDescriptionHtml',
            'notesHtml',
            'skillsHtml',
            'languagesHtml',
            'industryHtml',
            'keyResultsHtml',
            'specialNotesHtml'
        ))->setPaper('a4', 'portrait');
    }
}
