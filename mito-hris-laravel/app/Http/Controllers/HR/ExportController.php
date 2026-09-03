<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Repositories\Contracts\CandidateRepositoryInterface;
use App\Repositories\Contracts\EmployeeRepositoryInterface;
use App\Repositories\Contracts\AuditLogRepositoryInterface;
use App\Services\PdfGeneratorService;
use App\Services\ProbationService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx as XlsxWriter;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Font;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportController extends Controller
{
    protected CandidateRepositoryInterface $candidateRepo;
    protected EmployeeRepositoryInterface $employeeRepo;
    protected PdfGeneratorService $pdfService;
    protected ProbationService $probationService;
    protected AuditLogRepositoryInterface $auditRepo;

    public function __construct(
        CandidateRepositoryInterface $candidateRepo,
        EmployeeRepositoryInterface $employeeRepo,
        PdfGeneratorService $pdfService,
        ProbationService $probationService,
        AuditLogRepositoryInterface $auditRepo
    ) {
        $this->candidateRepo    = $candidateRepo;
        $this->employeeRepo     = $employeeRepo;
        $this->pdfService       = $pdfService;
        $this->probationService = $probationService;
        $this->auditRepo = $auditRepo;
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
        $this->auditRepo->log('Candidate', $candidate->recruitmentId, 'generated', 'candidate_resume', null, 'PDF', session('hr_user.email', 'HR Administrator'), 'Export');
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
        $this->auditRepo->log('Candidate', $candidate->recruitmentId, 'generated', 'offering_letter', null, 'PDF', session('hr_user.email', 'HR Administrator'), 'Export');
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
        $this->auditRepo->log($employee ? 'Employee' : 'Candidate', $nameId, 'generated', 'contract', null, 'PDF', session('hr_user.email', 'HR Administrator'), 'Export');
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
        $this->auditRepo->log('Employee', $employee->employeeId, 'generated', 'sk_pengangkatan', null, 'PDF', session('hr_user.email', 'HR Administrator'), 'Export');
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
        $this->auditRepo->log('Employee', $employee->employeeId, 'generated', 'sk_offboarding', null, 'PDF', session('hr_user.email', 'HR Administrator'), 'Export');
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
        $this->auditRepo->log('Employee', $employee->employeeId, 'generated', 'surat_bpjs', null, 'PDF', session('hr_user.email', 'HR Administrator'), 'Export');
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

        $this->auditRepo->log('Employee', $employee->employeeId, 'generated', 'offboarding_bundle', null, 'ZIP/PDF', session('hr_user.email', 'HR Administrator'), 'Export');

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
        $this->auditRepo->log('Employee', $employee->employeeId, 'generated', 'sk_rotation', null, 'PDF', session('hr_user.email', 'HR Administrator'), 'Export');
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
        $this->auditRepo->log('Employee', $employee->employeeId, 'generated', 'paklaring', null, 'PDF', session('hr_user.email', 'HR Administrator'), 'Export');
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
        $this->auditRepo->log('Probation', $employee->employeeId, 'generated', 'performance_review', null, 'PDF', session('hr_user.email', 'HR Administrator'), 'Export');
        return $pdf->download("PerformanceReview_{$safeName}.pdf");
    }


    /**
     * Export all Candidates to CSV (1:1 with backend/Export.gs).
     */
    public function exportCandidatesCsv(): StreamedResponse
    {
        $candidates = $this->candidateRepo->getAll();
        $this->auditRepo->log('Candidate', null, 'exported', 'format', null, ['format' => 'CSV', 'total_records' => $candidates->count()], session('hr_user.email', 'HR Administrator'), 'Export');

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
     * Export all Employees to XLSX — full canonical schema dari EmployeeData DTO.
     * Authorization: can:view_employees (Admin + Super Admin).
     * MPR Requestor tidak dapat mengakses — dilindungi portal.access + hr.auth di route group.
     */
    public function exportEmployeesXlsx(): StreamedResponse
    {
        $employees = $this->employeeRepo->getAll();

        $this->auditRepo->log(
            'Employee',
            null,
            'exported',
            'format',
            null,
            ['format' => 'XLSX', 'total_records' => $employees->count()],
            session('hr_user.email', 'HR Administrator'),
            'Export'
        );

        $filename = 'Data_Karyawan_MITO_' . now()->timezone('Asia/Jakarta')->format('Ymd_His') . '.xlsx';

        // ---------------------------------------------------------------
        // Headers canonical — 36 kolom sesuai EmployeeData DTO
        // ---------------------------------------------------------------
        $headers = [
            'Employee ID',
            'Full Name',
            'Branch Name',
            'Division',
            'Department',
            'Job Position (Location)',
            'Job Position',
            'Area Kerja',
            'Lokasi Kerja',
            'Job Level',
            'Grade',
            'Join Date',
            'Status Employee',
            'Direct Superior',
            'Indirect Superior',
            'Personal Email',
            'Working Email',
            'End Date (Contract)',
            'Birth Place',
            'Birth Date',
            'Citizen ID Address',
            'Residential Address',
            'NIK - NPWP 16 digit',
            'NPWP',
            'PTKP Status',
            'Bank Name',
            'Bank Account',
            'Bank Account Holder',
            'BPJS Ketenagakerjaan',
            'BPJS Kesehatan',
            'Mobile Phone',
            'Religion',
            'Gender',
            'Marital Status',
            'Blood Type',
            'Cost Center',
        ];

        // Kolom yang harus diperlakukan sebagai string (leading zeros / identifier)
        // Index 0-based dari array $headers di atas
        $stringColumns = [22, 23, 26, 28, 29, 30]; // NIK, NPWP, Bank Account, BPJS TK, BPJS KES, Mobile

        // ---------------------------------------------------------------
        // Build Spreadsheet
        // ---------------------------------------------------------------
        $spreadsheet = new Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Data Karyawan');

        // --- Header row (row 1) ---
        foreach ($headers as $colIndex => $headerText) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex + 1);
            $cell = $sheet->getCell($colLetter . '1');
            $cell->setValue($headerText);

            // Style: bold, background #005BAC, white text
            $sheet->getStyle($colLetter . '1')->applyFromArray([
                'font' => [
                    'bold'  => true,
                    'color' => ['argb' => 'FFFFFFFF'],
                    'size'  => 11,
                ],
                'fill' => [
                    'fillType'   => Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FF005BAC'],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical'   => Alignment::VERTICAL_CENTER,
                ],
            ]);
        }

        // Freeze header row
        $sheet->freezePane('A2');

        // --- Data rows ---
        $rowIndex = 2;
        foreach ($employees as $e) {
            $rowData = [
                $e->employeeId          ?? '',
                $e->fullName            ?? '',
                $e->branchName          ?? '',
                $e->division            ?? '',
                $e->department          ?? '',
                $e->jobPositionLocation ?? '',
                $e->jobPosition         ?? '',
                $e->areaKerja           ?? '',
                $e->lokasiKerja         ?? '',
                $e->jobLevel            ?? '',
                $e->grade               ?? '',
                $e->joinDate            ?? '',
                $e->statusEmployee      ?? '',
                $e->directSuperior      ?? '',
                $e->indirectSuperior    ?? '',
                $e->personalEmail       ?? '',
                $e->workingEmail        ?? '',
                $e->endDateContract     ?? '',
                $e->birthPlace          ?? '',
                $e->birthDate           ?? '',
                $e->citizenIdAddress    ?? '',
                $e->residentialAddress  ?? '',
                $e->nikNpwp             ?? '',  // string — index 22
                $e->npwp                ?? '',  // string — index 23
                $e->ptkpStatus          ?? '',
                $e->bankName            ?? '',
                $e->bankAccount         ?? '',  // string — index 26
                $e->bankAccountHolder   ?? '',
                $e->bpjsKetenagakerjaan ?? '',  // string — index 28
                $e->bpjsKesehatan       ?? '',  // string — index 29
                $e->mobilePhone         ?? '',  // string — index 30
                $e->religion            ?? '',
                $e->gender              ?? '',
                $e->maritalStatus       ?? '',
                $e->bloodType           ?? '',
                $e->costCenter          ?? '',
            ];

            foreach ($rowData as $colIndex => $value) {
                $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex + 1);
                $cellRef   = $colLetter . $rowIndex;

                if (in_array($colIndex, $stringColumns, true) && $value !== '') {
                    // Paksa DataType::TYPE_STRING agar NIK/NPWP/dll tidak dikonversi ke number/scientific notation
                    $sheet->getCell($cellRef)->setValueExplicit($value, DataType::TYPE_STRING);
                } else {
                    $sheet->getCell($cellRef)->setValue($value);
                }
            }

            $rowIndex++;
        }

        // Auto-size columns agar header tidak terpotong
        foreach (range(1, count($headers)) as $colIndex) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex);
            $sheet->getColumnDimension($colLetter)->setAutoSize(true);
        }

        // Set document properties
        $spreadsheet->getProperties()
            ->setCreator('MITO HRIS')
            ->setTitle('Data Karyawan MITO')
            ->setDescription('Export data karyawan dari MITO HRIS — ' . now()->timezone('Asia/Jakarta')->format('d/m/Y H:i'));

        // ---------------------------------------------------------------
        // Stream response
        // ---------------------------------------------------------------
        $responseHeaders = [
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control'       => 'no-cache, no-store, must-revalidate',
            'Pragma'              => 'no-cache',
            'Expires'             => '0',
        ];

        return response()->stream(function () use ($spreadsheet) {
            $writer = new XlsxWriter($spreadsheet);
            $writer->save('php://output');
            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet);
        }, 200, $responseHeaders);
    }
}
