<?php

namespace App\Services;

use App\Enums\SkDocumentType;
use App\Repositories\Contracts\EmployeeDocumentRepositoryInterface;
use App\Repositories\Contracts\EmployeeRepositoryInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Issues official HRIS document numbers with a fixed per-employee sequence.
 *
 * Format: {seq}/{CODE}/{ENTITY}/{ROMAN}/{YEAR}
 * Sequence is lifetime-stable per employee (001 sticks forever); only CODE changes.
 * Employee.Nomor SK always stores the latest issued number.
 * Full history lives in the Employee_Documents sheet.
 */
class SkNumberService
{
    private const ROMAN_MONTHS = ['I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'];

    public function __construct(
        private EmployeeDocumentRepositoryInterface $documents,
        private EmployeeRepositoryInterface $employees,
    ) {}

    /**
     * Issue a new document number, append history, and update Employee.Nomor SK.
     *
     * @return array{nomor:string, sequence:int, doc_code:string, entity:string, document_id:string}
     */
    public function issue(
        string $employeeId,
        SkDocumentType $type,
        string $branchName = '',
        ?string $issuedBy = null,
        string $reference = '',
        string $notes = '',
        ?Carbon $issuedAt = null,
    ): array {
        $employeeId = ltrim(trim($employeeId), "'");
        if ($employeeId === '') {
            throw new RuntimeException('Employee ID wajib diisi untuk penerbitan nomor SK.');
        }

        $now = ($issuedAt ?? now())->timezone('Asia/Jakarta');
        $issuedBy = $issuedBy ?: 'HR Administrator';
        $entity = $this->resolveEntityCode($branchName);
        $sequence = $this->resolveFixedSequence($employeeId);
        $nomor = $this->format($sequence, $type->value, $entity, $now);
        $documentId = sprintf(
            'DOC-%s-%03d-%s',
            $now->format('Ymd'),
            $sequence,
            $type->value
        );

        $row = [
            'Document ID' => $documentId,
            'Employee ID' => $employeeId,
            'Sequence' => (string) $sequence,
            'Doc Type' => $type->label(),
            'Doc Code' => $type->value,
            'Nomor' => $nomor,
            'Entity' => $entity,
            'Issued At' => $now->format('Y-m-d H:i:s'),
            'Issued By' => $issuedBy,
            'Reference' => $reference,
            'Notes' => $notes,
            'Created At' => $now->format('Y-m-d H:i:s'),
        ];

        if (!$this->documents->append($row)) {
            throw new RuntimeException('Gagal menyimpan riwayat dokumen SK ke Employee_Documents.');
        }

        $updated = $this->employees->update($employeeId, [
            'Nomor SK' => $nomor,
            // Kontrak PKWT/TAD: keep Contract Number in sync with the issued nomor
            // (same fixed seq family as SK documents).
            ...($type->isContract() ? ['Contract Number' => $nomor] : []),
            'Updated At' => $now->format('Y-m-d H:i:s'),
        ]);

        if (!$updated) {
            Log::warning('SkNumberService: nomor diterbitkan tapi gagal update Employee.Nomor SK', [
                'employee_id' => $employeeId,
                'nomor' => $nomor,
            ]);
        }

        return [
            'nomor' => $nomor,
            'sequence' => $sequence,
            'doc_code' => $type->value,
            'entity' => $entity,
            'document_id' => $documentId,
        ];
    }

    /**
     * Resolve a stored number for PDF export (prefer history by type, then latest Nomor SK).
     * Does not generate a new number.
     */
    public function resolveForPdf(string $employeeId, SkDocumentType $type, string $fallback = ''): string
    {
        $employeeId = ltrim(trim($employeeId), "'");
        $latestOfType = $this->documents->getLatestByEmployeeAndType($employeeId, $type->value);
        if ($latestOfType && !empty($latestOfType['Nomor'])) {
            return trim((string) $latestOfType['Nomor']);
        }

        $fallback = trim($fallback);
        if ($fallback !== '') {
            return $fallback;
        }

        $employee = $this->employees->findById($employeeId);
        return trim((string) ($employee?->nomorSk ?? ''));
    }

    public function historyForEmployee(string $employeeId): \Illuminate\Support\Collection
    {
        return $this->documents->getByEmployeeId($employeeId)
            ->sortByDesc(fn (array $row) => (string) ($row['Issued At'] ?? $row['Created At'] ?? ''))
            ->values();
    }

    public function format(int $sequence, string $code, string $entity, Carbon $at): string
    {
        $roman = self::ROMAN_MONTHS[max(0, min(11, $at->month - 1))];

        return sprintf('%03d/%s/%s/%s/%d', $sequence, strtoupper($code), $entity, $roman, $at->year);
    }

    public function resolveEntityCode(string $branchName): string
    {
        $b = strtolower(trim($branchName));
        if (str_contains($b, 'stein')) {
            return 'SPI';
        }
        if (str_contains($b, 'injeksi')) {
            return 'PII';
        }
        if (str_contains($b, 'mitra') || str_contains($b, 'elektro')) {
            return 'MEP';
        }

        return 'MSI';
    }

    /**
     * Fixed lifetime sequence for the employee.
     * Reuses existing Employee_Documents seq; otherwise allocates maxSequence()+1 under lock.
     */
    private function resolveFixedSequence(string $employeeId): int
    {
        $existing = $this->documents->sequenceForEmployee($employeeId);
        if ($existing !== null && $existing > 0) {
            return $existing;
        }

        $lock = Cache::lock('lock_sk_employee_seq_alloc', 15);
        try {
            $lock->block(15);

            // Re-check inside lock in case another request just assigned this employee.
            $existing = $this->documents->sequenceForEmployee($employeeId);
            if ($existing !== null && $existing > 0) {
                return $existing;
            }

            return max(1, $this->documents->maxSequence() + 1);
        } finally {
            optional($lock)->release();
        }
    }
}
