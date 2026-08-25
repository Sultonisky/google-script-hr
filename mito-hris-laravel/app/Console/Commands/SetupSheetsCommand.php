<?php

namespace App\Console\Commands;

use App\Services\SchemaValidationService;
use App\Services\Google\GoogleSheetsService;
use Illuminate\Console\Command;

class SetupSheetsCommand extends Command
{
    protected $signature = 'mito:setup-sheets {--fix : Automatically fix header mismatches}';
    protected $description = 'Validate and optionally fix Google Sheets schemas';

    public function handle(SchemaValidationService $validator, GoogleSheetsService $sheets): int
    {
        $this->info('Validating Google Sheets schemas...');

        $results = $validator->validateAllSheets();
        $allValid = true;

        foreach ($results as $sheet => $result) {
            if ($result['valid']) {
                $this->info("  [OK] {$sheet}: schema valid");
            } else {
                $this->error("  [FAIL] {$sheet}: schema invalid");
                if (!empty($result['missing'])) {
                    $this->warn("    Missing headers: " . implode(', ', $result['missing']));
                }
                if ($this->option('fix')) {
                    if ($validator->fixSheetHeaders($sheet)) {
                        $this->info("  [FIXED] {$sheet}: headers updated");
                        // Re-validate after fix to confirm
                        $recheck = $validator->validateSheet($sheet);
                        if ($recheck['valid']) {
                            $allValid = $allValid && true;
                        } else {
                            $this->error("  [STILL INVALID] {$sheet}: headers could not be fully fixed");
                            $allValid = false;
                        }
                    } else {
                        $this->error("  [FIX FAILED] {$sheet}: could not update headers");
                        $allValid = false;
                    }
                } else {
                    $allValid = false;
                }
            }
        }

        return $allValid ? Command::SUCCESS : Command::FAILURE;
    }
}