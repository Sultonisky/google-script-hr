<?php

namespace App\Repositories\GoogleSheets;

use App\Repositories\Contracts\PermissionCatalogRepositoryInterface;
use App\Support\PermissionCatalog;
use App\Services\Google\GoogleSheetsService;

class PermissionCatalogSheetsRepository implements PermissionCatalogRepositoryInterface
{
    public function __construct(private GoogleSheetsService $sheets)
    {
    }

    public function all(): array
    {
        $rows = $this->sheets->getRowsAsAssoc(config('google.sheets.permissions', 'Permissions'));
        $activeRows = array_values(array_filter(
            $rows,
            fn (array $row): bool => strtolower(trim((string) ($row['Status'] ?? 'Active'))) === 'active'
        ));
        $knownKeys = array_fill_keys(array_map(
            fn (array $row): string => strtolower(trim((string) ($row['Permission Key'] ?? $row['key'] ?? ''))),
            $rows
        ), true);
        $catalogAdditions = array_filter(
            PermissionCatalog::all(),
            fn (array $permission): bool => !isset($knownKeys[strtolower(trim((string) ($permission['key'] ?? '')))])
        );

        return [...$activeRows, ...$catalogAdditions];
    }
}