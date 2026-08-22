<?php

namespace App\Console\Commands;

use App\Repositories\Contracts\CandidateRepositoryInterface;
use App\Repositories\Contracts\EmployeeRepositoryInterface;
use Illuminate\Console\Command;

class SyncSheetsCommand extends Command
{
    protected $signature = 'mito:sync';
    protected $description = 'Sinkronisasi dan pembaruan cache data dari Google Spreadsheet ke Laravel';

    public function handle(
        CandidateRepositoryInterface $candidateRepo,
        EmployeeRepositoryInterface $employeeRepo
    ): int {
        $this->info('Memulai sinkronisasi data dari Google Sheets...');

        try {
            $candidates = $candidateRepo->getAll();
            $this->line("  ✓ Berhasil mengambil {$candidates->count()} data kandidat dari sheet 'data_kandidat'");

            $employees = $employeeRepo->getAll();
            $this->line("  ✓ Berhasil mengambil {$employees->count()} data karyawan dari sheet 'Employee'");

            $this->info('Sinkronisasi selesai! Cache lokal berhasil diperbarui.');
            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Gagal melakukan sinkronisasi: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
