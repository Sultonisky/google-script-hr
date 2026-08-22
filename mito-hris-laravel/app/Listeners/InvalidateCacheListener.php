<?php

namespace App\Listeners;

use App\Services\Google\GoogleSheetsService;

class InvalidateCacheListener
{
    protected GoogleSheetsService $sheets;

    public function __construct(GoogleSheetsService $sheets)
    {
        $this->sheets = $sheets;
    }

    public function handle(object $event): void
    {
        // Clear cached data across all primary sheets
        $this->sheets->clearCache(config('google.sheets.candidates', 'data_kandidat'));
        $this->sheets->clearCache(config('google.sheets.employees', 'Employee'));
        $this->sheets->clearCache(config('google.sheets.audit_log', 'Audit_Log'));
    }
}
