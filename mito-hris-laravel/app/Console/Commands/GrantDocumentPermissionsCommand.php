<?php

namespace App\Console\Commands;

class GrantDocumentPermissionsCommand extends AbstractGrantSplitPermissionsCommand
{
    protected $signature = 'mito:permissions:grant-document-access {--dry-run : Report changes without writing User_Permissions}';

    protected $description = 'Grant view_documents/download_documents to users who previously accessed Document Tracking via view_employees/manage_employees';

    protected function legacySources(): array
    {
        return [
            'view_documents' => 'view_employees',
            'download_documents' => 'manage_employees',
        ];
    }

    protected function grantedBy(): string
    {
        return 'migration:document_permissions';
    }

    protected function label(): string
    {
        return 'Document';
    }
}
