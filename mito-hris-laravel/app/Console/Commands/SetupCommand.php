<?php

namespace App\Console\Commands;

use App\Support\HrisDataDriver;
use Illuminate\Console\Command;

class SetupCommand extends Command
{
    protected $signature = 'mito:setup {--skip-sheets : Skip Google Sheets setup} {--skip-dummy : Skip dummy data seeding} {--force : Run destructive / Sheets-era steps when SoT=pgsql}';

    protected $description = 'Setup MITO HRIS: health, migrate, seed users (Sheets steps auto-skip jika SoT=pgsql)';

    public function handle(): int
    {
        $this->info('===========================================================');
        $this->info('  MITO HRIS — Setup Tool');
        $this->info('===========================================================');

        $env = config('app.env');
        $driver = HrisDataDriver::current();
        $this->line("Environment: {$env}");
        $this->line("HRIS_DATA_DRIVER: {$driver}");

        $this->line("\n1. Running health check...");
        $this->call('mito:health-check');

        $skipSheets = $this->option('skip-sheets') || ($driver === HrisDataDriver::PGSQL && ! $this->option('force'));
        if ($skipSheets) {
            $this->warn("\n2. Skip Google Sheets schema (SoT={$driver}). Backup: mito:setup-sheets --force");
        } else {
            $this->line("\n2. Setting up Google Sheets schema...");
            $schemaExit = $this->call('mito:setup-sheets', [
                '--fix' => true,
                '--force' => $this->option('force'),
            ]);
            if ($schemaExit !== Command::SUCCESS) {
                $this->error('Google Sheets schema validation failed. Setup stopped before seeding.');

                return Command::FAILURE;
            }
        }

        $this->line("\n3. Applying local database migrations...");
        $this->call('migrate', ['--force' => true]);

        $this->line("\n4. Seeding users (via data driver)...");
        $this->call('mito:seed-users', ['--force' => $this->option('force')]);

        $this->line("\n5. Seeding MPR Requestors (via data driver)...");
        $this->call('mito:seed-mpr-requestors', ['--force' => $this->option('force')]);

        $skipDummy = $this->option('skip-dummy') || ($driver === HrisDataDriver::PGSQL && ! $this->option('force'));
        if ($skipDummy) {
            $this->warn("\n6. Skip Sheets dummy seed (SoT={$driver}). Backup: mito:seed-dummy --force");
        } elseif ($env === 'local' || $this->option('force')) {
            $this->line("\n6. Seeding dummy data (Sheets-era)...");
            $this->call('mito:seed-dummy', ['--count' => 50, '--force' => true]);
        } else {
            $this->warn('Skipping dummy data (not in local environment). Use --force to override.');
        }

        $this->info("\nSetup completed successfully.");
        $this->info('Next steps:');
        $this->line('  - Pastikan HRIS_DATA_DRIVER=pgsql setelah ETL');
        $this->line('  - Mirror archive: php artisan mito:etl-db-to-sheets --dry-run');
        $this->line('  - Visit /hr/dashboard');

        return Command::SUCCESS;
    }
}
