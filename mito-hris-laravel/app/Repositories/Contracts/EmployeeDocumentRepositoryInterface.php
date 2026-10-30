<?php

namespace App\Repositories\Contracts;

use Illuminate\Support\Collection;

interface EmployeeDocumentRepositoryInterface
{
    public function getAll(): Collection;

    public function getByEmployeeId(string $employeeId): Collection;

    public function getLatestByEmployeeAndType(string $employeeId, string $docCode): ?array;

    public function append(array $row): ?string;

    /** Highest SK-family sequence already used across all employees (0 if none). Surat Peringatan is excluded. */
    public function maxSequence(): int;

    /** Fixed SK-family sequence for an employee (null if they have no non-SP document). */
    public function sequenceForEmployee(string $employeeId): ?int;
}
