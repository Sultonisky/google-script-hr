<?php

namespace App\Services;

use App\Enums\AbsenceSummonsLevel;
use App\Enums\SkDocumentType;
use App\Repositories\Contracts\AuditLogRepositoryInterface;
use App\Repositories\Contracts\EmployeeDocumentRepositoryInterface;
use App\Repositories\Contracts\EmployeeRepositoryInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AbsenceSummonsService
{
    public function __construct(
        private EmployeeRepositoryInterface $employees,
        private EmployeeDocumentRepositoryInterface $documents,
        private SkNumberService $skNumbers,
        private PdfGeneratorService $pdfService,
        private EmployeeDocumentArchiveService $archive,
        private AuditLogRepositoryInterface $auditRepo,
    ) {}

    /**
     * @param  array{level:string, doc_date:string, absence_start_date:string, absence_end_date:string, absence_second_start_date?:?string, absence_second_end_date?:?string, meeting_date:string, meeting_time:string, meeting_location:string, meeting_agenda:string}  $data
     * @return array{success:bool, message:string, status:int, document_id?:string, nomor?:string, file_name?:string}
     */
    public function generate(string $employeeId, array $data, string $issuedBy, ?string $archivedBy = null): array
    {
        $employee = $this->employees->findById($employeeId);
        if (! $employee) {
            return ['success' => false, 'message' => 'Karyawan tidak ditemukan.', 'status' => 404];
        }
        if (! WarningLetterService::isEligible($employee)) {
            return [
                'success' => false,
                'message' => 'Surat Penggilan Mangkir hanya dapat diterbitkan untuk karyawan berstatus Contract/PKWT atau Permanent/PKWTT.',
                'status' => 422,
            ];
        }

        $level = AbsenceSummonsLevel::from($data['level']);
        $firstSummons = $level === AbsenceSummonsLevel::SECOND
            ? $this->latestFirstSummons($employee->employeeId)
            : null;
        if ($level === AbsenceSummonsLevel::SECOND && $firstSummons === null) {
            return [
                'success' => false,
                'message' => 'Panggilan Kerja II hanya dapat diterbitkan setelah Panggilan Kerja I tercatat untuk karyawan ini.',
                'status' => 422,
            ];
        }

        $docDate = Carbon::parse($data['doc_date'], 'Asia/Jakarta')->startOfDay();
        $absenceStartDate = Carbon::parse($data['absence_start_date'], 'Asia/Jakarta')->startOfDay();
        $absenceEndDate = Carbon::parse($data['absence_end_date'], 'Asia/Jakarta')->startOfDay();
        $absenceSecondStartDate = filled($data['absence_second_start_date'] ?? null)
            ? Carbon::parse($data['absence_second_start_date'], 'Asia/Jakarta')->startOfDay()
            : null;
        $absenceSecondEndDate = filled($data['absence_second_end_date'] ?? null)
            ? Carbon::parse($data['absence_second_end_date'], 'Asia/Jakarta')->startOfDay()
            : null;
        $issuedAt = $docDate->copy()->setTimeFrom(now()->timezone('Asia/Jakarta'));
        $absencePeriod = $absenceStartDate->format('Y-m-d').' s.d. '.$absenceEndDate->format('Y-m-d');
        if ($absenceSecondStartDate && $absenceSecondEndDate) {
            $absencePeriod .= '; '.$absenceSecondStartDate->format('Y-m-d').' s.d. '.$absenceSecondEndDate->format('Y-m-d');
        }
        $entityCode = strtoupper(trim($data['company_entity']));
        $company = config("hris.mpr.companies.{$entityCode}");

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
        $extraData = [
            'sk_number' => $nomor,
            'doc_date' => $docDate->format('Y-m-d'),
            'absence_start_date' => $absenceStartDate->format('Y-m-d'),
            'absence_end_date' => $absenceEndDate->format('Y-m-d'),
            'absence_second_start_date' => $absenceSecondStartDate?->format('Y-m-d'),
            'absence_second_end_date' => $absenceSecondEndDate?->format('Y-m-d'),
            'meeting_date' => Carbon::parse($data['meeting_date'], 'Asia/Jakarta')->format('Y-m-d'),
            'meeting_time' => $data['meeting_time'],
            'meeting_location' => trim($data['meeting_location']),
            'meeting_agenda' => trim($data['meeting_agenda']),
            'company_entity' => $entityCode,
            'summons_level' => $level->value,
            'first_summons_number' => $firstSummons['Nomor'] ?? null,
            'first_summons_date' => isset($firstSummons['Issued At'])
                ? Carbon::parse($firstSummons['Issued At'], 'Asia/Jakarta')->format('Y-m-d')
                : null,
            'working_days' => $level === AbsenceSummonsLevel::SECOND
                ? (int) $data['working_days']
                : null,
        ];
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
     * Find the most recently issued first summons, including records created
     * before the first/second summons references were introduced.
     *
     * @return array<string, string>|null
     */
    private function latestFirstSummons(string $employeeId): ?array
    {
        return $this->documents->getByEmployeeId($employeeId)
            ->filter(function (array $row): bool {
                if (strtoupper(trim((string) ($row['Doc Code'] ?? ''))) !== SkDocumentType::SURAT_PEMANGGILAN_MANGKIR->value) {
                    return false;
                }

                return trim((string) ($row['Reference'] ?? '')) === AbsenceSummonsLevel::FIRST->reference()
                    || trim((string) ($row['Doc Type'] ?? '')) === AbsenceSummonsLevel::FIRST->documentLabel();
            })
            ->sortByDesc(fn (array $row) => (string) ($row['Issued At'] ?? $row['Created At'] ?? ''))
            ->first();
    }
}
