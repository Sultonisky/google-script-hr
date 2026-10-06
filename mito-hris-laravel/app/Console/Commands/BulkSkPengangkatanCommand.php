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
use PhpOffice\PhpSpreadsheet\IOFactory;
use ZipArchive;

/**
 * Bulk-issue SK Pengangkatan (SKP) for employees who are already permanent, from an
 * HR workbook (Employee ID, Full Name, Organization, Job Position, Branch Name).
 * Per row: issue the SKP number, render the PDF, archive it for Document Tracking and
 * add it to a ZIP. Status Employee is left untouched. Rows whose employee already has
 * an SKP are skipped, so the command is safe to re-run.
 *
 * Usage:
 *   php artisan mito:bulk-sk-pengangkatan "karyawan-tetap.xlsx" --dry-run
 *   php artisan mito:bulk-sk-pengangkatan "karyawan-tetap.xlsx" --issued-by="hr@mitogroup.id"
 */
class BulkSkPengangkatanCommand extends Command
{
    protected $signature = 'mito:bulk-sk-pengangkatan
        {file : Path ke file .xlsx}
        {--sheet=karyawan tetap : Nama sheet sumber}
        {--output= : Folder hasil ZIP dan laporan (default storage/app/bulk-sk-pengangkatan)}
        {--issued-by=HR Administrator : Penerbit yang dicatat di Employee_Documents dan arsip}
        {--dry-run : Validasi data dan tampilkan rencana nomor tanpa menulis data}
        {--force : Lewati konfirmasi sebelum menerbitkan nomor}';

    protected $description = 'Terbitkan SK Pengangkatan (SKP) massal dari file Excel, arsipkan PDF ke Document Tracking, dan kumpulkan dalam ZIP';

    private const REQUIRED_HEADERS = ['employee id', 'full name', 'organization', 'job position', 'branch name'];

    public function handle(
        EmployeeRepositoryInterface $employees,
        EmployeeDocumentRepositoryInterface $documents,
        SkNumberService $skNumbers,
        PdfGeneratorService $pdfService,
        EmployeeDocumentArchiveService $archive,
        AuditLogRepositoryInterface $audit,
    ): int {
        $dryRun = (bool) $this->option('dry-run');
        $issuedBy = trim((string) $this->option('issued-by')) ?: 'HR Administrator';
        $now = now()->timezone('Asia/Jakarta');

        if (!$dryRun && !class_exists(ZipArchive::class)) {
            $this->error('Ekstensi PHP zip tidak tersedia, ZIP tidak dapat dibuat.');

            return Command::FAILURE;
        }

        try {
            $rows = $this->readRows((string) $this->argument('file'), (string) $this->option('sheet'));
        } catch (\Throwable $e) {
            $this->error('Gagal membaca file: ' . $e->getMessage());

            return Command::FAILURE;
        }

        $plan = $this->buildPlan($rows, $employees, $documents, $skNumbers, $now);
        $ready = array_values(array_filter($plan, fn (array $item) => $item['status'] === 'SIAP'));

        $outputDir = rtrim((string) ($this->option('output') ?: storage_path('app/bulk-sk-pengangkatan')), '/\\');
        if (!is_dir($outputDir) && !mkdir($outputDir, 0775, true) && !is_dir($outputDir)) {
            $this->error("Folder output tidak dapat dibuat: {$outputDir}");

            return Command::FAILURE;
        }
        $stem = 'SK_Pengangkatan_Massal_' . $now->format('Ymd_His');

        if ($dryRun) {
            $this->renderPlan($plan);
            $report = $this->writeReport("{$outputDir}/{$stem}_dry-run.csv", $plan);
            $this->info('Dry run selesai (tidak ada data ditulis).');
            $this->line("  Laporan: {$report}");

            return Command::SUCCESS;
        }

        if ($ready === []) {
            $this->renderPlan($plan);
            $this->warn('Tidak ada karyawan yang siap diterbitkan SK.');

            return Command::SUCCESS;
        }

        if (!$this->option('force') && !$this->confirm('Terbitkan ' . count($ready) . ' SK Pengangkatan sekarang?', false)) {
            $this->warn('Dibatalkan, tidak ada data ditulis.');

            return Command::FAILURE;
        }

        $zipPath = "{$outputDir}/{$stem}.zip";
        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            $this->error("ZIP tidak dapat dibuat: {$zipPath}");

            return Command::FAILURE;
        }

        $reference = 'BULK-SKP-' . $now->format('Ymd');
        $bar = $this->output->createProgressBar(count($ready));
        foreach ($plan as $index => $item) {
            if ($item['status'] !== 'SIAP') {
                continue;
            }

            $plan[$index] = $this->issueOne($item, $now, $issuedBy, $reference, $skNumbers, $pdfService, $archive, $audit, $zip);
            $bar->advance();
        }
        $bar->finish();
        $this->newLine(2);

        $zipped = $zip->numFiles;
        $zip->close();

