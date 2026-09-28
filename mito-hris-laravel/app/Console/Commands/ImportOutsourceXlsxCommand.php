<?php

namespace App\Console\Commands;

use App\Services\OutsourceXlsxImportService;
use Illuminate\Console\Command;

/**
 * Initial load of outsource employees from the HR workbook into the active data store.
 * Upserts by Outsource ID; blank cells never overwrite existing values.
 */
class ImportOutsourceXlsxCommand extends Command
{
    protected $signature = 'mito:outsource:import-xlsx
                            {file : Path ke file .xlsx}
                            {--sheet=RAW DATA : Nama sheet sumber (kolom A–V)}
                            {--vendor=Damarindo : Vendor untuk seluruh baris yang diimpor}
                            {--dry-run : Tampilkan ringkasan tanpa menulis data}';

    protected $description = 'Impor data karyawan outsource dari file Excel (kolom A–V) ke data store outsource.';

    public function handle(OutsourceXlsxImportService $importer): int
    {
        $vendor = (string) $this->option('vendor');
        $vendors = config('hris.outsource.vendors', []);
        if (!in_array($vendor, $vendors, true)) {
            $this->error("Vendor tidak valid: {$vendor}. Pilih: " . implode(', ', $vendors));

            return Command::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');

        try {
            $result = $importer->import((string) $this->argument('file'), (string) $this->option('sheet'), $vendor, $dryRun);
        } catch (\Throwable $e) {
            $this->error('Gagal membaca file: ' . $e->getMessage());

            return Command::FAILURE;
        }

        foreach ($result['warnings'] as $warning) {
            $this->warn($warning);
        }
        foreach ($result['errors'] as $error) {
            $this->error($error);
        }

        $this->table(['Dibaca', $dryRun ? 'Akan dibuat' : 'Dibuat', $dryRun ? 'Akan diperbarui' : 'Diperbarui', 'Peringatan', 'Error'], [[
            $result['read'], $result['created'], $result['updated'], count($result['warnings']), count($result['errors']),
        ]]);

        if ($result['errors'] !== []) {
            return Command::FAILURE;
        }

        $this->info($dryRun ? 'Dry-run selesai. Jalankan tanpa --dry-run untuk menyimpan.' : 'Impor selesai.');

        return Command::SUCCESS;
    }
}
