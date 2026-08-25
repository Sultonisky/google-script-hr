<?php

namespace App\Services;

use App\Repositories\Contracts\MprRequestorRepositoryInterface;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

/**
 * Authentication service for MPR Requestor domain.
 *
 * This is intentionally separate from AuthService (internal HRIS Users).
 * Manager accounts ONLY exist in the mpr_requestor sheet, never in Users.
 */
class MprRequestorAuthService
{
    public function __construct(
        private MprRequestorRepositoryInterface $requestorRepo,
    ) {}

    // =========================================================================
    // AUTHENTICATION
    // =========================================================================

    /**
     * Attempt login against the mpr_requestor sheet.
     *
     * Returns an array with:
     *   success: bool
     *   error:   string  (only when success = false)
     *   user:    array   (only when success = true) — this is put into session('hr_user')
     */
    public function attemptLogin(string $identifier, string $password): array
    {
        $requestor = $this->requestorRepo->findByIdentifier($identifier);

        if ($requestor === null) {
            return [
                'success' => false,
                'error'   => null, // null = not found in this domain (caller will try next domain)
            ];
        }

        // Domain confirmed: this identifier belongs to an MPR Requestor.
        // All further errors are hard rejections (not "try next domain").

        if (strtolower(trim($requestor['Status'] ?? '')) !== 'active') {
            return [
                'success' => false,
                'error'   => 'Akun Anda (' . ($requestor['Email'] ?? '') . ') tidak aktif. Hubungi administrator.',
            ];
        }

        // Role must be Manager
        $role = trim($requestor['Role'] ?? '');
        if (strtolower($role) !== 'manager') {
            return [
                'success' => false,
                'error'   => 'Akun ini tidak memiliki akses ke portal MPR.',
            ];
        }

        $storedHash = trim((string) ($requestor['Password Hash'] ?? ''));
        if ($storedHash === '') {
            return [
                'success' => false,
                'error'   => 'Password belum diatur. Hubungi administrator.',
            ];
        }

        $passwordValid = false;
        $needsRehash   = false;

        if (str_starts_with($storedHash, '$2y$') || str_starts_with($storedHash, '$2b$')) {
            $passwordValid = Hash::check($password, $storedHash);
        } else {
            // Legacy SHA-256 fallback
            $sha256        = hash('sha256', $password);
            $passwordValid = hash_equals($storedHash, $sha256);
            $needsRehash   = true;
        }

        if (!$passwordValid) {
            return [
                'success' => false,
                'error'   => 'Password salah. Silakan coba lagi.',
            ];
        }

        if ($needsRehash) {
            try {
                $this->requestorRepo->updateByEmail($requestor['Email'], [
                    'passwordHash' => Hash::make($password),
                ]);
            } catch (\Throwable $e) {
                Log::warning('MprRequestorAuthService: failed to rehash password for ' . $requestor['Email']);
            }
        }

        $this->requestorRepo->updateLastLogin($requestor['Email']);

        // Normalize entity: stored as "MSI" or "MSI, SPI, MEP"
        $rawEntity     = trim($requestor['Entity'] ?? '');
        $entitiesArray = $rawEntity !== ''
            ? array_values(array_filter(array_map('trim', explode(',', $rawEntity))))
            : [];

        return [
            'success' => true,
            'message' => 'Login berhasil.',
            'user'    => [
                'email'        => $requestor['Email'],
                'fullName'     => $requestor['Full Name']    ?? $requestor['Email'],
                'role'         => 'Manager',
                'permissions'  => config('hris.auth.role_permissions.Manager', ['view_mpr', 'create_mpr', 'export_mpr']),
                'entities'     => $entitiesArray,
                'branch'       => trim($requestor['Branch'] ?? ''),
                // Identity source marker — used by middleware to differentiate domains
                'auth_domain'  => 'mpr_requestor',
                'requestor_id' => $requestor['Requestor ID'] ?? '',
            ],
        ];
    }

    // =========================================================================
    // HELPERS
    // =========================================================================

    public function hashPassword(string $password): string
    {
        return Hash::make($password);
    }

    public function isEmpty(): bool
    {
        return $this->requestorRepo->isEmpty();
    }
}
