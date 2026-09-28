<?php

namespace App\Repositories\Database;

use App\DTOs\EmployeeData;
use App\Models\Employee;
use App\Repositories\Contracts\EmployeeRepositoryInterface;
use App\Services\EmployeeIdGenerator;
use App\Support\EmployeeAttributeMap;
use Illuminate\Support\Collection;

class EmployeeDatabaseRepository implements EmployeeRepositoryInterface
{
    public function __construct(
        protected EmployeeIdGenerator $idGenerator
    ) {}

    public function getAll(array $filters = []): Collection
    {
        $query = Employee::query();

        if (!empty($filters['status'])) {
            $query->whereRaw('LOWER(TRIM(status_employee)) = ?', [strtolower(trim((string) $filters['status']))]);
        }

        if (!empty($filters['department'])) {
            $query->whereRaw('LOWER(TRIM(department)) = ?', [strtolower(trim((string) $filters['department']))]);
        }

        if (!empty($filters['search'])) {
            $search = '%' . strtolower(trim((string) $filters['search'])) . '%';
            $query->where(function ($q) use ($search) {
                $q->whereRaw('LOWER(full_name) LIKE ?', [$search])
                    ->orWhereRaw('LOWER(employee_id) LIKE ?', [$search])
                    ->orWhereRaw('LOWER(COALESCE(personal_email, \'\')) LIKE ?', [$search])
                    ->orWhereRaw('LOWER(COALESCE(job_position, \'\')) LIKE ?', [$search])
                    ->orWhereRaw('LOWER(COALESCE(nik_npwp, \'\')) LIKE ?', [$search]);
            });
        }

        return $query->orderBy('employee_id')->get()
            ->map(fn (Employee $row) => EmployeeAttributeMap::toData($row))
            ->values();
    }

    public function findById(string $employeeId): ?EmployeeData
    {
        $row = Employee::where('employee_id', trim($employeeId))->first();

        return $row ? EmployeeAttributeMap::toData($row) : null;
    }

    public function findByNik(string $nik): ?EmployeeData
    {
        $cleanNik = ltrim(trim($nik), "'");
        $row = Employee::where('nik_npwp', $cleanNik)->first();

        return $row ? EmployeeAttributeMap::toData($row) : null;
    }

    public function create(EmployeeData $data): EmployeeData
    {
        if (empty($data->employeeId)) {
            $existing = Employee::query()->pluck('employee_id');
            $data->employeeId = $this->idGenerator->generate($data->joinDate, $existing);
        }

        $payload = EmployeeAttributeMap::toFillable($data);
        $model = Employee::create($payload);

        return EmployeeAttributeMap::toData($model->fresh());
    }

    public function update(string $employeeId, array $attributes): bool
    {
        $model = Employee::where('employee_id', trim($employeeId))->first();
        if (!$model) {
            return false;
        }

        $updates = EmployeeAttributeMap::sheetAttributesToColumns($attributes);
        if ($updates === []) {
            return false;
        }

        // Prefer Eloquent touch for Updated At when only that key was sent via sheet header.
        if (array_key_exists('updated_at', $updates) && is_string($updates['updated_at'])) {
            // Keep explicit timestamp string if services send Jakarta formatted values.
            $model->timestamps = false;
            $ok = $model->update($updates);
            $model->timestamps = true;

            return $ok;
        }

        return $model->update($updates);
    }

    public function delete(string $employeeId): bool
    {
        return $this->update($employeeId, ['Status Employee' => 'Terminated']);
    }
}
