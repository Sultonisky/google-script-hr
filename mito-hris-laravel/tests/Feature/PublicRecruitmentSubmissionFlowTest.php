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

    public function test_apply_form_exposes_mito_page_loader_for_submit(): void
    {
        $response = $this->onDomain('recruitment')
            ->withSession(['candidate_consent' => true])
            ->get(route('public.career.form'));

        $response->assertOk();
        $response->assertSee('id="publicLoader"', false);
        $response->assertSee('window.showPublicLoader', false);
        $response->assertSee('showPublicLoader(\'Mengirim data\')', false);

        $outsource = $this->onDomain('outsource')->get(route('public.outsource.apply'));
        $outsource->assertOk();
        $outsource->assertSee('showPublicLoader(\'Mengirim data\')', false);
    }

    public function test_success_page_is_terminal_without_navigation_actions(): void
    {
        $response = $this->onDomain('recruitment')
            ->withSession(['public_recruitment_submission_completed' => true])
            ->get(route('public.career.submission-success'));

        $response->assertOk();
        $response->assertDontSee('Kembali ke Halaman Info');
        $response->assertSee('history.pushState', false);
    }

    public function test_invalid_submission_does_not_create_success_state(): void
    {
        $response = $this->onDomain('recruitment')
            ->withSession(['candidate_consent' => true])
            ->from(route('public.career.form'))
            ->post(route('public.career.store'), []);

        $response->assertRedirect(route('public.career.form'));
        $response->assertSessionHasErrors(['posisi_dilamar', 'nama_lengkap', 'nik', 'email', 'nomor_telepon', 'golongan_darah', 'agreement']);
        $response->assertSessionMissing('public_recruitment_submission_completed');
    }

    public function test_consent_without_location_unlocks_the_application_form(): void
    {
        $response = $this->onDomain('recruitment')
            ->post(route('public.career.consent'), [
                'consent' => '1',
            ]);

        $response->assertRedirect(route('public.career.form'));
        $response->assertSessionHas('candidate_consent', true);

        $form = $this->onDomain('recruitment')
            ->withSession(['candidate_consent' => true])
            ->get(route('public.career.form'));

        $form->assertOk();
    }

    public function test_landing_enables_proceed_from_consent_without_waiting_for_gps(): void
    {
        $response = $this->onDomain('recruitment')->get(route('public.career.index'));

        $response->assertOk();
        $response->assertSee('id="btnProceedApply"', false);
        $response->assertSee('id="consentCheckbox"', false);
        $response->assertSee('timeout: 4000', false);
        $response->assertSee("locationField.value = 'unavailable'", false);
        $response->assertSee('syncProceedButton', false);
    }

    private function validPayload(): array
    {
        return [
            'posisi_dilamar' => 'Staff',
            'nama_lengkap' => 'Pelamar Uji',
            'nik' => '3273010101900001',
            'email' => 'pelamar.uji@example.com',
            'nomor_telepon' => '81234567890',
            'jenis_kelamin' => 'Laki-laki',
            'birth_date' => '1990-01-01',
            'usia' => 36,
            'golongan_darah' => 'O',
            'marital_status' => 'Belum Menikah',
            'alamat_domisili' => 'Jl. Merdeka No. 1',
            'provinsi' => '32',
            'kota' => '3273',
            'kota_nama' => 'KOTA BANDUNG',
            'kecamatan' => 'Coblong',
            'pendidikan_terakhir' => 'S1',
            'pengalaman_kerja' => 'Fresh Graduate',
            'status_bekerja' => 'Unemployed',
            'kesediaan_bergabung' => 'Segera',
            'ekspektasi_gaji' => '5.000.000',
            'sumber_informasi' => 'Website Perusahaan',
            'agreement' => '1',
        ];
    }
}