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
use Illuminate\Http\JsonResponse;

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
            'active' => count(array_filter($users, fn(array $user): bool => strtolower(trim($user['Status'] ?? '')) === 'active')),
            'inactive' => count(array_filter($users, fn(array $user): bool => strtolower(trim($user['Status'] ?? '')) !== 'active')),
            'administrators' => count(array_filter($users, fn(array $user): bool => in_array($user['Role'] ?? '', ['Super Admin', 'Admin'], true))),
        ];

        return view('hr.users.index', compact('users', 'stats'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'  => 'required|string|min:3',
            'email' => 'required|email',
            'username' => 'required|string|min:3',
            'role'  => ['required', 'string', Rule::in(config('hris.auth.valid_roles_internal', []))],
            'password' => 'required|string|min:8|confirmed',
        ]);

        $timestamp = now()->timezone('Asia/Jakarta')->format('Y-m-d H:i:s');

        $this->userRepo->create([
            'email' => $validated['email'],
            'username' => $validated['username'],
            'fullName' => $validated['name'],
            'role' => $validated['role'],
            'status' => 'Active',
            'passwordHash' => Hash::make($validated['password']),
            'createdAt' => $timestamp,
            'updatedAt' => $timestamp,
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
            'createdBy' => session('hr_user.email', 'HR Administrator'),
        ]);

        if ($this->userRepo->findByEmail($validated['email']) === null) {
            return back()->withInput()->with('error', 'Pengguna gagal disimpan ke sheet Users.');
        }

        $this->auditRepo->log('User', $validated['email'], 'created', null, null, [
            'name' => $validated['name'],
            'role' => $validated['role'],
            'status' => 'Active'
        ], session('hr_user.email', 'HR Administrator'), 'Dashboard');

        return redirect()->route('hr.users.index')->with('success', "Pengguna '{$validated['name']}' berhasil ditambahkan.");
    }

    public function update(Request $request, string $email): RedirectResponse|JsonResponse
    {
        $existing = $this->userRepo->findByEmail($email);
        if ($existing === null) {
            return $this->updateError($request, 'Pengguna tidak ditemukan.', 404);
        }

        $validated = $request->validate([
            'name' => 'required|string|min:3',
            'username' => 'required|string|min:3',
            'role' => ['required', 'string', Rule::in(config('hris.auth.valid_roles_internal', []))],
            'status' => ['required', 'string', Rule::in(['Active', 'Inactive'])],
            'password' => 'nullable|string|min:8|confirmed',
        ]);

        foreach ($this->userRepo->getAll() as $user) {
            $sameUser = strtolower(trim($user['Email'] ?? '')) === strtolower(trim($email));
            if (!$sameUser && strtolower(trim($user['Username'] ?? '')) === strtolower(trim($validated['username']))) {
                return $this->updateError($request, 'Username sudah digunakan.', 422, ['username' => ['Username sudah digunakan.']]);
            }
        }

        $updates = [
            'fullName' => $validated['name'],
            'username' => $validated['username'],
            'role' => $validated['role'],
            'status' => $validated['status'],
            'updatedAt' => now()->timezone('Asia/Jakarta')->format('Y-m-d H:i:s'),
            'updated_at' => now()->timezone('Asia/Jakarta')->format('Y-m-d H:i:s'),
        ];
        if (!empty($validated['password'])) {
            $updates['passwordHash'] = Hash::make($validated['password']);
        }

        $hasChanges = false;
        foreach (['Full Name' => 'fullName', 'Username' => 'username', 'Role' => 'role', 'Status' => 'status'] as $field => $key) {
            if ((string) ($existing[$field] ?? '') !== (string) $updates[$key]) {
                $hasChanges = true;
                break;
            }
        }
        if (!$hasChanges && empty($validated['password'])) {
            return $this->updateSuccess($request, 'Tidak ada perubahan yang disimpan.');
        }

        $this->userRepo->updateByEmail($email, $updates);
        $updated = $this->userRepo->findByEmail($email);
        if ($updated === null) {
            return $this->updateError($request, 'Pengguna gagal diperbarui di sheet Users.', 500);
        }

        $auditFields = ['Full Name' => 'fullName', 'Username' => 'username', 'Role' => 'role', 'Status' => 'status'];
        foreach ($auditFields as $field => $key) {
            $oldValue = $existing[$field] ?? '';
            $newValue = $updated[$field] ?? $updates[$key];
            if ((string) $oldValue !== (string) $newValue) {
                $this->auditRepo->log('User', $email, 'UPDATE', $field, $oldValue, $newValue, session('hr_user.email', 'HR Administrator'), 'Dashboard');
            }
        }

        return $this->updateSuccess($request, 'Pengguna berhasil diperbarui.');
    }

    private function updateSuccess(Request $request, string $message): RedirectResponse|JsonResponse
    {
        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => $message]);
        }
        return redirect()->route('hr.users.index')->with('success', $message);
    }

    private function updateError(Request $request, string $message, int $status, array $errors = []): RedirectResponse|JsonResponse
    {
        if ($request->expectsJson()) {
            return response()->json(['success' => false, 'message' => $message, 'errors' => $errors], $status);
        }
        return back()->withInput()->withErrors($errors ?: ['update' => $message]);
    }
}
