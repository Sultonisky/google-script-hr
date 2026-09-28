<?php

namespace App\Console\Commands;

use App\Services\Etl\DatabaseToSheetsEtlService;
use App\Support\HrisDataDriver;
use Illuminate\Console\Command;

/**
 * One-way mirror: DB → Google Sheets (archive / visibility).
 *
 * SoT tetap database. Sync ini menimpa isi tab mirror; edit manual di Sheets
 * akan hilang pada sync berikutnya. Tidak pernah menulis balik ke DB.
 */
class EtlDatabaseToSheetsCommand extends Command
{
    protected $signature = 'mito:etl-db-to-sheets
                            {--dry-run : Hitung baris dari DB tanpa menulis ke Sheets}
                            {--only= : Comma-separated domains (default: all). See DatabaseToSheetsEtlService::DOMAINS}
                            {--force : Izinkan mirror meski HRIS_DATA_DRIVER bukan pgsql (berbahaya jika Sheets masih SoT)}';

    protected $description = 'Mirror DB → Google Sheets (1 arah). SoT tetap database; Sheets hanya archive.';

    public function handle(DatabaseToSheetsEtlService $etl): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $force = (bool) $this->option('force');
        $only = (string) $this->option('only');

        $domains = $only === ''
            ? ['all']
            : array_values(array_filter(array_map('trim', explode(',', $only))));

        $this->warn('MIRROR: Command ini MENULIS ke Google Sheets (replace data rows). DB tidak diubah.');
        $this->info('Driver saat ini: '.HrisDataDriver::current());
        if ($dryRun) {
            $this->info('Mode DRY-RUN: tidak ada write ke Sheets.');
        }
        if ($force) {
            $this->warn('--force aktif: guard driver pgsql dilewati.');
        }

        $this->info('Domains: '.($only === '' ? 'all' : implode(', ', $domains)));

        try {
            $summary = $etl->run($domains, $dryRun, $force);
        } catch (\Throwable $e) {
            $this->error('Mirror gagal: '.$e->getMessage());

            return Command::FAILURE;
        }

        $this->newLine();
        $this->table(
            ['Domain', 'Read (DB)', 'Written/Would', 'Skipped', 'Errors'],
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
            $this->warn("Selesai dengan {$totalErrors} error (lihat di atas). DB tetap utuh.");

            return Command::FAILURE;
        }

        $this->info($dryRun
            ? 'Dry-run selesai. Jalankan tanpa --dry-run untuk mirror ke Sheets.'
            : 'Mirror selesai. SoT tetap DB; Sheets = archive yang baru di-sync.');

        return Command::SUCCESS;
    }
}
