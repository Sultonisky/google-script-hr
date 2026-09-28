<?php

namespace App\Services;

use App\DTOs\EmployeeData;
use App\Enums\SkDocumentType;
use App\Models\EmployeeDocumentFile;
use App\Repositories\Contracts\EmployeeDocumentRepositoryInterface;
use App\Repositories\Contracts\EmployeeRepositoryInterface;
use Closure;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Keeps the first rendered PDF of every issued Employee_Documents row so HR
 * can re-download the identical file later from Document Tracking.
 */
class EmployeeDocumentArchiveService
{
    public function __construct(
        private EmployeeDocumentRepositoryInterface $documents,
        private EmployeeRepositoryInterface $employees,
        private PdfGeneratorService $pdfService,
    ) {}

    /**
     * Archive a freshly rendered PDF for the latest document of $type when its
     * nomor matches the number printed on the PDF. Never breaks the export.
     *
     * @param  string|Closure():string  $content
     */
    public function capture(
        string $employeeId,
        SkDocumentType $type,
        string $nomor,
        string|Closure $content,
        string $fileName,
        ?string $archivedBy = null,
    ): ?EmployeeDocumentFile {
        try {
            $employeeId = ltrim(trim($employeeId), "'");
            $nomor = trim($nomor);
            if ($employeeId === '' || ($type->isNumbered() && $nomor === '')) {
                return null;
            }

            $document = $this->documents->getLatestByEmployeeAndType($employeeId, $type->value);
            if (!$document) {
                return null;
            }
            if ($type->isNumbered() && trim((string) ($document['Nomor'] ?? '')) !== $nomor) {
                return null;
            }

            $documentId = trim((string) ($document['Document ID'] ?? ''));
            if ($documentId === '') {
                return null;
            }

            $existing = $this->findFile($documentId);
            if ($existing) {
                return $existing;
            }

            $pdf = $content instanceof Closure ? (string) $content() : $content;

            return $this->store($document, $pdf, $fileName, EmployeeDocumentFile::SOURCE_EXPORT, $archivedBy);
        } catch (\Throwable $e) {
            Log::warning('EmployeeDocumentArchiveService: gagal mengarsipkan PDF dokumen', [
                'employee_id' => $employeeId,
                'doc_code' => $type->value,
                'nomor' => $nomor,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Archived PDF for the document, or a regenerated one (then archived so
     * later downloads stay identical).
     *
     * @return array{content:string, file_name:string, source:string}|null null when the document does not exist
     *
     * @throws RuntimeException when the PDF cannot be regenerated
     */
    public function download(string $documentId, ?string $requestedBy = null): ?array
    {
        $documentId = trim($documentId);
        $document = $this->findDocument($documentId);
        if (!$document) {
            return null;
        }

        $file = $this->findFile($documentId);
        if ($file) {
            return [
                'content' => $file->content(),
                'file_name' => $file->file_name,
                'source' => $file->source,
            ];
        }

        $rendered = $this->regenerate($document);

        try {
            $this->store($document, $rendered['content'], $rendered['file_name'], EmployeeDocumentFile::SOURCE_REGENERATED, $requestedBy);
        } catch (\Throwable $e) {
            Log::warning('EmployeeDocumentArchiveService: PDF generate ulang tidak tersimpan', [
                'document_id' => $documentId,
                'error' => $e->getMessage(),
            ]);
        }

        return $rendered + ['source' => EmployeeDocumentFile::SOURCE_REGENERATED];
    }

    /**
     * @param  array<int, string>  $documentIds
     * @return array<string, string> document_id => source
     */
    public function archiveSources(array $documentIds): array
    {
        $documentIds = array_values(array_filter(array_map('trim', $documentIds)));
        if ($documentIds === []) {
            return [];
        }

        return EmployeeDocumentFile::query()
            ->whereIn('document_id', $documentIds)
            ->pluck('source', 'document_id')
            ->all();
    }

    private function findDocument(string $documentId): ?array
    {
        if ($documentId === '') {
            return null;
        }

        return $this->documents->getAll()
            ->first(fn (array $row) => trim((string) ($row['Document ID'] ?? '')) === $documentId);
    }

    private function findFile(string $documentId): ?EmployeeDocumentFile
    {
        return EmployeeDocumentFile::query()->where('document_id', $documentId)->first();
    }

    private function store(array $document, string $content, string $fileName, string $source, ?string $archivedBy): EmployeeDocumentFile
    {
        $documentId = trim((string) ($document['Document ID'] ?? ''));
        if ($content === '') {
            throw new RuntimeException('Konten PDF kosong, tidak diarsipkan.');
        }

        try {
            return EmployeeDocumentFile::query()->create([
                'document_id' => $documentId,
                'employee_id' => ltrim(trim((string) ($document['Employee ID'] ?? '')), "'"),
                'doc_code' => strtoupper(trim((string) ($document['Doc Code'] ?? ''))),
                'nomor' => trim((string) ($document['Nomor'] ?? '')),
                'file_name' => $fileName,
                'mime_type' => 'application/pdf',
                'size_bytes' => strlen($content),
                'checksum_sha256' => hash('sha256', $content),
                'content_base64' => base64_encode($content),
                'source' => $source,
                'archived_by' => $archivedBy,
                'archived_at' => now()->timezone('Asia/Jakarta'),
            ]);
        } catch (UniqueConstraintViolationException) {
            return EmployeeDocumentFile::query()->where('document_id', $documentId)->firstOrFail();
        }
    }

    /**
     * Rebuild the PDF from the document's own nomor + issue date and the
     * current employee master data (used only when no archive exists).
     *
     * @return array{content:string, file_name:string}
     */
    private function regenerate(array $document): array
    {
        $type = SkDocumentType::tryFrom(strtoupper(trim((string) ($document['Doc Code'] ?? ''))));
        if (!$type) {
            throw new RuntimeException('Jenis dokumen tidak dikenali, PDF tidak dapat dibuat ulang.');
        }

        $employeeId = ltrim(trim((string) ($document['Employee ID'] ?? '')), "'");
        $employee = $employeeId !== '' ? $this->employees->findById($employeeId) : null;
        if (!$employee) {
            throw new RuntimeException('Data karyawan untuk dokumen ini tidak ditemukan, PDF tidak dapat dibuat ulang.');
        }

        $nomor = trim((string) ($document['Nomor'] ?? ''));
        $issuedAt = $this->issuedAt($document);
        $extraData = [
            'sk_number' => $nomor,
            'skNumber' => $nomor,
            'letter_number' => $nomor,
            'doc_date' => $issuedAt->format('Y-m-d'),
        ];
        $empId = $employee->employeeId;

        $isTadContract = $type === SkDocumentType::PKWT
            && trim((string) ($document['Reference'] ?? '')) === OutsourceContractService::TAD_REFERENCE;

        [$pdf, $fileName] = match (true) {
            $isTadContract => [
                $this->pdfService->generateKontrakPkwtTadPdf($employee, $this->contractData($employee, $extraData, $nomor) + [
                    'mulai_tanggal' => $employee->joinDate,
                ]),
                'PKWT_TAD_' . preg_replace('/[^a-zA-Z0-9_-]+/', '_', $employee->fullName ?? 'Outsource') . "_{$empId}.pdf",
            ],
            default => $this->typePdf($employee, $type, $extraData, $nomor, $issuedAt),
        };

        return ['content' => $pdf->output(), 'file_name' => $fileName];
    }

    /**
     * @return array{0:\Barryvdh\DomPDF\PDF, 1:string}
     */
    private function typePdf(EmployeeData $employee, SkDocumentType $type, array $extraData, string $nomor, Carbon $issuedAt): array
    {
        $empId = $employee->employeeId;
        $stem = $this->employeeDocumentStem($employee);

        return match ($type) {
            SkDocumentType::PKWT => [
                $this->pdfService->generateKontrakPkwtPdf($employee, $this->contractData($employee, $extraData, $nomor)),
                "Kontrak_PKWT_{$empId}.pdf",
            ],
            SkDocumentType::PENGANGKATAN => [
                $this->pdfService->generateSkPengangkatanPdf($employee, $extraData),
                "SK_Pengangkatan_{$empId}.pdf",
            ],
            SkDocumentType::MUTASI, SkDocumentType::DEMOSI, SkDocumentType::PROMOSI => $this->rotationPdf($employee, $type, $extraData),
            SkDocumentType::OFFBOARDING => [
                $this->pdfService->generateSkOffPdf($employee, $extraData),
                "SK_Offboarding_{$stem}.pdf",
            ],
            SkDocumentType::PAKLARING => [
                $this->pdfService->generatePaklaringPdf($employee, $extraData + [
                    'last_working_date' => $employee->resignDate ?: $issuedAt->format('Y-m-d'),
                ]),
                "Paklaring_{$stem}.pdf",
            ],
            SkDocumentType::SURAT_BPJS => [
                $this->pdfService->generateSuratBpjsPdf($employee, [
                    'doc_date' => $extraData['doc_date'],
                    'last_working_date' => $employee->resignDate ?: $issuedAt->format('Y-m-d'),
                ]),
                "Surat_BPJS_{$stem}.pdf",
            ],
        };
    }

    private function contractData(EmployeeData $employee, array $extraData, string $nomor): array
    {
        $data = $extraData + [
            'contract_number' => $nomor,
            'contractNumber' => $nomor,
            'join_date' => $employee->joinDate,
            'contract_end' => $employee->endDateContract,
            'position' => $employee->jobPosition,
            'department' => $employee->department,
            'division' => $employee->division,
            'direct_superior' => $employee->directSuperior,
        ];

        $duration = $this->contractDuration($employee->joinDate, $employee->endDateContract);
        if ($duration !== null) {
            $data['contract_duration'] = $duration;
        }

        return array_filter($data, fn ($value) => $value !== null && $value !== '');
    }

    /**
     * @return array{0:\Barryvdh\DomPDF\PDF, 1:string}
     */
    private function rotationPdf(EmployeeData $employee, SkDocumentType $type, array $extraData): array
    {
        $employeeRotation = trim((string) ($employee->typeOfRotation ?? ''));
        $rotationType = $employeeRotation !== '' && SkDocumentType::fromRotationType($employeeRotation) === $type
            ? $employeeRotation
            : match ($type) {
                SkDocumentType::PROMOSI => 'Promosi',
                SkDocumentType::DEMOSI => 'Demosi',
                default => 'Mutasi',
            };

        $typeSlug = match (ucfirst(strtolower($rotationType))) {
            'Promosi' => 'Promosi',
            'Demosi' => 'Demosi',
            'Mutasi' => 'Mutasi',
            default => 'Rotasi',
        };
        $safeName = preg_replace('/[^a-zA-Z0-9_]/', '_', $employee->fullName ?? $employee->employeeId);

        return [
            $this->pdfService->generateSkRotationPdf($employee, $extraData + ['rotation_type' => $rotationType]),
            "SK_{$typeSlug}_{$safeName}_{$employee->employeeId}.pdf",
        ];
    }

    private function contractDuration(?string $start, ?string $end): ?string
    {
        if (trim((string) $start) === '' || trim((string) $end) === '') {
            return null;
        }

        try {
            $months = (int) round(Carbon::parse($start)->floatDiffInMonths(Carbon::parse($end)->addDay()));
        } catch (\Throwable) {
            return null;
        }

        return $months > 0 ? "{$months} Bulan" : null;
    }

    private function issuedAt(array $document): Carbon
    {
        $raw = trim((string) ($document['Issued At'] ?? '')) ?: trim((string) ($document['Created At'] ?? ''));

        try {
            return $raw !== '' ? Carbon::parse($raw, 'Asia/Jakarta') : now()->timezone('Asia/Jakarta');
        } catch (\Throwable) {
            return now()->timezone('Asia/Jakarta');
        }
    }

    private function employeeDocumentStem(EmployeeData $employee): string
    {
        $name = preg_replace('/[^a-zA-Z0-9]+/', '_', trim($employee->fullName ?? 'Employee'));
        $id = preg_replace('/[^a-zA-Z0-9_-]+/', '_', trim($employee->employeeId ?? 'Unknown'));

        return trim($name ?: 'Employee', '_') . '_Employee_' . ($id ?: 'Unknown');
    }
}
