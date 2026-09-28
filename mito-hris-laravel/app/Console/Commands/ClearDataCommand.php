<?php

namespace App\Console\Commands;

use App\Repositories\Contracts\AuditLogRepositoryInterface;
use App\Services\Google\GoogleSheetsService;
use App\Support\HrisDataDriver;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class ClearDataCommand extends Command
{
    protected $signature = 'mito:clear-data {--force : Skip confirmation} {--sheets : Also clear Google Sheets data (archive)}';

    protected $description = 'Clear cache/session; --sheets clear archive (disabled saat pgsql kecuali --force)';

    public function handle(GoogleSheetsService $sheets, AuditLogRepositoryInterface $auditRepo): int
    {
        $env = config('app.env');
        if ($env !== 'local' && ! $this->option('force')) {
            $this->error('This command is only allowed in local environment. Use --force to override.');

            return Command::FAILURE;
        }

        if (! $this->option('force')) {
            $target = $this->option('sheets') ? 'cache AND Google Sheets archive' : 'local cache and session data';
            if (! $this->confirm("This will delete all {$target}. Are you sure?")) {
                return Command::FAILURE;
            }
        }

        Artisan::call('cache:clear');
        $this->info('Local cache cleared.');

        $clearedSheets = false;
        if ($this->option('sheets')) {
            if (HrisDataDriver::usesPgsql() && ! $this->option('force')) {
                $this->warn('DISABLED: --sheets saat HRIS_DATA_DRIVER=pgsql (SoT = DB). Archive tidak dihapus.');
                $this->line('Backup override: php artisan mito:clear-data --sheets --force');
            } else {
                if (HrisDataDriver::usesPgsql()) {
                    $this->warn('Sheets-era --sheets dijalankan dengan --force (SoT tetap DB).');
                }
                $this->info('Clearing Google Sheets data (preserving headers)...');
                try {
                    $sheets->clearAllSheets();
                    $this->info('Google Sheets data cleared successfully.');
                    $clearedSheets = true;
                } catch (\Throwable $e) {
                    $this->error('Failed to clear Google Sheets: '.$e->getMessage());

                    return Command::FAILURE;
                }
            }
        }

        $this->info('Clear complete.');
        $auditRepo->log(
            'System',
            'mito:clear-data',
            'cleared',
            'scope',
            null,
            $clearedSheets ? 'Sheets and cache' : 'Cache and session',
            'SYSTEM',
            'Command'
        );

        return Command::SUCCESS;
    }
}
