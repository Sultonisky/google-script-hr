<?php

namespace App\Repositories\Sheets;

use App\DTOs\EmployeeData;
use App\Repositories\Contracts\EmployeeRepositoryInterface;
use App\Services\EmployeeIdGenerator;
use App\Services\Google\GoogleSheetsService;
use Illuminate\Support\Collection;

class EmployeeSheetsRepository implements EmployeeRepositoryInterface
{
    protected GoogleSheetsService $sheets;
    protected EmployeeIdGenerator $idGenerator;
    protected string $sheetName;

    public function __construct(GoogleSheetsService $sheets, EmployeeIdGenerator $idGenerator)
    {
        $this->sheets = $sheets;
        $this->idGenerator = $idGenerator;
        $this->sheetName = config('google.sheets.employees', 'Employee');
    }

    public function getAll(array $filters = []): Collection
    {
        $rows = $this->sheets->getRowsAsAssoc($this->sheetName);
        $collection = collect($rows)->map(fn($row) => EmployeeData::fromSheetRow($row));

        if (!empty($filters['status'])) {
            $status = strtolower(trim($filters['status']));
            $collection = $collection->filter(function(EmployeeData $e) use ($status) {
                return strtolower(trim($e->statusEmployee ?? '')) === $status;
            });
        }

        if (!empty($filters['department'])) {
            $dept = strtolower(trim($filters['department']));
            $collection = $collection->filter(function(EmployeeData $e) use ($dept) {
                return strtolower(trim($e->department ?? '')) === $dept;
            });
        }

        if (!empty($filters['search'])) {
            $search = strtolower(trim($filters['search']));
            $collection = $collection->filter(function(EmployeeData $e) use ($search) {
                return str_contains(strtolower($e->fullName ?? ''), $search)
                    || str_contains(strtolower($e->employeeId ?? ''), $search)
                    || str_contains(strtolower($e->personalEmail ?? ''), $search)
                    || str_contains(strtolower($e->jobPosition ?? ''), $search)
                    || str_contains(strtolower($e->nikNpwp ?? ''), $search);
            });
        }

        return $collection->values();
    }

    public function findById(string $employeeId): ?EmployeeData
    {
        $row = $this->sheets->findRowBy($this->sheetName, 'Employee ID', $employeeId);
        return $row ? EmployeeData::fromSheetRow($row) : null;
    }

    public function findByNik(string $nik): ?EmployeeData
    {
        $rows = $this->sheets->getRowsAsAssoc($this->sheetName);
        $cleanNik = trim(ltrim($nik, "'"));
        foreach ($rows as $row) {
            $rowNik = trim(ltrim($row['NIK - NPWP 16 digit'] ?? '', "'"));
            if ($rowNik === $cleanNik) {
                return EmployeeData::fromSheetRow($row);
            }
        }
        return null;
    }

    public function create(EmployeeData $data): EmployeeData
    {
        if (empty($data->employeeId)) {
            $data->employeeId = $this->idGenerator->generate();
        }

        $rowValues = $data->toSheetRow();
        $this->sheets->appendRow($this->sheetName, $rowValues);

        return $data;
    }

    public function update(string $employeeId, array $attributes): bool
    {
        $existing = $this->findById($employeeId);
        if (!$existing || !$existing->rowNumber) {
            return false;
        }

        $allRows = $this->sheets->getRange($this->sheetName, "A{$existing->rowNumber}:ZZ{$existing->rowNumber}", false);
        if (empty($allRows)) return false;

        $headers = $this->sheets->getRange($this->sheetName, '1:1', true)[0] ?? [];
        $currentRow = $allRows[0];

        foreach ($attributes as $key => $val) {
            $colIdx = array_search($key, $headers);
            if ($colIdx !== false) {
                $currentRow[$colIdx] = $val;
            }
        }

        $updatedAtIdx = array_search('Updated At', $headers);
        if ($updatedAtIdx !== false) {
            $currentRow[$updatedAtIdx] = now()->timezone('Asia/Jakarta')->format('Y-m-d H:i:s');
        }

        return $this->sheets->updateRow($this->sheetName, $existing->rowNumber, $currentRow);
    }

    public function delete(string $employeeId): bool
    {
        return $this->update($employeeId, ['Status Employee' => 'Terminated']);
    }
}
