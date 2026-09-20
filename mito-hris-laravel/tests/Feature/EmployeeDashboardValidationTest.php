<?php

namespace Tests\Feature;

use App\Repositories\Contracts\EmployeeRepositoryInterface;
use App\Repositories\Local\LocalEmployeeRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Session;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EmployeeDashboardValidationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app->instance(EmployeeRepositoryInterface::class, new LocalEmployeeRepository());
    }

    private function actingAsAdmin(): static
    {
        Session::put('hr_user', $this->migratedTestUser([
            'email'       => 'admin.dashboard@mito.id',
            'fullName'    => 'Admin User',
            'role'        => 'Admin',
            'permissions' => config('hris.auth.role_permissions.Admin') ?? [],
            'auth_domain' => 'users',
            'entities'    => [],
            'branch'      => '',
        ]));

        return $this;
    }

    #[Test]
    public function store_rejects_employee_id_with_letters(): void
    {
        $this->actingAsAdmin();

        $this->postJson('/hr/employees', [
            'fullName'       => 'Andi Wijaya',
            'statusEmployee' => 'Contract',
            'employeeId'     => 'EMP20250101',
        ])->assertStatus(422)->assertJsonValidationErrors(['employeeId']);
    }

    #[Test]
    public function store_rejects_division_and_job_titles_with_digits(): void
    {
        $this->actingAsAdmin();

        $this->postJson('/hr/employees', [
            'fullName'            => 'Andi Wijaya',
            'statusEmployee'      => 'Contract',
            'division'            => 'Finance 2',
            'jobPosition'         => 'Staff 1',
            'jobPositionLocation' => 'HR Staff 3 - Jakarta',
        ])->assertStatus(422)->assertJsonValidationErrors(['division', 'jobPosition', 'jobPositionLocation']);
    }

    #[Test]
    public function outsource_store_rejects_numeric_org_titles(): void
    {
        $this->actingAsAdmin();

        $this->postJson('/hr/outsource', [
            'fullName'            => 'Andi Wijaya',
            'outsourceVendor'     => 'PT Vendor Dummy',
            'division'            => 'Ops 9',
            'jobPosition'         => 'Operator 2',
            'jobPositionLocation' => 'Operator 2 - Bekasi',
        ])->assertStatus(422)->assertJsonValidationErrors(['division', 'jobPosition', 'jobPositionLocation']);
    }

    #[Test]
    public function update_rejects_division_and_job_titles_with_digits(): void
    {
        $this->actingAsAdmin();

        $this->putJson('/hr/employees/2025010101', [
            'fullName'            => 'Andi Wijaya',
            'division'            => 'Ops 9',
            'jobPosition'         => 'Operator 2',
            'jobPositionLocation' => 'Operator 2 - Bekasi',
        ])->assertStatus(422)->assertJsonValidationErrors(['division', 'jobPosition', 'jobPositionLocation']);
    }
}
