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

}
