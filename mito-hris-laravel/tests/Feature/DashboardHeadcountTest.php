<?php

namespace Tests\Feature;

use App\DTOs\EmployeeData;
use App\Repositories\Contracts\EmployeeRepositoryInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Session;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\TestCase;

class DashboardHeadcountTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function dashboard_shows_active_headcount_per_entity(): void
    {
        Session::put('hr_user', $this->migratedTestUser([
            'email'       => 'admin.headcount@mito.id',
            'fullName'    => 'Admin User',
            'role'        => 'Admin',
            'permissions' => config('hris.auth.role_permissions.Admin') ?? [],
            'auth_domain' => 'users',
            'entities'    => [],
            'branch'      => '',
        ]));

        $this->app->instance(EmployeeRepositoryInterface::class, new class implements EmployeeRepositoryInterface {
            public function getAll(array $filters = []): Collection
            {
                return collect([
                    new EmployeeData(employeeId: '1', branchName: 'PT Mahakarya Sukses Indonesia', statusEmployee: 'Permanent', division: 'Sales', department: 'Sales Ops'),
                    new EmployeeData(employeeId: '2', branchName: 'PT MAHAKARYA SUKSES INDONESIA', statusEmployee: 'PKWT', division: 'Sales', department: 'Sales Ops'),
                    new EmployeeData(employeeId: '3', branchName: 'PT Mahakarya Sukses Indonesia', statusEmployee: 'Resigned', division: 'Sales', department: 'Sales Ops'),
                    new EmployeeData(employeeId: '12', branchName: 'PT Mahakarya Sukses Indonesia', statusEmployee: 'Outsource', division: 'Marketing', department: 'Marketing'),
                    new EmployeeData(employeeId: '4', branchName: 'PT Stein Perkasa Internasional', statusEmployee: 'Permanent', division: 'Sales', department: 'sales ops'),
                    new EmployeeData(employeeId: '5', branchName: 'PT Stein Perkasa Internasional', statusEmployee: 'Kemitraan', division: 'HR', department: 'People'),
                    new EmployeeData(employeeId: '6', branchName: 'PT Stein Perkasa Internasional', statusEmployee: 'Terminated', division: 'Sales', department: 'Sales Ops'),
                    new EmployeeData(employeeId: '7', branchName: 'PT. PERKASA INJEKSI INDONESIA', statusEmployee: 'Contract', division: 'sales', department: 'Warehouse'),
                    new EmployeeData(employeeId: '8', branchName: 'PT Mitra Elektro Perkasa', statusEmployee: 'Outsource', division: 'Marketing', department: 'Marketing'),
                    new EmployeeData(employeeId: '9', branchName: 'PT Suka Dessert Indonesia', statusEmployee: 'Permanent', division: 'FAT', department: 'Finance'),
                    new EmployeeData(employeeId: '10', branchName: 'PT Suka Dessert Indonesia', statusEmployee: 'Contract Finished', division: 'FAT', department: 'Finance'),
                    new EmployeeData(employeeId: '11', branchName: '', statusEmployee: 'Permanent', division: 'Sales', department: 'Sales Ops'),
                    new EmployeeData(employeeId: '13', branchName: 'PT Mahakarya Sukses Indonesia', statusEmployee: 'Permanent', division: 'E - Commerce', department: 'E - Commerce'),
                    new EmployeeData(employeeId: '14', branchName: 'PT Stein Perkasa Internasional', statusEmployee: 'Permanent', division: 'E-Commerce', department: 'E-Commerce'),
                ]);
            }

            public function findById(string $employeeId): ?EmployeeData
            {
                return null;
            }

            public function findByNik(string $nik): ?EmployeeData
            {
                return null;
            }

            public function create(EmployeeData $data): EmployeeData
            {
                throw new RuntimeException('Headcount stub is read-only.');
            }

            public function update(string $employeeId, array $attributes): bool
            {
                return false;
            }

            public function delete(string $employeeId): bool
            {
                return false;
            }
        });

        $this->get('/hr/dashboard')
            ->assertOk()
            ->assertSee('Headcount Karyawan', false)
            ->assertSee('id="headcount-MSI">3', false)
            ->assertSee('id="headcount-SPI">3', false)
            ->assertSee('id="headcount-PII">1', false)
            ->assertSee('id="headcount-MEP">0', false)
            ->assertSee('id="headcount-SDI">1', false)
            ->assertSee('id="headcount-total">8 karyawan', false)
            ->assertSee('Headcount Karyawan by Divisi', false)
            ->assertSee('data-division="Sales" data-count="4"', false)
            ->assertSee('data-division="E - Commerce" data-count="2"', false)
            ->assertSee('data-division="FAT" data-count="1"', false)
            ->assertSee('data-division="HR" data-count="1"', false)
            ->assertDontSee('data-division="E-Commerce"', false)
            ->assertDontSee('data-division="Marketing"', false)
            ->assertSee('data-department="Sales Ops" data-count="3"', false)
            ->assertSee('data-department="E - Commerce" data-count="2"', false)
            ->assertSee('data-department="Finance" data-count="1"', false)
            ->assertSee('data-department="People" data-count="1"', false)
            ->assertSee('data-department="Warehouse" data-count="1"', false)
            ->assertDontSee('data-department="E-Commerce"', false)
            ->assertDontSee('data-department="Marketing"', false);
    }
}
