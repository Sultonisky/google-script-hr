<?php

namespace App\Repositories\Sheets;

use App\Repositories\Contracts\EmployeeDocumentRepositoryInterface;
use App\Services\Google\GoogleSheetsService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class EmployeeDocumentSheetsRepository implements EmployeeDocumentRepositoryInterface
{
    protected GoogleSheetsService $sheets;
    protected string $sheetName;

    public function __construct(GoogleSheetsService $sheets)
    {
        $this->sheets = $sheets;
        $this->sheetName = config('google.sheets.employee_documents', 'Employee_Documents');
    }

    public function getAll(): Collection
    {
        $this->ensureSheet();
        return collect($this->sheets->getRowsAsAssoc($this->sheetName));
    }

    public function getByEmployeeId(string $employeeId): Collection
    {
        $target = ltrim(trim($employeeId), "'");
        return $this->getAll()
            ->filter(fn (array $row) => ltrim(trim((string) ($row['Employee ID'] ?? '')), "'") === $target)
            ->values();
    }

    public function getLatestByEmployeeAndType(string $employeeId, string $docCode): ?array
    {
        $code = strtoupper(trim($docCode));
        $rows = $this->getByEmployeeId($employeeId)
            ->filter(fn (array $row) => strtoupper(trim((string) ($row['Doc Code'] ?? ''))) === $code)
            ->sortByDesc(fn (array $row) => (string) ($row['Issued At'] ?? $row['Created At'] ?? ''))
            ->values();

        return $rows->first();
    }

    public function append(array $row): bool
    {
        try {
            $this->ensureSheet();
            $headers = config('hris.schemas.Employee_Documents', []);
            $values = [];
            foreach ($headers as $header) {
                $values[] = (string) ($row[$header] ?? '');
            }
            $ok = $this->sheets->appendRow($this->sheetName, $values);
            if ($ok) {
                $this->sheets->clearCache($this->sheetName);
            }
            return $ok;
        } catch (\Throwable $e) {
            Log::error('EmployeeDocumentSheetsRepository::append failed: ' . $e->getMessage());
            return false;
        }
    }

    public function maxSequence(): int
    {
        return (int) $this->getAll()
            ->map(fn (array $row) => (int) ($row['Sequence'] ?? 0))
            ->max();
    }

    public function sequenceForEmployee(string $employeeId): ?int
    {
        $row = $this->getByEmployeeId($employeeId)->first();
        if (!$row) {
            return null;
        }
        $seq = (int) ($row['Sequence'] ?? 0);
        return $seq > 0 ? $seq : null;
    }

    protected function ensureSheet(): void
    {
        $headers = config('hris.schemas.Employee_Documents', []);
        $this->sheets->createSheetIfNotExists($this->sheetName);
        $this->sheets->ensureSheetHeaders($this->sheetName, $headers);
    }
}
