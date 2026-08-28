<?php

namespace App\Console\Commands;

use App\Repositories\Contracts\CandidateRepositoryInterface;
use App\Repositories\Contracts\EmployeeRepositoryInterface;
use App\Repositories\Contracts\MprRepositoryInterface;
use App\Repositories\Contracts\MprRequestorRepositoryInterface;
use App\Repositories\Contracts\AuditLogRepositoryInterface;
use Illuminate\Console\Command;

class SyncSheetsCommand extends Command
{
    protected $signature = 'mito:sync';
    protected $description = 'Sinkronisasi dan pembaruan cache data dari Google Spreadsheet ke Laravel';

    public function handle(
        CandidateRepositoryInterface $candidateRepo,
        EmployeeRepositoryInterface $employeeRepo,
        MprRepositoryInterface $mprRepo,
        MprRequestorRepositoryInterface $requestorRepo,
        AuditLogRepositoryInterface $auditRepo
    ): int {
        $this->info('Memulai sinkronisasi data dari Google Sheets...');

        try {
            $candidates = $candidateRepo->getAll();
            $this->line("  ✓ Berhasil mengambil {$candidates->count()} data kandidat dari sheet 'data_kandidat'");

            $employees = $employeeRepo->getAll();
            $this->line("  ✓ Berhasil mengambil {$employees->count()} data karyawan dari sheet 'Employee'");

            $mprs = $mprRepo->getAll();
            $this->line("  ✓ Berhasil mengambil {$mprs->count()} data Manpower Request dari sheet 'MPR'");

            $requestors = $requestorRepo->getAll();
            $this->line("  ✓ Berhasil mengambil " . count($requestors) . " MPR Requestor dari sheet 'mpr_requestor'");

            $this->info('Sinkronisasi selesai! Cache lokal berhasil diperbarui.');
            $auditRepo->log('System', 'mito:sync', 'synced', 'summary', null, [
                'candidates' => $candidates->count(),
                'employees' => $employees->count(),
                'mprs' => $mprs->count(),
                'requestors' => count($requestors),
            ], 'SYSTEM', 'Command');
            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Gagal melakukan sinkronisasi: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
