<?php

namespace App\Repositories\Contracts;

use Illuminate\Support\Collection;

interface AuditLogRepositoryInterface
{
    /**
     * Get all audit log entries, optionally filtered by entity ID.
     */
    public function getLogs(?string $entityId = null): Collection;

    /**
     * Write an audit log entry.
     */
    public function log(
        string $entityType,
        ?string $entityId,
        string $action,
        ?string $field = null,
        mixed $oldValue = null,
        mixed $newValue = null,
        ?string $user = null,
        string $source = 'System'
    ): bool;
}
