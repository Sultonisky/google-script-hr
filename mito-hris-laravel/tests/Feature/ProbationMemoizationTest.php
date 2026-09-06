<?php

namespace Tests\Feature;

use App\DTOs\EmployeeData;
use App\Repositories\Contracts\AuditLogRepositoryInterface;
use App\Repositories\Contracts\EmployeeRepositoryInterface;
use App\Services\Google\GoogleSheetsService;
use App\Services\ProbationService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Session;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use ReflectionClass;
use Tests\TestCase;

/**
 * ProbationMemoizationTest
 *
 * Production regression: EmployeeController/ProbationController loops called
 * getEvalHistory()/getAllProbationRecords()/latestEvalByEmployee() per
 * employee, each issuing clearCache() + a full-sheet Sheets read. With many
 * employees this hit Google's 60-reads/min/user quota -> HTTP 429 -> nginx
 * 504 (fastcgi upstream timeout).
 *
 * The fix: ProbationService memoizes the kandidat_probation rows for the
 * lifetime of a single request. Read methods reuse one in-memory dataset.
 * Writes invalidate BOTH the request-local memo AND the persistent
 * GoogleSheetsService cache, so the next read fetches fresh.
 *
 * These tests assert the contract: getRowsAsAssoc() is called exactly once
 * per request for repeated reads, and is called again after a write that
 * routes through invalidateProbationRowsCache().
 */
class ProbationMemoizationTest extends TestCase
{
    private const SHEET = 'kandidat_probation';

    /** @var array<int,string> */
    private array $probationHeaders;

