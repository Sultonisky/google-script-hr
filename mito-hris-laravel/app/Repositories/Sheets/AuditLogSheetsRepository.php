<?php

namespace App\Repositories\Sheets;

use App\Repositories\Contracts\AuditLogRepositoryInterface;
use App\Services\Google\GoogleSheetsService;
use Illuminate\Support\Collection;

class AuditLogSheetsRepository implements AuditLogRepositoryInterface
{
    protected GoogleSheetsService $sheets;
    protected string $sheetName;

    public function __construct(GoogleSheetsService $sheets)
    {
        $this->sheets = $sheets;
        $this->sheetName = config('google.sheets.audit_log', 'Audit_Log');
    }

    public function getLogs(?string $recruitmentId = null): Collection
    {
        $rows = $this->sheets->getRowsAsAssoc($this->sheetName);
        $collection = collect($rows);

        if ($recruitmentId !== null) {
            $target = trim($recruitmentId);
            $collection = $collection->filter(function($row) use ($target) {
                return trim($row['Recruitment ID'] ?? '') === $target;
            });
        }

        // Return latest first
        return $collection->reverse()->values();
    }

    public function log(string $recruitmentId, string $action, ?string $field = null, ?string $oldValue = null, ?string $newValue = null, ?string $user = null): bool
    {
        $user = $user ?: 'HR Administrator';
        $timestamp = now()->timezone('Asia/Jakarta')->format('Y-m-d H:i:s');

        $rowValues = [
            $recruitmentId,
            $action,
            $field ?? '',
            $oldValue ?? '',
            $newValue ?? '',
            $user,
            $timestamp,
        ];

        return $this->sheets->appendRow($this->sheetName, $rowValues);
    }
}
