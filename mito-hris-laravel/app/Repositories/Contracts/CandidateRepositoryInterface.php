<?php

namespace App\Repositories\Contracts;

use App\DTOs\CandidateData;
use Illuminate\Support\Collection;

interface CandidateRepositoryInterface
{
    /**
     * Get all candidates.
     * @return Collection<CandidateData>
     */
    public function getAll(array $filters = []): Collection;

    /**
     * Find candidate by Recruitment ID.
     */
    public function findById(string $recruitmentId): ?CandidateData;

    /**
     * Find candidate by NIK across recruitment sheets.
     */
    public function findByNik(string $nik, bool $useCache = true): ?CandidateData;

    /**
     * Create a new candidate.
     */
    public function create(CandidateData $data): CandidateData;

    /**
     * Update an existing candidate.
     */
    public function update(string $recruitmentId, array $attributes): bool;

    /**
     * Update candidate status.
     */
    public function updateStatus(string $recruitmentId, string $status, ?string $notes = null, array $extra = []): bool;

    /**
     * Delete candidate (or mark status deleted).
     */
    public function delete(string $recruitmentId): bool;

    public function getAllFromSheets(array $sheetKeys = []): Collection;

    public function moveToSheet(string $recruitmentId, string $targetSheetKey, array $extraData = []): bool;

    public function deleteFromSheet(string $recruitmentId, string $sheetKey): bool;
}
