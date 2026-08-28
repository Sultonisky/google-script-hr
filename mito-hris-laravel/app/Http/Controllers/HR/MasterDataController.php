<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Repositories\Contracts\CandidateRepositoryInterface;
use App\Repositories\Contracts\EmployeeRepositoryInterface;
use App\Repositories\Contracts\AuditLogRepositoryInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class MasterDataController extends Controller
{
    protected CandidateRepositoryInterface $candidateRepo;
    protected EmployeeRepositoryInterface $employeeRepo;
    protected AuditLogRepositoryInterface $auditRepo;

    public function __construct(
        CandidateRepositoryInterface $candidateRepo,
        EmployeeRepositoryInterface $employeeRepo,
        AuditLogRepositoryInterface $auditRepo
    ) {
        $this->candidateRepo = $candidateRepo;
        $this->employeeRepo = $employeeRepo;
        $this->auditRepo = $auditRepo;
    }

    public function index(Request $request): View
    {
        $allCandidates = $this->candidateRepo->getAll();
        $allEmployees = $this->employeeRepo->getAll();

        $customPositions = Cache::get('master_custom_positions', []);
        $customBranches = Cache::get('master_custom_branches', []);
        $customDepartments = Cache::get('master_custom_departments', []);

        $positions = $allCandidates->pluck('positionApplied')
            ->merge($allEmployees->pluck('jobPosition'))
            ->merge($customPositions)
            ->filter()->unique()->values();

        $branches = $allEmployees->pluck('branchName')
            ->merge($customBranches)
            ->filter()->unique()->values();

        $departments = $allEmployees->pluck('department')
            ->merge($customDepartments)
            ->filter()->unique()->values();

        $cities = $allCandidates->pluck('city')->filter()->unique()->values();

        $category = $request->query('cat', 'positions');
        $items = match($category) {
            'branches' => $branches,
            'departments' => $departments,
            default => $positions,
        };

        return view('hr.master-data.index', compact('positions', 'branches', 'departments', 'cities', 'items', 'category'));
    }

    /**
     * Store new master data item.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'category' => 'required|string',
            'name'     => 'required|string|min:2',
        ]);

        $cat = $request->input('category');
        $name = trim($request->input('name'));

        $cacheKey = "master_custom_{$cat}";
        $existing = Cache::get($cacheKey, []);
        if (!in_array($name, $existing)) {
            $existing[] = $name;
            Cache::forever($cacheKey, $existing);
            $this->auditRepo->log('MasterData', $cat, 'created', 'name', null, $name, session('hr_user.email', 'HR Administrator'), 'Dashboard');
        }

        return redirect()->route('hr.master-data.index', ['cat' => $cat])
            ->with('success', "Item master data '{$name}' berhasil ditambahkan.");
    }
}
