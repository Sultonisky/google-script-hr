<?php

namespace App\Services;

use App\DTOs\EmployeeData;
use App\Enums\SkDocumentType;
use App\Enums\WarningLetterLevel;
use App\Repositories\Contracts\AuditLogRepositoryInterface;
use App\Repositories\Contracts\EmployeeDocumentRepositoryInterface;
use App\Repositories\Contracts\EmployeeRepositoryInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Penerbitan Surat Peringatan (SP-1/2/3) untuk karyawan Contract/PKWT dan Permanent/PKWTT.
 * Isi surat hanya berasal dari form, sehingga PDF wajib diarsipkan saat terbit.
 */
class WarningLetterService
{
    public const VIOLATION_CATEGORIES = [
        'Kedisiplinan & Kehadiran',
        'Kinerja & Target Kerja',
        'Perilaku & Etika Kerja',
        'Pelanggaran Prosedur / SOP',
        'Keselamatan & Kesehatan Kerja (K3)',
        'Kerahasiaan & Aset Perusahaan',
        'Lainnya',
    ];

    public const REGULATION_TYPES = [
        'Peraturan Perusahaan',
        'Perjanjian Kerja',
        'Peraturan Perundang-undangan',
        'SOP / Instruksi Kerja',
        'Kebijakan Perusahaan',
    ];

    public const ELIGIBLE_STATUSES = ['contract', 'pkwt', 'permanent', 'pkwtt'];

    public function __construct(
        private EmployeeRepositoryInterface $employees,
        private EmployeeDocumentRepositoryInterface $documents,
        private SkNumberService $skNumbers,
        private PdfGeneratorService $pdfService,
        private EmployeeDocumentArchiveService $archive,
        private AuditLogRepositoryInterface $auditRepo,
    ) {}

    public static function isEligible(?EmployeeData $employee): bool
    {
        return $employee !== null
            && in_array(strtolower(trim((string) ($employee->statusEmployee ?? ''))), self::ELIGIBLE_STATUSES, true);
    }

    /**
     * Latest Surat Peringatan per employee: employee_id => [level, issued_at, nomor].
     *
     * @return array<string, array{level:string, issued_at:string, nomor:string}>
     */
    public function latestByEmployee(): array
    {
        return $this->documents->getAll()
            ->filter(fn (array $row) => strtoupper(trim((string) ($row['Doc Code'] ?? ''))) === SkDocumentType::SURAT_PERINGATAN->value)
            ->sortBy(fn (array $row) => (string) ($row['Issued At'] ?? ''))
            ->mapWithKeys(fn (array $row) => [
                ltrim(trim((string) ($row['Employee ID'] ?? '')), "'") => [
                    'level' => trim((string) ($row['Reference'] ?? '')),
                    'issued_at' => trim((string) ($row['Issued At'] ?? '')),
                    'nomor' => trim((string) ($row['Nomor'] ?? '')),
                ],
            ])
            ->all();
    }

    /**
     * Render draft PDF dari isi form tanpa menerbitkan nomor, mengarsipkan, atau mencatat audit.
     *
     * @return array{success:bool, message?:string, status:int, content?:string, file_name?:string}
     */
    public function preview(string $employeeId, array $data): array
    {
        $employee = $this->employees->findById($employeeId);
        if ($error = $this->eligibilityError($employee)) {
            return $error;
        }

        $extraData = $this->buildExtraData($data, null) + ['draft' => true];

        return [
            'success' => true,
            'status' => 200,
            'content' => $this->pdfService->generateWarningLetterPdf($employee, $extraData)->output(),
            'file_name' => sprintf('DRAFT_Surat_Peringatan_%s.pdf', WarningLetterLevel::from($data['level'])->value),
        ];
    }

    /**
     * @return array{success:false, message:string, status:int}|null
     */
    private function eligibilityError(?EmployeeData $employee): ?array
    {
        if (!$employee) {
            return ['success' => false, 'message' => 'Karyawan tidak ditemukan.', 'status' => 404];
        }
        if (!self::isEligible($employee)) {
            return [
                'success' => false,
                'message' => 'Surat Peringatan hanya dapat diterbitkan untuk karyawan berstatus Contract/PKWT atau Permanent/PKWTT.',
                'status' => 422,
            ];
        }

        return null;
    }

