<?php

namespace Tests\Feature;

use App\Services\Google\GoogleSheetsService;
use App\Support\PermissionCatalog;
use Mockery;
use Tests\TestCase;

/**
 * Tests for mito:seed-permissions
 *
 * All tests mock GoogleSheetsService so no real Google Sheets connection
 * is required. The production sheet is never touched by this test suite.
 *
 * Test criteria covered:
 *  1. dry-run does not write
 *  2. missing permissions are detected
 *  3. missing permissions are inserted (batch)
 *  4. existing permission is not duplicated
 *  5. existing inactive status is preserved
 *  6. command is idempotent (second run is a no-op)
 *  7. all PermissionCatalog keys can be seeded (sheet starts empty)
 *  8. malformed/unavailable sheet fails safely
 */
class SeedPermissionsCommandTest extends TestCase
{
    /** The expected 7 header values for the Permissions sheet. */
    private const HEADERS = [
        'Permission Key', 'Name', 'Description', 'Group', 'Status', 'Created At', 'Updated At',
    ];

    /** Sheet tab name used by the command (matches config default). */
    private const SHEET = 'Permissions';

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Build a mock GoogleSheetsService that returns $headerRow from getRange
     * and $dataRows from getRowsAsAssoc.
     *
     * @param  array       $dataRows    Rows returned by getRowsAsAssoc (assoc arrays).
     * @param  array|null  $appendedRows  Reference filled with rows passed to appendRows.
     * @param  array       $headerRow   Raw header row returned by getRange.
     */
    private function sheetsWithRows(
        array $dataRows,
        ?array &$appendedRows = null,
        array $headerRow = self::HEADERS,
    ): GoogleSheetsService {
        $sheets = Mockery::mock(GoogleSheetsService::class);

        // Header check — always returns the valid header row by default.
        $sheets->shouldReceive('getRange')
            ->with(self::SHEET, 'A1:G1', false)
            ->andReturn([$headerRow])
            ->byDefault();

        // Data rows read.
        $sheets->shouldReceive('getRowsAsAssoc')
            ->with(self::SHEET, false)
            ->andReturn($dataRows)
            ->byDefault();

        // Capture appended rows if caller wants them.
        if ($appendedRows !== null) {
            $captureRef = &$appendedRows;
            $sheets->shouldReceive('appendRows')
                ->with(self::SHEET, Mockery::type('array'))
                ->andReturnUsing(function (string $sheet, array $rows) use (&$captureRef): bool {
                    $captureRef = $rows;
                    return true;
                });
        } else {
            $sheets->shouldReceive('appendRows')->byDefault()->andReturn(true);
        }

        $sheets->shouldReceive('clearCache')->andReturn(null)->byDefault();

        return $sheets;
    }

    /**
     * Build one assoc row the way getRowsAsAssoc returns it from the sheet.
     */
    private function existingRow(string $key, string $status = 'active'): array
    {
        return [
            'Permission Key' => $key,
            'Name'           => 'Existing ' . $key,
            'Description'    => '',
            'Group'          => 'Test',
            'Status'         => $status,
            'Created At'     => '2025-01-01 00:00:00',
            'Updated At'     => '2025-01-01 00:00:00',
            '_row_number'    => 2,
        ];
    }

    // =========================================================================
    // 1. dry-run does not write
    // =========================================================================

    public function test_dry_run_does_not_write_to_sheet(): void
    {
        $sheets = Mockery::mock(GoogleSheetsService::class);

        $sheets->shouldReceive('getRange')
            ->with(self::SHEET, 'A1:G1', false)
            ->once()
            ->andReturn([self::HEADERS]);

        $sheets->shouldReceive('getRowsAsAssoc')
            ->with(self::SHEET, false)
            ->once()
            ->andReturn([]); // empty sheet — all catalog entries are missing

        // appendRows MUST NOT be called during dry-run.
        $sheets->shouldNotReceive('appendRows');
        $sheets->shouldNotReceive('appendRow');

        $this->app->instance(GoogleSheetsService::class, $sheets);

        $this->artisan('mito:seed-permissions', ['--dry-run' => true])
            ->assertExitCode(0)
            ->expectsOutputToContain('DRY-RUN');
    }

    // =========================================================================
    // 2. missing permissions are detected
    // =========================================================================

    public function test_dry_run_reports_correct_missing_count_when_sheet_is_empty(): void
    {
        $catalogCount = count(PermissionCatalog::all());

        $sheets = $this->sheetsWithRows([]); // nothing in sheet
        $this->app->instance(GoogleSheetsService::class, $sheets);

        $this->artisan('mito:seed-permissions', ['--dry-run' => true])
            ->assertExitCode(0)
            ->expectsOutputToContain("To be created        : {$catalogCount}");
    }

