<?php

namespace App\Support;

/**
 * Migration-only reader for the legacy role matrix.
 *
 * Runtime authorization must use User_Permissions instead.
 */
final class LegacyRolePermissionSource
{
    public static function permissionsForRole(?string $role): array
    {
        $canonicalRole = Rbac::normalizeRole($role);

        return config('hris.auth.role_permissions', [])[$canonicalRole] ?? [];
    }
}