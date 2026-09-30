<?php

namespace Tests\Feature;

use App\DTOs\CandidateData;
use App\Enums\CandidateStatus;
use App\Models\Candidate;
use App\Repositories\Contracts\AuditLogRepositoryInterface;
use App\Repositories\Contracts\CandidateRepositoryInterface;
use App\Repositories\Contracts\EmployeeRepositoryInterface;
use App\Repositories\Contracts\UserPermissionRepositoryInterface;
use App\Repositories\Database\CandidateDatabaseRepository;
use App\Repositories\Database\EmployeeDatabaseRepository;
use App\Services\PermissionResolver;
use App\Support\CandidateAttributeMap;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Session;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Bulk ubah status dari tabel halaman Recruitment
 * → POST /hr/recruitment/bulk-status.
 */
class BulkCandidateStatusTest extends TestCase
{
    use RefreshDatabase;

    private const URL = '/hr/recruitment/bulk-status';

    protected function setUp(): void
    {
        parent::setUp();

        $this->app->bind(CandidateRepositoryInterface::class, CandidateDatabaseRepository::class);
        $this->app->bind(EmployeeRepositoryInterface::class, EmployeeDatabaseRepository::class);

        $audit = Mockery::mock(AuditLogRepositoryInterface::class);
        $audit->shouldReceive('log')->andReturn(true)->byDefault();
        $audit->shouldReceive('getLogs')->andReturn(collect())->byDefault();
        $this->app->instance(AuditLogRepositoryInterface::class, $audit);

        Event::fake();
    }

    private function loginAsSuperAdmin(): void
    {
        Session::put('hr_user', [
            'email'       => 'admin@mito.id',
            'fullName'    => 'HR Admin',
            'role'        => 'Super Admin',
            'permissions' => ['*'],
        ]);
    }

    private function loginAsUserWithout(string $permission): void
    {
        $email = 'user@mito.id';
        Session::put('hr_user', $this->migratedTestUser([
            'email'       => $email,
            'fullName'    => 'Recruiter',
            'role'        => 'User',
            'permissions' => config('hris.auth.role_permissions')['User'] ?? [],
            'auth_domain' => 'users',
            'entities'    => [],
            'branch'      => '',
        ]));
        app(UserPermissionRepositoryInterface::class)->upsert($email, $permission, false, 'migration-test');
        app(PermissionResolver::class)->forget($email);
    }

    private function makePending(string $id, string $nik): void
    {
        $this->app->make(CandidateRepositoryInterface::class)->create(new CandidateData(
            recruitmentId: $id,
            fullName: 'Kandidat ' . $id,
            nik: $nik,
            email: strtolower($id) . '@example.com',
            phone: '08123456789',
            positionApplied: 'Staff IT',
            status: CandidateStatus::NEW->value,
        ));
    }

    private function lifecycle(string $id): ?string
    {
        return Candidate::where('recruitment_id', $id)->value('lifecycle_status');
    }

    #[Test]
    public function bulk_hold_moves_selected_pending_candidates_with_reason_and_processor(): void
    {
        $this->loginAsSuperAdmin();
        $this->makePending('REC-B-1', '3175010101010001');
        $this->makePending('REC-B-2', '3175010101010002');
        $this->makePending('REC-B-3', '3175010101010003');

        $this->postJson(self::URL, [
            'recruitment_ids' => ['REC-B-1', 'REC-B-2'],
            'status'          => 'Hold',
            'reason'          => 'Talent pool Q4',
        ])->assertOk()->assertJson([
            'success' => true,
            'updated' => 2,
            'failed'  => 0,
            'message' => '2 kandidat berhasil diubah ke Hold.',
        ]);

        $this->assertSame(CandidateAttributeMap::LIFECYCLE_HOLD, $this->lifecycle('REC-B-1'));
        $this->assertSame(CandidateAttributeMap::LIFECYCLE_HOLD, $this->lifecycle('REC-B-2'));
        $this->assertSame(CandidateAttributeMap::LIFECYCLE_PENDING, $this->lifecycle('REC-B-3'));

        $row = Candidate::where('recruitment_id', 'REC-B-1')->first();
        $this->assertSame('Talent pool Q4', $row->hold_reason);
        $this->assertSame('HR Admin', $row->processed_by);
    }

