<?php

namespace App\Services;

use App\Enums\AbsenceSummonsLevel;
use App\Enums\SkDocumentType;
use App\Repositories\Contracts\AuditLogRepositoryInterface;
use App\Repositories\Contracts\EmployeeDocumentRepositoryInterface;
use App\Repositories\Contracts\EmployeeRepositoryInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

class AbsenceSummonsService
{
    public function __construct(
        private EmployeeRepositoryInterface $employees,
        private EmployeeDocumentRepositoryInterface $documents,
        private SkNumberService $skNumbers,
        private PdfGeneratorService $pdfService,
        private EmployeeDocumentArchiveService $archive,
        private AuditLogRepositoryInterface $auditRepo,
        private LetterAttachmentImageService $attachmentImages,
    ) {}

    /**
     * @param  array{level:string, doc_date:string, absence_start_date:string, absence_end_date:string, absence_second_start_date?:?string, absence_second_end_date?:?string, meeting_date:string, meeting_time:string, meeting_location:string, meeting_agenda:string, attachments?:array<int, UploadedFile|null>, attachment_labels?:array<int, ?string>}  $data
     * @return array{success:bool, message:string, status:int, document_id?:string, nomor?:string, file_name?:string}
     */
    public function generate(string $employeeId, array $data, string $issuedBy, ?string $archivedBy = null): array
    {
        $context = $this->resolveContext($employeeId, $data);
        if (isset($context['error'])) {
            return $context['error'];
        }
        ['employee' => $employee, 'level' => $level, 'company' => $company] = $context;

        $docDate = Carbon::parse($data['doc_date'], 'Asia/Jakarta')->startOfDay();
        $issuedAt = $docDate->copy()->setTimeFrom(now()->timezone('Asia/Jakarta'));
        $absencePeriod = $data['absence_start_date'].' s.d. '.$this->absenceEndDate($level, $data);
        if (filled($data['absence_second_start_date'] ?? null) && filled($data['absence_second_end_date'] ?? null)) {
            $absencePeriod .= '; '.$data['absence_second_start_date'].' s.d. '.$data['absence_second_end_date'];
        }

        $issued = $this->skNumbers->issue(
            employeeId: $employee->employeeId,
            type: SkDocumentType::SURAT_PEMANGGILAN_MANGKIR,
            branchName: $company['name'],
            issuedBy: $issuedBy,
            reference: $level->reference(),
            notes: 'Periode mangkir: '.$absencePeriod,
            issuedAt: $issuedAt,
            docTypeLabel: $level->documentLabel(),
            documentIdSuffix: $level->value.'-'.Str::upper((string) Str::uuid()),
        );
        $nomor = $issued['nomor'];
        $extraData = $this->buildExtraData($context, $data, $nomor);
        $fileName = sprintf(
            'Surat_Penggilan_Mangkir_%s_%s_Employee_%s.pdf',
            $level === AbsenceSummonsLevel::FIRST ? 'I' : 'II',
            trim(preg_replace('/[^a-zA-Z0-9]+/', '_', (string) ($employee->fullName ?? 'Employee')), '_') ?: 'Employee',
            preg_replace('/[^a-zA-Z0-9_-]+/', '_', $employee->employeeId)
        );

        $content = $this->pdfService->generateAbsenceSummonsPdf($employee, $extraData)->output();
        $file = $this->archive->capture(
            $employee->employeeId,
            SkDocumentType::SURAT_PEMANGGILAN_MANGKIR,
            $nomor,
            $content,
            $fileName,
            $archivedBy,
            $issued['document_id']
        );
        if (! $file) {
            Log::error('AbsenceSummonsService: nomor SPM terbit tetapi PDF gagal diarsipkan', [
                'employee_id' => $employee->employeeId,
                'nomor' => $nomor,
                'document_id' => $issued['document_id'],
            ]);

            return [
                'success' => false,
                'message' => "Nomor {$nomor} sudah tercatat, tetapi PDF Surat Penggilan Mangkir gagal disimpan. Hubungi administrator sistem.",
                'status' => 500,
            ];
        }

        $this->auditRepo->log(
            'Employee',
            $employee->employeeId,
            'generated',
            'surat_pemanggilan_mangkir',
            null,
            "{$level->label()} {$nomor}",
            $issuedBy,
            $level->documentLabel()
        );

        return [
            'success' => true,
            'message' => $level->documentLabel().' untuk '.Str::of((string) $employee->fullName)->trim()." berhasil diterbitkan dengan nomor {$nomor}.",
            'status' => 201,
            'document_id' => $file->document_id,
            'nomor' => $nomor,
            'file_name' => $fileName,
        ];
    }

