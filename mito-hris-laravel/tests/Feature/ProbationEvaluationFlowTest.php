<?php

namespace Tests\Feature;

use App\DTOs\EmployeeData;
use App\Enums\ProbationDecisionType;
use App\Repositories\Contracts\AuditLogRepositoryInterface;
use App\Repositories\Contracts\EmployeeRepositoryInterface;
use App\Services\Google\GoogleSheetsService;
use Illuminate\Support\Facades\Session;
use Mockery;
use ReflectionClass;
use Tests\TestCase;

/**
 * ProbationEvaluationFlowTest
 *
 * End-to-end coverage of the Probation Evaluation flow:
 *   Submit → Backend Validation → Save Evaluation → Decision Classification
 *   → on-demand PDF URLs (hr.export.* — streamed directly to the browser,
 *     same convention as the other PDF functions; NO storage persistence)
 *   → Preview (HTML, same template as PDF).
 *
 * PDF rules:
 *   PASS   → SK Pengangkatan + Performance Review (direct download)
 *   FAIL   → Paklaring + Performance Review (direct download)
 *   EXTEND → NO PDF generated at all (evaluation + duration saved only)
 */
class ProbationEvaluationFlowTest extends TestCase
{
    private const HEADERS = [
        'Probation ID',
        'Employee ID',
        'Recruitment ID',
        'Contract Number',
        'Contract Duration',
        'Contract Start',
        'Contract End',
        'Join Date',
        'Status',
        'Onboarding Date',
        'Onboarding By',
        'Eval ID',
        'Eval Date',
        'Decision',
        'Extension Duration',
        'New Contract Start',
        'New Contract End',
        'Evaluator Notes',
        'Evaluator',
        'SK Status',
        'Notes',
        'Created At',
        'Updated At',
        'Integrity Total',
        'CI Total',
        'EE Total',
        'Teamwork Total',
        'Overall Total',
        'Category',
        'ind_integrity_1',
        'ind_integrity_2',
        'ind_integrity_3',
        'ind_integrity_4',
        'ind_ci_1',
        'ind_ci_2',
        'ind_ci_3',
        'ind_ci_4',
        'ind_ee_1',
        'ind_ee_2',
        'ind_tw_1',
        'ind_tw_2',
        'ind_tw_3',
    ];

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

    private function makeEmployee(): EmployeeData
    {
        return new EmployeeData(
            employeeId: 'EMP001',
            fullName: 'Budi Santoso',
            branchName: '',
            department: 'IT',
            jobPosition: 'Staff IT',
            jobPositionLocation: 'Staff IT - Jakarta',
            jobLevel: 'Staff',
            joinDate: '2026-05-01',
            statusEmployee: 'Probation',
            personalEmail: 'budi@example.com',
        );
    }

