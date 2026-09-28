<?php

namespace App\Repositories\Database;

use App\DTOs\OutsourceEmployeeData;
use App\Models\OutsourceEmployee;
use App\Repositories\Contracts\OutsourceEmployeeRepositoryInterface;
use App\Services\OutsourceIdGenerator;
use App\Support\OutsourceEmployeeAttributeMap;
use App\Support\OutsourceEmployeeFilter;
use Illuminate\Support\Collection;

class OutsourceEmployeeDatabaseRepository implements OutsourceEmployeeRepositoryInterface
{
    public function __construct(
        protected OutsourceIdGenerator $idGenerator
    ) {}

    public function getAll(array $filters = []): Collection
    {
        $rows = OutsourceEmployee::query()->orderBy('outsource_id')->get()
            ->map(fn (OutsourceEmployee $row) => OutsourceEmployeeAttributeMap::toData($row));

        return OutsourceEmployeeFilter::apply($rows, $filters);
    }

    public function findById(string $outsourceId): ?OutsourceEmployeeData
    {
        $row = OutsourceEmployee::where('outsource_id', strtoupper(trim($outsourceId)))->first();

        return $row ? OutsourceEmployeeAttributeMap::toData($row) : null;
    }

    public function findByContact(?string $whatsappNumber, ?string $email): ?OutsourceEmployeeData
    {
        return OutsourceEmployeeFilter::findByContact($this->getAll(), $whatsappNumber, $email);
    }

    public function create(OutsourceEmployeeData $data): OutsourceEmployeeData
    {
        if (empty($data->outsourceId)) {
            return $this->idGenerator->allocate(
                fn () => OutsourceEmployee::query()->pluck('outsource_id'),
                function (string $id) use ($data) {
                    $data->outsourceId = $id;

                    return $this->insert($data);
                }
            );
        }

        return $this->insert($data);
    }

    private function insert(OutsourceEmployeeData $data): OutsourceEmployeeData
    {
        $data->outsourceId = strtoupper(trim($data->outsourceId));

        $model = OutsourceEmployee::create(OutsourceEmployeeAttributeMap::toFillable($data));

        return OutsourceEmployeeAttributeMap::toData($model->fresh());
    }

    public function update(string $outsourceId, array $attributes): bool
    {
        $model = OutsourceEmployee::where('outsource_id', strtoupper(trim($outsourceId)))->first();
        if (!$model) {
            return false;
        }

        $updates = OutsourceEmployeeAttributeMap::attributesToColumns($attributes);
        if ($updates === []) {
            return false;
        }

        return $model->update($updates);
    }
}
