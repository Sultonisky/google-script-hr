<?php

namespace App\Repositories\Sheets;

use App\DTOs\OutsourceEmployeeData;
use App\Repositories\Contracts\OutsourceEmployeeRepositoryInterface;
use App\Services\Google\GoogleSheetsService;
use App\Services\OutsourceIdGenerator;
use App\Support\OutsourceEmployeeAttributeMap;
use App\Support\OutsourceEmployeeFilter;
use Illuminate\Support\Collection;

class OutsourceEmployeeSheetsRepository implements OutsourceEmployeeRepositoryInterface
{
    protected string $sheetName;

    public function __construct(
        protected GoogleSheetsService $sheets,
        protected OutsourceIdGenerator $idGenerator
    ) {
        $this->sheetName = config('google.sheets.outsource_employees', 'Outsource_Employees');
    }

    public function getAll(array $filters = []): Collection
    {
        $this->ensureSheet();
        $rows = collect($this->sheets->getRowsAsAssoc($this->sheetName))
            ->map(fn (array $row) => OutsourceEmployeeData::fromSheetRow($row))
            ->filter(fn (OutsourceEmployeeData $e) => !empty($e->outsourceId));

        return OutsourceEmployeeFilter::apply($rows, $filters);
    }

    public function findById(string $outsourceId): ?OutsourceEmployeeData
    {
        $target = strtoupper(trim($outsourceId));

        return $this->getAll()->first(fn (OutsourceEmployeeData $e) => strtoupper((string) $e->outsourceId) === $target);
    }

    public function findByContact(?string $whatsappNumber, ?string $email): ?OutsourceEmployeeData
    {
        return OutsourceEmployeeFilter::findByContact($this->getAll(), $whatsappNumber, $email);
    }

    public function create(OutsourceEmployeeData $data): OutsourceEmployeeData
    {
        if (empty($data->outsourceId)) {
            return $this->idGenerator->allocate(
                function () {
                    $this->sheets->clearCache($this->sheetName);

                    return $this->getAll()->pluck('outsourceId');
                },
                function (string $id) use ($data) {
                    $data->outsourceId = $id;

                    return $this->append($data);
                }
            );
        }

        return $this->append($data);
    }

    private function append(OutsourceEmployeeData $data): OutsourceEmployeeData
    {
        $now = now()->timezone('Asia/Jakarta')->format('Y-m-d H:i:s');
        $data->createdBy = $data->createdBy ?? 'HR Administrator';
        $data->createdAt = $data->createdAt ?? $now;
        $data->updatedAt = $now;

        $this->sheets->appendRow($this->sheetName, $data->toSheetRow());
        $this->sheets->clearCache($this->sheetName);

        return $data;
    }

    public function update(string $outsourceId, array $attributes): bool
    {
        $existing = $this->findById($outsourceId);
        if (!$existing || !$existing->rowNumber) {
            return false;
        }

        $changes = OutsourceEmployeeAttributeMap::writableAttributes($attributes);
        if ($changes === []) {
            return false;
        }

        foreach ($changes as $property => $value) {
            $existing->{$property} = $value;
        }
        $existing->updatedAt = now()->timezone('Asia/Jakarta')->format('Y-m-d H:i:s');

        $success = $this->sheets->updateRow($this->sheetName, $existing->rowNumber, $existing->toSheetRow());
        if ($success) {
            $this->sheets->clearCache($this->sheetName);
        }

        return $success;
    }

    protected function ensureSheet(): void
    {
        $this->sheets->createSheetIfNotExists($this->sheetName);
        $this->sheets->ensureSheetHeaders($this->sheetName, OutsourceEmployeeAttributeMap::headers());
    }
}
