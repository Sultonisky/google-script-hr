<?php

namespace App\Services;

use App\Services\Google\GoogleSheetsService;
use Illuminate\Support\Facades\Log;

class SchemaValidationService
{
    protected GoogleSheetsService $sheets;
    protected array $schemas;

    public function __construct(GoogleSheetsService $sheets)
    {
        $this->sheets = $sheets;
        $this->schemas = config('hris.schemas', []);
    }

    public function validateSheet(string $sheetName): array
    {
        $expectedHeaders = $this->schemas[$sheetName] ?? null;
        if (!$expectedHeaders) {
            return ['valid' => false, 'error' => "No schema defined for sheet: {$sheetName}"];
        }

        $data = $this->sheets->getRange($sheetName, 'A1:ZZ1', false);
        if (empty($data) || empty($data[0])) {
            return ['valid' => false, 'error' => "Sheet {$sheetName} has no headers or is empty"];
        }

        $actualHeaders = array_map('trim', $data[0]);
        // Only check for missing expected headers — extra columns in the sheet are tolerated
        // because GAS appends extra columns over time and we must not flag them as errors.
        $missing = array_values(array_diff($expectedHeaders, $actualHeaders));

        return [
            'valid' => empty($missing),
            'missing' => $missing,
            'extra' => [], // Extra columns are tolerated, not reported as errors
            'actual' => $actualHeaders,
            'expected' => $expectedHeaders,
        ];
    }

    public function validateAllSheets(): array
    {
        $results = [];
        foreach (array_keys($this->schemas) as $sheetName) {
            $results[$sheetName] = $this->validateSheet($sheetName);
        }
        return $results;
    }

    public function fixSheetHeaders(string $sheetName): bool
    {
        $expectedHeaders = $this->schemas[$sheetName] ?? null;
        if (!$expectedHeaders) {
            return false;
        }

        $spreadsheetId = config('google.spreadsheet_id');
        $service = $this->sheets->getSheetsService();

        try {
            // Ensure the sheet exists before reading or updating
            $this->sheets->createSheetIfNotExists($sheetName);

            // Read current headers to determine what exists
            $currentData = $this->sheets->getRange($sheetName, 'A1:ZZ1', false);
            $currentHeaders = !empty($currentData[0]) ? array_map('trim', $currentData[0]) : [];

            // Append any missing expected headers rather than overwriting the entire header row.
            // This preserves existing columns (including extra/legacy columns) and only adds
            // what is missing from the expected schema.
            $missing = array_values(array_diff($expectedHeaders, $currentHeaders));

            if (empty($missing)) {
                $this->sheets->clearCache($sheetName);
                return true;
            }

            // Determine starting column for appending
            $startCol = count($currentHeaders) + 1;
            $endCol   = $startCol + count($missing) - 1;

            // ── Expand the grid if needed ────────────────────────────────
            // Google Sheets rejects writes beyond the current column count.
            // We must call appendDimension (batchUpdate) to widen the sheet
            // BEFORE writing the new header values.
            $sheetId = $this->getSheetIdByName($spreadsheetId, $sheetName, $service);
            if ($sheetId !== null) {
                $columnsNeeded = $endCol - count($currentHeaders);
                if ($columnsNeeded > 0) {
                    $appendDimRequest = new \Google\Service\Sheets\Request([
                        'appendDimension' => [
                            'sheetId'   => $sheetId,
                            'dimension' => 'COLUMNS',
                            'length'    => $columnsNeeded,
                        ],
                    ]);
                    $batchBody = new \Google\Service\Sheets\BatchUpdateSpreadsheetRequest([
                        'requests' => [$appendDimRequest],
                    ]);
                    $service->spreadsheets->batchUpdate($spreadsheetId, $batchBody);
                }
            }

            // Now write the missing header names into the newly created columns
            $colLetter    = $this->columnIndexToLetter($startCol);
            $endColLetter = $this->columnIndexToLetter($endCol);
            $range = "{$sheetName}!{$colLetter}1:{$endColLetter}1";
            $body  = new \Google\Service\Sheets\ValueRange(['values' => [$missing]]);
            $params = ['valueInputOption' => 'USER_ENTERED'];
            $service->spreadsheets_values->update($spreadsheetId, $range, $body, $params);

            $this->sheets->clearCache($sheetName);
            return true;
        } catch (\Throwable $e) {
            Log::error("Failed to fix headers for {$sheetName}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Convert 1-indexed column number to spreadsheet letter (e.g. 1 → A, 27 → AA).
     */
    protected function columnIndexToLetter(int $index): string
    {
        $letter = '';
        while ($index > 0) {
            $mod = ($index - 1) % 26;
            $letter = chr(65 + $mod) . $letter;
            $index = (int)(($index - $mod) / 26);
        }
        return $letter;
    }

    protected function getSheetProperties($spreadsheetId, $sheetName)
    {
        $service = $this->sheets->getSheetsService();
        $spreadsheet = $service->spreadsheets->get($spreadsheetId);
        foreach ($spreadsheet->getSheets() as $sheet) {
            if ($sheet->getProperties()->getTitle() === $sheetName) {
                return $sheet;
            }
        }
        return null;
    }

    /**
     * Get the numeric sheetId for a named sheet tab.
     * Returns null if not found.
     */
    protected function getSheetIdByName(string $spreadsheetId, string $sheetName, $service): ?int
    {
        try {
            $spreadsheet = $service->spreadsheets->get($spreadsheetId);
            foreach ($spreadsheet->getSheets() as $sheet) {
                if ($sheet->getProperties()->getTitle() === $sheetName) {
                    return (int) $sheet->getProperties()->getSheetId();
                }
            }
        } catch (\Throwable $e) {
            Log::warning("getSheetIdByName({$sheetName}): " . $e->getMessage());
        }
        return null;
    }
}