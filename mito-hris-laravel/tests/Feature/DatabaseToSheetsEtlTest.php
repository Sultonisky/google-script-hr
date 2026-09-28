<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\MprRequestor;
use App\Services\Etl\DatabaseToSheetsEtlService;
use App\Services\Google\GoogleSheetsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

/**
 * DB → Sheets mirror: writes Sheets only; never imports Sheets into DB.
 */
class DatabaseToSheetsEtlTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    protected function setUp(): void
    {
        parent::setUp();
        config(['hris.data_driver' => 'pgsql']);
    }

    /** @return \Mockery\MockInterface&GoogleSheetsService */
    private function bindSheetsMock(): GoogleSheetsService
    {
        $sheets = Mockery::mock(GoogleSheetsService::class);
        $this->app->instance(GoogleSheetsService::class, $sheets);

        return $sheets;
    }

    public function test_dry_run_counts_without_writing_sheets(): void
    {
        Employee::query()->create([
            'employee_id' => 'EMP-1',
            'full_name' => 'Budi',
            'status_employee' => 'Contract',
        ]);
        MprRequestor::query()->create([
            'requestor_id' => 'MPR-REQ-001',
            'email' => 'req@mito.co.id',
            'username' => 'req',
            'full_name' => 'Requestor',
            'role' => 'Manpower',
            'status' => 'Active',
            'password_hash' => '$2y$10$hash',
        ]);

        $sheets = $this->bindSheetsMock();
        $sheets->shouldReceive('replaceSheetData')->never();
        $sheets->shouldReceive('clearSheetDataRows')->never();
        $sheets->shouldReceive('appendRows')->never();
        $sheets->shouldReceive('getRowsAsAssoc')->never();

        $this->artisan('mito:etl-db-to-sheets', [
            '--dry-run' => true,
            '--only' => 'employees,mpr_requestors',
        ])->assertSuccessful();
    }

    public function test_mirror_replaces_sheet_from_db(): void
    {
        Employee::query()->create([
            'employee_id' => 'EMP-1',
            'full_name' => 'Budi Santoso',
            'status_employee' => 'Contract',
            'nik_npwp' => '3175010101010001',
        ]);
        MprRequestor::query()->create([
            'requestor_id' => 'MPR-REQ-010',
            'email' => 'alice@mito.co.id',
            'username' => 'alice',
            'full_name' => 'Alice',
            'job_position' => 'Manager',
            'role' => 'Manpower',
            'status' => 'Active',
            'password_hash' => '$2y$10$aaa',
        ]);

        $sheets = $this->bindSheetsMock();
        $sheets->shouldReceive('replaceSheetData')
            ->once()
            ->withArgs(function (string $sheet, array $headers, array $rows) {
                return $sheet === 'Employee'
                    && in_array('Employee ID', $headers, true)
                    && count($rows) === 1
                    && ($rows[0][0] ?? null) === 'EMP-1';
            });
        $sheets->shouldReceive('replaceSheetData')
            ->once()
            ->withArgs(function (string $sheet, array $headers, array $rows) {
                return $sheet === 'mpr_requestor'
                    && in_array('Requestor ID', $headers, true)
                    && count($rows) === 1
                    && ($rows[0][0] ?? null) === 'MPR-REQ-010'
                    && ($rows[0][1] ?? null) === 'alice@mito.co.id';
            });
        $sheets->shouldReceive('getRowsAsAssoc')->never();

        $this->artisan('mito:etl-db-to-sheets', [
            '--only' => 'employees,mpr_requestors',
        ])->assertSuccessful();
    }

    public function test_refuses_when_driver_is_not_pgsql(): void
    {
        config(['hris.data_driver' => 'sheets']);
        $this->bindSheetsMock();

        $this->artisan('mito:etl-db-to-sheets', [
            '--only' => 'employees',
        ])->assertFailed();
    }

    public function test_force_allows_non_pgsql_driver(): void
    {
        config(['hris.data_driver' => 'sheets']);

        Employee::query()->create([
            'employee_id' => 'EMP-2',
            'full_name' => 'Citra',
            'status_employee' => 'Contract',
        ]);

        $sheets = $this->bindSheetsMock();
        $sheets->shouldReceive('replaceSheetData')->once();
        $sheets->shouldReceive('getRowsAsAssoc')->never();

        $this->artisan('mito:etl-db-to-sheets', [
            '--only' => 'employees',
            '--force' => true,
        ])->assertSuccessful();
    }

    public function test_service_rejects_unknown_domain(): void
    {
        $this->bindSheetsMock();
        $this->expectException(\RuntimeException::class);
        $this->app->make(DatabaseToSheetsEtlService::class)->run(['not-a-domain']);
    }
}
