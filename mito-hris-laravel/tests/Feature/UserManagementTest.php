<?php

namespace Tests\Feature;

use App\Repositories\Contracts\AuditLogRepositoryInterface;
use App\Repositories\Contracts\MprRequestorRepositoryInterface;
use App\Repositories\Contracts\UserPermissionRepositoryInterface;
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
            ->assertSee('admin@example.test')
            ->assertSee('id="userEditEmail" name="email"', false)
            ->assertDontSee('id="userEditEmail" readonly', false);
    }

    public function test_user_management_shows_success_and_error_as_floating_toast(): void
    {
        $this->actingAsRole('Super Admin');
        $repository = Mockery::mock(UserRepositoryInterface::class);
        $repository->shouldReceive('getAll')->twice()->andReturn([]);
        $this->app->instance(UserRepositoryInterface::class, $repository);

        $this->withSession(['success' => 'Pengguna berhasil diperbarui.'])
            ->get(route('hr.users.index'))
            ->assertOk()
            ->assertSee('window.__flashToasts', false)
            ->assertSee('"type":"success"', false)
            ->assertSee('Pengguna berhasil diperbarui.', false);

        $this->withSession(['error' => 'Pengguna gagal disimpan ke sheet Users.'])
            ->get(route('hr.users.index'))
            ->assertOk()
            ->assertSee('window.__flashToasts', false)
            ->assertSee('"type":"error"', false)
            ->assertSee('Pengguna gagal disimpan ke sheet Users.', false);
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
        $repository->shouldReceive('getAll')->once()->andReturn([]);
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
                'email' => 'admin@example.test',
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
            ->assertSee('Manager Test')
            ->assertSee('id="mprEditEmail" name="email"', false)
            ->assertDontSee('id="mprEditEmail" readonly', false);
    }

    public function test_mpr_requestors_show_success_as_floating_toast(): void
    {
        $this->actingAsRole('Super Admin');
        $repository = Mockery::mock(MprRequestorRepositoryInterface::class);
        $repository->shouldReceive('getAll')->once()->andReturn([]);
        $this->app->instance(MprRequestorRepositoryInterface::class, $repository);

        $this->withSession(['success' => 'MPR requestor berhasil diperbarui.'])
            ->get(route('hr.mpr-requestors.index'))
            ->assertOk()
            ->assertSee('window.__flashToasts', false)
            ->assertSee('"type":"success"', false)
            ->assertSee('MPR requestor berhasil diperbarui.', false);
    }

    public function test_mpr_requestor_search_includes_records_outside_the_current_page(): void
    {
        $this->actingAsRole('Super Admin');
        $requestors = [];
        for ($i = 1; $i <= 10; $i++) {
            $requestors[] = [
                'Email' => "req{$i}@example.test",
                'Username' => "req{$i}",
                'Full Name' => "Requestor {$i}",
                'Job Position' => 'Staff',
                'Role' => 'Manpower',
                'Status' => 'Active',
            ];
        }
        $requestors[] = [
            'Email' => 'zebra@example.test',
            'Username' => 'zebra.user',
            'Full Name' => 'Zebra Khusus',
            'Job Position' => 'Analyst',
            'Role' => 'Manpower',
            'Status' => 'Inactive',
        ];

        $repository = Mockery::mock(MprRequestorRepositoryInterface::class);
        $repository->shouldReceive('getAll')->andReturn($requestors);
        $this->app->instance(MprRequestorRepositoryInterface::class, $repository);

        $this->get(route('hr.mpr-requestors.index', ['per_page' => 10]))
            ->assertOk()
            ->assertSee('Requestor 1')
            ->assertDontSee('Zebra Khusus');

        $this->get(route('hr.mpr-requestors.index', ['search' => 'Zebra', 'per_page' => 10]))
            ->assertOk()
            ->assertSee('Zebra Khusus')
            ->assertSee('zebra@example.test')
            ->assertDontSee('Requestor 1');

        $this->get(route('hr.mpr-requestors.index', ['status' => 'inactive', 'per_page' => 10]))
            ->assertOk()
            ->assertSee('Zebra Khusus')
            ->assertDontSee('Requestor 1');
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
        $repository->shouldReceive('findByEmail')->once()->with('new.requestor@example.test')->andReturn(null);
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

    public function test_super_admin_cannot_add_mpr_requestor_with_existing_email(): void
    {
        $this->actingAsRole('Super Admin');
        $repository = Mockery::mock(MprRequestorRepositoryInterface::class);
        $repository->shouldReceive('findByEmail')->once()->with('new.requestor@example.test')->andReturn([
            'Email' => 'new.requestor@example.test',
        ]);
        $repository->shouldNotReceive('create');
        $this->app->instance(MprRequestorRepositoryInterface::class, $repository);

        $this->withoutMiddleware(VerifyCsrfToken::class)
            ->from(route('hr.mpr-requestors.index'))
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
            ->assertSessionHasErrors(['email' => 'Email sudah digunakan.']);
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
                'email' => 'manager@example.test',
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

    public function test_super_admin_can_change_user_email_and_reassign_permissions(): void
    {
        $this->actingAsRole('Super Admin');
        $existing = [
            'Email' => 'admin@example.test',
            'Username' => 'admin',
            'Full Name' => 'Admin Test',
            'Role' => 'User',
            'Status' => 'Active',
        ];
        $updated = array_merge($existing, ['Email' => 'admin.updated@example.test']);
        $repository = Mockery::mock(UserRepositoryInterface::class);
        $repository->shouldReceive('findByEmail')->once()->with('admin@example.test')->andReturn($existing);
        $repository->shouldReceive('getAll')->once()->andReturn([$existing]);
        $repository->shouldReceive('updateByEmail')->once()->with('admin@example.test', Mockery::on(function (array $data): bool {
            return ($data['email'] ?? null) === 'admin.updated@example.test'
                && $data['username'] === 'admin'
                && $data['fullName'] === 'Admin Test';
        }));
        $repository->shouldReceive('findByEmail')->once()->with('admin.updated@example.test')->andReturn($updated);
        $this->app->instance(UserRepositoryInterface::class, $repository);

        $permissions = Mockery::mock(UserPermissionRepositoryInterface::class);
        $permissions->shouldReceive('reassignEmail')->once()->with('admin@example.test', 'admin.updated@example.test');
        $this->app->instance(UserPermissionRepositoryInterface::class, $permissions);

        $audit = Mockery::mock(AuditLogRepositoryInterface::class);
        $audit->shouldReceive('log')->once()->withArgs(function (...$args): bool {
            return $args[0] === 'User'
                && $args[1] === 'admin.updated@example.test'
                && $args[2] === 'UPDATE'
                && $args[3] === 'Email'
                && $args[4] === 'admin@example.test'
                && $args[5] === 'admin.updated@example.test';
        })->andReturnTrue();
        $this->app->instance(AuditLogRepositoryInterface::class, $audit);

        $this->withoutMiddleware(VerifyCsrfToken::class)
            ->put(route('hr.users.update', ['email' => 'admin@example.test']), [
                'name' => 'Admin Test',
                'email' => 'admin.updated@example.test',
                'username' => 'admin',
                'role' => 'User',
                'status' => 'Active',
            ])
            ->assertRedirect(route('hr.users.index'))
            ->assertSessionHas('success');
    }

    public function test_super_admin_cannot_change_user_email_to_an_existing_email(): void
    {
        $this->actingAsRole('Super Admin');
        $existing = [
            'Email' => 'admin@example.test',
            'Username' => 'admin',
            'Full Name' => 'Admin Test',
            'Role' => 'User',
            'Status' => 'Active',
        ];
        $repository = Mockery::mock(UserRepositoryInterface::class);
        $repository->shouldReceive('findByEmail')->once()->with('admin@example.test')->andReturn($existing);
        $repository->shouldReceive('getAll')->once()->andReturn([
            $existing,
            ['Email' => 'other@example.test', 'Username' => 'other'],
        ]);
        $repository->shouldNotReceive('updateByEmail');
        $this->app->instance(UserRepositoryInterface::class, $repository);

        $this->withoutMiddleware(VerifyCsrfToken::class)
            ->from(route('hr.users.index'))
            ->put(route('hr.users.update', ['email' => 'admin@example.test']), [
                'name' => 'Admin Test',
                'email' => 'other@example.test',
                'username' => 'admin',
                'role' => 'User',
                'status' => 'Active',
            ])
            ->assertRedirect(route('hr.users.index'))
            ->assertSessionHasErrors(['email' => 'Email sudah digunakan.']);
    }

    public function test_super_admin_can_change_mpr_requestor_email(): void
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
        $updated = array_merge($existing, ['Email' => 'manager.updated@example.test']);
        $repository = Mockery::mock(MprRequestorRepositoryInterface::class);
        $repository->shouldReceive('findByEmail')->once()->with('manager@example.test')->andReturn($existing);
        $repository->shouldReceive('getAll')->once()->andReturn([$existing]);
        $repository->shouldReceive('updateByEmail')->once()->with('manager@example.test', Mockery::on(fn (array $data): bool => $data === [
            'fullName' => 'Manager Test',
            'username' => 'manager',
            'jobPosition' => 'Manager Regional',
            'role' => 'Manpower',
            'status' => 'Active',
            'email' => 'manager.updated@example.test',
        ]));
        $repository->shouldReceive('findByEmail')->once()->with('manager.updated@example.test')->andReturn($updated);
        $this->app->instance(MprRequestorRepositoryInterface::class, $repository);
        $audit = Mockery::mock(AuditLogRepositoryInterface::class);
        $audit->shouldReceive('log')->once()->withArgs(function (...$args): bool {
            return $args[0] === 'MPR Requestor' && $args[3] === 'Email' && $args[5] === 'manager.updated@example.test';
        })->andReturnTrue();
        $this->app->instance(AuditLogRepositoryInterface::class, $audit);

        $this->withoutMiddleware(VerifyCsrfToken::class)
            ->put(route('hr.mpr-requestors.update', ['email' => 'manager@example.test']), [
                'name' => 'Manager Test',
                'email' => 'manager.updated@example.test',
                'username' => 'manager',
                'job_position' => 'Manager Regional',
                'role' => 'Manpower',
                'status' => 'Active',
            ])
            ->assertRedirect(route('hr.mpr-requestors.index'))
            ->assertSessionHas('success');
    }

    public function test_force_delete_is_super_admin_only_and_removes_the_user(): void
    {
        $this->actingAsRole('Admin');
        $this->withoutMiddleware(VerifyCsrfToken::class)
            ->delete(route('hr.users.force-delete', ['email' => 'staff@example.test']))
            ->assertForbidden();

        $this->actingAsRole('Super Admin');
        $user = [
            'Email' => 'staff@example.test',
            'Full Name' => 'Staff Uji',
            'Role' => 'Staff',
            'Status' => 'Active',
        ];
        $users = Mockery::mock(UserRepositoryInterface::class);
        $users->shouldReceive('getAll')->once()->andReturn([
            $user,
            ['Email' => 'super.admin@mito.test', 'Full Name' => 'Super Admin Test', 'Role' => 'Super Admin', 'Status' => 'Active'],
        ]);
        $this->app->instance(UserRepositoryInterface::class, $users);
        $this->get(route('hr.users.index'))
            ->assertOk()
            ->assertSee('data-bs-target="#userForceDeleteModal"', false)
            ->assertSee('data-email="staff@example.test"', false)
            ->assertSee('Force delete pengguna', false);

        $users = Mockery::mock(UserRepositoryInterface::class);
        $users->shouldReceive('findByEmail')->twice()->with('staff@example.test')->andReturn($user, null);
        $users->shouldReceive('forceDeleteByEmail')->once()->with('staff@example.test');
        $users->shouldNotReceive('getAll');
        $this->app->instance(UserRepositoryInterface::class, $users);
        $permissions = Mockery::mock(UserPermissionRepositoryInterface::class);
        $permissions->shouldReceive('forceDeleteByEmail')->once()->with('staff@example.test')->andReturn(2);
        $this->app->instance(UserPermissionRepositoryInterface::class, $permissions);
        $this->mockAuditLog();

        $this->withoutMiddleware(VerifyCsrfToken::class)
            ->delete(route('hr.users.force-delete', ['email' => 'staff@example.test']))
            ->assertRedirect(route('hr.users.index'))
            ->assertSessionHas('success');
    }

    public function test_super_admin_cannot_force_delete_the_logged_in_account(): void
    {
        $this->actingAsRole('Super Admin');
        $users = Mockery::mock(UserRepositoryInterface::class);
        $users->shouldReceive('findByEmail')->once()->with('super.admin@mito.test')->andReturn([
            'Email' => 'super.admin@mito.test',
            'Full Name' => 'Super Admin Test',
            'Role' => 'Super Admin',
            'Status' => 'Active',
        ]);
        $users->shouldNotReceive('forceDeleteByEmail');
        $this->app->instance(UserRepositoryInterface::class, $users);

        $this->withoutMiddleware(VerifyCsrfToken::class)
            ->delete(route('hr.users.force-delete', ['email' => 'super.admin@mito.test']))
            ->assertRedirect(route('hr.users.index'))
            ->assertSessionHas('error', 'Akun yang sedang login tidak bisa di-force delete.');
    }

    public function test_super_admin_cannot_force_delete_the_last_active_super_admin(): void
    {
        $this->actingAsRole('Super Admin');
        $users = Mockery::mock(UserRepositoryInterface::class);
        $users->shouldReceive('findByEmail')->once()->with('root@example.test')->andReturn([
            'Email' => 'root@example.test',
            'Full Name' => 'Root',
            'Role' => 'Super Admin',
            'Status' => 'Active',
        ]);
        $users->shouldReceive('getAll')->once()->andReturn([
            ['Email' => 'root@example.test', 'Role' => 'Super Admin', 'Status' => 'Active'],
        ]);
        $users->shouldNotReceive('forceDeleteByEmail');
        $this->app->instance(UserRepositoryInterface::class, $users);

        $this->withoutMiddleware(VerifyCsrfToken::class)
            ->delete(route('hr.users.force-delete', ['email' => 'root@example.test']))
            ->assertRedirect(route('hr.users.index'))
            ->assertSessionHas('error', 'Super Admin aktif terakhir tidak bisa di-force delete.');
    }

    public function test_force_delete_removes_mpr_requestor_for_super_admin_only(): void
    {
        $this->actingAsRole('Admin');
        $this->withoutMiddleware(VerifyCsrfToken::class)
            ->delete(route('hr.mpr-requestors.force-delete', ['email' => 'manager@example.test']))
            ->assertForbidden();

        $this->actingAsRole('Super Admin');
        $requestor = [
            'Requestor ID' => 'MPR-REQ-009',
            'Email' => 'manager@example.test',
            'Full Name' => 'Manager Uji',
            'Role' => 'Manpower',
            'Status' => 'Active',
        ];
        $repository = Mockery::mock(MprRequestorRepositoryInterface::class);
        $repository->shouldReceive('getAll')->once()->andReturn([$requestor]);
        $this->app->instance(MprRequestorRepositoryInterface::class, $repository);
        $this->get(route('hr.mpr-requestors.index'))
            ->assertOk()
            ->assertSee('data-bs-target="#mprRequestorForceDeleteModal"', false)
            ->assertSee('Force delete MPR requestor', false);

        $repository = Mockery::mock(MprRequestorRepositoryInterface::class);
        $repository->shouldReceive('findByEmail')->twice()->with('manager@example.test')->andReturn($requestor, null);
        $repository->shouldReceive('forceDeleteByEmail')->once()->with('manager@example.test');
        $this->app->instance(MprRequestorRepositoryInterface::class, $repository);
        $audit = Mockery::mock(AuditLogRepositoryInterface::class);
        $audit->shouldReceive('log')->once()->withArgs(function (...$args): bool {
            return $args[0] === 'MPR Requestor' && $args[1] === 'MPR-REQ-009' && $args[2] === 'FORCE_DELETE';
        })->andReturnTrue();
        $this->app->instance(AuditLogRepositoryInterface::class, $audit);

        $this->withoutMiddleware(VerifyCsrfToken::class)
            ->delete(route('hr.mpr-requestors.force-delete', ['email' => 'manager@example.test']))
            ->assertRedirect(route('hr.mpr-requestors.index'))
            ->assertSessionHas('success');
    }

    public function test_force_delete_clears_user_permission_overrides_for_super_admin_only(): void
    {
        $this->actingAsRole('Admin');
        $this->withoutMiddleware(VerifyCsrfToken::class)
            ->deleteJson(route('hr.permissions.force-delete', ['email' => 'staff@example.test']))
            ->assertForbidden();

        $this->actingAsRole('Super Admin');
        $users = Mockery::mock(UserRepositoryInterface::class);
        $users->shouldReceive('findByEmail')->once()->with('staff@example.test')->andReturn([
            'Email' => 'staff@example.test',
            'Full Name' => 'Staff Uji',
            'Role' => 'Staff',
            'Status' => 'Active',
        ]);
        $this->app->instance(UserRepositoryInterface::class, $users);
        $permissions = Mockery::mock(UserPermissionRepositoryInterface::class);
        $permissions->shouldReceive('forceDeleteByEmail')->once()->with('staff@example.test')->andReturn(2);
        $this->app->instance(UserPermissionRepositoryInterface::class, $permissions);
        $audit = Mockery::mock(AuditLogRepositoryInterface::class);
        $audit->shouldReceive('log')->once()->withArgs(function (...$args): bool {
            return $args[0] === 'User Permission' && $args[2] === 'FORCE_DELETE' && $args[4] === 2;
        })->andReturnTrue();
        $this->app->instance(AuditLogRepositoryInterface::class, $audit);

        $this->withoutMiddleware(VerifyCsrfToken::class)
            ->deleteJson(route('hr.permissions.force-delete', ['email' => 'staff@example.test']))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('deleted', 2);
    }
}
