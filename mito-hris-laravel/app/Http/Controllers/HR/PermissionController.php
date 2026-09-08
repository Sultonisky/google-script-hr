<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Repositories\Contracts\AuditLogRepositoryInterface;
use App\Repositories\Contracts\PermissionCatalogRepositoryInterface;
use App\Repositories\Contracts\UserPermissionRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Services\PermissionResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class PermissionController extends Controller
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private UserPermissionRepositoryInterface $userPermissionRepository,
        private PermissionCatalogRepositoryInterface $permissionCatalog,
        private PermissionResolver $resolver,
        private AuditLogRepositoryInterface $auditRepository,
    ) {
    }

    public function index(): View
    {
        Gate::authorize('manage_permissions');

        $users = collect($this->userRepository->getAll())
            ->map(fn (array $user): array => $this->publicUser($user))
            ->sortBy('fullName')
            ->values();

        return view('hr.permissions.index', [
            'users' => $users,
            'permissionGroups' => collect($this->catalog())
                ->groupBy('group')
                ->map(fn ($permissions) => $permissions->values()->all())
                ->all(),
        ]);
    }

    public function users(Request $request): JsonResponse
    {
        Gate::authorize('manage_permissions');
        $query = strtolower(trim((string) $request->query('q', '')));

        $users = collect($this->userRepository->getAll())
            ->map(fn (array $user): array => $this->publicUser($user))
            ->filter(function (array $user) use ($query): bool {
                return $query === ''
                    || str_contains(strtolower($user['fullName']), $query)
                    || str_contains(strtolower($user['email']), $query)
                    || str_contains(strtolower($user['role']), $query);
            })
            ->sortBy('fullName')
            ->values();

        return response()->json(['users' => $users]);
    }

    public function show(string $email): JsonResponse
    {
        Gate::authorize('manage_permissions');
        $user = $this->findUser($email);
        if ($user === null) {
            return response()->json(['message' => 'Pengguna tidak ditemukan.'], 404);
        }

        $effective = [];
        foreach ($this->catalog() as $permission) {
            $key = $permission['key'];
            if ($key !== '') {
                $effective[$key] = $this->resolver->allows($this->sessionUser($user), $key);
            }
        }

        return response()->json([
            'user' => $this->publicUser($user),
            'permissions' => $effective,
        ]);
    }

    public function update(Request $request, string $email): JsonResponse
    {
        Gate::authorize('manage_permissions');
        $user = $this->findUser($email);
        if ($user === null) {
            return response()->json(['message' => 'Pengguna tidak ditemukan.'], 404);
        }

        $catalog = collect($this->catalog())
            ->mapWithKeys(function (array $permission): array {
                return [$permission['key'] => $permission];
            })
            ->filter(fn (array $permission, string $key): bool => $key !== '' && $permission['status'] === 'active');
        $validated = $request->validate([
            'permissions' => ['required', 'array'],
            'permissions.*' => ['string', 'distinct'],
        ]);
        $requested = array_fill_keys($validated['permissions'], true);
        $invalid = array_values(array_diff(array_keys($requested), $catalog->keys()->all()));
        if ($invalid !== []) {
            return response()->json(['message' => 'Permission tidak valid.', 'invalid' => $invalid], 422);
        }

        $target = $this->sessionUser($user);
        $actor = strtolower(trim((string) session('hr_user.email', '')));
        foreach ($catalog->keys()->all() as $key) {
            $old = $this->resolver->allows($target, $key);
            $new = isset($requested[$key]);
            if (!$this->userPermissionRepository->upsert($email, $key, $new, $actor)) {
                return response()->json(['message' => 'Permission gagal disimpan.'], 500);
            }
            if ($old !== $new) {
                $this->auditRepository->log('User Permission', strtolower(trim($email)), 'UPDATED', $key, $old, $new, $actor, 'Permission Management');
            }
        }
        $this->resolver->forget($email);

        return response()->json(['success' => true, 'message' => 'Permission berhasil diperbarui.']);
    }

    private function findUser(string $email): ?array
    {
        return $this->userRepository->findByEmail(strtolower(trim($email)));
    }

    private function catalog(): array
    {
        return array_values(array_map(function (array $permission): array {
            return [
                'key' => (string) ($permission['Permission Key'] ?? $permission['key'] ?? ''),
                'name' => (string) ($permission['Name'] ?? $permission['name'] ?? ''),
                'description' => (string) ($permission['Description'] ?? $permission['description'] ?? ''),
                'group' => (string) ($permission['Group'] ?? $permission['group'] ?? 'Other'),
                'status' => strtolower((string) ($permission['Status'] ?? $permission['status'] ?? 'Active')),
            ];
        }, $this->permissionCatalog->all()));
    }

    private function publicUser(array $user): array
    {
        return [
            'email' => strtolower(trim((string) ($user['Email'] ?? ''))),
            'fullName' => (string) ($user['Full Name'] ?? $user['Email'] ?? ''),
            'role' => (string) ($user['Role'] ?? ''),
            'status' => (string) ($user['Status'] ?? ''),
        ];
    }

    private function sessionUser(array $user): array
    {
        return [
            'email' => $user['Email'] ?? '',
            'fullName' => $user['Full Name'] ?? '',
            'role' => $user['Role'] ?? '',
        ];
    }
}