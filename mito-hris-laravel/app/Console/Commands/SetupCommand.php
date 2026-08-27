<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;

class SetupCommand extends Command
{
    protected $signature = 'mito:setup {--skip-sheets : Skip Google Sheets setup} {--skip-dummy : Skip dummy data seeding} {--force : Run destructive operations without confirmation}';
    protected $description = 'Complete MITO HRIS setup: environment check, sheets validation, user seeding, and optional dummy data';

    public function handle(): int
    {
        $this->info('===========================================================');
        $this->info('  MITO HRIS — Setup Tool');
        $this->info('===========================================================');

        $env = config('app.env');
        $this->line("Environment: {$env}");

        $this->line("\n1. Running health check...");
        $this->call('mito:health-check');

        if (!$this->option('skip-sheets')) {
            $this->line("\n2. Setting up Google Sheets schema...");
            $this->call('mito:setup-sheets', ['--fix' => true]);
        }

        $this->line("\n3. Seeding users...");
        $this->call('mito:seed-users', ['--force' => $this->option('force')]);

        $this->line("\n4. Seeding MPR Requestors (Manpower accounts)...");
        $this->call('mito:seed-mpr-requestors', ['--force' => $this->option('force')]);

        if (!$this->option('skip-dummy')) {
            if ($env === 'local' || $this->option('force')) {
                $this->line("\n4. Seeding dummy data...");
                $this->call('mito:seed-dummy', ['--count' => 50, '--force' => $this->option('force')]);
            } else {
                $this->warn("Skipping dummy data (not in local environment). Use --force to override.");
            }
        }

        $this->info("\nSetup completed successfully.");
        $this->info('Next steps:');
        $this->line('  - Internal HR login : admin@mito.co.id / password123');
        $this->line('  - MPR Manpower login : manager.msi@mito.co.id / password123');
        $this->line('  - Visit /hr/dashboard to access the HR system');
        $this->line('  - Visit /hr/mpr (after Manpower login) to access MPR portal');
        return Command::SUCCESS;
    }
}