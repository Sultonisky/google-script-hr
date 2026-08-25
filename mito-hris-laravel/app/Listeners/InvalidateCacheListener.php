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
        // Clear cached data across ALL candidate and employee sheets
        $sheetKeys = [
            'candidates',
            'candidates_hold',
            'candidates_blacklist',
            'candidates_accepted',
            'candidates_probation',
            'employees',
            'audit_log',
        ];

        foreach ($sheetKeys as $key) {
            $sheetName = config("google.sheets.{$key}");
            if ($sheetName) {
                $this->sheets->clearCache($sheetName);
            }
        }
    }
}
