<?php

namespace Tests\Feature;

use App\Repositories\Contracts\EmployeeRepositoryInterface;
use App\Repositories\Local\LocalEmployeeRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Session;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EmployeePickerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app->instance(EmployeeRepositoryInterface::class, new LocalEmployeeRepository());
    }

    private function sessionUser(string $role): array
    {
        return [
            'email'       => strtolower($role) . '@mito.id',
            'fullName'    => $role . ' User',
            'role'        => $role,
            'permissions' => config('hris.auth.role_permissions')[$role] ?? [],
            'auth_domain' => 'users',
            'entities'    => [],
            'branch'      => '',
        ];
    }

    private function actingAsRole(string $role): static
    {
        Session::put('hr_user', $this->migratedTestUser($this->sessionUser($role)));

        return $this;
    }

    #[Test]
    public function lookup_finds_employee_by_exact_id(): void
    {
        $this->actingAsRole('Admin');

        $this->getJson('/hr/employees/lookup?q=2019031401')
            ->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonPath('data.0.employeeId', '2019031401')
            ->assertJsonPath('data.0.fullName', 'Budi Santoso')
            ->assertJsonPath('data.0.division', 'GA')
            ->assertJsonPath('data.0.department', 'Facility Management');
    }

    #[Test]
    public function lookup_finds_employee_by_name_and_returns_division(): void
    {
        $this->actingAsRole('Admin');

        $this->getJson('/hr/employees/lookup?q=Rahayu')
            ->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonPath('data.0.employeeId', '2021072301')
            ->assertJsonPath('data.0.fullName', 'Siti Rahayu')
            ->assertJsonPath('data.0.division', 'Quality Control')
            ->assertJsonPath('data.0.department', 'Quality Management System');
    }

    #[Test]
    public function lookup_finds_employee_by_department(): void
    {
        $this->actingAsRole('Admin');

        $this->getJson('/hr/employees/lookup?q=Warehouse%20Operations')
            ->assertOk()
            ->assertJsonPath('data.0.employeeId', '2022100701');
    }

    #[Test]
    public function lookup_returns_empty_when_no_match_or_blank_query(): void
    {
        $this->actingAsRole('Admin');

        $this->getJson('/hr/employees/lookup?q=ZZZZ-NOT-FOUND')
            ->assertOk()
            ->assertJsonCount(0, 'data');

        $this->getJson('/hr/employees/lookup?q=')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    #[Test]
    public function user_can_lookup_employees_with_module_view_permission(): void
    {
        $this->actingAsRole('User');

        $this->getJson('/hr/employees/lookup?q=Budi')
            ->assertOk()
            ->assertJsonPath('data.0.employeeId', '2019031401');
    }

    #[Test]
    public function user_can_lookup_certification_employee_without_view_employees_permission(): void
    {
        $this->actingAsRole('User');

        $this->getJson('/hr/employees/lookup?q=Siti')
            ->assertOk()
            ->assertJsonPath('data.0.employeeId', '2021072301');
    }

    #[Test]
    public function provider_is_static_and_contains_certification_referenced_employees(): void
    {
        $repo = new LocalEmployeeRepository();

        $this->assertSame(13, $repo->getAll()->count());
        $this->assertNotNull($repo->findById('2019031401'));
        $this->assertNotNull($repo->findById('2021072301'));
        $this->assertNotNull($repo->findById('2018110201'));
        $this->assertNotNull($repo->findById('2022100701'));
        $this->assertNull($repo->findById('NOPE-001'));
        // Searchable by name and id through the repository contract.
        $this->assertSame(1, $repo->getAll(['search' => 'Rahayu'])->count());
        $this->assertSame(1, $repo->getAll(['search' => '2019031401'])->count());
    }
}
