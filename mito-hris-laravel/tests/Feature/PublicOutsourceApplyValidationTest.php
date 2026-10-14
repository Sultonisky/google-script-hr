<?php

namespace Tests\Feature;

use App\DTOs\OutsourceEmployeeData;
use App\Repositories\Contracts\AuditLogRepositoryInterface;
use App\Repositories\Contracts\OutsourceEmployeeRepositoryInterface;
use App\Repositories\Local\ArrayOutsourceEmployeeRepository;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Tests\TestCase;

class PublicOutsourceApplyValidationTest extends TestCase
{
    private ArrayOutsourceEmployeeRepository $outsourceRepo;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();

        $this->outsourceRepo = $this->app->make(ArrayOutsourceEmployeeRepository::class);
        $this->app->instance(OutsourceEmployeeRepositoryInterface::class, $this->outsourceRepo);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_form_collects_outsource_columns_without_hr_only_fields(): void
    {
        $response = $this->onDomain('outsource')->get(route('public.outsource.apply'));

        $response->assertOk();
        foreach ([
            'full_name', 'citizen_id_address', 'birth_date', 'birth_place', 'last_education', 'whatsapp_number',
            'email', 'vendor', 'job_title', 'work_location', 'work_city', 'cost_center', 'entity',
            'mito_join_date', 'contract_start_date', 'contract_end_date',
        ] as $field) {
            $response->assertSee('name="' . $field . '"', false);
        }
        foreach (['bank_account', 'payroll_scheme', 'umk_amount', 'basic_salary', 'incentive_amount', 'remarks', 'nik', 'npwp', 'bpjs_kesehatan'] as $field) {
            $response->assertDontSee('name="' . $field . '"', false);
        }
        $response->assertSee('value="Damarindo"', false);
        $response->assertSee('value="StaffInc"', false);
        $response->assertSee('validateContractDates', false);
        $response->assertSee('0 dari 14 data telah lengkap', false);
        $response->assertSee("phoneEl.addEventListener('input', scheduleContactCheck)", false);
        $response->assertSee("emailEl.addEventListener('input', scheduleContactCheck)", false);
        foreach (['mito_join_date', 'contract_start_date', 'contract_end_date'] as $field) {
            $response->assertDontSee('id="' . $field . '" name="' . $field . '" value="" required', false);
        }
        $response->assertSee(json_encode(route('public.outsource.contact-check')), false);
    }

    public function test_form_locks_from_server_duplicate_contact_error(): void
    {
        $response = $this->onDomain('outsource')
            ->withSession([
                'error' => 'Nomor WhatsApp atau email ini sudah terdaftar.',
                '_old_input' => ['whatsapp_number' => '81234567890', 'email' => 'budi@example.com'],
            ])
            ->get(route('public.outsource.apply'));

        $response->assertOk();
        $response->assertSee('scheduleContactCheck()', false);
        $response->assertSee('id="contactCheckStatus"', false);
        $response->assertSee('id="whatsappDuplicateFeedback"', false);
        $response->assertSee('id="emailDuplicateFeedback"', false);
        $response->assertDontSee('id="contactLockBanner"', false);
    }

    public function test_contact_check_reports_duplicate_phone_in_any_format(): void
    {
        $this->seedExisting();

        $this->onDomain('outsource')
            ->postJson(route('public.outsource.contact-check'), ['whatsapp_number' => '0812-3456-7890'])
            ->assertOk()
            ->assertJsonPath('available', false)
            ->assertJsonPath('duplicate_fields', ['whatsapp_number']);

        $this->onDomain('outsource')
            ->postJson(route('public.outsource.contact-check'), ['email' => 'LAMA@example.com'])
            ->assertOk()
            ->assertJsonPath('available', false)
            ->assertJsonPath('duplicate_fields', ['email']);

        $this->onDomain('outsource')
            ->postJson(route('public.outsource.contact-check'), [
                'whatsapp_number' => '0812-3456-7890',
                'email' => 'LAMA@example.com',
            ])
            ->assertOk()
            ->assertJsonPath('duplicate_fields', ['whatsapp_number', 'email']);

        $this->onDomain('outsource')
            ->postJson(route('public.outsource.contact-check'), ['whatsapp_number' => '81111111111', 'email' => 'baru@example.com'])
            ->assertOk()
            ->assertJsonPath('available', true)
            ->assertJsonPath('duplicate_fields', []);
    }

    public function test_valid_submission_creates_outsource_record(): void
    {
        $this->expectAudit();

        $response = $this->submit();

        $response->assertRedirect(route('public.outsource.success'));
        $response->assertSessionHas('public_outsource_submission_completed', true);

        $saved = $this->outsourceRepo->getAll()->first();
        $this->assertNotNull($saved);
        $this->assertMatchesRegularExpression('/^DM\d{8}$/', (string) $saved->outsourceId);
        $this->assertSame('Budi Santoso', $saved->fullName);
        $this->assertSame('+6281234567890', $saved->whatsappNumber);
        $this->assertSame('budi@example.com', $saved->email);
        $this->assertSame('Damarindo', $saved->vendor);
        $this->assertSame('PT. Mahakarya Sukses Indonesia', $saved->entity);
        $this->assertSame('2026-01-15', $saved->mitoJoinDate);
        $this->assertSame('2026-01-15', $saved->contractStartDate);
        $this->assertSame('2026-12-31', $saved->contractEndDate);
        $this->assertNull($saved->bankAccount);
        $this->assertNull($saved->payrollScheme);
        $this->assertNull($saved->umkAmount);
        $this->assertSame('Portal Outsource', $saved->createdBy);
        $this->assertNull($saved->basicSalary);
        $this->assertNull($saved->incentiveAmount);
        $this->assertNull($saved->remarks);
    }

