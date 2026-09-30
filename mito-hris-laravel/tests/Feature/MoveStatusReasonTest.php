<?php

namespace Tests\Feature;

use App\DTOs\CandidateData;
use App\Repositories\Contracts\AuditLogRepositoryInterface;
use App\Repositories\Contracts\CandidateRepositoryInterface;
use Illuminate\Support\Facades\Session;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Modal "Ubah Status" di halaman Accepted → POST /hr/recruitment/{id}/move-status.
 * Alasan wajib (maks 500 karakter) untuk Hold/Blacklist, sama seperti
 * HoldCandidateRequest / BlacklistCandidateRequest.
 */
class MoveStatusReasonTest extends TestCase
{
    private const RID = 'REC-20260801-000001';

    private function loginAsHrAdmin(): void
    {
        Session::put('hr_user', [
            'email'       => 'admin@mito.id',
            'fullName'    => 'HR Admin',
            'role'        => 'Super Admin',
            'permissions' => ['*'],
        ]);
    }

    private function bindRepos(CandidateRepositoryInterface $candidateRepo): void
    {
        $audit = Mockery::mock(AuditLogRepositoryInterface::class);
        $audit->shouldReceive('log')->andReturn(true);
        $audit->shouldReceive('getLogs')->andReturn(collect());

        $this->app->instance(CandidateRepositoryInterface::class, $candidateRepo);
        $this->app->instance(AuditLogRepositoryInterface::class, $audit);
    }

    private function acceptedCandidate(): CandidateData
    {
        return new CandidateData(
            recruitmentId: self::RID,
            fullName: 'Andi Saputra',
            email: 'andi@gmail.com',
            phone: '081234567890',
            nik: '3201010101900001',
            positionApplied: 'HR Staff',
            status: 'Accepted',
        );
    }

    private function move(string $to, ?string $reason): \Illuminate\Testing\TestResponse
    {
        return $this->postJson('/hr/recruitment/' . self::RID . '/move-status', array_filter([
            'from_status' => 'Accepted',
            'to_status'   => $to,
            'reason'      => $reason,
        ], fn ($v) => $v !== null));
    }

    #[Test]
    public function hold_without_reason_is_rejected(): void
    {
        $this->loginAsHrAdmin();
        $repo = Mockery::mock(CandidateRepositoryInterface::class);
        $repo->shouldNotReceive('moveToHold');
        $this->bindRepos($repo);

        $this->move('Hold', null)
            ->assertStatus(422)
            ->assertJson(['success' => false, 'message' => 'Alasan hold wajib diisi.']);
    }

    #[Test]
    public function blacklist_with_blank_reason_is_rejected(): void
    {
        $this->loginAsHrAdmin();
        $repo = Mockery::mock(CandidateRepositoryInterface::class);
        $repo->shouldNotReceive('moveToBlacklist');
        $this->bindRepos($repo);

        $this->move('Blacklist', '    ')
            ->assertStatus(422)
            ->assertJson(['success' => false, 'message' => 'Alasan blacklist wajib diisi.']);
    }

    #[Test]
    public function reason_longer_than_500_characters_is_rejected(): void
    {
        $this->loginAsHrAdmin();
        $repo = Mockery::mock(CandidateRepositoryInterface::class);
        $repo->shouldNotReceive('moveToHold');
        $this->bindRepos($repo);

        $this->move('Hold', str_repeat('a', 501))
            ->assertStatus(422)
            ->assertJson(['success' => false, 'message' => 'Alasan maksimal 500 karakter.']);
    }

    #[Test]
    public function hold_with_reason_saves_trimmed_reason_without_touching_hr_notes(): void
    {
        $this->loginAsHrAdmin();
        $captured = null;
        $repo = Mockery::mock(CandidateRepositoryInterface::class);
        $repo->shouldReceive('findById')->andReturn($this->acceptedCandidate());
        $repo->shouldReceive('moveToHold')->once()
            ->withArgs(function (string $id, array $extra) use (&$captured) {
                $captured = $extra;
                return $id === self::RID;
            })
            ->andReturn(true);
        $this->bindRepos($repo);

        $this->move('Hold', '  Posisi ditunda  ')
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertSame('Posisi ditunda', $captured['Hold Reason']);
        $this->assertArrayNotHasKey('HR Notes', $captured);
    }

    #[Test]
    public function hold_to_accepted_does_not_require_reason(): void
    {
        $this->loginAsHrAdmin();
        $repo = Mockery::mock(CandidateRepositoryInterface::class);
        $repo->shouldReceive('findById')->andReturn($this->acceptedCandidate());
        $repo->shouldReceive('moveToAccepted')->once()->andReturn(true);
        $this->bindRepos($repo);

        $this->postJson('/hr/recruitment/' . self::RID . '/move-status', [
            'from_status' => 'Hold',
            'to_status'   => 'Accepted',
        ])->assertOk()->assertJson(['success' => true]);
    }

    #[Test]
    public function blacklist_with_reason_succeeds(): void
    {
        $this->loginAsHrAdmin();
        $captured = null;
        $repo = Mockery::mock(CandidateRepositoryInterface::class);
        $repo->shouldReceive('findById')->andReturn($this->acceptedCandidate());
        $repo->shouldReceive('moveToBlacklist')->once()
            ->withArgs(function (string $id, array $extra) use (&$captured) {
                $captured = $extra;
                return $id === self::RID;
            })
            ->andReturn(true);
        $this->bindRepos($repo);

        $this->move('Blacklist', 'Pemalsuan dokumen')
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertSame('Pemalsuan dokumen', $captured['Blacklist Reason']);
        $this->assertArrayNotHasKey('HR Notes', $captured);
    }
}
