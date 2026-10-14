<?php

namespace Tests\Feature;

use App\DTOs\EmployeeData;
use App\Repositories\Contracts\EmployeeRepositoryInterface;
use Mockery;
use Tests\TestCase;

class EmployeeIntegrationApiTest extends TestCase
{
    private const API_TOKEN = 'test-employee-integration-token';

    protected function setUp(): void
    {
        parent::setUp();
        config(['hris.integration.employee_api_token' => self::API_TOKEN]);
    }

    public function test_employee_list_is_paginated_allowlisted_and_includes_filter_options(): void
    {
        $this->mockEmployeeRepository([
            new EmployeeData(
                employeeId: '2020041502',
                fullName: 'Employee Two',
                lokasiKerja: 'Jakarta',
                areaKerja: 'Head Office',
                nikNpwp: '1234567890123457',
                personalEmail: 'private@example.test'
            ),
            new EmployeeData(
                employeeId: '2020041501',
                fullName: 'Employee One',
                department: 'Treasury',
                lokasiKerja: 'Jakarta',
                areaKerja: 'Head Office',
                nikNpwp: "'1234567890123456",
                personalEmail: 'private@example.test',
                bankAccount: '123456789'
            ),
            new EmployeeData(
                employeeId: '2020041503',
                fullName: 'Employee Three',
                lokasiKerja: 'Bandung',
                areaKerja: 'Factory'
            ),
        ]);

        $this->withToken(self::API_TOKEN)
            ->getJson('/api/v1/employees?search=Treasury&work_location=jakarta&page=1&per_page=1')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.0.employee_id', '2020041501')
            ->assertJsonPath('data.0.nik', '1234567890123456')
            ->assertJsonPath('meta.current_page', 1)
            ->assertJsonPath('meta.per_page', 1)
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('meta.last_page', 1)
            ->assertJsonPath('filters.work_locations', ['Bandung', 'Jakarta'])
            ->assertJsonPath('filters.work_areas', ['Factory', 'Head Office'])
            ->assertJsonMissingPath('data.0.personal_email')
            ->assertJsonMissingPath('data.0.bank_account');
    }

    public function test_employee_list_validates_pagination_and_filter_inputs(): void
    {
        $this->withToken(self::API_TOKEN)
            ->getJson('/api/v1/employees?per_page=101')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('per_page');
    }

    public function test_employee_lookup_returns_allowlisted_employee_data(): void
    {
        $repository = Mockery::mock(EmployeeRepositoryInterface::class);
        $repository->shouldReceive('findByNik')
            ->once()
            ->with('1234567890123456')
            ->andReturn(new EmployeeData(
                employeeId: '2020041501',
                fullName: 'Employee One',
                nikNpwp: "'1234567890123456",
                personalEmail: 'private@example.test'
            ));
        $this->app->instance(EmployeeRepositoryInterface::class, $repository);

        $this->withToken(self::API_TOKEN)
            ->getJson('/api/v1/employees/by-nik/1234567890123456')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.employee_id', '2020041501')
            ->assertJsonPath('data.nik', '1234567890123456')
            ->assertJsonMissingPath('data.personal_email');
    }

    public function test_unknown_and_invalid_nik_have_expected_responses(): void
    {
        $repository = Mockery::mock(EmployeeRepositoryInterface::class);
        $repository->shouldReceive('findByNik')
            ->once()
            ->with('1234567890123456')
            ->andReturnNull();
        $this->app->instance(EmployeeRepositoryInterface::class, $repository);

        $this->withToken(self::API_TOKEN)
            ->getJson('/api/v1/employees/by-nik/not-a-nik')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('nik');

        $this->withToken(self::API_TOKEN)
            ->getJson('/api/v1/employees/by-nik/1234567890123456')
            ->assertNotFound()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Employee not found.');
    }

    public function test_employee_api_requires_a_configured_bearer_token(): void
    {
        $this->getJson('/api/v1/employees')->assertUnauthorized();

        $this->withToken('invalid-token')
            ->getJson('/api/v1/employees')
            ->assertUnauthorized();

        config(['hris.integration.employee_api_token' => '']);
        $this->withToken(self::API_TOKEN)
            ->getJson('/api/v1/employees')
            ->assertServiceUnavailable()
            ->assertJsonPath('success', false);
    }

    /**
     * @param  list<EmployeeData>  $employees
     */
    private function mockEmployeeRepository(array $employees): void
    {
        $repository = Mockery::mock(EmployeeRepositoryInterface::class);
        $repository->shouldReceive('getAll')->once()->andReturn(collect($employees));
        $this->app->instance(EmployeeRepositoryInterface::class, $repository);
    }
}
