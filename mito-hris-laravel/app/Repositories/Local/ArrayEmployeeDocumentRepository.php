<?php

namespace App\Repositories\Local;

use App\Repositories\Contracts\EmployeeDocumentRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * In-memory Employee_Documents store for tests / non-Sheets environments.
 */
class ArrayEmployeeDocumentRepository implements EmployeeDocumentRepositoryInterface
{
    /** @var array<int, array<string, string>> */
    private array $rows = [];

    public function getAll(): Collection
    {
        return collect($this->rows);
    }

    public function getByEmployeeId(string $employeeId): Collection
    {
        $target = ltrim(trim($employeeId), "'");
        return collect($this->rows)
            ->filter(fn (array $row) => ltrim(trim((string) ($row['Employee ID'] ?? '')), "'") === $target)
            ->values();
    }

    public function getLatestByEmployeeAndType(string $employeeId, string $docCode): ?array
    {
        $code = strtoupper(trim($docCode));
        return $this->getByEmployeeId($employeeId)
            ->filter(fn (array $row) => strtoupper(trim((string) ($row['Doc Code'] ?? ''))) === $code)
            ->sortByDesc(fn (array $row) => (string) ($row['Issued At'] ?? ''))
            ->first();
    }

    public function append(array $row): bool
    {
        $this->rows[] = $row;
        return true;
    }

    public function maxSequence(): int
    {
        return (int) collect($this->rows)->map(fn (array $row) => (int) ($row['Sequence'] ?? 0))->max();
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

    public function reset(): void
    {
        $this->rows = [];
    }
}
