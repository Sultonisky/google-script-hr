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

    private function parseBoolean(mixed $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? in_array(strtolower(trim((string) $value)), ['1', 'yes', 'y'], true);
    }
}