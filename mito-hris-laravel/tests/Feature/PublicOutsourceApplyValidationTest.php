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
        $response->assertSee('data-sanitize-moderate="true"', false);
        $response->assertSee('validateContractDates', false);
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

    private function bindReposExpectingCreate(): void
    {
        $this->capturedEmployee = null;

        $employeeRepo = Mockery::mock(EmployeeRepositoryInterface::class);
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
