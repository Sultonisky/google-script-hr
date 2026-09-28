<?php

namespace App\Console\Commands;

use App\Enums\SkDocumentType;
use App\Repositories\Contracts\EmployeeDocumentRepositoryInterface;
use App\Repositories\Contracts\EmployeeRepositoryInterface;
use App\Services\SkNumberService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * Record the unnumbered Surat BPJS in Employee_Documents for employees that were
 * offboarded (have an SKO) before Surat BPJS was tracked. Idempotent.
 *
 * Usage:
 *   php artisan mito:backfill-offboarding-bpjs --dry-run
 *   php artisan mito:backfill-offboarding-bpjs
 */
class BackfillOffboardingBpjsCommand extends Command
{
    protected $signature = 'mito:backfill-offboarding-bpjs
        {--dry-run : Tampilkan karyawan yang akan dicatat tanpa menulis data}';

    protected $description = 'Catat Surat BPJS (tanpa nomor) untuk karyawan offboarding lama yang belum tercatat';

    public function handle(
        EmployeeDocumentRepositoryInterface $documents,
        EmployeeRepositoryInterface $employees,
        SkNumberService $skNumbers,
    ): int {
        $dryRun = (bool) $this->option('dry-run');

        $byEmployee = $documents->getAll()
            ->groupBy(fn (array $row) => ltrim(trim((string) ($row['Employee ID'] ?? '')), "'"));

        $created = 0;
        $skipped = 0;

        foreach ($byEmployee as $employeeId => $rows) {
            if ($employeeId === '') {
                continue;
            }

            $codes = $rows->map(fn (array $row) => strtoupper(trim((string) ($row['Doc Code'] ?? ''))));
            if (!$codes->contains(SkDocumentType::OFFBOARDING->value)) {
                continue;
            }
            if ($codes->contains(SkDocumentType::SURAT_BPJS->value)) {
                $skipped++;
                continue;
            }

            $sko = $rows
                ->filter(fn (array $row) => strtoupper(trim((string) ($row['Doc Code'] ?? ''))) === SkDocumentType::OFFBOARDING->value)
                ->sortByDesc(fn (array $row) => (string) ($row['Issued At'] ?? $row['Created At'] ?? ''))
                ->first();
            $issuedAtRaw = trim((string) ($sko['Issued At'] ?? '')) ?: trim((string) ($sko['Created At'] ?? ''));

            $this->line("  [BPJS] {$employeeId} — mengikuti {$sko['Document ID']} ({$issuedAtRaw})");

            if ($dryRun) {
                $created++;
                continue;
            }

            $skNumbers->record(
                employeeId: $employeeId,
                type: SkDocumentType::SURAT_BPJS,
                branchName: (string) ($employees->findById($employeeId)?->branchName ?? ''),
                issuedBy: trim((string) ($sko['Issued By'] ?? '')) ?: null,
                reference: trim((string) ($sko['Reference'] ?? '')),
                notes: trim((string) ($sko['Notes'] ?? '')),
                issuedAt: $issuedAtRaw !== '' ? Carbon::parse($issuedAtRaw, 'Asia/Jakarta') : null,
            );
            $created++;
        }

        $this->newLine();
        $this->info($dryRun ? 'Dry run selesai (tidak ada data ditulis).' : 'Backfill Surat BPJS selesai.');
        $this->line('  ' . ($dryRun ? 'Akan dicatat' : 'Dicatat') . " : {$created}");
        $this->line("  Sudah ada    : {$skipped}");

        return Command::SUCCESS;
    }
}
