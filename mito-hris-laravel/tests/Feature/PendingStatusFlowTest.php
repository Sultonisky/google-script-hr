<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\DTOs\CandidateData;
use App\DTOs\EmployeeData;
use App\Repositories\Contracts\CandidateRepositoryInterface;
use App\Repositories\Contracts\EmployeeRepositoryInterface;
use App\Repositories\Contracts\AuditLogRepositoryInterface;
use App\Services\RecruitmentService;
use App\Services\EmployeeIdGenerator;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Session;
use App\Events\CandidateStatusChanged;
use Mockery;

/**
 * PendingStatusFlowTest
 *
 * Verifies Pending → Hold / Blacklist / Accepted is 1:1 with GAS behavior.
 *
 * GAS Reference (backend/Recruitment.gs):
 *   holdCandidate()        → setCell_ updates → buildStatusRow_ → appendRow(kandidat_hold)
 *                            → sheet.deleteRow(source) → writeAuditLog_
 *   blacklistCandidate()   → same pattern → kandidat_blacklist
 *   acceptCandidateToEmployee() → same pattern → kandidat_accepted
 *                                 (Employee sheet creation is done later at PKWT contract stage)
 *
 * Test matrix:
 * P01 – Hold: moveToSheet called with 'candidates_hold'
 * P02 – Hold: extraData contains Hold Reason + Follow Up Date + Processed Date/By
 * P03 – Blacklist: moveToSheet called with 'candidates_blacklist'
 * P04 – Blacklist: extraData contains Reason + Date + Updated By + Processed fields
 * P05 – Accept: moveToSheet called with 'candidates_accepted'
 * P06 – Accept: extraData has Status=Accepted + Processed Date/By, NO Employee ID injected
 * P07 – Accept: Employee sheet is NOT touched (Employee created only at PKWT stage)
 * P08 – Accept: moveToSheet called exactly once (no double-move)
 * P09 – All three fire CandidateStatusChanged event
 * P10 – accept() controller calls acceptCandidateToEmployee, NOT updateCandidateStatus
 * P11 – Duplicate accept: second call throws (candidate no longer in data_kandidat)
 * P12 – Unknown ID: holdCandidate throws RuntimeException with 'tidak ditemukan'
 * P13 – Hold route returns HTTP redirect
 * P14 – Blacklist route returns HTTP redirect
 * P15 – Accept route returns HTTP redirect + session success
 */
class PendingStatusFlowTest extends TestCase
{
    // =========================================================================
    // Helpers
    // =========================================================================

    private function loginAsHrAdmin(): void
    {
        Session::put('hr_user', [
            'email'       => 'admin@mito.id',
            'fullName'    => 'HR Admin',
            'role'        => 'Super Admin',
            'permissions' => ['*'],
        ]);
    }

    private function makePendingCandidate(array $overrides = []): CandidateData
    {
        return new CandidateData(
            recruitmentId:   $overrides['recruitmentId']   ?? 'REC-20260801-000001',
            fullName:        $overrides['fullName']        ?? 'Andi Saputra',
            email:           $overrides['email']           ?? 'andi@gmail.com',
            phone:           $overrides['phone']           ?? '081234567890',
            nik:             $overrides['nik']             ?? '3201010101900001',
            birthDate:       $overrides['birthDate']       ?? '1990-01-01',
            gender:          $overrides['gender']          ?? 'Laki-laki',
            maritalStatus:   $overrides['maritalStatus']   ?? 'Belum Menikah',
            positionApplied: $overrides['positionApplied'] ?? 'HR Staff',
            city:            $overrides['city']            ?? 'Jakarta',
            address:         $overrides['address']         ?? 'Jl. Merdeka No. 1',
            status:          $overrides['status']          ?? 'Pending',
            employeeId:      $overrides['employeeId']      ?? null,
        );
    }

    private function mockAuditRepo(): AuditLogRepositoryInterface
    {
        $mock = Mockery::mock(AuditLogRepositoryInterface::class);
        $mock->shouldReceive('log')->andReturn(true);
        $mock->shouldReceive('getLogs')->andReturn(collect());
        return $mock;
    }

