<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Repositories\Contracts\AuditLogRepositoryInterface;
use App\Repositories\Contracts\EmployeeRepositoryInterface;
use App\Services\PdfGeneratorService;
use App\Support\ExitClearanceForm;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ExitClearanceController extends Controller
{
    public function __construct(
        protected EmployeeRepositoryInterface $employeeRepo,
        protected PdfGeneratorService $pdfService,
        protected AuditLogRepositoryInterface $auditRepo,
    ) {}

    public function show(): View
    {
        return view('public.employee.exit-clearance', [
            'employees' => $this->employeeRepo->getAll(),
        ]);
    }

    public function pdf(Request $request, string $id)
    {
        // Sanitize route parameter — hanya alfanumerik, dash, underscore yang valid
        $id = preg_replace('/[^a-zA-Z0-9_-]/', '', $id);
        if ($id === '') {
            abort(404, 'Data karyawan tidak ditemukan.');
        }

        $employee = $this->employeeRepo->findById($id);
        if (!$employee) {
            abort(404, 'Data karyawan tidak ditemukan.');
        }

        $extraData = ExitClearanceForm::payload($request);
        $pdf = $this->pdfService->generateExitClearancePdf($employee, $extraData);
        $this->auditRepo->log(
            'Employee',
            $employee->employeeId,
            'generated',
            'exit_clearance',
            null,
            'PDF',
            'Publik',
            'Export'
        );

        $name = trim((string) preg_replace('/[^a-zA-Z0-9]+/', '_', trim((string) ($employee->fullName ?? 'Karyawan'))), '_');
        $employeeId = trim((string) preg_replace('/[^a-zA-Z0-9_-]+/', '_', trim((string) ($employee->employeeId ?? ''))), '_');
        if ($name === '') {
            $name = 'Karyawan';
        }
        $stem = $employeeId !== '' ? $name.'_'.$employeeId : $name;

        return $pdf->download('Exit_Clearance_'.$stem.'.pdf');
    }
}
