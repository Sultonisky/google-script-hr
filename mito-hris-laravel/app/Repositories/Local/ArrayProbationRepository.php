<?php

namespace App\Repositories\Local;

use App\Repositories\Contracts\ProbationRepositoryInterface;
use RuntimeException;

/**
 * In-memory probation evaluation store for tests / non-Sheets environments.
 */
class ArrayProbationRepository implements ProbationRepositoryInterface
{
    /** @var array<int, array<string, string>> */
    private array $rows = [];

    public function getAllRows(): array
    {
        return $this->rows;
    }

    public function appendEvalRow(array $data): void
    {
        $headers = config('hris.schemas.kandidat_probation', []);
        if (!is_array($headers) || $headers === []) {
            throw new RuntimeException('Schema kandidat_probation tidak valid.');
        }

        $normalized = [];
        foreach ($headers as $header) {
            $normalized[$header] = (string) ($data[$header] ?? '');
        }
        $this->rows[] = $normalized;
    }

    public function invalidateCache(): void
    {
        // No persistent cache for in-memory store.
    }

    public function reset(): void
    {
        $this->rows = [];
    }

    /**
     * Seed rows for tests (assoc keyed by schema headers).
     *
     * @param  array<int, array<string, string>>  $rows
     */
    public function seed(array $rows): void
    {
        $this->rows = array_values($rows);
    }
}
