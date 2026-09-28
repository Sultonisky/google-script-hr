<?php

namespace App\Console\Commands;

use App\Services\Etl\SheetsToDatabaseEtlService;
use Illuminate\Console\Command;

/**
 * One-way ETL: Google Sheets → DB.
 *
 * SAFETY: Spreadsheet is READ-ONLY. This command never append/update/clear Sheets.
 * Optional --truncate-db only clears local/staging DB tables before upsert.
 */
class EtlSheetsToDatabaseCommand extends Command
{
    protected $signature = 'mito:etl-sheets-to-db
                            {--dry-run : Count rows from Sheets without writing to DB}
                            {--only= : Comma-separated domains (default: all). See SheetsToDatabaseEtlService::DOMAINS}
                            {--truncate-db : Delete rows in target DB tables before import (NEVER touches Sheets)}';

    protected $description = 'ETL read-only dari Google Sheets ke database (upsert). Spreadsheet tidak dimodifikasi.';

    public function handle(SheetsToDatabaseEtlService $etl): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $truncateDb = (bool) $this->option('truncate-db');
        $only = (string) $this->option('only');

        $domains = $only === ''
            ? ['all']
            : array_values(array_filter(array_map('trim', explode(',', $only))));

        $this->warn('SAFETY: Command ini HANYA MEMBACA Google Sheets. Spreadsheet tidak akan diubah.');
        if ($truncateDb) {
            $this->warn('--truncate-db akan menghapus data di tabel DB target saja (bukan Sheets).');
        }
        if ($dryRun) {
            $this->info('Mode DRY-RUN: tidak ada write ke database.');
        }

        $this->info('Domains: ' . ($only === '' ? 'all' : implode(', ', $domains)));

        try {
            $summary = $etl->run($domains, $dryRun, $truncateDb && ! $dryRun);
        } catch (\Throwable $e) {
            $this->error('ETL gagal: ' . $e->getMessage());

            return Command::FAILURE;
        }

        $this->newLine();
        $this->table(
            ['Domain', 'Read', 'Written/Would', 'Skipped', 'Errors'],
            collect($summary)->map(fn (array $row, string $domain) => [
                $domain,
                $row['read'],
                $row['written'],
                $row['skipped'],
                count($row['errors']),
            ])->values()->all()
        );

        foreach ($summary as $domain => $row) {
            foreach ($row['errors'] as $error) {
                $this->error("[{$domain}] {$error}");
            }
        }

        $totalErrors = collect($summary)->sum(fn (array $row) => count($row['errors']));
        if ($totalErrors > 0) {
            $this->warn("Selesai dengan {$totalErrors} error (lihat di atas). Sheets tetap utuh.");

            return Command::FAILURE;
        }

        $this->info($dryRun
            ? 'Dry-run selesai. Jalankan tanpa --dry-run untuk menulis ke DB.'
            : 'ETL selesai. Spreadsheet tidak dimodifikasi.');

        return Command::SUCCESS;
    }
}
