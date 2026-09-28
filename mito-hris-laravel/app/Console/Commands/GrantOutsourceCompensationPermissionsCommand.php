<?php

namespace App\Console\Commands;

class GrantOutsourceCompensationPermissionsCommand extends AbstractGrantSplitPermissionsCommand
{
    protected $signature = 'mito:permissions:grant-outsource-compensation {--dry-run : Report changes without writing User_Permissions}';

    protected $description = 'Grant view/manage_outsource_compensation to users who previously saw Basic Salary and Incentive via view_outsource/manage_outsource';

    protected function legacySources(): array
    {
        return [
            'view_outsource_compensation' => 'view_outsource',
            'manage_outsource_compensation' => 'manage_outsource',
        ];
    }

    protected function grantedBy(): string
    {
        return 'migration:outsource_compensation_permissions';
    }

    protected function label(): string
    {
        return 'Outsource compensation';
    }
}
