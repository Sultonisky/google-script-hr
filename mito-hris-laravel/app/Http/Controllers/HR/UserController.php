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
use App\Repositories\Contracts\UserPermissionRepositoryInterface;
use App\Support\Rbac;
use Illuminate\Http\JsonResponse;

class UserController extends Controller
{
    public function __construct(
        protected UserRepositoryInterface $userRepo,
        protected AuditLogRepositoryInterface $auditRepo,
        protected UserPermissionRepositoryInterface $permissionRepo,
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
            'role'  => ['required', 'string', Rule::in(config('hris.auth.valid_roles', []))],
            'password' => 'required|string|min:8|confirmed',
        ]);

        $newEmail = trim($validated['email']);
        $newUsername = trim($validated['username']);
        foreach ($this->userRepo->getAll() as $user) {
            if (strtolower(trim((string) ($user['Email'] ?? ''))) === strtolower($newEmail)) {
                return back()->withInput()->withErrors(['email' => 'Email sudah digunakan.']);
            }
            if (strtolower(trim((string) ($user['Username'] ?? ''))) === strtolower($newUsername)) {
                return back()->withInput()->withErrors(['username' => 'Username sudah digunakan.']);
            }
        }

        $timestamp = now()->timezone('Asia/Jakarta')->format('Y-m-d H:i:s');

        $this->userRepo->create([
            'email' => $newEmail,
            'username' => $newUsername,
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

        if ($this->userRepo->findByEmail($newEmail) === null) {
            return back()->withInput()->with('error', 'Pengguna gagal disimpan ke sheet Users.');
        }

        $this->auditRepo->log('User', $newEmail, 'created', null, null, [
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
            'email' => 'required|email',
            'username' => 'required|string|min:3',
            'role' => ['required', 'string', Rule::in(config('hris.auth.valid_roles', []))],
            'status' => ['required', 'string', Rule::in(['Active', 'Inactive'])],
            'password' => 'nullable|string|min:8|confirmed',
        ]);

        $newEmail = trim($validated['email']);
        $newUsername = trim($validated['username']);

        foreach ($this->userRepo->getAll() as $user) {
            $sameUser = strtolower(trim((string) ($user['Email'] ?? ''))) === strtolower(trim($email));
            if (!$sameUser && strtolower(trim((string) ($user['Email'] ?? ''))) === strtolower($newEmail)) {
                return $this->updateError($request, 'Email sudah digunakan.', 422, ['email' => ['Email sudah digunakan.']]);
            }
            if (!$sameUser && strtolower(trim((string) ($user['Username'] ?? ''))) === strtolower($newUsername)) {
                return $this->updateError($request, 'Username sudah digunakan.', 422, ['username' => ['Username sudah digunakan.']]);
            }
        }

        $updates = [
            'fullName' => $validated['name'],
            'username' => $newUsername,
            'role' => $validated['role'],
            'status' => $validated['status'],
            'updatedAt' => now()->timezone('Asia/Jakarta')->format('Y-m-d H:i:s'),
            'updated_at' => now()->timezone('Asia/Jakarta')->format('Y-m-d H:i:s'),
        ];
        if (trim((string) ($existing['Email'] ?? '')) !== $newEmail) {
            $updates['email'] = $newEmail;
        }
        if (!empty($validated['password'])) {
            $updates['passwordHash'] = Hash::make($validated['password']);
        }

        $hasChanges = isset($updates['email']);
        if (!$hasChanges) {
            foreach (['Full Name' => 'fullName', 'Username' => 'username', 'Role' => 'role', 'Status' => 'status'] as $field => $key) {
                if ((string) ($existing[$field] ?? '') !== (string) $updates[$key]) {
                    $hasChanges = true;
                    break;
                }
            }
        }
        if (!$hasChanges && empty($validated['password'])) {
            return $this->updateSuccess($request, 'Tidak ada perubahan yang disimpan.');
        }

        $this->userRepo->updateByEmail($email, $updates);
        $lookupEmail = $updates['email'] ?? $email;
        $updated = $this->userRepo->findByEmail($lookupEmail);
        if ($updated === null) {
            return $this->updateError($request, 'Pengguna gagal diperbarui di sheet Users.', 500);
        }

        $auditFields = ['Full Name' => 'fullName', 'Username' => 'username', 'Role' => 'role', 'Status' => 'status'];
        if (isset($updates['email'])) {
            $auditFields = ['Email' => 'email'] + $auditFields;
        }
        foreach ($auditFields as $field => $key) {
            $oldValue = $existing[$field] ?? '';
            $newValue = $updated[$field] ?? $updates[$key];
            if ((string) $oldValue !== (string) $newValue) {
                $this->auditRepo->log('User', $lookupEmail, 'UPDATE', $field, $oldValue, $newValue, session('hr_user.email', 'HR Administrator'), 'Dashboard');
            }
        }

        if (isset($updates['email'])) {
            $this->permissionRepo->reassignEmail($email, (string) $updates['email']);
        }
        $this->syncEditedActorSession($email, $updated);

        return $this->updateSuccess($request, 'Pengguna berhasil diperbarui.');
    }

    public function forceDelete(Request $request, string $email): RedirectResponse|JsonResponse
    {
        $this->ensureSuperAdmin();

        $existing = $this->userRepo->findByEmail($email);
        if ($existing === null) {
            return $this->forceDeleteResponse($request, false, 'Pengguna tidak ditemukan.', 404);
        }

        $targetEmail = strtolower(trim((string) ($existing['Email'] ?? $email)));
        $actorEmail = strtolower(trim((string) session('hr_user.email', '')));
        if ($targetEmail !== '' && $targetEmail === $actorEmail) {
            return $this->forceDeleteResponse($request, false, 'Akun yang sedang login tidak bisa di-force delete.', 422);
        }
        if ($this->isLastActiveSuperAdmin($existing)) {
            return $this->forceDeleteResponse($request, false, 'Super Admin aktif terakhir tidak bisa di-force delete.', 422);
        }

        $this->permissionRepo->forceDeleteByEmail($targetEmail);
        $this->userRepo->forceDeleteByEmail($targetEmail);
        if ($this->userRepo->findByEmail($targetEmail) !== null) {
            return $this->forceDeleteResponse($request, false, 'Pengguna gagal dihapus.', 500);
        }

        $this->auditRepo->log(
            'User',
            $targetEmail,
            'FORCE_DELETE',
            null,
            $existing['Full Name'] ?? $targetEmail,
            null,
            session('hr_user.email', 'HR Administrator'),
            'Dashboard'
        );

        $name = trim((string) ($existing['Full Name'] ?? $targetEmail));

        return $this->forceDeleteResponse($request, true, "Pengguna '{$name}' berhasil dihapus permanen.");
    }

    private function ensureSuperAdmin(): void
    {
        abort_unless(
            Rbac::normalizeRole(session('hr_user.role')) === 'Super Admin',
            403,
            'Force delete hanya tersedia untuk Super Admin.'
        );
    }

    /**
     * @param  array<string, mixed>  $existing
     */
    private function isLastActiveSuperAdmin(array $existing): bool
    {
        if (Rbac::normalizeRole($existing['Role'] ?? null) !== 'Super Admin') {
            return false;
        }
        if (strtolower(trim((string) ($existing['Status'] ?? ''))) !== 'active') {
            return false;
        }

        $activeSuperAdmins = 0;
        foreach ($this->userRepo->getAll() as $user) {
            if (Rbac::normalizeRole($user['Role'] ?? null) !== 'Super Admin') {
                continue;
            }
            if (strtolower(trim((string) ($user['Status'] ?? ''))) === 'active') {
                $activeSuperAdmins++;
            }
        }

        return $activeSuperAdmins <= 1;
    }

    private function forceDeleteResponse(Request $request, bool $ok, string $message, int $status = 200): RedirectResponse|JsonResponse
    {
        if ($request->expectsJson()) {
            return response()->json(['success' => $ok, 'message' => $message], $ok ? 200 : $status);
        }

        return redirect()->route('hr.users.index')->with($ok ? 'success' : 'error', $message);
    }

    /**
     * @param  array<string, mixed>  $updated
     */
    private function syncEditedActorSession(string $previousEmail, array $updated): void
    {
        $actor = session('hr_user');
        if (!is_array($actor)) {
            return;
        }
        if (strtolower(trim((string) ($actor['email'] ?? ''))) !== strtolower(trim($previousEmail))) {
            return;
        }

        $actor['email'] = (string) ($updated['Email'] ?? $actor['email']);
        if (trim((string) ($updated['Full Name'] ?? '')) !== '') {
            $actor['fullName'] = $updated['Full Name'];
        }
        session(['hr_user' => $actor]);
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
