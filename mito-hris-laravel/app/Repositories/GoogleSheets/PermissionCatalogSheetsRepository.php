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
        if (empty($rows)) {
            return PermissionCatalog::all();
        }

        return array_values(array_filter($rows, fn (array $row): bool => strtolower(trim((string) ($row['Status'] ?? 'Active'))) === 'active'));
    }
}