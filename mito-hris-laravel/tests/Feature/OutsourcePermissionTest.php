<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\User;
use App\Repositories\Contracts\UserPermissionRepositoryInterface;
use App\Services\PermissionResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Session;
use Tests\TestCase;

class OutsourcePermissionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['hris.data_driver' => 'pgsql']);
        (new \App\Providers\AppServiceProvider($this->app))->register();

        Employee::query()->create([
            'employee_id' => 'EMP-OS-1',
            'full_name' => 'Bayu Saputra',
            'branch_name' => 'PT Mahakarya Sukses Indonesia',
            'job_position' => 'Operator',
            'status_employee' => 'Outsource',
            'outsource_vendor' => 'PT Vendor Jaya',
        ]);
        Employee::query()->create([
            'employee_id' => 'EMP-IN-1',
            'full_name' => 'Rina Kartika',
            'branch_name' => 'PT Mahakarya Sukses Indonesia',
            'job_position' => 'Staff Finance',
            'status_employee' => 'PKWT',
        ]);
    }

    private function actingWithPermissions(array $permissions): void
    {
        $email = 'custom.user@mito.id';
        $repository = app(UserPermissionRepositoryInterface::class);
        foreach ($permissions as $permission) {
            $repository->upsert($email, $permission, true, 'test');
        }
        app(PermissionResolver::class)->forget($email);

        Session::put('hr_user', [
            'email' => $email,
            'fullName' => 'Custom User',
            'role' => 'User',
            'permissions' => $permissions,
            'auth_domain' => 'users',
            'entities' => [],
            'branch' => '',
        ]);
    }

    public function test_employee_permissions_alone_no_longer_open_outsource_menu(): void
    {
        $this->actingWithPermissions(['view_employees', 'manage_employees']);

        $this->get('/hr/outsource')->assertForbidden();
        $this->getJson('/hr/outsource/EMP-OS-1/json')->assertForbidden();
        $this->putJson('/hr/outsource/EMP-OS-1', ['fullName' => 'Bayu'])->assertForbidden();
        $this->postJson('/hr/outsource', ['fullName' => 'Baru', 'outsourceVendor' => 'X'])->assertForbidden();
    }

    public function test_view_outsource_is_read_only_and_scoped_to_outsource_employees(): void
    {
        $this->actingWithPermissions(['view_outsource']);

        $this->get('/hr/outsource')
            ->assertOk()
            ->assertSee('Bayu Saputra')
            ->assertDontSee('id="btnAddOutsource"', false)
            ->assertDontSee('id="outsourceContractModal"', false);

        $this->getJson('/hr/outsource/EMP-OS-1/json')
            ->assertOk()
            ->assertJsonPath('employee.fullName', 'Bayu Saputra');
        $this->getJson('/hr/outsource/EMP-IN-1/json')->assertNotFound();
        $this->getJson('/hr/employees/EMP-OS-1/json')->assertForbidden();

        $this->putJson('/hr/outsource/EMP-OS-1', ['fullName' => 'Bayu'])->assertForbidden();
        $this->postJson('/hr/outsource/kontrak-pkwt-tad', ['employee_id' => 'EMP-OS-1'])->assertForbidden();
    }

    public function test_manage_outsource_edits_outsource_but_cannot_change_status_or_touch_internal_employees(): void
    {
        $this->actingWithPermissions(['view_outsource', 'manage_outsource']);

        $this->get('/hr/outsource')
            ->assertOk()
            ->assertSee('id="btnAddOutsource"', false);

        $this->putJson('/hr/outsource/EMP-OS-1', [
            'fullName' => 'Bayu Saputra Baru',
            'statusEmployee' => 'Outsource',
        ])->assertOk()->assertJsonPath('success', true);
        $this->assertSame('Bayu Saputra Baru', Employee::query()->where('employee_id', 'EMP-OS-1')->first()->full_name);

        $this->putJson('/hr/outsource/EMP-OS-1', [
            'fullName' => 'Bayu Saputra Baru',
            'statusEmployee' => 'PKWT',
        ])->assertStatus(422)
            ->assertJsonPath('message', 'Status karyawan outsource tidak dapat diubah dari menu Outsource.');
        $this->assertSame('Outsource', Employee::query()->where('employee_id', 'EMP-OS-1')->first()->status_employee);

        $this->putJson('/hr/outsource/EMP-IN-1', ['fullName' => 'Diubah'])->assertNotFound();
        $this->putJson('/hr/employees/EMP-IN-1', ['fullName' => 'Diubah'])->assertForbidden();
        $this->assertSame('Rina Kartika', Employee::query()->where('employee_id', 'EMP-IN-1')->first()->full_name);
    }

    public function test_grant_outsource_access_carries_over_legacy_access_and_respects_revokes(): void
    {
        foreach (['viewer@mito.id', 'manager@mito.id', 'revoked@mito.id'] as $email) {
            User::query()->create([
                'name' => $email, 'email' => $email, 'password' => 'x', 'role' => 'Admin', 'status' => 'Active',
            ]);
        }
        $repository = app(UserPermissionRepositoryInterface::class);
        $repository->upsert('viewer@mito.id', 'view_employees', true, 'seed');
        $repository->upsert('manager@mito.id', 'view_employees', true, 'seed');
        $repository->upsert('manager@mito.id', 'manage_employees', true, 'seed');
        $repository->upsert('revoked@mito.id', 'view_employees', true, 'seed');
        $repository->upsert('revoked@mito.id', 'manage_employees', true, 'seed');
        $repository->upsert('revoked@mito.id', 'view_outsource', false, 'admin');

        $granted = fn (string $email): array => collect($repository->mappingsForUser($email))
            ->filter(fn (array $row): bool => $row['Granted'] === 'TRUE')
            ->pluck('Permission Key')
            ->intersect(['view_outsource', 'manage_outsource'])
            ->values()
            ->all();

        $this->artisan('mito:permissions:grant-outsource-access', ['--dry-run' => true])->assertSuccessful();
        $this->assertSame([], $granted('manager@mito.id'));

        $this->artisan('mito:permissions:grant-outsource-access')->assertSuccessful();
        $this->assertSame(['view_outsource'], $granted('viewer@mito.id'));
        $this->assertEqualsCanonicalizing(['view_outsource', 'manage_outsource'], $granted('manager@mito.id'));
        $this->assertSame([], $granted('revoked@mito.id'));

        $this->artisan('mito:permissions:grant-outsource-access')
            ->expectsOutputToContain('Mappings created: 0')
            ->assertSuccessful();
    }
}
