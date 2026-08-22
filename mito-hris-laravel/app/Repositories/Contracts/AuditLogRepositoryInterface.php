<?php

namespace App\Repositories\Contracts;

use Illuminate\Support\Collection;

interface AuditLogRepositoryInterface
{
    /**
     * Get all audit log entries, optionally filtered by ID.
     */
    public function getLogs(?string $recruitmentId = null): Collection;

    /**
     * Write an audit log entry.
     */
    public function log(string $recruitmentId, string $action, ?string $field = null, ?string $oldValue = null, ?string $newValue = null, ?string $user = null): bool;
}
