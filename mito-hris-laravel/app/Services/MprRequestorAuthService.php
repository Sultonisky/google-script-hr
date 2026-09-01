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
    protected function normalizeEntities(array $requestor): array
    {
        $raw = $requestor['Entities'] ?? $requestor['Entity'] ?? $requestor['entities'] ?? $requestor['entity'] ?? '';

        if (is_array($raw)) {
            $items = $raw;
        } else {
            $items = preg_split('/[,;\n|]+/', (string) $raw) ?: [];
        }

        $normalized = [];
        foreach ($items as $item) {
            $value = trim((string) $item);
            if ($value === '') {
                continue;
            }
            $normalized[] = $value;
        }

        return $normalized;
    }

    protected function normalizeBranch(array $requestor): string
    {
        foreach (['Branch', 'branch'] as $key) {
            $value = trim((string) ($requestor[$key] ?? ''));
            if ($value !== '') {
                return $value;
            }
        }

        return '';
    }

    protected function normalizeJobPosition(array $requestor): string
    {
        foreach (['Job Position', 'jobPosition', 'job_position'] as $key) {
            $value = trim((string) ($requestor[$key] ?? ''));
            if ($value !== '') {
                return $value;
            }
        }

        return '';
    }

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

        $role = trim((string) ($requestor['Role'] ?? ''));
        $normalizedRole = strtolower($role);
        if (!in_array($normalizedRole, ['manpower', 'manager'], true)) {
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

        $jobPosition = $this->normalizeJobPosition($requestor);
        $entities = $this->normalizeEntities($requestor);
        $branch = $this->normalizeBranch($requestor);

        return [
            'success' => true,
            'message' => 'Login berhasil.',
            'user'    => [
                'email'       => $requestor['Email'],
                'fullName'    => $requestor['Full Name'] ?? $requestor['Email'],
                'jobPosition' => $jobPosition,
                'role'        => $role ?: 'Manager',
                'permissions' => ['view_mpr', 'create_mpr', 'export_mpr'],
                'portal'      => 'mpr',
                'auth_domain' => 'mpr_requestor',
                'requestor_id' => $requestor['Requestor ID'] ?? '',
                'entities'    => $entities,
                'branch'      => $branch,
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