    public function test_hr_only_fields_are_ignored_when_tampered(): void
    {
        $this->expectAudit();

        $this->submit([
            'basic_salary' => '9999999',
            'incentive_amount' => '9999999',
            'remarks' => 'diisi pelamar',
            'bank_account' => '1234567890',
            'payroll_scheme' => '70/30',
            'umk_amount' => '5396761',
        ])->assertRedirect(route('public.outsource.success'));

        $saved = $this->outsourceRepo->getAll()->first();
        $this->assertNull($saved->basicSalary);
        $this->assertNull($saved->incentiveAmount);
        $this->assertNull($saved->remarks);
        $this->assertNull($saved->bankAccount);
        $this->assertNull($saved->payrollScheme);
        $this->assertNull($saved->umkAmount);
    }

    public function test_duplicate_phone_or_email_cannot_apply_again(): void
    {
        $this->seedExisting();
        $this->expectNoAudit();

        foreach ([
            ['whatsapp_number' => '6281234567890', 'email' => 'lain@example.com'],
            ['whatsapp_number' => '81111111111', 'email' => 'lama@example.com'],
        ] as $overrides) {
            $response = $this->submit($overrides);
            $response->assertRedirect(route('public.outsource.apply'));
            $response->assertSessionHas('error');
            $response->assertSessionMissing('public_outsource_submission_completed');
        }

        $this->assertCount(1, $this->outsourceRepo->getAll());
    }

    public function test_rejects_vendor_entity_and_education_outside_the_lists(): void
    {
        $this->expectNoAudit();

        $this->submit([
            'vendor' => 'PT Vendor Lain',
            'entity' => 'PT Tidak Dikenal',
            'last_education' => 'SMA',
        ])->assertSessionHasErrors(['vendor', 'entity', 'last_education']);

        $this->assertCount(0, $this->outsourceRepo->getAll());
    }

    public function test_rejects_contract_end_on_or_before_start(): void
    {
        $this->expectNoAudit();

        $this->submit([
            'contract_start_date' => '2026-01-15',
            'contract_end_date' => '2026-01-15',
        ])->assertSessionHasErrors(['contract_end_date']);
    }

    public function test_submission_accepts_blank_optional_dates(): void
    {
        $this->expectAudit();

        $this->submit([
            'mito_join_date' => '',
            'contract_start_date' => '',
            'contract_end_date' => '',
        ])->assertRedirect(route('public.outsource.success'));

        $saved = $this->outsourceRepo->getAll()->first();
        $this->assertNotNull($saved);
        $this->assertNull($saved->mitoJoinDate);
        $this->assertNull($saved->contractStartDate);
        $this->assertNull($saved->contractEndDate);
    }

    public function test_rejects_applicant_younger_than_seventeen(): void
    {
        $this->expectNoAudit();

        $this->submit([
            'birth_date' => now()->timezone('Asia/Jakarta')->subYears(16)->toDateString(),
        ])->assertSessionHasErrors(['birth_date']);
    }

    public function test_rejects_invalid_contact_formats(): void
    {
        $this->expectNoAudit();

        $this->submit([
            'email' => 'bukan-email',
            'whatsapp_number' => '12345',
            'full_name' => 'Budi<script>',
        ])->assertSessionHasErrors(['email', 'whatsapp_number', 'full_name']);
    }

    private function submit(array $overrides = [])
    {
        return $this->onDomain('outsource')
            ->from(route('public.outsource.apply'))
            ->post(route('public.outsource.store'), $this->validPayload($overrides));
    }

    private function seedExisting(): void
    {
        $this->outsourceRepo->create(new OutsourceEmployeeData(
            outsourceId: 'DM20260001',
            fullName: 'Karyawan Lama',
            whatsappNumber: '+6281234567890',
            email: 'lama@example.com',
            vendor: 'Damarindo',
        ));
    }

    private function expectAudit(): void
    {
        $auditRepo = Mockery::mock(AuditLogRepositoryInterface::class);
        $auditRepo->shouldReceive('log')->once()->andReturn(true);
        $this->app->instance(AuditLogRepositoryInterface::class, $auditRepo);
    }

    private function expectNoAudit(): void
    {
        $auditRepo = Mockery::mock(AuditLogRepositoryInterface::class);
        $auditRepo->shouldNotReceive('log');
        $this->app->instance(AuditLogRepositoryInterface::class, $auditRepo);
    }

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'full_name' => 'Budi Santoso',
            'citizen_id_address' => 'Jl. Merdeka No. 1, RT 01/RW 02, Bandung',
            'birth_date' => '1990-01-01',
            'birth_place' => 'Bandung',
            'last_education' => 'SLTA',
            'whatsapp_number' => '81234567890',
            'email' => 'Budi@Example.com',
            'vendor' => 'Damarindo',
            'job_title' => 'SPB/SPG Toko',
            'work_location' => 'Toko Sinar Jaya (Pasar Baru)',
            'work_city' => 'Kota Bandung',
            'cost_center' => 'Bandung',
            'entity' => 'PT. Mahakarya Sukses Indonesia',
            'mito_join_date' => '2026-01-15',
            'contract_start_date' => '2026-01-15',
            'contract_end_date' => '2026-12-31',
            'agreement' => '1',
        ], $overrides);
    }
}
