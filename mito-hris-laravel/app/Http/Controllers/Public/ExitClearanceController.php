<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Repositories\Contracts\AuditLogRepositoryInterface;
use App\Services\PdfGeneratorService;
use App\Support\ExitClearanceForm;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ExitClearanceController extends Controller
{
    public function __construct(
        protected PdfGeneratorService $pdfService,
        protected AuditLogRepositoryInterface $auditRepo,
    ) {}

    public function show(): View
    {
        return view('public.employee.exit-clearance');
    }

    public function pdf(Request $request)
    {
        $extraData = ExitClearanceForm::payload($request);

        $pdf = $this->pdfService->generateExitClearancePdf($extraData);

        $this->auditRepo->log(
            'Employee',
            '-',
            'generated',
            'exit_clearance',
            null,
            'PDF',
            'Publik',
            'Export'
        );
        $name     = trim((string) preg_replace('/[^a-zA-Z0-9]+/', '_', $extraData['full_name'] ?: 'Karyawan'), '_');
        $name     = trim((string) preg_replace('/[^a-zA-Z0-9]+/', '_', $extraData['full_name'] ?: 'Karyawan'), '_');
        $stem     = $name ?: 'Karyawan';

        return $pdf->download('Exit_Clearance_' . $stem . '.pdf');
    }
}
