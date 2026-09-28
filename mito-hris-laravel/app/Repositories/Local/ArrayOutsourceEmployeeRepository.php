<?php

namespace App\Repositories\Local;

use App\DTOs\OutsourceEmployeeData;
use App\Repositories\Contracts\OutsourceEmployeeRepositoryInterface;
use App\Services\OutsourceIdGenerator;
use App\Support\OutsourceEmployeeAttributeMap;
use App\Support\OutsourceEmployeeFilter;
use Illuminate\Support\Collection;

/**
 * In-memory outsource store for tests / offline (local driver).
 */
class ArrayOutsourceEmployeeRepository implements OutsourceEmployeeRepositoryInterface
{
    /** @var array<string, OutsourceEmployeeData> keyed by Outsource ID */
    private array $rows = [];

    public function __construct(
        protected OutsourceIdGenerator $idGenerator
    ) {}

    public function getAll(array $filters = []): Collection
    {
        return OutsourceEmployeeFilter::apply(collect(array_values($this->rows)), $filters);
    }

    public function findById(string $outsourceId): ?OutsourceEmployeeData
    {
        return $this->rows[strtoupper(trim($outsourceId))] ?? null;
    }

    public function findByContact(?string $whatsappNumber, ?string $email): ?OutsourceEmployeeData
    {
        return OutsourceEmployeeFilter::findByContact($this->getAll(), $whatsappNumber, $email);
    }

    public function create(OutsourceEmployeeData $data): OutsourceEmployeeData
    {
        if (empty($data->outsourceId)) {
            $data->outsourceId = $this->idGenerator->generate(array_keys($this->rows));
        }

        $now = now()->timezone('Asia/Jakarta')->format('Y-m-d H:i:s');
        $data->createdBy = $data->createdBy ?? 'HR Administrator';
        $data->createdAt = $data->createdAt ?? $now;
        $data->updatedAt = $now;
        $this->rows[strtoupper($data->outsourceId)] = $data;

        return $data;
    }

    public function update(string $outsourceId, array $attributes): bool
    {
        $existing = $this->findById($outsourceId);
        $changes = OutsourceEmployeeAttributeMap::writableAttributes($attributes);
        if (!$existing || $changes === []) {
            return false;
        }

        foreach ($changes as $property => $value) {
            $existing->{$property} = $value;
        }
        $existing->updatedAt = now()->timezone('Asia/Jakarta')->format('Y-m-d H:i:s');

        return true;
    }
}