    /**
     * Riwayat panggilan mangkir per karyawan untuk prefill modal:
     * employee_id => [latest => panggilan terakhir, first => Panggilan Kerja I terakhir].
     *
     * @return array<string, array{latest: array{level:string, nomor:string, issued_at:string}, first: ?array{nomor:string, issued_at:string, absence_start_date:?string}}>
     */
    public function historyByEmployee(): array
    {
        $history = [];
        $this->documents->getAll()
            ->filter(fn (array $row) => strtoupper(trim((string) ($row['Doc Code'] ?? ''))) === SkDocumentType::SURAT_PEMANGGILAN_MANGKIR->value)
            ->sortBy(fn (array $row) => (string) ($row['Issued At'] ?? $row['Created At'] ?? ''))
            ->each(function (array $row) use (&$history) {
                $level = $this->documentLevel($row);
                $employeeId = ltrim(trim((string) ($row['Employee ID'] ?? '')), "'");
                if (! $level || $employeeId === '') {
                    return;
                }

                $nomor = trim((string) ($row['Nomor'] ?? ''));
                $issuedAt = trim((string) ($row['Issued At'] ?? ''));
                $history[$employeeId]['latest'] = ['level' => $level->value, 'nomor' => $nomor, 'issued_at' => $issuedAt];
                $history[$employeeId]['first'] ??= null;
                if ($level === AbsenceSummonsLevel::FIRST) {
                    preg_match('/Periode mangkir:\s*(\d{4}-\d{2}-\d{2})/', (string) ($row['Notes'] ?? ''), $match);
                    $history[$employeeId]['first'] = [
                        'nomor' => $nomor,
                        'issued_at' => $issuedAt,
                        'absence_start_date' => $match[1] ?? null,
                    ];
                }
            });

        return $history;
    }

    private function documentLevel(array $row): ?AbsenceSummonsLevel
    {
        $reference = trim((string) ($row['Reference'] ?? ''));
        $docType = trim((string) ($row['Doc Type'] ?? ''));
        foreach (AbsenceSummonsLevel::cases() as $level) {
            if ($reference === $level->reference() || $docType === $level->documentLabel()) {
                return $level;
            }
        }

        return null;
    }

    /**
     * Render a draft PDF from the form input without issuing a number or archiving.
     *
     * @return array{success:bool, status:int, message?:string, content?:string, file_name?:string}
     */
    public function preview(string $employeeId, array $data): array
    {
        $context = $this->resolveContext($employeeId, $data);
        if (isset($context['error'])) {
            return $context['error'];
        }

        $extraData = $this->buildExtraData($context, $data, null) + ['draft' => true];

        return [
            'success' => true,
            'status' => 200,
            'content' => $this->pdfService->generateAbsenceSummonsPdf($context['employee'], $extraData)->output(),
            'file_name' => sprintf(
                'DRAFT_Surat_Panggilan_Kerja_%s.pdf',
                $context['level'] === AbsenceSummonsLevel::FIRST ? 'I' : 'II'
            ),
        ];
    }

