<?php

namespace App\Support;

final class Rbac
{
    public static function normalizeRole(?string $role): string
    {
        $role = trim((string) $role);
        $roles = config('hris.auth.valid_roles_internal', []);

        foreach ($roles as $validRole) {
            if (strcasecmp($role, $validRole) === 0) {
                return $validRole;
            }
        }

        $alias = config('hris.auth.role_aliases', [])[$role] ?? null;
        if ($alias !== null) {
            return $alias;
        }

        foreach (config('hris.auth.role_aliases', []) as $legacyRole => $canonicalRole) {
            if (strcasecmp($role, $legacyRole) === 0) {
                return $canonicalRole;
            }
        }

        return $role;
    }

    public static function permissionsForRole(?string $role): array
    {
        $canonicalRole = self::normalizeRole($role);
        return config('hris.auth.role_permissions', [])[$canonicalRole] ?? [];
    }

    public static function allows(?string $role, string $permission): bool
    {
        $permissions = self::permissionsForRole($role);
        return in_array('*', $permissions, true) || in_array($permission, $permissions, true);
    }

    public static function allowsDedicatedPortal(array $user, string $portal): bool
    {
        $allowedRoles = config("hris.auth.dedicated_portal_roles.{$portal}", []);
        $sourceRole = trim((string) ($user['source_role'] ?? $user['role'] ?? ''));

        foreach ($allowedRoles as $allowedRole) {
            if (strcasecmp($sourceRole, (string) $allowedRole) === 0) {
                return true;
            }
        }

        return false;
    }
}
