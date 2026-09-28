<?php

namespace Tests\Feature;

use App\Support\HrisDataDriver;
use Tests\TestCase;

class HrisDataDriverTest extends TestCase
{
    public function test_defaults_to_local_when_sheets_disabled(): void
    {
        config(['google.enabled' => false, 'hris.data_driver' => null]);
        $this->assertSame(HrisDataDriver::LOCAL, HrisDataDriver::current());
    }

    public function test_defaults_to_sheets_when_sheets_enabled(): void
    {
        config(['google.enabled' => true, 'hris.data_driver' => null]);
        $this->assertSame(HrisDataDriver::SHEETS, HrisDataDriver::current());
    }

    public function test_explicit_pgsql_driver_wins(): void
    {
        config(['google.enabled' => true, 'hris.data_driver' => 'pgsql']);
        $this->assertSame(HrisDataDriver::PGSQL, HrisDataDriver::current());
        $this->assertTrue(HrisDataDriver::usesPgsql());
    }

    public function test_pgsql_driver_binds_employee_database_repositories(): void
    {
        config(['google.enabled' => false, 'hris.data_driver' => 'pgsql']);

        // Re-register bindings as boot-time config change would in a fresh app.
        $provider = new \App\Providers\AppServiceProvider($this->app);
        $provider->register();

        $this->assertInstanceOf(
            \App\Repositories\Database\EmployeeDatabaseRepository::class,
            $this->app->make(\App\Repositories\Contracts\EmployeeRepositoryInterface::class)
        );
        $this->assertInstanceOf(
            \App\Repositories\Database\EmployeeDocumentDatabaseRepository::class,
            $this->app->make(\App\Repositories\Contracts\EmployeeDocumentRepositoryInterface::class)
        );
        $this->assertInstanceOf(
            \App\Repositories\Database\ProbationDatabaseRepository::class,
            $this->app->make(\App\Repositories\Contracts\ProbationRepositoryInterface::class)
        );
        $this->assertInstanceOf(
            \App\Repositories\Database\CandidateDatabaseRepository::class,
            $this->app->make(\App\Repositories\Contracts\CandidateRepositoryInterface::class)
        );
        $this->assertInstanceOf(
            \App\Repositories\Database\AuditLogDatabaseRepository::class,
            $this->app->make(\App\Repositories\Contracts\AuditLogRepositoryInterface::class)
        );
        $this->assertInstanceOf(
            \App\Repositories\Database\MprDatabaseRepository::class,
            $this->app->make(\App\Repositories\Contracts\MprRepositoryInterface::class)
        );
        $this->assertInstanceOf(
            \App\Repositories\Database\MprRequestorDatabaseRepository::class,
            $this->app->make(\App\Repositories\Contracts\MprRequestorRepositoryInterface::class)
        );
        $this->assertInstanceOf(
            \App\Repositories\Database\UserPermissionDatabaseRepository::class,
            $this->app->make(\App\Repositories\Contracts\UserPermissionRepositoryInterface::class)
        );
        $this->assertInstanceOf(
            \App\Repositories\Database\PermissionCatalogDatabaseRepository::class,
            $this->app->make(\App\Repositories\Contracts\PermissionCatalogRepositoryInterface::class)
        );
    }
}
