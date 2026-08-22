<?php

namespace App\Repositories\Contracts;

use App\DTOs\EmployeeData;
use Illuminate\Support\Collection;

interface EmployeeRepositoryInterface
{
    /**
     * Get all employees.
     * @return Collection<EmployeeData>
     */
    public function getAll(array $filters = []): Collection;

    /**
     * Find employee by Employee ID.
     */
    public function findById(string $employeeId): ?EmployeeData;

    /**
     * Find employee by NIK / NPWP.
     */
    public function findByNik(string $nik): ?EmployeeData;

    /**
     * Create a new employee.
     */
    public function create(EmployeeData $data): EmployeeData;

    /**
     * Update existing employee.
     */
    public function update(string $employeeId, array $attributes): bool;

    /**
     * Delete / mark employee offboarded.
     */
    public function delete(string $employeeId): bool;
}
