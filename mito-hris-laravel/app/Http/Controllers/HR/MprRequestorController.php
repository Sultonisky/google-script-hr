<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Repositories\Contracts\MprRequestorRepositoryInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;
use App\Repositories\Contracts\AuditLogRepositoryInterface;
use App\Support\Rbac;

class MprRequestorController extends Controller
{
    public function __construct(
        protected MprRequestorRepositoryInterface $requestorRepo,
        protected AuditLogRepositoryInterface $auditRepo
    ) {}

    public function index(Request $request): View
    {
        $allRequestors = $this->requestorRepo->getAll();
        $searchFilter = trim((string) $request->query('search', ''));
        $statusFilter = strtolower(trim((string) $request->query('status', '')));
        if (!in_array($statusFilter, ['active', 'inactive'], true)) {
            $statusFilter = '';
        }

        $filtered = $allRequestors;
        if ($searchFilter !== '') {
            $needle = mb_strtolower($searchFilter);
            $filtered = array_values(array_filter(
                $filtered,
                fn (array $requestor): bool => str_contains($this->requestorSearchText($requestor), $needle)
            ));
        }
        if ($statusFilter !== '') {
            $filtered = array_values(array_filter(
                $filtered,
                fn (array $requestor): bool => strtolower(trim((string) ($requestor['Status'] ?? ''))) === $statusFilter
            ));
        }

        $perPage = max(1, min(100, (int) $request->query('per_page', 10)));
        $total = count($filtered);
        $lastPage = max(1, (int) ceil($total / $perPage));
        $currentPage = min(max(1, (int) $request->query('page', 1)), $lastPage);
        $requestors = array_values(array_slice($filtered, ($currentPage - 1) * $perPage, $perPage));
        $stats = [
            'total' => count($allRequestors),
            'active' => count(array_filter($allRequestors, fn(array $requestor): bool => strtolower(trim($requestor['Status'] ?? '')) === 'active')),
            'inactive' => count(array_filter($allRequestors, fn(array $requestor): bool => strtolower(trim($requestor['Status'] ?? '')) !== 'active')),
            'job_positions' => count(array_filter($allRequestors, fn(array $requestor): bool => trim((string) ($requestor['Job Position'] ?? '')) !== '')),
        ];

        return view('hr.mpr-requestors.index', compact(
            'requestors',
            'stats',
            'total',
            'perPage',
            'currentPage',
            'lastPage',
            'searchFilter',
            'statusFilter'
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|min:3',
            'email' => 'required|email',
            'username' => 'required|string|min:3',
            'job_position' => 'required|string|min:2|max:255',
            'role' => ['required', 'string', Rule::in(config('hris.auth.valid_roles_requestor', []))],
            'password' => 'required|string|min:8|confirmed',
        ]);

        $jobPosition = trim((string) $validated['job_position']);

        if ($this->requestorRepo->findByEmail($validated['email']) !== null) {
            return back()->withInput()->withErrors([
                'email' => 'Email sudah digunakan.',
            ]);
        }

        $this->requestorRepo->create([
            'email' => $validated['email'],
            'username' => $validated['username'],
            'fullName' => $validated['name'],
            'jobPosition' => $jobPosition,
            'role' => $validated['role'],
            'status' => 'Active',
            'passwordHash' => Hash::make($validated['password']),
            'createdBy' => session('hr_user.email', 'HR Administrator'),
        ]);

        if ($this->requestorRepo->findByEmail($validated['email']) === null) {
            return back()->withInput()->with('error', 'MPR requestor gagal disimpan ke sheet mpr_requestor.');
        }

        return redirect()->route('hr.mpr-requestors.index')
            ->with('success', "MPR requestor '{$validated['name']}' berhasil ditambahkan.");
    }

