<?php

namespace Tests\Feature;

use App\Repositories\Contracts\AuditLogRepositoryInterface;
use App\Repositories\Contracts\MprRequestorRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Mockery;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    private function actingAsRole(string $role): void
    {
        Session::put('hr_user', [
            'email' => strtolower(str_replace(' ', '.', $role)) . '@mito.test',
            'fullName' => $role . ' Test',
            'role' => $role,
            'permissions' => config('hris.auth.role_permissions.' . $role, []),
            'auth_domain' => 'users',
            'entities' => [],
            'branch' => '',
        ]);
    }

    private function mockAuditLog(): void
    {
        $audit = Mockery::mock(AuditLogRepositoryInterface::class);
        $audit->shouldReceive('log')->once()->andReturnTrue();
        $this->app->instance(AuditLogRepositoryInterface::class, $audit);
    }

    public function test_super_admin_can_show_user_management_with_sheet_users(): void
    {
        $this->actingAsRole('Super Admin');
        $users = [[
            'Email' => 'admin@example.test',
            'Username' => 'admin',
            'Full Name' => 'Admin Test',
            'Role' => 'Super Admin',
            'Status' => 'Active',
        ]];
        $repository = Mockery::mock(UserRepositoryInterface::class);
        $repository->shouldReceive('getAll')->once()->andReturn($users);
        $this->app->instance(UserRepositoryInterface::class, $repository);

        $this->get(route('hr.users.index'))
            ->assertOk()
            ->assertViewIs('hr.users.index')
            ->assertSee('Admin Test')
            ->assertSee('admin@example.test');
    }

    public function test_non_super_admin_cannot_access_user_management(): void
    {
        $this->actingAsRole('Admin');

        $this->get(route('hr.users.index'))->assertForbidden();
    }

    public function test_super_admin_can_add_user_with_hashed_password(): void
    {
        $this->actingAsRole('Super Admin');
        $repository = Mockery::mock(UserRepositoryInterface::class);
        $repository->shouldReceive('create')->once()->with(Mockery::on(function (array $data): bool {
            return $data['email'] === 'new.user@example.test'
                && $data['fullName'] === 'New User'
                && $data['role'] === 'Admin'
                && $data['status'] === 'Active'
                && Hash::check('secret-password', $data['passwordHash'])
                && $data['passwordHash'] !== 'secret-password';
        }));
        $repository->shouldReceive('findByEmail')->once()->with('new.user@example.test')->andReturn([
            'Email' => 'new.user@example.test',
        ]);
        $this->app->instance(UserRepositoryInterface::class, $repository);
        $this->mockAuditLog();

        $this->withoutMiddleware(VerifyCsrfToken::class)
            ->post(route('hr.users.store'), [
                'name' => 'New User',
                'email' => 'new.user@example.test',
                'role' => 'Admin',
                'password' => 'secret-password',
                'password_confirmation' => 'secret-password',
            ])
            ->assertRedirect(route('hr.users.index'))
            ->assertSessionHas('success');
    }

    public function test_super_admin_can_show_mpr_requestors_with_sheet_data(): void
    {
        $this->actingAsRole('Super Admin');
        $requestors = [[
            'Requestor ID' => 'MPR-REQ-001',
            'Email' => 'manager@example.test',
            'Username' => 'manager',
            'Full Name' => 'Manager Test',
            'Role' => 'Manpower',
            'Status' => 'Active',
            'Entity' => 'MSI',
            'Branch' => 'Jakarta',
        ]];
        $repository = Mockery::mock(MprRequestorRepositoryInterface::class);
        $repository->shouldReceive('getAll')->once()->andReturn($requestors);
        $this->app->instance(MprRequestorRepositoryInterface::class, $repository);

        $this->get(route('hr.mpr-requestors.index'))
            ->assertOk()
            ->assertViewIs('hr.mpr-requestors.index')
            ->assertSee('Manager Test')
            ->assertSee('MSI');
    }

    public function test_non_super_admin_cannot_access_mpr_requestors(): void
    {
        $this->actingAsRole('Admin');

        $this->get(route('hr.mpr-requestors.index'))->assertForbidden();
    }

    public function test_super_admin_can_add_mpr_requestor_with_hashed_password(): void
    {
        $this->actingAsRole('Super Admin');
        $repository = Mockery::mock(MprRequestorRepositoryInterface::class);
        $repository->shouldReceive('create')->once()->with(Mockery::on(function (array $data): bool {
            return $data['email'] === 'new.requestor@example.test'
                && $data['username'] === 'newrequestor'
                && $data['fullName'] === 'New Requestor'
                && $data['role'] === 'Manpower'
                && $data['status'] === 'Active'
                && $data['entity'] === 'MSI'
                && $data['branch'] === 'Jakarta'
                && Hash::check('requestor-password', $data['passwordHash'])
                && $data['passwordHash'] !== 'requestor-password';
        }));
        $repository->shouldReceive('findByEmail')->once()->with('new.requestor@example.test')->andReturn([
            'Email' => 'new.requestor@example.test',
        ]);
        $this->app->instance(MprRequestorRepositoryInterface::class, $repository);

        $this->withoutMiddleware(VerifyCsrfToken::class)
            ->post(route('hr.mpr-requestors.store'), [
                'name' => 'New Requestor',
                'email' => 'new.requestor@example.test',
                'username' => 'newrequestor',
                'role' => 'Manpower',
                'entity' => 'MSI',
                'branch' => 'Jakarta',
                'password' => 'requestor-password',
                'password_confirmation' => 'requestor-password',
            ])
            ->assertRedirect(route('hr.mpr-requestors.index'))
            ->assertSessionHas('success');
    }
}
