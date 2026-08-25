<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Repositories\Contracts\EmployeeRepositoryInterface;
use App\Services\EmployeeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
        $employees = $this->employeeRepo->getAll([
            'department' => $request->query('department'),
            'status'     => $request->query('status'),
            'search'     => $request->query('search'),
        ]);

        $all = $this->employeeRepo->getAll();
        $stats = [
            'total'     => $all->count(),
            'pkwtt'     => $all->filter(fn($e) => strtoupper($e->statusEmployee ?? '') === 'PKWTT')->count(),
            'pkwt'      => $all->filter(fn($e) => strtoupper($e->statusEmployee ?? '') === 'PKWT')->count(),
            'probation' => $all->filter(fn($e) => strtolower($e->statusEmployee ?? '') === 'probation')->count(),
            'off_contract' => $all->filter(fn($e) => strtolower($e->statusEmployee ?? '') === 'contract finished')->count(),
        ];

        $contractEmployees = $all->filter(fn($e) => in_array(strtoupper($e->statusEmployee ?? ''), ['PKWT', 'CONTRACT']));
        $departments = $all->pluck('department')->filter()->unique()->values();

        return view('hr.employees.index', compact('employees', 'stats', 'departments', 'all', 'contractEmployees'));
    }

    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'employees' => 'required|array',
            'employees.*.fullName' => 'required|string',
        ]);

        $result = $this->employeeService->importEmployees(
            $request->input('employees'),
            auth()->user()?->name ?? 'HR Administrator'
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
            'rotation_type'    => 'required|string|in:Promosi,Demosi,Mutasi',
            'new_job_position' => 'required|string',
            'new_department'   => 'required|string',
            'effective_date'   => 'required|date',
        ]);

        // Ambil semua data request kecuali sk_number — Nomor SK wajib di-generate server-side
        $data = $request->except(['sk_number']);

        $result = $this->employeeService->processRotation($id, $data, auth()->user()?->name ?? 'HR Team');

        $pdfQuery = http_build_query([
            'rotation_type'     => $result['rotationType'],
            'sk_number'         => $result['skNumber'],
            'new_job_position'  => $result['newPosition'],
            'new_department'    => $result['newDepartment'],
            'new_branch_name'   => $result['newBranch'],
            'effective_date'    => $result['effectiveDate'],
            'notes'             => $result['notes'],
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
     * Process Employee Offboarding.
     */
    public function offboard(Request $request, string $id): RedirectResponse
    {
        $request->validate([
            'offboarding_type' => 'required|string',
            'reason'           => 'required|string',
            'effective_date'   => 'required|date',
        ]);

        $this->employeeService->processOffboarding($id, $request->all(), auth()->user()?->name ?? 'HR Team');

        return redirect()->back()->with('success', "Offboarding karyawan {$id} berhasil diproses.");
    }

    /**
     * Process Off Contract for Contract Employee.
     */
    public function offContract(Request $request, string $id): RedirectResponse
    {
        $request->validate([
            'last_working_date' => 'required|date',
        ]);

        $this->employeeService->processOffContract($id, $request->all(), auth()->user()?->name ?? 'HR Team');

        return redirect()->back()->with('success', "Proses Off Contract karyawan {$id} berhasil diselesaikan.");
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
