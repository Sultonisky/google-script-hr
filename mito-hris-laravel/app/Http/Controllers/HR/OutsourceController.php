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
        $allEmployees = $this->employeeRepo->getAll();
        $searchFilter = $request->query('search');

        $outsources = $allEmployees->filter(function ($e) {
            $vendor = strtolower(trim($e->outsourceVendor ?? ''));
            $createdBy = strtolower(trim($e->createdBy ?? ''));
            $status = strtolower(trim($e->statusEmployee ?? ''));
            return !empty($vendor) || $createdBy === 'system (outsource form)' || $status === 'outsource';
        });

        if ($searchFilter) {
            $search = strtolower($searchFilter);
            $outsources = $outsources->filter(fn($e) =>
                str_contains(strtolower($e->fullName ?? ''), $search) ||
                str_contains(strtolower($e->employeeId ?? ''), $search) ||
                str_contains(strtolower($e->outsourceVendor ?? ''), $search)
            );
        }

        $outsources = $outsources->values();

        $stats = [
            'permanent' => 0,
            'contract'  => $outsources->count(),
            'probation' => 0,
        ];

        return view('hr.outsource.index', compact('outsources', 'stats'));
    }
}
