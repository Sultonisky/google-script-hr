<?php

namespace App\Console\Commands;

class GrantOutsourcePermissionsCommand extends AbstractGrantSplitPermissionsCommand
{
    protected $signature = 'mito:permissions:grant-outsource-access {--dry-run : Report changes without writing User_Permissions}';

    protected $description = 'Grant view_outsource/manage_outsource to users who previously accessed the Outsource menu via view_employees/manage_employees';

    protected function legacySources(): array
    {
        return [
            'view_outsource' => 'view_employees',
            'manage_outsource' => 'manage_employees',
        ];
    }

    protected function grantedBy(): string
    {
        return 'migration:outsource_permissions';
    }

    protected function label(): string
    {
        return 'Outsource';
    }
}
