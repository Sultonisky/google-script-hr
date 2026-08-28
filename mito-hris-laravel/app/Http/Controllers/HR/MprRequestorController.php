<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Repositories\Contracts\MprRequestorRepositoryInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MprRequestorController extends Controller
{
    public function __construct(protected MprRequestorRepositoryInterface $requestorRepo) {}

    public function index(Request $request): View
    {
        $allRequestors = $this->requestorRepo->getAll();
        $perPage = max(1, min(100, (int) $request->query('per_page', 10)));
        $total = count($allRequestors);
        $lastPage = max(1, (int) ceil($total / $perPage));
        $currentPage = min(max(1, (int) $request->query('page', 1)), $lastPage);
        $requestors = array_values(array_slice($allRequestors, ($currentPage - 1) * $perPage, $perPage));
        $stats = [
            'total' => $total,
            'active' => count(array_filter($allRequestors, fn (array $requestor): bool => strtolower(trim($requestor['Status'] ?? '')) === 'active')),
            'inactive' => count(array_filter($allRequestors, fn (array $requestor): bool => strtolower(trim($requestor['Status'] ?? '')) !== 'active')),
            'entities' => count(array_filter($allRequestors, fn (array $requestor): bool => trim($requestor['Entity'] ?? '') !== '')),
        ];

        return view('hr.mpr-requestors.index', compact('requestors', 'stats', 'total', 'perPage', 'currentPage', 'lastPage'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|min:3',
            'email' => 'required|email',
            'username' => 'required|string|min:3',
            'role' => ['required', 'string', Rule::in(config('hris.auth.valid_roles_requestor', []))],
            'entity' => 'required|string',
            'branch' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $this->requestorRepo->create([
            'email' => $validated['email'],
            'username' => $validated['username'],
            'fullName' => $validated['name'],
            'role' => $validated['role'],
            'status' => 'Active',
            'passwordHash' => Hash::make($validated['password']),
            'entity' => $validated['entity'],
            'branch' => $validated['branch'],
            'createdBy' => session('hr_user.email', 'HR Administrator'),
        ]);

        if ($this->requestorRepo->findByEmail($validated['email']) === null) {
            return back()->withInput()->with('error', 'MPR requestor gagal disimpan ke sheet mpr_requestor.');
        }

        return redirect()->route('hr.mpr-requestors.index')
            ->with('success', "MPR requestor '{$validated['name']}' berhasil ditambahkan.");
    }
}
