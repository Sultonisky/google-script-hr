<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Repositories\Contracts\EmployeeRepositoryInterface;
use App\Repositories\Contracts\AuditLogRepositoryInterface;
use App\Services\EmployeeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class EmployeeController extends Controller
{
    protected EmployeeRepositoryInterface $employeeRepo;
    protected EmployeeService $employeeService;
    protected AuditLogRepositoryInterface $auditRepo;

    public function __construct(
        EmployeeRepositoryInterface $employeeRepo,
        EmployeeService $employeeService,
        AuditLogRepositoryInterface $auditRepo
    ) {
        $this->employeeRepo = $employeeRepo;
        $this->employeeService = $employeeService;
        $this->auditRepo = $auditRepo;
    }

    public function index(Request $request): View
    {
        $perPage    = max(1, (int) $request->query('per_page', 10));
        $currentPage = max(1, (int) $request->query('page', 1));

        // Fetch ALL employees once — used for both stats and filtered/paginated table
        $all = $this->employeeRepo->getAll();

        // Stats — always computed from the full dataset, never from current page
        $stats = [
            'total'     => $all->count(),
            'permanent' => $all->filter(fn($e) => in_array(strtolower(trim($e->statusEmployee ?? '')), ['permanent', 'pkwtt']))->count(),
            'pkwtt'     => 0, // folded into 'permanent' above
            'contract'  => $all->filter(fn($e) => in_array(strtolower(trim($e->statusEmployee ?? '')), ['contract', 'pkwt']))->count(),
            'pkwt'      => 0, // folded into 'contract' above
            'probation' => $all->filter(fn($e) => strtolower(trim($e->statusEmployee ?? '')) === 'probation')->count(),
            'outsource' => $all->filter(fn($e) => strtolower(trim($e->statusEmployee ?? '')) === 'outsource')->count(),
            'off_contract' => $all->filter(fn($e) => strtolower(trim($e->statusEmployee ?? '')) === 'contract finished')->count(),
        ];

        // Apply search/filter to full dataset
        $filtered = $all;

        $departmentFilter = $request->query('department');
        if ($departmentFilter) {
            $dept = strtolower(trim($departmentFilter));
            $filtered = $filtered->filter(fn($e) => strtolower(trim($e->department ?? '')) === $dept);
        }

        $statusFilter = $request->query('status');
        if ($statusFilter) {
            $st = strtolower(trim($statusFilter));
            // Normalize: 'permanent' matches both 'permanent' and 'pkwtt'
            // 'contract' matches 'contract' and 'pkwt'
            $filtered = $filtered->filter(function ($e) use ($st) {
                $es = strtolower(trim($e->statusEmployee ?? ''));
                return match ($st) {
                    'permanent' => in_array($es, ['permanent', 'pkwtt']),
                    'contract'  => in_array($es, ['contract', 'pkwt']),
                    default     => $es === $st,
                };
            });
        }

        $searchFilter = $request->query('search');
        if ($searchFilter) {
            $search = strtolower(trim($searchFilter));
            $filtered = $filtered->filter(
                fn($e) =>
                str_contains(strtolower($e->fullName ?? ''), $search)
                    || str_contains(strtolower($e->employeeId ?? ''), $search)
                    || str_contains(strtolower($e->personalEmail ?? ''), $search)
                    || str_contains(strtolower($e->workingEmail ?? ''), $search)
                    || str_contains(strtolower($e->jobPosition ?? ''), $search)
                    || str_contains(strtolower($e->nikNpwp ?? ''), $search)
            );
        }

        // Sort logic: check date_sort first (Terbaru/Terlama dropdown), then sort (A-Z/Z-A dropdown)
        $dateSortParam = $request->query('date_sort');
        $nameSortParam = $request->query('sort');
        
        // Priority: date_sort > sort > default (join_date_asc)
        if ($dateSortParam) {
            $sortFilter = $dateSortParam;
        } elseif ($nameSortParam) {
            $sortFilter = $nameSortParam;
        } else {
            $sortFilter = 'join_date_asc'; // Default ke terlama
        }
        
        // Apply sorting based on sort filter
        $filtered = match($sortFilter) {
            'name_asc' => $filtered->sortBy(fn($e) => strtolower(trim($e->fullName ?? ''))),
            'name_desc' => $filtered->sortByDesc(fn($e) => strtolower(trim($e->fullName ?? ''))),
            'emp_id_asc' => $filtered->sortBy(fn($e) => strtolower(trim($e->employeeId ?? ''))),
            'emp_id_desc' => $filtered->sortByDesc(fn($e) => strtolower(trim($e->employeeId ?? ''))),
            'nik_asc' => $filtered->sortBy(fn($e) => strtolower(trim($e->nikNpwp ?? ''))),
            'nik_desc' => $filtered->sortByDesc(fn($e) => strtolower(trim($e->nikNpwp ?? ''))),
            'dept_asc' => $filtered->sortBy(fn($e) => strtolower(trim($e->department ?? ''))),
            'dept_desc' => $filtered->sortByDesc(fn($e) => strtolower(trim($e->department ?? ''))),
            'division_asc' => $filtered->sortBy(fn($e) => strtolower(trim($e->division ?? ''))),
            'division_desc' => $filtered->sortByDesc(fn($e) => strtolower(trim($e->division ?? ''))),
            'position_asc' => $filtered->sortBy(fn($e) => strtolower(trim($e->jobPosition ?? ''))),
            'position_desc' => $filtered->sortByDesc(fn($e) => strtolower(trim($e->jobPosition ?? ''))),
            'join_date_asc' => $filtered->sortBy(fn($e) => $e->joinDate ?? '9999-99-99'),
            'join_date_desc' => $filtered->sortByDesc(fn($e) => $e->joinDate ?? '0000-00-00'),
            // DEFAULT: Sort by join_date_asc (oldest to newest)
            default => $filtered->sortBy(fn($e) => $e->joinDate ?? '9999-99-99'),
        };

        $filtered = $filtered->values();
        $total = $filtered->count();

        // Manual pagination
        $offset    = ($currentPage - 1) * $perPage;
        $employees = $filtered->slice($offset, $perPage)->values();

        $contractEmployees = $all->filter(fn($e) => in_array(strtoupper(trim($e->statusEmployee ?? '')), ['PKWT', 'CONTRACT']));
        $departments = $all->pluck('department')->filter()->unique()->sort()->values();

        return view('hr.employees.index', compact(
            'employees',
            'stats',
            'departments',
            'all',
            'contractEmployees',
            'total',
            'currentPage',
            'perPage',
            'searchFilter',
            'statusFilter',
            'departmentFilter',
            'sortFilter'
        ));
    }

    /**
     * Preview import — validate rows and check duplicates WITHOUT writing to Google Sheets.
     * Mirrors GAS importEmployees() validation logic but skips the batch write.
     * Called by Fetch API from the import modal Step 2 button.
     *
     * POST /hr/employees/import/preview
     * Body: { employees: [...] }  (JSON)
     */
    public function importPreview(Request $request): JsonResponse
    {
        $request->validate([
            'employees'   => 'required|array|min:1|max:500',
        ]);

        $rows   = $request->input('employees', []);
        $result = $this->employeeService->previewImport($rows);

        return response()->json($result);
    }

    /**
     * Download blank import template (CSV).
     * Header columns match GAS empImportTemplateHeaders() exactly.
     *
     * GET /hr/employees/import/template
     */
    public function importTemplate(): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        // Headers 1:1 with GAS empImportTemplateHeaders()
        $headers = [
            'Full Name',
            'NIK',
            'NPWP',
            'Tempat Lahir',
            'Tanggal Lahir',
            'Jenis Kelamin',
            'Agama',
            'Status Pernikahan',
            'Golongan Darah',
            'Status PTKP',
            'Alamat KTP',
            'Alamat Domisili',
            'No HP',
            'Email Pribadi',
            'Email Kantor',
            'Nama Bank',
            'Nomor Rekening',
            'Atas Nama Rekening',
            'BPJS Ketenagakerjaan',
            'BPJS Kesehatan',
            'Branch Name',
            'Division',
            'Department',
            'Job Position',
            'Job Position (No Location)',
            'Job Level',
            'Grade',
            'Area Kerja',
            'Lokasi Kerja',
            'Cost Center',
            'Direct Superior',
            'Indirect Superior',
            'Status Employee',
            'Join Date',
            'End Date Contract',
            'Outsource Vendor',
            'Job Position Former',
            'Type of Rotation',
            'Tanggal Mutasi',
            'Nomor SK',
            'Resign Date',
            'Catatan HR',
        ];

        // Example rows 1:1 with GAS empImportTemplateExamples()
        $examples = [
            [
                'Budi Santoso',
                "'3201234567890001",
                "'091234567890000",
                'Jakarta',
                '1995-03-15',
                'Laki-laki',
                'Islam',
                'Menikah',
                'O',
                'K/1',
                'Jl. Melati No. 10 Jakarta Barat',
                'Jl. Melati No. 10 Jakarta Barat',
                "'081234567890",
                'budi.santoso@gmail.com',
                'budi.santoso@mitogroup.co.id',
                'BCA',
                "'1234567890",
                'Budi Santoso',
                "'00012345678",
                "'00098765432",
                'Jakarta Head Office',
                'Operations',
                'Human Resources',
                'HR Operations Staff - Jakarta',
                'HR Operations Staff',
                'Staff',
                'Grade 3',
                'Jakarta Barat',
                'Jakarta',
                'CC-HR-001',
                'Ahmad Fauzi',
                'Direktur HR',
                'Permanent',
                '2024-01-15',
                '',
                '',
                '',
                '',
                '',
                'SK/HRD/2024/001',
                '',
                'Karyawan teladan',
            ],
            [
                'Siti Rahmawati',
                "'3201987654320002",
                '',
                'Bandung',
                '1998-07-20',
                'Perempuan',
                'Islam',
                'Belum Menikah',
                'A',
                'TK/0',
                'Jl. Dago No. 45 Bandung',
                'Jl. Kebon Jeruk No. 12 Jakarta Barat',
                "'081987654321",
                'siti.rahma@gmail.com',
                'siti.rahma@mitogroup.co.id',
                'Mandiri',
                "'1300098765432",
                'Siti Rahmawati',
                '',
                '',
                'Jakarta Head Office',
                'Finance & Accounting',
                'Finance',
                'Staff Finance - Jakarta',
                'Staff Finance',
                'Staff',
                'Grade 3',
                'Jakarta Barat',
                'Jakarta',
                'CC-FIN-002',
                'Dewi Sartika',
                'Finance Manager',
                'Contract',
                '2024-06-01',
                '2025-05-31',
                '',
                '',
                '',
                '',
                '',
                '',
                'Kontrak periode 1 (12 bulan)',
            ],
        ];

        $filename = 'template_import_karyawan.csv';

        $callback = function () use ($headers, $examples) {
            $out = fopen('php://output', 'w');
            // UTF-8 BOM for Excel compatibility
            fputs($out, "\xEF\xBB\xBF");
            fputcsv($out, $headers);
            foreach ($examples as $row) {
                fputcsv($out, $row);
            }
            fclose($out);
        };

        return response()->streamDownload($callback, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * Execute import — validate rows and APPEND only NEW, VALID rows to Google Sheets.
     * Mirrors GAS importEmployees() exactly.
     * Frontend sends parsed rows as JSON array.
     *
     * POST /hr/employees/import
     * Body: { employees: [...] }  (JSON)
     */
    public function import(Request $request): JsonResponse
    {
        $request->validate([
            'employees'   => 'required|array|min:1|max:500',
        ]);

        $result = $this->employeeService->importEmployees(
            $request->input('employees', []),
            Auth::user()?->name ?? 'HR Administrator'
        );

        // Always return JSON — modal uses Fetch API
        $httpStatus = $result['success'] ? 200 : 422;
        return response()->json($result, $httpStatus);
    }

    /**
     * Process Employee Rotation / Mutasi / Promosi / Demosi.
     */
    public function rotate(Request $request, string $id): RedirectResponse|JsonResponse
    {
        $request->validate([
            'rotation_type'    => 'required|string|in:Promosi,Demosi,Mutasi',
            'new_job_position' => 'required|string',
            'new_department'   => 'nullable|string',   // optional: Mutasi tidak selalu ganti department
            'effective_date'   => 'required|date',
        ]);

        // Ambil semua data request kecuali sk_number — Nomor SK wajib di-generate server-side
        $data = $request->except(['sk_number']);

        $result = $this->employeeService->processRotation($id, $data, Auth::user()?->name ?? 'HR Team');

        $pdfQuery = http_build_query([
            'rotation_type'     => $result['rotationType'],
            'sk_number'         => $result['skNumber'],
            'new_job_position'  => $result['newPosition'],
            'new_department'    => $result['newDepartment'],
            'new_branch_name'   => $result['newBranch'],
            'effective_date'    => $result['effectiveDate'],
            'notes'             => $result['notes'],
            // old data snapshot — diambil dari result (bukan dari employee sheet yang sudah ter-update)
            'old_job_position'  => $result['oldPosition'],
            'old_department'    => $result['oldDepartment'],
            'old_branch_name'   => $result['oldBranch'],
        ]);
        $downloadUrl = route('hr.export.sk-rotation', ['id' => $id]) . '?' . $pdfQuery;
        $result['download_url'] = $downloadUrl;
        $result['download_route'] = route('hr.export.sk-rotation', ['id' => $id]);

        if ($request->wantsJson() || $request->ajax() || $request->isXmlHttpRequest() || ($request->header('X-Requested-With') === 'XMLHttpRequest')) {
            if (!$result['success']) {
                return response()->json($result, 422);
            }
            return response()->json($result);
        }

        return redirect()
            ->back()
            ->with($result['success'] ? 'success' : 'error', $result['message'])
            ->with('auto_download_sk_rotation', $id)
            ->with('auto_download_sk_rotation_query', $pdfQuery);
    }

    /**
     * AJAX Employee Search — digunakan oleh modal search (Rotasi, Off Contract, Offboarding).
     * GET /hr/employees/search?q=keyword&status=active&limit=8
     *
     * Ini adalah server-side alternative untuk window.__allEmployees client-side search.
     * Keduanya menggunakan source yang sama (Google Sheets Employee).
     */
    public function search(Request $request): JsonResponse
    {
        $q      = trim($request->query('q', ''));
        $status = trim($request->query('status', ''));
        $limit  = min((int) $request->query('limit', 8), 50);

        if (strlen($q) < 1) {
            return response()->json(['data' => [], 'total' => 0]);
        }

        $all = $this->employeeRepo->getAll();

        // Status filter
        if (!empty($status)) {
            $statusLower = strtolower($status);
            $all = $all->filter(function ($e) use ($statusLower) {
                $s = strtolower(trim($e->statusEmployee ?? ''));
                // Support alias: 'contract' matches 'contract' and 'pkwt'
                if ($statusLower === 'contract') {
                    return $s === 'contract' || $s === 'pkwt' || str_contains($s, 'contract');
                }
                if ($statusLower === 'active') {
                    return !in_array($s, ['resigned', 'terminated', 'retired', 'deceased', 'inactive', 'contract finished', '']);
                }
                return $s === $statusLower;
            });
        }

        // Keyword search — case-insensitive, partial match (1:1 GAS behavior)
        $qLower = strtolower($q);
        $results = $all->filter(function ($e) use ($qLower) {
            return str_contains(strtolower($e->fullName ?? ''), $qLower)
                || str_contains(strtolower($e->employeeId ?? ''), $qLower)
                || str_contains(strtolower($e->jobPosition ?? ''), $qLower)
                || str_contains(strtolower($e->department ?? ''), $qLower);
        })->take($limit);

        $data = $results->map(fn($e) => [
            'employeeId'          => $e->employeeId,
            'fullName'            => $e->fullName,
            'statusEmployee'      => $e->statusEmployee,
            'jobPosition'         => $e->jobPosition,
            'jobPositionLocation' => $e->jobPositionLocation,
            'department'          => $e->department,
            'branchName'          => $e->branchName,
            'joinDate'            => $e->joinDate,
            'endDateContract'     => $e->endDateContract,
            'bpjsKetenagakerjaan' => $e->bpjsKetenagakerjaan,
            'bpjsKesehatan'       => $e->bpjsKesehatan,
        ])->values();

        return response()->json(['data' => $data, 'total' => $data->count()]);
    }

    /**
     * Process Employee Offboarding.
     * Supports multipart/form-data with optional file attachments.
     * Returns pdf_urls for auto-download on the frontend (1:1 pattern with offContract).
     */
    public function offboard(Request $request, string $id): RedirectResponse|JsonResponse
    {
        $request->validate([
            'offboarding_type'  => 'required|string|in:Resignation,Termination,Retirement,Death',
            'reason'            => 'required|string|max:1000',
            'last_working_date' => 'required|date',
            // attachments: one file per doc type, keyed as attachment_{docType}
            // Only Death is required; others are optional
        ]);

        $offboardingType = $request->input('offboarding_type');

        // Backend attachment validation — Death requires Surat Kematian (1:1 GAS _hasRequiredDeathDocument_)
        if ($offboardingType === 'Death') {
            $hasDeathDoc = false;
            foreach ($request->allFiles() as $key => $file) {
                if (str_starts_with($key, 'attachment_')) {
                    $docType = str_replace('attachment_', '', $key);
                    if (strtolower($docType) === 'surat kematian' || $docType === 'Surat_Kematian') {
                        $hasDeathDoc = true;
                        break;
                    }
                }
            }
            // Also check as generic key 'attachment_death_cert'
            if (!$hasDeathDoc && $request->hasFile('attachment_death_cert')) {
                $hasDeathDoc = true;
            }
            if (!$hasDeathDoc) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tipe Meninggal Dunia (Death) wajib melampirkan Surat Kematian.',
                ], 422);
            }
        }

        // Validate uploaded files: max 5MB, allowed MIME types (1:1 GAS _OFFB_DOC_MAX_BYTES / _OFFB_DOC_ACCEPT)
        $allowedMimes = [
            'application/pdf',
            'image/jpeg',
            'image/png',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
        ];
        foreach ($request->allFiles() as $key => $file) {
            if (!str_starts_with($key, 'attachment_')) {
                continue;
            }
            if ($file->getSize() > 5 * 1024 * 1024) {
                return response()->json([
                    'success' => false,
                    'message' => "File \"{$file->getClientOriginalName()}\" melebihi batas 5 MB.",
                ], 422);
            }
            if (!in_array($file->getMimeType(), $allowedMimes, true)) {
                return response()->json([
                    'success' => false,
                    'message' => "Tipe file \"{$file->getClientOriginalName()}\" tidak diizinkan. Gunakan PDF, JPG, PNG, DOC, atau DOCX.",
                ], 422);
            }
        }

        // Collect attachments keyed by doc type label
        $attachments = [];
        foreach ($request->allFiles() as $key => $file) {
            if (!str_starts_with($key, 'attachment_')) {
                continue;
            }
            // Convert key back to doc type label: attachment_Surat_Kematian → Surat Kematian
            $docType = str_replace(['attachment_', '_'], ['', ' '], $key);
            $attachments[trim($docType)] = $file;
        }

        // Merge attachments into data array under a special key
        $data               = $request->all();
        $data['_attachments'] = $attachments;

        try {
            $result = $this->employeeService->processOffboarding(
                $id,
                $data,
                Auth::user()?->name ?? 'HR Team'
            );
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error("EmployeeController::offboard error for {$id}: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat memproses offboarding: ' . $e->getMessage(),
            ], 500);
        }

        if (!$result['success']) {
            return response()->json($result, 422);
        }

        // Build staggered PDF download URLs (same pattern as offContract)
        if ($request->wantsJson() || $request->ajax() || $request->isXmlHttpRequest()) {
            return response()->json($result);
        }

        return redirect()->back()->with('success', $result['message']);
    }

    /**
     * Process Off Contract for Contract Employee.
     */
    public function offContract(Request $request, string $id): RedirectResponse|JsonResponse
    {
        $request->validate([
            'last_working_date' => 'required|date',
        ]);

        $result = $this->employeeService->processOffContract($id, $request->all(), Auth::user()?->name ?? 'HR Team');

        // Off Contract hanya menghasilkan satu dokumen: Paklaring.
        $pdfUrls = [];
        if ($result['success']) {
            $lwd = $request->input('last_working_date');
            $extraQ = http_build_query([
                'effective_date'    => $lwd,
                'last_working_date' => $lwd,
                'notes'             => $request->input('notes', ''),
                'approved_by'       => $request->input('approved_by', ''),
            ]);
            // The service has already confirmed the employee update. Do not
            // perform a second Sheets lookup here: eventual consistency can
            // otherwise make a successful update return no PDF URLs.
            $pdfUrls['paklaring'] = route('hr.export.paklaring', ['id' => $id]) . '?' . $extraQ;
        }

        $result['pdf_urls'] = $pdfUrls;

        if ($request->wantsJson() || $request->ajax() || $request->isXmlHttpRequest()) {
            return response()->json($result, $result['success'] ? 200 : 422);
        }

        return redirect()->back()->with('success', "Proses Off Contract karyawan {$id} berhasil diselesaikan.");
    }

    /**
     * Promote Contract employee to Probation (1:1 with GAS promoteEmployeeToProbation).
     */
    public function promoteToProbation(Request $request, string $id): JsonResponse|RedirectResponse
    {
        $request->validate([
            'probation_start'    => 'required|date',
            'probation_duration' => 'required|string',
            'probation_end'      => 'nullable|date',
            'contract_number'    => 'nullable|string',
            'notes'              => 'nullable|string',
        ]);

        $result = $this->employeeService->promoteToProbation(
            $id,
            $request->all(),
            Auth::user()?->name ?? 'HR Team'
        );

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json($result, $result['success'] ? 200 : 422);
        }

        return redirect()
            ->back()
            ->with($result['success'] ? 'success' : 'error', $result['message']);
    }

    /**
     * Update Employee data — 1:1 with GAS updateEmployee().
     * Dipanggil via Fetch API dari modal Edit Employee di drawer.
     * PUT /hr/employees/{id}
     */
    public function update(Request $request, string $id): JsonResponse
    {
        // Validasi minimal — nama wajib, field lain opsional (sesuai GAS updateEmployee)
        $request->validate([
            'fullName' => 'required|string|max:255',
        ]);

        $employee = $this->employeeRepo->findById($id);
        if (!$employee) {
            return response()->json([
                'success' => false,
                'message' => 'Karyawan tidak ditemukan.',
            ], 404);
        }

        // Map semua field yang dikirim dari modal Edit Employee
        // Sesuai GAS updateEmployee() keyMap — hanya update field yang dikirim
        $allowedFields = [
            // Identitas & Data Pribadi
            'fullName'             => 'Full Name',
            'nikNpwp'              => 'NIK - NPWP 16 digit',
            'nik'                  => 'NIK - NPWP 16 digit',
            'npwp'                 => 'NPWP',
            'birthPlace'           => 'Birth Place',
            'birthDate'            => 'Birth Date',
            'gender'               => 'Gender',
            'religion'             => 'Religion',
            'maritalStatus'        => 'Marital Status',
            'bloodType'            => 'Blood Type',
            'ptkpStatus'           => 'PTKP Status',
            'personalEmail'        => 'Personal Email',
            'workingEmail'         => 'Working Email',
            'mobilePhone'          => 'Mobile Phone',
            'citizenIdAddress'     => 'Citizen ID Address',
            'residentialAddress'   => 'Residential Address',
            // Bank & BPJS
            'bankName'             => 'Bank Name',
            'bankAccount'          => 'Bank Account',
            'bankAccountHolder'    => 'Bank Account Holder',
            'bpjsKetenagakerjaan'  => 'BPJS Ketenagakerjaan',
            'bpjsKesehatan'        => 'BPJS Kesehatan',
            // Organisasi & Pekerjaan
            'statusEmployee'       => 'Status Employee',
            'branchName'           => 'Branch Name',
            'division'             => 'Division',
            'department'           => 'Department',
            'jobPositionLocation'  => 'Job Position (Location)',
            'jobPosition'          => 'Job Position',
            'jobLevel'             => 'Job Level',
            'grade'                => 'Grade',
            'areaKerja'            => 'Area Kerja',
            'lokasiKerja'          => 'Lokasi Kerja',
            'costCenter'           => 'Cost Center',
            'directSuperior'       => 'Direct Superior',
            'indirectSuperior'     => 'Indirect Superior',
            'joinDate'             => 'Join Date',
            'outsourceVendor'      => 'Outsource Vendor',
            // Kontrak
            'endDateContract'      => 'End Date (Contract)',
            'contractStart'        => 'Start Date (Contract)',
            'contractDuration'     => 'Contract Duration',
            'contractNumber'       => 'Contract Number',
            // Karier & Mutasi
            'jobPositionFormer'    => 'Job Position (Former)',
            'typeOfRotation'       => 'Type of Rotation',
            'rotationDate'         => 'Tanggal Mutasi/Demosi/Promosi',
            'nomorSk'              => 'Nomor SK',
            'resignDate'           => 'Resign Date',
            // Catatan
            'hrNotes'              => 'HR Notes',
            'notes'                => 'HR Notes',
        ];

        $attributes = [];
        foreach ($allowedFields as $requestKey => $sheetHeader) {
            if ($request->has($requestKey)) {
                $attributes[$sheetHeader] = $request->input($requestKey) ?? '';
            }
        }

        if (empty($attributes)) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak ada field yang diubah.',
            ], 422);
        }

        $attributes['Updated At'] = now()->timezone('Asia/Jakarta')->format('Y-m-d H:i:s');

        $success = $this->employeeRepo->update($id, $attributes);

        if (!$success) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui data karyawan.',
            ], 500);
        }

        // Audit log perubahan status jika ada
        $newStatus = $request->input('statusEmployee');
        if ($newStatus && $newStatus !== $employee->statusEmployee) {
            // AuditRepo sudah dipanggil di dalam Repository via update,
            // tapi tambahkan log eksplisit untuk perubahan status via edit form
        }

        // Kembalikan data karyawan terbaru untuk keperluan drawer refresh
        $updated = $this->employeeRepo->findById($id);

        return response()->json([
            'success'  => true,
            'message'  => 'Data karyawan berhasil diperbarui.',
            'employee' => $updated,
        ]);
    }

    public function getJson(string $id): JsonResponse
    {
        $employee = $this->employeeRepo->findById($id);
        if (!$employee) {
            return response()->json(['success' => false, 'error' => 'Karyawan tidak ditemukan.'], 404);
        }

        try {
            $auditLogs = $this->auditRepo->getLogs((string) $employee->employeeId)
                ->filter(function ($log) {
                    return in_array(strtolower(trim($log['Entity Type'] ?? $log['entityType'] ?? '')), ['employee', 'outsource'], true);
                })
                ->take(20)
                ->values();
        } catch (\Throwable $e) {
            report($e);
            $auditLogs = collect();
        }

        return response()->json([
            'success' => true,
            'employee' => $employee,
            'auditLogs' => $auditLogs,
        ]);
    }
}
