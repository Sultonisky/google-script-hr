<?php

namespace App\Services\Google;

use Google\Service\Sheets;
use Google\Service\Sheets\ValueRange;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class GoogleSheetsService
{
    protected GoogleClientFactory $factory;
    protected string $spreadsheetId;
    protected int $cacheTtl;

    public function __construct(GoogleClientFactory $factory)
    {
        $this->factory = $factory;
        $this->spreadsheetId = config('google.spreadsheet_id', '');
        $this->cacheTtl = (int) config('google.cache_ttl', 60);
    }

    /**
     * Get raw values from a sheet range.
     */
    public function getRange(string $sheetName, string $range = 'A:ZZ', bool $useCache = true): array
    {
        $cacheKey = "sheets_{$this->spreadsheetId}_{$sheetName}_" . md5($range);

        if ($useCache && Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        try {
            $service = $this->factory->getSheetsService();
            $fullRange = "{$sheetName}!{$range}";
            $response = $service->spreadsheets_values->get($this->spreadsheetId, $fullRange);
            $values = $response->getValues() ?? [];

            if ($useCache) {
                Cache::put($cacheKey, $values, $this->cacheTtl);
            }

            return $values;
        } catch (\Throwable $e) {
            Log::error("GoogleSheetsService::getRange error on {$sheetName}: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get all rows as associative arrays using the first row as keys.
     */
    public function getRowsAsAssoc(string $sheetName, bool $useCache = true): array
    {
        $data = $this->getRange($sheetName, 'A:ZZ', $useCache);
        if (empty($data) || count($data) < 2) {
            return [];
        }

        $headers = array_map('trim', $data[0]);
        $rows = [];

        for ($i = 1; $i < count($data); $i++) {
            $row = $data[$i];
            $assoc = [];
            foreach ($headers as $colIndex => $header) {
                if ($header === '') continue;
                $assoc[$header] = $row[$colIndex] ?? '';
            }
            // Store 1-indexed sheet row number for quick updates
            $assoc['_row_number'] = $i + 1;
            $rows[] = $assoc;
        }

        return $rows;
    }

    /**
     * Find a single row by a column name and value.
     */
    public function findRowBy(string $sheetName, string $columnName, string $value): ?array
    {
        $rows = $this->getRowsAsAssoc($sheetName);
        foreach ($rows as $row) {
            if (isset($row[$columnName]) && trim($row[$columnName]) === trim($value)) {
                return $row;
            }
        }
        return null;
    }

    /**
     * Append a row to the sheet.
     */
    public function appendRow(string $sheetName, array $rowValues): bool
    {
        try {
            $service = $this->factory->getSheetsService();
            $body = new ValueRange([
                'values' => [$rowValues]
            ]);

            $params = ['valueInputOption' => 'USER_ENTERED'];
            $service->spreadsheets_values->append($this->spreadsheetId, "{$sheetName}!A:A", $body, $params);

            $this->clearCache($sheetName);
            return true;
        } catch (\Throwable $e) {
            Log::error("GoogleSheetsService::appendRow error on {$sheetName}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Update an entire row by 1-indexed row number.
     */
    public function updateRow(string $sheetName, int $rowNumber, array $rowValues): bool
    {
        try {
            $service = $this->factory->getSheetsService();
            $range = "{$sheetName}!A{$rowNumber}";
            $body = new ValueRange([
                'values' => [$rowValues]
            ]);

            $params = ['valueInputOption' => 'USER_ENTERED'];
            $service->spreadsheets_values->update($this->spreadsheetId, $range, $body, $params);

            $this->clearCache($sheetName);
            return true;
        } catch (\Throwable $e) {
            Log::error("GoogleSheetsService::updateRow error on {$sheetName} row {$rowNumber}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Update specific cells or a range.
     */
    public function updateRange(string $sheetName, string $range, array $values): bool
    {
        try {
            $service = $this->factory->getSheetsService();
            $fullRange = "{$sheetName}!{$range}";
            $body = new ValueRange([
                'values' => $values
            ]);

            $params = ['valueInputOption' => 'USER_ENTERED'];
            $service->spreadsheets_values->update($this->spreadsheetId, $fullRange, $body, $params);

            $this->clearCache($sheetName);
            return true;
        } catch (\Throwable $e) {
            Log::error("GoogleSheetsService::updateRange error on {$sheetName} {$range}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Invalidate cached data for a sheet.
     */
    public function clearCache(string $sheetName): void
    {
        // Flush tags or cache keys for sheet
        Cache::forget("sheets_{$this->spreadsheetId}_{$sheetName}_" . md5('A:ZZ'));
    }
}
