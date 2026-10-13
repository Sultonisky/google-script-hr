<?php

namespace App\Console\Commands;

use App\DTOs\EmployeeData;
use App\Enums\SkDocumentType;
use App\Repositories\Contracts\AuditLogRepositoryInterface;
use App\Repositories\Contracts\EmployeeDocumentRepositoryInterface;
use App\Repositories\Contracts\EmployeeRepositoryInterface;
use App\Services\EmployeeDocumentArchiveService;
use App\Services\PdfGeneratorService;
use App\Services\SkNumberService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * Issue SK Pengangkatan (SKP) for a single employee looked up by Employee ID or NIK.
 * The sequence follows Employee_Documents (employee's fixed seq, else max + 1), so the
 * number continues whatever is already running in the target environment.
 * If the employee already has an SKP, the PDF is re-rendered with the existing number.
 *
 * Usage:
 *   php artisan mito:sk-pengangkatan 3171012904990001 --dry-run
 *   php artisan mito:sk-pengangkatan 20260702 --issued-by="hr@mitogroup.id"
 */
class SkPengangkatanCommand extends Command
{
    protected $signature = 'mito:sk-pengangkatan
        {employee : Employee ID atau NIK karyawan}
        {--branch= : Override Branch Name / PT (default dari HRIS)}
        {--job-position= : Override jabatan (default dari HRIS)}
        {--department= : Override departemen (default dari HRIS)}
        {--division= : Override divisi (default dari HRIS)}
        {--doc-date= : Tanggal SK (Y-m-d, default hari ini)}
        {--output= : Folder hasil PDF (default storage/app/sk-pengangkatan)}
        {--issued-by=HR Administrator : Penerbit yang dicatat di Employee_Documents dan arsip}
        {--dry-run : Tampilkan rencana nomor tanpa menulis data}
        {--force : Lewati konfirmasi sebelum menerbitkan nomor}';

    protected $description = 'Terbitkan SK Pengangkatan (SKP) untuk satu karyawan dengan nomor urut lanjutan, arsipkan PDF ke Document Tracking';

    public function handle(
        EmployeeRepositoryInterface $employees,
        EmployeeDocumentRepositoryInterface $documents,
        SkNumberService $skNumbers,
        PdfGeneratorService $pdfService,
        EmployeeDocumentArchiveService $archive,
        AuditLogRepositoryInterface $audit,
    ): int {
        $key = ltrim(trim((string) $this->argument('employee')), "'");
        $employee = $key !== '' ? ($employees->findById($key) ?? $employees->findByNik($key)) : null;
        if (!$employee) {
            $this->error("Karyawan dengan Employee ID / NIK '{$key}' tidak ditemukan di HRIS.");

            return Command::FAILURE;
        }

        try {
            $docDate = trim((string) $this->option('doc-date'));
            $now = ($docDate !== ''
                ? Carbon::parse($docDate, 'Asia/Jakarta')->setTimeFrom(now()->timezone('Asia/Jakarta'))
                : now())->timezone('Asia/Jakarta');
        } catch (\Throwable) {
            $this->error('Format --doc-date tidak valid, gunakan Y-m-d.');

            return Command::FAILURE;
        }

        $employeeId = $employee->employeeId;
        $issuedBy = trim((string) $this->option('issued-by')) ?: 'HR Administrator';
        $branch = trim((string) $this->option('branch')) ?: (string) ($employee->branchName ?? '');
        $fields = [
            'job_position' => trim((string) $this->option('job-position')) ?: ($employee->jobPositionLocation ?: $employee->jobPosition),
            'department' => trim((string) $this->option('department')) ?: $employee->department,
            'division' => trim((string) $this->option('division')) ?: ($employee->division ?: $employee->department),
        ];

        $existing = $documents->getLatestByEmployeeAndType($employeeId, SkDocumentType::PENGANGKATAN->value);
        $existingNomor = trim((string) ($existing['Nomor'] ?? ''));
        $sequence = $documents->sequenceForEmployee($employeeId) ?? ($documents->maxSequence() + 1);
        $nomor = $existingNomor !== ''
            ? $existingNomor
            : $skNumbers->format($sequence, SkDocumentType::PENGANGKATAN->value, $skNumbers->resolveEntityCode($branch), $now);

        $missing = array_keys(array_filter($fields + ['branch_name' => $branch], fn ($value) => trim((string) $value) === ''));
        $this->renderSummary($employee, $branch, $fields, $nomor, $existingNomor !== '', $now);
        if ($missing !== []) {
            $this->warn('Data kosong (akan tampil placeholder di PDF): ' . implode(', ', $missing));
        }

        if ($this->option('dry-run')) {
            $this->info('Dry run selesai (tidak ada data ditulis).');

            return Command::SUCCESS;
        }

        $question = $existingNomor !== ''
            ? "Karyawan sudah punya SK Pengangkatan {$existingNomor}. Cetak ulang PDF dengan nomor tersebut?"
            : "Terbitkan SK Pengangkatan untuk {$employee->fullName}?";
        if (!$this->option('force') && !$this->confirm($question, false)) {
            $this->warn('Dibatalkan, tidak ada data ditulis.');

            return Command::FAILURE;
        }

        $outputDir = rtrim((string) ($this->option('output') ?: storage_path('app/sk-pengangkatan')), '/\\');
        if (!is_dir($outputDir) && !mkdir($outputDir, 0775, true) && !is_dir($outputDir)) {
            $this->error("Folder output tidak dapat dibuat: {$outputDir}");

            return Command::FAILURE;
        }

        if ($existingNomor === '') {
            try {
                $issued = $skNumbers->issue(
                    employeeId: $employeeId,
                    type: SkDocumentType::PENGANGKATAN,
                    branchName: $branch,
                    issuedBy: $issuedBy,
                    reference: 'CLI-SKP-' . $now->format('Ymd'),
                    notes: 'SK Pengangkatan via artisan',
                    issuedAt: $now,
                );
            } catch (\Throwable $e) {
                $this->error('Nomor tidak terbit: ' . $e->getMessage());

                return Command::FAILURE;
            }
            $nomor = $issued['nomor'];
            $sequence = $issued['sequence'];
        }

        try {
            $extraData = array_filter([
                'sk_number' => $nomor,
                'skNumber' => $nomor,
                'letter_number' => $nomor,
                'doc_date' => $now->toDateString(),
                'branch_name' => $branch,
                ...$fields,
            ], fn ($value) => $value !== null && $value !== '');

            $content = $pdfService->generateSkPengangkatanPdf($employee, $extraData)->output();
        } catch (\Throwable $e) {
            $this->error("Nomor {$nomor} sudah terbit, PDF gagal dibuat: " . $e->getMessage());

            return Command::FAILURE;
        }

        if (!$archive->capture($employeeId, SkDocumentType::PENGANGKATAN, $nomor, $content, "SK_Pengangkatan_{$employeeId}.pdf", $issuedBy)) {
            $this->warn('PDF tidak terarsip ke Document Tracking.');
        }
        $audit->log('Employee', $employeeId, 'generated', 'sk_pengangkatan', null, 'PDF', $issuedBy, 'CLI Export');

        $name = trim((string) preg_replace('/[^A-Za-z0-9]+/', '_', $employee->fullName), '_') ?: 'Employee';
        $path = sprintf('%s/%03d_SK_Pengangkatan_%s_%s.pdf', $outputDir, $sequence, $name, $employeeId);
        file_put_contents($path, $content);

        $this->info("SK Pengangkatan {$nomor} selesai.");
        $this->line("  PDF: {$path}");

        return Command::SUCCESS;
    }

    private function renderSummary(EmployeeData $employee, string $branch, array $fields, string $nomor, bool $existing, Carbon $now): void
    {
        $this->table(['Field', 'Nilai'], [
            ['Employee ID', $employee->employeeId],
            ['Nama', $employee->fullName],
            ['NIK', (string) ($employee->nikNpwp ?? '')],
            ['Status Employee', (string) ($employee->statusEmployee ?? '')],
            ['Branch / PT', $branch],
            ['Jabatan', (string) $fields['job_position']],
            ['Departemen', (string) $fields['department']],
            ['Divisi', (string) $fields['division']],
            ['Tanggal SK', $now->toDateString()],
            ['Nomor SK', $nomor . ($existing ? ' (sudah terbit, cetak ulang)' : ' (baru)')],
        ]);
    }
}
