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

class MprRequestorController extends Controller
{
    public function __construct(
        protected MprRequestorRepositoryInterface $requestorRepo,
        protected AuditLogRepositoryInterface $auditRepo
    ) {}

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
            'active' => count(array_filter($allRequestors, fn(array $requestor): bool => strtolower(trim($requestor['Status'] ?? '')) === 'active')),
            'inactive' => count(array_filter($allRequestors, fn(array $requestor): bool => strtolower(trim($requestor['Status'] ?? '')) !== 'active')),
            'job_positions' => count(array_filter($allRequestors, fn(array $requestor): bool => trim((string) ($requestor['Job Position'] ?? '')) !== '')),
        ];

        return view('hr.mpr-requestors.index', compact('requestors', 'stats', 'total', 'perPage', 'currentPage', 'lastPage'));
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
            'username' => 'required|string|min:3',
            'job_position' => 'required|string|min:2|max:255',
            'role' => ['required', 'string', Rule::in(config('hris.auth.valid_roles_requestor', []))],
            'status' => ['required', 'string', Rule::in(['Active', 'Inactive'])],
            'password' => 'nullable|string|min:8|confirmed',
        ]);

        $jobPosition = trim((string) $validated['job_position']);

        foreach ($this->requestorRepo->getAll() as $requestor) {
            $sameRequestor = strtolower(trim($requestor['Email'] ?? '')) === strtolower(trim($email));
            if (!$sameRequestor && strtolower(trim($requestor['Username'] ?? '')) === strtolower(trim($validated['username']))) {
                return $this->updateError($request, 'Username sudah digunakan.', 422, ['username' => ['Username sudah digunakan.']]);
            }
        }

        $updates = [
            'fullName' => $validated['name'],
            'username' => $validated['username'],
            'jobPosition' => $jobPosition,
            'role' => $validated['role'],
            'status' => $validated['status'],
        ];
        if (!empty($validated['password'])) {
            $updates['passwordHash'] = Hash::make($validated['password']);
        }

        $hasChanges = false;
        foreach (['Full Name' => 'fullName', 'Username' => 'username', 'Job Position' => 'jobPosition', 'Role' => 'role', 'Status' => 'status'] as $field => $key) {
            $oldValue = $existing[$field] ?? '';
            $newValue = $updates[$key] ?? '';
            if ((string) $oldValue !== (string) $newValue) {
                $hasChanges = true;
                break;
            }
        }
        if (!$hasChanges && empty($validated['password'])) {
            return $this->updateSuccess($request, 'Tidak ada perubahan yang disimpan.');
        }

        $this->requestorRepo->updateByEmail($email, $updates);
        $updated = $this->requestorRepo->findByEmail($email);
        if ($updated === null) {
            return $this->updateError($request, 'MPR requestor gagal diperbarui di sheet mpr_requestor.', 500);
        }

        $auditFields = ['Full Name' => 'fullName', 'Username' => 'username', 'Job Position' => 'jobPosition', 'Role' => 'role', 'Status' => 'status'];
        foreach ($auditFields as $field => $key) {
            $oldValue = $existing[$field] ?? '';
            $newValue = $updated[$field] ?? $updates[$key] ?? '';
            if ((string) $oldValue !== (string) $newValue) {
                $this->auditRepo->log('MPR Requestor', $existing['Requestor ID'] ?? $email, 'UPDATE', $field, $oldValue, $newValue, session('hr_user.email', 'HR Administrator'), 'Dashboard');
            }
        }

        return $this->updateSuccess($request, 'MPR requestor berhasil diperbarui.');
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
