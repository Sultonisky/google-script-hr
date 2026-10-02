<?php

namespace App\Console\Commands;

class GrantEmployeeActionPermissionsCommand extends AbstractGrantSplitPermissionsCommand
{
    protected $signature = 'mito:permissions:grant-employee-actions {--dry-run : Report changes without writing User_Permissions}';

    protected $description = 'Grant rotation/offboarding/off-contract/warning-letter permissions to users who previously performed them via manage_employees';

    protected function legacySources(): array
    {
        return [
            'rotate_employees' => 'manage_employees',
            'offboard_employees' => 'manage_employees',
            'off_contract_employees' => 'manage_employees',
            'manage_warning_letters' => 'manage_employees',
        ];
    }

    protected function grantedBy(): string
    {
        return 'migration:employee_action_permissions';
    }

    protected function label(): string
    {
        return 'Employee action';
    }
}