    public function test_dry_run_reports_zero_missing_when_sheet_is_fully_populated(): void
    {
        // Pre-populate the sheet with every catalog key.
        $allRows = array_map(
            fn(array $entry): array => $this->existingRow($entry['key']),
            PermissionCatalog::all()
        );

        $sheets = $this->sheetsWithRows($allRows);
        $this->app->instance(GoogleSheetsService::class, $sheets);

        $this->artisan('mito:seed-permissions', ['--dry-run' => true])
            ->assertExitCode(0)
            ->expectsOutputToContain('To be created        : 0')
            ->expectsOutputToContain('Nothing to do');
    }

    // =========================================================================
    // 3. missing permissions are inserted
    // =========================================================================

    public function test_missing_permissions_are_appended_in_normal_run(): void
    {
        // Sheet has two existing rows; the rest of the catalog is missing.
        $first  = PermissionCatalog::all()[0];
        $second = PermissionCatalog::all()[1];

        $existingRows = [
            $this->existingRow($first['key']),
            $this->existingRow($second['key']),
        ];

        $appended = [];
        $sheets   = $this->sheetsWithRows($existingRows, $appended);
        $this->app->instance(GoogleSheetsService::class, $sheets);

        $expectedMissing = count(PermissionCatalog::all()) - 2;

        $this->artisan('mito:seed-permissions')
            ->assertExitCode(0)
            ->expectsOutputToContain("To be created        : {$expectedMissing}");

        // Verify the batch that was appended does not contain the two existing keys.
        $this->assertCount($expectedMissing, $appended);

        $appendedKeys = array_column($appended, 0); // column 0 = Permission Key
        $this->assertNotContains($first['key'],  $appendedKeys);
        $this->assertNotContains($second['key'], $appendedKeys);
    }

    // =========================================================================
    // 4. existing permission is not duplicated
    // =========================================================================

    public function test_existing_permission_key_is_not_duplicated(): void
    {
        // Choose one catalog key that is already in the sheet.
        $existingKey = PermissionCatalog::all()[0]['key'];
        $existingRows = [$this->existingRow($existingKey)];

        $appended = [];
        $sheets   = $this->sheetsWithRows($existingRows, $appended);
        $this->app->instance(GoogleSheetsService::class, $sheets);

        $this->artisan('mito:seed-permissions')->assertExitCode(0);

        // The existing key must not appear in the appended batch.
        $appendedKeys = array_column($appended, 0);
        $this->assertNotContains(
            $existingKey,
            $appendedKeys,
            "Existing key '{$existingKey}' must not be appended again"
        );

        // Total appended should be catalog count minus the one that already exists.
        $this->assertCount(count(PermissionCatalog::all()) - 1, $appended);
    }

    // =========================================================================
    // 5. existing inactive status is preserved
    // =========================================================================

    public function test_inactive_existing_row_is_not_overwritten_or_reactivated(): void
    {
        // Simulate a row that an operator has manually deactivated.
        $inactiveKey  = PermissionCatalog::all()[0]['key'];
        $inactiveRow  = $this->existingRow($inactiveKey, 'inactive');

        $appended = [];
        $sheets   = $this->sheetsWithRows([$inactiveRow], $appended);
        $this->app->instance(GoogleSheetsService::class, $sheets);

        $this->artisan('mito:seed-permissions')->assertExitCode(0);

        // The inactive row must not be re-appended.
        $appendedKeys = array_column($appended, 0);
        $this->assertNotContains(
            $inactiveKey,
            $appendedKeys,
            "Inactive key '{$inactiveKey}' must be left untouched — command must not re-append it"
        );

        // The sheet mock was never given an updateRow expectation,
        // so Mockery will fail the test if updateRow is unexpectedly called.
    }

    // =========================================================================
    // 6. command is idempotent
    // =========================================================================

    public function test_command_is_idempotent_second_run_appends_nothing(): void
    {
        // Simulate a sheet that already has every catalog key.
        $allRows = array_map(
            fn(array $entry): array => $this->existingRow($entry['key']),
            PermissionCatalog::all()
        );

        $sheets = Mockery::mock(GoogleSheetsService::class);

        $sheets->shouldReceive('getRange')
            ->with(self::SHEET, 'A1:G1', false)
            ->andReturn([self::HEADERS]);

        $sheets->shouldReceive('getRowsAsAssoc')
            ->with(self::SHEET, false)
            ->andReturn($allRows);

        // appendRows must NEVER be called when everything is already present.
        $sheets->shouldNotReceive('appendRows');
        $sheets->shouldNotReceive('appendRow');

        $this->app->instance(GoogleSheetsService::class, $sheets);

        $this->artisan('mito:seed-permissions')
            ->assertExitCode(0)
            ->expectsOutputToContain('Nothing to do');
    }

