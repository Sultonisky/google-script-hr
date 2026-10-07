<?php

namespace App\Repositories\Database;

use App\Models\Permission;
use App\Repositories\Contracts\PermissionCatalogRepositoryInterface;
use App\Support\PermissionCatalog;

class PermissionCatalogDatabaseRepository implements PermissionCatalogRepositoryInterface
{
    public function all(): array
    {
        $rows = Permission::query()
            ->orderBy('group')
            ->orderBy('permission_key')
            ->get();

        $activeRows = $rows
            ->filter(fn (Permission $row): bool => strtolower(trim((string) $row->status)) === 'active')
            ->map(fn (Permission $row) => [
            'Permission Key' => (string) $row->permission_key,
            'Name' => (string) ($row->name ?? ''),
            'Description' => (string) ($row->description ?? ''),
            'Group' => (string) ($row->group ?? ''),
            'Status' => (string) ($row->status ?? 'active'),
            'Created At' => optional($row->created_at)?->timezone('Asia/Jakarta')->format('Y-m-d H:i:s') ?? '',
            'Updated At' => optional($row->updated_at)?->timezone('Asia/Jakarta')->format('Y-m-d H:i:s') ?? '',
        ])->values()->all();

        $knownKeys = array_fill_keys(
            $rows->map(fn (Permission $row): string => strtolower(trim((string) $row->permission_key)))->all(),
            true
        );
        $catalogAdditions = array_filter(
            PermissionCatalog::all(),
            fn (array $permission): bool => !isset($knownKeys[strtolower(trim((string) ($permission['key'] ?? '')))])
        );

        return [...$activeRows, ...$catalogAdditions];
    }

    /**
     * Idempotent seed from static PermissionCatalog (append missing keys only).
     *
     * @return int number of inserted rows
     */
    public function syncFromStaticCatalog(): int
    {
        $existing = Permission::query()->pluck('permission_key')->map(fn ($k) => (string) $k)->all();
        $existingLookup = array_fill_keys($existing, true);
        $inserted = 0;

        foreach (PermissionCatalog::all() as $entry) {
            $key = trim((string) ($entry['key'] ?? ''));
            if ($key === '' || isset($existingLookup[$key])) {
                continue;
            }

            Permission::create([
                'permission_key' => $key,
                'name' => (string) ($entry['name'] ?? ''),
                'description' => (string) ($entry['description'] ?? ''),
                'group' => (string) ($entry['group'] ?? ''),
                'status' => 'active',
            ]);
            $inserted++;
            $existingLookup[$key] = true;
        }

        return $inserted;
    }
}
