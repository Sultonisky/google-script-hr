<?php

namespace App\Services;

use App\Enums\SkDocumentType;
use App\Enums\WarningLetterLevel;
use App\Repositories\Contracts\EmployeeDocumentRepositoryInterface;
use App\Repositories\Contracts\EmployeeRepositoryInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Issues official HRIS document numbers with a fixed per-employee sequence.
 * Surat Peringatan has its own sequence and encodes the level as SP-1, SP-2, SP-3, or SP-F.
 * Each SP letter takes the next free SP number. The number is not kept for one employee.
 *
 * Format: {seq}/{CODE}/{ENTITY}/{ROMAN}/{YEAR}
 * SK sequence is lifetime-stable per employee (001 sticks forever); only CODE changes.
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
     * Issue a new document number, append history, and update Employee.Nomor SK
     * (unless the type opts out, e.g. Surat Peringatan).
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
        ?string $docTypeLabel = null,
        ?string $numberSuffix = null,
        int $points = 0,
    ): array {
        if (!$type->isNumbered()) {
            throw new RuntimeException("{$type->label()} tidak memakai nomor; gunakan record().");
        }

        $employeeId = ltrim(trim($employeeId), "'");
        if ($employeeId === '') {
            throw new RuntimeException('Employee ID wajib diisi untuk penerbitan nomor SK.');
        }

        $now = ($issuedAt ?? now())->timezone('Asia/Jakarta');
        $entity = $this->resolveEntityCode($branchName);
        $sequence = $type === SkDocumentType::SURAT_PERINGATAN
            ? $this->resolveWarningLetterSequence()
            : $this->resolveFixedSequence($employeeId);
        $code = $type === SkDocumentType::SURAT_PERINGATAN
            ? (WarningLetterLevel::tryFrom(trim($reference))?->numberCode() ?? $type->value)
            : $type->value;
        $nomor = $this->format($sequence, $code, $entity, $now, $numberSuffix);
        $documentId = $this->appendHistory($employeeId, $type, $sequence, $nomor, $entity, $now, $issuedBy, $reference, $notes, $docTypeLabel, $points);

        if (!$type->updatesEmployeeNomorSk()) {
            return [
                'nomor' => $nomor,
                'sequence' => $sequence,
                'doc_code' => $type->value,
                'entity' => $entity,
                'document_id' => $documentId,
            ];
        }

        $updated = $this->employees->update($employeeId, [
            'Nomor SK' => $nomor,
            // Kontrak PKWT (incl. TAD): keep Contract Number in sync with the issued nomor
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
     * Track an unnumbered document (e.g. Surat BPJS) in Employee_Documents.
     * Nomor stays empty and Employee.Nomor SK is not touched.
     *
     * @return array{document_id:string, sequence:int, doc_code:string, entity:string}
     */
    public function record(
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
            throw new RuntimeException('Employee ID wajib diisi untuk pencatatan dokumen.');
        }

        $now = ($issuedAt ?? now())->timezone('Asia/Jakarta');
        $entity = $this->resolveEntityCode($branchName);
        $sequence = $this->resolveFixedSequence($employeeId);
        $documentId = $this->appendHistory($employeeId, $type, $sequence, '', $entity, $now, $issuedBy, $reference, $notes);

        return [
            'document_id' => $documentId,
            'sequence' => $sequence,
            'doc_code' => $type->value,
            'entity' => $entity,
        ];
    }

    private function appendHistory(
        string $employeeId,
        SkDocumentType $type,
        int $sequence,
        string $nomor,
        string $entity,
        Carbon $now,
        ?string $issuedBy,
        string $reference,
        string $notes,
        ?string $docTypeLabel = null,
        int $points = 0,
    ): string {
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
            'Doc Type' => $docTypeLabel ?: $type->label(),
            'Doc Code' => $type->value,
            'Nomor' => $nomor,
            'Entity' => $entity,
            'Issued At' => $now->format('Y-m-d H:i:s'),
            'Issued By' => $issuedBy ?: 'HR Administrator',
            'Reference' => $reference,
            'Notes' => $notes,
            'Points' => (string) max(0, $points),
            'Created At' => $now->format('Y-m-d H:i:s'),
        ];

        $storedDocumentId = $this->documents->append($row);
        if (!$storedDocumentId) {
            throw new RuntimeException('Gagal menyimpan riwayat dokumen SK ke Employee_Documents.');
        }

        return $storedDocumentId;
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

    /**
     * Date printed on the document and used for the roman month/year in the number.
     * An empty value keeps the current Jakarta clock for CLI and older callers.
     */
    public function issuedAtFrom(?string $docDate): Carbon
    {
        $now = now()->timezone('Asia/Jakarta');
        $raw = trim((string) $docDate);
        if ($raw === '') {
            return $now;
        }

        try {
            return Carbon::parse($raw, 'Asia/Jakarta')->timezone('Asia/Jakarta')->setTimeFrom($now);
        } catch (\Throwable) {
            return $now;
        }
    }

    /**
     * Stored issue date for a later PDF download, so the letter date does not
     * jump to the moment the file is generated again.
     */
    public function issuedDateForPdf(string $employeeId, SkDocumentType $type): ?string
    {
        $employeeId = ltrim(trim($employeeId), "'");
        $latest = $this->documents->getLatestByEmployeeAndType($employeeId, $type->value);
        $raw = trim((string) ($latest['Issued At'] ?? ''));
        if ($raw === '') {
            return null;
        }

        try {
            return Carbon::parse($raw, 'Asia/Jakarta')->timezone('Asia/Jakarta')->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }

    public function format(int $sequence, string $code, string $entity, Carbon $at, ?string $suffix = null): string
    {
        $roman = self::ROMAN_MONTHS[max(0, min(11, $at->month - 1))];
        $nomor = sprintf('%03d/%s/%s/%s/%d', $sequence, strtoupper($code), $entity, $roman, $at->year);
        $suffix = strtoupper(trim((string) $suffix));
        if ($suffix !== '') {
            $nomor .= '/' . $suffix;
        }

        return $nomor;
    }

    public function employeeOwnsNomor(string $employeeId, SkDocumentType $type, string $nomor): bool
    {
        $nomor = trim($nomor);
        if ($nomor === '') {
            return false;
        }

        return $this->historyForEmployee($employeeId)->contains(
            fn (array $row) => strtoupper(trim((string) ($row['Doc Code'] ?? ''))) === $type->value
                && trim((string) ($row['Nomor'] ?? '')) === $nomor
        );
    }

    public function resolveEntityCode(string $branchName): string
    {
        $branch = trim($branchName);
        $b = strtolower($branch);

        foreach (config('hris.mpr.companies', []) as $code => $company) {
            if (!is_array($company)) {
                continue;
            }

            $entityCode = strtoupper(trim((string) ($company['code'] ?? $code)));
            $companyName = strtolower(trim((string) ($company['name'] ?? '')));
            $hasCodeToken = preg_match(
                '/(?<![A-Z0-9])' . preg_quote($entityCode, '/') . '(?![A-Z0-9])/i',
                $branch
            ) === 1;

            if ($hasCodeToken || ($companyName !== '' && str_contains($b, $companyName))) {
                return $entityCode;
            }
        }

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

    /**
     * Next free SP number across every letter. A second letter for the same
     * employee, at any level, takes a new number instead of reusing theirs.
     */
    private function resolveWarningLetterSequence(): int
    {
        $lock = Cache::lock('lock_sp_seq_alloc', 15);
        try {
            $lock->block(15);

            $used = $this->documents->getAll()
                ->filter(fn (array $row) => strtoupper(trim((string) ($row['Doc Code'] ?? ''))) === SkDocumentType::SURAT_PERINGATAN->value)
                ->map(fn (array $row) => (int) ($row['Sequence'] ?? 0))
                ->filter(fn (int $sequence) => $sequence > 0)
                ->flip();

            $sequence = 1;
            while ($used->has($sequence)) {
                $sequence++;
            }

            return $sequence;
        } finally {
            optional($lock)->release();
        }
    }
}
