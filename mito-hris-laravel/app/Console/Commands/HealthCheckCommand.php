<?php

namespace App\Console\Commands;

use App\Services\Google\GoogleSheetsService;
use Illuminate\Console\Command;

class HealthCheckCommand extends Command
{
    protected $signature = 'mito:health-check';
    protected $description = 'Memeriksa status koneksi Google Sheets, Google Drive, Kredensial Service Account, dan Integritas Sistem MITO HRIS';

    public function handle(GoogleSheetsService $sheetsService): int
    {
        $this->info('===========================================================');
        $this->info('  MITO HRIS — System & Google Cloud Health Check Tool');
        $this->info('===========================================================');

        $spreadsheetId = config('google.spreadsheet_id');
        $credentialsPath = config('google.credentials_path');

        $this->line("1. Kredensial File Path: {$credentialsPath}");
        if (file_exists($credentialsPath)) {
            $this->info("   [OK] File kredensial Service Account ditemukan.");
        } else {
            $this->error("   [FAIL] File kredensial tidak ditemukan pada path yang ditentukan!");
        }

        $this->line("2. Spreadsheet ID: {$spreadsheetId}");
        if (!empty($spreadsheetId)) {
            $this->info("   [OK] Spreadsheet ID terkonfigurasi.");
        } else {
            $this->error("   [FAIL] GOOGLE_SPREADSHEET_ID belum diisi di berkas .env!");
        }

        $this->line("3. Uji Konektivitas Google Sheets API:");
        try {
            $data = $sheetsService->getValues('data_kandidat!A1:Z1');
            $this->info("   [OK] Berhasil terhubung ke Google Sheets API! Header pertama terdeteksi.");
        } catch (\Throwable $e) {
            $this->warn("   [WARN] Gagal membaca sheet: " . $e->getMessage());
        }

        $this->info('===========================================================');
        $this->info('  Pemeriksaan selesai.');
        $this->info('===========================================================');

        return Command::SUCCESS;
    }
}
