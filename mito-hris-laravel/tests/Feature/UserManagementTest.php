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
        Session::put('hr_user', $this->migratedTestUser([
            'email' => strtolower(str_replace(' ', '.', $role)) . '@mito.test',
            'fullName' => $role . ' Test',
            'role' => $role,
            'permissions' => config('hris.auth.role_permissions.' . $role, []),
            'auth_domain' => 'users',
        ]));
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
                && isset($data['createdAt'])
                && isset($data['updatedAt'])
                && $data['createdAt'] === $data['updatedAt']
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
                'username' => 'newuser',
                'role' => 'Admin',
                'password' => 'secret-password',
                'password_confirmation' => 'secret-password',
            ])
            ->assertRedirect(route('hr.users.index'))
            ->assertSessionHas('success');
    }

    public function test_super_admin_cannot_create_user_with_legacy_department_role(): void
    {
        $this->actingAsRole('Super Admin');

        $this->withoutMiddleware(VerifyCsrfToken::class)
            ->post(route('hr.users.store'), [
                'name' => 'Legacy Role User',
                'email' => 'legacy.user@example.test',
                'username' => 'legacyuser',
                'role' => 'Department Access',
                'password' => 'secret-password',
                'password_confirmation' => 'secret-password',
            ])
            ->assertSessionHasErrors('role');
    }

    public function test_super_admin_can_edit_user_and_audits_changed_fields(): void
    {
        $this->actingAsRole('Super Admin');
        $existing = [
            'Email' => 'admin@example.test',
            'Username' => 'admin',
            'Full Name' => 'Admin Test',
            'Role' => 'User',
            'Status' => 'Active',
            'Password Hash' => Hash::make('old-password'),
        ];
        $updated = array_merge($existing, ['Username' => 'admin.updated', 'Full Name' => 'Admin Updated', 'Role' => 'Admin', 'Status' => 'Inactive']);
        $repository = Mockery::mock(UserRepositoryInterface::class);
        $repository->shouldReceive('findByEmail')->once()->with('admin@example.test')->andReturn($existing);
        $repository->shouldReceive('getAll')->once()->andReturn([$existing]);
        $repository->shouldReceive('updateByEmail')->once()->with('admin@example.test', Mockery::on(function (array $data): bool {
            return $data['fullName'] === 'Admin Updated'
                && $data['username'] === 'admin.updated'
                && $data['role'] === 'Admin'
                && $data['status'] === 'Inactive'
                && isset($data['updatedAt'])
                && !empty($data['updatedAt']);
        }));
        $repository->shouldReceive('findByEmail')->once()->with('admin@example.test')->andReturn($updated);
        $this->app->instance(UserRepositoryInterface::class, $repository);
        $audit = Mockery::mock(AuditLogRepositoryInterface::class);
        $audit->shouldReceive('log')->times(4)->withArgs(function (...$args): bool {
            return $args[0] === 'User' && $args[2] === 'UPDATE' && $args[7] === 'Dashboard';
        })->andReturnTrue();
        $this->app->instance(AuditLogRepositoryInterface::class, $audit);

        $this->withoutMiddleware(VerifyCsrfToken::class)
            ->put(route('hr.users.update', ['email' => 'admin@example.test']), [
                'name' => 'Admin Updated',
                'username' => 'admin.updated',
                'role' => 'Admin',
                'status' => 'Inactive',
            ])
            ->assertRedirect(route('hr.users.index'))
            ->assertSessionHas('success');
    }

    public function test_non_super_admin_cannot_create_or_edit_user(): void
    {
        $this->actingAsRole('Admin');

        $this->post(route('hr.users.store'), [])->assertForbidden();
        $this->put(route('hr.users.update', ['email' => 'admin@example.test']), [])->assertForbidden();
    }

    public function test_super_admin_can_show_mpr_requestors_with_sheet_data(): void
    {
        $this->actingAsRole('Super Admin');
        $requestors = [[
            'Requestor ID' => 'MPR-REQ-001',
            'Email' => 'manager@example.test',
            'Username' => 'manager',
            'Full Name' => 'Manager Test',
            'Job Position' => 'Manager Regional',
            'Role' => 'Manpower',
            'Status' => 'Active',
        ]];
        $repository = Mockery::mock(MprRequestorRepositoryInterface::class);
        $repository->shouldReceive('getAll')->once()->andReturn($requestors);
        $this->app->instance(MprRequestorRepositoryInterface::class, $repository);

        $this->get(route('hr.mpr-requestors.index'))
            ->assertOk()
            ->assertViewIs('hr.mpr-requestors.index')
            ->assertSee('Manager Test');
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
                && $data['jobPosition'] === 'Regional Manager'
                && $data['role'] === 'Manpower'
                && $data['status'] === 'Active'
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
                'job_position' => 'Regional Manager',
                'role' => 'Manpower',
                'password' => 'requestor-password',
                'password_confirmation' => 'requestor-password',
            ])
            ->assertRedirect(route('hr.mpr-requestors.index'))
            ->assertSessionHas('success');
    }

    public function test_super_admin_can_edit_mpr_requestor_and_audits_changes(): void
    {
        $this->actingAsRole('Super Admin');
        $existing = [
            'Requestor ID' => 'MPR-REQ-001',
            'Email' => 'manager@example.test',
            'Username' => 'manager',
            'Full Name' => 'Manager Test',
            'Job Position' => 'Manager Regional',
            'Role' => 'Manpower',
            'Status' => 'Active',
        ];
        $updated = array_merge($existing, ['Full Name' => 'Manager Updated', 'Job Position' => 'Regional Head']);
        $repository = Mockery::mock(MprRequestorRepositoryInterface::class);
        $repository->shouldReceive('findByEmail')->once()->with('manager@example.test')->andReturn($existing);
        $repository->shouldReceive('getAll')->once()->andReturn([$existing]);
        $repository->shouldReceive('updateByEmail')->once()->with('manager@example.test', Mockery::on(fn(array $data): bool => $data === [
            'fullName' => 'Manager Updated',
            'username' => 'manager',
            'jobPosition' => 'Regional Head',
            'role' => 'Manpower',
            'status' => 'Active',
        ]));
        $repository->shouldReceive('findByEmail')->once()->with('manager@example.test')->andReturn($updated);
        $this->app->instance(MprRequestorRepositoryInterface::class, $repository);
        $audit = Mockery::mock(AuditLogRepositoryInterface::class);
        $audit->shouldReceive('log')->times(2)->withArgs(function (...$args): bool {
            return $args[0] === 'MPR Requestor' && $args[2] === 'UPDATE' && $args[7] === 'Dashboard';
        })->andReturnTrue();
        $this->app->instance(AuditLogRepositoryInterface::class, $audit);

        $this->withoutMiddleware(VerifyCsrfToken::class)
            ->put(route('hr.mpr-requestors.update', ['email' => 'manager@example.test']), [
                'name' => 'Manager Updated',
                'username' => 'manager',
                'job_position' => 'Regional Head',
                'role' => 'Manpower',
                'status' => 'Active',
            ])
            ->assertRedirect(route('hr.mpr-requestors.index'))
            ->assertSessionHas('success');
    }

    public function test_non_super_admin_cannot_create_or_edit_mpr_requestor(): void
    {
        $this->actingAsRole('Admin');

        $this->post(route('hr.mpr-requestors.store'), [])->assertForbidden();
        $this->put(route('hr.mpr-requestors.update', ['email' => 'manager@example.test']), [])->assertForbidden();
    }
}
