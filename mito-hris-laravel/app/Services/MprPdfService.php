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
     * Resolve company profile array from entity code (canonical config: hris.mpr.companies).
     */
    public function resolveCompany(string $entityCode = ''): array
    {
        $code = strtoupper(trim($entityCode));
        $companies = config('hris.mpr.companies', []);

        if (isset($companies[$code]) && is_array($companies[$code])) {
            return $companies[$code];
        }

        // Default: PT Mahakarya Sukses Indonesia (MSI)
        return $companies['MSI'] ?? [
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
        // Resolve company profile dari entity code (config hris.mpr.companies)
        $company = $this->resolveCompany($mpr->entity ?? '');

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
