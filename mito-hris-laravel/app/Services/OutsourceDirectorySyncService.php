<?php

namespace App\Services;

use App\DTOs\OutsourceEmployeeData;
use App\Repositories\Contracts\OutsourceEmployeeRepositoryInterface;
use Illuminate\Database\QueryException;

class OutsourceDirectorySyncService
{
    public function __construct(
        private readonly OutsourceEmployeeRepositoryInterface $outsourceRepo,
    ) {}

    /**
     * @param  list<array{outsource_id: string, full_name: string}>  $people
     * @return array{
     *   data: list<array{outsource_id: string, status: 'created'|'skipped'|'conflict'|'would_create'}>,
     *   meta: array{processed: int, created: int, skipped: int, conflict: int, would_create: int}
     * }
     */
    public function sync(array $people, bool $dryRun = false): array
    {
        $results = [];
        $counts = ['processed' => 0, 'created' => 0, 'skipped' => 0, 'conflict' => 0, 'would_create' => 0];

        foreach ($people as $person) {
            $outsourceId = strtoupper(trim($person['outsource_id']));
            $existing = $this->outsourceRepo->findById($outsourceId);

            if ($existing !== null) {
                $status = $this->normalizeName($existing->fullName) === $this->normalizeName($person['full_name'])
                    ? 'skipped'
                    : 'conflict';
            } elseif ($dryRun) {
                $status = 'would_create';
            } else {
                try {
                    $this->outsourceRepo->create(new OutsourceEmployeeData(
                        outsourceId: $outsourceId,
                        fullName: trim($person['full_name']),
                        createdBy: 'Attendance Person List',
                    ));
                    $status = 'created';
                } catch (QueryException $exception) {
                    if (! $this->isUniqueConstraintViolation($exception) || $this->outsourceRepo->findById($outsourceId) === null) {
                        throw $exception;
                    }

                    $status = 'skipped';
                }
            }

            $counts[$status]++;
            $counts['processed']++;
            $results[] = ['outsource_id' => $outsourceId, 'status' => $status];
        }

        return ['data' => $results, 'meta' => $counts];
    }

    private function normalizeName(?string $name): string
    {
        return mb_strtolower(preg_replace('/\s+/u', ' ', trim((string) $name)) ?? '');
    }

    private function isUniqueConstraintViolation(QueryException $exception): bool
    {
        $sqlState = (string) ($exception->errorInfo[0] ?? $exception->getCode());

        return in_array($sqlState, ['23000', '23505'], true);
    }
}
