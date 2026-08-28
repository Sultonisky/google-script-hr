<?php

namespace App\Repositories\Contracts;

use App\DTOs\MprData;
use Illuminate\Support\Collection;

interface MprRepositoryInterface
{
    /**
     * Get all MPR records with optional filters (search, department, status, etc.).
     * @return Collection<MprData>
     */
    public function getAll(array $filters = []): Collection;

    /**
     * Get all MPR records created by or belonging to a specific Manager email.
     * @return Collection<MprData>
     */
    public function getAllForManager(string $email, array $filters = []): Collection;

    /**
     * Find an MPR record by its MPR Number.
     */
    public function findByMprNumber(string $mprNumber): ?MprData;

    /**
     * Find an MPR record by ID or MPR Number.
     */
    public function findById(string $id): ?MprData;

    /**
     * Create and persist a new MPR record.
     */
    public function create(MprData $data): MprData;

    public function update(string $mprNumber, MprData $data): MprData;
}