    private function makeService(
        CandidateRepositoryInterface $candidateRepo,
        ?EmployeeRepositoryInterface $employeeRepo = null
    ): RecruitmentService {
        return new RecruitmentService(
            $candidateRepo,
            $employeeRepo ?? Mockery::mock(EmployeeRepositoryInterface::class),
            Mockery::mock(\App\Services\Google\GoogleDriveService::class),
            $this->mockAuditRepo(),
            new EmployeeIdGenerator(),
            $this->app->make(\App\Services\SkNumberService::class)
        );
    }

    // =========================================================================
    // P01: Hold calls moveToSheet with 'candidates_hold'
    // =========================================================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function p01_hold_calls_moveToSheet_with_candidates_hold(): void
    {
        $candidate    = $this->makePendingCandidate();
        $movedToSheet = null;

        $candidateRepo = Mockery::mock(CandidateRepositoryInterface::class);
        $candidateRepo->shouldReceive('findById')->andReturn($candidate);
        $candidateRepo->shouldReceive('moveToSheet')
            ->once()
            ->withArgs(function (string $id, string $sheet, array $extra) use (&$movedToSheet) {
                $movedToSheet = $sheet;
                return $id === 'REC-20260801-000001';
            })
            ->andReturn(true);

        Event::fake();
        $result = $this->makeService($candidateRepo)->holdCandidate('REC-20260801-000001', 'Posisi belum dibuka');

        $this->assertTrue($result);
        $this->assertSame('candidates_hold', $movedToSheet);
    }

    // =========================================================================
    // P02: Hold extraData has Reason, Follow Up Date, Processed Date/By (1:1 GAS)
    // =========================================================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function p02_hold_extra_data_contains_required_fields(): void
    {
        $candidate     = $this->makePendingCandidate();
        $capturedExtra = null;

        $candidateRepo = Mockery::mock(CandidateRepositoryInterface::class);
        $candidateRepo->shouldReceive('findById')->andReturn($candidate);
        $candidateRepo->shouldReceive('moveToSheet')
            ->withArgs(function (string $id, string $sheet, array $extra) use (&$capturedExtra) {
                $capturedExtra = $extra;
                return true;
            })
            ->andReturn(true);

        Event::fake();
        $this->makeService($candidateRepo)->holdCandidate(
            'REC-20260801-000001', 'Budget tidak tersedia', '2026-10-01', 'catatan HR'
        );

        $this->assertArrayHasKey('Hold Reason',         $capturedExtra);
        $this->assertArrayHasKey('Hold Follow Up Date', $capturedExtra);
        $this->assertArrayHasKey('Processed Date',      $capturedExtra);
        $this->assertArrayHasKey('Processed By',        $capturedExtra);
        $this->assertSame('Budget tidak tersedia', $capturedExtra['Hold Reason']);
        $this->assertSame('2026-10-01',             $capturedExtra['Hold Follow Up Date']);
    }

    // =========================================================================
    // P03: Blacklist calls moveToSheet with 'candidates_blacklist'
    // =========================================================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function p03_blacklist_calls_moveToSheet_with_candidates_blacklist(): void
    {
        $candidate    = $this->makePendingCandidate();
        $movedToSheet = null;

        $candidateRepo = Mockery::mock(CandidateRepositoryInterface::class);
        $candidateRepo->shouldReceive('findById')->andReturn($candidate);
        $candidateRepo->shouldReceive('moveToSheet')
            ->once()
            ->withArgs(function (string $id, string $sheet, array $extra) use (&$movedToSheet) {
                $movedToSheet = $sheet;
                return true;
            })
            ->andReturn(true);

        Event::fake();
        $this->makeService($candidateRepo)->blacklistCandidate('REC-20260801-000001', 'Pemalsuan data');

        $this->assertSame('candidates_blacklist', $movedToSheet);
    }

