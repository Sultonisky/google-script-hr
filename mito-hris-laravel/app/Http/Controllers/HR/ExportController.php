<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Repositories\Contracts\CandidateRepositoryInterface;
use App\Repositories\Contracts\EmployeeRepositoryInterface;
use App\Services\PdfGeneratorService;
use App\Services\ProbationService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportController extends Controller
{
    protected CandidateRepositoryInterface $candidateRepo;
    protected EmployeeRepositoryInterface $employeeRepo;
    protected PdfGeneratorService $pdfService;
    protected ProbationService $probationService;

    public function __construct(
        CandidateRepositoryInterface $candidateRepo,
        EmployeeRepositoryInterface $employeeRepo,
        PdfGeneratorService $pdfService,
        ProbationService $probationService
    ) {
        $this->candidateRepo    = $candidateRepo;
        $this->employeeRepo     = $employeeRepo;
        $this->pdfService       = $pdfService;
        $this->probationService = $probationService;
    }

    private function employeeDocumentStem($employee): string
    {
        $name = preg_replace('/[^a-zA-Z0-9]+/', '_', trim($employee->fullName ?? 'Employee'));
        $id   = preg_replace('/[^a-zA-Z0-9_-]+/', '_', trim($employee->employeeId ?? 'Unknown'));

        return trim($name ?: 'Employee', '_') . '_Employee_' . ($id ?: 'Unknown');
    }

    /**
     * Download or stream Candidate Resume PDF.
     */
    public function candidatePdf(string $id)
    {
        $candidate = $this->candidateRepo->findById($id);
        if (!$candidate) {
            abort(404, 'Data kandidat tidak ditemukan.');
        }

        $pdf = $this->pdfService->generateCandidateResumePdf($candidate);
        return $pdf->stream("Resume_{$candidate->recruitmentId}_{$candidate->fullName}.pdf");
    }

    /**
     * Download Offering Letter PDF.
     */
    public function offeringLetterPdf(Request $request, string $id)
    {
        $candidate = $this->candidateRepo->findById($id);
        if (!$candidate) {
            abort(404, 'Data kandidat tidak ditemukan.');
        }

        $extraData = $request->all();
        $pdf = $this->pdfService->generateOfferingLetterPdf($candidate, $extraData);
        return $pdf->download("Offering_Letter_{$candidate->recruitmentId}.pdf");
    }

    /**
     * Download Kontrak PKWT PDF.
     * Lookup priority: employeeId → recruitmentId (fallback, handles Google Sheets cache race condition).
     * Selalu mengembalikan PDF response (Content-Type: application/pdf) agar
     * Fetch API di frontend dapat membaca sebagai Blob dan auto-download.
     */
    public function kontrakPkwtPdf(Request $request, string $id)
    {
        $employee  = $this->employeeRepo->findById($id);
        $candidate = null;

        if (!$employee) {
            // Coba lookup berdasarkan recruitmentId (pdfId bisa jadi recruitmentId)
            $candidate = $this->candidateRepo->findById($id);
        }

        // Fallback: race condition — employee baru di-write ke sheet tapi belum ter-cache.
        // Gunakan recruitment_id dari query param untuk cari kandidat.
        if (!$employee && !$candidate) {
            $fallbackRid = $request->query('recruitment_id');
            if ($fallbackRid) {
                $candidate = $this->candidateRepo->findById($fallbackRid);
            }
        }

        if (!$employee && !$candidate) {
            abort(404, 'Data karyawan/kandidat tidak ditemukan. Coba reload halaman dan export ulang.');
        }

        $subject   = $employee ?: $candidate;
        $extraData = $request->all();

        // Pastikan extraData menyertakan employeeId & recruitmentId agar template
        // mendapat nilai yang benar meskipun di-lookup via candidate fallback.
        if ($employee && empty($extraData['employee_id'])) {
            $extraData['employee_id'] = $employee->employeeId;
        } elseif ($candidate) {
            if (empty($extraData['employee_id'])) {
                $extraData['employee_id'] = $candidate->employeeId ?: $candidate->recruitmentId;
            }
            if (empty($extraData['recruitment_id'])) {
                $extraData['recruitment_id'] = $candidate->recruitmentId;
            }
        }

        $pdf   = $this->pdfService->generateKontrakPkwtPdf($subject, $extraData);
        $nameId = $employee
            ? $employee->employeeId
            : ($candidate->employeeId ?: $candidate->recruitmentId);

        // Gunakan download() agar browser menerima disposition attachment
        // dan Content-Type application/pdf — dibutuhkan oleh Fetch+Blob di frontend.
        return $pdf->download("Kontrak_PKWT_{$nameId}.pdf");
    }

    /**
     * Download SK Pengangkatan Karyawan Tetap (PKWTT) PDF.
     */
    public function skPengangkatanPdf(Request $request, string $id)
    {
        $employee = $this->employeeRepo->findById($id);
        if (!$employee) {
            abort(404, 'Data karyawan tidak ditemukan.');
        }

        $extraData = $request->all();
        $pdf = $this->pdfService->generateSkPengangkatanPdf($employee, $extraData);
        return $pdf->download("SK_Pengangkatan_{$employee->employeeId}.pdf");
    }

    /**
     * Download SK Resign / Offboarding PDF.
     */
    public function skOffPdf(Request $request, string $id)
    {
        $employee = $this->employeeRepo->findById($id);
        if (!$employee) {
            abort(404, 'Data karyawan tidak ditemukan.');
        }

        $extraData = $request->all();
        $pdf = $this->pdfService->generateSkOffPdf($employee, $extraData);
        return $pdf->download("SK_Offboarding_{$this->employeeDocumentStem($employee)}.pdf");
    }

    /**
     * Download Surat Keterangan untuk BPJS Ketenagakerjaan PDF.
     */
    public function suratBpjsPdf(Request $request, string $id)
    {
        $employee = $this->employeeRepo->findById($id);
        if (!$employee) {
            abort(404, 'Data karyawan tidak ditemukan.');
        }

        $extraData = $request->all();
        $pdf = $this->pdfService->generateSuratBpjsPdf($employee, $extraData);
        return $pdf->download("Surat_BPJS_{$this->employeeDocumentStem($employee)}.pdf");
    }

    /**
     * Generate all offboarding PDFs and return them as one ZIP download.
     */
    public function offboardingBundlePdf(Request $request, string $id)
    {
        $employee = $this->employeeRepo->findById($id);
        if (!$employee) {
            abort(404, 'Data karyawan tidak ditemukan.');
        }

        $extraData = $request->all();
        $fileStem = $this->employeeDocumentStem($employee);
        $documents = [
            "SK_Offboarding_{$fileStem}.pdf" => $this->pdfService->generateSkOffPdf($employee, $extraData)->output(),
            "Surat_BPJS_{$fileStem}.pdf" => $this->pdfService->generateSuratBpjsPdf($employee, $extraData)->output(),
            "Paklaring_{$fileStem}.pdf" => $this->pdfService->generatePaklaringPdf($employee, $extraData)->output(),
        ];

        $zipPath = tempnam(storage_path('app'), 'offboarding_');
        $zip = new \ZipArchive();
        if (!$zipPath || $zip->open($zipPath, \ZipArchive::OVERWRITE) !== true) {
            if ($zipPath) @unlink($zipPath);
            abort(500, 'Gagal membuat bundle dokumen offboarding.');
        }

        foreach ($documents as $filename => $content) {
            $zip->addFromString($filename, $content);
        }
        $zip->close();

        return response()->download(
            $zipPath,
            "Dokumen_Offboarding_{$this->employeeDocumentStem($employee)}.zip",
            ['Content-Type' => 'application/zip']
        )->deleteFileAfterSend(true);
    }

    /**
     * Download SK Rotasi/Mutasi/Promosi/Demosi PDF.
     */
    public function skRotationPdf(Request $request, string $id)
    {
        $employee = $this->employeeRepo->findById($id);
        if (!$employee) {
            abort(404, 'Data karyawan tidak ditemukan.');
        }

        $extraData   = $request->all();
        $rotationType = $extraData['rotation_type'] ?? $extraData['rotationType'] ?? ($employee->typeOfRotation ?? 'Rotasi');

        // Type-aware filename — 1:1 GAS fileName pattern
        $typeSlug = match (ucfirst(strtolower($rotationType))) {
            'Promosi' => 'Promosi',
            'Demosi'  => 'Demosi',
            'Mutasi'  => 'Mutasi',
            default   => 'Rotasi',
        };
        $safeName = preg_replace('/[^a-zA-Z0-9_]/', '_', $employee->fullName ?? $employee->employeeId);
        $filename = "SK_{$typeSlug}_{$safeName}_{$employee->employeeId}.pdf";

        $pdf = $this->pdfService->generateSkRotationPdf($employee, $extraData);
        return $pdf->download($filename);
    }

    /**
     * Download Paklaring PDF.
     * Called after isPutusKontrak evaluation decision.
     * Query params: letter_number, evalId, last_working_date (set by ProbationController)
     */
    public function paklaringPdf(Request $request, string $id)
    {
        $employee = $this->employeeRepo->findById($id);
        if (!$employee) {
            // Employee status was just changed to Terminated — clear cache and retry
            abort(404, 'Data karyawan tidak ditemukan. Silakan coba lagi.');
        }

        $extraData = $request->all();

        // Ensure last_working_date is populated for Paklaring content
        // Priority: query param → employee resignDate → today (1:1 GAS exportPaklaringPDF)
        if (empty($extraData['last_working_date']) && !empty($employee->resignDate)) {
            $extraData['last_working_date'] = $employee->resignDate;
        }
        if (empty($extraData['last_working_date'])) {
            $extraData['last_working_date'] = now()->timezone('Asia/Jakarta')->format('Y-m-d');
        }

        $pdf = $this->pdfService->generatePaklaringPdf($employee, $extraData);
        return $pdf->download("Paklaring_{$this->employeeDocumentStem($employee)}.pdf");
    }

    /**
     * Download Performance Review – Evaluation Form PDF (2026).
     * Reads latest evaluation data from kandidat_probation for the given employee + eval_id.
     */
    public function performanceReviewPdf(Request $request, string $id)
    {
        $employee = $this->employeeRepo->findById($id);
        if (!$employee) {
            abort(404, 'Data karyawan tidak ditemukan.');
        }

        $evalId = $request->query('eval_id', '');

        // Fetch eval history and find matching eval record (or use most recent)
        $history = $this->probationService->getEvalHistory($id);
        $evalData = [];
        if (!empty($history)) {
            if ($evalId) {
                foreach ($history as $h) {
                    if (($h['evalId'] ?? '') === $evalId) {
                        $evalData = $h;
                        break;
                    }
                }
            }
            // Fallback: use most recent (history is sorted newest-first)
            if (empty($evalData)) {
                $evalData = $history[0];
            }
        }

        // Merge approval sign-off data: prefer query params, fallback to evalData (from sheet)
        $extraData = $request->only([
            'reviewer_name',
            'approval_dept',
            'approval_dept_name',
            'approval_dept_date',
            'approval_hrbp',
            'approval_hrbp_name',
            'approval_hrbp_date',
        ]);
        // If query params empty, use data from evalData (persisted in sheet)
        foreach (
            [
                'reviewer_name',
                'approval_dept',
                'approval_dept_name',
                'approval_dept_date',
                'approval_hrbp',
                'approval_hrbp_name',
                'approval_hrbp_date'
            ] as $field
        ) {
            if (empty($extraData[$field]) && !empty($evalData[$field])) {
                $extraData[$field] = $evalData[$field];
            }
        }

        $pdf = $this->pdfService->generatePerformanceReviewPdf($employee, $evalData, $extraData);
        $safeName = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $employee->employeeId ?? 'emp');
        return $pdf->download("PerformanceReview_{$safeName}.pdf");
    }


    /**
     * Export all Candidates to CSV (1:1 with backend/Export.gs).
     */
    public function exportCandidatesCsv(): StreamedResponse
    {
        $candidates = $this->candidateRepo->getAll();

        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="Data_Kandidat_MITO_' . date('Ymd_His') . '.csv"',
        ];

        return response()->stream(function () use ($candidates) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'Recruitment ID',
                'Nama Lengkap',
                'NIK',
                'Email',
                'No Telepon',
                'Kota',
                'Posisi',
                'Pendidikan',
                'Pengalaman',
                'Ekspektasi Gaji',
                'Status',
                'Tanggal Daftar'
            ]);

            foreach ($candidates as $c) {
                fputcsv($handle, [
                    $c->recruitmentId,
                    $c->fullName,
                    "'" . $c->nik,
                    $c->email,
                    "'" . $c->phone,
                    $c->city,
                    $c->positionApplied,
                    $c->education,
                    $c->workExperience,
                    $c->expectedSalary,
                    $c->status,
                    $c->createdDate
                ]);
            }
            fclose($handle);
        }, 200, $headers);
    }

    /**
     * Export all Employees to CSV (1:1 with backend/Export.gs).
     */
    public function exportEmployeesCsv(): StreamedResponse
    {
        $employees = $this->employeeRepo->getAll();

        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="Data_Karyawan_MITO_' . date('Ymd_His') . '.csv"',
        ];

        return response()->stream(function () use ($employees) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'Employee ID',
                'Nama Lengkap',
                'NIK',
                'Email',
                'No Telepon',
                'Departemen',
                'Posisi',
                'Status Employee',
                'Tanggal Masuk'
            ]);

            foreach ($employees as $e) {
                fputcsv($handle, [
                    $e->employeeId,
                    $e->fullName,
                    "'" . $e->nikNpwp,
                    $e->personalEmail,
                    "'" . $e->mobilePhone,
                    $e->department,
                    $e->jobPosition,
                    $e->statusEmployee,
                    $e->joinDate
                ]);
            }
            fclose($handle);
        }, 200, $headers);
    }
}
