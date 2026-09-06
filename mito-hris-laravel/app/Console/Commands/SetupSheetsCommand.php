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

        $commonError = collect($results)
            ->pluck('error')
            ->filter(fn($error) => is_string($error) && str_starts_with($error, 'Google Sheets tidak dapat dibaca:'))
            ->first();
        if ($commonError) {
            $this->error("  [FAIL] {$commonError}");
            return Command::FAILURE;
        }

        $fixedSheets = [];

        foreach ($results as $sheet => $result) {
            if ($result['valid']) {
                $this->info("  [OK] {$sheet}: schema valid");
            } else {
                $this->error("  [FAIL] {$sheet}: schema invalid");
                if (!empty($result['error'])) {
                    $this->error("    {$result['error']}");
                    $allValid = false;
                    continue;
                }
                if (!empty($result['missing'])) {
                    $this->warn("    Missing headers: " . implode(', ', $result['missing']));
                }
                if ($this->option('fix')) {
                    if ($validator->fixSheetHeaders($sheet)) {
                        $this->info("  [FIXED] {$sheet}: headers updated");
                        $fixedSheets[] = $sheet;
                    } else {
                        $this->error("  [FIX FAILED] {$sheet}: could not update headers");
                        $allValid = false;
                    }
                } else {
                    $allValid = false;
                }
            }
        }

        if ($this->option('fix') && !empty($fixedSheets)) {
            usleep(500000);
            $rechecked = $validator->validateAllSheets();
            $recheckError = collect($rechecked)
                ->pluck('error')
                ->filter(fn($error) => is_string($error) && str_starts_with($error, 'Google Sheets tidak dapat dibaca:'))
                ->first();
            if ($recheckError) {
                $this->error("  [FAIL] {$recheckError}");
                return Command::FAILURE;
            }
            foreach ($fixedSheets as $sheet) {
                if (($rechecked[$sheet]['valid'] ?? false) === true) {
                    continue;
                }
                $this->error("  [STILL INVALID] {$sheet}: headers could not be fully fixed");
                $allValid = false;
            }
        }

        return $allValid ? Command::SUCCESS : Command::FAILURE;
    }
}