    // =========================================================================
    // P04: Blacklist extraData has Reason, Date, Updated By, Processed fields (1:1 GAS)
    // =========================================================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function p04_blacklist_extra_data_contains_required_fields(): void
    {
        $candidate     = $this->makePendingCandidate();
        $capturedExtra = null;

        $candidateRepo = Mockery::mock(CandidateRepositoryInterface::class);
        $candidateRepo->shouldReceive('findById')->andReturn($candidate);
        $candidateRepo->shouldReceive('moveToSheet')
            ->withArgs(function (string $id, string $sheet, array $extra) use (&$capturedExtra) {
                $capturedExtra = $extra;
                return true;
            })
            ->andReturn(true);

        Event::fake();
        $this->makeService($candidateRepo)->blacklistCandidate(
            'REC-20260801-000001', 'No-show interview', null, 'HR Admin'
        );

        $this->assertSame('No-show interview', $capturedExtra['Blacklist Reason']     ?? null);
        $this->assertNotEmpty($capturedExtra['Blacklist Date']                         ?? '');
        $this->assertSame('HR Admin',          $capturedExtra['Blacklist Updated By'] ?? null);
        $this->assertArrayHasKey('Processed Date', $capturedExtra);
        $this->assertArrayHasKey('Processed By',   $capturedExtra);
    }

    // =========================================================================
    // P05: Accept calls moveToSheet with 'candidates_accepted'
    // =========================================================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function p05_accept_calls_moveToSheet_with_candidates_accepted(): void
    {
        $candidate    = $this->makePendingCandidate();
        $movedToSheet = null;

        $candidateRepo = Mockery::mock(CandidateRepositoryInterface::class);
        $candidateRepo->shouldReceive('findById')->andReturn($candidate);
        $candidateRepo->shouldReceive('moveToSheet')
            ->once()
            ->withArgs(function (string $id, string $sheet, array $extra) use (&$movedToSheet) {
                $movedToSheet = $sheet;
                return $id === 'REC-20260801-000001';
            })
            ->andReturn(true);

        $employeeRepo = Mockery::mock(EmployeeRepositoryInterface::class);
        $employeeRepo->shouldNotReceive('create');
        $employeeRepo->shouldNotReceive('findById');

        Event::fake();
        $result = $this->makeService($candidateRepo, $employeeRepo)
            ->acceptCandidateToEmployee('REC-20260801-000001');

        $this->assertTrue($result);
        $this->assertSame('candidates_accepted', $movedToSheet);
    }

    // =========================================================================
    // P06: Accept extraData = Status=Accepted + Processed Date/By
    //      Employee ID is NOT injected here (only at PKWT contract stage)
    // =========================================================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function p06_accept_extra_data_has_status_and_processed_no_employee_id(): void
    {
        $candidate     = $this->makePendingCandidate();
        $capturedExtra = null;

        $candidateRepo = Mockery::mock(CandidateRepositoryInterface::class);
        $candidateRepo->shouldReceive('findById')->andReturn($candidate);
        $candidateRepo->shouldReceive('moveToSheet')
            ->withArgs(function (string $id, string $sheet, array $extra) use (&$capturedExtra) {
                $capturedExtra = $extra;
                return true;
            })
            ->andReturn(true);

        $employeeRepo = Mockery::mock(EmployeeRepositoryInterface::class);
        $employeeRepo->shouldNotReceive('create');
        $employeeRepo->shouldNotReceive('findById');

        Event::fake();
        $this->makeService($candidateRepo, $employeeRepo)
            ->acceptCandidateToEmployee('REC-20260801-000001', [], 'HR Admin');

        $this->assertSame('Accepted', $capturedExtra['Status']        ?? null);
        $this->assertNotEmpty($capturedExtra['Processed Date']         ?? '');
        $this->assertSame('HR Admin', $capturedExtra['Processed By']  ?? null);

        // Employee ID must NOT be set here — only during PKWT contract
        $this->assertArrayNotHasKey('Employee ID', $capturedExtra);
    }

