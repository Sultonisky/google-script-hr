<?php

namespace App\Console\Commands;

use App\Services\Google\GoogleSheetsService;
use App\Support\PermissionCatalog;
use Illuminate\Console\Command;

/**
 * mito:seed-permissions
 *
 * Idempotently provisions the Permissions Google Sheet from the authoritative
 * static catalog defined in App\Support\PermissionCatalog::all().
 *
 * Rules:
 *  - Only appends rows for catalog entries that do not yet exist in the sheet.
 *  - Never deletes, truncates, or rewrites the sheet.
 *  - Preserves any existing row values (including Status = inactive).
 *  - --dry-run performs zero writes and reports what would change.
 *  - Fails with a clear error when the sheet is inaccessible or the header
 *    row is missing, rather than partially corrupting data.
 */
class SeedPermissionsCommand extends Command
{
    protected $signature = 'mito:seed-permissions
                            {--dry-run : Report missing entries without writing to the sheet}';

    protected $description = 'Provision the Permissions sheet from the application permission catalog';

    /**
     * Canonical 7-column schema for the Permissions sheet.
     * Matches config/hris.php schemas.Permissions and the header row that
     * mito:setup-sheets --fix already writes.
     */
    private const EXPECTED_HEADERS = [
        'Permission Key',
        'Name',
        'Description',
        'Group',
        'Status',
        'Created At',
        'Updated At',
    ];

    public function handle(GoogleSheetsService $sheets): int
    {
        $sheetName = config('google.sheets.permissions', 'Permissions');
        $dryRun    = (bool) $this->option('dry-run');
        $mode      = $dryRun ? 'DRY-RUN' : 'APPLY';

        $this->info("Permission catalog seed [{$mode}] — sheet: {$sheetName}");

        // ── 1. Verify the sheet header row ────────────────────────────────────
        $headerCheck = $this->verifyHeaders($sheets, $sheetName);
        if ($headerCheck !== null) {
            $this->error($headerCheck);
            $this->line('  Run: php artisan mito:setup-sheets --fix');
            return Command::FAILURE;
        }

        // ── 2. Read existing rows (no cache — always fresh for seeding) ───────
        try {
            $existingRows = $sheets->getRowsAsAssoc($sheetName, false);
        } catch (\Throwable $e) {
            $this->error('Failed to read existing Permissions rows: ' . $e->getMessage());
            return Command::FAILURE;
        }

        // Build a lookup of permission keys that already exist in the sheet.
        $existingKeys = [];
        foreach ($existingRows as $row) {
            $key = trim((string) ($row['Permission Key'] ?? ''));
            if ($key !== '') {
                $existingKeys[$key] = true;
            }
        }

        // ── 3. Determine which catalog entries are missing ────────────────────
        $catalog   = PermissionCatalog::all();
        $timestamp = now()->timezone('Asia/Jakarta')->format('Y-m-d H:i:s');

        $toInsert = [];
        foreach ($catalog as $entry) {
            $key = trim((string) ($entry['key'] ?? ''));
            if ($key === '') {
                continue;
            }
            if (isset($existingKeys[$key])) {
                continue; // already present — skip
            }
            $toInsert[] = [
                $key,
                (string) ($entry['name']        ?? ''),
                (string) ($entry['description'] ?? ''),
                (string) ($entry['group']       ?? ''),
                'active',
                $timestamp,
                $timestamp,
            ];
        }

        // ── 4. Report ─────────────────────────────────────────────────────────
        $totalCatalog  = count($catalog);
        $totalExisting = count($existingKeys);
        $totalMissing  = count($toInsert);
        $totalSkipped  = $totalCatalog - $totalMissing;

        $this->line("  Catalog permissions  : {$totalCatalog}");
        $this->line("  Already in sheet     : {$totalExisting}");
        $this->line("  To be created        : {$totalMissing}");
        $this->line("  Skipped (existing)   : {$totalSkipped}");

        if ($totalMissing === 0) {
            $this->info('Permissions sheet is already up to date. Nothing to do.');
            return Command::SUCCESS;
        }

        if ($dryRun) {
            $this->line('');
            $this->warn('DRY-RUN — the following rows would be appended:');
            foreach ($toInsert as $row) {
                $this->line("  [{$row[0]}] {$row[1]} ({$row[3]})");
            }
            $this->info("DRY-RUN complete. {$totalMissing} row(s) would be created.");
            return Command::SUCCESS;
        }

        // ── 5. Append missing rows in one batch call ──────────────────────────
        try {
            $success = $sheets->appendRows($sheetName, $toInsert);
        } catch (\Throwable $e) {
            $this->error('Failed to append rows to Permissions sheet: ' . $e->getMessage());
            return Command::FAILURE;
        }

        if (!$success) {
            $this->error('appendRows returned false — check Google Sheets API logs.');
            return Command::FAILURE;
        }

        $this->info("Permission catalog seed complete. {$totalMissing} row(s) created.");
        return Command::SUCCESS;
    }

    /**
     * Verify the sheet has the expected 7-column header row.
     *
     * Returns null on success, or an error string on failure.
     * Does NOT write anything — read-only.
     */
    private function verifyHeaders(GoogleSheetsService $sheets, string $sheetName): ?string
    {
        try {
            $raw = $sheets->getRange($sheetName, 'A1:G1', false);
        } catch (\Throwable $e) {
            return "Cannot read Permissions sheet: " . $e->getMessage();
        }

        if (empty($raw) || empty($raw[0])) {
            return "Permissions sheet has no header row. "
                . "The sheet tab may not exist or may be completely empty.";
        }

        $actualHeaders = array_map('trim', $raw[0]);

        // Pad to expected length so comparison works even if trailing cells
        // were returned as an empty array by the Sheets API.
        while (count($actualHeaders) < count(self::EXPECTED_HEADERS)) {
            $actualHeaders[] = '';
        }

        $missing = array_values(array_diff(self::EXPECTED_HEADERS, $actualHeaders));
        if (!empty($missing)) {
            return "Permissions sheet header mismatch. "
                . "Missing columns: " . implode(', ', $missing) . ". "
                . "Expected: " . implode(', ', self::EXPECTED_HEADERS);
        }

        return null;
    }
}
