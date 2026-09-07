<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Repositories\Contracts\EmployeeRepositoryInterface;
use App\Services\EmployeeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OutsourceController extends Controller
{
    protected EmployeeRepositoryInterface $employeeRepo;
    protected EmployeeService $employeeService;

    public function __construct(
        EmployeeRepositoryInterface $employeeRepo,
        EmployeeService $employeeService
    ) {
        $this->employeeRepo    = $employeeRepo;
        $this->employeeService = $employeeService;
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

        return view('hr.outsource.index', compact(
            'outsources',
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
}
