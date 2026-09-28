<?php

namespace Tests\Feature;

use App\DTOs\EmployeeData;
use App\Models\ProbationEvaluation;
use App\Repositories\Contracts\AuditLogRepositoryInterface;
use App\Repositories\Contracts\EmployeeDocumentRepositoryInterface;
use App\Repositories\Contracts\EmployeeRepositoryInterface;
use App\Repositories\Contracts\ProbationRepositoryInterface;
use App\Repositories\Database\EmployeeDatabaseRepository;
use App\Repositories\Database\EmployeeDocumentDatabaseRepository;
use App\Repositories\Database\ProbationDatabaseRepository;
use App\Services\ProbationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

/**
 * Probation evaluations on Eloquent (HRIS_DATA_DRIVER=pgsql path).
 * Does not touch Google Sheets.
 */
class ProbationDatabaseRepositoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app->bind(EmployeeRepositoryInterface::class, EmployeeDatabaseRepository::class);
        $this->app->bind(EmployeeDocumentRepositoryInterface::class, EmployeeDocumentDatabaseRepository::class);
        $this->app->bind(ProbationRepositoryInterface::class, ProbationDatabaseRepository::class);

        $audit = Mockery::mock(AuditLogRepositoryInterface::class);
        $audit->shouldReceive('log')->andReturn(true)->byDefault();
        $this->app->instance(AuditLogRepositoryInterface::class, $audit);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @return array{indicators: array<string, string>} */
    private function allCheckedIndicators(): array
    {
        $keys = [
            'integrity_1', 'integrity_2', 'integrity_3', 'integrity_4',
            'ci_1', 'ci_2', 'ci_3', 'ci_4',
            'ee_1', 'ee_2',
            'tw_1', 'tw_2', 'tw_3',
        ];

        return ['indicators' => collect($keys)->mapWithKeys(fn ($k) => [$k => '1'])->all()];
    }

    public function test_append_and_get_all_rows_round_trip(): void
    {
        /** @var ProbationRepositoryInterface $repo */
        $repo = $this->app->make(ProbationRepositoryInterface::class);

        $repo->appendEvalRow([
            'Probation ID' => 'PROB-1',
            'Employee ID' => 'EMP-1',
            'Status' => 'Probation',
            'Eval ID' => 'EVAL-1',
            'Eval Date' => '2026-09-24 10:00:00',
            'Decision' => 'Perpanjang Kontrak',
            'Overall Total' => '10',
            'Category' => 'Baik',
            'ind_integrity_1' => '1',
            'ind_ci_1' => '0',
            'Reviewer Name' => 'Atasan',
        ]);

        $rows = $repo->getAllRows();
        $this->assertCount(1, $rows);
        $this->assertSame('EMP-1', $rows[0]['Employee ID']);
        $this->assertSame('EVAL-1', $rows[0]['Eval ID']);
        $this->assertSame('10', $rows[0]['Overall Total']);
        $this->assertSame('1', $rows[0]['ind_integrity_1']);
        $this->assertSame('0', $rows[0]['ind_ci_1']);
        $this->assertSame('Atasan', $rows[0]['Reviewer Name']);
        $this->assertSame(1, ProbationEvaluation::count());
    }

    public function test_evaluate_lulus_persists_eval_and_updates_employee(): void
    {
        /** @var EmployeeRepositoryInterface $employees */
        $employees = $this->app->make(EmployeeRepositoryInterface::class);
        $employees->create(new EmployeeData(
            employeeId: 'EMP-PASS',
            fullName: 'Budi',
            branchName: 'HO',
            statusEmployee: 'Contract',
            joinDate: '2026-01-01',
            endDateContract: '2026-06-30',
        ));

        /** @var ProbationService $service */
        $service = $this->app->make(ProbationService::class);
        $result = $service->evaluateProbation('EMP-PASS', array_merge([
            'decision' => 'Diangkat sebagai Karyawan Tetap',
        ], $this->allCheckedIndicators()), 'HR Admin');

        $this->assertTrue($result['success']);
        $this->assertTrue($result['isLulus']);
        $this->assertStringContainsString('/SKP/', (string) ($result['skNumber'] ?? ''));

        $history = $service->getEvalHistory('EMP-PASS');
        $this->assertCount(1, $history);
        $this->assertSame(13, (int) $history[0]['overallTotal']);
        $this->assertSame('1', $history[0]['ind_integrity_1']);

        $updated = $employees->findById('EMP-PASS');
        $this->assertSame('PKWTT', $updated?->statusEmployee);
        $this->assertFalse($service->canEvaluate('EMP-PASS'));
    }

    public function test_evaluate_extend_then_history_allows_pass(): void
    {
        /** @var EmployeeRepositoryInterface $employees */
        $employees = $this->app->make(EmployeeRepositoryInterface::class);
        $employees->create(new EmployeeData(
            employeeId: 'EMP-EXT',
            fullName: 'Citra',
            branchName: 'HO',
            statusEmployee: 'Contract',
            joinDate: '2026-01-01',
            endDateContract: '2026-06-30',
        ));

        /** @var ProbationService $service */
        $service = $this->app->make(ProbationService::class);

        $extend = $service->evaluateProbation('EMP-EXT', array_merge([
            'decision' => 'Perpanjang Kontrak',
            'extension_start' => '2026-07-01',
            'extension_end' => '2026-12-31',
        ], $this->allCheckedIndicators()), 'HR Admin');

        $this->assertTrue($extend['success']);
        $this->assertTrue($extend['isPerpanjang']);

        $emp = $employees->findById('EMP-EXT');
        $this->assertSame('Contract', $emp?->statusEmployee);
        $this->assertSame('2026-07-01', $emp?->contractStart);
        $this->assertSame('2026-12-31', $emp?->endDateContract);
        $this->assertTrue($service->canEvaluate('EMP-EXT'));

        $pass = $service->evaluateProbation('EMP-EXT', array_merge([
            'decision' => 'Diangkat sebagai Karyawan Tetap',
        ], $this->allCheckedIndicators()), 'HR Admin');

        $this->assertTrue($pass['success']);
        $this->assertCount(2, $service->getEvalHistory('EMP-EXT'));
        $this->assertSame('PKWTT', $employees->findById('EMP-EXT')?->statusEmployee);
    }

    public function test_pgsql_driver_binds_probation_database_repository(): void
    {
        config(['google.enabled' => false, 'hris.data_driver' => 'pgsql']);
        $provider = new \App\Providers\AppServiceProvider($this->app);
        $provider->register();

        $this->assertInstanceOf(
            ProbationDatabaseRepository::class,
            $this->app->make(ProbationRepositoryInterface::class)
        );
    }
}
