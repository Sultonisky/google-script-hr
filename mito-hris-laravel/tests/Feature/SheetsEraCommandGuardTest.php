<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SheetsEraCommandGuardTest extends TestCase
{
    use RefreshDatabase;

    public function test_sheets_era_commands_are_disabled_when_driver_is_pgsql(): void
    {
        config(['hris.data_driver' => 'pgsql']);

        $this->artisan('mito:setup-sheets')
            ->expectsOutputToContain('DISABLED')
            ->assertSuccessful();

        $this->artisan('mito:validate-schema')
            ->expectsOutputToContain('DISABLED')
            ->assertSuccessful();

        $this->artisan('mito:dummy', ['--force' => false])
            ->expectsOutputToContain('DISABLED')
            ->assertSuccessful();

        $this->artisan('hris:audit-nik')
            ->expectsOutputToContain('DISABLED')
            ->assertSuccessful();
    }

    public function test_seed_permissions_writes_database_when_driver_is_pgsql(): void
    {
        config(['hris.data_driver' => 'pgsql']);

        $sheets = \Mockery::mock(\App\Services\Google\GoogleSheetsService::class);
        $sheets->shouldReceive('appendRows')->never();
        $sheets->shouldReceive('getRowsAsAssoc')->never();
        $this->app->instance(\App\Services\Google\GoogleSheetsService::class, $sheets);

        $this->artisan('mito:seed-permissions', ['--dry-run' => true])
            ->expectsOutputToContain('database')
            ->assertSuccessful();
        $this->assertSame(0, \App\Models\Permission::count());

        $this->artisan('mito:seed-permissions')->assertSuccessful();
        $this->assertSame(count(\App\Support\PermissionCatalog::all()), \App\Models\Permission::count());

        $this->artisan('mito:seed-permissions')
            ->expectsOutputToContain('already up to date')
            ->assertSuccessful();
    }

    public function test_sheets_era_commands_run_when_driver_is_sheets(): void
    {
        config([
            'hris.data_driver' => 'sheets',
            'google.enabled' => true,
        ]);

        // Without live Sheets, seed-permissions should attempt (not soft-disabled).
        // It may fail on API — we only assert it does not print DISABLED.
        $this->artisan('mito:seed-permissions', ['--dry-run' => true])
            ->doesntExpectOutputToContain('DISABLED');
    }
}
