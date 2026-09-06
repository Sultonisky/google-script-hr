<?php

namespace Tests\Feature;

use App\DTOs\CandidateData;
use App\Services\RecruitmentService;
use Mockery;
use Tests\TestCase;

class PublicRecruitmentSubmissionFlowTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_successful_submission_redirects_to_dedicated_success_page(): void
    {
        $service = Mockery::mock(RecruitmentService::class);
        $service->shouldReceive('apply')->once()->andReturn(new CandidateData(
            recruitmentId: 'REC-TEST-001',
            fullName: 'Pelamar Uji',
        ));
        $this->app->instance(RecruitmentService::class, $service);

        $response = $this->onDomain('recruitment')
            ->withSession(['candidate_consent' => true])
            ->post(route('public.career.store'), $this->validPayload());

        $response->assertRedirect(route('public.career.submission-success'));
        $response->assertSessionHas('public_recruitment_submission_completed', true);
        $response->assertSessionMissing('candidate_consent');
    }

    public function test_success_page_requires_completed_submission_session(): void
    {
        $response = $this->onDomain('recruitment')->get(route('public.career.submission-success'));

        $response->assertRedirect(route('public.career.form'));
    }

    public function test_completed_submission_redirects_form_to_success_page(): void
    {
        $response = $this->onDomain('recruitment')
            ->withSession(['public_recruitment_submission_completed' => true])
            ->get(route('public.career.form'));

        $response->assertRedirect(route('public.career.submission-success'));
    }

    public function test_completed_submission_post_is_redirected_without_persisting_again(): void
    {
        $service = Mockery::mock(RecruitmentService::class);
        $service->shouldNotReceive('apply');
        $this->app->instance(RecruitmentService::class, $service);

        $response = $this->onDomain('recruitment')
            ->withSession(['public_recruitment_submission_completed' => true])
            ->post(route('public.career.store'), $this->validPayload());

        $response->assertRedirect(route('public.career.submission-success'));
    }

    public function test_invalid_submission_does_not_create_success_state(): void
    {
        $response = $this->onDomain('recruitment')
            ->withSession(['candidate_consent' => true])
            ->from(route('public.career.form'))
            ->post(route('public.career.store'), []);

        $response->assertRedirect(route('public.career.form'));
        $response->assertSessionHasErrors(['posisi_dilamar', 'nama_lengkap', 'nik', 'email', 'nomor_telepon', 'agreement']);
        $response->assertSessionMissing('public_recruitment_submission_completed');
    }

    private function validPayload(): array
    {
        return [
            'posisi_dilamar' => 'Staff',
            'nama_lengkap' => 'Pelamar Uji',
            'nik' => '3273010101900001',
            'email' => 'pelamar.uji@example.com',
            'nomor_telepon' => '81234567890',
            'agreement' => '1',
        ];
    }
}