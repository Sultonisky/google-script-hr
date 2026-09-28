<?php

namespace App\Repositories\Contracts;

/**
 * Persistence boundary for kandidat_probation evaluation rows.
 * Sheets and Postgres implementations share this contract; callers never
 * talk to GoogleSheetsService directly.
 */
interface ProbationRepositoryInterface
{
    /**
     * @return array<int, array<string, string>>
     */
    public function getAllRows(): array;

    /**
     * Append one evaluation row keyed by canonical schema headers.
     *
     * @param  array<string, string>  $data
     */
    public function appendEvalRow(array $data): void;

    /**
     * Drop any backing-store cache so the next getAllRows() is fresh.
     */
    public function invalidateCache(): void;
}
