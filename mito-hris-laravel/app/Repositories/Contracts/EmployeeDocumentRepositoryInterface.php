<?php

namespace App\Repositories\Contracts;

use Illuminate\Support\Collection;

interface EmployeeDocumentRepositoryInterface
{
    public function getAll(): Collection;

    public function getByEmployeeId(string $employeeId): Collection;

    public function getLatestByEmployeeAndType(string $employeeId, string $docCode): ?array;

    public function append(array $row): bool;

    /** Highest sequence number already used across all employees (0 if none). */
    public function maxSequence(): int;

    /** Fixed sequence for an employee if they already have any document (null if none). */
    public function sequenceForEmployee(string $employeeId): ?int;
}
