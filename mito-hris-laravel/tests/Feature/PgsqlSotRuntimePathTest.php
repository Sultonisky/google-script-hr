<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Repositories\Contracts\EmployeeRepositoryInterface;
use App\Repositories\Database\EmployeeDatabaseRepository;
use App\Services\EmployeeService;
use App\Services\Google\GoogleSheetsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Session;
use Mockery;
use Tests\TestCase;

class PgsqlSotRuntimePathTest extends TestCase
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
        config([
            'hris.data_driver' => 'pgsql',
            'google.enabled' => true,
        ]);
        $provider = new \App\Providers\AppServiceProvider($this->app);
        $provider->register();
    }

    public function test_create_employee_writes_to_database_not_sheets(): void
    {
        $sheets = Mockery::mock(GoogleSheetsService::class);
        $sheets->shouldReceive('appendRow')->never();
        $sheets->shouldReceive('appendRows')->never();
        $this->app->instance(GoogleSheetsService::class, $sheets);

        $this->assertInstanceOf(
            EmployeeDatabaseRepository::class,
            $this->app->make(EmployeeRepositoryInterface::class)
        );

        $result = $this->app->make(EmployeeService::class)->createEmployee([
            'fullName' => 'Budi Santoso',
            'statusEmployee' => 'Contract',
            'joinDate' => '2026-01-15',
            'department' => 'IT',
        ], 'Tester');

        $this->assertTrue($result['success'], $result['message'] ?? '');
        $this->assertNotEmpty($result['employeeId']);
        $this->assertSame(1, Employee::query()->count());
        $this->assertSame('Budi Santoso', Employee::query()->first()->full_name);
    }

    public function test_refresh_data_uses_database_when_driver_is_pgsql(): void
    {
        Employee::query()->create([
            'employee_id' => 'EMP-REF-1',
            'full_name' => 'Refresh Emp',
            'status_employee' => 'Contract',
        ]);

        $sheets = Mockery::mock(GoogleSheetsService::class);
        $sheets->shouldReceive('healthCheck')->never();
        $sheets->shouldReceive('getRange')->never();
        $this->app->instance(GoogleSheetsService::class, $sheets);

        Session::put('hr_user', [
            'email' => 'admin@mito.id',
            'name' => 'Admin',
            'role' => 'Super Admin',
            'permissions' => ['*'],
            'auth_domain' => 'users',
        ]);

        $response = $this->postJson('/hr/refresh-data');
        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.driver', 'pgsql')
            ->assertJsonPath('data.summary.employees.rows', 1);
        $this->assertStringContainsString('database', strtolower((string) $response->json('message')));
    }
}
