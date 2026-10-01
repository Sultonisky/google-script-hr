<?php

namespace Tests\Feature;

use App\DTOs\CandidateData;
use App\Enums\CandidateStatus;
use App\Repositories\Contracts\AuditLogRepositoryInterface;
use App\Repositories\Contracts\CandidateRepositoryInterface;
use App\Repositories\Contracts\EmployeeRepositoryInterface;
use App\Repositories\Database\CandidateDatabaseRepository;
use App\Repositories\Database\EmployeeDatabaseRepository;
use App\Services\RecruitmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Session;
use Mockery;
use Tests\TestCase;

/**
 * Tabel halaman Hold: kolom Tgl Hold + Diproses Oleh (setara halaman Blacklist),
 * kolom Aksi Ubah Status (setara halaman Accepted), tanpa kolom Follow Up.
 */
class HoldPageTableTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app->bind(CandidateRepositoryInterface::class, CandidateDatabaseRepository::class);
        $this->app->bind(EmployeeRepositoryInterface::class, EmployeeDatabaseRepository::class);

        $audit = Mockery::mock(AuditLogRepositoryInterface::class);
        $audit->shouldReceive('log')->andReturn(true)->byDefault();
        $audit->shouldReceive('getLogs')->andReturn(collect())->byDefault();
        $this->app->instance(AuditLogRepositoryInterface::class, $audit);

        Session::put('hr_user', [
            'email'       => 'admin@mito.id',
            'fullName'    => 'HR Admin',
            'role'        => 'Super Admin',
            'permissions' => ['*'],
        ]);
    }

    public function test_hold_table_shows_hold_date_and_processor_without_follow_up(): void
    {
        Event::fake();

        $this->app->make(CandidateRepositoryInterface::class)->create(new CandidateData(
            recruitmentId: 'REC-HOLD-0001',
            fullName: 'Andi Wijaya',
            nik: '3175010101010002',
            email: 'andi@example.com',
            phone: '08123456789',
            positionApplied: 'Staff IT',
            status: CandidateStatus::NEW->value,
        ));
        $this->app->make(RecruitmentService::class)
            ->holdCandidate('REC-HOLD-0001', 'Menunggu budget', null, null, 'Rina HR');

        $processedDate = $this->app->make(CandidateRepositoryInterface::class)
            ->findById('REC-HOLD-0001')?->processedDate;
        $this->assertNotEmpty($processedDate);

        $this->get(route('hr.recruitment.hold'))
            ->assertOk()
            ->assertSee('<th>Tgl Hold</th>', false)
            ->assertSee('<th>Diproses Oleh</th>', false)
            ->assertDontSee('<th>Follow Up</th>', false)
            ->assertSee('Menunggu budget')
            ->assertSee($processedDate)
            ->assertSee('Rina HR')
            ->assertSee('<th>Aksi</th>', false)
            ->assertSee('data-action="open-move-status-modal" data-recruitment-id="REC-HOLD-0001" data-from-status="Hold"', false);
    }
}
