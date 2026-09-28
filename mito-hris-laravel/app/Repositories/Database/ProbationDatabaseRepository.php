<?php

namespace App\Repositories\Database;

use App\Models\ProbationEvaluation;
use App\Repositories\Contracts\ProbationRepositoryInterface;
use App\Support\ProbationAttributeMap;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class ProbationDatabaseRepository implements ProbationRepositoryInterface
{
    public function getAllRows(): array
    {
        return ProbationEvaluation::query()
            ->orderBy('id')
            ->get()
            ->map(fn (ProbationEvaluation $row) => ProbationAttributeMap::toSheetRow($row))
            ->all();
    }

    public function appendEvalRow(array $data): void
    {
        $headers = config('hris.schemas.kandidat_probation', []);
        if (!is_array($headers) || $headers === [] || count($headers) !== count(array_unique($headers))) {
            throw new RuntimeException('Schema kandidat_probation tidak valid atau memiliki header duplikat.');
        }

        $employeeId = trim((string) ($data['Employee ID'] ?? ''));
        if ($employeeId === '') {
            throw new RuntimeException('Employee ID wajib diisi untuk probation evaluation.');
        }

        try {
            ProbationEvaluation::create(ProbationAttributeMap::toFillable($data));
        } catch (\Throwable $e) {
            Log::error('ProbationDatabaseRepository::appendEvalRow failed: ' . $e->getMessage());
            throw $e;
        }
    }

    public function invalidateCache(): void
    {
        // Eloquent has no Sheets-style version cache.
    }
}
