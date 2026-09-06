<?php

namespace Tests\Feature;

use App\DTOs\EmployeeData;
use App\Repositories\Contracts\AuditLogRepositoryInterface;
use App\Repositories\Contracts\EmployeeRepositoryInterface;
use App\Services\EmployeeService;
use App\Services\Google\GoogleSheetsService;
use App\Services\ProbationService;
use Illuminate\Support\Facades\Session;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class ProbationFlowEnhancementTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function loginAsHr(): void
    {
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
        Session::put('hr_user', [
            'email' => 'admin@mito.id',
            'fullName' => 'HR Admin',
            'role' => 'Super Admin',
            'permissions' => ['*'],
            'auth_domain' => 'users',
            'entities' => [],
            'branch' => '',
        ]);
    }

    private function employee(string $id, string $status = 'Contract'): EmployeeData
    {
        return new EmployeeData(
            employeeId: $id,
            fullName: 'Employee ' . $id,
            statusEmployee: $status,
            joinDate: '2026-01-01',
            endDateContract: '2026-06-30',
            department: 'IT',
            jobPosition: 'Staff'
        );
    }

    private function employeeRepo(array $employees): EmployeeRepositoryInterface
    {
        $repo = Mockery::mock(EmployeeRepositoryInterface::class);
        $byId = collect($employees)->keyBy('employeeId')->all();
        $repo->shouldReceive('findById')->andReturnUsing(fn($id) => $byId[$id] ?? null);
        $repo->shouldReceive('getAll')->andReturn(collect($employees))->byDefault();
        $repo->shouldReceive('update')->andReturn(true)->byDefault();
        return $repo;
    }

    private function auditRepo(): AuditLogRepositoryInterface
    {
        $repo = Mockery::mock(AuditLogRepositoryInterface::class);
        $repo->shouldReceive('log')->andReturn(true)->byDefault();
        return $repo;
    }

    private function sheets(array $rows, ?array &$appended = null): GoogleSheetsService
    {
        $service = Mockery::mock(GoogleSheetsService::class);
        $service->shouldReceive('clearCache')->andReturn(null)->byDefault();
        $service->shouldReceive('getRowsAsAssoc')->andReturn($rows)->byDefault();
        $service->shouldReceive('getRange')->andReturn([[]])->byDefault();
        $service->shouldReceive('ensureSheetHeaders')->andReturn(true)->byDefault();
        $service->shouldReceive('updateRange')->andReturn(true)->byDefault();
        $service->shouldReceive('appendRow')->andReturnUsing(function ($sheet, $row) use (&$appended) {
            if ($appended !== null) {
                $appended[] = ['sheet' => $sheet, 'row' => $row];
            }
            return true;
        })->byDefault();
        return $service;
    }

    private function probationRow(string $id, string $decision = '', string $updatedAt = '2026-08-01 10:00:00'): array
    {
        return [
            'Probation ID' => 'PROB-' . $id,
            'Employee ID' => $id,
            'Status' => 'Probation',
            'Decision' => $decision,
            'Updated At' => $updatedAt,
        ];
    }

    private function bind(array $employees, array $rows, ?array &$appended = null): void
    {
        $this->app->instance(EmployeeRepositoryInterface::class, $this->employeeRepo($employees));
        $this->app->instance(AuditLogRepositoryInterface::class, $this->auditRepo());
        $this->app->instance(GoogleSheetsService::class, $this->sheets($rows, $appended));
    }

    public function test_contract_without_history_is_active_and_evaluable_without_writing_history(): void
    {
        $employee = $this->employee('EMP-CONTRACT');
        $appended = [];
        $this->bind([$employee], [], $appended);
        $service = $this->app->make(ProbationService::class);

        $this->assertTrue($service->isActiveProbation($employee->employeeId));
        $this->assertTrue($service->canEvaluate($employee->employeeId));
        $service->isActiveProbation($employee->employeeId);
        $service->canEvaluate($employee->employeeId);

        $this->assertCount(0, $appended);
    }

    public function test_contract_without_history_appears_in_probation_index_without_writing_history(): void
    {
        $this->loginAsHr();
        $appended = [];
        $this->bind([$this->employee('EMP-INDEX')], [], $appended);

        $response = $this->get('/hr/probation');

        $response->assertOk()->assertSee('EMP-INDEX', false);
        $this->assertCount(0, $appended);
    }

    public function test_permanent_without_history_is_not_active_or_evaluable(): void
    {
        $employee = $this->employee('EMP-PERM', 'Permanent');
        $this->bind([$employee], []);
        $service = $this->app->make(ProbationService::class);

        $this->assertFalse($service->isActiveProbation($employee->employeeId));
        $this->assertFalse($service->canEvaluate($employee->employeeId));
        $this->expectException(RuntimeException::class);
        $service->evaluateProbation($employee->employeeId, ['decision' => 'Lulus']);
    }

    public function test_latest_empty_decision_is_active(): void
    {
        $employee = $this->employee('EMP-EMPTY');
        $this->bind([$employee], [$this->probationRow('EMP-EMPTY')]);
        $service = $this->app->make(ProbationService::class);

        $this->assertTrue($service->isActiveProbation('EMP-EMPTY'));
        $this->assertTrue($service->canEvaluate('EMP-EMPTY'));
    }

    public function test_latest_extend_decision_is_active_and_evaluable(): void
    {
        $employee = $this->employee('EMP-EXTEND');
        $this->bind([$employee], [$this->probationRow('EMP-EXTEND', 'Perpanjang Kontrak')]);
        $service = $this->app->make(ProbationService::class);

        $this->assertTrue($service->isActiveProbation('EMP-EXTEND'));
        $this->assertTrue($service->canEvaluate('EMP-EXTEND'));
    }

    public function test_terminal_decisions_are_inactive(): void
    {
        $passed = $this->employee('EMP-PASSED', 'Permanent');
        $failed = $this->employee('EMP-FAILED', 'Terminated');
        $this->bind([$passed, $failed], [
            $this->probationRow('EMP-PASSED', 'Lulus', '2026-08-02 10:00:00'),
            $this->probationRow('EMP-FAILED', 'Tidak Lulus', '2026-08-03 10:00:00'),
        ]);
        $service = $this->app->make(ProbationService::class);

        $this->assertFalse($service->isActiveProbation('EMP-PASSED'));
        $this->assertFalse($service->isActiveProbation('EMP-FAILED'));
    }

    public function test_evaluation_without_history_appends_one_row_and_keeps_contract_for_extend(): void
    {
        $employee = $this->employee('EMP-EVAL');
        $appended = [];
        $this->bind([$employee], [], $appended);
        $service = $this->app->make(ProbationService::class);
        $updated = [];
        $repo = $this->app->make(EmployeeRepositoryInterface::class);
        $repo->shouldReceive('update')->andReturnUsing(function ($id, $data) use (&$updated) {
            $updated[] = [$id, $data];
            return true;
        });

        $result = $service->evaluateProbation('EMP-EVAL', [
            'decision' => 'Perpanjang Kontrak',
            'extension_start' => '2026-07-01',
            'indicators' => [],
        ], 'HR Admin');

        $this->assertTrue($result['success']);
        $this->assertCount(1, $appended);
        $this->assertSame('kandidat_probation', $appended[0]['sheet']);
        $this->assertSame('Probation', $appended[0]['row'][8]);
        $this->assertSame('Contract', $employee->statusEmployee);
        $this->assertTrue($service->isActiveProbation('EMP-EVAL'));
        $this->assertNotEmpty($updated);
    }

    public function test_lulus_changes_employee_to_permanent(): void
    {
        $employee = $this->employee('EMP-LULUS');
        $updates = [];
        $repo = $this->employeeRepo([$employee]);
        $repo->shouldReceive('update')->andReturnUsing(function ($id, $data) use (&$updates) {
            $updates[] = $data;
            return true;
        });
        $this->app->instance(EmployeeRepositoryInterface::class, $repo);
        $this->app->instance(AuditLogRepositoryInterface::class, $this->auditRepo());
        $appended = [];
        $this->app->instance(GoogleSheetsService::class, $this->sheets([], $appended));

        $result = $this->app->make(ProbationService::class)->evaluateProbation('EMP-LULUS', [
            'decision' => 'Lulus',
            'indicators' => [],
        ]);

        $this->assertTrue($result['success']);
        $this->assertSame('PKWTT', $updates[0]['Status Employee']);
    }

    public function test_active_probation_blocks_rotation(): void
    {
        $employee = $this->employee('EMP-LOCK');
        $this->bind([$employee], [$this->probationRow('EMP-LOCK')]);
        $service = $this->app->make(EmployeeService::class);

        $this->expectException(RuntimeException::class);
        $service->processRotation('EMP-LOCK', ['rotation_type' => 'Mutasi'], 'HR Admin');
    }

    public function test_active_probation_blocks_off_contract(): void
    {
        $employee = $this->employee('EMP-OFF');
        $this->bind([$employee], [$this->probationRow('EMP-OFF')]);
        $service = $this->app->make(EmployeeService::class);

        $this->expectException(RuntimeException::class);
        $service->processOffContract('EMP-OFF', ['last_working_date' => '2026-06-30'], 'HR Admin');
    }

    public function test_active_probation_blocks_contract_field_edit(): void
    {
        $this->loginAsHr();
        $employee = $this->employee('EMP-CONTRACT-EDIT');
        $this->bind([$employee], [$this->probationRow('EMP-CONTRACT-EDIT')]);

        $this->putJson('/hr/employees/EMP-CONTRACT-EDIT', [
            'fullName' => 'Updated Name',
            'endDateContract' => '2026-12-31',
        ])->assertStatus(422)->assertJsonPath('success', false);
    }

    public function test_non_contract_edit_remains_allowed_after_lulus(): void
    {
        $this->loginAsHr();
        $employee = $this->employee('EMP-EDIT', 'Permanent');
        $repo = $this->employeeRepo([$employee]);
        $this->app->instance(EmployeeRepositoryInterface::class, $repo);
        $this->app->instance(AuditLogRepositoryInterface::class, $this->auditRepo());
        $this->app->instance(GoogleSheetsService::class, $this->sheets([
            $this->probationRow('EMP-EDIT', 'Lulus'),
        ]));

        $this->putJson('/hr/employees/EMP-EDIT', [
            'fullName' => 'Updated Name',
        ])->assertOk()->assertJsonPath('success', true);
    }

    public function test_probation_routes_keep_current_evaluation_functions(): void
    {
        $routes = collect(app('router')->getRoutes())->map(fn($route) => $route->uri())->all();

        $this->assertContains('hr/probation', $routes);
        $this->assertContains('hr/probation/{id}/evaluate', $routes);
        $this->assertContains('hr/probation/{id}/eval-history', $routes);
        $this->assertContains('hr/probation/{id}/preview', $routes);
        $this->assertCount(4, array_values(array_filter($routes, fn($uri) => str_starts_with($uri, 'hr/probation'))));
    }
}