    // =========================================================================
    // P07: Accept does NOT touch Employee sheet (Employee created at PKWT stage)
    // =========================================================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function p07_accept_does_not_create_or_query_employee_sheet(): void
    {
        $candidate = $this->makePendingCandidate();

        $candidateRepo = Mockery::mock(CandidateRepositoryInterface::class);
        $candidateRepo->shouldReceive('findById')->andReturn($candidate);
        $candidateRepo->shouldReceive('moveToSheet')->andReturn(true);

        // Strict: neither findById nor create must be called on employeeRepo
        $employeeRepo = Mockery::mock(EmployeeRepositoryInterface::class);
        $employeeRepo->shouldNotReceive('create');
        $employeeRepo->shouldNotReceive('findById');

        Event::fake();
        $result = $this->makeService($candidateRepo, $employeeRepo)
            ->acceptCandidateToEmployee('REC-20260801-000001');

        $this->assertTrue($result);
    }

    // =========================================================================
    // P08: moveToSheet is called exactly once (no double-move)
    // =========================================================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function p08_accept_moveToSheet_called_exactly_once(): void
    {
        $candidate  = $this->makePendingCandidate();
        $callCount  = 0;

        $candidateRepo = Mockery::mock(CandidateRepositoryInterface::class);
        $candidateRepo->shouldReceive('findById')->andReturn($candidate);
        $candidateRepo->shouldReceive('moveToSheet')
            ->withArgs(function () use (&$callCount) {
                $callCount++;
                return true;
            })
            ->andReturn(true);

        $employeeRepo = Mockery::mock(EmployeeRepositoryInterface::class);
        $employeeRepo->shouldNotReceive('create');
        $employeeRepo->shouldNotReceive('findById');

        Event::fake();
        $this->makeService($candidateRepo, $employeeRepo)
            ->acceptCandidateToEmployee('REC-20260801-000001');

        $this->assertSame(1, $callCount, 'moveToSheet should be called exactly once — no double-move');
    }

    // =========================================================================
    // P09: All three status changes fire CandidateStatusChanged event
    // =========================================================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function p09_all_three_fire_CandidateStatusChanged_event(): void
    {
        Event::fake();

        $candidate = $this->makePendingCandidate();

        $candidateRepo = Mockery::mock(CandidateRepositoryInterface::class);
        $candidateRepo->shouldReceive('findById')->andReturn($candidate)->times(3);
        $candidateRepo->shouldReceive('moveToSheet')->andReturn(true)->times(3);

        $employeeRepo = Mockery::mock(EmployeeRepositoryInterface::class);
        $employeeRepo->shouldNotReceive('create');
        $employeeRepo->shouldNotReceive('findById');

        $service = $this->makeService($candidateRepo, $employeeRepo);

        $service->holdCandidate('REC-20260801-000001', 'reason');
        $service->blacklistCandidate('REC-20260801-000001', 'reason');
        $service->acceptCandidateToEmployee('REC-20260801-000001');

        Event::assertDispatched(CandidateStatusChanged::class, 3);
    }

