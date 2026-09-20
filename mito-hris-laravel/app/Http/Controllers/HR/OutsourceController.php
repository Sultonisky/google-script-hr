<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Repositories\Contracts\AuditLogRepositoryInterface;
use App\Repositories\Contracts\EmployeeRepositoryInterface;
use App\Services\EmployeeService;
use App\Services\OutsourceContractService;
use App\Services\PdfGeneratorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class OutsourceController extends Controller
{
    protected EmployeeRepositoryInterface $employeeRepo;
    protected EmployeeService $employeeService;
    protected OutsourceContractService $contractService;
    protected PdfGeneratorService $pdfService;
    protected AuditLogRepositoryInterface $auditRepo;

    public function __construct(
        EmployeeRepositoryInterface $employeeRepo,
        EmployeeService $employeeService,
        OutsourceContractService $contractService,
        PdfGeneratorService $pdfService,
        AuditLogRepositoryInterface $auditRepo
    ) {
        $this->employeeRepo    = $employeeRepo;
        $this->employeeService = $employeeService;
        $this->contractService = $contractService;
        $this->pdfService      = $pdfService;
        $this->auditRepo       = $auditRepo;
    }

    public function index(Request $request): View
    {
        $perPage     = max(1, (int) $request->query('per_page', 10));
        $currentPage = max(1, (int) $request->query('page', 1));

        // Single source of truth: Employee sheet, filtered to Outsource only
        $allEmployees = $this->employeeRepo->getAll();
        $allOutsources = $allEmployees->filter(
            fn($e) => strtolower(trim($e->statusEmployee ?? '')) === 'outsource'
        );

        // Stats — always from full Outsource dataset (not current page)
        $stats = [
            'total'   => $allOutsources->count(),
        ];

        // Apply search
        $filtered = $allOutsources;
        $searchFilter = $request->query('search');
        if ($searchFilter) {
            $search = strtolower(trim($searchFilter));
            $filtered = $filtered->filter(
                fn($e) =>
                str_contains(strtolower($e->fullName ?? ''), $search) ||
                    str_contains(strtolower($e->employeeId ?? ''), $search) ||
                    str_contains(strtolower($e->jobPosition ?? ''), $search) ||
                    str_contains(strtolower($e->outsourceVendor ?? ''), $search)
            );
        }

        // Sort
        $sortFilter = $request->query('sort', 'name_asc');
        $sortFilter = in_array($sortFilter, ['name_asc', 'name_desc'], true) ? $sortFilter : 'name_asc';

        $orderFilter = $request->query('order');
        $orderFilter = in_array($orderFilter, ['newest', 'oldest'], true) ? $orderFilter : '';

        if ($orderFilter === 'newest') {
            $filtered = $filtered->sortByDesc(fn($e) => strtotime((string) ($e->joinDate ?? $e->createdDate ?? '1970-01-01')) ?: 0);
        } elseif ($orderFilter === 'oldest') {
            $filtered = $filtered->sortBy(fn($e) => strtotime((string) ($e->joinDate ?? $e->createdDate ?? '1970-01-01')) ?: 0);
        } elseif ($sortFilter === 'name_asc') {
            $filtered = $filtered->sortBy(fn($e) => strtolower(trim($e->fullName ?? '')));
        } elseif ($sortFilter === 'name_desc') {
            $filtered = $filtered->sortByDesc(fn($e) => strtolower(trim($e->fullName ?? '')));
        }

        $filtered = $filtered->values();
        $total = $filtered->count();

        // Manual pagination
        $offset     = ($currentPage - 1) * $perPage;
        $outsources = $filtered->slice($offset, $perPage)->values();

        // Full outsource list for Proses Kontrak modal search (all pages)
        $allOutsourcesForSearch = $allOutsources->values();

        return view('hr.outsource.index', compact(
            'outsources',
            'allOutsourcesForSearch',
            'stats',
            'total',
            'currentPage',
            'perPage',
            'searchFilter',
            'sortFilter',
            'orderFilter'
        ));
    }

    /**
     * Tambah karyawan outsource baru secara manual dari dashboard HR.
     * Status Employee di-force ke 'Outsource' — tidak bisa diubah dari request.
     *
     * POST /hr/outsource
     * Requires: can:manage_employees
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'fullName'            => 'required|string|max:255',
            'outsourceVendor'     => 'required|string|max:255',
            'employeeId'          => ['nullable', 'string', 'max:12', 'regex:/^\d{1,12}$/'],
            'division'            => ['nullable', 'string', 'max:255', 'regex:/^[\p{L}]+(?:[ \-\/]+[\p{L}]+)*$/u'],
            'jobPosition'         => ['nullable', 'string', 'max:255', 'regex:/^[\p{L}]+(?:[ \-\/]+[\p{L}]+)*$/u'],
            'jobPositionLocation' => ['nullable', 'string', 'max:255', 'regex:/^[\p{L}]+(?:[ \-\/]+[\p{L}]+)*$/u'],
            'joinDate'            => 'nullable|date',
            'endDateContract'     => 'nullable|date',
            'personalEmail'       => 'nullable|email|max:255',
            'workingEmail'        => 'nullable|email|max:255',
            'birthDate'           => 'nullable|date',
            'nikNpwp'             => 'nullable|string|max:20',
            'npwp'                => 'nullable|string|max:20',
            'mobilePhone'         => 'nullable|string|max:20',
            'bankAccount'         => 'nullable|string|max:30',
            'bpjsKetenagakerjaan' => 'nullable|string|max:30',
            'bpjsKesehatan'       => 'nullable|string|max:30',
        ], [
            'employeeId.regex' => 'Employee ID hanya boleh angka (maksimal 12 digit).',
            'division.regex' => 'Divisi hanya boleh huruf, spasi, tanda hubung, dan garis miring.',
            'jobPosition.regex' => 'Jabatan hanya boleh huruf, spasi, tanda hubung, dan garis miring.',
            'jobPositionLocation.regex' => 'Jabatan (dengan lokasi) hanya boleh huruf, spasi, tanda hubung, dan garis miring.',
        ]);

        // Force statusEmployee = Outsource — tidak boleh dioverride dari frontend
        $data                  = $request->all();
        $data['statusEmployee'] = 'Outsource';

        $result = $this->employeeService->createEmployee(
            $data,
            $this->hrUserName()
        );

        return response()->json($result, $result['success'] ? 201 : 422);
    }

    /**
     * Allocate nomor kontrak + siapkan 2 PDF (kontrak + surat pernyataan).
     * Returns JSON with download URLs — frontend download keduanya.
     *
     * POST /hr/outsource/kontrak-pkwt-tad
     */
    public function generateKontrakPkwtTad(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'employee_id'   => 'required|string|max:64',
            'perusahaan'    => 'required|string|max:255',
            'beralamat_di'  => 'required|string|max:500',
            'mulai_tanggal' => 'required|date',
            'pendidikan'    => 'required|string|max:100',
            'contract_duration' => 'nullable|string|max:50',
        ]);

        $employee = $this->employeeRepo->findById($validated['employee_id']);
        if (!$employee) {
            return response()->json(['success' => false, 'message' => 'Data karyawan outsource tidak ditemukan.'], 404);
        }

        if (!$this->contractService->isOutsource($employee)) {
            return response()->json(['success' => false, 'message' => 'Karyawan yang dipilih bukan status Outsource.'], 422);
        }

        $alloc = $this->contractService->allocateContractNumber($employee);
        $now   = now()->timezone('Asia/Jakarta');

        $extraData = [
            'contract_number'   => $alloc['contract_number'],
            'perusahaan'        => $validated['perusahaan'],
            'beralamat_di'      => $validated['beralamat_di'],
            'mulai_tanggal'     => $validated['mulai_tanggal'],
            'pendidikan'        => $validated['pendidikan'],
            'join_date'         => $validated['mulai_tanggal'],
            'doc_date'          => $now->format('Y-m-d'),
            'contract_duration' => $validated['contract_duration'] ?? '12 Bulan',
            'position'          => $employee->jobPosition,
            'department'        => $employee->department,
            'division'          => $employee->division,
            'direct_superior'   => $employee->directSuperior,
        ];

        $token = (string) Str::uuid();
        Cache::put('osc_pkwt_tad_' . $token, [
            'employee_id' => $employee->employeeId,
            'extra_data'  => $extraData,
            'safe_name'   => preg_replace('/[^a-zA-Z0-9_-]+/', '_', $employee->fullName ?? 'Outsource'),
        ], now()->addMinutes(15));

        $this->auditRepo->log(
            'Outsource',
            $employee->employeeId,
            'generated',
            'contract_pkwt_tad',
            null,
            $alloc['contract_number'],
            $this->hrUserName(),
            'Export'
        );

        $baseQuery = ['token' => $token];

        return response()->json([
            'success'         => true,
            'contract_number' => $alloc['contract_number'],
            'message'         => 'Kontrak PKWT TAD siap diunduh.',
            'pdf_urls'        => [
                'kontrak'    => route('hr.outsource.kontrak-pkwt-tad.download', $baseQuery + ['type' => 'kontrak']),
                'pernyataan' => route('hr.outsource.kontrak-pkwt-tad.download', $baseQuery + ['type' => 'pernyataan']),
            ],
        ]);
    }

    /**
     * Download satu PDF (kontrak | pernyataan) via token dari generate.
     *
     * GET /hr/outsource/kontrak-pkwt-tad/download?token=...&type=kontrak|pernyataan
     */
    public function downloadKontrakPkwtTad(Request $request): Response
    {
        $token = trim((string) $request->query('token', ''));
        $type  = strtolower(trim((string) $request->query('type', 'kontrak')));

        if ($token === '' || !in_array($type, ['kontrak', 'pernyataan'], true)) {
            abort(404, 'Parameter download tidak valid.');
        }

        $payload = Cache::get('osc_pkwt_tad_' . $token);
        if (!$payload || empty($payload['employee_id'])) {
            abort(410, 'Link download kedaluwarsa. Generate ulang dari form.');
        }

        $employee = $this->employeeRepo->findById($payload['employee_id']);
        if (!$employee) {
            abort(404, 'Data karyawan tidak ditemukan.');
        }

        $extraData = $payload['extra_data'] ?? [];
        $safeName  = $payload['safe_name'] ?? 'Outsource';
        $empId     = $employee->employeeId;

        if ($type === 'pernyataan') {
            $pdf = $this->pdfService->generateSuratPernyataanOutsourcePdf($employee, $extraData);
            $filename = "Surat_Pernyataan_{$safeName}_{$empId}.pdf";
        } else {
            $pdf = $this->pdfService->generateKontrakPkwtTadPdf($employee, $extraData);
            $filename = "PKWT_TAD_{$safeName}_{$empId}.pdf";
        }

        return $pdf->download($filename);
    }
}
