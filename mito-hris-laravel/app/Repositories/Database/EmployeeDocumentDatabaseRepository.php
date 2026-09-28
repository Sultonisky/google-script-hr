<?php

namespace App\Repositories\Database;

use App\Models\EmployeeDocument;
use App\Repositories\Contracts\EmployeeDocumentRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class EmployeeDocumentDatabaseRepository implements EmployeeDocumentRepositoryInterface
{
    public function getAll(): Collection
    {
        return EmployeeDocument::query()
            ->orderBy('id')
            ->get()
            ->map(fn (EmployeeDocument $row) => $this->toSheetFormat($row))
            ->values();
    }

    public function getByEmployeeId(string $employeeId): Collection
    {
        $target = ltrim(trim($employeeId), "'");

        return EmployeeDocument::query()
            ->where('employee_id', $target)
            ->orderBy('id')
            ->get()
            ->map(fn (EmployeeDocument $row) => $this->toSheetFormat($row))
            ->values();
    }

    public function getLatestByEmployeeAndType(string $employeeId, string $docCode): ?array
    {
        $code = strtoupper(trim($docCode));
        $target = ltrim(trim($employeeId), "'");

        $row = EmployeeDocument::query()
            ->where('employee_id', $target)
            ->whereRaw('UPPER(TRIM(doc_code)) = ?', [$code])
            ->orderByDesc('issued_at')
            ->orderByDesc('id')
            ->first();

        return $row ? $this->toSheetFormat($row) : null;
    }

    public function append(array $row): bool
    {
        try {
            EmployeeDocument::create([
                'document_id' => (string) ($row['Document ID'] ?? ''),
                'employee_id' => ltrim(trim((string) ($row['Employee ID'] ?? '')), "'"),
                'sequence' => (int) ($row['Sequence'] ?? 0),
                'doc_type' => (string) ($row['Doc Type'] ?? ''),
                'doc_code' => strtoupper(trim((string) ($row['Doc Code'] ?? ''))),
                'nomor' => (string) ($row['Nomor'] ?? ''),
                'entity' => (string) ($row['Entity'] ?? ''),
                'issued_at' => (string) ($row['Issued At'] ?? ''),
                'issued_by' => (string) ($row['Issued By'] ?? ''),
                'reference' => (string) ($row['Reference'] ?? ''),
                'notes' => (string) ($row['Notes'] ?? ''),
            ]);

            return true;
        } catch (\Throwable $e) {
            Log::error('EmployeeDocumentDatabaseRepository::append failed: ' . $e->getMessage());

            return false;
        }
    }

    public function maxSequence(): int
    {
        return (int) (EmployeeDocument::query()->max('sequence') ?? 0);
    }

    public function sequenceForEmployee(string $employeeId): ?int
    {
        $target = ltrim(trim($employeeId), "'");
        $seq = EmployeeDocument::query()
            ->where('employee_id', $target)
            ->where('sequence', '>', 0)
            ->value('sequence');

        return $seq !== null ? (int) $seq : null;
    }

    /**
     * @return array<string, string>
     */
    private function toSheetFormat(EmployeeDocument $row): array
    {
        return [
            'Document ID' => (string) $row->document_id,
            'Employee ID' => (string) $row->employee_id,
            'Sequence' => (string) $row->sequence,
            'Doc Type' => (string) ($row->doc_type ?? ''),
            'Doc Code' => (string) ($row->doc_code ?? ''),
            'Nomor' => (string) ($row->nomor ?? ''),
            'Entity' => (string) ($row->entity ?? ''),
            'Issued At' => (string) ($row->issued_at ?? ''),
            'Issued By' => (string) ($row->issued_by ?? ''),
            'Reference' => (string) ($row->reference ?? ''),
            'Notes' => (string) ($row->notes ?? ''),
            'Created At' => optional($row->created_at)?->timezone('Asia/Jakarta')->format('Y-m-d H:i:s') ?? '',
        ];
    }
}
