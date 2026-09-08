<?php

namespace App\Support;

final class PermissionCatalog
{
    public static function all(): array
    {
        return [
            ['key' => 'assets.access', 'name' => 'Access Assets portal', 'description' => 'Enter the dedicated Assets portal.', 'group' => 'Assets'],
            ['key' => 'assets.view', 'name' => 'View assets', 'description' => 'View asset data.', 'group' => 'Assets'],
            ['key' => 'assets.create', 'name' => 'Create assets', 'description' => 'Create asset records.', 'group' => 'Assets'],
            ['key' => 'assets.update', 'name' => 'Update assets', 'description' => 'Update asset records.', 'group' => 'Assets'],
            ['key' => 'assets.delete', 'name' => 'Delete assets', 'description' => 'Delete asset records.', 'group' => 'Assets'],
            ['key' => 'assets.assign', 'name' => 'Assign assets', 'description' => 'Assign assets to employees.', 'group' => 'Assets'],
            ['key' => 'assets.return', 'name' => 'Return assets', 'description' => 'Record asset returns.', 'group' => 'Assets'],
            ['key' => 'assets.generate_code', 'name' => 'Generate asset codes', 'description' => 'Generate asset codes.', 'group' => 'Assets'],
            ['key' => 'certificates.access', 'name' => 'Access Certificates portal', 'description' => 'Enter the dedicated Certificates portal.', 'group' => 'Certificates'],
            ['key' => 'certificates.view', 'name' => 'View certificates', 'description' => 'View certification data.', 'group' => 'Certificates'],
            ['key' => 'certificates.create', 'name' => 'Create certificates', 'description' => 'Create certification records.', 'group' => 'Certificates'],
            ['key' => 'certificates.update', 'name' => 'Update certificates', 'description' => 'Update certification records.', 'group' => 'Certificates'],
            ['key' => 'certificates.delete', 'name' => 'Delete certificates', 'description' => 'Delete certification records.', 'group' => 'Certificates'],
            ['key' => 'certificates.generate_code', 'name' => 'Generate certificate codes', 'description' => 'Generate certification codes.', 'group' => 'Certificates'],
            ['key' => 'manage_recruitment', 'name' => 'Manage recruitment', 'description' => 'Manage recruitment workflows.', 'group' => 'Recruitment'],
            ['key' => 'manage_employees', 'name' => 'Manage employees', 'description' => 'Create and manage employee records.', 'group' => 'Employees'],
            ['key' => 'view_employees', 'name' => 'View employees', 'description' => 'View employee records.', 'group' => 'Employees'],
            ['key' => 'view_recruitment', 'name' => 'View recruitment', 'description' => 'View recruitment records.', 'group' => 'Recruitment'],
            ['key' => 'update_candidates', 'name' => 'Update candidates', 'description' => 'Update candidate status.', 'group' => 'Recruitment'],
            ['key' => 'create_offering', 'name' => 'Create offerings', 'description' => 'Create offering and contract documents.', 'group' => 'Recruitment'],
            ['key' => 'manage_hold_blacklist', 'name' => 'Manage hold and blacklist', 'description' => 'Manage candidate hold and blacklist actions.', 'group' => 'Recruitment'],
            ['key' => 'manage_probation', 'name' => 'Manage probation', 'description' => 'Manage probation evaluations.', 'group' => 'Probation'],
            ['key' => 'view_mpr', 'name' => 'View MPR', 'description' => 'View manpower requests.', 'group' => 'MPR'],
            ['key' => 'create_mpr', 'name' => 'Create MPR', 'description' => 'Create manpower requests.', 'group' => 'MPR'],
            ['key' => 'update_mpr', 'name' => 'Update MPR', 'description' => 'Update manpower requests.', 'group' => 'MPR'],
            ['key' => 'export_mpr', 'name' => 'Export MPR', 'description' => 'Export manpower requests.', 'group' => 'MPR'],
            ['key' => 'manage_settings', 'name' => 'Manage settings', 'description' => 'Manage system settings and users.', 'group' => 'Settings'],
            ['key' => 'manage_permissions', 'name' => 'Manage permissions', 'description' => 'Manage user-specific permissions.', 'group' => 'Settings'],
            ['key' => 'view_reports', 'name' => 'View reports', 'description' => 'View audit and report data.', 'group' => 'Reports'],
            ['key' => 'view_asset', 'name' => 'View legacy assets', 'description' => 'Compatibility permission for legacy HRIS Assets routes.', 'group' => 'Assets'],
            ['key' => 'edit_asset', 'name' => 'Edit legacy assets', 'description' => 'Compatibility permission for legacy HRIS Assets mutations.', 'group' => 'Assets'],
            ['key' => 'view_certification', 'name' => 'View legacy certificates', 'description' => 'Compatibility permission for legacy HRIS Certificates routes.', 'group' => 'Certificates'],
            ['key' => 'manage_certification', 'name' => 'Manage legacy certificates', 'description' => 'Compatibility permission for legacy HRIS Certificates mutations.', 'group' => 'Certificates'],
            ['key' => 'lookup_employee', 'name' => 'Lookup employees', 'description' => 'Use employee lookup for supported workflows.', 'group' => 'Employees'],
        ];
    }

    public static function keys(): array
    {
        return array_column(self::all(), 'key');
    }

    public static function find(string $key): ?array
    {
        foreach (self::all() as $permission) {
            if ($permission['key'] === $key) {
                return $permission;
            }
        }

        return null;
    }

    public static function grouped(): array
    {
        return collect(self::all())->groupBy('group')->map(fn ($permissions) => $permissions->values()->all())->all();
    }
}