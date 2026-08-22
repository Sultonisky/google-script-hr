<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Repositories\Contracts\EmployeeRepositoryInterface;
use App\Services\ProbationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProbationController extends Controller
{
    protected EmployeeRepositoryInterface $employeeRepo;
    protected ProbationService $probationService;

    public function __construct(
        EmployeeRepositoryInterface $employeeRepo,
        ProbationService $probationService
    ) {
        $this->employeeRepo = $employeeRepo;
        $this->probationService = $probationService;
    }

    public function index(Request $request): View
    {
        $probations = $this->employeeRepo->getAll([
            'status'     => 'Probation',
            'department' => $request->query('department'),
            'search'     => $request->query('search'),
        ]);

        $all = $this->employeeRepo->getAll();
        $departments = $all->pluck('department')->filter()->unique()->values();

        $stats = [
            'onboarding' => $probations->count(),
            'evaluated'  => 0,
            'passed'     => $all->filter(fn($e) => strtoupper($e->statusEmployee ?? '') === 'PKWTT')->count(),
            'extended'   => 0,
        ];

        return view('hr.probation.index', compact('probations', 'departments', 'stats'));
    }

    /**
     * Evaluate probation employee.
     */
    public function evaluate(Request $request, string $id): RedirectResponse
    {
        $request->validate([
            'decision' => 'required|string',
            'score'    => 'required|numeric',
        ]);

        $this->probationService->evaluateProbation($id, $request->all(), auth()->user()?->name ?? 'HR Team');

        return redirect()->back()->with('success', "Evaluasi probation karyawan {$id} berhasil disimpan.");
    }
}
