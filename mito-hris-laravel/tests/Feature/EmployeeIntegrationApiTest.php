<?php

namespace Tests\Feature;

use App\DTOs\EmployeeData;
use App\Repositories\Contracts\EmployeeRepositoryInterface;
use Mockery;
use Tests\TestCase;

class EmployeeIntegrationApiTest extends TestCase
{
    private const API_TOKEN = 'test-employee-integration-token';

    public function test_employee_endpoints_fail_closed_when_token_is_not_configured(): void
    {
        config(['hris.integration.employee_api_token' => '']);

        $this->getJson('/api/v1/employees')
            ->assertServiceUnavailable()
            ->assertJsonPath('success', false);
    }

    public function test_employee_endpoints_reject_requests_without_a_valid_bearer_token(): void
    {
        config(['hris.integration.employee_api_token' => self::API_TOKEN]);

        $this->getJson('/api/v1/employees')
            ->assertUnauthorized()
            ->assertJsonPath('success', false);
    }

    public function test_employee_list_is_paginated_and_limited_to_attendance_fields(): void
    {
        config(['hris.integration.employee_api_token' => self::API_TOKEN]);

        $repository = Mockery::mock(EmployeeRepositoryInterface::class);
        $repository->shouldReceive('getAll')
            ->once()
            ->andReturn(collect([
                new EmployeeData(
                    employeeId: '2020041501',
                    fullName: 'Employee One',
                    branchName: 'MSI',
                    division: 'Finance',
                    department: 'Treasury',
                    jobPosition: 'Treasury Staff',
                    statusEmployee: 'Permanent (PKWTT)',
                    lokasiKerja: 'Jakarta',
                    areaKerja: 'Head Office',
                    nikNpwp: '1234567890123456',
                    personalEmail: 'private@example.test',
                    bankAccount: '123456789',
                ),
                new EmployeeData(
                    employeeId: '2020041502',
                    fullName: 'Employee Two',
                    lokasiKerja: 'Tangerang',
                    areaKerja: 'Factory',
                    nikNpwp: '1234567890123457',
                ),
            ]));
        $this->app->instance(EmployeeRepositoryInterface::class, $repository);

        $this->withToken(self::API_TOKEN)
            ->getJson('/api/v1/employees?page=1&per_page=1')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.0.employee_id', '2020041501')
            ->assertJsonPath('data.0.nik', '1234567890123456')
            ->assertJsonPath('data.0.full_name', 'Employee One')
            ->assertJsonPath('data.0.status_employee', 'Permanent (PKWTT)')
            ->assertJsonPath('data.0.department', 'Treasury')
            ->assertJsonPath('meta.current_page', 1)
            ->assertJsonPath('meta.per_page', 1)
            ->assertJsonPath('meta.total', 2)
            ->assertJsonPath('filters.work_locations', ['Jakarta', 'Tangerang'])
            ->assertJsonPath('filters.work_areas', ['Factory', 'Head Office'])
            ->assertJsonMissingPath('data.0.personal_email')
            ->assertJsonMissingPath('data.0.bank_account');
    }

    public function test_employee_list_filters_by_work_location_and_work_area(): void
    {
        config(['hris.integration.employee_api_token' => self::API_TOKEN]);

        $repository = Mockery::mock(EmployeeRepositoryInterface::class);
        $repository->shouldReceive('getAll')
            ->once()
            ->andReturn(collect([
                new EmployeeData(
                    employeeId: '2020041501',
                    fullName: 'Employee One',
                    lokasiKerja: 'Jakarta',
                    areaKerja: 'Head Office',
                    nikNpwp: '1234567890123456',
                ),
                new EmployeeData(
                    employeeId: '2020041502',
                    fullName: 'Employee Two',
                    lokasiKerja: 'Jakarta',
                    areaKerja: 'Factory',
                    nikNpwp: '1234567890123457',
                ),
                new EmployeeData(
                    employeeId: '2020041503',
                    fullName: 'Employee Three',
                    lokasiKerja: 'Tangerang',
                    areaKerja: 'Factory',
                    nikNpwp: '1234567890123458',
                ),
            ]));
        $this->app->instance(EmployeeRepositoryInterface::class, $repository);

        $this->withToken(self::API_TOKEN)
            ->getJson('/api/v1/employees?work_location=jakarta&work_area=Head%20Office')
            ->assertOk()
            ->assertJsonPath('data.0.employee_id', '2020041501')
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('filters.work_locations', ['Jakarta', 'Tangerang'])
            ->assertJsonPath('filters.work_areas', ['Factory', 'Head Office']);
    }

    public function test_employee_list_searches_only_attendance_relevant_fields(): void
    {
        config(['hris.integration.employee_api_token' => self::API_TOKEN]);

        $repository = Mockery::mock(EmployeeRepositoryInterface::class);
        $repository->shouldReceive('getAll')
            ->once()
            ->andReturn(collect([
                new EmployeeData(
                    employeeId: '2020041501',
                    fullName: 'Employee One',
                    department: 'Treasury',
                    nikNpwp: '1234567890123456',
                ),
                new EmployeeData(
                    employeeId: '2020041502',
                    fullName: 'Other Employee',
                    personalEmail: 'treasury@example.test',
                ),
            ]));
        $this->app->instance(EmployeeRepositoryInterface::class, $repository);

        $this->withToken(self::API_TOKEN)
            ->getJson('/api/v1/employees?search=Treasury')
            ->assertOk()
            ->assertJsonPath('data.0.department', 'Treasury')
            ->assertJsonPath('meta.total', 1);
    }

    public function test_employee_can_be_found_by_nik(): void
    {
        config(['hris.integration.employee_api_token' => self::API_TOKEN]);

        $repository = Mockery::mock(EmployeeRepositoryInterface::class);
        $repository->shouldReceive('findByNik')
            ->once()
            ->with('1234567890123456')
            ->andReturn(new EmployeeData(
                employeeId: '2020041501',
                fullName: 'Employee One',
                nikNpwp: '1234567890123456',
            ));
        $this->app->instance(EmployeeRepositoryInterface::class, $repository);

        $this->withToken(self::API_TOKEN)
            ->getJson('/api/v1/employees/by-nik/1234567890123456')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.employee_id', '2020041501')
            ->assertJsonPath('data.nik', '1234567890123456');
    }

    public function test_unknown_nik_returns_not_found(): void
    {
        config(['hris.integration.employee_api_token' => self::API_TOKEN]);

        $repository = Mockery::mock(EmployeeRepositoryInterface::class);
        $repository->shouldReceive('findByNik')
            ->once()
            ->with('1234567890123456')
            ->andReturnNull();
        $this->app->instance(EmployeeRepositoryInterface::class, $repository);

        $this->withToken(self::API_TOKEN)
            ->getJson('/api/v1/employees/by-nik/1234567890123456')
            ->assertNotFound()
            ->assertJsonPath('success', false);
    }

    public function test_invalid_nik_is_rejected(): void
    {
        config(['hris.integration.employee_api_token' => self::API_TOKEN]);

        $this->withToken(self::API_TOKEN)
            ->getJson('/api/v1/employees/by-nik/not-a-nik')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('nik');
    }
}
