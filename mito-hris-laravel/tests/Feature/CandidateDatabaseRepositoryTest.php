<?php

namespace Tests\Feature;

use App\DTOs\CandidateData;
use App\Enums\CandidateStatus;
use App\Models\Candidate;
use App\Repositories\Contracts\AuditLogRepositoryInterface;
use App\Repositories\Contracts\CandidateRepositoryInterface;
use App\Repositories\Contracts\EmployeeRepositoryInterface;
use App\Repositories\Database\CandidateDatabaseRepository;
use App\Repositories\Database\EmployeeDatabaseRepository;
use App\Services\RecruitmentService;
use App\Support\CandidateAttributeMap;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Mockery;
use Tests\TestCase;

/**
 * Candidate lifecycle on Eloquent (HRIS_DATA_DRIVER=pgsql).
 * Multi-tab Sheets become lifecycle_status on one table. No Sheets writes.
 */
class CandidateDatabaseRepositoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app->bind(CandidateRepositoryInterface::class, CandidateDatabaseRepository::class);
        $this->app->bind(EmployeeRepositoryInterface::class, EmployeeDatabaseRepository::class);

        $audit = Mockery::mock(AuditLogRepositoryInterface::class);
        $audit->shouldReceive('log')->andReturn(true)->byDefault();
        $this->app->instance(AuditLogRepositoryInterface::class, $audit);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function makePending(string $id = 'REC-20260924-0001'): CandidateData
    {
        /** @var CandidateRepositoryInterface $repo */
        $repo = $this->app->make(CandidateRepositoryInterface::class);

        return $repo->create(new CandidateData(
            recruitmentId: $id,
            fullName: 'Andi Wijaya',
            nik: '3175010101010002',
            email: 'andi@example.com',
            phone: '08123456789',
            city: 'Jakarta',
            positionApplied: 'Staff IT',
            status: CandidateStatus::NEW->value,
        ));
    }

    public function test_create_find_and_list_pending_bucket(): void
    {
        $created = $this->makePending();

        /** @var CandidateRepositoryInterface $repo */
        $repo = $this->app->make(CandidateRepositoryInterface::class);

        $found = $repo->findById($created->recruitmentId);
        $this->assertNotNull($found);
        $this->assertSame('Andi Wijaya', $found->fullName);
        $this->assertSame('3175010101010002', $found->nik);

        $byNik = $repo->findByNik("'3175010101010002");
        $this->assertSame($created->recruitmentId, $byNik?->recruitmentId);

        $pending = $repo->listByLifecycle(['candidates']);
        $this->assertCount(1, $pending);
        $this->assertSame(CandidateAttributeMap::LIFECYCLE_PENDING, Candidate::first()?->lifecycle_status);
    }

    public function test_hold_blacklist_accept_change_lifecycle_not_duplicate_rows(): void
    {
        Event::fake();
        $this->makePending('REC-MOVE-1');

        /** @var RecruitmentService $service */
        $service = $this->app->make(RecruitmentService::class);

        $this->assertTrue($service->holdCandidate('REC-MOVE-1', 'Budget', '2026-10-01'));
        $this->assertSame(1, Candidate::count());
        $this->assertSame(CandidateAttributeMap::LIFECYCLE_HOLD, Candidate::first()?->lifecycle_status);
        $this->assertSame('Budget', Candidate::first()?->hold_reason);

        $this->assertTrue($service->blacklistCandidate('REC-MOVE-1', 'No-show'));
        $this->assertSame(1, Candidate::count());
        $this->assertSame(CandidateAttributeMap::LIFECYCLE_BLACKLIST, Candidate::first()?->lifecycle_status);

        // Reset to pending-like accepted path: accept from current row
        Candidate::query()->update(['lifecycle_status' => CandidateAttributeMap::LIFECYCLE_PENDING]);
        $this->assertTrue($service->acceptCandidateToEmployee('REC-MOVE-1'));
        $this->assertSame(CandidateAttributeMap::LIFECYCLE_ACCEPTED, Candidate::first()?->lifecycle_status);
        $this->assertSame('Accepted', Candidate::first()?->status);
        $this->assertCount(1, $this->app->make(CandidateRepositoryInterface::class)->listByLifecycle(['candidates_accepted']));
        $this->assertCount(0, $this->app->make(CandidateRepositoryInterface::class)->listByLifecycle(['candidates']));
    }

    public function test_offering_and_onboarding_attributes_persist_in_payloads(): void
    {
        $this->makePending('REC-OFFER-1');
        /** @var CandidateRepositoryInterface $repo */
        $repo = $this->app->make(CandidateRepositoryInterface::class);

        $this->assertTrue($repo->update('REC-OFFER-1', [
            'Offering Position' => 'Engineer',
            'Offering Response' => 'Diterima',
            'Offering Allow Pulsa' => '100000',
            'Onboarding Status' => 'Contract',
            'Onboarding By' => 'HR Admin',
        ]));

        $found = $repo->findById('REC-OFFER-1');
        $this->assertSame('Engineer', $found?->offeringPosition);
        $this->assertSame('Diterima', $found?->offeringResponse);
        $this->assertSame('100000', $found?->offeringAllowPulsa);
        $this->assertSame('Contract', $found?->onboardingStatus);
        $this->assertSame('HR Admin', $found?->onboardingBy);
    }

    public function test_pgsql_driver_binds_candidate_database_repository(): void
    {
        config(['google.enabled' => false, 'hris.data_driver' => 'pgsql']);
        $provider = new \App\Providers\AppServiceProvider($this->app);
        $provider->register();

        $this->assertInstanceOf(
            CandidateDatabaseRepository::class,
            $this->app->make(CandidateRepositoryInterface::class)
        );
    }
}
