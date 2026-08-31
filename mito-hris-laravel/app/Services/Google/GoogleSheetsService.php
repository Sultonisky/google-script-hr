<?php

namespace App\Services\Google;

use Google\Service\Sheets;
use Google\Service\Sheets\ValueRange;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class GoogleSheetsService
{
    protected ?GoogleClientFactory $factory = null;
    protected string $spreadsheetId = '';
    protected int $cacheTtl = 60;

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

        // Check version stamp — if sheet was updated since last cache, skip cache
        if ($useCache && Cache::has($cacheKey)) {
            $versionKey    = "sheets_ver_{$this->spreadsheetId}_{$sheetName}";
            $cachedVersion = Cache::get("sheets_ver_at_{$cacheKey}", 0);
            $currentVersion = Cache::get($versionKey, 0);
            if ($cachedVersion === $currentVersion) {
                return Cache::get($cacheKey);
            }
            // Version mismatch — stale data, fall through to fresh fetch
        }

        try {
            $service = $this->factory->getSheetsService();
            $fullRange = "{$sheetName}!{$range}";
            $response = $service->spreadsheets_values->get($this->spreadsheetId, $fullRange);
            $values = $response->getValues() ?? [];

            if ($useCache) {
                $versionKey = "sheets_ver_{$this->spreadsheetId}_{$sheetName}";
                $version    = Cache::get($versionKey, 0);
                Cache::put($cacheKey, $values, $this->cacheTtl);
                Cache::put("sheets_ver_at_{$cacheKey}", $version, $this->cacheTtl);
            }

            return $values;
        } catch (\Throwable $e) {
            Log::error("GoogleSheetsService::getRange error on {$sheetName}: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Run a lightweight Google Sheets health check without exposing credentials.
     */
    public function healthCheck(array $sheetNames = ['Employee', 'data_kandidat', 'MPR']): array
    {
        if (empty($this->spreadsheetId)) {
            return [
                'success' => false,
                'status' => 'unhealthy',
                'message' => 'Spreadsheet ID belum dikonfigurasi.',
                'sheets' => [],
            ];
        }

        $healthySheets = [];

        foreach ($sheetNames as $sheetName) {
            if (empty($sheetName) || ! is_string($sheetName)) {
                continue;
            }

            try {
                $values = $this->getRange($sheetName, 'A1:Z1', false);
                if (! is_array($values) || empty($values) || empty($values[0])) {
                    return [
                        'success' => false,
                        'status' => 'unhealthy',
                        'message' => "Sheet {$sheetName} tidak dapat diakses atau belum memiliki header.",
                        'sheets' => $healthySheets,
                    ];
                }

                $healthySheets[] = $sheetName;
            } catch (\Throwable $e) {
                Log::warning("GoogleSheetsService::healthCheck failed for {$sheetName}: " . $e->getMessage());

                return [
                    'success' => false,
                    'status' => 'unhealthy',
                    'message' => 'Koneksi Google Sheets gagal saat health check.',
                    'sheets' => $healthySheets,
                ];
            }
        }

        return [
            'success' => true,
            'status' => 'healthy',
            'message' => 'Koneksi Google Sheets berhasil.',
            'sheets' => $healthySheets,
        ];
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
     * Get raw values from a specific range (alias for getRange).
     */
    public function getValues(string $range, bool $useCache = true): array
    {
        // Parse sheet name from range (e.g., "data_kandidat!A1:Z1")
        $parts = explode('!', $range);
        $sheetName = $parts[0] ?? '';
        $rangePart = $parts[1] ?? 'A:ZZ';
        return $this->getRange($sheetName, $rangePart, $useCache);
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
     * Prevent spreadsheet formula injection without corrupting legitimate data.
     * Google Sheets only treats values beginning with '=' as formulas. Numeric and
     * phone-like values such as +628..., -123, and @username are preserved.
     */
    private function sanitizeCellValue(mixed $value): string
    {
        if ($value === null || $value === false) {
            return '';
        }

        if (is_array($value) || is_object($value)) {
            return '';
        }

        if (is_bool($value)) {
            return $value ? 'TRUE' : 'FALSE';
        }

        $string = trim((string) $value);

        if ($string === '') {
            return '';
        }

        if (str_starts_with($string, '=')) {
            return "'{$string}";
        }

        return $string;
    }

    /**
     * Sanitize a row array so every value is a scalar string accepted by the
     * Google Sheets API v4. null, bool, array and object values are coerced.
     */
    private function sanitizeRow(array $row): array
    {
        return array_values(array_map(function ($value) {
            return $this->sanitizeCellValue($value);
        }, $row));
    }

    /**
     * Sanitize nested row data for range updates.
     */
    private function sanitizeValues(array $values): array
    {
        return array_map(function ($row) {
            if (!is_array($row)) {
                return [$this->sanitizeCellValue($row)];
            }

            return $this->sanitizeRow($row);
        }, $values);
    }

    /**
     * Append a row to the sheet.
     */
    public function appendRow(string $sheetName, array $rowValues): bool
    {
        try {
            $service = $this->factory->getSheetsService();
            $body = new ValueRange([
                'values' => [$this->sanitizeRow($rowValues)]
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
                'values' => [$this->sanitizeRow($rowValues)]
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
                'values' => $this->sanitizeValues($values)
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
     * Invalidate ALL cached data for a sheet (any range variant).
     * Strategy: store a per-sheet version counter; all reads check it.
     * On write, increment the counter → all prior cache entries become invalid.
     */
    public function clearCache(string $sheetName): void
    {
        // Primary range key used by getRowsAsAssoc / getRange("A:ZZ")
        Cache::forget("sheets_{$this->spreadsheetId}_{$sheetName}_" . md5('A:ZZ'));

        // Row-level range keys written by update() — pattern "A{n}:ZZ{n}"
        // We cannot enumerate all row numbers, so we use a version stamp:
        // bump the version, which is checked in getRange() before returning cached data.
        $versionKey = "sheets_ver_{$this->spreadsheetId}_{$sheetName}";
        Cache::put($versionKey, (Cache::get($versionKey, 0) + 1), 86400);

        // Also forget the "1:1" header row cache used by update() & moveToSheet()
        Cache::forget("sheets_{$this->spreadsheetId}_{$sheetName}_" . md5('1:1'));
    }

    public function clearAllSheets(): void
    {
        $spreadsheetId = config('google.spreadsheet_id');
        $sheetsConfig  = config('google.sheets', []);
        $service       = $this->factory->getSheetsService();

        foreach ($sheetsConfig as $key => $sheetName) {
            if (empty($sheetName)) continue;
            try {
                // Read total rows to know how far to clear
                $response = $service->spreadsheets_values->get($spreadsheetId, "{$sheetName}!A:A");
                $totalRows = count($response->getValues() ?? []);
                if ($totalRows <= 1) continue; // Only header, nothing to clear

                $range     = "{$sheetName}!A2:ZZ{$totalRows}";
                $clearBody = new \Google\Service\Sheets\ClearValuesRequest();
                $service->spreadsheets_values->clear($spreadsheetId, $range, $clearBody);
                $this->clearCache($sheetName);
            } catch (\Throwable $e) {
                Log::warning("GoogleSheetsService::clearAllSheets({$sheetName}): " . $e->getMessage());
            }
        }
    }

    public function getSheetsService()
    {
        return $this->factory->getSheetsService();
    }

    /**
     * Pastikan tab sheet ada di Spreadsheet. Jika belum ada, buat sheet baru.
     */
    public function createSheetIfNotExists(string $sheetName): bool
    {
        try {
            $service = $this->factory->getSheetsService();
            $spreadsheet = $service->spreadsheets->get($this->spreadsheetId);
            $sheetExists = false;
            foreach ($spreadsheet->getSheets() as $sheet) {
                if ($sheet->getProperties()->getTitle() === $sheetName) {
                    $sheetExists = true;
                    break;
                }
            }

            if (!$sheetExists) {
                $addSheetRequest = new \Google\Service\Sheets\Request([
                    'addSheet' => [
                        'properties' => [
                            'title' => $sheetName,
                        ]
                    ]
                ]);
                $batchUpdateRequest = new \Google\Service\Sheets\BatchUpdateSpreadsheetRequest([
                    'requests' => [$addSheetRequest]
                ]);
                $service->spreadsheets->batchUpdate($this->spreadsheetId, $batchUpdateRequest);
            }
            return true;
        } catch (\Throwable $e) {
            Log::warning("GoogleSheetsService::createSheetIfNotExists({$sheetName}): " . $e->getMessage());
            return false;
        }
    }

    /**
     * Pastikan sheet memiliki baris header sesuai $headers.
     * Jika sheet kosong, tulis header di baris 1.
     * Jika sudah ada header, tidak melakukan apa-apa (idempotent).
     * 1:1 dengan GAS ensureStatusSheetHeaders_()
     */
    public function ensureSheetHeaders(string $sheetName, array $headers): void
    {
        try {
            $this->createSheetIfNotExists($sheetName);
            $existing = $this->getRange($sheetName, '1:1', false);
            if (!empty($existing[0]) && !empty(array_filter($existing[0]))) {
                // Header sudah ada — tidak overwrite
                return;
            }
            // Sheet kosong atau baris 1 kosong — tulis header
            $service   = $this->factory->getSheetsService();
            $range     = "{$sheetName}!A1";
            $body      = new ValueRange(['values' => [$this->sanitizeRow($headers)]]);
            $params    = ['valueInputOption' => 'USER_ENTERED'];
            $service->spreadsheets_values->update($this->spreadsheetId, $range, $body, $params);
            $this->clearCache($sheetName);
        } catch (\Throwable $e) {
            Log::warning("GoogleSheetsService::ensureSheetHeaders({$sheetName}): " . $e->getMessage());
        }
    }
}
