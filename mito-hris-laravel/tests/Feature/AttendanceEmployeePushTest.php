<?php

namespace Tests\Feature;

use App\DTOs\EmployeeData;
use App\Repositories\Contracts\EmployeeRepositoryInterface;
use App\Services\AttendanceEmployeePushService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;
use Tests\TestCase;

class AttendanceEmployeePushTest extends TestCase
{
    use RefreshDatabase;

    private const URL = 'https://attendance.example.test/api/v1/integrations/hris/employees';

    private const TOKEN = 'test-attendance-employee-push-token';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'hris.data_driver' => 'local',
            'hris.integration.attendance.base_url' => 'https://attendance.example.test',
            'hris.integration.attendance.employee_push_api_token' => self::TOKEN,
        ]);
    }

    public function test_hris_create_and_edit_push_only_approved_profile_fields(): void
    {
        $statuses = ['created', 'updated'];
        $requestIndex = 0;
        $storedEmployee = null;
        $repository = $this->createMock(EmployeeRepositoryInterface::class);
        $repository->method('getAll')->willReturnCallback(function () use (&$storedEmployee) {
            return collect($storedEmployee ? [$storedEmployee] : []);
        });
        $repository->method('create')->willReturnCallback(function (EmployeeData $employee) use (&$storedEmployee) {
            $storedEmployee = $employee;

            return $employee;
        });
        $repository->method('findById')->willReturnCallback(function (string $employeeId) use (&$storedEmployee) {
            return $storedEmployee?->employeeId === $employeeId ? $storedEmployee : null;
        });
        $repository->method('update')->willReturnCallback(function (string $employeeId, array $attributes) use (&$storedEmployee) {
            $fields = [
                'Full Name' => 'fullName',
                'Resign Date' => 'resignDate',
                'Working Email' => 'workingEmail',
                'Bank Account' => 'bankAccount',
            ];
            foreach ($attributes as $key => $value) {
                if (isset($fields[$key])) {
                    $storedEmployee->{$fields[$key]} = $value;
                }
            }

            return $storedEmployee?->employeeId === $employeeId;
        });
        $this->app->instance(EmployeeRepositoryInterface::class, $repository);
        Http::fake([self::URL => function (Request $request) use (&$statuses, &$requestIndex) {
            $status = $statuses[$requestIndex++];

            return Http::response($this->successResponse($request['people'], $status));
        }]);
        $this->actingAsEmployeeManager();

        $created = $this->postJson('/hr/employees', [
            'fullName' => 'Nama HRIS',
            'statusEmployee' => 'Contract',
            'nikNpwp' => "'3170000000000001",
            'joinDate' => '2026-10-13',
            'endDateContract' => '2027-10-12',
            'jobPosition' => 'Staff',
            'department' => 'Human Resources',
            'division' => 'People',
            'branchName' => 'MITO',
            'workingEmail' => 'nama@mito.id',
            'mobilePhone' => "'081234567890",
        ])->assertCreated();

        $employeeId = $created->json('employeeId');
        $updated = $this->putJson('/hr/employees/'.$employeeId, [
            'fullName' => 'Nama HRIS Diperbarui',
            'resignDate' => '2027-09-01',
            'workingEmail' => 'baru@mito.id',
            'bankAccount' => '999999999',
        ])->assertOk();

        $requests = Http::recorded()->values();
        $this->assertCount(2, $requests);
        $this->assertSame('Bearer '.self::TOKEN, $requests[0][0]->header('Authorization')[0]);
        $this->assertSame([
            'employee_id' => $employeeId,
            'nik' => '3170000000000001',
            'full_name' => 'Nama HRIS',
            'employment_status' => 'Contract',
            'join_date' => '2026-10-13',
            'end_date' => '2027-10-12',
            'job_position' => 'Staff',
            'department' => 'Human Resources',
            'division' => 'People',
            'branch' => 'MITO',
            'email' => 'nama@mito.id',
            'phone' => '081234567890',
        ], $requests[0][0]['people'][0]);
        $this->assertSame([
            'employee_id' => $employeeId,
            'nik' => '3170000000000001',
            'full_name' => 'Nama HRIS Diperbarui',
            'employment_status' => 'Contract',
            'join_date' => '2026-10-13',
            'end_date' => '2027-09-01',
            'job_position' => 'Staff',
            'department' => 'Human Resources',
            'division' => 'People',
            'branch' => 'MITO',
            'email' => 'baru@mito.id',
            'phone' => '081234567890',
        ], $requests[1][0]['people'][0]);
        foreach ($requests as [$request]) {
            $this->assertSame(['people', 'dry_run'], array_keys($request->data()));
            $this->assertFalse($request['dry_run']);
            $this->assertSame([
                'employee_id', 'nik', 'full_name', 'employment_status', 'join_date', 'end_date',
                'job_position', 'department', 'division', 'branch', 'email', 'phone',
            ], array_keys($request['people'][0]));
        }
    }

    public function test_employee_push_command_previews_by_default_and_executes_explicitly(): void
    {
        $repository = \Mockery::mock(EmployeeRepositoryInterface::class);
        $repository->shouldReceive('getAll')->twice()->andReturn(collect([
            new EmployeeData(employeeId: '2026101301', fullName: 'Preview Employee', statusEmployee: 'Permanent'),
        ]));
        $this->app->instance(EmployeeRepositoryInterface::class, $repository);
        Http::fake([self::URL => function (Request $request) {
            return Http::response($this->successResponse(
                $request['people'],
                $request['dry_run'] ? 'would_create' : 'created',
            ));
        }]);

        $this->artisan('mito:employees-push-attendance')
            ->expectsOutputToContain('WOULD_CREATE: 2026101301')
            ->expectsOutputToContain('Preview saja')
            ->assertSuccessful();
        $this->artisan('mito:employees-push-attendance', ['--execute' => true])
            ->expectsOutputToContain('CREATED: 2026101301')
            ->assertSuccessful();

        Http::assertSent(fn (Request $request) => $request['dry_run'] === true
            && $request['people'][0]['employee_id'] === '2026101301');
        Http::assertSent(fn (Request $request) => $request['dry_run'] === false
            && $request['people'][0]['employee_id'] === '2026101301');
    }

    public function test_full_sync_splits_employee_pushes_into_batches_of_one_hundred(): void
    {
        $employees = collect(range(1, 101))->map(fn (int $number) => new EmployeeData(
            employeeId: 'EMP-'.$number,
            fullName: 'Employee '.$number,
        ));
        Http::fake([self::URL => function (Request $request) {
            return Http::response($this->successResponse($request['people'], 'would_create'));
        }]);

        $result = $this->app->make(AttendanceEmployeePushService::class)
            ->pushEmployees($employees, true);

        $this->assertSame(101, $result['meta']['processed']);
        $this->assertSame(101, $result['meta']['would_create']);
        $requests = Http::recorded()->values();
        $this->assertCount(2, $requests);
        $this->assertCount(100, $requests[0][0]['people']);
        $this->assertCount(1, $requests[1][0]['people']);
        $this->assertTrue($requests[0][0]['dry_run']);
        $this->assertTrue($requests[1][0]['dry_run']);
        $this->assertNull($requests[0][0]['people'][0]['nik']);
        $this->assertNull($requests[0][0]['people'][0]['job_position']);
    }

    public function test_push_rejects_malformed_response_and_does_not_send_when_unconfigured(): void
    {
        $repository = \Mockery::mock(EmployeeRepositoryInterface::class);
        $repository->shouldReceive('getAll')->once()->andReturn(collect([
            new EmployeeData(employeeId: '2026101301', fullName: 'Malformed Response Employee'),
        ]));
        $this->app->instance(EmployeeRepositoryInterface::class, $repository);
        Http::fake([self::URL => Http::response([
            'success' => true,
            'data' => [['employee_id' => '2026101301', 'status' => 'created']],
            'meta' => ['processed' => 0],
        ])]);

        $this->artisan('mito:employees-push-attendance', ['--execute' => true])
            ->assertFailed();

        Http::fake();
        config(['hris.integration.attendance.employee_push_api_token' => '']);
        $service = $this->app->make(AttendanceEmployeePushService::class);
        $service->pushCreated([new EmployeeData(employeeId: '2026101301', fullName: 'No Push')]);
        Http::assertNothingSent();
    }

    private function actingAsEmployeeManager(): void
    {
        Session::put('hr_user', $this->migratedTestUser([
            'email' => 'hr.employee-sync@mito.id',
            'fullName' => 'HR Employee Sync',
            'role' => 'Admin',
            'permissions' => config('hris.auth.role_permissions.Admin') ?? [],
            'auth_domain' => 'users',
            'entities' => [],
            'branch' => '',
        ]));
    }

    private function successResponse(array $people, string $status): array
    {
        $meta = array_fill_keys([
            'processed', 'created', 'updated', 'skipped', 'conflict', 'would_create', 'would_update',
        ], 0);
        $meta['processed'] = count($people);
        $meta[$status] = count($people);

        return [
            'success' => true,
            'data' => array_map(fn (array $person) => [
                'employee_id' => $person['employee_id'],
                'status' => $status,
            ], $people),
            'meta' => $meta,
        ];
    }
}
