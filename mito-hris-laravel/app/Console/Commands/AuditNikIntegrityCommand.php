<?php

namespace App\Console\Commands;

use App\Services\Google\GoogleSheetsService;
use Illuminate\Console\Command;

class AuditNikIntegrityCommand extends Command
{
    protected $signature = 'hris:audit-nik
                            {--export : Export hasil audit ke CSV}
                            {--fix-trailing-zeros : Tandai NIK yang berakhir dengan banyak angka 0 (suspect precision loss)}';

    protected $description = 'Audit integritas NIK di Google Sheets untuk mendeteksi data corrupt akibat float precision loss';

    public function handle(GoogleSheetsService $sheets): int
    {
        $this->info('🔍 Memulai audit integritas NIK...');
        $this->newLine();

        $sheetsToCheck = [
            'data_kandidat' => 'Kandidat Aktif',
            'candidates_accepted' => 'Kandidat Accepted',
            'candidates_hold' => 'Kandidat Hold',
            'candidates_blacklist' => 'Kandidat Blacklist',
            'Employee' => 'Employee',
        ];

        $totalChecked = 0;
        $totalSuspect = 0;
        $suspectRecords = [];

        foreach ($sheetsToCheck as $sheetName => $label) {
            $this->line("📋 Checking {$label} ({$sheetName})...");
            
            try {
                $rows = $sheets->getRowsAsAssoc($sheetName, false); // No cache
                
                if (empty($rows)) {
                    $this->warn("   ⚠️  Sheet kosong atau tidak ditemukan");
                    continue;
                }

                $sheetSuspect = 0;

                foreach ($rows as $row) {
                    $totalChecked++;
                    
                    $nik = $row['NIK'] ?? ($row['NIK - NPWP 16 digit'] ?? '');
                    $nikCleaned = ltrim(trim($nik), "'");
                    
                    // Detect suspect patterns
                    $isSuspect = false;
                    $reason = [];

                    // 1. NIK berakhir dengan 4+ angka nol (precision loss indicator)
                    if (preg_match('/0{4,}$/', $nikCleaned)) {
                        $isSuspect = true;
                        $reason[] = 'Trailing zeros (possible precision loss)';
                    }

                    // 2. NIK contains scientific notation characters
                    if (stripos($nikCleaned, 'E') !== false) {
                        $isSuspect = true;
                        $reason[] = 'Scientific notation';
                    }

                    // 3. NIK length != 16
                    if (strlen($nikCleaned) !== 16) {
                        $isSuspect = true;
                        $reason[] = 'Invalid length: ' . strlen($nikCleaned);
                    }

                    // 4. NIK not all numeric
                    if (!ctype_digit($nikCleaned)) {
                        $isSuspect = true;
                        $reason[] = 'Contains non-numeric characters';
                    }

                    if ($isSuspect) {
                        $totalSuspect++;
                        $sheetSuspect++;

                        $suspectRecords[] = [
                            'sheet' => $label,
                            'id' => $row['Recruitment ID'] ?? $row['Employee ID'] ?? 'N/A',
                            'name' => $row['Full Name'] ?? 'N/A',
                            'nik_raw' => $nik,
                            'nik_cleaned' => $nikCleaned,
                            'reason' => implode('; ', $reason),
                            'row_number' => $row['_row_number'] ?? 'N/A',
                        ];
                    }
                }

                if ($sheetSuspect > 0) {
                    $this->warn("   ⚠️  Found {$sheetSuspect} suspect NIK(s)");
                } else {
                    $this->info("   ✅ All NIK valid");
                }

            } catch (\Throwable $e) {
                $this->error("   ❌ Error: " . $e->getMessage());
            }
        }

        $this->newLine();
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->info("✅ Total checked: {$totalChecked}");
        
        if ($totalSuspect > 0) {
            $this->warn("⚠️  Suspect NIK found: {$totalSuspect}");
            $this->newLine();

            // Display suspect records
            $this->table(
                ['Sheet', 'ID', 'Name', 'NIK (Raw)', 'NIK (Cleaned)', 'Issue', 'Row'],
                array_map(fn($r) => [
                    $r['sheet'],
                    $r['id'],
                    \Illuminate\Support\Str::limit($r['name'], 25),
                    $r['nik_raw'],
                    $r['nik_cleaned'],
                    $r['reason'],
                    $r['row_number'],
                ], $suspectRecords)
            );

            // Export option
            if ($this->option('export')) {
                $filename = storage_path('logs/nik-audit-' . now()->format('Y-m-d_His') . '.csv');
                $handle = fopen($filename, 'w');
                fputcsv($handle, array_keys($suspectRecords[0]));
                foreach ($suspectRecords as $record) {
                    fputcsv($handle, $record);
                }
                fclose($handle);
                $this->info("📄 Exported to: {$filename}");
            }

            $this->newLine();
            $this->warn('⚠️  RECOMMENDED ACTIONS:');
            $this->line('   1. Review suspect records above');
            $this->line('   2. Contact affected candidates/employees for NIK re-verification');
            $this->line('   3. Update NIK manually in Google Sheets (use apostrophe prefix)');
            $this->line('   4. After fix, GoogleSheetsService with RAW mode will prevent future corruption');

        } else {
            $this->info('✅ All NIK data appears valid!');
        }

        return $totalSuspect > 0 ? 1 : 0;
    }
}
