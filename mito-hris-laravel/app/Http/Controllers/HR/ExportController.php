<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Repositories\Contracts\CandidateRepositoryInterface;
use App\Repositories\Contracts\EmployeeRepositoryInterface;
use App\Services\PdfGeneratorService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportController extends Controller
{
    protected CandidateRepositoryInterface $candidateRepo;
    protected EmployeeRepositoryInterface $employeeRepo;
    protected PdfGeneratorService $pdfService;

    public function __construct(
        CandidateRepositoryInterface $candidateRepo,
        EmployeeRepositoryInterface $employeeRepo,
        PdfGeneratorService $pdfService
    ) {
        $this->candidateRepo = $candidateRepo;
        $this->employeeRepo = $employeeRepo;
        $this->pdfService = $pdfService;
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
     */
    public function kontrakPkwtPdf(Request $request, string $id)
    {
        $employee = $this->employeeRepo->findById($id);
        $candidate = null;
        if (!$employee) {
            $candidate = $this->candidateRepo->findById($id);
            if (!$candidate) {
                abort(404, 'Data karyawan/kandidat tidak ditemukan.');
            }
        }

        $subject = $employee ?: $candidate;
        $extraData = $request->all();
        $pdf = $this->pdfService->generateKontrakPkwtPdf($subject, $extraData);
        $nameId = $employee ? $employee->employeeId : $candidate->recruitmentId;
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
        return $pdf->download("SK_OFF_{$employee->employeeId}.pdf");
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
        return $pdf->download("Surat_BPJS_{$employee->employeeId}.pdf");
    }

    /**
     * Download SK Rotasi/Mutasi PDF.
     */
    public function skRotationPdf(Request $request, string $id)
    {
        $employee = $this->employeeRepo->findById($id);
        if (!$employee) {
            abort(404, 'Data karyawan tidak ditemukan.');
        }

        $extraData = $request->all();
        $pdf = $this->pdfService->generateSkRotationPdf($employee, $extraData);
        return $pdf->download("SK_Rotasi_{$employee->employeeId}.pdf");
    }

    /**
     * Download Paklaring PDF.
     */
    public function paklaringPdf(Request $request, string $id)
    {
        $employee = $this->employeeRepo->findById($id);
        if (!$employee) {
            abort(404, 'Data karyawan tidak ditemukan.');
        }

        $extraData = $request->all();
        $pdf = $this->pdfService->generatePaklaringPdf($employee, $extraData);
        return $pdf->download("Paklaring_{$employee->employeeId}.pdf");
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
                'Recruitment ID', 'Nama Lengkap', 'NIK', 'Email', 'No Telepon', 'Kota',
                'Posisi', 'Pendidikan', 'Pengalaman', 'Ekspektasi Gaji', 'Status', 'Tanggal Daftar'
            ]);

            foreach ($candidates as $c) {
                fputcsv($handle, [
                    $c->recruitmentId, $c->fullName, "'" . $c->nik, $c->email,
                    "'" . $c->phone, $c->city, $c->positionApplied, $c->education,
                    $c->workExperience, $c->expectedSalary, $c->status, $c->createdDate
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
                'Employee ID', 'Nama Lengkap', 'NIK', 'Email', 'No Telepon',
                'Departemen', 'Posisi', 'Status Employee', 'Tanggal Masuk'
            ]);

            foreach ($employees as $e) {
                fputcsv($handle, [
                    $e->employeeId, $e->fullName, "'" . $e->nikNpwp, $e->personalEmail,
                    "'" . $e->mobilePhone, $e->department, $e->jobPosition, $e->statusEmployee, $e->joinDate
                ]);
            }
            fclose($handle);
        }, 200, $headers);
    }
}
