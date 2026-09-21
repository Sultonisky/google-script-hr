<?php

namespace Tests\Feature;

use App\DTOs\EmployeeData;
use App\Repositories\Contracts\AuditLogRepositoryInterface;
use App\Repositories\Contracts\EmployeeRepositoryInterface;
use Mockery;
use Tests\TestCase;

class PublicOutsourceApplyValidationTest extends TestCase
{
    private ?EmployeeData $capturedEmployee = null;

    protected function setUp(): void
    {
        parent::setUp();
        \Illuminate\Support\Facades\Cache::flush();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_form_drops_probation_and_keeps_flexible_office_email(): void
    {
        $response = $this->onDomain('outsource')->get(route('public.outsource.apply'));

        $response->assertOk();
        $response->assertDontSee('value="Probation"', false);
        $response->assertDontSee('value="Contract"', false);
        $response->assertDontSee('value="Permanent"', false);
        $response->assertSee('value="Outsource"', false);
        $response->assertSee('Status dikunci Outsource karena pendaftaran melalui portal outsource.');
        $response->assertSee('name="email_kantor"', false);
        $response->assertSee('Gunakan email kerja yang aktif. Email MITO tidak wajib.');
        $response->assertDontSee('placeholder="nama@mitogroup.co.id"', false);
        $response->assertSee('data-sanitize-name="true"', false);
        $response->assertSee('data-sanitize-digits="true"', false);
        $response->assertSee('data-sanitize-npwp="true"', false);
        $response->assertSee('sanitizeNpwpField', false);
        $response->assertSee('pattern="[0-9]{15,16}"', false);
        $response->assertSee('maxlength="16"', false);
        $response->assertSee('data-sanitize-moderate="true"', false);
        $response->assertSee('validateContractDates', false);
    }

    public function test_form_exposes_strict_duplicate_nik_ui_lock(): void
    {
        $response = $this->onDomain('outsource')->get(route('public.outsource.apply'));

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
        $response->assertSee(route('public.outsource.nik-check'), false);
        $response->assertDontSee('processNIK(', false);
    }

    public function test_form_locks_from_server_duplicate_error_without_autofill(): void
    {
        $response = $this->onDomain('outsource')
            ->withSession([
                'error' => 'NIK ini sudah terdaftar. Setiap NIK hanya dapat digunakan untuk satu kali pendaftaran.',
            ])
            ->get(route('public.outsource.apply'));

        $response->assertOk();
        $response->assertSee('setNikBlocked(true, serverError)', false);
        $response->assertSee('showNikChecking()', false);
        $response->assertSee('scheduleNikAvailabilityCheck', false);
    }

    public function test_nik_check_endpoint_reports_duplicate(): void
    {
        $employeeRepo = Mockery::mock(EmployeeRepositoryInterface::class);
        $employeeRepo->shouldReceive('findByNik')
            ->once()
            ->with('3273010101900001')
            ->andReturn(new EmployeeData(
                employeeId: '2026011501',
                fullName: 'Karyawan Lama',
                nikNpwp: '3273010101900001',
            ));
        $this->app->instance(EmployeeRepositoryInterface::class, $employeeRepo);

        $response = $this->onDomain('outsource')
            ->postJson(route('public.outsource.nik-check'), [
                'nik' => '3273010101900001',
            ]);

        $response->assertOk()
            ->assertJsonPath('available', false)
            ->assertJsonPath('message', 'NIK ini sudah terdaftar. Setiap NIK hanya dapat digunakan untuk satu kali pendaftaran.');
    }

    public function test_existing_employee_nik_cannot_apply_again(): void
    {
        $employeeRepo = Mockery::mock(EmployeeRepositoryInterface::class);
        $employeeRepo->shouldReceive('getAll')->andReturn(collect([
            new EmployeeData(
                employeeId: '2026011501',
                fullName: 'Karyawan Lama',
                nikNpwp: '3273010101900001',
            ),
        ]));
        $employeeRepo->shouldNotReceive('create');

        $auditRepo = Mockery::mock(AuditLogRepositoryInterface::class);
        $auditRepo->shouldNotReceive('log');

        $this->app->instance(EmployeeRepositoryInterface::class, $employeeRepo);
        $this->app->instance(AuditLogRepositoryInterface::class, $auditRepo);

        $response = $this->onDomain('outsource')
            ->from(route('public.outsource.apply'))
            ->post(route('public.outsource.store'), $this->validPayload());

        $response->assertRedirect(route('public.outsource.apply'));
        $response->assertSessionHas('error', 'NIK ini sudah terdaftar. Setiap NIK hanya dapat digunakan untuk satu kali pendaftaran.');
        $response->assertSessionMissing('public_outsource_submission_completed');
    }

    public function test_invalid_submission_shows_errors_and_keeps_the_form_retryable(): void
    {
        $this->bindReposExpectingNoCreate();

        $response = $this->postInvalid([
            'nama_lengkap' => 'Budi123',
            'email_pribadi' => 'bukan-email',
        ]);

        $response->assertRedirect(route('public.outsource.apply'));
        $response->assertSessionHasErrors(['nama_lengkap', 'email_pribadi']);

        $form = $this->onDomain('outsource')->get(route('public.outsource.apply'));
        $form->assertOk();
        $form->assertSee('Form belum bisa dikirim.', false);
        $form->assertSee('sessionStorage.removeItem(SUBMISSION_FLAG)', false);
        $form->assertSee('prepareFormForSubmit', false);
        $form->assertSee('name="nama_bank"', false);
    }

    public function test_sheet_write_failure_returns_to_form_with_error(): void
    {
        $employeeRepo = Mockery::mock(EmployeeRepositoryInterface::class);
        $employeeRepo->shouldReceive('getAll')->andReturn(collect());
        $employeeRepo->shouldReceive('create')->once()->andThrow(new \RuntimeException('Google Sheets timeout'));

        $auditRepo = Mockery::mock(AuditLogRepositoryInterface::class);
        $auditRepo->shouldNotReceive('log');

        $this->app->instance(EmployeeRepositoryInterface::class, $employeeRepo);
        $this->app->instance(AuditLogRepositoryInterface::class, $auditRepo);

        $response = $this->onDomain('outsource')
            ->from(route('public.outsource.apply'))
            ->post(route('public.outsource.store'), $this->validPayload());

        $response->assertRedirect(route('public.outsource.apply'));
        $response->assertSessionHas('error');
        $this->assertStringContainsString('Google Sheets timeout', (string) session('error'));
        $response->assertSessionMissing('public_outsource_submission_completed');
    }

    public function test_audit_failure_does_not_block_success_page(): void
    {
        $this->capturedEmployee = null;

        $employeeRepo = Mockery::mock(EmployeeRepositoryInterface::class);
        $employeeRepo->shouldReceive('getAll')->andReturn(collect());
        $employeeRepo->shouldReceive('create')
            ->once()
            ->andReturnUsing(function (EmployeeData $data) {
                $this->capturedEmployee = $data;

                return $data;
            });

        $auditRepo = Mockery::mock(AuditLogRepositoryInterface::class);
        $auditRepo->shouldReceive('log')->once()->andThrow(new \RuntimeException('Audit sheet unavailable'));

        $this->app->instance(EmployeeRepositoryInterface::class, $employeeRepo);
        $this->app->instance(AuditLogRepositoryInterface::class, $auditRepo);

        $response = $this->onDomain('outsource')
            ->from(route('public.outsource.apply'))
            ->post(route('public.outsource.store'), $this->validPayload());

        $response->assertRedirect(route('public.outsource.success'));
        $response->assertSessionHas('public_outsource_submission_completed', true);
        $this->assertNotNull($this->capturedEmployee);
    }

    public function test_accepts_non_mito_office_email_and_persists(): void
    {
        $this->bindReposExpectingCreate();

        $response = $this->onDomain('outsource')
            ->from(route('public.outsource.apply'))
            ->post(route('public.outsource.store'), $this->validPayload([
                'email_kantor' => 'Vendor.Staff@gmail.com',
            ]));

        $response->assertRedirect(route('public.outsource.success'));
        $response->assertSessionHas('public_outsource_submission_completed', true);
        $this->assertSame('vendor.staff@gmail.com', $this->capturedEmployee?->workingEmail);
        $this->assertSame('Outsource', $this->capturedEmployee?->statusEmployee);
        $this->assertSame('Staff', $this->capturedEmployee?->jobPosition);
        $this->assertSame('Staff (Jakarta)', $this->capturedEmployee?->jobPositionLocation);
        $this->assertMatchesRegularExpression('/^20260115\d{2,}$/', (string) $this->capturedEmployee?->employeeId);
        $this->assertStringStartsNotWith('EMP-OS-', (string) $this->capturedEmployee?->employeeId);
    }

    public function test_job_position_location_combines_position_and_work_location(): void
    {
        $this->bindReposExpectingCreate();

        $response = $this->onDomain('outsource')
            ->from(route('public.outsource.apply'))
            ->post(route('public.outsource.store'), $this->validPayload([
                'posisi_jabatan' => 'Engineering Manager',
                'job_level' => 'Supervisor',
                'lokasi_kerja' => 'Jakarta',
            ]));

        $response->assertRedirect(route('public.outsource.success'));
        $this->assertSame('Engineering Manager', $this->capturedEmployee?->jobPosition);
        $this->assertSame('Engineering Manager (Jakarta)', $this->capturedEmployee?->jobPositionLocation);
        $this->assertSame('Jakarta', $this->capturedEmployee?->lokasiKerja);
        $this->assertSame('Supervisor', $this->capturedEmployee?->jobLevel);
    }

    public function test_employee_id_uses_join_date_and_next_sequence(): void
    {
        \Illuminate\Support\Facades\Cache::flush();

        $this->bindReposExpectingCreate(collect([
            new EmployeeData(employeeId: '2026011503', fullName: 'Karyawan Lama'),
            new EmployeeData(employeeId: 'EMP-OS-2026-1111', fullName: 'Prefix Lama'),
        ]));

        $response = $this->onDomain('outsource')
            ->from(route('public.outsource.apply'))
            ->post(route('public.outsource.store'), $this->validPayload([
                'tanggal_masuk' => '2026-01-15',
            ]));

        $response->assertRedirect(route('public.outsource.success'));
        $this->assertSame('2026011504', $this->capturedEmployee?->employeeId);
    }

    public function test_rejects_name_fields_with_digits_or_symbols(): void
    {
        $this->bindReposExpectingNoCreate();

        $response = $this->postInvalid([
            'nama_lengkap' => 'Budi123',
            'tempat_lahir' => 'Jakarta-Barat',
            'atasan_langsung' => 'Andi@HR',
            'nama_pemilik_rekening' => 'Budi_Santoso',
        ]);

        $response->assertRedirect(route('public.outsource.apply'));
        $response->assertSessionHasErrors([
            'nama_lengkap',
            'tempat_lahir',
            'atasan_langsung',
            'nama_pemilik_rekening',
        ]);
        $response->assertSessionMissing('public_outsource_submission_completed');
    }

    public function test_strips_digit_noise_from_numeric_fields(): void
    {
        $this->bindReposExpectingCreate();

        $response = $this->onDomain('outsource')
            ->from(route('public.outsource.apply'))
            ->post(route('public.outsource.store'), $this->validPayload([
                'nik' => '3273-0101-0190-0001',
                'nomor_telepon' => '812-3456-7890',
                'npwp' => '01.123.456.7-123.000',
                'nomor_rekening' => '1234 5678 90',
                'bpjs_ketenagakerjaan' => '1234-5678-9012',
                'bpjs_kesehatan' => '1234-5678-901234',
            ]));

        $response->assertRedirect(route('public.outsource.success'));
        $this->assertSame('3273010101900001', $this->capturedEmployee?->nikNpwp);
        $this->assertSame('+6281234567890', $this->capturedEmployee?->mobilePhone);
        $this->assertSame('011234567123000', $this->capturedEmployee?->npwp);
        $this->assertSame('1234567890', $this->capturedEmployee?->bankAccount);
        $this->assertSame('123456789012', $this->capturedEmployee?->bpjsKetenagakerjaan);
        $this->assertSame('12345678901234', $this->capturedEmployee?->bpjsKesehatan);
    }

    public function test_rejects_npwp_outside_fifteen_or_sixteen_digits(): void
    {
        $this->bindReposExpectingNoCreate();

        $tooLong = $this->postInvalid([
            'npwp' => '12345678910111213',
        ]);
        $tooLong->assertRedirect(route('public.outsource.apply'));
        $tooLong->assertSessionHasErrors(['npwp']);

        $tooShort = $this->postInvalid([
            'npwp' => '12345678901234',
        ]);
        $tooShort->assertRedirect(route('public.outsource.apply'));
        $tooShort->assertSessionHasErrors(['npwp']);
    }

    public function test_accepts_sixteen_digit_npwp(): void
    {
        $this->bindReposExpectingCreate();

        $response = $this->onDomain('outsource')
            ->from(route('public.outsource.apply'))
            ->post(route('public.outsource.store'), $this->validPayload([
                'npwp' => '1234567891011121',
            ]));

        $response->assertRedirect(route('public.outsource.success'));
        $this->assertSame('1234567891011121', $this->capturedEmployee?->npwp);
    }

    public function test_rejects_moderate_fields_with_disallowed_characters(): void
    {
        $this->bindReposExpectingNoCreate();

        $response = $this->postInvalid([
            'vendor_outsource' => 'PT Vendor<script>',
            'divisi' => 'IT @ HQ',
            'cost_center' => 'CC#1',
            'lokasi_kerja' => 'Jakarta!',
        ]);

        $response->assertRedirect(route('public.outsource.apply'));
        $response->assertSessionHasErrors([
            'vendor_outsource',
            'divisi',
            'cost_center',
            'lokasi_kerja',
        ]);
    }

    public function test_forces_employee_status_to_outsource_when_tampered(): void
    {
        $this->bindReposExpectingCreate();

        $response = $this->onDomain('outsource')
            ->from(route('public.outsource.apply'))
            ->post(route('public.outsource.store'), $this->validPayload([
                'status_karyawan' => 'Contract',
            ]));

        $response->assertRedirect(route('public.outsource.success'));
        $this->assertSame('Outsource', $this->capturedEmployee?->statusEmployee);
    }

    public function test_rejects_contract_end_on_or_before_join_date(): void
    {
        $this->bindReposExpectingNoCreate();

        $response = $this->postInvalid([
            'tanggal_masuk' => '2026-01-15',
            'tanggal_berakhir_kontrak' => '2026-01-15',
        ]);

        $response->assertRedirect(route('public.outsource.apply'));
        $response->assertSessionHasErrors(['tanggal_berakhir_kontrak']);
    }

    public function test_rejects_applicant_younger_than_seventeen(): void
    {
        $this->bindReposExpectingNoCreate();

        $response = $this->postInvalid([
            'birth_date' => now()->timezone('Asia/Jakarta')->subYears(16)->toDateString(),
        ]);

        $response->assertRedirect(route('public.outsource.apply'));
        $response->assertSessionHasErrors(['birth_date']);
    }

    public function test_rejects_invalid_email_formats(): void
    {
        $this->bindReposExpectingNoCreate();

        $response = $this->postInvalid([
            'email_pribadi' => 'bukan-email',
            'email_kantor' => 'juga-bukan',
        ]);

        $response->assertRedirect(route('public.outsource.apply'));
        $response->assertSessionHasErrors(['email_pribadi', 'email_kantor']);
    }

    private function postInvalid(array $overrides)
    {
        return $this->onDomain('outsource')
            ->from(route('public.outsource.apply'))
            ->post(route('public.outsource.store'), $this->validPayload($overrides));
    }

    private function bindReposExpectingCreate($existingEmployees = null): void
    {
        $this->capturedEmployee = null;

        $employeeRepo = Mockery::mock(EmployeeRepositoryInterface::class);
        $employeeRepo->shouldReceive('getAll')->andReturn($existingEmployees ?? collect());
        $employeeRepo->shouldReceive('findByNik')->andReturn(null);
        $employeeRepo->shouldReceive('create')
            ->once()
            ->withArgs(function (EmployeeData $data) {
                $this->capturedEmployee = $data;

                return true;
            })
            ->andReturnUsing(fn (EmployeeData $data) => $data);

        $auditRepo = Mockery::mock(AuditLogRepositoryInterface::class);
        $auditRepo->shouldReceive('log')->once()->andReturn(true);

        $this->app->instance(EmployeeRepositoryInterface::class, $employeeRepo);
        $this->app->instance(AuditLogRepositoryInterface::class, $auditRepo);
    }

    private function bindReposExpectingNoCreate(): void
    {
        $employeeRepo = Mockery::mock(EmployeeRepositoryInterface::class);
        $employeeRepo->shouldNotReceive('create');

        $auditRepo = Mockery::mock(AuditLogRepositoryInterface::class);
        $auditRepo->shouldNotReceive('log');

        $this->app->instance(EmployeeRepositoryInterface::class, $employeeRepo);
        $this->app->instance(AuditLogRepositoryInterface::class, $auditRepo);
    }

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'nama_lengkap' => 'Budi Santoso',
            'nik' => '3273010101900001',
            'birth_date' => '1990-01-01',
            'tempat_lahir' => 'Jakarta',
            'usia' => 36,
            'jenis_kelamin' => 'Laki-laki',
            'agama' => 'Islam',
            'golongan_darah' => 'O',
            'status_pernikahan' => 'Belum Menikah',
            'email_pribadi' => 'budi.pribadi@example.com',
            'email_kantor' => 'budi.kantor@vendor.co.id',
            'nomor_telepon' => '81234567890',
            'provinsi' => '32',
            'kota' => '3273',
            'kota_nama' => 'KOTA BANDUNG',
            'kecamatan' => 'Coblong',
            'alamat_ktp' => 'Jl. Merdeka No. 1',
            'alamat_domisili' => 'Jl. Sudirman No. 2',
            'cabang_penempatan' => 'PT Mahakarya Sukses Indonesia',
            'vendor_outsource' => 'PT Karya Mitra Sejahtera',
            'divisi' => 'Human Resources',
            'departemen' => 'Recruitment',
            'area_kerja' => 'Head Office',
            'cost_center' => 'HR-01',
            'lokasi_kerja' => 'Jakarta',
            'posisi_jabatan' => 'Staff',
            'job_level' => 'Associate',
            'status_karyawan' => 'Outsource',
            'tanggal_masuk' => '2026-01-15',
            'tanggal_berakhir_kontrak' => '2026-12-31',
            'atasan_langsung' => 'Siti Rahma',
            'atasan_tidak_langsung' => 'Andi Wijaya',
            'nomor_rekening' => '1234567890',
            'nama_pemilik_rekening' => 'Budi Santoso',
            'npwp' => '011234567123000',
            'status_ptkp' => 'TK/0',
            'bpjs_ketenagakerjaan' => '123456789012',
            'bpjs_kesehatan' => '12345678901234',
            'agreement' => '1',
        ], $overrides);
    }
}
