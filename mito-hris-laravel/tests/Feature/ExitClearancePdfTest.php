<?php

namespace Tests\Feature;

use App\Support\ExitClearanceForm;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ExitClearancePdfTest extends TestCase
{
    /** Payload dasar yang selalu lolos validasi — override field tertentu di tiap test. */
    private function basePayload(array $overrides = []): array
    {
        return array_merge([
            'full_name'         => 'Sari Wulandari',
            'nik'               => '3174000000000001',
            'employee_id'       => '20240015',
            'position'          => 'Staff Finance',
            'division'          => 'Finance',
            'company'           => 'msi',
            'last_working_date' => '2026-10-26',
        ], $overrides);
    }

    public function test_exit_clearance_memuat_sdi_kendaraan_dan_approval(): void
    {
        $extraData = ExitClearanceForm::payload(Request::create('/', 'POST', $this->basePayload([
            'company'           => 'sdi',
            'hardcopy'          => '1',
            'delegated_to'      => 'Budi Santoso',
            'handover_date'     => '2026-10-20',
            'handover_other'    => 'Map kontrak',
            'ga'                => ['vehicle' => '1'],
            'it'                => [
                'email'    => 'sari@mitogroup.test',
                'password' => 'RahasiaLaptop',
                'laptop'   => 'Dell Latitude',
                'phone'    => '08123456789',
            ],
            'division_assets'    => ['devices' => 'Headset'],
            'petty_status'      => 'ada',
            'petty_amount'      => '1.500.000',
            'petty_to'          => 'Kasir',
            'approvers'         => [
                'hrga' => ['name' => 'Nina HR', 'date' => '2026-10-26'],
            ],
        ])));

        $html = view('pdf.exit-clearance', compact('extraData'))->render();

        $this->assertStringContainsString('EXIT CLEARANCE FORM', $html);
        $this->assertStringContainsString('data:image/png;base64,', $html);
        $this->assertStringContainsString('alt="MITO group"', $html);
        $this->assertStringNotContainsString('>MITO<', $html);
        $this->assertStringContainsString('PT Suka Dessert Indonesia', $html);
        $this->assertStringNotContainsString('PT Stein Perkasa Internasional', $html);
        $this->assertStringNotContainsString('PT Mitra Elektro Perkasa', $html);
        $this->assertStringNotContainsString('PT Perkasa Injeksi Indonesia', $html);
        $this->assertStringNotContainsString('PT Mahakarya Sukses Indonesia', $html);
        $this->assertStringContainsString('Kendaraan', $html);
        $this->assertStringContainsString('Surat-surat Kendaraan', $html);
        $this->assertStringNotContainsString('Kondisi', $html);
        $this->assertStringContainsString('Map kontrak', $html);
        $this->assertStringContainsString('class="tick">✓</span>', $html);
        $this->assertStringContainsString('Selasa, 20 Oktober 2026', $html);
        $this->assertStringContainsString('RahasiaLaptop', $html);
        $this->assertStringContainsString('Dell Latitude', $html);
        $this->assertStringContainsString('08123456789', $html);
        $this->assertStringContainsString('Headset', $html);
        $this->assertStringContainsString('Perangkat lainnya', $html);
        $this->assertStringContainsString('Akun-akun lainnya', $html);
        $this->assertStringContainsString('Password', $html);
        $this->assertStringContainsString('3174000000000001', $html);
        $this->assertStringContainsString('20240015', $html);
        $this->assertStringContainsString('Sari Wulandari', $html);
        $this->assertStringContainsString('Senin, 26 Oktober 2026', $html);
        $this->assertStringContainsString('sari@mitogroup.test', $html);
        $this->assertStringNotContainsString('Akses / password', $html);
        $this->assertStringContainsString('Rp 1.500.000', $html);
        $this->assertStringContainsString('Kasir', $html);
        $this->assertStringContainsString('Nina HR', $html);
        $this->assertStringContainsString('class="sign-date">26 Oktober 2026', $html);
        $this->assertStringNotContainsString('Tanda tangan', $html);

        $pdf = app(\App\Services\PdfGeneratorService::class)->generateExitClearancePdf(
            ExitClearanceForm::payload(Request::create('/', 'POST', $this->basePayload([
                'company'           => 'sdi',
                'hardcopy'          => '1',
                'delegated_to'      => 'Budi Santoso',
                'handover_date'     => '2026-10-20',
                'handover_other'    => 'Map kontrak',
                'ga'                => ['vehicle' => '1'],
                'it'                => [
                    'email'    => 'sari@mitogroup.test',
                    'password' => 'RahasiaLaptop',
                    'laptop'   => 'Dell Latitude',
                    'phone'    => '08123456789',
                ],
                'division_assets'    => ['devices' => 'Headset'],
                'petty_status'      => 'ada',
                'petty_amount'      => '1500000',
                'petty_to'          => 'Kasir',
                'approvers'         => [
                    'employee'   => ['name' => 'Yuri Ismawan Md', 'date' => '2026-11-07'],
                    'supervisor' => ['name' => 'Edwin Sumargo', 'date' => '2026-10-26'],
                    'delegate'   => ['name' => 'Nawar', 'date' => '2026-10-26'],
                    'hrga'       => ['name' => 'Anwar', 'date' => '2026-10-26'],
                    'it'         => ['name' => 'Ibul', 'date' => '2026-10-26'],
                    'accounting' => ['name' => 'Mbul', 'date' => '2026-10-26'],
                ],
            ])))
        );
        $output = $pdf->output();
        $this->assertStringStartsWith('%PDF', $output);
        $this->assertSame(1, $pdf->getDomPDF()->getCanvas()->get_page_count());
    }

    public function test_perusahaan_mengikuti_nama_di_master(): void
    {
        $this->assertSame('spi', ExitClearanceForm::matchCompany('PT. Steen Perkasa Internasional'));
        $this->assertSame('pii', ExitClearanceForm::matchCompany('PT. Perkasa Intens Indonesia'));
        $this->assertSame('mep', ExitClearanceForm::matchCompany('PT. Mitra Elektronik Perkasa'));
        $this->assertSame('sdi', ExitClearanceForm::matchCompany('PT Suka Dessert Indonesia'));
        $this->assertSame('msi', ExitClearanceForm::matchCompany('PT Mahakarya Sukses Indonesia'));
        $this->assertSame('', ExitClearanceForm::matchCompany(''));
    }

    public function test_perusahaan_tidak_dikenal_ditolak(): void
    {
        $this->expectException(ValidationException::class);

        ExitClearanceForm::payload(Request::create('/', 'POST', $this->basePayload([
            'company' => 'unknown',
        ])));
    }

    public function test_nama_wajib_diisi(): void
    {
        $this->expectException(ValidationException::class);

        ExitClearanceForm::payload(Request::create('/', 'POST', [
            'company'           => 'msi',
            'last_working_date' => '2026-10-26',
            // full_name tidak diisi
        ]));
    }

    public function test_email_dan_nominal_tidak_valid_ditolak(): void
    {
        $this->expectException(ValidationException::class);

        ExitClearanceForm::payload(Request::create('/', 'POST', $this->basePayload([
            'it' => ['email' => 'bukan-email'],
        ])));
    }

    public function test_nominal_wajib_saat_status_ada(): void
    {
        $this->expectException(ValidationException::class);

        ExitClearanceForm::payload(Request::create('/', 'POST', $this->basePayload([
            'petty_status' => 'ada',
        ])));
    }

    public function test_nominal_diabaikan_saat_status_tidak_ada(): void
    {
        $payload = ExitClearanceForm::payload(Request::create('/', 'POST', $this->basePayload([
            'petty_status' => 'tidak_ada',
            'petty_amount' => '1500000',
            'petty_to'     => 'Kasir',
        ])));

        $this->assertSame('', $payload['petty']['amount']);
        $this->assertSame('', $payload['petty']['to']);
    }
}
