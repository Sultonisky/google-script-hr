<?php

namespace Tests\Feature;

use App\DTOs\EmployeeData;
use App\Enums\SkDocumentType;
use App\Repositories\Contracts\EmployeeDocumentRepositoryInterface;
use App\Repositories\Contracts\EmployeeRepositoryInterface;
use App\Repositories\Database\EmployeeDatabaseRepository;
use App\Repositories\Database\EmployeeDocumentDatabaseRepository;
use App\Services\SkNumberService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Exercises Eloquent-backed employee + document repos (HRIS_DATA_DRIVER=pgsql path).
 * Uses sqlite in phpunit — schema is Postgres-compatible via the same migrations.
 * Does not touch Google Sheets.
 */
class EmployeeDocumentDatabaseRepositoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app->bind(EmployeeRepositoryInterface::class, EmployeeDatabaseRepository::class);
        $this->app->bind(EmployeeDocumentRepositoryInterface::class, EmployeeDocumentDatabaseRepository::class);
    }

    public function test_create_find_update_employee_round_trip(): void
    {
        /** @var EmployeeRepositoryInterface $employees */
        $employees = $this->app->make(EmployeeRepositoryInterface::class);

        $created = $employees->create(new EmployeeData(
            fullName: 'Budi Santoso',
            department: 'IT',
            jobPosition: 'Staff',
            statusEmployee: 'Contract',
            nikNpwp: '3175010101010001',
            joinDate: '2026-01-15',
            endDateContract: '2026-07-14',
        ));

        $this->assertNotEmpty($created->employeeId);
        $this->assertSame('Budi Santoso', $created->fullName);

        $found = $employees->findById($created->employeeId);
        $this->assertNotNull($found);
        $this->assertSame('3175010101010001', $found->nikNpwp);

        $byNik = $employees->findByNik("'3175010101010001");
        $this->assertNotNull($byNik);
        $this->assertSame($created->employeeId, $byNik->employeeId);

        $ok = $employees->update($created->employeeId, [
            'Status Employee' => 'PKWTT',
            'Start Date (Contract)' => '2026-07-15',
            'Contract Duration' => '6 Bulan',
            'End Date (Contract)' => '2027-01-14',
        ]);
        $this->assertTrue($ok);

        $updated = $employees->findById($created->employeeId);
        $this->assertSame('PKWTT', $updated->statusEmployee);
        $this->assertSame('2026-07-15', $updated->contractStart);
        $this->assertSame('6 Bulan', $updated->contractDuration);
        $this->assertSame('2027-01-14', $updated->endDateContract);
    }

    public function test_filters_search_and_soft_delete_status(): void
    {
        /** @var EmployeeRepositoryInterface $employees */
        $employees = $this->app->make(EmployeeRepositoryInterface::class);

        $employees->create(new EmployeeData(
            employeeId: 'EMP-A',
            fullName: 'Alice IT',
            department: 'IT',
            statusEmployee: 'Contract',
            personalEmail: 'alice@mito.id',
        ));
        $employees->create(new EmployeeData(
            employeeId: 'EMP-B',
            fullName: 'Bob HR',
            department: 'HR',
            statusEmployee: 'PKWTT',
        ));

        $this->assertCount(1, $employees->getAll(['department' => 'IT']));
        $this->assertCount(1, $employees->getAll(['status' => 'pkwtt']));
        $this->assertCount(1, $employees->getAll(['search' => 'alice']));

        $this->assertTrue($employees->delete('EMP-A'));
        $this->assertSame('Terminated', $employees->findById('EMP-A')?->statusEmployee);
    }

    public function test_document_append_sequence_and_sk_issue_on_db(): void
    {
        /** @var EmployeeRepositoryInterface $employees */
        $employees = $this->app->make(EmployeeRepositoryInterface::class);
        /** @var EmployeeDocumentRepositoryInterface $docs */
        $docs = $this->app->make(EmployeeDocumentRepositoryInterface::class);

        $emp = $employees->create(new EmployeeData(
            employeeId: 'EMP-DOC-1',
            fullName: 'Citra',
            branchName: 'HO',
            statusEmployee: 'Contract',
        ));

        $sk = $this->app->make(SkNumberService::class);
        $issued = $sk->issue(
            employeeId: $emp->employeeId,
            type: SkDocumentType::PENGANGKATAN,
            branchName: 'HO',
            issuedBy: 'HR Admin',
        );

        $this->assertStringContainsString('/SKP/', $issued['nomor']);
        $this->assertSame(1, $docs->maxSequence());
        $this->assertSame(1, $docs->sequenceForEmployee($emp->employeeId));

        $latest = $docs->getLatestByEmployeeAndType($emp->employeeId, 'SKP');
        $this->assertNotNull($latest);
        $this->assertSame($issued['nomor'], $latest['Nomor']);
        $this->assertSame('SKP', $latest['Doc Code']);
    }

    public function test_reissuing_same_type_same_day_keeps_every_history_row(): void
    {
        /** @var EmployeeRepositoryInterface $employees */
        $employees = $this->app->make(EmployeeRepositoryInterface::class);
        /** @var EmployeeDocumentRepositoryInterface $docs */
        $docs = $this->app->make(EmployeeDocumentRepositoryInterface::class);

        $emp = $employees->create(new EmployeeData(
            employeeId: 'EMP-DOC-2',
            fullName: 'Dewi',
            branchName: 'HO',
            statusEmployee: 'Contract',
        ));

        $sk = $this->app->make(SkNumberService::class);
        $at = \Illuminate\Support\Carbon::parse('2026-09-28 09:00:00', 'Asia/Jakarta');
        foreach (range(1, 3) as $i) {
            $sk->issue(
                employeeId: $emp->employeeId,
                type: SkDocumentType::PKWT,
                branchName: 'HO',
                issuedBy: 'HR Admin',
                issuedAt: $at->copy()->addMinutes($i),
            );
        }

        $history = $docs->getByEmployeeId($emp->employeeId);
        $this->assertCount(3, $history);
        $this->assertSame(
            ['DOC-20260928-001-PKWT', 'DOC-20260928-001-PKWT-2', 'DOC-20260928-001-PKWT-3'],
            $history->pluck('Document ID')->all()
        );
        $this->assertSame(1, $docs->sequenceForEmployee($emp->employeeId));
    }
}
