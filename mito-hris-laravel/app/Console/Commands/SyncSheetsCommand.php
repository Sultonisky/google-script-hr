<?php

namespace App\Console\Commands;

use App\Repositories\Contracts\AuditLogRepositoryInterface;
use App\Repositories\Contracts\CandidateRepositoryInterface;
use App\Repositories\Contracts\EmployeeRepositoryInterface;
use App\Repositories\Contracts\MprRepositoryInterface;
use App\Repositories\Contracts\MprRequestorRepositoryInterface;
use App\Support\HrisDataDriver;
use Illuminate\Console\Command;

class SyncSheetsCommand extends Command
{
    protected $signature = 'mito:sync';

    protected $description = 'Warm data dari SoT aktif (DB jika pgsql, Sheets jika sheets) via repositories';

    public function handle(
        CandidateRepositoryInterface $candidateRepo,
        EmployeeRepositoryInterface $employeeRepo,
        MprRepositoryInterface $mprRepo,
        MprRequestorRepositoryInterface $requestorRepo,
        AuditLogRepositoryInterface $auditRepo
    ): int {
        $driver = HrisDataDriver::current();
        $this->info("Memulai warm-cache dari SoT [{$driver}]...");

        try {
            $candidates = $candidateRepo->getAll();
            $this->line("  ✓ Kandidat: {$candidates->count()}");

            $employees = $employeeRepo->getAll();
            $this->line("  ✓ Karyawan: {$employees->count()}");

            $mprs = $mprRepo->getAll();
            $this->line("  ✓ MPR: {$mprs->count()}");

            $requestors = $requestorRepo->getAll();
            $this->line('  ✓ MPR Requestor: '.count($requestors));

            $this->info('Selesai. Data dibaca dari driver aktif (bukan sync 2 arah).');
            $auditRepo->log('System', 'mito:sync', 'synced', 'summary', null, [
                'driver' => $driver,
                'candidates' => $candidates->count(),
                'employees' => $employees->count(),
                'mprs' => $mprs->count(),
                'requestors' => count($requestors),
            ], 'SYSTEM', 'Command');

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Gagal warm-cache: '.$e->getMessage());

            return Command::FAILURE;
        }
    }
}
