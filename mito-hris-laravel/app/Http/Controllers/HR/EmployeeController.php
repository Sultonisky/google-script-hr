<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Repositories\Contracts\EmployeeRepositoryInterface;
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

    public function __construct(
        EmployeeRepositoryInterface $employeeRepo,
        EmployeeService $employeeService
    ) {
        $this->employeeRepo = $employeeRepo;
        $this->employeeService = $employeeService;
    }

    public function index(Request $request): View
    {
        // Single read dari Google Sheets — $all digunakan untuk stats, departments, dan modal search
        $all = $this->employeeRepo->getAll();

        // Sort parameter
        $sort   = $request->query('sort', 'newest');
        $search = $request->query('search');
        $dept   = $request->query('department');
        $status = $request->query('status');

        // Apply filters
        $filtered = $all;

        if (!empty($status)) {
            $statusLower = strtolower(trim($status));
            $filtered = $filtered->filter(function ($e) use ($statusLower) {
                return strtolower(trim($e->statusEmployee ?? '')) === $statusLower;
            });
        }

        if (!empty($dept)) {
            $deptLower = strtolower(trim($dept));
            $filtered = $filtered->filter(function ($e) use ($deptLower) {
                return strtolower(trim($e->department ?? '')) === $deptLower;
            });
        }

        if (!empty($search)) {
            $searchLower = strtolower(trim($search));
            $filtered = $filtered->filter(function ($e) use ($searchLower) {
                return str_contains(strtolower($e->fullName ?? ''), $searchLower)
                    || str_contains(strtolower($e->employeeId ?? ''), $searchLower)
                    || str_contains(strtolower($e->personalEmail ?? ''), $searchLower)
                    || str_contains(strtolower($e->workingEmail ?? ''), $searchLower)
                    || str_contains(strtolower($e->jobPosition ?? ''), $searchLower)
                    || str_contains(strtolower($e->jobPositionLocation ?? ''), $searchLower)
                    || str_contains(strtolower($e->department ?? ''), $searchLower)
                    || str_contains(strtolower($e->nikNpwp ?? ''), $searchLower);
            });
        }

        // Sort — 1:1 GAS empApplyFilters sort options
        switch ($sort) {
            case 'oldest':
                $filtered = $filtered->sortBy(fn($e) => $e->joinDate ?? '');
                break;
            case 'name_asc':
                $filtered = $filtered->sortBy(fn($e) => $e->fullName ?? '');
                break;
            case 'name_desc':
                $filtered = $filtered->sortByDesc(fn($e) => $e->fullName ?? '');
                break;
            default: // newest
                $filtered = $filtered->sortByDesc(fn($e) => $e->joinDate ?? '');
        }
        $filtered = $filtered->values();

        $stats = [
            'total'     => $all->count(),
            'permanent' => $all->filter(fn($e) => strtolower(trim($e->statusEmployee ?? '')) === 'permanent')->count(),
            'contract'  => $all->filter(fn($e) => in_array(strtolower(trim($e->statusEmployee ?? '')), ['contract', 'pkwt']))->count(),
            'probation' => $all->filter(fn($e) => strtolower(trim($e->statusEmployee ?? '')) === 'probation')->count(),
            'outsource' => $all->filter(function ($e) {
                $s = strtolower(trim($e->statusEmployee ?? ''));
                return !empty($e->outsourceVendor) ||
                    $e->createdBy === 'System (Outsource Form)' ||
                    $s === 'outsource';
            })->count(),
        ];

        $departments = $all->pluck('department')->filter()->unique()->sort()->values();

        // Paginate — 1:1 GAS EMP_PAGE_SIZE = 20, Laravel uses 20/page
        $page    = (int) $request->query('page', 1);
        $perPage = 20;
        $items   = $filtered->forPage($page, $perPage)->values();
        $paginator = new \Illuminate\Pagination\LengthAwarePaginator(
            $items,
            $filtered->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        $employees = $paginator;

        // $all is passed to view so modals can serialize window.__allEmployees
        // Only active employees are included in modal search (see modal blades)
        return view('hr.employees.index', compact('employees', 'stats', 'departments', 'all'));
    }

    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'employees' => 'required|array',
            'employees.*.fullName' => 'required|string',
        ]);

        $result = $this->employeeService->importEmployees(
            $request->input('employees'),
            Auth::user()?->name ?? 'HR Administrator'
        );

        if ($result['success']) {
            return redirect()->back()->with('success', $result['message']);
        } else {
            return redirect()->back()->with('error', $result['message']);
        }
    }

    /**
     * Process Employee Rotation / Mutasi / Promosi / Demosi.
     */
    public function rotate(Request $request, string $id): RedirectResponse|JsonResponse
    {
        $request->validate([
            'rotation_type'    => 'required|string',
            'new_job_position' => 'required|string',
            'new_department'   => 'nullable|string',   // optional: Mutasi tidak selalu ganti department
            'effective_date'   => 'required|date',
        ]);

        // Capture old_job_position & old_department dari form (snapshot SEBELUM update employee)
        // Jika tidak dikirim dari form, processRotation akan mengambil langsung dari Employee sheet
        // (lihat EmployeeService::processRotation — $oldPosition = $employee->jobPosition)
        $result = $this->employeeService->processRotation($id, $request->all(), Auth::user()?->name ?? 'HR Team');

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
                    return !in_array($s, ['resigned','terminated','retired','deceased','inactive','contract finished','']);
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
     */
    public function offboard(Request $request, string $id): RedirectResponse|JsonResponse
    {
        $request->validate([
            'offboarding_type' => 'required|string',
            'reason'           => 'required|string',
            'last_working_date' => 'required|date',
        ]);

        $this->employeeService->processOffboarding($id, $request->all(), Auth::user()?->name ?? 'HR Team');

        if ($request->wantsJson() || $request->ajax() || $request->isXmlHttpRequest()) {
            return response()->json(['success' => true, 'message' => "Offboarding karyawan {$id} berhasil diproses."]);
        }

        return redirect()->back()->with('success', "Offboarding karyawan {$id} berhasil diproses.");
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

        // Build PDF download URLs for Surat BPJS (auto-generate after success)
        $pdfUrls = [];
        if ($result['success']) {
            $employee = $this->employeeRepo->findById($id);
            if ($employee) {
                $lwd = $request->input('last_working_date');
                $extraQ = http_build_query([
                    'effective_date'    => $lwd,
                    'last_working_date' => $lwd,
                    'notes'             => $request->input('notes', ''),
                    'approved_by'       => $request->input('approved_by', ''),
                ]);
                // Paklaring URL
                $pdfUrls['paklaring']  = route('hr.export.paklaring',  ['id' => $id]) . '?' . $extraQ;
                // Surat BPJS URL — only if checkbox checked (default: generate)
                if ($request->input('generate_bpjs', 'on') !== false) {
                    $pdfUrls['surat_bpjs'] = route('hr.export.surat-bpjs', ['id' => $id]) . '?' . $extraQ;
                }
            }
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

        return response()->json([
            'success' => true,
            'employee' => $employee,
        ]);
    }
}
