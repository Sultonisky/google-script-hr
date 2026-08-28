<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Repositories\Contracts\EmployeeRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OutsourceController extends Controller
{
    protected EmployeeRepositoryInterface $employeeRepo;

    public function __construct(EmployeeRepositoryInterface $employeeRepo)
    {
        $this->employeeRepo = $employeeRepo;
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
        $sortFilter = 'name_asc';
        $filtered = $filtered->sortBy(fn($e) => strtolower(trim($e->fullName ?? '')));

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
            'sortFilter'
        ));
    }
}
