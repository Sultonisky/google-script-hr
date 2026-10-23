<?php

namespace Tests\Feature;

use App\DTOs\EmployeeData;
use Tests\TestCase;

class SuratNdaPdfTest extends TestCase
{
    public function test_nda_mengikuti_template(): void
    {
        $html = view('pdf.surat-nda', [
            'employee' => new EmployeeData(
                fullName: 'Anton Hardianto Muslim',
                nikNpwp: '3603172308920002',
                citizenIdAddress: 'Kp Kadu RT.003 RW.001 Kel Kadu Kec.Curug',
                jobPosition: 'Purchasing Associate',
                branchName: 'PT Mahakarya Sukses Indonesia',
            ),
            'company' => [
                'name' => 'PT MAHAKARYA SUKSES INDONESIA',
                'city' => 'Jakarta',
            ],
            'extraData' => [
                'doc_date' => '2026-10-08',
            ],
        ])->render();

        $this->assertStringContainsString('SURAT PERNYATAAN NON-DISCLOSURE AGREEMENT', $html);
        $this->assertStringContainsString('Saya yang bertandatangan di bawah ini :', $html);
        $this->assertStringContainsString('Anton Hardianto Muslim', $html);
        $this->assertStringContainsString('3603172308920002', $html);
        $this->assertStringContainsString('Kp Kadu RT.003 RW.001 Kel Kadu Kec.Curug', $html);
        $this->assertStringContainsString('Purchasing Associate', $html);
        $this->assertStringContainsString('sungguh – sungguh', $html);
        $this->assertStringContainsString('PT. Mahakarya Sukses Indonesia', $html);
        $this->assertStringContainsString('design sistem', $html);
        $this->assertStringContainsString('pemecatan secara tidak terhormat', $html);
        $this->assertStringContainsString('tanpa paksaan dan tekanan dari pihak manapun.', $html);
        $this->assertStringContainsString('Jakarta, 8 Oktober 2026', $html);
        $this->assertStringContainsString('Yang Membuat Pernyataan,', $html);
        $this->assertStringContainsString('Saksi – saksi :', $html);
        $this->assertStringNotContainsString('08 Oktober 2026', $html);
    }

    public function test_nda_tanpa_tanggal_surat_tidak_memakai_hari_ini(): void
    {
        $html = view('pdf.surat-nda', [
            'employee' => new EmployeeData(fullName: 'Anton Hardianto Muslim'),
            'company' => ['name' => 'PT MAHAKARYA SUKSES INDONESIA', 'city' => 'Jakarta'],
            'extraData' => [],
        ])->render();

        $this->assertStringContainsString('Jakarta, -', $html);
        $this->assertStringNotContainsString('Oktober', $html);
    }
}