    protected function setUp(): void
    {
        parent::setUp();
        // Pull the canonical PROBATION_HEADERS from the service so the test
        // never drifts if new columns are appended.
        $this->probationHeaders = (new ReflectionClass(ProbationService::class))
            ->getConstant('PROBATION_HEADERS');
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Build a single probation row carrying both identity and an eval record.
     */
    private function makeRow(string $empId, string $evalId, string $decision, string $evalDate): array
    {
        $row = array_combine($this->probationHeaders, array_fill(0, count($this->probationHeaders), ''));
        $row['Employee ID'] = $empId;
        $row['Eval ID']     = $evalId;
        $row['Eval Date']   = $evalDate;
        $row['Decision']    = $decision;
        return $row;
    }

    /**
     * Bind a mocked GoogleSheetsService + lightweight repos and return the
     * resolved ProbationService. getRowsAsAssoc is bound ->once() (default):
     * any second call inside the same request will throw via Mockery in
     * tearDown, proving the memo is doing its job.
     */
    private function bindServiceWithRows(array $rows): ProbationService
    {
        $sheets = Mockery::mock(GoogleSheetsService::class);
        $sheets->shouldReceive('clearCache')->andReturn(null)->byDefault();
        $sheets->shouldReceive('getRowsAsAssoc')
            ->with(self::SHEET)
            ->once()
            ->andReturn($rows);

        $this->app->instance(GoogleSheetsService::class, $sheets);
        $this->app->instance(EmployeeRepositoryInterface::class, Mockery::mock(EmployeeRepositoryInterface::class));
        $this->app->instance(AuditLogRepositoryInterface::class, Mockery::mock(AuditLogRepositoryInterface::class));

        return $this->app->make(ProbationService::class);
    }

    // =========================================================================
    // 1. Repeated canEvaluate() (delegates to getEvalHistory) = 1 read
    // =========================================================================

    #[Test]
    public function repeated_canEvaluate_calls_load_sheet_only_once(): void
    {
        $row = $this->makeRow('EMP001', 'EVAL-1', 'Diangkat sebagai Karyawan Tetap', '2026-08-01 10:00:00');
        $svc = $this->bindServiceWithRows([$row]);

        // 5 canEvaluate() calls in a loop (mirrors ProbationController@index line 83).
        for ($i = 0; $i < 5; $i++) {
            $svc->canEvaluate('EMP001');
        }

        // getRowsAsAssoc was bound ->once(); Mockery fails in tearDown if a
        // second read slipped through. Reaching here proves the memo.
        $this->assertTrue(true);
    }

    // =========================================================================
    // 2. Repeated getEvalHistory() = 1 read
    // =========================================================================

    #[Test]
    public function repeated_getEvalHistory_loads_sheet_only_once(): void
    {
        $row = $this->makeRow('EMP001', 'EVAL-1', 'Diangkat sebagai Karyawan Tetap', '2026-08-01 10:00:00');
        $svc = $this->bindServiceWithRows([$row]);

        $a = $svc->getEvalHistory('EMP001');
        $b = $svc->getEvalHistory('EMP001');
        $c = $svc->getEvalHistory('EMP001');

        $this->assertCount(1, $a);
        $this->assertCount(1, $b);
        $this->assertCount(1, $c);
        $this->assertSame('EVAL-1', $a[0]['evalId']);
    }

    // =========================================================================
    // 3. Repeated getAllProbationRecords() = 1 read
    // =========================================================================

    #[Test]
    public function repeated_getAllProbationRecords_loads_sheet_only_once(): void
    {
        $r1 = $this->makeRow('EMP001', 'EVAL-1', 'Diangkat sebagai Karyawan Tetap', '2026-08-01 10:00:00');
        $r2 = $this->makeRow('EMP002', 'EVAL-2', 'Perpanjang Kontrak',             '2026-08-02 10:00:00');
        $svc = $this->bindServiceWithRows([$r1, $r2]);

        $first  = $svc->getAllProbationRecords();
        $second = $svc->getAllProbationRecords();
        $third  = $svc->getAllProbationRecords();

        $this->assertCount(2, $first);
        $this->assertCount(2, $second);
        $this->assertCount(2, $third);
    }

    // =========================================================================
    // 4. Repeated latestEvalByEmployee() = 1 read
    // =========================================================================

    #[Test]
    public function repeated_latestEvalByEmployee_loads_sheet_only_once(): void
    {
        $r1 = $this->makeRow('EMP001', 'EVAL-1', 'Diangkat sebagai Karyawan Tetap', '2026-08-01 10:00:00');
        $svc = $this->bindServiceWithRows([$r1]);

        $a = $svc->latestEvalByEmployee();
        $b = $svc->latestEvalByEmployee();
        $c = $svc->latestEvalByEmployee();

        $this->assertCount(1, $a);
        $this->assertCount(1, $b);
        $this->assertCount(1, $c);
        $this->assertSame('EVAL-1', $a->first()['evalId']);
    }

    // =========================================================================
    // 5. Mixed workload (the real N+1 path) = 1 read
    // =========================================================================

    #[Test]
    public function mixed_read_methods_share_one_load(): void
    {
        // Realistic page: getAllProbationRecords() + latestEvalByEmployee()
        // + per-employee canEvaluate() + per-employee getEvalHistory().
        $empA = $this->makeRow('EMP001', 'EVAL-1', 'Diangkat sebagai Karyawan Tetap', '2026-08-01 10:00:00');
        $empB = $this->makeRow('EMP002', 'EVAL-2', 'Perpanjang Kontrak',             '2026-08-02 10:00:00');
        $empC = $this->makeRow('EMP003', 'EVAL-3', 'Tidak Lulus',                    '2026-08-03 10:00:00');
        $svc  = $this->bindServiceWithRows([$empA, $empB, $empC]);

        $svc->getAllProbationRecords();
        $svc->latestEvalByEmployee();
        $svc->getEvalHistory('EMP001');
        $svc->canEvaluate('EMP001');
        $svc->canEvaluate('EMP002');
        $svc->canEvaluate('EMP003');
        $svc->getEvalHistory('EMP002');

        $this->assertTrue(true);
    }

    // =========================================================================
    // 6. Active probation reads, including the sidebar loop = 1 read
    // =========================================================================

    #[Test]
    public function repeated_active_probation_checks_share_one_load(): void
    {
        $row = $this->makeRow('EMP001', 'EVAL-1', 'Perpanjang Kontrak', '2026-08-01 10:00:00');
        $row['Status'] = 'Probation';

        $sheets = Mockery::mock(GoogleSheetsService::class);
        $sheets->shouldReceive('getRowsAsAssoc')
            ->with(self::SHEET)
            ->once()
            ->andReturn([$row]);

        $employees = Mockery::mock(EmployeeRepositoryInterface::class);
        $employees->shouldReceive('findById')
            ->with('EMP001')
            ->twice()
            ->andReturn(new EmployeeData(employeeId: 'EMP001', statusEmployee: 'Contract'));

        $this->app->instance(GoogleSheetsService::class, $sheets);
        $this->app->instance(EmployeeRepositoryInterface::class, $employees);
        $this->app->instance(AuditLogRepositoryInterface::class, Mockery::mock(AuditLogRepositoryInterface::class));

        $svc = $this->app->make(ProbationService::class);

        $this->assertTrue($svc->isActiveProbation('EMP001'));
        $this->assertTrue($svc->isActiveProbation('EMP001'));
    }

    // =========================================================================
    // 7. Actual HR sidebar/dashboard render path = 1 read
    // =========================================================================

    #[Test]
    public function hr_sidebar_probation_count_reuses_probation_load(): void
    {
        cache()->forget('hr_sidebar_probation_count');
        Session::put('hr_user', [
            'email' => 'admin@mito.id',
            'role' => 'Admin',
            'permissions' => config('hris.auth.role_permissions.Admin', []),
            'auth_domain' => 'users',
        ]);

        $rows = [];
        foreach (['EMP001', 'EMP002'] as $employeeId) {
            $row = $this->makeRow($employeeId, 'EVAL-' . $employeeId, 'Perpanjang Kontrak', '2026-08-01 10:00:00');
            $row['Status'] = 'Probation';
            $rows[] = $row;
        }

        $sheets = Mockery::mock(GoogleSheetsService::class);
        $sheets->shouldReceive('getRowsAsAssoc')
            ->with(self::SHEET)
            ->once()
            ->andReturn($rows);

        $employees = Mockery::mock(EmployeeRepositoryInterface::class);
        $employees->shouldReceive('getAll')
            ->once()
            ->andReturn(new Collection([
                (object) ['employeeId' => 'EMP001'],
                (object) ['employeeId' => 'EMP002'],
            ]));
        $employees->shouldReceive('findById')
            ->withArgs(fn (string $employeeId) => in_array($employeeId, ['EMP001', 'EMP002'], true))
            ->twice()
            ->andReturnUsing(fn (string $employeeId) => new EmployeeData(
                employeeId: $employeeId,
                statusEmployee: 'Contract'
            ));

        $this->app->instance(GoogleSheetsService::class, $sheets);
        $this->app->instance(EmployeeRepositoryInterface::class, $employees);
        $this->app->instance(AuditLogRepositoryInterface::class, Mockery::mock(AuditLogRepositoryInterface::class));

        $rendered = (string) $this->view('components.hr-sidebar');

        $this->assertStringContainsString('Probation', $rendered);
        $this->assertStringContainsString('2', $rendered);
    }

    // =========================================================================
    // 8. Write path invalidates memo; next read triggers a fresh Sheets read
    // =========================================================================

    #[Test]
    public function appendProbationEvalRow_invalidates_memo_before_next_read(): void
    {
        $first  = $this->makeRow('EMP001', 'EVAL-1', 'Perpanjang Kontrak',              '2026-08-01 10:00:00');
        $second = $this->makeRow('EMP001', 'EVAL-2', 'Diangkat sebagai Karyawan Tetap', '2026-08-10 10:00:00');

        $sheets = Mockery::mock(GoogleSheetsService::class);
        // 1st read returns [$first]; 2nd read (after invalidate) returns both.
        $sheets->shouldReceive('clearCache')->andReturn(null)->byDefault();
        $sheets->shouldReceive('getRowsAsAssoc')
            ->with(self::SHEET)
            ->twice()
            ->andReturn([$first], [$first, $second]);
        // appendProbationEvalRow reads the header row. Return the full canonical
        // headers so it skips the updateRange branch.
        $sheets->shouldReceive('getRange')
            ->with(self::SHEET, '1:1', false)
            ->andReturn([$this->probationHeaders]);
        $sheets->shouldReceive('appendRow')->andReturn(true);

        $this->app->instance(GoogleSheetsService::class, $sheets);
        $this->app->instance(EmployeeRepositoryInterface::class, Mockery::mock(EmployeeRepositoryInterface::class));
        $this->app->instance(AuditLogRepositoryInterface::class, Mockery::mock(AuditLogRepositoryInterface::class));

        /** @var ProbationService $svc */
        $svc = $this->app->make(ProbationService::class);

        // First read populates the memo with the pre-write snapshot.
        $before = $svc->getEvalHistory('EMP001');
        $this->assertCount(1, $before);
        $this->assertSame('EVAL-1', $before[0]['evalId']);

        // Drive the write path directly (private helper). After this returns,
        // invalidateProbationRowsCache() must have reset the memo AND cleared
        // the persistent GoogleSheetsService cache.
        $writeRef = new \ReflectionMethod(ProbationService::class, 'appendProbationEvalRow');
        $writeRef->setAccessible(true);
        $writeRef->invoke($svc, [
            'Employee ID' => 'EMP001',
            'Eval ID'     => 'EVAL-2',
            'Eval Date'   => '2026-08-10 10:00:00',
            'Decision'    => 'Diangkat sebagai Karyawan Tetap',
        ]);

        // Next read MUST bypass the memo and hit Sheets again, surfacing the
        // newly appended EVAL-2 row.
        $after = $svc->getEvalHistory('EMP001');
        $this->assertCount(2, $after, 'Write must invalidate memo so next read fetches fresh.');
        $this->assertSame('EVAL-2', $after[0]['evalId'], 'Newest eval must appear first.');
    }
}
