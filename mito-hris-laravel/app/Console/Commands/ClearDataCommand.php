<?php

namespace App\Console\Commands;

use App\Services\Google\GoogleSheetsService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use App\Repositories\Contracts\AuditLogRepositoryInterface;

class ClearDataCommand extends Command
{
    protected $signature = 'mito:clear-data {--force : Skip confirmation} {--sheets : Also clear Google Sheets data}';
    protected $description = 'Clear application data (database and optionally Google Sheets)';

    public function handle(GoogleSheetsService $sheets, AuditLogRepositoryInterface $auditRepo): int
    {
        $env = config('app.env');
        if ($env !== 'local' && !$this->option('force')) {
            $this->error('This command is only allowed in local environment. Use --force to override.');
            return Command::FAILURE;
        }

        if (!$this->option('force')) {
            $target = $this->option('sheets') ? 'database AND Google Sheets data' : 'local cache and session data';
            if (!$this->confirm("This will delete all {$target}. Are you sure?")) {
                return Command::FAILURE;
            }
        }

        // Clear local Laravel cache/session data
        Artisan::call('cache:clear');
        $this->info('Local cache cleared.');

        if ($this->option('sheets')) {
            $this->info('Clearing Google Sheets data (preserving headers)...');
            try {
                $sheets->clearAllSheets();
                $this->info('Google Sheets data cleared successfully.');
            } catch (\Throwable $e) {
                $this->error('Failed to clear Google Sheets: ' . $e->getMessage());
                return Command::FAILURE;
            }
        }

        $this->info('Clear complete.');
        $auditRepo->log('System', 'mito:clear-data', 'cleared', 'scope', null, $this->option('sheets') ? 'Sheets and cache' : 'Cache and session', 'SYSTEM', 'Command');
        return Command::SUCCESS;
    }
}