    // =========================================================================
    // 7. all PermissionCatalog keys can be seeded from an empty sheet
    // =========================================================================

    public function test_all_catalog_keys_are_appended_when_sheet_is_empty(): void
    {
        $catalog = PermissionCatalog::all();
        $appended = [];

        $sheets = $this->sheetsWithRows([], $appended);
        $this->app->instance(GoogleSheetsService::class, $sheets);

        $this->artisan('mito:seed-permissions')->assertExitCode(0);

        // Every catalog key must be in the batch.
        $this->assertCount(count($catalog), $appended);

        $appendedKeys = array_column($appended, 0);
        foreach ($catalog as $entry) {
            $this->assertContains(
                $entry['key'],
                $appendedKeys,
                "Catalog key '{$entry['key']}' must be included in the appended batch"
            );
        }

        // Every appended row must have exactly 7 columns in the correct order.
        foreach ($appended as $row) {
            $this->assertCount(7, $row, 'Each appended row must have 7 columns');
            // Column 0 = Permission Key, column 4 = Status (must be 'active' for new rows).
            $this->assertSame('active', $row[4], 'New catalog rows must default to status=active');
            // Columns 5 and 6 are timestamps — non-empty strings.
            $this->assertNotEmpty($row[5], 'Created At timestamp must be set');
            $this->assertNotEmpty($row[6], 'Updated At timestamp must be set');
        }
    }

    // =========================================================================
    // 8. malformed/unavailable sheet fails safely
    // =========================================================================

    public function test_missing_header_row_causes_graceful_failure(): void
    {
        $sheets = Mockery::mock(GoogleSheetsService::class);

        // getRange returns an empty array — sheet exists but has no header row.
        $sheets->shouldReceive('getRange')
            ->with(self::SHEET, 'A1:G1', false)
            ->andReturn([]);

        // Neither data reads nor writes should occur after a header failure.
        $sheets->shouldNotReceive('getRowsAsAssoc');
        $sheets->shouldNotReceive('appendRows');
        $sheets->shouldNotReceive('appendRow');

        $this->app->instance(GoogleSheetsService::class, $sheets);

        $this->artisan('mito:seed-permissions')
            ->assertExitCode(1);
    }

    public function test_incomplete_header_row_causes_graceful_failure(): void
    {
        $sheets = Mockery::mock(GoogleSheetsService::class);

        // Header row is missing the 'Status' and 'Group' columns.
        $truncatedHeaders = ['Permission Key', 'Name', 'Description'];
        $sheets->shouldReceive('getRange')
            ->with(self::SHEET, 'A1:G1', false)
            ->andReturn([$truncatedHeaders]);

        $sheets->shouldNotReceive('getRowsAsAssoc');
        $sheets->shouldNotReceive('appendRows');

        $this->app->instance(GoogleSheetsService::class, $sheets);

        $this->artisan('mito:seed-permissions')
            ->assertExitCode(1);
    }

    public function test_google_sheets_api_exception_during_read_causes_graceful_failure(): void
    {
        $sheets = Mockery::mock(GoogleSheetsService::class);

        // Simulate a network or API error when reading the header row.
        $sheets->shouldReceive('getRange')
            ->with(self::SHEET, 'A1:G1', false)
            ->andThrow(new \RuntimeException('Google Sheets API error: connection timeout'));

        $sheets->shouldNotReceive('appendRows');

        $this->app->instance(GoogleSheetsService::class, $sheets);

        $this->artisan('mito:seed-permissions')
            ->assertExitCode(1);
    }

    public function test_appendrows_failure_returns_error_exit_code(): void
    {
        $sheets = Mockery::mock(GoogleSheetsService::class);

        $sheets->shouldReceive('getRange')
            ->with(self::SHEET, 'A1:G1', false)
            ->andReturn([self::HEADERS]);

        $sheets->shouldReceive('getRowsAsAssoc')
            ->with(self::SHEET, false)
            ->andReturn([]); // empty — all entries would be appended

        // appendRows fails (API write error).
        $sheets->shouldReceive('appendRows')
            ->once()
            ->andReturn(false);

        $this->app->instance(GoogleSheetsService::class, $sheets);

        $this->artisan('mito:seed-permissions')
            ->assertExitCode(1);
    }
}
