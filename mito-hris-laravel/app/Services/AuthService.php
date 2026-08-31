<?php

namespace App\Services;

use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Support\Facades\Hash;
use App\Support\Rbac;


class AuthService
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
    ) {}

    public function attemptLogin(string $identifier, string $password): array
    {
        $user = $this->userRepository->findByIdentifier($identifier);

        if ($user === null) {
            return [
                'success' => false,
                'error' => 'User tidak ditemukan. Periksa email/username dan password Anda.',
            ];
        }

        // Manager accounts are NOT stored in the Users sheet.
        // They live in mpr_requestor. Reject early to prevent domain confusion.
        $role = Rbac::normalizeRole($user['Role'] ?? null);
        if (strtolower($role) === 'manager') {
            return [
                'success' => false,
                'error'   => 'Akun ini bukan akun internal HRIS. Silakan gunakan portal MPR.',
            ];
        }

        if (($user['Status'] ?? '') !== 'Active') {
            return [
                'success' => false,
                'error' => 'Akun Anda (' . ($user['Email'] ?? '') . ') belum aktif. Hubungi administrator.',
            ];
        }

        $storedHash = trim((string) ($user['Password Hash'] ?? ''));
        if ($storedHash === '') {
            return [
                'success' => false,
                'error' => 'Password belum diatur. Hubungi administrator.',
            ];
        }

        $passwordValid = false;
        $needsRehash = false;

        if (str_starts_with($storedHash, '$2y$') || str_starts_with($storedHash, '$2b$')) {
            $passwordValid = Hash::check($password, $storedHash);
        } else {
            $sha256 = hash('sha256', $password);
            $passwordValid = hash_equals($storedHash, $sha256);
            $needsRehash = true;
        }

        if (!$passwordValid) {
            return [
                'success' => false,
                'error' => 'Password salah. Silakan coba lagi.',
            ];
        }

        if ($needsRehash) {
            $this->userRepository->updateByEmail($user['Email'], [
                'passwordHash' => Hash::make($password),
            ]);
        }

        $this->userRepository->updateLastLogin($user['Email']);

        return [
            'success' => true,
            'message' => 'Login berhasil.',
            'user' => [
                'email'       => $user['Email'],
                'fullName'    => $user['Full Name'] ?? $user['Email'],
                'role'        => $role,
                'permissions' => $this->getPermissionsForRole($role),
                // Internal HRIS users do NOT use entity/branch for auth
                'entities'    => [],
                'branch'      => '',
                'portal'      => 'hris',
                // Identity source marker
                'auth_domain' => 'users',
            ],
        ];
    }

    public function getPermissionsForRole(string $role): array
    {
        return Rbac::permissionsForRole($role);
    }

    public function hasPermission(string $role, string $permission): bool
    {
        return Rbac::allows($role, $permission);
    }

    public function isValidRole(string $role): bool
    {
        // Internal HRIS roles only — Manager is NOT a valid internal role
        $validRoles = config('hris.auth.valid_roles_internal', []);

        return in_array(Rbac::normalizeRole($role), $validRoles, true);
    }

    public function hashPassword(string $password): string
    {
        return Hash::make($password);
    }

    public function isFirstRun(): bool
    {
        return $this->userRepository->isEmpty();
    }

    public function seedSuperAdmin(string $email, string $fullName): array
    {
        if (!$this->isFirstRun()) {
            return [
                'success' => false,
                'error' => 'Users sheet sudah berisi data. Gunakan addUser untuk menambah pengguna.',
            ];
        }

        $this->userRepository->create([
            'email' => $email,
            'fullName' => $fullName,
            'role' => 'Super Admin',
            'status' => 'Active',
            'passwordHash' => '',
            'createdBy' => 'system',
        ]);

        return [
            'success' => true,
            'message' => 'Super Admin berhasil dibuat: ' . $email,
            'user' => [
                'email' => strtolower(trim($email)),
                'fullName' => $fullName,
                'role' => 'Super Admin',
                'permissions' => $this->getPermissionsForRole('Super Admin'),
            ],
        ];
    }
}