    public function update(Request $request, string $email): RedirectResponse|JsonResponse
    {
        $existing = $this->requestorRepo->findByEmail($email);
        if ($existing === null) {
            return $this->updateError($request, 'MPR requestor tidak ditemukan.', 404);
        }

        $validated = $request->validate([
            'name' => 'required|string|min:3',
            'email' => 'required|email',
            'username' => 'required|string|min:3',
            'job_position' => 'required|string|min:2|max:255',
            'role' => ['required', 'string', Rule::in(config('hris.auth.valid_roles_requestor', []))],
            'status' => ['required', 'string', Rule::in(['Active', 'Inactive'])],
            'password' => 'nullable|string|min:8|confirmed',
        ]);

        $newEmail = trim($validated['email']);
        $newUsername = trim($validated['username']);
        $jobPosition = trim((string) $validated['job_position']);

        foreach ($this->requestorRepo->getAll() as $requestor) {
            $sameRequestor = strtolower(trim((string) ($requestor['Email'] ?? ''))) === strtolower(trim($email));
            if (!$sameRequestor && strtolower(trim((string) ($requestor['Email'] ?? ''))) === strtolower($newEmail)) {
                return $this->updateError($request, 'Email sudah digunakan.', 422, ['email' => ['Email sudah digunakan.']]);
            }
            if (!$sameRequestor && strtolower(trim((string) ($requestor['Username'] ?? ''))) === strtolower($newUsername)) {
                return $this->updateError($request, 'Username sudah digunakan.', 422, ['username' => ['Username sudah digunakan.']]);
            }
        }

        $updates = [
            'fullName' => $validated['name'],
            'username' => $newUsername,
            'jobPosition' => $jobPosition,
            'role' => $validated['role'],
            'status' => $validated['status'],
        ];
        if (trim((string) ($existing['Email'] ?? '')) !== $newEmail) {
            $updates['email'] = $newEmail;
        }
        if (!empty($validated['password'])) {
            $updates['passwordHash'] = Hash::make($validated['password']);
        }

        $hasChanges = isset($updates['email']);
        if (!$hasChanges) {
            foreach (['Full Name' => 'fullName', 'Username' => 'username', 'Job Position' => 'jobPosition', 'Role' => 'role', 'Status' => 'status'] as $field => $key) {
                $oldValue = $existing[$field] ?? '';
                $newValue = $updates[$key] ?? '';
                if ((string) $oldValue !== (string) $newValue) {
                    $hasChanges = true;
                    break;
                }
            }
        }
        if (!$hasChanges && empty($validated['password'])) {
            return $this->updateSuccess($request, 'Tidak ada perubahan yang disimpan.');
        }

        $this->requestorRepo->updateByEmail($email, $updates);
        $lookupEmail = $updates['email'] ?? $email;
        $updated = $this->requestorRepo->findByEmail($lookupEmail);
        if ($updated === null) {
            return $this->updateError($request, 'MPR requestor gagal diperbarui di sheet mpr_requestor.', 500);
        }

        $auditFields = ['Full Name' => 'fullName', 'Username' => 'username', 'Job Position' => 'jobPosition', 'Role' => 'role', 'Status' => 'status'];
        if (isset($updates['email'])) {
            $auditFields = ['Email' => 'email'] + $auditFields;
        }
        foreach ($auditFields as $field => $key) {
            $oldValue = $existing[$field] ?? '';
            $newValue = $updated[$field] ?? $updates[$key] ?? '';
            if ((string) $oldValue !== (string) $newValue) {
                $this->auditRepo->log('MPR Requestor', $existing['Requestor ID'] ?? $email, 'UPDATE', $field, $oldValue, $newValue, session('hr_user.email', 'HR Administrator'), 'Dashboard');
            }
        }

        return $this->updateSuccess($request, 'MPR requestor berhasil diperbarui.');
    }

    public function forceDelete(Request $request, string $email): RedirectResponse|JsonResponse
    {
        abort_unless(
            Rbac::normalizeRole(session('hr_user.role')) === 'Super Admin',
            403,
            'Force delete hanya tersedia untuk Super Admin.'
        );

        $existing = $this->requestorRepo->findByEmail($email);
        if ($existing === null) {
            return $this->forceDeleteResponse($request, false, 'MPR requestor tidak ditemukan.', 404);
        }

        $targetEmail = strtolower(trim((string) ($existing['Email'] ?? $email)));
        $this->requestorRepo->forceDeleteByEmail($targetEmail);
        if ($this->requestorRepo->findByEmail($targetEmail) !== null) {
            return $this->forceDeleteResponse($request, false, 'MPR requestor gagal dihapus.', 500);
        }

        $this->auditRepo->log(
            'MPR Requestor',
            $existing['Requestor ID'] ?? $targetEmail,
            'FORCE_DELETE',
            null,
            $existing['Full Name'] ?? $targetEmail,
            null,
            session('hr_user.email', 'HR Administrator'),
            'Dashboard'
        );

        $name = trim((string) ($existing['Full Name'] ?? $targetEmail));

        return $this->forceDeleteResponse($request, true, "MPR requestor '{$name}' berhasil dihapus permanen.");
    }

    private function forceDeleteResponse(Request $request, bool $ok, string $message, int $status = 200): RedirectResponse|JsonResponse
    {
        if ($request->expectsJson()) {
            return response()->json(['success' => $ok, 'message' => $message], $ok ? 200 : $status);
        }

        return redirect()->route('hr.mpr-requestors.index')->with($ok ? 'success' : 'error', $message);
    }

    private function requestorSearchText(array $requestor): string
    {
        return mb_strtolower(implode(' ', [
            $requestor['Full Name'] ?? '',
            $requestor['Email'] ?? '',
            $requestor['Username'] ?? '',
            $requestor['Job Position'] ?? '',
        ]));
    }

    private function updateSuccess(Request $request, string $message): RedirectResponse|JsonResponse
    {
        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => $message]);
        }
        return redirect()->route('hr.mpr-requestors.index')->with('success', $message);
    }

    private function updateError(Request $request, string $message, int $status, array $errors = []): RedirectResponse|JsonResponse
    {
        if ($request->expectsJson()) {
            return response()->json(['success' => false, 'message' => $message, 'errors' => $errors], $status);
        }
        return back()->withInput()->withErrors($errors ?: ['update' => $message]);
    }
}
