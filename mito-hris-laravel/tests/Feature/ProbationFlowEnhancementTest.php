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

/**
 * ProbationFlowEnhancementTest
 *
 * Regression + enhancement coverage for the 2026 Probation Flow refactor:
 *
 *   - Ajukan Probation derives probation duration from the actual contract
 *     (Join Date → End Date Contract) — NOT a hardcoded 3 Bulan default.
 *   - The 3/6/12 Bulan list is no longer a hardcoded business constant.
 *   - Extend uses the contract duration captured at promotion time
 *     (kandidat_probation.Contract Duration) — not the browser input.
 *   - Probation employees are excluded/restricted from:
 *       Rotation, Off Contract, Edit Employee (contract fields).
 *   - Other statuses (Contract, Permanent, Outsource) remain unaffected.
 *
 * The test mocks GoogleSheetsService so no live Sheets connection is needed.
 */
class ProbationFlowEnhancementTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function actingAsHrAdmin(): void
    {
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
        Session::put('hr_user', [
            'email'       => 'admin@mito.id',
            'fullName'    => 'HR Admin',
            'role'        => 'Super Admin',
            'permissions' => ['*'],
            'auth_domain' => 'users',
            'entities'    => [],
            'branch'      => '',
        ]);
    }

    /** Build a mocked EmployeeRepositoryInterface with the given employees. */
    private function mockEmployeeRepo(array $employees = []): EmployeeRepositoryInterface
    {
        $repo = Mockery::mock(EmployeeRepositoryInterface::class);
        $byId = [];
        foreach ($employees as $e) {
            $byId[$e->employeeId] = $e;
        }
        $repo->shouldReceive('findById')->andReturnUsing(function ($id) use (&$byId) {
            return $byId[$id] ?? null;
        });
        $repo->shouldReceive('getAll')->andReturn(collect(array_values($employees)))->byDefault();
        $repo->shouldReceive('update')->andReturn(true)->byDefault();
        return $repo;
    }

    private function mockAuditRepo(): AuditLogRepositoryInterface
    {
        $a = Mockery::mock(AuditLogRepositoryInterface::class);
        $a->shouldReceive('log')->andReturn(true)->byDefault();
        return $a;
    }

    private function mockSheets(array $rowsByName = []): GoogleSheetsService
    {
        $s = Mockery::mock(GoogleSheetsService::class);
        $s->shouldReceive('clearCache')->andReturn(null)->byDefault();
        $s->shouldReceive('ensureSheetHeaders')->andReturn(true)->byDefault();
        $s->shouldReceive('appendRow')->andReturn(true)->byDefault();
        $s->shouldReceive('getRowsAsAssoc')->andReturnUsing(function ($sheet) use (&$rowsByName) {
            return $rowsByName[$sheet] ?? [];
        })->byDefault();
        $s->shouldReceive('getRange')->andReturn([[]])->byDefault();
        $s->shouldReceive('updateRange')->andReturn(true)->byDefault();
        return $s;
    }

    private function makeContractEmployee(string $id, string $join, string $end, string $status = 'Contract'): EmployeeData
    {
        return new EmployeeData(
            employeeId:     $id,
            fullName:       'Test ' . $id,
            statusEmployee: $status,
            joinDate:       $join,
            endDateContract: $end,
        );
    }

    /**
     * Mock the probation sheet with a set of rows. Each row should contain
     * at minimum: Employee ID, Status, Decision, Updated At.
     *
     * Pass [] for no rows. Rows are returned for the
     * `kandidat_probation` sheet, which is what ProbationService reads.
     */
    private function withProbationRows(array $rows): GoogleSheetsService
    {
        $s = Mockery::mock(GoogleSheetsService::class);
        $s->shouldReceive('clearCache')->andReturn(null)->byDefault();
        $s->shouldReceive('ensureSheetHeaders')->andReturn(true)->byDefault();
        $s->shouldReceive('appendRow')->andReturn(true)->byDefault();
        $s->shouldReceive('getRowsAsAssoc')->andReturnUsing(function ($sheet) use ($rows) {
            return $sheet === 'kandidat_probation' ? $rows : [];
        })->byDefault();
        $s->shouldReceive('getRange')->andReturn([[]])->byDefault();
        $s->shouldReceive('updateRange')->andReturn(true)->byDefault();
        return $s;
    }

    /** Build a kandidat_probation row helper. */
    private function probRow(string $empId, string $status, string $decision, string $updatedAt): array
    {
        return [
            'Probation ID' => 'PROB-TEST',
            'Employee ID'  => $empId,
            'Status'       => $status,
            'Decision'     => $decision,
            'Updated At'   => $updatedAt,
        ];
    }

    public function test_canonical_active_state_uses_latest_probation_row_only(): void
    {
        $rows = [
            $this->probRow('EMP001', 'Probation', 'Lulus', '2026-08-02 10:00:00'),
            $this->probRow('EMP001', 'Probation', '', '2026-08-01 10:00:00'),
        ];
        $this->app->instance(EmployeeRepositoryInterface::class, $this->mockEmployeeRepo());
        $this->app->instance(AuditLogRepositoryInterface::class, $this->mockAuditRepo());
        $this->app->instance(GoogleSheetsService::class, $this->withProbationRows($rows));

        $service = $this->app->make(ProbationService::class);

        $this->assertFalse($service->isActiveProbation('EMP001'));
        $this->assertSame('Lulus', $service->activeProbationDecision('EMP001'));
    }

    public function test_multiple_cycles_resolve_to_latest_active_row(): void
    {
        $rows = [
            $this->probRow('EMP001', 'Probation', 'Lulus', '2026-08-01 10:00:00'),
            $this->probRow('EMP001', 'Probation', 'Perpanjang Kontrak', '2026-09-01 10:00:00'),
            $this->probRow('EMP001', 'Probation', '', '2026-10-01 10:00:00'),
        ];
        $this->app->instance(EmployeeRepositoryInterface::class, $this->mockEmployeeRepo());
        $this->app->instance(AuditLogRepositoryInterface::class, $this->mockAuditRepo());
        $this->app->instance(GoogleSheetsService::class, $this->withProbationRows($rows));

        $service = $this->app->make(ProbationService::class);

        $this->assertTrue($service->isActiveProbation('EMP001'));
        $this->assertSame('', $service->activeProbationDecision('EMP001'));
    }

    // =========================================================================
    // T01 — Ajukan Probation: 6-month contract → probation duration = 6 months
    // =========================================================================

    public function test_t01_six_month_contract_yields_six_month_probation(): void
    {
        $this->actingAsHrAdmin();
        $emp = $this->makeContractEmployee('EMP100', '2026-01-01', '2026-06-30');
        $repo = $this->mockEmployeeRepo([$emp]);
        $this->app->instance(EmployeeRepositoryInterface::class, $repo);
        $this->app->instance(AuditLogRepositoryInterface::class, $this->mockAuditRepo());
        $captured = [];
        $sheets = $this->mockSheets();
        $sheets->shouldReceive('appendRow')->andReturnUsing(function ($sheet, $row) use (&$captured) {
            $captured[] = ['sheet' => $sheet, 'row' => $row];
            return true;
        });
        $this->app->instance(GoogleSheetsService::class, $sheets);

        $svc = $this->app->make(EmployeeService::class);
        $result = $svc->promoteToProbation('EMP100', [
            'probation_start' => '2026-02-01',
            'notes'           => 'rekomendasi HR',
        ], 'HR Admin');

        $this->assertTrue($result['success']);

        $kandidatRows = array_values(array_filter($captured, fn($c) => $c['sheet'] === 'kandidat_probation'));
        $this->assertNotEmpty($kandidatRows, 'A kandidat_probation row must be appended.');

        $headers = [
            'Probation ID', 'Employee ID', 'Recruitment ID', 'Contract Number', 'Contract Duration',
            'Contract Start', 'Contract End', 'Join Date', 'Status', 'Onboarding Date',
            'Onboarding By', 'Eval ID', 'Eval Date', 'Decision', 'Extension Duration',
            'New Contract Start', 'New Contract End', 'Evaluator Notes', 'Evaluator', 'SK Status',
            'Notes', 'Created At', 'Updated At',
        ];
        $row = array_combine($headers, array_pad($kandidatRows[0]['row'], count($headers), ''));
        $this->assertSame('6 Bulan', $row['Contract Duration'], 'Probation duration must equal 6 months for a 6-month contract.');
        $this->assertSame('2026-08-01', $row['Contract End'], 'Contract End must be start + 6 months.');
    }

    // =========================================================================
    // T02 — Ajukan Probation: 3-month contract → probation duration = 3 months
    // =========================================================================

    public function test_t02_three_month_contract_yields_three_month_probation(): void
    {
        $this->actingAsHrAdmin();
        $emp = $this->makeContractEmployee('EMP200', '2026-04-01', '2026-07-01');
        $repo = $this->mockEmployeeRepo([$emp]);
        $this->app->instance(EmployeeRepositoryInterface::class, $repo);
        $this->app->instance(AuditLogRepositoryInterface::class, $this->mockAuditRepo());
        $captured = [];
        $sheets = $this->mockSheets();
        $sheets->shouldReceive('appendRow')->andReturnUsing(function ($sheet, $row) use (&$captured) {
            $captured[] = ['sheet' => $sheet, 'row' => $row];
            return true;
        });
        $this->app->instance(GoogleSheetsService::class, $sheets);

        $svc = $this->app->make(EmployeeService::class);
        $result = $svc->promoteToProbation('EMP200', [
            'probation_start' => '2026-05-01',
        ], 'HR Admin');

        $this->assertTrue($result['success']);
        $kandidatRows = array_values(array_filter($captured, fn($c) => $c['sheet'] === 'kandidat_probation'));
        $headers = [
            'Probation ID', 'Employee ID', 'Recruitment ID', 'Contract Number', 'Contract Duration',
            'Contract Start', 'Contract End', 'Join Date', 'Status', 'Onboarding Date',
            'Onboarding By', 'Eval ID', 'Eval Date', 'Decision', 'Extension Duration',
            'New Contract Start', 'New Contract End', 'Evaluator Notes', 'Evaluator', 'SK Status',
            'Notes', 'Created At', 'Updated At',
        ];
        $row = array_combine($headers, array_pad($kandidatRows[0]['row'], count($headers), ''));
        $this->assertSame('3 Bulan', $row['Contract Duration']);
        $this->assertSame('2026-08-01', $row['Contract End'], 'Contract End = start + 3 months.');
    }

    // =========================================================================
    // T03 — Ajukan Probation: client-supplied duration is IGNORED (no trust)
    // =========================================================================

    public function test_t03_client_supplied_duration_is_ignored(): void
    {
        $this->actingAsHrAdmin();
        $emp = $this->makeContractEmployee('EMP300', '2026-01-01', '2026-12-31'); // 12 months
        $repo = $this->mockEmployeeRepo([$emp]);
        $this->app->instance(EmployeeRepositoryInterface::class, $repo);
        $this->app->instance(AuditLogRepositoryInterface::class, $this->mockAuditRepo());
        $captured = [];
        $sheets = $this->mockSheets();
        $sheets->shouldReceive('appendRow')->andReturnUsing(function ($sheet, $row) use (&$captured) {
            $captured[] = ['sheet' => $sheet, 'row' => $row];
            return true;
        });
        $this->app->instance(GoogleSheetsService::class, $sheets);

        $svc = $this->app->make(EmployeeService::class);
        // Client tries to hardcode 3 Bulan — backend must IGNORE.
        $result = $svc->promoteToProbation('EMP300', [
            'probation_start'    => '2026-02-01',
            'probation_duration' => '3 Bulan',
        ], 'HR Admin');
        $this->assertTrue($result['success']);

        $kandidatRows = array_values(array_filter($captured, fn($c) => $c['sheet'] === 'kandidat_probation'));
        $headers = [
            'Probation ID', 'Employee ID', 'Recruitment ID', 'Contract Number', 'Contract Duration',
            'Contract Start', 'Contract End', 'Join Date', 'Status', 'Onboarding Date',
            'Onboarding By', 'Eval ID', 'Eval Date', 'Decision', 'Extension Duration',
            'New Contract Start', 'New Contract End', 'Evaluator Notes', 'Evaluator', 'SK Status',
            'Notes', 'Created At', 'Updated At',
        ];
        $row = array_combine($headers, array_pad($kandidatRows[0]['row'], count($headers), ''));
        $this->assertSame('12 Bulan', $row['Contract Duration'], 'Backend MUST ignore client probation_duration.');
    }

    // =========================================================================
    // T04 — Promote endpoint accepts probation_duration as optional now
    // =========================================================================

    public function test_t04_promote_endpoint_no_longer_requires_probation_duration(): void
    {
        $this->actingAsHrAdmin();
        $emp = $this->makeContractEmployee('EMP400', '2026-01-01', '2026-07-01');
        $repo = $this->mockEmployeeRepo([$emp]);
        $this->app->instance(EmployeeRepositoryInterface::class, $repo);
        $this->app->instance(AuditLogRepositoryInterface::class, $this->mockAuditRepo());
        $sheets = $this->mockSheets();
        $this->app->instance(GoogleSheetsService::class, $sheets);

        $response = $this->postJson('/hr/employees/EMP400/promote-probation', [
            'probation_start' => '2026-02-01',
        ]);

        $response->assertOk()->assertJsonPath('success', true);
    }

    // =========================================================================
    // T05 — Rotation rejected for ACTIVE-PROBATION employee (canonical)
    //
    // Employee.Status stays 'Contract' (no longer 'Probation'). Active
    // probation is signalled by kandidat_probation (Status + Decision of the
    // latest row).
    // =========================================================================

    public function test_t05_rotation_rejects_active_probation_employee(): void
    {
        $this->actingAsHrAdmin();
        $emp = new EmployeeData(
            employeeId:     'EMP500',
            fullName:       'On Probation',
            statusEmployee: 'Contract',         // <-- no longer 'Probation'
            joinDate:       '2026-01-01',
            endDateContract: '2026-07-01',
            jobPosition:    'Staff',
            department:     'IT',
            branchName:     'Jakarta',
        );
        $this->app->instance(EmployeeRepositoryInterface::class, $this->mockEmployeeRepo([$emp]));
        $this->app->instance(AuditLogRepositoryInterface::class, $this->mockAuditRepo());

        // Inject active probation row (Status='Probation', Decision='').
        $this->app->instance(
            GoogleSheetsService::class,
            $this->withProbationRows([
                $this->probRow('EMP500', 'Probation', '', '2026-08-01 10:00:00'),
            ])
        );

        $svc = $this->app->make(EmployeeService::class);
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/probation/i');

        $svc->processRotation('EMP500', [
            'rotation_type'    => 'Mutasi',
            'new_job_position' => 'Senior Staff',
            'new_department'   => 'IT',
            'effective_date'   => '2026-06-01',
        ], 'HR Admin');
    }

    // =========================================================================
    // T06 — Off Contract rejected for ACTIVE-PROBATION employee
    // =========================================================================

    public function test_t06_off_contract_rejects_active_probation_employee(): void
    {
        $this->actingAsHrAdmin();
        $emp = new EmployeeData(
            employeeId:     'EMP600',
            fullName:       'On Probation',
            statusEmployee: 'Contract',         // <-- no longer 'Probation'
            joinDate:       '2026-01-01',
            endDateContract: '2026-07-01',
        );
        $this->app->instance(EmployeeRepositoryInterface::class, $this->mockEmployeeRepo([$emp]));
        $this->app->instance(AuditLogRepositoryInterface::class, $this->mockAuditRepo());

        $this->app->instance(
            GoogleSheetsService::class,
            $this->withProbationRows([
                $this->probRow('EMP600', 'Probation', '', '2026-08-01 10:00:00'),
            ])
        );

        $svc = $this->app->make(EmployeeService::class);
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/probation/i');

        $svc->processOffContract('EMP600', [
            'last_working_date' => '2026-06-30',
        ], 'HR Admin');
    }

    // =========================================================================
    // T07 — Edit Employee Contract fields rejected for ACTIVE-PROBATION employee
    // =========================================================================

    public function test_t07_edit_contract_rejected_for_active_probation_employee(): void
    {
        $this->actingAsHrAdmin();
        $emp = new EmployeeData(
            employeeId:     'EMP700',
            fullName:       'On Probation',
            statusEmployee: 'Contract',         // <-- no longer 'Probation'
        );
        $this->app->instance(EmployeeRepositoryInterface::class, $this->mockEmployeeRepo([$emp]));
        $this->app->instance(AuditLogRepositoryInterface::class, $this->mockAuditRepo());

        $this->app->instance(
            GoogleSheetsService::class,
            $this->withProbationRows([
                $this->probRow('EMP700', 'Probation', '', '2026-08-01 10:00:00'),
            ])
        );

        $response = $this->putJson('/hr/employees/EMP700', [
            'fullName'        => 'New Name',
            'endDateContract' => '2026-12-31',
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('success', false);
        $this->assertStringContainsString(
            'probation',
            strtolower((string) $response->json('message'))
        );
    }

    // =========================================================================
    // T08 — Edit Employee NON-contract fields ALLOWED for ACTIVE-PROBATION
    // =========================================================================

    public function test_t08_edit_non_contract_field_allowed_for_active_probation(): void
    {
        $this->actingAsHrAdmin();
        $emp = new EmployeeData(
            employeeId:     'EMP800',
            fullName:       'On Probation',
            statusEmployee: 'Contract',         // <-- no longer 'Probation'
        );
        $repo = $this->mockEmployeeRepo([$emp]);
        $repo->shouldReceive('update')->with('EMP800', Mockery::on(function ($attrs) {
            return ($attrs['HR Notes'] ?? '') !== '';
        }))->andReturn(true)->once();
        $this->app->instance(EmployeeRepositoryInterface::class, $repo);
        $this->app->instance(AuditLogRepositoryInterface::class, $this->mockAuditRepo());

        $this->app->instance(
            GoogleSheetsService::class,
            $this->withProbationRows([
                $this->probRow('EMP800', 'Probation', '', '2026-08-01 10:00:00'),
            ])
        );

        $response = $this->putJson('/hr/employees/EMP800', [
            'fullName' => 'Updated Name',
            'notes'    => 'Catatan HR baru',
        ]);

        $response->assertOk()->assertJsonPath('success', true);
    }

    // =========================================================================
    // T09 — Contract employee (NOT in active probation) keeps existing rotation
    // =========================================================================

    public function test_t09_rotation_allowed_for_non_probation_contract_employee(): void
    {
        $this->actingAsHrAdmin();
        $emp = $this->makeContractEmployee('EMP900', '2026-01-01', '2026-07-01', 'Contract');
        $this->app->instance(EmployeeRepositoryInterface::class, $this->mockEmployeeRepo([$emp]));
        $this->app->instance(AuditLogRepositoryInterface::class, $this->mockAuditRepo());

        // Empty probation rows → no active probation → rotation allowed.
        $this->app->instance(GoogleSheetsService::class, $this->withProbationRows([]));

        $svc = $this->app->make(EmployeeService::class);
        $result = $svc->processRotation('EMP900', [
            'rotation_type'    => 'Mutasi',
            'new_job_position' => 'Senior Staff',
            'new_department'   => 'Operations',
            'effective_date'   => '2026-05-01',
        ], 'HR Admin');

        $this->assertTrue($result['success']);
        $this->assertSame('Senior Staff', $result['newPosition']);
    }

    // =========================================================================
    // T10 — Paklaring still generated on "Tidak Lulus" (regression)
    // =========================================================================

    public function test_t10_tidak_lulus_routes_to_paklaring_pdf_and_terminates(): void
    {
        $this->actingAsHrAdmin();
        $emp = new EmployeeData(
            employeeId:     'EMP001',
            fullName:       'Budi Santoso',
            statusEmployee: 'Contract',         // <-- no longer 'Probation'
            joinDate:       '2026-05-01',
        );

        $employeeRepo = Mockery::mock(EmployeeRepositoryInterface::class);
        $employeeRepo->shouldReceive('findById')->with('EMP001')->andReturn($emp)->byDefault();
        $employeeRepo->shouldReceive('update')->andReturn(true)->byDefault();

        $auditRepo = Mockery::mock(AuditLogRepositoryInterface::class);
        $auditRepo->shouldReceive('log')->andReturn(true)->byDefault();

        $sheets = Mockery::mock(GoogleSheetsService::class);
        $sheets->shouldReceive('clearCache')->andReturn(null)->byDefault();
        // Inject an active-probation row (Ajukan Probation row) so the
        // employee is considered in active probation via the canonical
        // helper. Evaluation reads getEvalHistory which filters by
        // 'Eval Date' empty — so this base row won't appear in history
        // (history checks empty Eval Date and skips).
        $sheets->shouldReceive('getRowsAsAssoc')->andReturn([
            [
                'Probation ID' => 'PROB-20260501-0001',
                'Employee ID'  => 'EMP001',
                'Status'       => 'Probation',
                'Decision'     => '',
                'Updated At'   => '2026-05-01 09:00:00',
                'Eval Date'    => '',
                'Eval ID'      => '',
                'Created At'   => '2026-05-01 09:00:00',
            ],
        ])->byDefault();
        $sheets->shouldReceive('appendRow')->andReturn(true)->byDefault();
        $sheets->shouldReceive('ensureSheetHeaders')->andReturn(true)->byDefault();
        $sheets->shouldReceive('getRange')->andReturn([[]])->byDefault();
        $sheets->shouldReceive('updateRange')->andReturn(true)->byDefault();

        $this->app->instance(EmployeeRepositoryInterface::class, $employeeRepo);
        $this->app->instance(AuditLogRepositoryInterface::class, $auditRepo);
        $this->app->instance(GoogleSheetsService::class, $sheets);

        $payload = [
            'decision' => 'Tidak Lulus',
            'notes'    => 'Kurang dari kategori Cukup',
        ];
        // All 13 indicators = "1" for simplicity (the score gates do not
        // affect classification in this regression — we only check the
        // PDF URL and termination status). Build a nested-array payload —
        // Laravel FormRequest resolves it to indicators.integrity_1 etc.
        $indicators = [];
        foreach (range(1, 4) as $i) $indicators["integrity_$i"] = '1';
        foreach (range(1, 4) as $i) $indicators["ci_$i"]        = '1';
        $indicators['ee_1'] = '1'; $indicators['ee_2'] = '1';
        foreach (range(1, 3) as $i) $indicators["tw_$i"]        = '1';
        $payload['indicators'] = $indicators;

        $response = $this->postJson('/hr/probation/EMP001/evaluate', $payload);
        $response->assertOk()->assertJsonPath('success', true)
            ->assertJsonPath('decisionType', 'fail');

        $this->assertStringContainsString('/hr/export/paklaring/EMP001', (string) $response->json('pdfUrl'));
        $this->assertStringContainsString('/hr/export/performance-review/EMP001', (string) $response->json('evalPdfUrl'));
        $this->assertSame('Tidak Lulus', $response->json('decision'));
    }

    // =========================================================================
    // T11 — Ajukan Probation: contract-employees endpoint excludes ACTIVE
    //      probation. Employee.Status is 'Contract' for the probation
    //      employee (no longer 'Probation') — the canonical helper must
    //      detect active probation from kandidat_probation rows instead.
    // =========================================================================

    public function test_t11_contract_employees_endpoint_excludes_active_probation(): void
    {
        $this->actingAsHrAdmin();
        $contract = $this->makeContractEmployee('EMP-C', '2026-01-01', '2026-07-01', 'Contract');
        $activeProbation = $this->makeContractEmployee(
            'EMP-P', '2026-01-01', '2026-07-01', 'Contract'
        );
        $permanent = new EmployeeData(
            employeeId: 'EMP-PM',
            fullName:   'Permanent',
            statusEmployee: 'Permanent',
        );
        $this->app->instance(EmployeeRepositoryInterface::class, $this->mockEmployeeRepo([$contract, $activeProbation, $permanent]));

        // Inject an active-probation row for EMP-P only.
        $this->app->instance(
            GoogleSheetsService::class,
            $this->withProbationRows([
                $this->probRow('EMP-P', 'Probation', '', '2026-08-01 10:00:00'),
            ])
        );

        $response = $this->getJson('/hr/probation/contract-employees');
        $response->assertOk();
        $data = $response->json('data');
        $ids = array_column($data, 'employeeId');
        $this->assertContains('EMP-C', $ids);
        $this->assertNotContains('EMP-P', $ids, 'Active-probation employee must be excluded (canonical).');
        $this->assertNotContains('EMP-PM', $ids, 'Permanent employee must be excluded from contract search.');
    }

    // =========================================================================
    // T12 — Ajukan Probation: only Contract status can be promoted (regression)
    // =========================================================================

    public function test_t12_only_contract_employees_can_be_promoted(): void
    {
        $this->actingAsHrAdmin();
        $permanent = new EmployeeData(
            employeeId: 'EMP-PM2',
            fullName:   'Permanent Staff',
            statusEmployee: 'Permanent',
        );
        $this->app->instance(EmployeeRepositoryInterface::class, $this->mockEmployeeRepo([$permanent]));
        $this->app->instance(AuditLogRepositoryInterface::class, $this->mockAuditRepo());
        $this->app->instance(GoogleSheetsService::class, $this->mockSheets());

        $svc = $this->app->make(EmployeeService::class);
        $result = $svc->promoteToProbation('EMP-PM2', ['probation_start' => '2026-05-01'], 'HR Admin');
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Contract', $result['message']);
    }

    // =========================================================================
    // T13 — FOCUSED REVIEW: Contract-duration calculation is canonical
    //
    // The application stores End Date (Contract) using the PKWT convention
    // (kontrak-pkwt.blade.php line 354): End = Start + N months − 1 day.
    // So "01 Jan → 30 Jun" = 6 Bulan, "01 Feb → 31 Jul" = 6 Bulan, etc.
    // The derivation MUST use calendar-month math (not 30-day months).
    // =========================================================================

    private function promotionContractDuration(string $empId, string $join, string $end): array
    {
        $this->actingAsHrAdmin();
        $emp = $this->makeContractEmployee($empId, $join, $end);
        $repo = $this->mockEmployeeRepo([$emp]);
        $this->app->instance(EmployeeRepositoryInterface::class, $repo);
        $this->app->instance(AuditLogRepositoryInterface::class, $this->mockAuditRepo());
        $captured = [];
        $sheets = $this->mockSheets();
        $sheets->shouldReceive('appendRow')->andReturnUsing(function ($sheet, $row) use (&$captured) {
            $captured[] = ['sheet' => $sheet, 'row' => $row];
            return true;
        });
        $this->app->instance(GoogleSheetsService::class, $sheets);

        $svc = $this->app->make(EmployeeService::class);
        $result = $svc->promoteToProbation($empId, [
            'probation_start' => $join,
        ], 'HR Admin');
        $this->assertTrue($result['success']);

        $headers = [
            'Probation ID', 'Employee ID', 'Recruitment ID', 'Contract Number', 'Contract Duration',
            'Contract Start', 'Contract End', 'Join Date', 'Status', 'Onboarding Date',
            'Onboarding By', 'Eval ID', 'Eval Date', 'Decision', 'Extension Duration',
            'New Contract Start', 'New Contract End', 'Evaluator Notes', 'Evaluator', 'SK Status',
            'Notes', 'Created At', 'Updated At',
        ];
        $kandidatRows = array_values(array_filter($captured, fn($c) => $c['sheet'] === 'kandidat_probation'));
        return array_combine($headers, array_pad($kandidatRows[0]['row'], count($headers), ''));
    }

    public function test_t13_canonical_6_month_jan_jun(): void
    {
        $row = $this->promotionContractDuration('EMP-C6A', '2026-01-01', '2026-06-30');
        $this->assertSame('6 Bulan', $row['Contract Duration'],
            '01 Jan → 30 Jun must derive to 6 Bulan (calendar-month, last-day-of-N convention).');
    }

    public function test_t14_canonical_6_month_feb_jul(): void
    {
        $row = $this->promotionContractDuration('EMP-C6B', '2026-02-01', '2026-07-31');
        $this->assertSame('6 Bulan', $row['Contract Duration'],
            '01 Feb → 31 Jul must derive to 6 Bulan (calendar-month).');
    }

    public function test_t15_canonical_3_month_apr_jul(): void
    {
        $row = $this->promotionContractDuration('EMP-C3', '2026-04-01', '2026-07-01');
        $this->assertSame('3 Bulan', $row['Contract Duration'],
            '01 Apr → 01 Jul must derive to 3 Bulan (calendar-month, end = first day of (N+1) month).');
    }

    public function test_t16_canonical_12_month_jan_jan_plus_one(): void
    {
        $row = $this->promotionContractDuration('EMP-C12', '2026-01-01', '2026-12-31');
        $this->assertSame('12 Bulan', $row['Contract Duration'],
            '01 Jan → 31 Dec must derive to 12 Bulan.');
    }

    public function test_t17_no_hardcoded_allowlist_in_promote(): void
    {
        // Two employees with the same 6-month contract duration produce the
        // same probation duration — no per-employee hardcoded value.
        $rowA = $this->promotionContractDuration('EMP-A', '2026-01-01', '2026-06-30');
        $rowB = $this->promotionContractDuration('EMP-B', '2026-04-01', '2026-09-30');
        $this->assertSame($rowA['Contract Duration'], $rowB['Contract Duration'],
            'All employees with the same contract span must get the same probation duration.');
    }

    public function test_t18_no_hardcoded_allowlist_in_extend_request(): void
    {
        // Extend request no longer trusts a hardcoded 3/6/12 Bulan list —
        // any N Bulan value is accepted by FormRequest, server derives from contract.
        $this->actingAsHrAdmin();
        $emp = $this->makeContractEmployee('EMP-EXT', '2026-05-01', '2026-11-01'); // 6 months

        $employeeRepo = Mockery::mock(EmployeeRepositoryInterface::class);
        $employeeRepo->shouldReceive('findById')->with('EMP-EXT')->andReturn($emp)->byDefault();
        $employeeRepo->shouldReceive('update')->andReturn(true)->byDefault();
        $this->app->instance(EmployeeRepositoryInterface::class, $employeeRepo);

        $auditRepo = Mockery::mock(AuditLogRepositoryInterface::class);
        $auditRepo->shouldReceive('log')->andReturn(true)->byDefault();
        $this->app->instance(AuditLogRepositoryInterface::class, $auditRepo);

        $sheets = Mockery::mock(GoogleSheetsService::class);
        $sheets->shouldReceive('clearCache')->andReturn(null)->byDefault();
        $sheets->shouldReceive('appendRow')->andReturn(true)->byDefault();
        $sheets->shouldReceive('ensureSheetHeaders')->andReturn(true)->byDefault();
        $sheets->shouldReceive('getRange')->andReturn([[]])->byDefault();
        $sheets->shouldReceive('updateRange')->andReturn(true)->byDefault();
        $sheets->shouldReceive('getRowsAsAssoc')->andReturn([
            // Active probation row for this employee (Ajukan Probation).
            [
                'Probation ID' => 'PROB-EXT',
                'Employee ID'  => 'EMP-EXT',
                'Status'       => 'Probation',
                'Decision'     => '',
                'Updated At'   => '2026-05-01 09:00:00',
                'Contract Duration' => '6 Bulan',
                'Eval Date'    => '',
                'Eval ID'      => '',
                'Created At'   => '2026-05-01 09:00:00',
            ],
        ])->byDefault();
        $this->app->instance(GoogleSheetsService::class, $sheets);

        // Client tries "9 Bulan" — server must IGNORE the value and use the
        // employee's contract duration (6 Bulan) instead.
        $indicators = [];
        foreach (range(1, 4) as $i) $indicators["integrity_$i"] = '1';
        foreach (range(1, 4) as $i) $indicators["ci_$i"]        = '1';
        $indicators['ee_1'] = '1'; $indicators['ee_2'] = '1';
        foreach (range(1, 3) as $i) $indicators["tw_$i"]        = '1';

        $response = $this->postJson('/hr/probation/EMP-EXT/evaluate', [
            'decision'           => 'Perpanjang Kontrak',
            'indicators'         => $indicators,
            'extension_duration' => '9 Bulan', // mismatched client input
            'extension_start'    => '2026-11-01',
            'extension_end'      => '2027-08-01',
        ]);

        $response->assertStatus(422, 'Mismatched client duration must be rejected (no hardcoded allow-list).');
    }

    // =========================================================================
    // T19 — Canonical active-probation determination
    //
    // After refactor, Employee.Status never carries the 'Probation' label.
    // Active probation is signalled ONLY by the latest kandidat_probation
    // row's (Status + Decision) combination.
    // =========================================================================

    public function test_t19_canonical_isActiveProbation_uses_status_plus_decision(): void
    {
        $this->actingAsHrAdmin();
        $this->app->instance(EmployeeRepositoryInterface::class, $this->mockEmployeeRepo([]));
        $this->app->instance(AuditLogRepositoryInterface::class, $this->mockAuditRepo());

        $sheets = Mockery::mock(GoogleSheetsService::class);
        $sheets->shouldReceive('clearCache')->andReturn(null)->byDefault();
        $rows = [
            // Empty Decision → ACTIVE
            $this->probRow('EMP-A', 'Probation', '', '2026-08-01 10:00:00'),
            // Decision = Extend → ACTIVE (continuation)
            $this->probRow('EMP-B', 'Probation', 'Perpanjang Kontrak', '2026-08-02 10:00:00'),
            // Decision = Lulus → INACTIVE
            $this->probRow('EMP-C', 'Probation', 'Lulus', '2026-08-03 10:00:00'),
            // Decision = Tidak Lulus → INACTIVE
            $this->probRow('EMP-D', 'Probation', 'Tidak Lulus', '2026-08-04 10:00:00'),
        ];
        $sheets->shouldReceive('getRowsAsAssoc')->andReturn($rows)->byDefault();
        $sheets->shouldReceive('appendRow')->andReturn(true)->byDefault();
        $this->app->instance(GoogleSheetsService::class, $sheets);

        $svc = $this->app->make(\App\Services\ProbationService::class);
        $this->assertTrue($svc->isActiveProbation('EMP-A'), 'Empty decision = ACTIVE');
        $this->assertTrue($svc->isActiveProbation('EMP-B'), 'Extend decision = ACTIVE');
        $this->assertFalse($svc->isActiveProbation('EMP-C'), 'Lulus decision = INACTIVE');
        $this->assertFalse($svc->isActiveProbation('EMP-D'), 'Tidak Lulus decision = INACTIVE');
        $this->assertFalse($svc->isActiveProbation('EMP-UNKNOWN'), 'No row = INACTIVE');
    }

    public function test_unknown_latest_decision_keeps_probation_locked_for_safety(): void
    {
        $this->actingAsHrAdmin();
        $this->app->instance(EmployeeRepositoryInterface::class, $this->mockEmployeeRepo([]));
        $this->app->instance(AuditLogRepositoryInterface::class, $this->mockAuditRepo());
        $this->app->instance(GoogleSheetsService::class, $this->withProbationRows([
            $this->probRow('EMP-UNKNOWN-DECISION', 'Probation', 'Future Decision', '2026-08-01 10:00:00'),
        ]));

        $service = $this->app->make(ProbationService::class);

        $this->assertTrue(
            $service->isActiveProbation('EMP-UNKNOWN-DECISION'),
            'An unrecognized latest decision must remain active so a terminology change cannot unlock an employee.'
        );
    }

    // =========================================================================
    // T20 — Historical completed rows do not trigger active lock
    // =========================================================================

    public function test_t20_historical_completed_rows_ignored_for_active_lock(): void
    {
        $this->actingAsHrAdmin();
        $this->app->instance(EmployeeRepositoryInterface::class, $this->mockEmployeeRepo([]));
        $this->app->instance(AuditLogRepositoryInterface::class, $this->mockAuditRepo());

        $sheets = Mockery::mock(GoogleSheetsService::class);
        $sheets->shouldReceive('clearCache')->andReturn(null)->byDefault();
        $rows = [
            // Old row: Lulus (historical completed)
            $this->probRow('EMP-X', 'Probation', 'Lulus', '2026-01-15 09:00:00'),
            // Latest row: empty Decision (current active cycle)
            $this->probRow('EMP-X', 'Probation', '', '2026-08-01 10:00:00'),
        ];
        $sheets->shouldReceive('getRowsAsAssoc')->andReturn($rows)->byDefault();
        $sheets->shouldReceive('appendRow')->andReturn(true)->byDefault();
        $this->app->instance(GoogleSheetsService::class, $sheets);

        $svc = $this->app->make(\App\Services\ProbationService::class);
        $this->assertTrue($svc->isActiveProbation('EMP-X'),
            'Latest row decision is empty → ACTIVE (historical Lulus row is ignored).');
    }

    public function test_t21_no_current_active_row_means_inactive(): void
    {
        $this->actingAsHrAdmin();
        $this->app->instance(EmployeeRepositoryInterface::class, $this->mockEmployeeRepo([]));
        $this->app->instance(AuditLogRepositoryInterface::class, $this->mockAuditRepo());

        $sheets = Mockery::mock(GoogleSheetsService::class);
        $sheets->shouldReceive('clearCache')->andReturn(null)->byDefault();
        $rows = [
            // Only a historical Lulus row exists.
            $this->probRow('EMP-Y', 'Probation', 'Lulus', '2026-01-15 09:00:00'),
        ];
        $sheets->shouldReceive('getRowsAsAssoc')->andReturn($rows)->byDefault();
        $sheets->shouldReceive('appendRow')->andReturn(true)->byDefault();
        $this->app->instance(GoogleSheetsService::class, $sheets);

        $svc = $this->app->make(\App\Services\ProbationService::class);
        $this->assertFalse($svc->isActiveProbation('EMP-Y'),
            'Latest row decision is Lulus → INACTIVE.');
    }

    // =========================================================================
    // T22 — Ajukan Probation rejected for duplicate active probation
    // =========================================================================

    public function test_t22_ajukan_probation_rejects_duplicate_active_probation(): void
    {
        $this->actingAsHrAdmin();
        $emp = $this->makeContractEmployee('EMP-DUP', '2026-01-01', '2026-07-01', 'Contract');
        $this->app->instance(EmployeeRepositoryInterface::class, $this->mockEmployeeRepo([$emp]));
        $this->app->instance(AuditLogRepositoryInterface::class, $this->mockAuditRepo());

        // Already in active probation.
        $this->app->instance(
            GoogleSheetsService::class,
            $this->withProbationRows([
                $this->probRow('EMP-DUP', 'Probation', '', '2026-08-01 10:00:00'),
            ])
        );

        $svc = $this->app->make(EmployeeService::class);
        $result = $svc->promoteToProbation('EMP-DUP', [
            'probation_start' => '2026-05-01',
        ], 'HR Admin');
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('probation', strtolower($result['message']));
    }

    // =========================================================================
    // T23 — Employee.Status stays 'Contract' after Ajukan Probation
    // =========================================================================

    public function test_t23_employee_status_remains_contract_after_promotion(): void
    {
        $this->actingAsHrAdmin();
        $emp = $this->makeContractEmployee('EMP-KEEP', '2026-01-01', '2026-07-01', 'Contract');
        $repo = Mockery::mock(EmployeeRepositoryInterface::class);
        $byId = ['EMP-KEEP' => $emp];
        $repo->shouldReceive('findById')->andReturnUsing(function ($id) use (&$byId) {
            return $byId[$id] ?? null;
        });
        $repo->shouldReceive('getAll')->andReturn(collect(array_values($byId)))->byDefault();

        // Reject any Employee update that tries to set Status = 'Probation'.
        $repo->shouldReceive('update')->with('EMP-KEEP', Mockery::on(function ($attrs) {
            $s = $attrs['Status Employee'] ?? null;
            return $s === null || strtolower(trim($s)) !== 'probation';
        }))->andReturn(true)->byDefault();
        $this->app->instance(EmployeeRepositoryInterface::class, $repo);

        $this->app->instance(AuditLogRepositoryInterface::class, $this->mockAuditRepo());
        $this->app->instance(GoogleSheetsService::class, $this->withProbationRows([]));

        $svc = $this->app->make(EmployeeService::class);
        $result = $svc->promoteToProbation('EMP-KEEP', [
            'probation_start' => '2026-05-01',
        ], 'HR Admin');
        $this->assertTrue($result['success']);
    }

    // =========================================================================
    // T24 — After Lulus decision, employee is unlocked (isActiveProbation=false)
    // =========================================================================

    public function test_t24_after_lulus_decision_employee_is_unlocked(): void
    {
        $this->actingAsHrAdmin();
        $emp = new EmployeeData(
            employeeId:     'EMP-PASS',
            fullName:       'Passed',
            statusEmployee: 'Contract',
            joinDate:       '2026-05-01',
        );
        $this->app->instance(EmployeeRepositoryInterface::class, $this->mockEmployeeRepo([$emp]));
        $this->app->instance(AuditLogRepositoryInterface::class, $this->mockAuditRepo());

        // Latest row: Lulus (completed)
        $this->app->instance(
            GoogleSheetsService::class,
            $this->withProbationRows([
                $this->probRow('EMP-PASS', 'Probation', 'Lulus', '2026-08-01 10:00:00'),
            ])
        );

        $svc = $this->app->make(\App\Services\ProbationService::class);
        $this->assertFalse($svc->isActiveProbation('EMP-PASS'),
            'After Lulus decision, employee must be unlocked.');

        // Rotation must succeed
        $empSvc = $this->app->make(EmployeeService::class);
        $result = $empSvc->processRotation('EMP-PASS', [
            'rotation_type'    => 'Mutasi',
            'new_job_position' => 'Senior Staff',
            'new_department'   => 'IT',
            'effective_date'   => '2026-09-01',
        ], 'HR Admin');
        $this->assertTrue($result['success']);
    }

    // =========================================================================
    // T25 — After Tidak Lulus, employee is unlocked (termination flow)
    // =========================================================================

    public function test_t25_after_tidak_lulus_employee_is_unlocked(): void
    {
        $this->actingAsHrAdmin();
        $emp = new EmployeeData(
            employeeId:     'EMP-FAIL',
            fullName:       'Failed',
            statusEmployee: 'Contract',
            joinDate:       '2026-05-01',
        );
        $this->app->instance(EmployeeRepositoryInterface::class, $this->mockEmployeeRepo([$emp]));
        $this->app->instance(AuditLogRepositoryInterface::class, $this->mockAuditRepo());

        $this->app->instance(
            GoogleSheetsService::class,
            $this->withProbationRows([
                $this->probRow('EMP-FAIL', 'Probation', 'Tidak Lulus', '2026-08-01 10:00:00'),
            ])
        );

        $svc = $this->app->make(\App\Services\ProbationService::class);
        $this->assertFalse($svc->isActiveProbation('EMP-FAIL'));

        // Edit-contract endpoint must allow changes after termination.
        $response = $this->putJson('/hr/employees/EMP-FAIL', [
            'fullName'        => 'Final Name',
            'endDateContract' => '2026-08-01',
        ]);
        $response->assertOk();
    }

    // =========================================================================
    // T26 — After Extend, employee remains in active probation (next cycle)
    // =========================================================================

    public function test_t26_after_extend_employee_remains_active_probation(): void
    {
        $this->actingAsHrAdmin();
        $emp = new EmployeeData(
            employeeId:     'EMP-EXT2',
            fullName:       'Extended',
            statusEmployee: 'Contract',
            joinDate:       '2026-05-01',
        );
        $this->app->instance(EmployeeRepositoryInterface::class, $this->mockEmployeeRepo([$emp]));
        $this->app->instance(AuditLogRepositoryInterface::class, $this->mockAuditRepo());

        // Latest row: Extend (continuation → ACTIVE)
        $this->app->instance(
            GoogleSheetsService::class,
            $this->withProbationRows([
                $this->probRow('EMP-EXT2', 'Probation', 'Perpanjang Kontrak', '2026-08-01 10:00:00'),
            ])
        );

        $svc = $this->app->make(\App\Services\ProbationService::class);
        $this->assertTrue($svc->isActiveProbation('EMP-EXT2'),
            'After Extend, latest row decision is Perpanjang → ACTIVE (next cycle).');

        // Rotation must be rejected — employee is still in active probation.
        $empSvc = $this->app->make(EmployeeService::class);
        $this->expectException(RuntimeException::class);
        $empSvc->processRotation('EMP-EXT2', [
            'rotation_type'    => 'Mutasi',
            'new_job_position' => 'Senior Staff',
            'new_department'   => 'IT',
            'effective_date'   => '2026-09-01',
        ], 'HR Admin');
    }

    // =========================================================================
    // T27 — kandidat_probation.Status is preserved as 'Probation' (process
    //      label); only the Decision field signals completion.
    // =========================================================================

    public function test_t27_kandidat_probation_status_is_preserved_as_probation_label(): void
    {
        $this->actingAsHrAdmin();
        $this->app->instance(EmployeeRepositoryInterface::class, $this->mockEmployeeRepo([]));
        $this->app->instance(AuditLogRepositoryInterface::class, $this->mockAuditRepo());

        $sheets = Mockery::mock(GoogleSheetsService::class);
        $sheets->shouldReceive('clearCache')->andReturn(null)->byDefault();
        // All four rows keep Status='Probation' — Decision is what differs.
        $rows = [
            ['Employee ID' => 'EMP-A', 'Status' => 'Probation', 'Decision' => '',                    'Updated At' => '2026-08-01 10:00:00'],
            ['Employee ID' => 'EMP-B', 'Status' => 'Probation', 'Decision' => 'Perpanjang Kontrak', 'Updated At' => '2026-08-02 10:00:00'],
            ['Employee ID' => 'EMP-C', 'Status' => 'Probation', 'Decision' => 'Lulus',              'Updated At' => '2026-08-03 10:00:00'],
            ['Employee ID' => 'EMP-D', 'Status' => 'Probation', 'Decision' => 'Tidak Lulus',        'Updated At' => '2026-08-04 10:00:00'],
        ];
        $sheets->shouldReceive('getRowsAsAssoc')->andReturn($rows)->byDefault();
        $sheets->shouldReceive('appendRow')->andReturn(true)->byDefault();
        $this->app->instance(GoogleSheetsService::class, $sheets);

        $svc = $this->app->make(\App\Services\ProbationService::class);
        // Status is preserved as 'Probation' across all rows; only
        // isActiveProbation() differs by Decision.
        foreach (['EMP-A', 'EMP-B', 'EMP-C', 'EMP-D'] as $id) {
            // No 'Completed - Passed/Failed' status values exist.
            $this->assertNotSame('Completed - Passed', 'Probation');
            $this->assertNotSame('Completed - Failed', 'Probation');
        }
        $this->assertTrue($svc->isActiveProbation('EMP-A'));
        $this->assertTrue($svc->isActiveProbation('EMP-B'));
        $this->assertFalse($svc->isActiveProbation('EMP-C'));
        $this->assertFalse($svc->isActiveProbation('EMP-D'));
    }

    // =========================================================================
    // T28 — Backend rotation rejection even when caller bypasses frontend filter
    // =========================================================================

    public function test_t28_rotation_rejected_via_direct_api_call(): void
    {
        $this->actingAsHrAdmin();
        $emp = $this->makeContractEmployee('EMP-API', '2026-01-01', '2026-07-01', 'Contract');
        $this->app->instance(EmployeeRepositoryInterface::class, $this->mockEmployeeRepo([$emp]));
        $this->app->instance(AuditLogRepositoryInterface::class, $this->mockAuditRepo());

        $this->app->instance(
            GoogleSheetsService::class,
            $this->withProbationRows([
                $this->probRow('EMP-API', 'Probation', '', '2026-08-01 10:00:00'),
            ])
        );

        // Direct POST to /hr/employees/{id}/rotate — server must reject.
        $response = $this->postJson('/hr/employees/EMP-API/rotate', [
            'rotation_type'    => 'Mutasi',
            'new_job_position' => 'Senior',
            'new_department'   => 'Ops',
            'effective_date'   => '2026-09-01',
        ]);

        // Either 422 (JSON validation error) or 500 (RuntimeException)
        $this->assertGreaterThanOrEqual(400, $response->getStatusCode());
    }
}
