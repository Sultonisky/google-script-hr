<?php

namespace App\Services;

use App\DTOs\CandidateData;
use App\DTOs\EmployeeData;
use Barryvdh\DomPDF\Facade\Pdf;

class PdfGeneratorService
{
    /**
     * Resolve company profile array from branch name — 1:1 with getCompanyProfile() in GAS js/pdfExport.html
     */
    private function resolveCompany(string $branchName = ''): array
    {
        $b = strtolower($branchName);

        if (str_contains($b, 'stein')) {
                return [
                    'name'    => 'PT STEIN PERKASA INTERNASIONAL',
                    'address' => 'Rukan Mangga Dua Square Blok H No. 18-21, Jl. Gunung Sahari Raya Nomor 1, Kel. Ancol, Kec. Pademangan, Kota Jakarta Utara, DKI Jakarta - 14430',
                    'city'    => 'Jakarta',
                    'brand'   => 'STEIN',
                    'code'    => 'SPI',
                ];
        }

        if (str_contains($b, 'injeksi')) {
                return [
                    'name'    => 'PT PERKASA INJEKSI INDONESIA',
                    'address' => 'Jl. Gajah Tunggal, Kp. Gembor, RT.004/RW.001, Kel. Pasir Jaya, Kec. Jatiuwung, Kota Tangerang, Banten 15135',
                    'city'    => 'Tangerang',
                    'brand'   => 'PERKASA INJEKSI',
                    'code'    => 'PII',
                ];
        }

        if (str_contains($b, 'mitra') || str_contains($b, 'elektro')) {
                return [
                    'name'    => 'PT MITRA ELEKTRO PERKASA',
                    'address' => 'Rukan Mangga Dua Square Blok H No. 18-21, Jln. Gunung Sahari Raya Nomor 1, Kel. Ancol/Kec. Pademangan, Kota Jakarta Utara, DKI Jakarta',
                    'city'    => 'Jakarta',
                    'brand'   => 'MITRA ELEKTRO',
                    'code'    => 'MEP',
                ];
        }

        // Default: PT Mahakarya Sukses Indonesia
                return [
                    'name'    => 'PT MAHAKARYA SUKSES INDONESIA',
                    'address' => 'Jl. Gajah Tunggal, Kp. Gembor, RT.004/RW.001, Kel. Pasir Jaya, Kec. Jatiuwung, Kota Tangerang, Banten 15135',
                    'city'    => 'Tangerang',
                    'brand'   => 'MITO',
                    'code'    => 'MSI',
                ];
    }

    /**
     * Extract branch name from multiple possible sources.
     */
    private function getBranchName(EmployeeData|CandidateData|null $subject, array $extraData = []): string
    {
        return $extraData['branch_name']
            ?? $extraData['company_entity']
            ?? ($subject instanceof EmployeeData ? ($subject->branchName ?? '') : '')
            ?? '';
    }

    /**
     * Generate Candidate Resume / Profile PDF.
     */
    public function generateCandidateResumePdf(CandidateData $candidate): \Barryvdh\DomPDF\PDF
    {
        $company = $this->resolveCompany('');
        return Pdf::loadView('pdf.candidate-profile', compact('candidate', 'company'))
            ->setPaper('a4', 'portrait');
    }

    /**
     * Generate Offering Letter PDF (1:1 with GAS exportOfferingLetterPDF).
     */
    public function generateOfferingLetterPdf(CandidateData $candidate, array $extraData = []): \Barryvdh\DomPDF\PDF
    {
        $branchName = $extraData['branch_name'] ?? $extraData['company_entity'] ?? '';
        $company = $this->resolveCompany($branchName);

        // Resolve salary values — support both formats from form (formatted "4.000.000" or raw "4000000")
        $cleanNum = fn($v) => (int) preg_replace('/[^0-9]/', '', (string) ($v ?? '0'));
        $salaryBasic    = $cleanNum($extraData['salary_basic'] ?? $extraData['salaryBasic'] ?? $candidate->expectedSalary ?? 0);
        $allowPulsa     = $cleanNum($extraData['allow_pulsa'] ?? $extraData['allowPulsa'] ?? 100000);
        $allowTransport = $cleanNum($extraData['allow_transport'] ?? $extraData['allowTransport'] ?? 500000);
        $totalBruto     = $salaryBasic + $allowPulsa + $allowTransport;

        $extraData = array_merge($extraData, [
            'salary_basic'    => $salaryBasic,
            'allow_pulsa'     => $allowPulsa,
            'allow_transport' => $allowTransport,
            'total_bruto'     => $totalBruto,
        ]);

        return Pdf::loadView('pdf.offering-letter', compact('candidate', 'extraData', 'company'))
            ->setPaper('a4', 'portrait');
    }

