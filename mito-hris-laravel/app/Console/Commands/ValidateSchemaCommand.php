<?php

namespace App\Console\Commands;

use App\Console\Commands\Concerns\SoftDeprecatesSheetsEraCommand;
use App\Services\SchemaValidationService;
use Illuminate\Console\Command;

class ValidateSchemaCommand extends Command
{
    use SoftDeprecatesSheetsEraCommand;

    protected $signature = 'mito:validate-schema {--sheet= : Specific sheet to validate} {--force : Jalankan meski SoT sudah pgsql}';

    protected $description = '[Sheets-era / archive] Validate Google Sheets headers vs expected schemas';

    public function handle(SchemaValidationService $validator): int
    {
        if ($this->refuseSheetsEraUnlessForced('Archive schema check only. SoT headers live in DB migrations.')) {
            return Command::SUCCESS;
        }

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