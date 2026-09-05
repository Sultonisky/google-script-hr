<?php

namespace App\Repositories\Local;

use App\DTOs\EmployeeData;
use App\Repositories\Contracts\EmployeeRepositoryInterface;
use Illuminate\Support\Collection;
use RuntimeException;

/**
 * LOCAL-DEVELOPMENT-ONLY employee source (additive + isolated).
 * Bound only in the `local` environment; production keeps
 * EmployeeSheetsRepository. IDs follow YYYYMMDD+NN (EmployeeIdGenerator).
 */
class LocalEmployeeRepository implements EmployeeRepositoryInterface
{
    private const ROWS = [
        ['employee_id' => '2018110201', 'full_name' => 'Rudi Hartono',   'division' => 'Engineering',    'department' => 'Mechanical Engineering',  'job_position' => 'Maintenance Engineer'],
        ['employee_id' => '2019031401', 'full_name' => 'Budi Santoso',   'division' => 'GA',              'department' => 'Facility Management',     'job_position' => 'General Affairs Staff'],
        ['employee_id' => '2019061201', 'full_name' => 'Agus Salim',     'division' => 'Engineering',    'department' => 'Maintenance Engineering', 'job_position' => 'Welder / Maintenance Technician'],
        ['employee_id' => '2020041501', 'full_name' => 'Rina Wulandari', 'division' => 'Finance',         'department' => 'Treasury',                'job_position' => 'Treasury Staff'],
        ['employee_id' => '2020051101', 'full_name' => 'Dewi Lestari',   'division' => 'Human Resources', 'department' => 'Learning & Development',  'job_position' => 'Learning & Development Officer'],
        ['employee_id' => '2020093001', 'full_name' => 'Maya Anggraini', 'division' => 'Legal',           'department' => 'Legal Compliance',        'job_position' => 'Legal Staff'],
        ['employee_id' => '2021010401', 'full_name' => 'Andi Pratama',   'division' => 'IT',              'department' => 'IT Infrastructure',       'job_position' => 'IT Infrastructure Engineer'],
        ['employee_id' => '2021020501', 'full_name' => 'Citra Lestari',  'division' => 'Human Resources', 'department' => 'Human Resources',         'job_position' => 'HR Generalist'],
        ['employee_id' => '2021072301', 'full_name' => 'Siti Rahayu',    'division' => 'Quality Control', 'department' => 'Quality Management System', 'job_position' => 'Quality Control Officer'],
        ['employee_id' => '2021092001', 'full_name' => 'Doni Kurniawan', 'division' => 'Sales',           'department' => 'General Trade',           'job_position' => 'Sales Executive'],
        ['employee_id' => '2021121001', 'full_name' => 'Joko Susilo',    'division' => 'GA',              'department' => 'Security',                'job_position' => 'Security Officer'],
        ['employee_id' => '2022041801', 'full_name' => 'Andi Wijaya',    'division' => 'Operations',      'department' => 'Logistics',               'job_position' => 'Logistics Staff'],
        ['employee_id' => '2022100701', 'full_name' => 'Hendra Gunawan', 'division' => 'Warehouse',       'department' => 'Warehouse Operations',    'job_position' => 'Warehouse Operator'],
    ];

    public function getAll(array $filters = []): Collection
    {
        $collection = collect(self::ROWS)->map(fn (array $row) => $this->toEmployee($row));

        if (!empty($filters['status'])) {
            $status = strtolower(trim($filters['status']));
            $collection = $collection->filter(function (EmployeeData $e) use ($status) {
                return strtolower(trim($e->statusEmployee ?? '')) === $status;
            });
        }

        if (!empty($filters['department'])) {
            $dept = strtolower(trim($filters['department']));
            $collection = $collection->filter(function (EmployeeData $e) use ($dept) {
                return strtolower(trim($e->department ?? '')) === $dept;
            });
        }

        if (!empty($filters['search'])) {
            $search = strtolower(trim($filters['search']));
            $collection = $collection->filter(function (EmployeeData $e) use ($search) {
                return str_contains(strtolower($e->fullName ?? ''), $search)
                    || str_contains(strtolower($e->employeeId ?? ''), $search)
                    || str_contains(strtolower($e->division ?? ''), $search)
                    || str_contains(strtolower($e->department ?? ''), $search)
                    || str_contains(strtolower($e->jobPosition ?? ''), $search);
            });
        }

        return $collection->values();
    }

    public function findById(string $employeeId): ?EmployeeData
    {
        foreach (self::ROWS as $row) {
            if (trim($row['employee_id']) === trim($employeeId)) {
                return $this->toEmployee($row);
            }
        }

        return null;
    }

    public function findByNik(string $nik): ?EmployeeData
    {
        return null;
    }

    public function create(EmployeeData $data): EmployeeData
    {
        throw new RuntimeException('Local dummy employee source is read-only.');
    }

    public function update(string $employeeId, array $attributes): bool
    {
        throw new RuntimeException('Local dummy employee source is read-only.');
    }

    public function delete(string $employeeId): bool
    {
        throw new RuntimeException('Local dummy employee source is read-only.');
    }

    private function toEmployee(array $row): EmployeeData
    {
        return new EmployeeData(
            employeeId: $row['employee_id'],
            fullName: $row['full_name'],
            division: $row['division'],
            department: $row['department'],
            jobPosition: $row['job_position'] ?? null,
            statusEmployee: 'Permanent (PKWTT)',
            branchName: 'PT Mahakarya Sukses Indonesia (MSI)',
        );
    }
}
