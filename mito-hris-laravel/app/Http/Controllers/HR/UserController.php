<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;
use Illuminate\Validation\Rule;
use App\Repositories\Contracts\AuditLogRepositoryInterface;

class UserController extends Controller
{
    public function __construct(
        protected UserRepositoryInterface $userRepo,
        protected AuditLogRepositoryInterface $auditRepo
    ) {}

    public function index(): View
    {
        $users = $this->userRepo->getAll();
        $stats = [
            'total' => count($users),
            'active' => count(array_filter($users, fn (array $user): bool => strtolower(trim($user['Status'] ?? '')) === 'active')),
            'inactive' => count(array_filter($users, fn (array $user): bool => strtolower(trim($user['Status'] ?? '')) !== 'active')),
            'administrators' => count(array_filter($users, fn (array $user): bool => in_array($user['Role'] ?? '', ['Super Admin', 'Admin'], true))),
        ];

        return view('hr.users.index', compact('users', 'stats'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'  => 'required|string|min:3',
            'email' => 'required|email',
            'role'  => ['required', 'string', Rule::in(config('hris.auth.valid_roles_internal', []))],
            'password' => 'required|string|min:8|confirmed',
        ]);

        $this->userRepo->create([
            'email' => $validated['email'],
            'fullName' => $validated['name'],
            'role' => $validated['role'],
            'status' => 'Active',
            'passwordHash' => Hash::make($validated['password']),
            'createdBy' => session('hr_user.email', 'HR Administrator'),
        ]);

        if ($this->userRepo->findByEmail($validated['email']) === null) {
            return back()->withInput()->with('error', 'Pengguna gagal disimpan ke sheet Users.');
        }

        $this->auditRepo->log('User', $validated['email'], 'created', null, null, [
            'name' => $validated['name'], 'role' => $validated['role'], 'status' => 'Active'
        ], session('hr_user.email', 'HR Administrator'), 'Dashboard');

        return redirect()->route('hr.users.index')->with('success', "Pengguna '{$validated['name']}' berhasil ditambahkan.");
    }
}