    /**
     * Generate Perjanjian Kerja Waktu Tertentu (PKWT) PDF.
     * 1:1 with GAS exportKontrakPKWTPDF().
     */
    public function generateKontrakPkwtPdf(EmployeeData|CandidateData $subject, array $extraData = []): \Barryvdh\DomPDF\PDF
    {
        $branchName = $this->getBranchName($subject, $extraData);
        $company = $this->resolveCompany($branchName);

        $employee  = $subject instanceof EmployeeData  ? $subject : null;
        $candidate = $subject instanceof CandidateData ? $subject : null;

        return Pdf::loadView('pdf.kontrak-pkwt', compact('employee', 'candidate', 'extraData', 'company'))
            ->setPaper('a4', 'portrait');
    }

    /**
     * Generate SK Pengangkatan Karyawan Tetap (PKWTT) PDF.
     * 1:1 with GAS exportSKTetapPDF().
     */
    public function generateSkPengangkatanPdf(EmployeeData $employee, array $extraData = []): \Barryvdh\DomPDF\PDF
    {
        $company = $this->resolveCompany($this->getBranchName($employee, $extraData));
        return Pdf::loadView('pdf.sk-pengangkatan', compact('employee', 'extraData', 'company'))
            ->setPaper('a4', 'portrait');
    }

    /**
     * Generate SK Resign / Offboarding / Pemberhentian PDF.
     * 1:1 with GAS exportOffboardingLetterPDF().
     */
    public function generateSkOffPdf(EmployeeData $employee, array $extraData = []): \Barryvdh\DomPDF\PDF
    {
        $company = $this->resolveCompany($this->getBranchName($employee, $extraData));
        return Pdf::loadView('pdf.sk-off', compact('employee', 'extraData', 'company'))
            ->setPaper('a4', 'portrait');
    }

    /**
     * Generate Surat Keterangan untuk BPJS Ketenagakerjaan PDF.
     * 1:1 with GAS exportSuratBPJS().
     */
    public function generateSuratBpjsPdf(EmployeeData $employee, array $extraData = []): \Barryvdh\DomPDF\PDF
    {
        $company = $this->resolveCompany($this->getBranchName($employee, $extraData));
        return Pdf::loadView('pdf.surat-bpjs', compact('employee', 'extraData', 'company'))
            ->setPaper('a4', 'portrait');
    }

    /**
     * Generate SK Mutasi / Rotasi / Demosi / Promosi PDF.
     * 1:1 with GAS exportRotationLetterPDF().
     */
    public function generateSkRotationPdf(EmployeeData $employee, array $extraData = []): \Barryvdh\DomPDF\PDF
    {
        $company = $this->resolveCompany($this->getBranchName($employee, $extraData));
        return Pdf::loadView('pdf.sk-rotation', compact('employee', 'extraData', 'company'))
            ->setPaper('a4', 'portrait');
    }

    /**
     * Generate Surat Keterangan Kerja (Paklaring) PDF.
     * 1:1 with GAS exportPaklaringPDF().
     */
    public function generatePaklaringPdf(EmployeeData $employee, array $extraData = []): \Barryvdh\DomPDF\PDF
    {
        $company = $this->resolveCompany($this->getBranchName($employee, $extraData));
        return Pdf::loadView('pdf.paklaring', compact('employee', 'extraData', 'company'))
            ->setPaper('a4', 'portrait');
    }
}
