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

        $actualHeaders = $this->normalizeHeaders($data[0]);
        $missing = array_values(array_diff($expectedHeaders, $actualHeaders));
        $extra = array_values(array_diff($actualHeaders, $expectedHeaders));
        $orderedHeadersMatch = array_slice($actualHeaders, 0, count($expectedHeaders)) === $expectedHeaders;

        return [
            'valid' => empty($missing) && empty($extra) && $orderedHeadersMatch,
            'missing' => $missing,
            'extra' => $extra,
            'actual' => $actualHeaders,
            'expected' => $expectedHeaders,
        ];
    }

    public function validateAllSheets(): array
    {
        $sheetNames = array_keys($this->schemas);
        try {
            $headersBySheet = $this->sheets->getSheetHeaders($sheetNames);
        } catch (\Throwable $e) {
            $message = 'Google Sheets tidak dapat dibaca: ' . $e->getMessage();
            return array_fill_keys($sheetNames, [
                'valid' => false,
                'error' => $message,
            ]);
        }
        $results = [];
        foreach ($sheetNames as $sheetName) {
            $expectedHeaders = $this->schemas[$sheetName];
            $data = $headersBySheet[$sheetName] ?? [];
            if (empty($data) || empty($data[0])) {
                $results[$sheetName] = [
                    'valid' => false,
                    'error' => "Sheet {$sheetName} has no headers or is empty",
                ];
                continue;
            }

            $actualHeaders = $this->normalizeHeaders($data[0]);
            $missing = array_values(array_diff($expectedHeaders, $actualHeaders));
            $extra = array_values(array_diff($actualHeaders, $expectedHeaders));
            $orderedHeadersMatch = array_slice($actualHeaders, 0, count($expectedHeaders)) === $expectedHeaders;
            $results[$sheetName] = [
                'valid' => empty($missing) && empty($extra) && $orderedHeadersMatch,
                'missing' => $missing,
                'extra' => $extra,
                'actual' => $actualHeaders,
                'expected' => $expectedHeaders,
            ];
        }
        return $results;
    }

    public function fixSheetHeaders(string $sheetName): bool
    {
        $expectedHeaders = $this->schemas[$sheetName] ?? null;
        if (!$expectedHeaders) {
            return false;
        }

        if ($sheetName === 'MPR') {
            return $this->migrateMprSchema($expectedHeaders);
        }

        $spreadsheetId = config('google.spreadsheet_id');
        $service = $this->sheets->getSheetsService();

        try {
            // Ensure the sheet exists before reading or updating
            $this->sheets->createSheetIfNotExists($sheetName);

            // Read current headers to determine what exists
            $currentData = $this->sheets->getRange($sheetName, 'A1:ZZ1', false);
            $currentHeaders = !empty($currentData[0]) ? $this->normalizeHeaders($currentData[0]) : [];

            $missing = array_values(array_diff($expectedHeaders, $currentHeaders));

            // Remove legacy trailing columns after the canonical schema has
            // been established. This keeps validation strict without moving
            // existing canonical columns or their data.
            if (
                count($currentHeaders) > count($expectedHeaders)
                && array_slice($currentHeaders, 0, count($expectedHeaders)) === $expectedHeaders
            ) {
                $sheetId = $this->getSheetIdByName($spreadsheetId, $sheetName, $service);
                if ($sheetId !== null) {
                    $deleteRequest = new \Google\Service\Sheets\Request([
                        'deleteDimension' => [
                            'range' => [
                                'sheetId' => $sheetId,
                                'dimension' => 'COLUMNS',
                                'startIndex' => count($expectedHeaders),
                                'endIndex' => count($currentHeaders),
                            ],
                        ],
                    ]);
                    $service->spreadsheets->batchUpdate(
                        $spreadsheetId,
                        new \Google\Service\Sheets\BatchUpdateSpreadsheetRequest(['requests' => [$deleteRequest]])
                    );
                    $this->sheets->clearCache($sheetName);
                    return true;
                }
            }

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
     * Migrate the legacy MPR layout while keeping existing row values aligned.
     */
    protected function migrateMprSchema(array $expectedHeaders): bool
    {
        $spreadsheetId = config('google.spreadsheet_id');
        $service = $this->sheets->getSheetsService();
        $legacyAliases = [
            'Requestor Name' => ['Manager Name'],
            'Requestor Email' => ['Manager Email'],
            'Entity' => ['Company'],
        ];

        try {
            $data = $this->sheets->getRange('MPR', 'A:ZZ', false);
            if (empty($data)) {
                return false;
            }

            $currentHeaders = !empty($data[0]) ? $this->normalizeHeaders($data[0]) : [];
            $headerLookup = [];
            foreach ($currentHeaders as $index => $header) {
                if ($header === '') {
                    continue;
                }
                $headerLookup[strtolower($header)][] = $index;
            }

            $rewrittenRows = [
                $expectedHeaders,
            ];

            foreach (array_slice($data, 1) as $row) {
                $newRow = [];
                foreach ($expectedHeaders as $header) {
                    $candidateHeaders = array_merge($legacyAliases[$header] ?? [], [$header]);
                    $value = '';

                    foreach ($candidateHeaders as $candidate) {
                        $candidateKey = strtolower(trim((string) $candidate));
                        foreach ($headerLookup[$candidateKey] ?? [] as $index) {
                            if (isset($row[$index]) && trim((string) $row[$index]) !== '') {
                                $value = $row[$index];
                                break 2;
                            }
                        }
                    }

                    if ($header === 'Approval Division' && $value === '') {
                        $divisionCandidates = array_merge(['Division'], ['Divisi']);
                        foreach ($divisionCandidates as $candidate) {
                            $candidateKey = strtolower(trim((string) $candidate));
                            foreach ($headerLookup[$candidateKey] ?? [] as $index) {
                                if (isset($row[$index]) && trim((string) $row[$index]) !== '') {
                                    $value = $row[$index];
                                    break 2;
                                }
                            }
                        }
                    }

                    $newRow[] = $value;
                }

                $rewrittenRows[] = $newRow;
            }

            $sheetWidth = max(count($expectedHeaders), count($currentHeaders));
            $endColumnLetter = $this->columnIndexToLetter($sheetWidth);
            $range = 'MPR!A1:' . $endColumnLetter . count($rewrittenRows);

            $service->spreadsheets_values->update(
                $spreadsheetId,
                $range,
                new \Google\Service\Sheets\ValueRange(['values' => $rewrittenRows]),
                ['valueInputOption' => 'USER_ENTERED']
            );

            if (count($currentHeaders) > count($expectedHeaders)) {
                $sheetId = $this->getSheetIdByName($spreadsheetId, 'MPR', $service);
                if ($sheetId !== null) {
                    $deleteRequest = new \Google\Service\Sheets\Request([
                        'deleteDimension' => [
                            'range' => [
                                'sheetId' => $sheetId,
                                'dimension' => 'COLUMNS',
                                'startIndex' => count($expectedHeaders),
                                'endIndex' => count($currentHeaders),
                            ],
                        ],
                    ]);
                    $service->spreadsheets->batchUpdate(
                        $spreadsheetId,
                        new \Google\Service\Sheets\BatchUpdateSpreadsheetRequest(['requests' => [$deleteRequest]])
                    );
                }
            }

            $this->sheets->clearCache('MPR');
            return true;
        } catch (\Throwable $e) {
            Log::error('Failed to migrate MPR schema: ' . $e->getMessage());
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

    private function normalizeHeaders(array $headers): array
    {
        $headers = array_map(static fn($header): string => trim((string) $header), $headers);
        while (!empty($headers) && end($headers) === '') {
            array_pop($headers);
        }
        return array_values($headers);
    }
}
