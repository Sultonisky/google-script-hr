<?php

namespace App\Repositories\Contracts;

use App\DTOs\OutsourceEmployeeData;
use Illuminate\Support\Collection;

interface OutsourceEmployeeRepositoryInterface
{
    /**
     * Filters: search (name/ID/job title/vendor/location), vendor.
     *
     * @return Collection<int, OutsourceEmployeeData>
     */
    public function getAll(array $filters = []): Collection;

    public function findById(string $outsourceId): ?OutsourceEmployeeData;

    /**
     * First record whose WhatsApp number or email matches (duplicate registration guard).
     */
    public function findByContact(?string $whatsappNumber, ?string $email): ?OutsourceEmployeeData;

    /**
     * Persist a new record; allocates Outsource ID when empty.
     */
    public function create(OutsourceEmployeeData $data): OutsourceEmployeeData;

    /**
     * @param  array<string, mixed>  $attributes  keyed by OutsourceEmployeeData property
     */
    public function update(string $outsourceId, array $attributes): bool;
}