    #[Test]
    public function bulk_accept_does_not_require_reason(): void
    {
        $this->loginAsSuperAdmin();
        $this->makePending('REC-B-1', '3175010101010001');

        $this->postJson(self::URL, [
            'recruitment_ids' => ['REC-B-1'],
            'status'          => 'Accepted',
        ])->assertOk()->assertJson(['success' => true, 'updated' => 1]);

        $this->assertSame(CandidateAttributeMap::LIFECYCLE_ACCEPTED, $this->lifecycle('REC-B-1'));
    }

    #[Test]
    public function non_pending_or_unknown_candidates_are_reported_as_failed(): void
    {
        $this->loginAsSuperAdmin();
        $this->makePending('REC-B-1', '3175010101010001');
        $this->makePending('REC-B-2', '3175010101010002');
        Candidate::where('recruitment_id', 'REC-B-2')
            ->update(['lifecycle_status' => CandidateAttributeMap::LIFECYCLE_ACCEPTED]);

        $this->postJson(self::URL, [
            'recruitment_ids' => ['REC-B-1', 'REC-B-2', 'REC-UNKNOWN'],
            'status'          => 'Blacklist',
            'reason'          => 'Dokumen palsu',
        ])->assertOk()->assertJson([
            'success'    => true,
            'updated'    => 1,
            'failed'     => 2,
            'failed_ids' => ['REC-B-2', 'REC-UNKNOWN'],
        ]);

        $this->assertSame(CandidateAttributeMap::LIFECYCLE_BLACKLIST, $this->lifecycle('REC-B-1'));
        $this->assertSame(CandidateAttributeMap::LIFECYCLE_ACCEPTED, $this->lifecycle('REC-B-2'));
    }

    #[Test]
    public function hold_without_reason_is_rejected(): void
    {
        $this->loginAsSuperAdmin();
        $this->makePending('REC-B-1', '3175010101010001');

        $this->postJson(self::URL, [
            'recruitment_ids' => ['REC-B-1'],
            'status'          => 'Hold',
            'reason'          => '   ',
        ])->assertStatus(422)->assertJsonValidationErrors(['reason']);

        $this->assertSame(CandidateAttributeMap::LIFECYCLE_PENDING, $this->lifecycle('REC-B-1'));
    }

    #[Test]
    public function invalid_status_and_empty_selection_are_rejected(): void
    {
        $this->loginAsSuperAdmin();

        $this->postJson(self::URL, ['recruitment_ids' => [], 'status' => 'Pending'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['recruitment_ids', 'status']);
    }

    #[Test]
    public function user_without_hold_blacklist_permission_cannot_bulk_hold(): void
    {
        $this->loginAsUserWithout('manage_hold_blacklist');
        $this->makePending('REC-B-1', '3175010101010001');

        $this->postJson(self::URL, [
            'recruitment_ids' => ['REC-B-1'],
            'status'          => 'Hold',
            'reason'          => 'Talent pool',
        ])->assertStatus(403)->assertJson(['success' => false]);

        $this->assertSame(CandidateAttributeMap::LIFECYCLE_PENDING, $this->lifecycle('REC-B-1'));
    }

    #[Test]
    public function recruitment_page_renders_bulk_action_bar(): void
    {
        $this->loginAsSuperAdmin();
        $this->makePending('REC-B-1', '3175010101010001');

        $this->get(route('hr.recruitment.index'))
            ->assertOk()
            ->assertSee('id="bulkActionBar"', false)
            ->assertSee('data-status="Accepted"', false)
            ->assertSee('data-status="Hold"', false)
            ->assertSee('data-status="Blacklist"', false)
            ->assertSee('id="bulkStatusModal"', false);
    }
}
