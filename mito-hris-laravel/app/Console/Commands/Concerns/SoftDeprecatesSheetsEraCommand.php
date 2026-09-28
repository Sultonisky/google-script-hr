<?php

namespace App\Console\Commands\Concerns;

use App\Support\HrisDataDriver;

/**
 * Soft-disable Sheets-era Artisan commands when SoT is already Postgres.
 * Commands stay in the codebase as backup/reference; use --force to run.
 */
trait SoftDeprecatesSheetsEraCommand
{
    /**
     * @return bool true = abort handle()
     */
    protected function refuseSheetsEraUnlessForced(string $hint = ''): bool
    {
        if (! HrisDataDriver::usesPgsql()) {
            return false;
        }

        $forced = $this->hasForceOption() && (bool) $this->option('force');

        if ($forced) {
            $this->warn('Sheets-era command dijalankan dengan --force (HRIS_DATA_DRIVER=pgsql).');
            $this->line('SoT tetap database. Ini backup/reference — waspadai overwrite archive Sheets.');
            if ($hint !== '') {
                $this->line($hint);
            }

            return false;
        }

        $this->warn('DISABLED: Command Sheets-era. HRIS_DATA_DRIVER=pgsql (SoT = database).');
        $this->line('Tidak dihapus — disimpan sebagai backup/reference untuk prod/rollback.');
        if ($hint !== '') {
            $this->line($hint);
        }
        $this->line('Override: --force. Prefer: mito:etl-db-to-sheets / seed via DB repos / migrate.');

        return true;
    }

    private function hasForceOption(): bool
    {
        try {
            $this->option('force');

            return true;
        } catch (\Throwable) {
            return false;
        }
    }
}
