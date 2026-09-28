<?php

namespace App\Console\Commands;

use App\Models\Employee;
use App\Models\MprRequestor;
use App\Models\User;
use App\Services\Google\GoogleSheetsService;
use App\Support\HrisDataDriver;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class HealthCheckCommand extends Command
{
    protected $signature = 'mito:health-check';
    protected $description = 'Cek SoT (DB saat pgsql), koneksi Google Sheets/Drive (mirror), dan kredensial';

    public function handle(GoogleSheetsService $sheetsService): int
    {
        $this->info('===========================================================');
        $this->info('  MITO HRIS — System Health Check');
        $this->info('===========================================================');

        $driver = HrisDataDriver::current();
        $this->line("0. HRIS_DATA_DRIVER: {$driver}");
        if ($driver === HrisDataDriver::PGSQL) {
            $this->info('   [OK] SoT = PostgreSQL. Sheets = archive/mirror saja.');
        } elseif ($driver === HrisDataDriver::SHEETS) {
            $this->warn('   [WARN] SoT masih Google Sheets.');
        } else {
            $this->line('   [INFO] Driver local/test.');
        }

        $dbOk = true;
        $this->line('1. Database (SoT saat pgsql):');
        try {
            DB::connection()->getPdo();
            $conn = config('database.default');
            $this->info("   [OK] Connected ({$conn}).");
            if (Schema::hasTable('employees')) {
                $this->line('   employees='.Employee::query()->count()
                    .' users='.User::query()->count()
                    .' mpr_requestors='.MprRequestor::query()->count());
            }
        } catch (\Throwable $e) {
            $dbOk = false;
            $this->error('   [FAIL] '.$e->getMessage());
        }

        $credentialsPath = config('google.credentials_path');
        $spreadsheetId = config('google.spreadsheet_id');

        $this->line("2. Kredensial Service Account: {$credentialsPath}");
        if (is_string($credentialsPath) && $credentialsPath !== '' && file_exists($credentialsPath)) {
            $this->info('   [OK] File kredensial ditemukan.');
        } else {
            $level = $driver === HrisDataDriver::PGSQL ? 'warn' : 'error';
            $this->{$level}('   ['.($level === 'warn' ? 'WARN' : 'FAIL').'] File kredensial tidak ditemukan (perlu untuk Drive/mirror).');
        }

        $this->line("3. Spreadsheet ID (archive/mirror): {$spreadsheetId}");
        if (! empty($spreadsheetId)) {
            $this->info('   [OK] Spreadsheet ID terkonfigurasi.');
        } else {
            $this->warn('   [WARN] GOOGLE_SPREADSHEET_ID kosong (mirror/ETL archive tidak tersedia).');
        }

        $this->line('4. Uji konektivitas Google Sheets (mirror, opsional jika SoT=pgsql):');
        try {
            $sheetsService->getValues('data_kandidat!A1:Z1', false);
            $this->info('   [OK] Google Sheets API terhubung.');
        } catch (\Throwable $e) {
            if ($driver === HrisDataDriver::PGSQL) {
                $this->warn('   [WARN] Mirror Sheets tidak terbaca: '.$e->getMessage());
            } else {
                $this->error('   [FAIL] '.$e->getMessage());
                $dbOk = false;
            }
        }

        $this->info('===========================================================');
        $this->info('  Pemeriksaan selesai.');
        $this->info('===========================================================');

        return $dbOk ? Command::SUCCESS : Command::FAILURE;
    }
}
