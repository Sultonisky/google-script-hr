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
            'ga'                => [
                'vehicle' => '1',
                'vehicle_docs' => '1',
                'other' => 'Kunci gudang',
            ],
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
                'hrga' => ['name' => 'Nina HR', 'date' => '2026-10-26'],
            ],
        ])));

        $html = view('pdf.exit-clearance', compact('extraData'))->render();

        $this->assertSame(2, substr_count($html, 'class="blank asset-row-spacer" colspan="2"'));
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
        $this->assertStringContainsString('Kunci gudang', $html);
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
        $this->assertStringNotContainsString('NIK', $html);
        $this->assertStringNotContainsString('Employee ID', $html);
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

    public function test_penerima_delegasi_harus_berupa_nama(): void
    {
        $this->expectException(ValidationException::class);

        ExitClearanceForm::payload(Request::create('/', 'POST', $this->basePayload([
            'delegated_to' => 'Budi 123',
        ])));
    }

    public function test_nama_karyawan_dan_approver_harus_berupa_nama(): void
    {
        foreach ([
            ['full_name' => 'Sari 123'],
            ['approvers' => ['employee' => ['name' => 'Yuri 123']]],
        ] as $invalidName) {
            try {
                ExitClearanceForm::payload(Request::create('/', 'POST', $this->basePayload($invalidName)));
                $this->fail('Nama dengan angka harus ditolak.');
            } catch (ValidationException) {
                $this->addToAssertionCount(1);
            }
        }
    }

    public function test_input_teks_menolak_nilai_di_atas_batas_pdf(): void
    {
        $longInputs = [
            ['full_name' => str_repeat('A', 81)],
            ['position' => str_repeat('A', 61)],
            ['division' => str_repeat('A', 61)],
            ['delegated_to' => str_repeat('A', 81)],
            ['handover_other' => str_repeat('x', 101)],
            ['ga' => ['other' => str_repeat('x', 81)]],
            ['it' => ['email' => 'a@'.str_repeat('b', 60).'.'.str_repeat('c', 35).'.co']],
            ['it' => ['password' => str_repeat('x', 61)]],
            ['it' => ['laptop' => str_repeat('x', 81)]],
            ['it' => ['other' => str_repeat('x', 81)]],
            ['division_assets' => ['devices' => str_repeat('x', 101)]],
            ['petty_status' => 'ada', 'petty_amount' => '1', 'petty_to' => str_repeat('A', 81)],
            ['loan_status' => 'ada', 'loan_amount' => '1', 'loan_to' => str_repeat('A', 81)],
            ['accounting_other' => str_repeat('x', 121)],
            ['approvers' => ['employee' => ['name' => str_repeat('A', 41)]]],
        ];

        foreach ($longInputs as $overrides) {
            try {
                ExitClearanceForm::payload(Request::create('/', 'POST', $this->basePayload($overrides)));
                $this->fail('Input yang melebihi batas harus ditolak.');
            } catch (ValidationException) {
                $this->addToAssertionCount(1);
            }
        }
    }

    public function test_email_dan_nominal_tidak_valid_ditolak(): void
    {
        $this->expectException(ValidationException::class);

        ExitClearanceForm::payload(Request::create('/', 'POST', $this->basePayload([
            'it' => ['email' => 'bukan-email'],
        ])));
    }

    public function test_nomor_handphone_harus_berisi_digit_saja(): void
    {
        $this->expectException(ValidationException::class);

        ExitClearanceForm::payload(Request::create('/', 'POST', $this->basePayload([
            'it' => ['phone' => '+628123456789'],
        ])));
    }

    public function test_nominal_wajib_saat_status_ada(): void
    {
        $this->expectException(ValidationException::class);

        ExitClearanceForm::payload(Request::create('/', 'POST', $this->basePayload([
            'petty_status' => 'ada',
        ])));
    }

    public function test_nominal_harus_berupa_angka(): void
    {
        $this->expectException(ValidationException::class);

        ExitClearanceForm::payload(Request::create('/', 'POST', $this->basePayload([
            'petty_status' => 'ada',
            'petty_amount' => '1500abc',
            'petty_to'     => 'Kasir',
        ])));
    }

    public function test_nominal_dengan_pemisah_koma_diterima(): void
    {
        $payload = ExitClearanceForm::payload(Request::create('/', 'POST', $this->basePayload([
            'petty_status' => 'ada',
            'petty_amount' => '1,500,000',
            'petty_to'     => 'Kasir',
        ])));

        $this->assertSame('Rp 1.500.000', $payload['petty']['amount']);
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