    private function buildExtraData(array $data, ?string $nomor): array
    {
        $level = WarningLetterLevel::from($data['level']);
        $docDate = Carbon::parse($data['doc_date'], 'Asia/Jakarta')->startOfDay();
        $validityMonths = $level->validityMonths();

        return [
            'sk_number' => $nomor ?? '',
            'letter_number' => $nomor ?? '',
            'doc_date' => $docDate->format('Y-m-d'),
            'level' => $level->value,
            'violation_category' => $data['violation_category'] ?? '',
            'violation_description' => trim((string) $data['violation_description']),
            'incident_date' => $data['incident_date'] ?? null,
            'regulation_reference' => trim((string) ($data['regulation_reference'] ?? '')),
            'violations' => $level === WarningLetterLevel::SP1_FINAL ? array_values($data['violations'] ?? []) : [],
            'superior_name' => trim((string) ($data['superior_name'] ?? '')),
            'superior_position' => trim((string) ($data['superior_position'] ?? 'Atasan Langsung')),
            'corrective_actions' => trim((string) ($data['corrective_actions'] ?? '')),
            'validity_months' => $validityMonths,
            'valid_until' => $docDate->copy()->addMonthsNoOverflow($validityMonths)->subDay()->format('Y-m-d'),
        ];
    }

    /**
     * @param  array{level:string, doc_date:string, violation_category:string, violation_description:string,
     *               incident_date?:?string, regulation_reference?:?string, corrective_actions?:?string,
     *               superior_name?:?string, superior_position?:?string,
     *               violations?:array<int, array{description:string, references:array<int, array<string, string>>}>}  $data
     * @return array{success:bool, message:string, status:int, document_id?:string, nomor?:string, file_name?:string}
     */
    public function generate(string $employeeId, array $data, string $issuedBy, ?string $archivedBy = null): array
    {
        $employee = $this->employees->findById($employeeId);
        if ($error = $this->eligibilityError($employee)) {
            return $error;
        }

        $level = WarningLetterLevel::from($data['level']);
        $violationReference = trim((string) ($data['regulation_reference'] ?? ''))
            ?: trim((string) ($data['violation_category'] ?? ''));
        $docDate = Carbon::parse($data['doc_date'], 'Asia/Jakarta')->startOfDay();
        $validUntil = $docDate->copy()->addMonthsNoOverflow($level->validityMonths())->subDay();
        $issuedAt = $docDate->copy()->setTimeFrom(now()->timezone('Asia/Jakarta'));

        $issued = $this->skNumbers->issue(
            employeeId: $employee->employeeId,
            type: SkDocumentType::SURAT_PERINGATAN,
            branchName: (string) ($employee->branchName ?? ''),
            issuedBy: $issuedBy,
            reference: $level->value,
            notes: $violationReference.' — berlaku s.d. '.$validUntil->format('Y-m-d'),
            issuedAt: $issuedAt,
            docTypeLabel: $level->label(),
        );
        $nomor = $issued['nomor'];

        $extraData = $this->buildExtraData($data, $nomor);

        $fileName = sprintf(
            'Surat_Peringatan_%s_%s_Employee_%s.pdf',
            $level->value,
            trim(preg_replace('/[^a-zA-Z0-9]+/', '_', (string) ($employee->fullName ?? 'Employee')), '_') ?: 'Employee',
            preg_replace('/[^a-zA-Z0-9_-]+/', '_', $employee->employeeId)
        );

        $content = $this->pdfService->generateWarningLetterPdf($employee, $extraData)->output();
        $file = $this->archive->capture($employee->employeeId, SkDocumentType::SURAT_PERINGATAN, $nomor, $content, $fileName, $archivedBy);
        if (!$file) {
            Log::error('WarningLetterService: nomor SP terbit tetapi PDF gagal diarsipkan', [
                'employee_id' => $employee->employeeId,
                'nomor' => $nomor,
                'document_id' => $issued['document_id'],
            ]);

            return [
                'success' => false,
                'message' => "Nomor {$nomor} sudah tercatat, tetapi PDF Surat Peringatan gagal disimpan. Hubungi administrator sistem.",
                'status' => 500,
            ];
        }

        $this->auditRepo->log(
            'Employee',
            $employee->employeeId,
            'generated',
            'surat_peringatan',
            null,
            "{$level->shortLabel()} {$nomor}",
            $issuedBy,
            'Surat Peringatan'
        );

        return [
            'success' => true,
            'message' => "{$level->label()} untuk " . Str::of((string) $employee->fullName)->trim() . " berhasil diterbitkan dengan nomor {$nomor}.",
            'status' => 201,
            'document_id' => $file->document_id,
            'nomor' => $nomor,
            'file_name' => $fileName,
        ];
    }
}
