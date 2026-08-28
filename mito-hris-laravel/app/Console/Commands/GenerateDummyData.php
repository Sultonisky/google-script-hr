<?php

namespace App\Console\Commands;

use App\Services\DummyDataService;
use App\Repositories\Contracts\AuditLogRepositoryInterface;
use Illuminate\Console\Command;

class GenerateDummyData extends Command
{
    protected $signature = 'mito:dummy
                            {--count=50 : Jumlah kandidat yang dihasilkan (default: 50)}
                            {--force : Skip konfirmasi prompt}';

    protected $description = 'Generate dummy/demo data ke Google Sheets (kandidat, employee, probation, audit log)';

    public function handle(DummyDataService $dummyDataService, AuditLogRepositoryInterface $auditRepo): int
    {
        $count = (int) $this->option('count');
        $count = max(10, min(200, $count));

        if (!$this->option('force')) {
            $this->warn('⚠️  Peringatan: Command ini akan MENGHAPUS semua data existing di sheet berikut:');
            $this->line('   • data_kandidat, kandidat_hold, kandidat_accepted, kandidat_blacklist');
            $this->line('   • Employee, kandidat_probation, Audit_Log');
            $this->newLine();

            if (!$this->confirm("Lanjutkan generate {$count} dummy data?", false)) {
                $this->info('Dibatalkan.');
                return Command::SUCCESS;
            }
        }

        $this->info("Memulai generate dummy data (count={$count})...");
        $this->newLine();

        $start = microtime(true);

        try {
            $result = $dummyDataService->generateAll($count);

            if (isset($result['error'])) {
                $this->error('Gagal generate dummy data: ' . $result['error']);
                return Command::FAILURE;
            }

            $elapsed = round(microtime(true) - $start, 2);

            $this->info('✅  Dummy data berhasil dibuat!');
            $this->newLine();
            $this->table(
                ['Sheet', 'Records'],
                [
                    ['data_kandidat (pending)',    $result['pending']   ?? 0],
                    ['kandidat_hold',              $result['hold']      ?? 0],
                    ['kandidat_accepted',          $result['accepted']  ?? 0],
                    ['kandidat_blacklist',         $result['blacklist'] ?? 0],
                    ['Employee',                   $result['employees'] ?? 0],
                    ['kandidat_probation',         $result['probation'] ?? 0],
                    ['Audit_Log',                  $result['audit']     ?? 0],
                ]
            );

            $this->newLine();
            $this->line("⏱  Selesai dalam {$elapsed} detik.");
            $this->line('💡  Jalankan `php artisan mito:sync` untuk refresh cache.');
            $auditRepo->log('System', 'mito:dummy', 'generated', 'summary', null, $result, 'SYSTEM', 'Command');

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Error: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