    /**
     * @return array{error: array{success:bool, message:string, status:int}}|array{employee:\App\DTOs\EmployeeData, level:AbsenceSummonsLevel, firstSummons:?array, entityCode:string, company:array, attachments:list<array{label:string, src:string, width:int, height:int}>}
     */
    private function resolveContext(string $employeeId, array $data): array
    {
        $employee = $this->employees->findById($employeeId);
        if (! $employee) {
            return ['error' => ['success' => false, 'message' => 'Karyawan tidak ditemukan.', 'status' => 404]];
        }
        if (! WarningLetterService::isEligible($employee)) {
            return ['error' => [
                'success' => false,
                'message' => 'Surat Penggilan Mangkir hanya dapat diterbitkan untuk karyawan berstatus Contract/PKWT atau Permanent/PKWTT.',
                'status' => 422,
            ]];
        }

        $level = AbsenceSummonsLevel::from($data['level']);
        $firstSummons = $level === AbsenceSummonsLevel::SECOND
            ? $this->latestFirstSummons($employee->employeeId)
            : null;
        if ($level === AbsenceSummonsLevel::SECOND && $firstSummons === null) {
            return ['error' => [
                'success' => false,
                'message' => 'Panggilan Kerja II hanya dapat diterbitkan setelah Panggilan Kerja I tercatat untuk karyawan ini.',
                'status' => 422,
            ]];
        }

        try {
            $attachments = $this->prepareAttachments($data);
        } catch (RuntimeException $e) {
            return ['error' => ['success' => false, 'message' => $e->getMessage(), 'status' => 422]];
        }

        $entityCode = $this->skNumbers->resolveEntityCode((string) ($employee->branchName ?? ''));

        return [
            'employee' => $employee,
            'level' => $level,
            'firstSummons' => $firstSummons,
            'entityCode' => $entityCode,
            'company' => config("hris.mpr.companies.{$entityCode}"),
            'attachments' => $attachments,
        ];
    }

    /**
     * @return list<array{label:string, src:string, width:int, height:int}>
     */
    private function prepareAttachments(array $data): array
    {
        $labels = array_values($data['attachment_labels'] ?? []);
        $attachments = [];
        foreach (array_values($data['attachments'] ?? []) as $index => $file) {
            if (! $file instanceof UploadedFile) {
                continue;
            }
            $number = count($attachments) + 1;
            try {
                $image = $this->attachmentImages->prepare($file);
            } catch (RuntimeException) {
                throw new RuntimeException("File lampiran {$number} bukan gambar yang valid atau rusak.");
            }
            $attachments[] = ['label' => trim((string) ($labels[$index] ?? ''))] + $image;
        }

        return $attachments;
    }

    private function buildExtraData(array $context, array $data, ?string $nomor): array
    {
        $date = static fn (?string $value): ?string => filled($value)
            ? Carbon::parse($value, 'Asia/Jakarta')->format('Y-m-d')
            : null;
        $level = $context['level'];
        $firstSummons = $context['firstSummons'];

        return [
            'sk_number' => $nomor,
            'doc_date' => $date($data['doc_date']),
            'absence_start_date' => $date($data['absence_start_date']),
            'absence_end_date' => $date($this->absenceEndDate($level, $data)),
            'absence_second_start_date' => $date($data['absence_second_start_date'] ?? null),
            'absence_second_end_date' => $date($data['absence_second_end_date'] ?? null),
            'meeting_date' => $date($data['meeting_date']),
            'meeting_time' => $data['meeting_time'],
            'meeting_location' => trim($data['meeting_location']),
            'meeting_agenda' => trim((string) ($data['meeting_agenda'] ?? '')) ?: null,
            'attachment_count' => count($context['attachments']),
            'attachments' => $context['attachments'],
            'company_entity' => $context['entityCode'],
            'summons_level' => $level->value,
            'first_summons_number' => $firstSummons['Nomor'] ?? null,
            'first_summons_date' => $date($firstSummons['Issued At'] ?? null),
            'working_days' => $level === AbsenceSummonsLevel::SECOND
                ? (int) $data['working_days']
                : null,
        ];
    }

    /**
     * Panggilan Kerja II menghitung mangkir sampai dengan tanggal surat.
     */
    private function absenceEndDate(AbsenceSummonsLevel $level, array $data): ?string
    {
        return $level === AbsenceSummonsLevel::SECOND
            ? $data['doc_date']
            : ($data['absence_end_date'] ?? null);
    }

    /**
     * Find the most recently issued first summons, including records created
     * before the first/second summons references were introduced.
     *
     * @return array<string, string>|null
     */
    private function latestFirstSummons(string $employeeId): ?array
    {
        return $this->documents->getByEmployeeId($employeeId)
            ->filter(fn (array $row): bool => strtoupper(trim((string) ($row['Doc Code'] ?? ''))) === SkDocumentType::SURAT_PEMANGGILAN_MANGKIR->value
                && $this->documentLevel($row) === AbsenceSummonsLevel::FIRST)
            ->sortByDesc(fn (array $row) => (string) ($row['Issued At'] ?? $row['Created At'] ?? ''))
            ->first();
    }
}
