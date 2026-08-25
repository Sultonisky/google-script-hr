<?php

namespace App\Console\Commands;

use App\Services\SchemaValidationService;
use Illuminate\Console\Command;

class ValidateSchemaCommand extends Command
{
    protected $signature = 'mito:validate-schema {--sheet= : Specific sheet to validate}';
    protected $description = 'Validate the schema of Google Sheets against expected headers';

    public function handle(SchemaValidationService $validator): int
    {
        $this->info('MITO HRIS Schema Validation');
        $this->line('');

        if ($sheet = $this->option('sheet')) {
            $result = $validator->validateSheet($sheet);
            $this->displayResult($sheet, $result);
            return $result['valid'] ? Command::SUCCESS : Command::FAILURE;
        }

        $results = $validator->validateAllSheets();
        $allValid = true;
        foreach ($results as $sheet => $result) {
            $this->displayResult($sheet, $result);
            if (!$result['valid']) $allValid = false;
        }

        return $allValid ? Command::SUCCESS : Command::FAILURE;
    }

    protected function displayResult(string $sheet, array $result): void
    {
        $status = $result['valid'] ? '✅' : '❌';
        $this->line("{$status} {$sheet}");
        if (!$result['valid']) {
            if (!empty($result['missing'])) {
                $this->warn("   Missing: " . implode(', ', $result['missing']));
            }
            if (!empty($result['error'])) {
                $this->warn("   Error: " . $result['error']);
            }
        }
    }
}