        $this->renderPlan($plan);
        $report = $this->writeReport("{$outputDir}/{$stem}_laporan.csv", $plan);
        $this->info('Penerbitan SK Pengangkatan massal selesai.');
        if ($zipped > 0) {
            $this->line("  ZIP     : {$zipPath} ({$zipped} PDF)");
        }
        $this->line("  Laporan : {$report}");

        return collect($plan)->contains(fn (array $item) => $item['status'] === 'GAGAL')
            ? Command::FAILURE
            : Command::SUCCESS;
    }

    /**
     * @return array<int, array<string, string>> keyed by Excel row number, values keyed by lowercase header
     */
    private function readRows(string $path, string $sheetName): array
    {
        if (!is_file($path)) {
            throw new \RuntimeException("File tidak ditemukan: {$path}");
        }

        $book = IOFactory::load($path);
        $sheet = $book->getSheetByName($sheetName);
        if (!$sheet) {
            throw new \RuntimeException("Sheet '{$sheetName}' tidak ada. Sheet tersedia: " . implode(', ', $book->getSheetNames()));
        }

        $raw = $sheet->toArray(null, true, true, false);
        $header = array_map(fn ($value) => strtolower(trim((string) $value)), $raw[0] ?? []);
        $missing = array_diff(self::REQUIRED_HEADERS, $header);
        if ($missing !== []) {
            throw new \RuntimeException('Kolom wajib tidak ada: ' . implode(', ', $missing));
        }

        $rows = [];
        foreach (array_slice($raw, 1, null, true) as $offset => $values) {
            $row = [];
            foreach ($header as $col => $name) {
                if ($name !== '') {
                    $row[$name] = trim((string) ($values[$col] ?? ''));
                }
            }
            if (implode('', $row) !== '') {
                $rows[$offset + 1] = $row;
            }
        }

        return $rows;
    }

    /**
     * @param  array<int, array<string, string>>  $rows
     * @return array<int, array<string, mixed>>
     */
    private function buildPlan(
        array $rows,
        EmployeeRepositoryInterface $employees,
        EmployeeDocumentRepositoryInterface $documents,
        SkNumberService $skNumbers,
        Carbon $now,
    ): array {
        $nextSequence = $documents->maxSequence();
        $seen = [];
        $plan = [];

        foreach ($rows as $line => $row) {
            $employeeId = ltrim($row['employee id'], "'");
            $item = [
                'line' => $line,
                'employee_id' => $employeeId,
                'full_name' => $row['full name'],
                'job_position' => $row['job position'],
                'organization' => $row['organization'],
                'branch_name' => $row['branch name'],
                'employee' => null,
                'sequence' => null,
                'nomor' => '',
                'document_id' => '',
                'status' => 'SIAP',
                'notes' => [],
            ];

            if ($employeeId === '') {
                $plan[] = ['status' => 'GAGAL', 'notes' => ['Employee ID kosong']] + $item;
                continue;
            }
            if (isset($seen[$employeeId])) {
                $plan[] = ['status' => 'LEWATI', 'notes' => ["Duplikat dengan baris {$seen[$employeeId]}"]] + $item;
                continue;
            }
            $seen[$employeeId] = $line;

            $employee = $employees->findById($employeeId);
            if (!$employee) {
                $plan[] = ['status' => 'GAGAL', 'notes' => ['Employee ID tidak ditemukan di HRIS']] + $item;
                continue;
            }
            $item['employee'] = $employee;

            $existing = $documents->getLatestByEmployeeAndType($employeeId, SkDocumentType::PENGANGKATAN->value);
            if ($existing) {
                $item['nomor'] = trim((string) ($existing['Nomor'] ?? ''));
                $item['document_id'] = trim((string) ($existing['Document ID'] ?? ''));
                $plan[] = ['status' => 'LEWATI', 'notes' => ['Sudah punya SK Pengangkatan']] + $item;
                continue;
            }

            $item['notes'] = $this->differences($item, $employee, $skNumbers);
            $item['sequence'] = $documents->sequenceForEmployee($employeeId) ?? ++$nextSequence;
            $branch = $item['branch_name'] !== '' ? $item['branch_name'] : (string) ($employee->branchName ?? '');
            $item['nomor'] = $skNumbers->format(
                $item['sequence'],
                SkDocumentType::PENGANGKATAN->value,
                $skNumbers->resolveEntityCode($branch),
                $now
            );
            $plan[] = $item;
        }

        return $plan;
    }

    /**
     * File values are printed on the SK; differences with HRIS master data are reported only.
     *
     * @return array<int, string>
     */
    private function differences(array $item, EmployeeData $employee, SkNumberService $skNumbers): array
    {
        $same = fn (?string $a, ?string $b) => strtolower(preg_replace('/\s+/', ' ', trim((string) $a)))
            === strtolower(preg_replace('/\s+/', ' ', trim((string) $b)));

        $notes = [];
        if ($item['full_name'] !== '' && !$same($item['full_name'], $employee->fullName)) {
            $notes[] = "Nama HRIS: {$employee->fullName}";
        }
        $hrisPosition = $employee->jobPositionLocation ?: $employee->jobPosition;
        if ($item['job_position'] !== '' && !$same($item['job_position'], $hrisPosition)) {
            $notes[] = "Jabatan HRIS: {$hrisPosition}";
        }
        if ($item['organization'] !== '' && !$same($item['organization'], $employee->department)) {
            $notes[] = "Departemen HRIS: {$employee->department}";
        }
        if ($item['branch_name'] !== ''
            && $skNumbers->resolveEntityCode($item['branch_name']) !== $skNumbers->resolveEntityCode((string) $employee->branchName)) {
            $notes[] = "PT HRIS: {$employee->branchName}";
        }

        return $notes;
    }

    private function issueOne(
        array $item,
        Carbon $now,
        string $issuedBy,
        string $reference,
        SkNumberService $skNumbers,
        PdfGeneratorService $pdfService,
        EmployeeDocumentArchiveService $archive,
        AuditLogRepositoryInterface $audit,
        ZipArchive $zip,
    ): array {
        /** @var EmployeeData $employee */
        $employee = $item['employee'];
        $employeeId = $item['employee_id'];
        $branch = $item['branch_name'] !== '' ? $item['branch_name'] : (string) ($employee->branchName ?? '');

        try {
            $issued = $skNumbers->issue(
                employeeId: $employeeId,
                type: SkDocumentType::PENGANGKATAN,
                branchName: $branch,
                issuedBy: $issuedBy,
                reference: $reference,
                notes: 'SK Pengangkatan massal',
                issuedAt: $now,
            );
        } catch (\Throwable $e) {
            $item['status'] = 'GAGAL';
            $item['notes'][] = 'Nomor tidak terbit: ' . $e->getMessage();

            return $item;
        }

        $item['nomor'] = $issued['nomor'];
        $item['sequence'] = $issued['sequence'];
        $item['document_id'] = $issued['document_id'];

        try {
            $extraData = array_filter([
                'sk_number' => $issued['nomor'],
                'skNumber' => $issued['nomor'],
                'letter_number' => $issued['nomor'],
                'doc_date' => $now->toDateString(),
                'branch_name' => $branch,
                'job_position' => $item['job_position'],
                'department' => $item['organization'] ?: $employee->department,
                'division' => $employee->division ?: $item['organization'],
            ], fn ($value) => $value !== null && $value !== '');

            $content = $pdfService->generateSkPengangkatanPdf($employee, $extraData)->output();

            $archived = $archive->capture(
                $employeeId,
                SkDocumentType::PENGANGKATAN,
                $issued['nomor'],
                $content,
                "SK_Pengangkatan_{$employeeId}.pdf",
                $issuedBy,
                $issued['document_id'],
            );
            if (!$archived) {
                $item['notes'][] = 'PDF tidak terarsip';
            }

            $audit->log('Employee', $employeeId, 'generated', 'sk_pengangkatan', null, 'PDF', $issuedBy, 'Bulk Export');

            $name = trim((string) preg_replace('/[^A-Za-z0-9]+/', '_', $employee->fullName ?: $item['full_name']), '_');
            $zip->addFromString(sprintf('%03d_SK_Pengangkatan_%s_%s.pdf', $issued['sequence'], $name ?: 'Employee', $employeeId), $content);

            $item['status'] = 'TERBIT';
        } catch (\Throwable $e) {
            $item['status'] = 'GAGAL';
            $item['notes'][] = 'Nomor sudah terbit, PDF gagal dibuat: ' . $e->getMessage();
        }

        return $item;
    }

    private function renderPlan(array $plan): void
    {
        $this->table(
            ['Baris', 'Employee ID', 'Nama', 'Status', 'Nomor SK', 'Catatan'],
            array_map(fn (array $item) => [
                $item['line'],
                $item['employee_id'],
                $item['full_name'],
                $item['status'],
                $item['nomor'],
                implode('; ', $item['notes']),
            ], $plan)
        );

        $counts = array_count_values(array_column($plan, 'status'));
        ksort($counts);
        $this->line('  ' . implode(' | ', array_map(fn ($status, $count) => "{$status}: {$count}", array_keys($counts), $counts)));
    }

    private function writeReport(string $path, array $plan): string
    {
        $handle = fopen($path, 'w');
        fwrite($handle, "\xEF\xBB\xBF");
        fputcsv($handle, ['Baris', 'Employee ID', 'Nama', 'Jabatan', 'Organization', 'Branch Name', 'Status', 'Nomor SK', 'Document ID', 'Catatan']);
        foreach ($plan as $item) {
            fputcsv($handle, [
                $item['line'],
                $item['employee_id'],
                $item['full_name'],
                $item['job_position'],
                $item['organization'],
                $item['branch_name'],
                $item['status'],
                $item['nomor'],
                $item['document_id'],
                implode('; ', $item['notes']),
            ]);
        }
        fclose($handle);

        return $path;
    }
}
