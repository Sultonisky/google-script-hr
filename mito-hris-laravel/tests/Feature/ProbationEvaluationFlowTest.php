<?php

namespace Tests\Feature;

use App\DTOs\EmployeeData;
use App\Enums\ProbationDecisionType;
use App\Repositories\Contracts\AuditLogRepositoryInterface;
use App\Repositories\Contracts\EmployeeRepositoryInterface;
use App\Services\Google\GoogleSheetsService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Session;
use Mockery;
use ReflectionClass;
use Tests\TestCase;

/**
 * ProbationEvaluationFlowTest
 *
 * End-to-end coverage of the Probation Evaluation flow:
 *   Submit â†’ Backend Validation â†’ Save Evaluation â†’ Decision Classification
 *   â†’ on-demand PDF URLs (hr.export.* â€” streamed directly to the browser,
 *     same convention as the other PDF functions; NO storage persistence)
 *   â†’ Preview (HTML, same template as PDF).
 *
 * PDF rules:
 *   PASS   â†’ SK Pengangkatan + Performance Review (direct download)
 *   FAIL   â†’ Paklaring + Performance Review (direct download)
 *   EXTEND â†’ NO PDF generated at all (evaluation + duration saved only)
 */
class ProbationEvaluationFlowTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    private function loginAsHrAdmin(): void
    {
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
        Session::put('hr_user', [
            'email'       => 'admin@mito.id',
            'fullName'    => 'HR Admin',
            'role'        => 'Super Admin',
            'permissions' => ['*'],
        ]);
    }

    private function makeEmployee(string $endDateContract = '2026-11-01', string $joinDate = '2026-05-01'): EmployeeData
    {
        return new EmployeeData(
            employeeId: 'EMP001',
            fullName: 'Budi Santoso',
            branchName: '',
            department: 'IT',
            jobPosition: 'Staff IT',
            jobPositionLocation: 'Staff IT - Jakarta',
            jobLevel: 'Staff',
            joinDate: $joinDate,
            // Contract duration is derived server-side from these dates.
            endDateContract: $endDateContract,
            // Employee.Status is restricted to Permanent / Contract /
            // Outsource; the probation process lives in kandidat_probation.
            statusEmployee: 'Contract',
            personalEmail: 'budi@example.com',
        );
    }

    private function headers(): array
    {
        return config('hris.schemas.kandidat_probation');
    }

    /**
     * Bind mocks for the evaluate flow. $capturedRows receives every appended
     * kandidat_probation row as a header-mapped assoc array.
     *
     * A null $existingRows value seeds legacy history; an explicit empty array
     * exercises first evaluation for a Contract employee with no history.
     */
    private function bindEvaluateFlowMocks(
        array &$capturedRows,
        ?array $existingRows = null,
        string $endDateContract = '2026-11-01',
        string $joinDate = '2026-05-01'
    ): GoogleSheetsService {
        $employeeRepo = Mockery::mock(EmployeeRepositoryInterface::class);
        $employeeRepo->shouldReceive('findById')->with('EMP001')->andReturn($this->makeEmployee($endDateContract, $joinDate))->byDefault();
        $employeeRepo->shouldReceive('update')->andReturn(true)->byDefault();

        $auditRepo = Mockery::mock(AuditLogRepositoryInterface::class);
        $auditRepo->shouldReceive('log')->andReturn(true)->byDefault();

        $headers = $this->headers();
        $sheets  = Mockery::mock(GoogleSheetsService::class);
        $sheets->shouldReceive('getRange')
            ->with('kandidat_probation', '1:1', false)
            ->andReturn([$headers])->byDefault();
        $sheets->shouldReceive('appendRow')
            ->withArgs(function (string $sheet, array $row) use ($headers, &$capturedRows) {
                if (count($row) !== count($headers)) {
                    return false;
                }
                $capturedRows[] = array_combine($headers, $row);
                return true;
            })->andReturn(true)->byDefault();
        $sheets->shouldReceive('clearCache')->andReturn(null)->byDefault();
        $sheets->shouldReceive('updateRange')->andReturn(true)->byDefault();
        // Optional legacy history fixture for re-evaluation tests.
        if ($existingRows === null) {
            $existingRows = [
                array_combine($headers, array_pad([
                    'PROB-EMP001',
                    'EMP001',
                    '',
                    '6 Bulan',
                    '2026-05-01',
                    '2026-11-01',
                    '2026-05-01',
                    'Probation',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '2026-05-01 09:00:00',
                    '2026-05-01 09:00:00',
                ], count($headers), '')),
            ];
        }
        $sheets->shouldReceive('getRowsAsAssoc')->andReturn($existingRows)->byDefault();

        $this->app->instance(EmployeeRepositoryInterface::class, $employeeRepo);
        $this->app->instance(AuditLogRepositoryInterface::class, $auditRepo);
        $this->app->instance(GoogleSheetsService::class, $sheets);
        $this->app->instance(
            \App\Repositories\Contracts\EmployeeDocumentRepositoryInterface::class,
            new \App\Repositories\Local\ArrayEmployeeDocumentRepository()
        );

        return $sheets;
    }

    private static function ref(array $row, string $key): string
    {
        return (string) ($row[$key] ?? '');
    }

    private function validIndicatorPayload(): array
    {
        $keys = [
            'integrity_1',
            'integrity_2',
            'integrity_3',
            'integrity_4',
            'ci_1',
            'ci_2',
            'ci_3',
            'ci_4',
            'ee_1',
            'ee_2',
            'tw_1',
            'tw_2',
            'tw_3'
        ];
        return ['indicators' => collect($keys)->mapWithKeys(fn($k) => [$k => '1'])->all()];
    }

    /** Manual extend window submitted by HR (start + end required). */
    private function manualExtend(string $start, string $end): array
    {
        return [
            'extension_start' => $start,
            'extension_end'   => $end,
        ];
    }

    /** POST an evaluation with mocked sheets; returns [response, capturedRows]. */
    private function postEvaluation(
        string $decision,
        array $extra = [],
        array $existingRows = [],
        string $endDateContract = '2026-11-01',
        string $joinDate = '2026-05-01'
    ): array {
        $this->loginAsHrAdmin();

        $captured = [];
        $this->bindEvaluateFlowMocks($captured, $existingRows, $endDateContract, $joinDate);

        $payload = array_merge([
            'decision'           => $decision,
            'notes'              => 'Catatan evaluasi',
            'reviewer_name'      => 'Manager A',
            'approval_dept'      => 'Setuju',
            'approval_dept_name' => 'Dept Head',
            'approval_dept_date' => '2026-08-01',
            'approval_hrbp'      => 'Setuju',
            'approval_hrbp_name' => 'HRBP',
            'approval_hrbp_date' => '2026-08-02',
        ], $this->validIndicatorPayload(), $extra);

        return [$this->postJson('/hr/probation/EMP001/evaluate', $payload), $captured];
    }

    // =========================================================================
    // T01 â€” PASS: evaluation saved + on-demand PDF URLs (SK + Performance Review)
    // =========================================================================

    public function test_t01_pass_returns_download_urls_for_sk_and_performance_review(): void
    {
        [$response, $rows] = $this->postEvaluation('Lulus');

        $response->assertOk()->assertJsonPath('success', true)
            ->assertJsonPath('decisionType', 'pass');

        $row = end($rows);
        $this->assertCount(1, $rows, 'First evaluation must create exactly one history row.');
        $this->assertSame('Probation', self::ref($row, 'Status'));
        $this->assertSame('Lulus', self::ref($row, 'Decision'));
        $this->assertSame('13', self::ref($row, 'Overall Total'));
        $this->assertSame('Sangat Baik', self::ref($row, 'Category'));

        $data = $response->json();
        $this->assertStringContainsString('/hr/export/sk-pengangkatan/EMP001', $data['pdfUrl']);
        $this->assertStringContainsString('sk_number=', $data['pdfUrl']);
        $this->assertStringContainsString('/hr/export/performance-review/EMP001', $data['evalPdfUrl']);
    }

    public function test_evaluation_row_preserves_all_45_header_positions(): void
    {
        [$response, $rows] = $this->postEvaluation('Perpanjang Kontrak', array_merge([
            'notes'              => 'Catatan lengkap',
            'reviewer_name'      => 'Reviewer',
            'approval_dept'      => 'Setuju',
            'approval_dept_name' => 'Department Head',
            'approval_dept_date' => '2026-12-02',
            'approval_hrbp'      => 'Setuju',
            'approval_hrbp_name' => 'HRBP',
            'approval_hrbp_date' => '2026-12-03',
        ], $this->manualExtend('2026-12-01', '2027-05-31')));

        $response->assertOk();
        $this->assertCount(1, $rows);
        $this->assertSame($this->headers(), array_keys($rows[0]));
        $this->assertSame('EMP001', self::ref($rows[0], 'Employee ID'));
        $this->assertSame('Probation', self::ref($rows[0], 'Status'));
        $this->assertSame('Perpanjang Kontrak', self::ref($rows[0], 'Decision'));
        $this->assertSame('6 Bulan', self::ref($rows[0], 'Extension Duration'));
        $this->assertSame('2026-12-01', self::ref($rows[0], 'New Contract Start'));
        $this->assertSame('2027-05-31', self::ref($rows[0], 'New Contract End'));
        $this->assertSame('13', self::ref($rows[0], 'Overall Total'));
        $this->assertSame('Sangat Baik', self::ref($rows[0], 'Category'));
        $this->assertSame('1', self::ref($rows[0], 'ind_integrity_1'));
        $this->assertSame('1', self::ref($rows[0], 'ind_tw_3'));
        $this->assertSame('Reviewer', self::ref($rows[0], 'Reviewer Name'));
        $this->assertSame('Setuju', self::ref($rows[0], 'Approval Dept'));
        $this->assertSame('HRBP', self::ref($rows[0], 'Approval HRBP Name'));
    }

    // =========================================================================
    // T02 â€” FAIL: evaluation saved + on-demand PDF URLs (Paklaring + Performance Review)
    // =========================================================================

    public function test_t02_fail_returns_download_urls_for_paklaring_and_performance_review(): void
    {
        [$response, $rows] = $this->postEvaluation('Tidak Lulus');

        $response->assertOk()->assertJsonPath('success', true)
            ->assertJsonPath('decisionType', 'fail');

        $row = end($rows);
        $this->assertSame('Probation', self::ref($row, 'Status'));
        $this->assertSame('Tidak Lulus', self::ref($row, 'Decision'));
        $this->assertNotSame('', self::ref($row, 'Eval ID'));

        $data = $response->json();
        $this->assertStringContainsString('/hr/export/paklaring/EMP001', $data['pdfUrl']);
        $this->assertStringContainsString('/hr/export/performance-review/EMP001', $data['evalPdfUrl']);
    }

    // =========================================================================
    // T03 â€” EXTEND: server resolves duration from the current contract.
    // =========================================================================

    public function test_t03_extend_resolves_duration_from_contract(): void
    {
        [$response, $rows] = $this->postEvaluation(
            'Perpanjang Kontrak',
            $this->manualExtend('2026-12-01', '2027-05-31')
        );

        $response->assertOk()->assertJsonPath('success', true)
            ->assertJsonPath('decisionType', 'extend')
            ->assertJsonPath('pdfUrl', null)
            ->assertJsonPath('evalPdfUrl', null);

        $row = end($rows);
        $this->assertSame('Probation', self::ref($row, 'Status'));
        $this->assertSame('6 Bulan', self::ref($row, 'Extension Duration'));
        $this->assertSame('2026-12-01', self::ref($row, 'New Contract Start'));
        // Server derives end date = start + 6 months - 1 day = 2027-05-31
        $this->assertSame('2027-05-31', self::ref($row, 'New Contract End'));
        $this->assertNotSame('', self::ref($row, 'Eval ID'));
    }

    // =========================================================================
    // T03b — EXTEND must not overwrite Employee Join Date (original hire date).
    //       Only End Date (Contract) advances to New Contract End.
    // =========================================================================

    public function test_t03b_extend_preserves_employee_join_date(): void
    {
        $this->loginAsHrAdmin();

        $originalJoinDate = '2024-12-23';
        $employee = new EmployeeData(
            employeeId: 'EMP001',
            fullName: 'Budi Santoso',
            branchName: 'HO',
            department: 'HR',
            jobPosition: 'Staff',
            joinDate: $originalJoinDate,
            endDateContract: '2025-06-22', // 6 months from join (PKWT: start + N − 1 day)
            statusEmployee: 'Contract',
            personalEmail: 'budi@example.com',
        );

        $employeeUpdates = [];
        $employeeRepo = Mockery::mock(EmployeeRepositoryInterface::class);
        $employeeRepo->shouldReceive('findById')->with('EMP001')->andReturn($employee);
        $employeeRepo->shouldReceive('update')
            ->withArgs(function (string $id, array $updates) use (&$employeeUpdates) {
                $employeeUpdates[] = $updates;
                return $id === 'EMP001';
            })
            ->andReturn(true);

        $auditRepo = Mockery::mock(AuditLogRepositoryInterface::class);
        $auditRepo->shouldReceive('log')->andReturn(true)->byDefault();

        $headers = $this->headers();
        $sheets = Mockery::mock(GoogleSheetsService::class);
        $sheets->shouldReceive('getRange')->with('kandidat_probation', '1:1', false)->andReturn([$headers])->byDefault();
        $sheets->shouldReceive('appendRow')->andReturn(true)->byDefault();
        $sheets->shouldReceive('getRowsAsAssoc')->andReturn([])->byDefault();
        $sheets->shouldReceive('clearCache')->andReturn(null)->byDefault();
        $sheets->shouldReceive('updateRange')->andReturn(true)->byDefault();

        $this->app->instance(EmployeeRepositoryInterface::class, $employeeRepo);
        $this->app->instance(AuditLogRepositoryInterface::class, $auditRepo);
        $this->app->instance(GoogleSheetsService::class, $sheets);

        $payload = array_merge([
            'decision' => 'Perpanjang Kontrak',
        ], $this->manualExtend('2026-09-22', '2027-03-21'), $this->validIndicatorPayload());

        $response = $this->postJson('/hr/probation/EMP001/evaluate', $payload);

        $response->assertOk()->assertJsonPath('decisionType', 'extend');
        $this->assertCount(1, $employeeUpdates);
        $this->assertArrayNotHasKey(
            'Join Date',
            $employeeUpdates[0],
            'Extend must not overwrite Employee Join Date with New Contract Start / submit date.'
        );
        $this->assertSame(
            '2027-03-21',
            $employeeUpdates[0]['End Date (Contract)'] ?? null,
            'Extend must advance End Date (Contract) to the manually entered New Contract End.'
        );
        $this->assertSame(
            '2026-09-22',
            $employeeUpdates[0]['Start Date (Contract)'] ?? null,
            'Extend must sync Start Date (Contract) for the Employee edit modal contract section.'
        );
        $this->assertSame(
            '6 Bulan',
            $employeeUpdates[0]['Contract Duration'] ?? null,
            'Extend must sync Contract Duration for the Employee edit modal contract section.'
        );
    }

    // =========================================================================
    // T04 â€” EXTEND: a manipulated client duration is ignored.
    // =========================================================================

    public function test_t04_extend_rejects_mismatched_duration(): void
    {
        [$response, $rows] = $this->postEvaluation('Perpanjang Kontrak', array_merge([
            'extension_duration' => '12 Bulan',
        ], $this->manualExtend('2026-12-01', '2027-02-28')));

        $response->assertOk();
        $this->assertSame('3 Bulan', self::ref($rows[0], 'Extension Duration'));
        $this->assertSame('2027-02-28', self::ref($rows[0], 'New Contract End'));
    }

    // =========================================================================
    // T05 â€” EXTEND: client sends no duration â†’ server still resolves from contract
    // =========================================================================

    public function test_t05_extend_resolves_duration_when_client_sends_none(): void
    {
        [$response, $rows] = $this->postEvaluation(
            'Perpanjang Kontrak',
            $this->manualExtend('2026-12-01', '2027-05-31')
        );

        $response->assertOk()->assertJsonPath('success', true)
            ->assertJsonPath('decisionType', 'extend')
            ->assertJsonPath('pdfUrl', null);

        $row = end($rows);
        $this->assertSame('6 Bulan', self::ref($row, 'Extension Duration'));
        $this->assertSame('2026-12-01', self::ref($row, 'New Contract Start'));
        $this->assertSame('2027-05-31', self::ref($row, 'New Contract End'));
    }

    // =========================================================================
    // T06 â€” EXTEND without extension_start â†’ validation error
    //       (Duration is no longer user-provided; only start date is required.)
    // =========================================================================

    public function test_t06_extend_without_start_date_fails_validation(): void
    {
        // No extension_start sent â†’ backend must reject with 422.
        // extension_duration is irrelevant â€” backend ignores it regardless.
        [$response, $rows] = $this->postEvaluation('Perpanjang Kontrak', [
            'extension_end' => '2027-05-31',
        ]);

        $response->assertStatus(422);
        $this->assertCount(0, $rows, 'No evaluation row may be written when validation fails.');
    }

    public function test_t06b_extend_without_end_date_fails_validation(): void
    {
        [$response, $rows] = $this->postEvaluation('Perpanjang Kontrak', [
            'extension_start' => '2026-12-01',
        ]);

        $response->assertStatus(422);
        $this->assertCount(0, $rows);
    }

    public function test_t06c_extend_end_before_start_fails_validation(): void
    {
        [$response, $rows] = $this->postEvaluation(
            'Perpanjang Kontrak',
            $this->manualExtend('2026-12-01', '2026-11-01')
        );

        $response->assertStatus(422);
        $this->assertCount(0, $rows);
    }

    public function test_duplicate_evaluation_submission_does_not_append_twice(): void
    {
        $this->loginAsHrAdmin();
        $captured = [];
        $this->bindEvaluateFlowMocks($captured, []);
        $payload = array_merge([
            'decision' => 'Perpanjang Kontrak',
            'extension_start' => '2026-12-01',
            'extension_end' => '2027-05-31',
            'notes' => 'duplicate-protection-' . uniqid(),
        ], $this->validIndicatorPayload());

        $this->postJson('/hr/probation/EMP001/evaluate', $payload)
            ->assertOk()
            ->assertJsonPath('success', true);
        $this->postJson('/hr/probation/EMP001/evaluate', $payload)
            ->assertStatus(422);

        $this->assertCount(1, $captured);
    }

    // =========================================================================
    // T07 â€” Authorized preview renders Performance Review HTML from sheet data
    // =========================================================================

    public function test_t07_authorized_user_can_preview_performance_review(): void
    {
        $this->loginAsHrAdmin();

        $employeeRepo = Mockery::mock(EmployeeRepositoryInterface::class);
        $employeeRepo->shouldReceive('findById')->with('EMP001')->andReturn($this->makeEmployee());
        $this->app->instance(EmployeeRepositoryInterface::class, $employeeRepo);

        $sheetRow = array_combine($this->headers(), array_fill(0, count($this->headers()), ''));
        $sheetRow['Employee ID'] = 'EMP001';
        $sheetRow['Eval ID']     = 'EVAL-1';
        $sheetRow['Eval Date']   = '2026-08-01 10:00:00';
        $sheetRow['Overall Total'] = '11';
        $sheetRow['Category']      = 'Sangat Baik';
        $sheetRow['Decision']      = 'Diangkat sebagai Karyawan Tetap';

        $sheets = Mockery::mock(GoogleSheetsService::class);
        $sheets->shouldReceive('clearCache')->andReturn(null)->byDefault();
        $sheets->shouldReceive('getRowsAsAssoc')
            ->with('kandidat_probation')
            ->andReturn([$sheetRow]);
        $this->app->instance(GoogleSheetsService::class, $sheets);

        $response = $this->get('/hr/probation/EMP001/preview?eval_id=EVAL-1');

        $response->assertOk();
        $response->assertSee('Budi Santoso', false);
    }

    // =========================================================================
    // T08 â€” On-demand Performance Review PDF streams directly (no storage)
    // =========================================================================

    public function test_t08_performance_review_pdf_streams_directly_on_demand(): void
    {
        $this->loginAsHrAdmin();

        $employeeRepo = Mockery::mock(EmployeeRepositoryInterface::class);
        $employeeRepo->shouldReceive('findById')->with('EMP001')->andReturn($this->makeEmployee());
        $this->app->instance(EmployeeRepositoryInterface::class, $employeeRepo);

        $sheetRow = array_combine($this->headers(), array_fill(0, count($this->headers()), ''));
        $sheetRow['Employee ID']   = 'EMP001';
        $sheetRow['Eval ID']       = 'EVAL-1';
        $sheetRow['Eval Date']     = '2026-08-01 10:00:00';
        $sheetRow['Overall Total'] = '11';
        $sheetRow['Category']      = 'Sangat Baik';
        $sheetRow['Decision']      = 'Diangkat sebagai Karyawan Tetap';

        $sheets = Mockery::mock(GoogleSheetsService::class);
        $sheets->shouldReceive('clearCache')->andReturn(null)->byDefault();
        $sheets->shouldReceive('getRowsAsAssoc')->andReturn([$sheetRow]);
        $this->app->instance(GoogleSheetsService::class, $sheets);

        $response = $this->get('/hr/export/performance-review/EMP001?eval_id=EVAL-1');

        $response->assertOk();
        $this->assertStringContainsString('application/pdf', (string) $response->headers->get('Content-Type'));
        // DomPDF streams binary content starting with the %PDF magic bytes
        ob_start();
        $response->sendContent();
        $content = (string) ob_get_clean();
        $this->assertSame('%PDF', substr($content, 0, 4));
    }

    // =========================================================================
    // T09 â€” Unauthorized users cannot preview or download anything
    // =========================================================================

    public function test_t09_unauthorized_user_cannot_preview_or_download(): void
    {
        // No hr_user session â†’ hr.auth middleware must block access
        $this->get('/hr/probation/EMP001/preview?eval_id=EVAL-1')->assertRedirect();
        $this->get('/hr/export/performance-review/EMP001?eval_id=EVAL-1')->assertRedirect();
        $this->get('/hr/export/sk-pengangkatan/EMP001')->assertRedirect();
        $this->get('/hr/export/paklaring/EMP001')->assertRedirect();
    }

    // =========================================================================
    // T10 â€” Legacy score columns AND storage document-reference columns
    //       are absent from all active references
    // =========================================================================

    public function test_t10_legacy_and_document_reference_columns_are_removed(): void
    {
        // Legacy score columns from the GAS era â€” removed by the cleanup task
        $legacy = [
            'Score Performance',
            'Score Discipline',
            'Score Communication',
            'Score Initiative',
            'Score Teamwork',
            'Average Score',
        ];
        // Document-reference columns from the (reverted) storage-based design
        $storageRefs = [
            'Performance Review File',
            'Decision Doc Type',
            'Decision Doc File',
            'Extension Letter No',
        ];

        $headers = $this->headers();

        foreach ([...$legacy, ...$storageRefs] as $col) {
            $this->assertNotContains(
                $col,
                config('hris.schemas.kandidat_probation'),
                "Column \"{$col}\" must not exist in config/hris.php schema."
            );
            $this->assertNotContains(
                $col,
                $headers,
                "Column \"{$col}\" must not exist in PROBATION_HEADERS."
            );
        }
    }

    // =========================================================================
    // T11 â€” Enum classification: EXTEND produces NO document at all
    // =========================================================================

    public function test_t11_decision_type_classification_and_pdf_rules(): void
    {
        $this->assertSame(ProbationDecisionType::PASS, ProbationDecisionType::fromDecisionString('Diangkat sebagai Karyawan Tetap'));
        $this->assertSame(ProbationDecisionType::FAIL, ProbationDecisionType::fromDecisionString('Tidak Lulus'));
        $this->assertSame(ProbationDecisionType::EXTEND, ProbationDecisionType::fromDecisionString('Perpanjang Kontrak'));

        $this->assertTrue(ProbationDecisionType::PASS->hasPdf());
        $this->assertTrue(ProbationDecisionType::FAIL->hasPdf());
        $this->assertFalse(ProbationDecisionType::EXTEND->hasPdf(), 'EXTEND must NOT generate any PDF.');

        $this->assertSame('sk_pengangkatan', ProbationDecisionType::PASS->documentType());
        $this->assertSame('paklaring', ProbationDecisionType::FAIL->documentType());
        $this->assertNull(ProbationDecisionType::EXTEND->documentType(), 'EXTEND has no decision document.');
    }

    // =========================================================================
    // T12/T13 â€” Re-evaluation after EXTEND uses history and forbids EXTEND
    // =========================================================================

    private function extendHistoryRow(): array
    {
        $row = array_combine($this->headers(), array_fill(0, count($this->headers()), ''));
        $row['Employee ID'] = 'EMP001';
        $row['Eval ID'] = 'EVAL-EXTEND-1';
        $row['Eval Date'] = '2026-08-01 10:00:00';
        $row['Decision'] = 'Extend';
        $row['Extension Duration'] = '3 Bulan';
        $row['New Contract Start'] = '2026-09-01';
        $row['New Contract End'] = '2026-12-01';
        $row['ind_integrity_1'] = '1';
        $row['ind_ci_1'] = '0';
        return $row;
    }

    public function test_t12_re_evaluation_after_extend_allows_lulus_and_preserves_history(): void
    {
        $previous = $this->extendHistoryRow();
        [$response, $secondRows] = $this->postEvaluation('Lulus', [], [$previous]);

        $response->assertOk()->assertJsonPath('success', true)->assertJsonPath('decisionType', 'pass');
        $this->assertSame('Lulus', self::ref(end($secondRows), 'Decision'));
        $this->assertSame('Extend', self::ref($previous, 'Decision'));
    }

    /**
     * T13 — Second EXTEND is rejected after a first EXTEND.
     * Business rule: extension is allowed only once. After EXTEND the employee
     * must be evaluated to PASS or FAIL on the next evaluation.
     * No evaluation row may be written when the second EXTEND is blocked.
     */
    public function test_t13_second_extend_after_first_extend_is_rejected(): void
    {
        $previous = $this->extendHistoryRow();

        [$response, $secondRows] = $this->postEvaluation(
            'Perpanjang Kontrak',
            $this->manualExtend('2027-06-01', '2027-11-30'),
            [$previous],
            '2027-05-31',
            '2026-12-01'
        );

        $response->assertStatus(422);
        $this->assertCount(
            0,
            $secondRows,
            'A second Extend must not append an evaluation row — only one extension is allowed.'
        );
        $this->assertStringContainsString(
            'Perpanjangan probation hanya dapat dilakukan satu kali',
            $response->json('message') ?? ''
        );
    }

    /**
     * T13b — After first EXTEND, PASS is still allowed (not blocked).
     */
    public function test_t13b_pass_after_extend_succeeds(): void
    {
        $previous = $this->extendHistoryRow();

        [$response, $secondRows] = $this->postEvaluation('Lulus', [], [$previous]);

        $response->assertOk()->assertJsonPath('success', true)
            ->assertJsonPath('decisionType', 'pass');
        $this->assertCount(1, $secondRows, 'PASS after EXTEND must write exactly one evaluation row.');
        $this->assertSame('Lulus', self::ref($secondRows[0], 'Decision'));
    }

    // =========================================================================
    // T14 â€” EXTEND: 3-month contract â†’ extension duration = 3 Bulan
    // =========================================================================

    public function test_t14_extend_3_month_contract_derives_3_bulan(): void
    {
        // joinDate=2026-09-01, endDateContract=2026-11-30 â†’ 3 calendar months
        [$response, $rows] = $this->postEvaluation(
            'Perpanjang Kontrak',
            $this->manualExtend('2026-12-01', '2027-02-28'),
            [],
            '2026-11-30',
            '2026-09-01'
        );

        $response->assertOk()->assertJsonPath('success', true)
            ->assertJsonPath('decisionType', 'extend');

        $row = end($rows);
        $this->assertSame(
            '3 Bulan',
            self::ref($row, 'Extension Duration'),
            'Contract duration 3 months â†’ Extension Duration must be "3 Bulan"'
        );
        // New Contract End = 2026-12-01 + 3 months âˆ’ 1 day = 2027-02-28
        $this->assertSame('2027-02-28', self::ref($row, 'New Contract End'));
        // Server-derived duration must appear in the JSON response
        $this->assertSame(
            '3 Bulan',
            $response->json('extensionDuration'),
            'JSON response extensionDuration must reflect server-derived value, not browser input'
        );
    }

    // =========================================================================
    // T15 â€” EXTEND: 12-month contract â†’ extension duration = 12 Bulan
    // =========================================================================

    public function test_t15_extend_12_month_contract_derives_12_bulan(): void
    {
        // joinDate=2026-05-01, endDateContract=2027-04-30 â†’ 12 calendar months
        [$response, $rows] = $this->postEvaluation(
            'Perpanjang Kontrak',
            $this->manualExtend('2027-05-01', '2028-04-30'),
            [],
            '2027-04-30',
            '2026-05-01'
        );

        $response->assertOk()->assertJsonPath('decisionType', 'extend');

        $row = end($rows);
        $this->assertSame(
            '12 Bulan',
            self::ref($row, 'Extension Duration'),
            'Contract duration 12 months â†’ Extension Duration must be "12 Bulan"'
        );
        // New Contract End = 2027-05-01 + 12 months âˆ’ 1 day = 2028-04-30
        $this->assertSame('2028-04-30', self::ref($row, 'New Contract End'));
        $this->assertSame('12 Bulan', $response->json('extensionDuration'));
    }

    // =========================================================================
    // T16 â€” EXTEND: manipulated request sends 12 Bulan; contract is 3 months.
    //       Backend must store 3 Bulan regardless.
    // =========================================================================

    public function test_t16_manipulated_request_duration_is_ignored_backend_uses_contract(): void
    {
        // joinDate=2026-09-01, endDateContract=2026-11-30 â†’ 3 calendar months
        [$response, $rows] = $this->postEvaluation(
            'Perpanjang Kontrak',
            array_merge([
                'extension_duration' => '12 Bulan',
            ], $this->manualExtend('2026-12-01', '2027-02-28')),
            [],
            '2026-11-30',
            '2026-09-01'
        );

        $response->assertOk();
        $row = end($rows);
        $this->assertSame(
            '3 Bulan',
            self::ref($row, 'Extension Duration'),
            'Manipulated extension_duration=12 Bulan must be ignored; duration comes from manual start/end'
        );
        $this->assertSame(
            '2027-02-28',
            self::ref($row, 'New Contract End'),
            'New Contract End must equal the manually submitted extension_end'
        );
        $this->assertSame(
            '3 Bulan',
            $response->json('extensionDuration'),
            'JSON extensionDuration must echo server-derived value, never browser input'
        );
    }

    // =========================================================================
    // T17 â€” EXTEND: JSON response extensionDuration reflects server-derived value
    //       (regression test for the controller fix that replaced $evalData with $result)
    // =========================================================================

    public function test_t17_json_response_extension_duration_is_server_derived(): void
    {
        // 6-month contract (default: joinDate=2026-05-01, endDateContract=2026-11-01 â†’ 6 months)
        [$response, $rows] = $this->postEvaluation(
            'Perpanjang Kontrak',
            $this->manualExtend('2026-12-01', '2027-05-31')
        );

        $response->assertOk()->assertJsonPath('decisionType', 'extend');
        // The JSON extensionDuration must be the server-derived "6 Bulan",
        // not an empty string (which was the bug before the controller fix).
        $this->assertSame(
            '6 Bulan',
            $response->json('extensionDuration'),
            'Before fix: controller echoed $evalData[extension_duration] = "" (empty). '
                . 'After fix: controller returns $result[extensionDuration] = "6 Bulan".'
        );
        // Sheet row must also match
        $this->assertSame('6 Bulan', self::ref(end($rows), 'Extension Duration'));
    }

    // =========================================================================
    // T18 â€” EXTEND: missing contract dates â†’ RuntimeException blocks submission
    // =========================================================================

    public function test_t18_extend_blocked_when_contract_duration_not_derivable(): void
    {
        $this->loginAsHrAdmin();
        $captured = [];

        // Employee with no endDateContract â†’ duration cannot be computed
        $employeeWithNoEnd = new EmployeeData(
            employeeId: 'EMP001',
            fullName: 'Budi Santoso',
            branchName: '',
            department: 'IT',
            jobPosition: 'Staff IT',
            jobPositionLocation: 'Staff IT - Jakarta',
            jobLevel: 'Staff',
            joinDate: '2026-05-01',
            endDateContract: null,       // â† no contract end date
            statusEmployee: 'Contract',
            personalEmail: 'budi@example.com',
        );

        $employeeRepo = Mockery::mock(EmployeeRepositoryInterface::class);
        $employeeRepo->shouldReceive('findById')->with('EMP001')->andReturn($employeeWithNoEnd);
        $employeeRepo->shouldReceive('update')->andReturn(true)->byDefault();
        $this->app->instance(EmployeeRepositoryInterface::class, $employeeRepo);

        $auditRepo = Mockery::mock(AuditLogRepositoryInterface::class);
        $auditRepo->shouldReceive('log')->andReturn(true)->byDefault();
        $this->app->instance(AuditLogRepositoryInterface::class, $auditRepo);

        $headers = $this->headers();
        $sheets  = Mockery::mock(GoogleSheetsService::class);
        $sheets->shouldReceive('getRange')->andReturn([$headers])->byDefault();
        $sheets->shouldReceive('getRowsAsAssoc')->andReturn([])->byDefault();
        $sheets->shouldReceive('clearCache')->andReturn(null)->byDefault();
        $sheets->shouldReceive('appendRow')
            ->withArgs(function (string $s, array $r) use ($headers, &$captured) {
                $captured[] = array_combine($headers, $r);
                return true;
            })->andReturn(true)->byDefault();
        $this->app->instance(GoogleSheetsService::class, $sheets);

        $payload = array_merge([
            'decision' => 'Perpanjang Kontrak',
        ], $this->manualExtend('2026-12-01', '2027-05-31'), $this->validIndicatorPayload());

        $response = $this->postJson('/hr/probation/EMP001/evaluate', $payload);

        // Manual start/end make extend independent of Employee contract dates.
        $response->assertOk()->assertJsonPath('decisionType', 'extend');
        $this->assertCount(1, $captured);
        $this->assertSame('6 Bulan', (string) ($captured[0]['Extension Duration'] ?? ''));
    }

    // =========================================================================
    // T19 â€” Date calculation: each contract duration produces correct end date
    //       (canonical: End = Start + N months âˆ’ 1 day)
    // =========================================================================
    // T19 - Date calculation: each contract duration produces correct end date
    //       (canonical: End = Start + N months - 1 day)
    //       Each case is a separate test method to avoid Mockery singleton bleed.
    // =========================================================================

    public function test_t19a_date_calculation_3_month_contract(): void
    {
        // 3-month: joinDate=2026-09-01, contractEnd=2026-11-30
        // New end = 2026-12-01 + 3 months - 1 day = 2027-02-28
        [$response, $rows] = $this->postEvaluation(
            'Perpanjang Kontrak',
            $this->manualExtend('2026-12-01', '2027-02-28'),
            [],
            '2026-11-30',
            '2026-09-01'
        );
        $response->assertOk()->assertJsonPath('decisionType', 'extend');
        $row = end($rows);
        $this->assertSame('3 Bulan', self::ref($row, 'Extension Duration'));
        $this->assertSame('2027-02-28', self::ref($row, 'New Contract End'));
        $this->assertSame('2026-12-01', self::ref($row, 'New Contract Start'));
    }

    public function test_t19b_date_calculation_6_month_contract(): void
    {
        // 6-month: joinDate=2026-05-01, contractEnd=2026-11-01
        // New end = 2026-12-01 + 6 months - 1 day = 2027-05-31
        [$response, $rows] = $this->postEvaluation(
            'Perpanjang Kontrak',
            $this->manualExtend('2026-12-01', '2027-05-31'),
            [],
            '2026-11-01',
            '2026-05-01'
        );
        $response->assertOk()->assertJsonPath('decisionType', 'extend');
        $row = end($rows);
        $this->assertSame('6 Bulan', self::ref($row, 'Extension Duration'));
        $this->assertSame('2027-05-31', self::ref($row, 'New Contract End'));
        $this->assertSame('2026-12-01', self::ref($row, 'New Contract Start'));
    }

    public function test_t19c_date_calculation_12_month_contract(): void
    {
        // 12-month: joinDate=2026-05-01, contractEnd=2027-04-30
        // New end = 2027-05-01 + 12 months - 1 day = 2028-04-30
        [$response, $rows] = $this->postEvaluation(
            'Perpanjang Kontrak',
            $this->manualExtend('2027-05-01', '2028-04-30'),
            [],
            '2027-04-30',
            '2026-05-01'
        );
        $response->assertOk()->assertJsonPath('decisionType', 'extend');
        $row = end($rows);
        $this->assertSame('12 Bulan', self::ref($row, 'Extension Duration'));
        $this->assertSame('2028-04-30', self::ref($row, 'New Contract End'));
        $this->assertSame('2027-05-01', self::ref($row, 'New Contract Start'));
    }

    // =========================================================================
    // T20 — Imported employee (no kandidat_accepted, no Offering Contract Duration)
    //       can still extend successfully using joinDate / endDateContract.
    //
    // This is the canonical "Case 2 — Import" test from the spec:
    //   Status Employee = Contract
    //   No kandidat_accepted record
    //   No Offering Contract Duration
    //   Join Date = valid
    //   End Date (Contract) = valid
    //   → Extend must succeed, duration derived from contract dates.
    // =========================================================================

    public function test_t20_imported_employee_extend_succeeds_without_recruitment_record(): void
    {
        $this->loginAsHrAdmin();

        // Employee created via Excel import — no recruitment_id, no offering data
        $importedEmployee = new EmployeeData(
            employeeId: 'IMP001',
            fullName: 'Siti Rahmawati',
            branchName: '',
            department: 'Finance',
            jobPosition: 'Staff Finance',
            joinDate: '2026-02-15',          // ← import-supplied contract start
            endDateContract: '2026-08-14',   // ← import-supplied contract end (6 months)
            statusEmployee: 'Contract',
            personalEmail: 'siti@example.com',
        );

        $employeeRepo = Mockery::mock(EmployeeRepositoryInterface::class);
        $employeeRepo->shouldReceive('findById')->with('IMP001')->andReturn($importedEmployee);
        $employeeRepo->shouldReceive('update')->andReturn(true)->byDefault();

        $auditRepo = Mockery::mock(AuditLogRepositoryInterface::class);
        $auditRepo->shouldReceive('log')->andReturn(true)->byDefault();

        $headers = $this->headers();
        $captured = [];
        $sheets = Mockery::mock(GoogleSheetsService::class);
        $sheets->shouldReceive('getRange')->with('kandidat_probation', '1:1', false)->andReturn([$headers])->byDefault();
        $sheets->shouldReceive('appendRow')
            ->withArgs(function (string $sheet, array $row) use ($headers, &$captured) {
                if (count($row) !== count($headers)) return false;
                $captured[] = array_combine($headers, $row);
                return true;
            })->andReturn(true)->byDefault();
        $sheets->shouldReceive('getRowsAsAssoc')->andReturn([])->byDefault();  // no prior history
        $sheets->shouldReceive('clearCache')->andReturn(null)->byDefault();
        $sheets->shouldReceive('updateRange')->andReturn(true)->byDefault();

        $this->app->instance(EmployeeRepositoryInterface::class, $employeeRepo);
        $this->app->instance(AuditLogRepositoryInterface::class, $auditRepo);
        $this->app->instance(GoogleSheetsService::class, $sheets);

        $payload = array_merge([
            'decision'           => 'Perpanjang Kontrak',
            'extension_start'    => '2026-08-15',
            'extension_end'      => '2027-02-14',
            // recruitment_id intentionally omitted — imported employee has none
        ], $this->validIndicatorPayload());

        $response = $this->postJson('/hr/probation/IMP001/evaluate', $payload);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('decisionType', 'extend');

        $this->assertCount(1, $captured, 'Imported employee extend must append exactly one evaluation row.');

        $row = $captured[0];
        // Duration = monthsBetween(2026-02-15, 2026-08-14) = 6 months
        $this->assertSame(
            '6 Bulan',
            self::ref($row, 'Extension Duration'),
            'Duration must be derived from manual New Contract Start/End dates.'
        );
        $this->assertSame('2026-08-15', self::ref($row, 'New Contract Start'));
        // New end = 2026-08-15 + 6 months − 1 day = 2027-02-14
        $this->assertSame('2027-02-14', self::ref($row, 'New Contract End'));
        $this->assertSame('IMP001', self::ref($row, 'Employee ID'));
        // No recruitment_id — must be stored as empty string, not cause an error
        $this->assertSame(
            '',
            self::ref($row, 'Recruitment ID'),
            'Missing recruitment_id must be stored as empty string, not cause an error.'
        );
    }

    // =========================================================================
    // T21 — Invalid contract dates (end < start) → Extend rejected safely
    //       No probation history appended, no Employee mutation, no fake duration.
    //
    // This is "Case 4 — Invalid date" from the spec.
    // =========================================================================

    public function test_t21_extend_rejected_when_contract_end_before_start(): void
    {
        $this->loginAsHrAdmin();

        // Employee contract dates are invalid, but manual extend dates are valid.
        $badEmployee = new EmployeeData(
            employeeId: 'EMP_BAD',
            fullName: 'Karyawan BadDate',
            joinDate: '2026-08-01',
            endDateContract: '2026-07-01',
            statusEmployee: 'Contract',
        );

        $employeeRepo = Mockery::mock(EmployeeRepositoryInterface::class);
        $employeeRepo->shouldReceive('findById')->with('EMP_BAD')->andReturn($badEmployee);
        $employeeRepo->shouldReceive('update')->andReturn(true)->once();

        $auditRepo = Mockery::mock(AuditLogRepositoryInterface::class);
        $auditRepo->shouldReceive('log')->andReturn(true)->byDefault();

        $headers = $this->headers();
        $captured = [];
        $sheets = Mockery::mock(GoogleSheetsService::class);
        $sheets->shouldReceive('getRange')->andReturn([$headers])->byDefault();
        $sheets->shouldReceive('getRowsAsAssoc')->andReturn([])->byDefault();
        $sheets->shouldReceive('clearCache')->andReturn(null)->byDefault();
        $sheets->shouldReceive('appendRow')
            ->withArgs(function ($s, $r) use ($headers, &$captured) {
                $captured[] = array_combine($headers, $r);
                return true;
            })->andReturn(true)->byDefault();

        $this->app->instance(EmployeeRepositoryInterface::class, $employeeRepo);
        $this->app->instance(AuditLogRepositoryInterface::class, $auditRepo);
        $this->app->instance(GoogleSheetsService::class, $sheets);

        $payload = array_merge([
            'decision' => 'Perpanjang Kontrak',
        ], $this->manualExtend('2026-09-01', '2027-02-28'), $this->validIndicatorPayload());

        $response = $this->postJson('/hr/probation/EMP_BAD/evaluate', $payload);

        $response->assertOk()->assertJsonPath('decisionType', 'extend');
        $this->assertCount(1, $captured);
        $this->assertSame('6 Bulan', self::ref($captured[0], 'Extension Duration'));
    }

    // =========================================================================
    // T22 — Non-contract employee (Status = Permanent) → Extend rejected.
    //       This is "Case 6 — Non-contract employee" from the spec.
    // =========================================================================

    public function test_t22_permanent_employee_extend_rejected(): void
    {
        $this->loginAsHrAdmin();

        $permanentEmployee = new EmployeeData(
            employeeId: 'EMP_PERM2',
            fullName: 'Karyawan Tetap',
            joinDate: '2024-01-01',
            endDateContract: null,
            statusEmployee: 'Permanent',
        );

        $employeeRepo = Mockery::mock(EmployeeRepositoryInterface::class);
        $employeeRepo->shouldReceive('findById')->with('EMP_PERM2')->andReturn($permanentEmployee);
        $employeeRepo->shouldReceive('update')->never();

        $auditRepo = Mockery::mock(AuditLogRepositoryInterface::class);
        $auditRepo->shouldReceive('log')->andReturn(true)->byDefault();

        $captured = [];
        $sheets = Mockery::mock(GoogleSheetsService::class);
        $sheets->shouldReceive('getRange')->andReturn([$this->headers()])->byDefault();
        $sheets->shouldReceive('getRowsAsAssoc')->andReturn([])->byDefault();
        $sheets->shouldReceive('clearCache')->andReturn(null)->byDefault();
        $sheets->shouldReceive('appendRow')
            ->withArgs(function ($s, $r) use (&$captured) {
                $captured[] = $r;
                return true;
            })
            ->andReturn(true)->byDefault();

        $this->app->instance(EmployeeRepositoryInterface::class, $employeeRepo);
        $this->app->instance(AuditLogRepositoryInterface::class, $auditRepo);
        $this->app->instance(GoogleSheetsService::class, $sheets);

        $payload = array_merge([
            'decision' => 'Perpanjang Kontrak',
        ], $this->manualExtend('2026-09-01', '2027-02-28'), $this->validIndicatorPayload());

        $response = $this->postJson('/hr/probation/EMP_PERM2/evaluate', $payload);

        $response->assertStatus(422);
        $this->assertCount(0, $captured, 'No evaluation row may be written for a non-contract employee.');
    }

    // =========================================================================
    // T23 — Recruitment employee works identically to imported employee.
    //       Both must reach the same extend logic; no origin-based branching.
    //       This is "Case 1 — Recruitment employee" from the spec.
    //
    //       The recruitment employee has a valid joinDate and endDateContract on
    //       the Employee sheet (set during processContractOnboarding). The extend
    //       must derive duration from those dates, NOT from kandidat_accepted's
    //       Offering Contract Duration.
    // =========================================================================

    public function test_t23_recruitment_employee_extend_uses_employee_contract_dates_not_offering(): void
    {
        // Manual start/end define the extension window; Employee join/end are only
        // used for Contract Duration snapshot on the evaluation row history.
        [$response, $rows] = $this->postEvaluation(
            'Perpanjang Kontrak',
            $this->manualExtend('2026-12-01', '2027-05-31'),
            [],
            '2026-11-01',
            '2026-05-01'
        );

        $response->assertOk()->assertJsonPath('decisionType', 'extend');
        $this->assertSame(
            '6 Bulan',
            self::ref($rows[0], 'Extension Duration'),
            'Extension Duration must come from manual New Contract Start/End.'
        );
        $this->assertSame('2027-05-31', self::ref($rows[0], 'New Contract End'));
    }

    // =========================================================================
    // T24 — After EXTEND, drawer/edit contract fields use New Contract Start/End
    //       (not Join Date) when Start Date (Contract) is missing on Employee.
    // =========================================================================

    public function test_t24_enrich_contract_display_uses_latest_extend_dates(): void
    {
        $employee = new EmployeeData(
            employeeId: 'EMP001',
            fullName: 'Budi Santoso',
            joinDate: '2024-12-23',
            endDateContract: '2027-02-10',
            contractStart: null,
            contractDuration: null,
            statusEmployee: 'Contract',
        );

        $headers = $this->headers();
        $extendRow = array_combine($headers, array_fill(0, count($headers), ''));
        $extendRow['Employee ID'] = 'EMP001';
        $extendRow['Eval ID'] = 'EVAL-EXT';
        $extendRow['Eval Date'] = '2026-09-22 10:00:00';
        $extendRow['Decision'] = 'Perpanjang Kontrak';
        $extendRow['Extension Duration'] = '6 Bulan';
        $extendRow['New Contract Start'] = '2026-08-11';
        $extendRow['New Contract End'] = '2027-02-10';

        $sheets = Mockery::mock(GoogleSheetsService::class);
        $sheets->shouldReceive('getRowsAsAssoc')->andReturn([$extendRow])->byDefault();
        $this->app->instance(GoogleSheetsService::class, $sheets);

        $employeeRepo = Mockery::mock(EmployeeRepositoryInterface::class);
        $this->app->instance(EmployeeRepositoryInterface::class, $employeeRepo);
        $auditRepo = Mockery::mock(AuditLogRepositoryInterface::class);
        $this->app->instance(AuditLogRepositoryInterface::class, $auditRepo);

        $service = $this->app->make(\App\Services\ProbationService::class);
        $enriched = $service->enrichEmployeeContractDisplay($employee);

        $this->assertSame('2026-08-11', $enriched->contractStart);
        $this->assertSame('2027-02-10', $enriched->endDateContract);
        $this->assertSame('6 Bulan', $enriched->contractDuration);
        $this->assertSame('2024-12-23', $enriched->joinDate, 'Join Date must remain the original hire date.');
    }
}
