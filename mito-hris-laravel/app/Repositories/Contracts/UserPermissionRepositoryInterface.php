<?php

namespace App\Repositories\Contracts;

interface UserPermissionRepositoryInterface
{
    public function mappingsForUser(string $email): array;

    public function upsert(string $email, string $permissionKey, bool $granted, string $grantedBy): bool;

    public function reassignEmail(string $fromEmail, string $toEmail): void;
}