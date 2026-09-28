<?php

namespace Tests\Feature;

use App\DTOs\CandidateData;
use App\Repositories\Contracts\AuditLogRepositoryInterface;
use App\Repositories\Contracts\CandidateRepositoryInterface;
use App\Repositories\Contracts\EmployeeRepositoryInterface;
use App\Services\EmployeeIdGenerator;
use App\Services\Google\GoogleDriveService;
use App\Services\RecruitmentService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class PublicRecruitmentNikUniquenessTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        Event::fake();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_existing_pending_nik_cannot_apply_again(): void
    {
        $repo = $this->mockRepo();
        $repo->shouldReceive('findByNik')->andReturn(new CandidateData(
            recruitmentId: 'REC-EXISTING-001',
            nik: '3174100205050003',
            status: 'Pending',
        ));
        $repo->shouldNotReceive('create');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('NIK ini sudah terdaftar');

        $this->makeService($repo)->apply($this->payload());
    }

    public function test_blacklisted_nik_is_rejected(): void
    {
        $repo = $this->mockRepo();
        $repo->shouldReceive('findByNik')->andReturn(new CandidateData(
            recruitmentId: 'REC-EXISTING-002',
            nik: '3174100205050003',
            status: 'Blacklist',
        ));
        $repo->shouldNotReceive('create');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('daftar hitam');

        $this->makeService($repo)->apply($this->payload());
    }

    public function test_parallel_submit_with_same_nik_is_blocked_by_lock(): void
    {
        $repo = $this->mockRepo();
        $repo->shouldReceive('findByNik')->andReturn(null);
        $repo->shouldReceive('create')->once()->andReturnUsing(function (CandidateData $data) {
            $data->recruitmentId = 'REC-LOCK-001';
            return $data;
        });

        $service = $this->makeService($repo);
        $first = $service->apply($this->payload());
        $this->assertSame('REC-LOCK-001', $first->recruitmentId);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('sudah terdaftar atau sedang diproses');
        $service->apply($this->payload());
    }

    public function test_successful_apply_creates_once_when_nik_is_new(): void
    {
        $repo = $this->mockRepo();
        $repo->shouldReceive('findByNik')->andReturn(null);
        $repo->shouldReceive('create')->once()->andReturnUsing(function (CandidateData $data) {
            $data->recruitmentId = 'REC-NEW-001';
            return $data;
        });

        $created = $this->makeService($repo)->apply($this->payload());
        $this->assertSame('REC-NEW-001', $created->recruitmentId);
        $this->assertSame('3174100205050003', $created->nik);
    }

    public function test_nik_check_endpoint_reports_duplicate(): void
    {
        $service = Mockery::mock(RecruitmentService::class);
        $service->shouldReceive('nikRegistrationStatus')
            ->once()
            ->with('3174100205050003')
            ->andReturn([
                'available' => false,
                'message' => 'NIK ini sudah terdaftar. Setiap NIK hanya dapat digunakan untuk satu kali pendaftaran.',
            ]);
        $this->app->instance(RecruitmentService::class, $service);

        $response = $this->onDomain('recruitment')
            ->withSession(['candidate_consent' => true])
            ->postJson(route('public.career.nik-check'), [
                'nik' => '3174100205050003',
            ]);

        $response->assertOk()
            ->assertJsonPath('available', false)
            ->assertJsonPath('message', 'NIK ini sudah terdaftar. Setiap NIK hanya dapat digunakan untuk satu kali pendaftaran.');
    }

    public function test_apply_form_exposes_strict_duplicate_nik_ui_lock(): void
    {
        $response = $this->onDomain('recruitment')
            ->withSession(['candidate_consent' => true])
            ->get(route('public.career.form'));

        $response->assertOk();
        $response->assertSee('id="nikDuplicateWarning"', false);
        $response->assertSee('id="nikLockBanner"', false);
        $response->assertSee('lockFormExceptNik', false);
        $response->assertSee('clearNikAutofill', false);
        $response->assertSee('applyNikAutofill', false);
        $response->assertSee('nik-duplicate-locked', false);
        $response->assertSee('submitBtn.disabled = nikIsBlocked || nikCheckPending', false);
        $response->assertSee('if (result.data && result.data.available === false)', false);
        $response->assertSee('Ubah NIK di kolom identitas untuk membuka kembali formulir', false);
        $response->assertDontSee('processNIK(nik)', false);
    }

    public function test_apply_form_locks_from_server_duplicate_error_without_autofill(): void
    {
        $response = $this->onDomain('recruitment')
            ->withSession([
                'candidate_consent' => true,
                'error' => 'NIK ini sudah terdaftar. Setiap NIK hanya dapat digunakan untuk satu kali pendaftaran.',
            ])
            ->get(route('public.career.form'));

        $response->assertOk();
        $response->assertSee('setNikBlocked(true, serverError)', false);
        $response->assertSee('showNikChecking()', false);
        $response->assertSee('scheduleNikAvailabilityCheck', false);
    }

    private function makeService(CandidateRepositoryInterface $candidateRepo): RecruitmentService
    {
        $employeeRepo = Mockery::mock(EmployeeRepositoryInterface::class);
        $auditRepo = Mockery::mock(AuditLogRepositoryInterface::class);
        $auditRepo->shouldReceive('log')->andReturn(true);
        $drive = Mockery::mock(GoogleDriveService::class);
        $idGenerator = Mockery::mock(EmployeeIdGenerator::class);

        return new RecruitmentService(
            $candidateRepo,
            $employeeRepo,
            $drive,
            $auditRepo,
            $idGenerator,
            $this->app->make(\App\Services\SkNumberService::class)
        );
    }

    private function mockRepo(): CandidateRepositoryInterface
    {
        return Mockery::mock(CandidateRepositoryInterface::class);
    }

    private function payload(): array
    {
        return [
            'nama_lengkap' => 'Andi Wijaya',
            'nik' => '3174100205050003',
            'email' => 'andi.wijaya@example.com',
            'nomor_telepon' => '875258233',
            'posisi_dilamar' => 'Graphic Designer',
            'agreement' => '1',
        ];
    }
}
