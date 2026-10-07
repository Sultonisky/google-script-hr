<?php

namespace App\Support;

final class PermissionCatalog
{
    public static function all(): array
    {
        $permissions = [
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
            ['key' => 'rotate_employees', 'name' => 'Rotate employees', 'description' => 'Process employee rotation/mutation and generate SK Rotasi.', 'group' => 'Employees'],
            ['key' => 'offboard_employees', 'name' => 'Offboard employees', 'description' => 'Process employee offboarding/resign and generate SK Offboarding, Surat BPJS, and Paklaring.', 'group' => 'Employees'],
            ['key' => 'off_contract_employees', 'name' => 'Off-contract employees', 'description' => 'End employee contracts (off contract) and generate Paklaring.', 'group' => 'Employees'],
            ['key' => 'manage_warning_letters', 'name' => 'Manage warning letters', 'description' => 'Issue and download Surat Peringatan (SP-1/2/3) and Surat Penggilan Mangkir.', 'group' => 'Employees'],
            ['key' => 'view_recruitment', 'name' => 'View recruitment', 'description' => 'View recruitment records.', 'group' => 'Recruitment'],
            ['key' => 'update_candidates', 'name' => 'Update candidates', 'description' => 'Update candidate status.', 'group' => 'Recruitment'],
            ['key' => 'create_offering', 'name' => 'Create offerings', 'description' => 'Create offering and contract documents.', 'group' => 'Recruitment'],
            ['key' => 'manage_hold_blacklist', 'name' => 'Manage hold and blacklist', 'description' => 'Manage candidate hold and blacklist actions.', 'group' => 'Recruitment'],
            ['key' => 'manage_probation', 'name' => 'Manage probation', 'description' => 'Manage probation evaluations.', 'group' => 'Probation'],
            ['key' => 'view_contracts', 'name' => 'View contracts', 'description' => 'View contract tracking and upcoming contract end dates.', 'group' => 'Contracts'],
            ['key' => 'view_outsource', 'name' => 'View outsource', 'description' => 'View outsource employees in the Outsource menu.', 'group' => 'Outsource'],
            ['key' => 'manage_outsource', 'name' => 'Manage outsource', 'description' => 'Add and edit outsource employees and process PKWT TAD contracts.', 'group' => 'Outsource'],
            ['key' => 'view_outsource_compensation', 'name' => 'View outsource salary', 'description' => 'View Basic Salary and Incentive of outsource employees.', 'group' => 'Outsource'],
            ['key' => 'manage_outsource_compensation', 'name' => 'Manage outsource salary', 'description' => 'Edit Basic Salary and Incentive of outsource employees, including via Excel import.', 'group' => 'Outsource'],
            ['key' => 'view_outsource_payslip', 'name' => 'View outsource payslips', 'description' => 'View imported outsource payslips (HKE, salary, deductions, THP).', 'group' => 'Outsource'],
            ['key' => 'manage_outsource_payslip', 'name' => 'Manage outsource payslips', 'description' => 'Download the payslip template and import outsource payslips from Excel.', 'group' => 'Outsource'],
            ['key' => 'view_outsource_incentive', 'name' => 'View outsource incentives', 'description' => 'View imported outsource incentives per period.', 'group' => 'Outsource'],
            ['key' => 'manage_outsource_incentive', 'name' => 'Manage outsource incentives', 'description' => 'Download the incentive template and import outsource incentives from Excel.', 'group' => 'Outsource'],
            ['key' => 'view_documents', 'name' => 'View documents', 'description' => 'View issued employee documents in Document Tracking.', 'group' => 'Documents'],
            ['key' => 'download_documents', 'name' => 'Download documents', 'description' => 'Re-download archived employee document PDFs from Document Tracking.', 'group' => 'Documents'],
            ['key' => 'view_mpr', 'name' => 'View MPR', 'description' => 'View manpower requests.', 'group' => 'MPR'],
            ['key' => 'create_mpr', 'name' => 'Create MPR', 'description' => 'Create manpower requests.', 'group' => 'MPR'],
            ['key' => 'update_mpr', 'name' => 'Update MPR', 'description' => 'Update manpower requests.', 'group' => 'MPR'],
            ['key' => 'export_mpr', 'name' => 'Export MPR', 'description' => 'Export manpower requests.', 'group' => 'MPR'],
            ['key' => 'manage_settings', 'name' => 'Manage settings', 'description' => 'Manage system settings and users.', 'group' => 'Settings'],
            ['key' => 'manage_permissions', 'name' => 'Manage permissions', 'description' => 'Manage user-specific permissions.', 'group' => 'Settings'],
            ['key' => 'open_spreadsheet', 'name' => 'Open spreadsheet archive', 'description' => 'Open the Google Sheets archive (database mirror) from the dashboard quick actions.', 'group' => 'Settings'],
            ['key' => 'view_reports', 'name' => 'View reports', 'description' => 'View audit and report data.', 'group' => 'Reports'],
            ['key' => 'view_asset', 'name' => 'View legacy assets', 'description' => 'Compatibility permission for legacy HRIS Assets routes.', 'group' => 'Assets'],
            ['key' => 'edit_asset', 'name' => 'Edit legacy assets', 'description' => 'Compatibility permission for legacy HRIS Assets mutations.', 'group' => 'Assets'],
            ['key' => 'view_certification', 'name' => 'View legacy certificates', 'description' => 'Compatibility permission for legacy HRIS Certificates routes.', 'group' => 'Certificates'],
            ['key' => 'manage_certification', 'name' => 'Manage legacy certificates', 'description' => 'Compatibility permission for legacy HRIS Certificates mutations.', 'group' => 'Certificates'],
            ['key' => 'lookup_employee', 'name' => 'Lookup employees', 'description' => 'Use employee lookup for supported workflows.', 'group' => 'Employees'],
        ];

        $categories = [
            'building' => 'Building',
            'vehicle' => 'Vehicle',
            'office' => 'Office',
            'electronics' => 'Electronics',
        ];
        $actions = [
            'view' => ['View', 'view category asset records.'],
            'create' => ['Create', 'create category asset records.'],
            'update' => ['Update', 'update category asset records.'],
            'delete' => ['Dispose', 'dispose category assets.'],
            'assign' => ['Assign', 'assign category assets to employees.'],
            'return' => ['Return', 'record returns for category assets.'],
            'generate_code' => ['Generate codes for', 'generate category asset codes.'],
        ];

        foreach ($categories as $categoryKey => $categoryLabel) {
            foreach ($actions as $action => [$name, $description]) {
                $permissions[] = [
                    'key' => "assets.{$categoryKey}.{$action}",
                    'name' => "{$name} {$categoryLabel} assets",
                    'description' => ucfirst($description) . ' This permission applies only to this asset category.',
                    'group' => "Assets - {$categoryLabel}",
                ];
            }
        }

        return $permissions;
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