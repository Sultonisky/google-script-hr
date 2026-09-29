<?php

namespace Tests\Feature;

use App\Repositories\Contracts\UserPermissionRepositoryInterface;
use App\Services\PermissionResolver;
use App\Support\PermissionCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;
use Tests\TestCase;

class EmployeeActionPermissionTest extends TestCase
{
    use RefreshDatabase;

    private const ACTION_PERMISSIONS = [
        'rotate_employees',
        'offboard_employees',
        'off_contract_employees',
        'manage_warning_letters',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        config(['hris.data_driver' => 'pgsql']);
        (new \App\Providers\AppServiceProvider($this->app))->register();
    }

    private function actingAsUserWith(array $permissions, string $email = 'staff@mito.id'): void
    {
        $repository = app(UserPermissionRepositoryInterface::class);
        foreach ($permissions as $permission) {
            $repository->upsert($email, $permission, true, 'test');
        }
        app(PermissionResolver::class)->forget($email);

        Session::put('hr_user', [
            'email' => $email,
            'fullName' => 'Staff HR',
            'role' => 'User',
            'permissions' => [],
            'auth_domain' => 'users',
            'entities' => [],
            'branch' => '',
        ]);
    }

    public function test_action_permissions_are_registered(): void
    {
        foreach (self::ACTION_PERMISSIONS as $key) {
            $this->assertNotNull(PermissionCatalog::find($key), "{$key} missing from catalog");
            $this->assertSame(['view_employees'], app(PermissionResolver::class)->dependenciesFor($key));
        }
    }

    public function test_routes_use_dedicated_action_permissions(): void
    {
        $expected = [
            'hr.employees.rotate' => 'can:rotate_employees',
            'hr.export.sk-rotation' => 'can:rotate_employees',
            'hr.employees.offboard' => 'can:offboard_employees',
            'hr.export.sk-off' => 'can:offboard_employees',
            'hr.export.surat-bpjs' => 'can:offboard_employees',
            'hr.export.offboarding-bundle' => 'can:offboard_employees',
            'hr.employees.off-contract' => 'can:off_contract_employees',
            'hr.export.paklaring' => 'can:export_paklaring',
            'hr.employees.warning-letter' => 'can:manage_warning_letters',
            'hr.employees.warning-letter.download' => 'can:manage_warning_letters',
        ];

        foreach ($expected as $routeName => $permission) {
            $middleware = Route::getRoutes()->getByName($routeName)->gatherMiddleware();
            $this->assertContains($permission, $middleware, "{$routeName} must require {$permission}");
            $this->assertNotContains('can:manage_employees', $middleware, "{$routeName} must not rely on manage_employees");
        }
    }

    public function test_manage_employees_alone_cannot_run_sensitive_actions(): void
    {
        $this->actingAsUserWith(['view_employees', 'manage_employees']);

        $this->postJson('/hr/employees/EMP001/rotate')->assertForbidden();
        $this->postJson('/hr/employees/EMP001/offboard')->assertForbidden();
        $this->postJson('/hr/employees/EMP001/off-contract')->assertForbidden();
        $this->postJson('/hr/employees/EMP001/warning-letter')->assertForbidden();
        $this->get('/hr/employees/EMP001/warning-letter/DOC-1')->assertForbidden();
        $this->get('/hr/export/sk-rotation/EMP001')->assertForbidden();
        $this->get('/hr/export/sk-off/EMP001')->assertForbidden();
        $this->get('/hr/export/surat-bpjs/EMP001')->assertForbidden();
        $this->get('/hr/export/offboarding-bundle/EMP001')->assertForbidden();
        $this->get('/hr/export/paklaring/EMP001')->assertForbidden();
    }

    public function test_paklaring_allows_offboarding_or_off_contract_permission(): void
    {
        $this->actingAsUserWith(['view_employees', 'offboard_employees'], 'offboard@mito.id');
        $this->assertTrue(Gate::allows('export_paklaring'));

        $this->actingAsUserWith(['view_employees', 'off_contract_employees'], 'offcontract@mito.id');
        $this->assertTrue(Gate::allows('export_paklaring'));

        $this->actingAsUserWith(['view_employees', 'rotate_employees', 'manage_warning_letters'], 'other@mito.id');
        $this->assertFalse(Gate::allows('export_paklaring'));
    }

    public function test_each_action_permission_is_independent(): void
    {
        $this->actingAsUserWith(['view_employees', 'rotate_employees']);

        $this->assertTrue(Gate::allows('rotate_employees'));
        $this->assertFalse(Gate::allows('offboard_employees'));
        $this->assertFalse(Gate::allows('off_contract_employees'));
        $this->assertFalse(Gate::allows('manage_warning_letters'));
        $this->postJson('/hr/employees/EMP001/offboard')->assertForbidden();
    }

    public function test_grant_command_carries_over_manage_employees_and_respects_revokes(): void
    {
        foreach (['manager@mito.id', 'viewer@mito.id', 'revoked@mito.id'] as $email) {
            \App\Models\User::query()->create([
                'name' => $email, 'email' => $email, 'password' => 'x', 'role' => 'Admin', 'status' => 'Active',
            ]);
        }
        $repository = app(UserPermissionRepositoryInterface::class);
        $repository->upsert('manager@mito.id', 'view_employees', true, 'seed');
        $repository->upsert('manager@mito.id', 'manage_employees', true, 'seed');
        $repository->upsert('viewer@mito.id', 'view_employees', true, 'seed');
        $repository->upsert('revoked@mito.id', 'view_employees', true, 'seed');
        $repository->upsert('revoked@mito.id', 'manage_employees', true, 'seed');
        $repository->upsert('revoked@mito.id', 'offboard_employees', false, 'admin');

        $granted = fn (string $email): array => collect($repository->mappingsForUser($email))
            ->filter(fn (array $row): bool => $row['Granted'] === 'TRUE')
            ->pluck('Permission Key')
            ->intersect(self::ACTION_PERMISSIONS)
            ->values()
            ->all();

        $this->artisan('mito:permissions:grant-employee-actions', ['--dry-run' => true])->assertSuccessful();
        $this->assertSame([], $granted('manager@mito.id'));

        $this->artisan('mito:permissions:grant-employee-actions')->assertSuccessful();
        $this->assertEqualsCanonicalizing(self::ACTION_PERMISSIONS, $granted('manager@mito.id'));
        $this->assertSame([], $granted('viewer@mito.id'));
        $this->assertEqualsCanonicalizing(
            ['rotate_employees', 'off_contract_employees', 'manage_warning_letters'],
            $granted('revoked@mito.id')
        );

        $this->artisan('mito:permissions:grant-employee-actions')
            ->expectsOutputToContain('Mappings created: 0')
            ->assertSuccessful();
    }
}
