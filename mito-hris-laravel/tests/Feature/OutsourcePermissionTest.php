<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\OutsourceEmployee;
use App\Models\User;
use App\Repositories\Contracts\AuditLogRepositoryInterface;
use App\Repositories\Contracts\UserPermissionRepositoryInterface;
use App\Services\PermissionResolver;
use App\Support\PermissionCatalog;
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

        OutsourceEmployee::query()->create([
            'outsource_id' => 'DM20260001',
            'full_name' => 'Bayu Saputra',
            'entity' => 'PT. Mahakarya Sukses Indonesia',
            'job_title' => 'SPB/SPG Toko',
            'whatsapp_number' => '+6281234567890',
            'email' => 'bayu@example.com',
            'contract_end_date' => '2026-08-31',
            'vendor' => 'Damarindo',
            'created_by' => 'test',
        ]);
        // Legacy Outsource rows in Employee stay untouched but no longer feed the Outsource menu.
        Employee::query()->create([
            'employee_id' => 'EMP-OS-1',
            'full_name' => 'Legacy Outsource',
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
        $this->getJson('/hr/outsource/DM20260001/json')->assertForbidden();
        $this->putJson('/hr/outsource/DM20260001', ['fullName' => 'Bayu'])->assertForbidden();
        $this->postJson('/hr/outsource', ['fullName' => 'Baru', 'vendor' => 'Damarindo'])->assertForbidden();
    }

    public function test_view_outsource_is_read_only_and_reads_the_outsource_store(): void
    {
        $this->actingWithPermissions(['view_outsource']);

        $this->get('/hr/outsource')
            ->assertOk()
            ->assertSee('Bayu Saputra')
            ->assertSee('DM20260001')
            ->assertSee('Tgl Akhir Kontrak (StaffInc)')
            ->assertSee('2026-08-31')
            ->assertDontSee('Legacy Outsource')
            ->assertDontSee('id="btnAddOutsource"', false)
            ->assertDontSee('id="outsourceContractModal"', false)
            ->assertDontSee('id="outsourceFormModal"', false);

        $this->getJson('/hr/outsource/DM20260001/json')
            ->assertOk()
            ->assertJsonPath('employee.fullName', 'Bayu Saputra')
            ->assertJsonPath('employee.vendor', 'Damarindo');
        $this->getJson('/hr/outsource/EMP-OS-1/json')->assertNotFound();
        $this->getJson('/hr/outsource/EMP-IN-1/json')->assertNotFound();
        $this->getJson('/hr/employees/EMP-OS-1/json')->assertForbidden();

        $this->putJson('/hr/outsource/DM20260001', ['fullName' => 'Bayu'])->assertForbidden();
        $this->postJson('/hr/outsource/kontrak-pkwt-tad', ['employee_id' => 'DM20260001'])->assertForbidden();
    }

    public function test_vendor_filter_limits_the_list(): void
    {
        OutsourceEmployee::query()->create([
            'outsource_id' => 'DM20260002',
            'full_name' => 'Sinta Staffinc',
            'vendor' => 'StaffInc',
            'created_by' => 'test',
        ]);
        $this->actingWithPermissions(['view_outsource']);

        $this->get('/hr/outsource?vendor=StaffInc')
            ->assertOk()
            ->assertSee('Sinta Staffinc')
            ->assertDontSee('Bayu Saputra');
    }

    public function test_manage_outsource_creates_and_edits_only_outsource_records(): void
    {
        $this->actingWithPermissions(['view_outsource', 'manage_outsource', 'view_outsource_compensation', 'manage_outsource_compensation']);

        $this->get('/hr/outsource')
            ->assertOk()
            ->assertSee('id="btnAddOutsource"', false)
            ->assertSee('id="outsourceFormModal"', false);

        $this->putJson('/hr/outsource/DM20260001', [
            'fullName' => 'Bayu Saputra Baru',
            'vendor' => 'StaffInc',
        ])->assertOk()->assertJsonPath('success', true);
        $row = OutsourceEmployee::query()->where('outsource_id', 'DM20260001')->first();
        $this->assertSame('Bayu Saputra Baru', $row->full_name);
        $this->assertSame('StaffInc', $row->vendor);

        $this->putJson('/hr/outsource/DM20260001', ['vendor' => 'Vendor Lain'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('vendor');

        $created = $this->postJson('/hr/outsource', [
            'fullName' => 'Dewi Lestari',
            'vendor' => 'Damarindo',
            'whatsappNumber' => '0812-9999-0000',
            'email' => 'Dewi@Example.com',
            'bankAccount' => '1234 567 890',
            'umkAmount' => 'Rp 5.396.761',
            'basicSalary' => '3777733',
            'remarks' => "Baris 1\nBaris 2",
        ])->assertCreated()->assertJsonPath('success', true);
        $newId = $created->json('employee.outsourceId');
        $this->assertMatchesRegularExpression('/^DM\d{4}\d{4}$/', (string) $newId);
        $saved = OutsourceEmployee::query()->where('outsource_id', $newId)->first();
        $this->assertSame('+6281299990000', $saved->whatsapp_number);
        $this->assertSame('dewi@example.com', $saved->email);
        $this->assertSame('1234567890', $saved->bank_account);
        $this->assertEquals(5396761, (float) $saved->umk_amount);
        $this->assertSame("Baris 1\nBaris 2", $saved->remarks);

        $this->postJson('/hr/outsource', [
            'fullName' => 'Duplikat',
            'vendor' => 'Damarindo',
            'whatsappNumber' => '081234567890',
        ])->assertStatus(422)
            ->assertJsonPath('message', 'No WA atau email sudah terdaftar pada data outsource lain.');

        $this->putJson('/hr/outsource/EMP-IN-1', ['fullName' => 'Diubah'])->assertNotFound();
        $this->putJson('/hr/employees/EMP-IN-1', ['fullName' => 'Diubah'])->assertForbidden();
        $this->assertSame('Rina Kartika', Employee::query()->where('employee_id', 'EMP-IN-1')->first()->full_name);
        $this->assertSame('Legacy Outsource', Employee::query()->where('employee_id', 'EMP-OS-1')->first()->full_name);
    }

    public function test_compensation_columns_need_their_own_view_permission(): void
    {
        OutsourceEmployee::query()->where('outsource_id', 'DM20260001')->update([
            'basic_salary' => 2788800,
            'incentive_amount' => 1195200,
            'remarks' => 'Resign per 20/9',
        ]);
        $audit = app(AuditLogRepositoryInterface::class);
        $audit->log('Outsource', 'DM20260001', 'updated', 'Remarks', '-', 'Resign per 20/9', 'HR', 'HR Dashboard');
        $audit->log('Outsource', 'DM20260001', 'updated', 'Basic Salary', '0', '2788800', 'HR', 'HR Dashboard');
        $audit->log('Outsource', 'DM20260001', 'updated', 'Job Title', '-', 'SPB/SPG Toko', 'HR', 'HR Dashboard');

        $this->actingWithPermissions(['view_outsource']);
        $this->get('/hr/outsource')->assertOk()
            ->assertDontSee('id="osDrBasicSalary"', false)
            ->assertDontSee('id="osDrIncentive"', false)
            ->assertSee('id="osDrRemarks"', false);
        $hidden = $this->getJson('/hr/outsource/DM20260001/json')->assertOk()
            ->assertJsonPath('employee.fullName', 'Bayu Saputra')
            ->assertJsonMissingPath('employee.basicSalary')
            ->assertJsonMissingPath('employee.incentiveAmount')
            ->assertJsonPath('employee.remarks', 'Resign per 20/9');
        $this->assertEqualsCanonicalizing(['Remarks', 'Job Title'], collect($hidden->json('auditLogs'))->pluck('Field')->all());

        $this->actingWithPermissions(['view_outsource', 'view_outsource_compensation']);
        $this->get('/hr/outsource')->assertOk()
            ->assertSee('id="osDrBasicSalary"', false)
            ->assertSee('id="osDrIncentive"', false);
        $visible = $this->getJson('/hr/outsource/DM20260001/json')->assertOk()
            ->assertJsonPath('employee.basicSalary', 2788800)
            ->assertJsonPath('employee.incentiveAmount', 1195200)
            ->assertJsonPath('employee.remarks', 'Resign per 20/9');
        $this->assertCount(3, $visible->json('auditLogs'));
    }

    public function test_manage_without_compensation_permission_cannot_change_salary_columns(): void
    {
        OutsourceEmployee::query()->where('outsource_id', 'DM20260001')->update(['basic_salary' => 2788800, 'remarks' => 'Asli']);
        $this->actingWithPermissions(['view_outsource', 'manage_outsource', 'view_outsource_compensation']);

        $this->get('/hr/outsource')->assertOk()
            ->assertSee('id="osfUmk"', false)
            ->assertDontSee('id="osfBasicSalary"', false)
            ->assertDontSee('id="osfIncentive"', false)
            ->assertSee('id="osfRemarks"', false)
            ->assertDontSee('30%');

        $this->putJson('/hr/outsource/DM20260001', [
            'jobTitle' => 'GTM',
            'basicSalary' => '9999999',
            'incentiveAmount' => '5000000',
            'remarks' => 'Diubah',
        ])->assertOk()->assertJsonPath('success', true);
        $row = OutsourceEmployee::query()->where('outsource_id', 'DM20260001')->first();
        $this->assertSame('GTM', $row->job_title);
        $this->assertEquals(2788800, (float) $row->basic_salary);
        $this->assertNull($row->incentive_amount);
        $this->assertSame('Diubah', $row->remarks);

        $newId = $this->postJson('/hr/outsource', [
            'fullName' => 'Dewi Lestari',
            'vendor' => 'Damarindo',
            'basicSalary' => '3777733',
            'remarks' => 'Titipan',
        ])->assertCreated()->json('employee.outsourceId');
        $saved = OutsourceEmployee::query()->where('outsource_id', $newId)->first();
        $this->assertNull($saved->basic_salary);
        $this->assertSame('Titipan', $saved->remarks);

        $this->actingWithPermissions(['view_outsource', 'manage_outsource', 'view_outsource_compensation', 'manage_outsource_compensation']);
        $this->get('/hr/outsource')->assertOk()->assertSee('id="osfBasicSalary"', false)->assertSee('id="osfIncentive"', false);
        $this->putJson('/hr/outsource/DM20260001', ['basicSalary' => '3000000', 'incentiveAmount' => '900000'])->assertOk();
        $row->refresh();
        $this->assertEquals(3000000, (float) $row->basic_salary);
        $this->assertEquals(900000, (float) $row->incentive_amount);
    }

    public function test_compensation_permissions_are_in_catalog_with_dependencies(): void
    {
        $this->assertNotNull(PermissionCatalog::find('view_outsource_compensation'));
        $this->assertNotNull(PermissionCatalog::find('manage_outsource_compensation'));

        $normalized = app(PermissionResolver::class)->normalizeDependencies(['manage_outsource_compensation' => true]);
        foreach (['view_outsource', 'manage_outsource', 'view_outsource_compensation'] as $dependency) {
            $this->assertTrue($normalized[$dependency] ?? false, $dependency);
        }
    }

    public function test_grant_outsource_compensation_keeps_existing_access_and_respects_revokes(): void
    {
        foreach (['viewer@mito.id', 'manager@mito.id', 'revoked@mito.id'] as $email) {
            User::query()->create([
                'name' => $email, 'email' => $email, 'password' => 'x', 'role' => 'Admin', 'status' => 'Active',
            ]);
        }
        $repository = app(UserPermissionRepositoryInterface::class);
        $repository->upsert('viewer@mito.id', 'view_outsource', true, 'seed');
        $repository->upsert('manager@mito.id', 'view_outsource', true, 'seed');
        $repository->upsert('manager@mito.id', 'manage_outsource', true, 'seed');
        $repository->upsert('revoked@mito.id', 'view_outsource', true, 'seed');
        $repository->upsert('revoked@mito.id', 'manage_outsource', true, 'seed');
        $repository->upsert('revoked@mito.id', 'view_outsource_compensation', false, 'admin');

        $granted = fn (string $email): array => collect($repository->mappingsForUser($email))
            ->filter(fn (array $row): bool => $row['Granted'] === 'TRUE')
            ->pluck('Permission Key')
            ->intersect(['view_outsource_compensation', 'manage_outsource_compensation'])
            ->values()
            ->all();

        $this->artisan('mito:permissions:grant-outsource-compensation', ['--dry-run' => true])->assertSuccessful();
        $this->assertSame([], $granted('manager@mito.id'));

        $this->artisan('mito:permissions:grant-outsource-compensation')->assertSuccessful();
        $this->assertSame(['view_outsource_compensation'], $granted('viewer@mito.id'));
        $this->assertEqualsCanonicalizing(['view_outsource_compensation', 'manage_outsource_compensation'], $granted('manager@mito.id'));
        $this->assertSame([], $granted('revoked@mito.id'));

        $this->artisan('mito:permissions:grant-outsource-compensation')
            ->expectsOutputToContain('Mappings created: 0')
            ->assertSuccessful();
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
