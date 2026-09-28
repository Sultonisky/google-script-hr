<?php

namespace App\Console\Commands;

use App\Console\Commands\Concerns\SoftDeprecatesSheetsEraCommand;
use App\Repositories\Contracts\AuditLogRepositoryInterface;
use App\Services\DummyDataService;
use Illuminate\Console\Command;

class SeedDummyCommand extends Command
{
    use SoftDeprecatesSheetsEraCommand;

    protected $signature = 'mito:seed-dummy {--count=50 : Number of candidates to generate per status} {--force : Skip confirmation / izinkan saat SoT=pgsql}';

    protected $description = '[Sheets-era] Dummy data ke Google Sheets (disabled saat SoT=pgsql kecuali --force)';

    public function handle(DummyDataService $dummyService, AuditLogRepositoryInterface $auditRepo): int
    {
        if ($this->refuseSheetsEraUnlessForced('SoT=pgsql: jangan seed dummy ke Sheets.')) {
            return Command::SUCCESS;
        }

        $env = config('app.env');
        if ($env !== 'local' && ! $this->option('force')) {
            $this->error('Dummy data generation is only allowed in local environment. Use --force to override.');

            return Command::FAILURE;
        }

        if (!$this->option('force')) {
            if (!$this->confirm('This will generate new dummy data in Google Sheets. Continue?')) {
                return Command::FAILURE;
            }
        }

        $count = (int) $this->option('count');
        if ($count <= 0) {
            $this->error('--count must be a positive integer greater than zero.');
            return Command::FAILURE;
        }

        $this->info("Generating dummy data with {$count} candidates per status...");

        $result = $dummyService->generateAll($count);

        if (!empty($result['error'])) {
            $this->error('Dummy data generation failed: ' . $result['error']);
            return Command::FAILURE;
        }

        $this->info("Generated:");
        $this->line("  Pending: {$result['pending']}");
        $this->line("  Hold: {$result['hold']}");
        $this->line("  Accepted: {$result['accepted']}");
        $this->line("  Blacklist: {$result['blacklist']}");
        $this->line("  Employees: {$result['employees']}");
        $this->line("  Probation: {$result['probation']}");
        $this->line("  Audit logs: {$result['audit']}");
        $auditRepo->log('System', 'mito:seed-dummy', 'generated', 'summary', null, $result, 'SYSTEM', 'Command');

        return Command::SUCCESS;
    }
}