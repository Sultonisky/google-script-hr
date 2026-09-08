<?php

namespace App\Repositories\Local;

use App\Repositories\Contracts\UserPermissionRepositoryInterface;

class ArrayUserPermissionRepository implements UserPermissionRepositoryInterface
{
    private array $mappings = [];

    public function mappingsForUser(string $email): array
    {
        $email = strtolower(trim($email));
        return array_values(array_filter($this->mappings, fn (array $mapping): bool => $mapping['User Email'] === $email));
    }

    public function upsert(string $email, string $permissionKey, bool $granted, string $grantedBy): bool
    {
        $email = strtolower(trim($email));
        foreach ($this->mappings as &$mapping) {
            if ($mapping['User Email'] === $email && $mapping['Permission Key'] === $permissionKey) {
                $mapping['Granted'] = $granted;
                $mapping['Granted By'] = $grantedBy;
                $mapping['Updated At'] = now()->toDateTimeString();
                return true;
            }
        }

        $this->mappings[] = [
            'User Email' => $email,
            'Permission Key' => $permissionKey,
            'Granted' => $granted,
            'Granted By' => $grantedBy,
            'Created At' => now()->toDateTimeString(),
            'Updated At' => now()->toDateTimeString(),
        ];

        return true;
    }
}