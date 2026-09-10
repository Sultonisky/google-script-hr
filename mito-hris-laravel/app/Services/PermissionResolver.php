<?php

namespace App\Services;

use App\Repositories\Contracts\UserPermissionRepositoryInterface;
use App\Support\PermissionCatalog;
use App\Support\Rbac;

class PermissionResolver
{
    private array $cache = [];

    public function __construct(private UserPermissionRepositoryInterface $repository)
    {
    }

    public function allows(array $user, string $permission): bool
    {
        if (PermissionCatalog::find($permission) === null && $permission !== '*') {
            return false;
        }

        // MPR requestors are a separate portal identity and keep their
        // existing dedicated-session permission contract.
        if (($user['auth_domain'] ?? null) === 'mpr_requestor') {
            return in_array('*', $user['permissions'] ?? [], true)
                || in_array($permission, $user['permissions'] ?? [], true);
        }

        if (Rbac::normalizeRole($user['role'] ?? null) === 'Super Admin') {
            return true;
        }

        if ($permission === 'lookup_employee') {
            return $this->allows($user, 'view_employees')
                || $this->allows($user, 'view_asset')
                || $this->allows($user, 'view_certification');
        }

        $email = strtolower(trim((string) ($user['email'] ?? $user['Email'] ?? '')));
        $mappings = $this->mappings($email);
        if (array_key_exists($permission, $mappings)) {
            return $mappings[$permission];
        }

        foreach ($this->compatibilityAliases($permission) as $alias) {
            if (array_key_exists($alias, $mappings)) {
                return $mappings[$alias];
            }
        }

        return false;
    }

    public function mappings(string $email): array
    {
        $email = strtolower(trim($email));
        if (!array_key_exists($email, $this->cache)) {
            $this->cache[$email] = [];
            foreach ($this->repository->mappingsForUser($email) as $mapping) {
                $key = trim((string) ($mapping['Permission Key'] ?? ''));
                if ($key === '') {
                    continue;
                }
                $granted = $this->parseBoolean($mapping['Granted'] ?? false);
                // A conflicting duplicate must never turn an explicit revoke
                // into a grant; false is the deterministic safety state.
                $this->cache[$email][$key] = array_key_exists($key, $this->cache[$email])
                    ? ($this->cache[$email][$key] && $granted)
                    : $granted;
            }
        }

        return $this->cache[$email];
    }

    public function forget(string $email): void
    {
        unset($this->cache[strtolower(trim($email))]);
    }

    /**
     * Return the dependency requirements for a permission.
     *
     * Keys are permission keys; values are arrays of permission keys that must
     * also be granted for the requested permission to be meaningful.
     *
     * Portal access permissions have no requirements.
     * Feature permissions require their portal access permission.
     */
    public function dependenciesFor(string $permission): array
    {
        $permission = trim((string) $permission);

        return match ($permission) {
            'assets.view',
            'assets.create',
            'assets.update',
            'assets.delete',
            'assets.assign',
            'assets.return' => ['assets.access'],

            'assets.generate_code' => ['assets.access'],

            'certificates.view',
            'certificates.create',
            'certificates.update',
            'certificates.delete' => ['certificates.access'],

            'certificates.generate_code' => ['certificates.access'],

            default => [],
        };
    }

    /**
     * Normalize a set of granted permissions by adding missing dependencies.
     *
     * @return array<string, bool> normalized permission map
     */
    public function normalizeDependencies(array $granted): array
    {
        $normalized = $granted;

        foreach ($granted as $permission => $value) {
            if ($value !== true) {
                continue;
            }

            foreach ($this->dependenciesFor($permission) as $dependency) {
                if (empty($normalized[$dependency])) {
                    $normalized[$dependency] = true;
                }
            }
        }

        return $normalized;
    }

    /**
     * Determine if user has access to the general HRIS portal.
     *
     * Architecture: Users with ONLY dedicated portal permissions (assets, certificates)
     * must be explicitly restricted via Permission Mappings. If a user has:
     *   - No permission mappings → allowed (uses role-based access)
     *   - Permission mappings with non-dedicated permissions → allowed
     *   - Permission mappings with ONLY dedicated permissions → denied
     *
     * To restrict user to dedicated portals only (e.g., assets-only user):
     * 1. Create user in Users sheet with desired Role
     * 2. Go to Permission Management (HR → Permissions)
     * 3. Select the user and assign ONLY dedicated portal permissions (assets.*, certificates.*)
     * 4. Leave all HRIS permissions unchecked
     * 5. Save permissions
     *
     * The user will then be denied HRIS portal access on login.
     */
    public function hasHrisAccess(array $user): bool
    {
        if (Rbac::normalizeRole($user['role'] ?? null) === 'Super Admin') {
            return true;
        }

        $mappings = $this->mappings((string) ($user['email'] ?? $user['Email'] ?? ''));
        $granted  = array_filter($mappings, static fn (bool $v) => $v === true);

        if ($granted === []) {
            return true;
        }

        foreach (array_keys($granted) as $permission) {
            if (!$this->isDedicatedPortalPermission($permission)) {
                return true;
            }
        }

        return false;
    }

    private function compatibilityAliases(string $permission): array
    {
        return match (true) {
            in_array($permission, ['assets.view'], true) => ['view_asset'],
            in_array($permission, ['assets.create', 'assets.update', 'assets.delete', 'assets.assign', 'assets.return', 'assets.generate_code'], true) => ['edit_asset'],
            in_array($permission, ['certificates.view'], true) => ['view_certification'],
            in_array($permission, ['certificates.create', 'certificates.update', 'certificates.delete', 'certificates.generate_code'], true) => ['manage_certification'],
            'view_asset' === $permission => ['assets.view'],
            'edit_asset' === $permission => ['assets.create', 'assets.update', 'assets.delete', 'assets.assign', 'assets.return', 'assets.generate_code'],
            'view_certification' === $permission => ['certificates.view'],
            'manage_certification' === $permission => ['certificates.create', 'certificates.update', 'certificates.delete', 'certificates.generate_code'],
            default => [],
        };
    }

    private function isDedicatedPortalPermission(string $permission): bool
    {
        return str_starts_with($permission, 'assets.')
            || str_starts_with($permission, 'certificates.')
            || in_array($permission, [
                'view_asset',
                'edit_asset',
                'view_certification',
                'manage_certification',
            ], true);
    }

    private function parseBoolean(mixed $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? in_array(strtolower(trim((string) $value)), ['1', 'yes', 'y'], true);
    }
}