    // =========================================================================
    // P10: accept() controller calls acceptCandidateToEmployee (not updateCandidateStatus)
    //      Verified by ensuring moveToSheet('candidates_accepted') is called
    //      and updateStatus() is NOT called
    // =========================================================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function p10_accept_controller_calls_acceptCandidateToEmployee(): void
    {
        $this->loginAsHrAdmin();

        $candidate = $this->makePendingCandidate();

        $candidateRepo = Mockery::mock(CandidateRepositoryInterface::class);
        $candidateRepo->shouldReceive('findById')->andReturn($candidate);
        $candidateRepo->shouldReceive('moveToSheet')
            ->with('REC-20260801-000001', 'candidates_accepted', Mockery::type('array'))
            ->once()
            ->andReturn(true);
        // updateStatus should NOT be called by accept endpoint
        $candidateRepo->shouldNotReceive('updateStatus');

        $employeeRepo = Mockery::mock(EmployeeRepositoryInterface::class);
        $employeeRepo->shouldNotReceive('create');
        $employeeRepo->shouldNotReceive('findById');

        $auditRepo = $this->mockAuditRepo();

        $this->app->instance(CandidateRepositoryInterface::class, $candidateRepo);
        $this->app->instance(EmployeeRepositoryInterface::class, $employeeRepo);
        $this->app->instance(AuditLogRepositoryInterface::class, $auditRepo);

        $response = $this->post('/hr/recruitment/REC-20260801-000001/accept');
        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    // =========================================================================
    // P11: Duplicate accept — second call throws (candidate not found)
    // =========================================================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function p11_duplicate_accept_throws_not_found(): void
    {
        $candidateRepo = Mockery::mock(CandidateRepositoryInterface::class);
        $candidateRepo->shouldReceive('findById')->andReturn(null);

        $this->expectException(\RuntimeException::class);
        $this->makeService($candidateRepo)->acceptCandidateToEmployee('REC-20260801-000001');
    }

    // =========================================================================
    // P12: Unknown candidate ID throws RuntimeException with 'tidak ditemukan'
    // =========================================================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function p12_hold_with_unknown_id_throws_runtime_exception(): void
    {
        $candidateRepo = Mockery::mock(CandidateRepositoryInterface::class);
        $candidateRepo->shouldReceive('findById')->andReturn(null);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/tidak ditemukan/i');
        $this->makeService($candidateRepo)->holdCandidate('INVALID-ID', 'reason');
    }

    // =========================================================================
    // P13: Hold route returns HTTP redirect (form POST, not JSON)
    // =========================================================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function p13_hold_route_returns_redirect(): void
    {
        $this->loginAsHrAdmin();

        $candidate = $this->makePendingCandidate();

        $candidateRepo = Mockery::mock(CandidateRepositoryInterface::class);
        $candidateRepo->shouldReceive('findById')->andReturn($candidate);
        $candidateRepo->shouldReceive('moveToSheet')->andReturn(true);

        $this->app->instance(CandidateRepositoryInterface::class, $candidateRepo);
        $this->app->instance(AuditLogRepositoryInterface::class, $this->mockAuditRepo());

        $response = $this->post('/hr/recruitment/REC-20260801-000001/hold', [
            'reason' => 'Posisi belum tersedia',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    // =========================================================================
    // P14: Blacklist route returns HTTP redirect (form POST, not JSON)
    // =========================================================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function p14_blacklist_route_returns_redirect(): void
    {
        $this->loginAsHrAdmin();

        $candidate = $this->makePendingCandidate();

        $candidateRepo = Mockery::mock(CandidateRepositoryInterface::class);
        $candidateRepo->shouldReceive('findById')->andReturn($candidate);
        $candidateRepo->shouldReceive('moveToSheet')->andReturn(true);

        $this->app->instance(CandidateRepositoryInterface::class, $candidateRepo);
        $this->app->instance(AuditLogRepositoryInterface::class, $this->mockAuditRepo());

        $response = $this->post('/hr/recruitment/REC-20260801-000001/blacklist', [
            'reason' => 'Pemalsuan data',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    // =========================================================================
    // P15: Accept route returns HTTP redirect + session success
    // =========================================================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function p15_accept_route_returns_redirect_with_success(): void
    {
        $this->loginAsHrAdmin();

        $candidate = $this->makePendingCandidate();

        $candidateRepo = Mockery::mock(CandidateRepositoryInterface::class);
        $candidateRepo->shouldReceive('findById')->andReturn($candidate);
        $candidateRepo->shouldReceive('moveToSheet')->andReturn(true);

        $employeeRepo = Mockery::mock(EmployeeRepositoryInterface::class);
        $employeeRepo->shouldNotReceive('create');
        $employeeRepo->shouldNotReceive('findById');

        $this->app->instance(CandidateRepositoryInterface::class, $candidateRepo);
        $this->app->instance(EmployeeRepositoryInterface::class, $employeeRepo);
        $this->app->instance(AuditLogRepositoryInterface::class, $this->mockAuditRepo());

        $response = $this->post('/hr/recruitment/REC-20260801-000001/accept');

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    // =========================================================================
    // Teardown
    // =========================================================================

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