    /**
     * Bind mocks for the evaluate flow. $capturedRows receives every appended
     * kandidat_probation row as a header-mapped assoc array.
     */
    private function bindEvaluateFlowMocks(array &$capturedRows, array $existingRows = []): GoogleSheetsService
    {
        $employeeRepo = Mockery::mock(EmployeeRepositoryInterface::class);
        $employeeRepo->shouldReceive('findById')->with('EMP001')->andReturn($this->makeEmployee())->byDefault();
        $employeeRepo->shouldReceive('update')->andReturn(true)->byDefault();

        $auditRepo = Mockery::mock(AuditLogRepositoryInterface::class);
        $auditRepo->shouldReceive('log')->andReturn(true)->byDefault();

        $headers = self::HEADERS;
        $sheets  = Mockery::mock(GoogleSheetsService::class);
        $sheets->shouldReceive('getRange')
            ->with('kandidat_probation', '1:1', false)
            ->andReturn([$headers])->byDefault();
        $sheets->shouldReceive('appendRow')
            ->withArgs(function (string $sheet, array $row) use ($headers, &$capturedRows) {
                $capturedRows[] = array_combine(
                    array_pad($headers, count($row), ''),
                    array_pad($row, count($headers), '')
                );
                return true;
            })->andReturn(true)->byDefault();
        $sheets->shouldReceive('clearCache')->andReturn(null)->byDefault();
        $sheets->shouldReceive('updateRange')->andReturn(true)->byDefault();
        $sheets->shouldReceive('getRowsAsAssoc')->andReturn($existingRows)->byDefault();

        $this->app->instance(EmployeeRepositoryInterface::class, $employeeRepo);
        $this->app->instance(AuditLogRepositoryInterface::class, $auditRepo);
        $this->app->instance(GoogleSheetsService::class, $sheets);

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

    /** POST an evaluation with mocked sheets; returns [response, capturedRows]. */
    private function postEvaluation(string $decision, array $extra = [], array $existingRows = []): array
    {
        $this->loginAsHrAdmin();

        $captured = [];
        $this->bindEvaluateFlowMocks($captured, $existingRows);

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
    // T01 — PASS: evaluation saved + on-demand PDF URLs (SK + Performance Review)
    // =========================================================================

    public function test_t01_pass_returns_download_urls_for_sk_and_performance_review(): void
    {
        [$response, $rows] = $this->postEvaluation('Lulus');

        $response->assertOk()->assertJsonPath('success', true)
            ->assertJsonPath('decisionType', 'pass');

        $row = end($rows);
        $this->assertSame('Lulus', self::ref($row, 'Decision'));
        $this->assertSame('13', self::ref($row, 'Overall Total'));
        $this->assertSame('Sangat Baik', self::ref($row, 'Category'));

        $data = $response->json();
        $this->assertStringContainsString('/hr/export/sk-pengangkatan/EMP001', $data['pdfUrl']);
        $this->assertStringContainsString('sk_number=', $data['pdfUrl']);
        $this->assertStringContainsString('/hr/export/performance-review/EMP001', $data['evalPdfUrl']);
    }

    // =========================================================================
    // T02 — FAIL: evaluation saved + on-demand PDF URLs (Paklaring + Performance Review)
    // =========================================================================

    public function test_t02_fail_returns_download_urls_for_paklaring_and_performance_review(): void
    {
        [$response, $rows] = $this->postEvaluation('Tidak Lulus');

        $response->assertOk()->assertJsonPath('success', true)
            ->assertJsonPath('decisionType', 'fail');

        $row = end($rows);
        $this->assertSame('Tidak Lulus', self::ref($row, 'Decision'));
        $this->assertNotSame('', self::ref($row, 'Eval ID'));

        $data = $response->json();
        $this->assertStringContainsString('/hr/export/paklaring/EMP001', $data['pdfUrl']);
        $this->assertStringContainsString('/hr/export/performance-review/EMP001', $data['evalPdfUrl']);
    }

    // =========================================================================
    // T03/T04/T05 — EXTEND 3/6/12 Bulan: duration saved, NO PDF generated at all
    // =========================================================================

    public static function extensionDurationProvider(): array
    {
        return [['3 Bulan'], ['6 Bulan'], ['12 Bulan']];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('extensionDurationProvider')]
    public function test_t03_to_t05_extend_saves_duration_and_generates_no_pdf(string $duration): void
    {
        [$response, $rows] = $this->postEvaluation('Perpanjang Kontrak', [
            'extension_duration' => $duration,
            'extension_start'    => '2026-09-01',
            'extension_end'      => '2026-12-01',
        ]);

        $response->assertOk()->assertJsonPath('success', true)
            ->assertJsonPath('decisionType', 'extend')
            ->assertJsonPath('pdfUrl', null)
            ->assertJsonPath('evalPdfUrl', null);

        $row = end($rows);
        $this->assertSame($duration, self::ref($row, 'Extension Duration'));
        $this->assertSame('2026-09-01', self::ref($row, 'New Contract Start'));
        $this->assertSame('2026-12-01', self::ref($row, 'New Contract End'));
        $this->assertNotSame('', self::ref($row, 'Eval ID'));
    }

    // =========================================================================
    // T06 — EXTEND without duration → validation error
    // =========================================================================

    public function test_t06_extend_without_duration_fails_validation(): void
    {
        [$response, $rows] = $this->postEvaluation('Perpanjang Kontrak');

        $response->assertStatus(422);
        $this->assertCount(0, $rows, 'No evaluation row may be written when validation fails.');
    }

    // =========================================================================
    // T07 — Authorized preview renders Performance Review HTML from sheet data
    // =========================================================================

    public function test_t07_authorized_user_can_preview_performance_review(): void
    {
        $this->loginAsHrAdmin();

        $employeeRepo = Mockery::mock(EmployeeRepositoryInterface::class);
        $employeeRepo->shouldReceive('findById')->with('EMP001')->andReturn($this->makeEmployee());
        $this->app->instance(EmployeeRepositoryInterface::class, $employeeRepo);

        $sheetRow = array_combine(self::HEADERS, array_fill(0, count(self::HEADERS), ''));
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
    // T08 — On-demand Performance Review PDF streams directly (no storage)
    // =========================================================================

    public function test_t08_performance_review_pdf_streams_directly_on_demand(): void
    {
        $this->loginAsHrAdmin();

        $employeeRepo = Mockery::mock(EmployeeRepositoryInterface::class);
        $employeeRepo->shouldReceive('findById')->with('EMP001')->andReturn($this->makeEmployee());
        $this->app->instance(EmployeeRepositoryInterface::class, $employeeRepo);

        $sheetRow = array_combine(self::HEADERS, array_fill(0, count(self::HEADERS), ''));
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
    // T09 — Unauthorized users cannot preview or download anything
    // =========================================================================

    public function test_t09_unauthorized_user_cannot_preview_or_download(): void
    {
        // No hr_user session → hr.auth middleware must block access
        $this->get('/hr/probation/EMP001/preview?eval_id=EVAL-1')->assertRedirect();
        $this->get('/hr/export/performance-review/EMP001?eval_id=EVAL-1')->assertRedirect();
        $this->get('/hr/export/sk-pengangkatan/EMP001')->assertRedirect();
        $this->get('/hr/export/paklaring/EMP001')->assertRedirect();
    }

    // =========================================================================
    // T10 — Legacy score columns AND storage document-reference columns
    //       are absent from all active references
    // =========================================================================

    public function test_t10_legacy_and_document_reference_columns_are_removed(): void
    {
        // Legacy score columns from the GAS era — removed by the cleanup task
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

        $headers = (new ReflectionClass(\App\Services\ProbationService::class))->getConstant('PROBATION_HEADERS');

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
    // T11 — Enum classification: EXTEND produces NO document at all
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
    // T12/T13 — Re-evaluation after EXTEND uses history and forbids EXTEND
    // =========================================================================

    private function extendHistoryRow(): array
    {
        $row = array_combine(self::HEADERS, array_fill(0, count(self::HEADERS), ''));
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

    public function test_t13_re_evaluation_after_extend_rejects_forced_extend_without_writing(): void
    {
        $previous = $this->extendHistoryRow();
        [$response, $secondRows] = $this->postEvaluation('Extend', [
            'extension_duration' => '3 Bulan',
            'extension_start' => '2026-12-01',
            'extension_end' => '2027-03-01',
        ], [$previous]);

        $response->assertStatus(422);
        $this->assertCount(0, $secondRows, 'A forced second Extend must not append an evaluation row.');
    }
}
