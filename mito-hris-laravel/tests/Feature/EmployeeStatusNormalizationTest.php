<?php

namespace Tests\Feature;

use App\Enums\EmployeeStatus;
use App\Models\Employee;
use App\Repositories\Contracts\EmployeeRepositoryInterface;
use App\Services\EmployeeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Session;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class EmployeeStatusNormalizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['hris.data_driver' => 'pgsql']);
        (new \App\Providers\AppServiceProvider($this->app))->register();
    }

    private function employee(string $id, string $status): void
    {
        Employee::query()->create([
            'employee_id' => $id,
            'full_name' => "Karyawan {$id}",
            'status_employee' => $status,
            'join_date' => '2025-01-06',
            'end_date_contract' => '2026-12-31',
        ]);
    }

    public function test_legacy_values_map_to_three_employment_types(): void
    {
        $this->assertSame(EmployeeStatus::PERMANENT, EmployeeStatus::fromValue(' pkwtt '));
        $this->assertSame(EmployeeStatus::PERMANENT, EmployeeStatus::fromValue('Permanent (PKWTT)'));
        $this->assertSame(EmployeeStatus::CONTRACT, EmployeeStatus::fromValue('PKWT'));
        $this->assertSame(EmployeeStatus::CONTRACT, EmployeeStatus::fromValue('Probation'));
        $this->assertSame(EmployeeStatus::OUTSOURCE, EmployeeStatus::fromValue('outsource'));
        $this->assertNull(EmployeeStatus::fromValue('Kemitraan'));
        $this->assertNull(EmployeeStatus::fromValue('Resigned'));

        $this->assertSame('Contract Finished', EmployeeStatus::canonical('contract finished'));
        $this->assertSame('Kemitraan', EmployeeStatus::canonical(' Kemitraan '));
        $this->assertTrue(EmployeeStatus::isTerminal('terminated'));
    }

    public function test_import_preserves_unknown_status_for_manual_review(): void
    {
        $service = $this->app->make(EmployeeService::class);
        $rows = [[
            'employeeId' => 'EMP-UNKNOWN',
            'fullName' => 'Karyawan Kemitraan',
            'statusEmployee' => 'Kemitraan',
        ]];

        $preview = $service->previewImport($rows);
        $this->assertSame('Kemitraan', $preview['rows'][0]['statusEmployee']);
        $this->assertStringContainsString('dipertahankan apa adanya', $preview['rows'][0]['issues'][0]);

        $result = $service->importEmployees($rows, 'HR Admin');

        $this->assertSame(1, $result['imported']);
        $this->assertSame(
            'Kemitraan',
            Employee::query()->where('employee_id', 'EMP-UNKNOWN')->value('status_employee')
        );
    }

    public function test_database_status_filter_matches_legacy_aliases(): void
    {
        $this->employee('EMP-PKWT', 'PKWT');
        $this->employee('EMP-CONTRACT', 'Contract');
        $this->employee('EMP-PKWTT', 'PKWTT');
        $this->employee('EMP-UNKNOWN', 'Kemitraan');
        $employees = $this->app->make(EmployeeRepositoryInterface::class);

        $this->assertEqualsCanonicalizing(
            ['EMP-PKWT', 'EMP-CONTRACT'],
            $employees->getAll(['status' => 'Contract'])->pluck('employeeId')->all()
        );
        $this->assertSame(
            ['EMP-PKWTT'],
            $employees->getAll(['status' => 'Permanent'])->pluck('employeeId')->all()
        );
        $this->assertSame(
            ['EMP-UNKNOWN'],
            $employees->getAll(['status' => 'Kemitraan'])->pluck('employeeId')->all()
        );
    }

    public function test_existing_unknown_status_can_be_preserved_or_manually_changed(): void
    {
        $this->employee('EMP-UNKNOWN', 'Kemitraan');
        Session::put('hr_user', $this->migratedTestUser([
            'email' => 'admin.status@mito.id',
            'fullName' => 'Admin User',
            'role' => 'Admin',
            'permissions' => config('hris.auth.role_permissions.Admin') ?? [],
            'auth_domain' => 'users',
            'entities' => [],
            'branch' => '',
        ]));

        $this->putJson('/hr/employees/EMP-UNKNOWN', [
            'fullName' => 'Nama Diperbarui',
            'statusEmployee' => 'Kemitraan',
        ])->assertOk();
        $this->assertSame('Kemitraan', Employee::query()->where('employee_id', 'EMP-UNKNOWN')->value('status_employee'));

        $this->putJson('/hr/employees/EMP-UNKNOWN', [
            'fullName' => 'Nama Diperbarui',
            'statusEmployee' => 'Contract',
        ])->assertOk();
        $this->assertSame('Contract', Employee::query()->where('employee_id', 'EMP-UNKNOWN')->value('status_employee'));
    }

    public function test_legacy_contract_statuses_are_in_probation_and_contract_tracking_menus(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-12 12:00:00', 'Asia/Jakarta'));
        try {
            $this->employee('EMP-PKWT', 'PKWT');
            $this->employee('EMP-PROB', 'Probation');
            $this->employee('EMP-CONTRACT', 'Contract');
            Employee::query()->whereIn('employee_id', ['EMP-PKWT', 'EMP-PROB', 'EMP-CONTRACT'])
                ->update(['end_date_contract' => '2026-10-22']);

            Session::put('hr_user', $this->migratedTestUser([
                'email' => 'admin.status@mito.id',
                'fullName' => 'Admin User',
                'role' => 'Admin',
                'permissions' => config('hris.auth.role_permissions.Admin') ?? [],
                'auth_domain' => 'users',
                'entities' => [],
                'branch' => '',
            ]));

            $probation = Mockery::mock(\App\Services\ProbationService::class);
            $probation->shouldReceive('latestEvalByEmployee')->once()->andReturn(collect());
            $probation->shouldReceive('canEvaluate')->times(3)->andReturn(true);
            $this->app->instance(\App\Services\ProbationService::class, $probation);

            $this->get('/hr/probation')
                ->assertOk()
                ->assertSee('Karyawan EMP-PKWT')
                ->assertSee('Karyawan EMP-PROB')
                ->assertSee('Karyawan EMP-CONTRACT');

            $this->get('/hr/contracts')
                ->assertOk()
                ->assertSee('Karyawan EMP-PKWT')
                ->assertSee('Karyawan EMP-PROB')
                ->assertSee('Karyawan EMP-CONTRACT');
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_employee_dashboard_counts_other_non_terminal_statuses(): void
    {
        $this->employee('EMP-PERM', 'Permanent');
        $this->employee('EMP-CONTRACT', 'Contract');
        $this->employee('EMP-OUTSOURCE', 'Outsource');
        $this->employee('EMP-KEMITRAAN', 'Kemitraan');
        $this->employee('EMP-BLANK', '');
        $this->employee('EMP-RESIGNED', 'Resigned');

        Session::put('hr_user', $this->migratedTestUser([
            'email' => 'admin.status@mito.id',
            'fullName' => 'Admin User',
            'role' => 'Admin',
            'permissions' => config('hris.auth.role_permissions.Admin') ?? [],
            'auth_domain' => 'users',
            'entities' => [],
            'branch' => '',
        ]));

        $this->get('/hr/employees')
            ->assertOk()
            ->assertSee('id="empStatOtherActive"', false)
            ->assertSee('>2</div>', false)
            ->assertSee('Status Lainnya')
            ->assertSee('Contoh:')
            ->assertSee('Kemitraan')
            ->assertSee('(kosong)');
    }

    public function test_employee_status_filter_shows_resigned_and_terminated_counts(): void
    {
        $this->employee('EMP-RESIGNED-1', 'Resigned');
        $this->employee('EMP-RESIGNED-2', ' resigned ');
        $this->employee('EMP-TERMINATED-1', 'Terminated');
        $this->employee('EMP-TERMINATED-2', 'TERMINATED');
        $this->employee('EMP-RETIRED', 'Retired');
        $this->employee('EMP-DECEASED', 'Deceased');
        $this->employee('EMP-INACTIVE', 'Inactive');
        $this->employee('EMP-CONTRACT-FINISHED', 'Contract Finished');

        Session::put('hr_user', $this->migratedTestUser([
            'email' => 'admin.status@mito.id',
            'fullName' => 'Admin User',
            'role' => 'Admin',
            'permissions' => config('hris.auth.role_permissions.Admin') ?? [],
            'auth_domain' => 'users',
            'entities' => [],
            'branch' => '',
        ]));

        $this->get('/hr/employees')
            ->assertOk()
            ->assertSee('Resigned (2)')
            ->assertSee('Terminated (2)')
            ->assertSee('Retired (1)')
            ->assertSee('Deceased (1)')
            ->assertSee('Inactive (1)')
            ->assertSee('Contract Finished (1)');
    }

    public function test_employee_terminal_status_filter_returns_matching_status(): void
    {
        $this->employee('EMP-DECEASED', 'Deceased');
        $this->employee('EMP-RESIGNED', 'Resigned');
        $this->employee('EMP-FINISHED', 'Contract Finished');

        Session::put('hr_user', $this->migratedTestUser([
            'email' => 'admin.status@mito.id',
            'fullName' => 'Admin User',
            'role' => 'Admin',
            'permissions' => config('hris.auth.role_permissions.Admin') ?? [],
            'auth_domain' => 'users',
            'entities' => [],
            'branch' => '',
        ]));

        $response = $this->get('/hr/employees?status=Deceased')->assertOk();
        preg_match('/<tbody id="empTableBody">(.*?)<\/tbody>/s', $response->getContent(), $matches);
        $employeeRows = $matches[1] ?? '';

        $this->assertStringContainsString('Karyawan EMP-DECEASED', $employeeRows);
        $this->assertStringNotContainsString('Karyawan EMP-RESIGNED', $employeeRows);
        $this->assertStringNotContainsString('Karyawan EMP-FINISHED', $employeeRows);

        $response = $this->get('/hr/employees?status=Contract%20Finished')->assertOk();
        preg_match('/<tbody id="empTableBody">(.*?)<\/tbody>/s', $response->getContent(), $matches);
        $employeeRows = $matches[1] ?? '';

        $this->assertStringContainsString('Karyawan EMP-FINISHED', $employeeRows);
        $this->assertStringNotContainsString('Karyawan EMP-DECEASED', $employeeRows);
    }

    public function test_employee_other_status_filter_returns_unknown_active_statuses_only(): void
    {
        $this->employee('EMP-PERM', 'Permanent');
        $this->employee('EMP-KEMITRAAN', 'Kemitraan');
        $this->employee('EMP-BLANK', '');
        $this->employee('EMP-RESIGNED', 'Resigned');
        $this->employee('EMP-FINISHED', 'Contract Finished');

        Session::put('hr_user', $this->migratedTestUser([
            'email' => 'admin.status@mito.id',
            'fullName' => 'Admin User',
            'role' => 'Admin',
            'permissions' => config('hris.auth.role_permissions.Admin') ?? [],
            'auth_domain' => 'users',
            'entities' => [],
            'branch' => '',
        ]));

        $response = $this->get('/hr/employees?status=other_active')->assertOk();
        $this->assertMatchesRegularExpression(
            '/<tbody id="empTableBody">(.*?)<\/tbody>/s',
            $response->getContent()
        );
        preg_match('/<tbody id="empTableBody">(.*?)<\/tbody>/s', $response->getContent(), $matches);
        $employeeRows = $matches[1] ?? '';

        $this->assertStringContainsString('Karyawan EMP-KEMITRAAN', $employeeRows);
        $this->assertStringContainsString('Karyawan EMP-BLANK', $employeeRows);
        $this->assertStringNotContainsString('Karyawan EMP-PERM', $employeeRows);
        $this->assertStringNotContainsString('Karyawan EMP-RESIGNED', $employeeRows);
        $this->assertStringNotContainsString('Karyawan EMP-FINISHED', $employeeRows);
        $response->assertSee('value="other_active" selected', false);
    }

    public function test_command_previews_without_writing(): void
    {
        $this->employee('EMP-1', 'PKWTT');

        $this->artisan('mito:normalize-employee-status')->assertSuccessful();

        $this->assertSame('PKWTT', Employee::query()->where('employee_id', 'EMP-1')->value('status_employee'));
    }

    public function test_command_execute_normalizes_and_leaves_unknown_and_terminal(): void
    {
        $this->employee('EMP-1', 'PKWTT');
        $this->employee('EMP-2', 'PKWT');
        $this->employee('EMP-3', 'Probation');
        $this->employee('EMP-4', 'Kemitraan');
        $this->employee('EMP-5', 'Resigned');
        $this->employee('EMP-6', 'Outsource');

        $this->artisan('mito:normalize-employee-status', ['--execute' => true])->assertSuccessful();

        $statuses = Employee::query()->pluck('status_employee', 'employee_id')->all();
        $this->assertSame([
            'EMP-1' => 'Permanent',
            'EMP-2' => 'Contract',
            'EMP-3' => 'Contract',
            'EMP-4' => 'Kemitraan',
            'EMP-5' => 'Resigned',
            'EMP-6' => 'Outsource',
        ], $statuses);
    }

    public function test_off_contract_rejects_non_contract_employee(): void
    {
        $this->employee('EMP-PERM', 'Permanent');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Off Contract hanya dapat diproses untuk karyawan berstatus Contract.');

        $this->app->make(EmployeeService::class)
            ->processOffContract('EMP-PERM', ['last_working_date' => '2026-06-30'], 'HR Admin');
    }
}
