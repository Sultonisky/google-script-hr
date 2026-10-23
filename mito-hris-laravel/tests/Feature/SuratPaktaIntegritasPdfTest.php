<?php

namespace Tests\Feature;

use App\DTOs\EmployeeData;
use Barryvdh\DomPDF\Facade\Pdf;
use Tests\TestCase;

class SuratPaktaIntegritasPdfTest extends TestCase
{
    public function test_pakta_integritas_mengikuti_template(): void
    {
        $html = view('pdf.surat-pakta-integritas', [
            'employee' => new EmployeeData(
                employeeId: '20010401',
                fullName: 'Edy Loa',
                branchName: 'PT Stein Perkasa Internasional',
                nikNpwp: '3671050303630010',
            ),
            'company' => [
                'name' => 'PT STEIN PERKASA INTERNASIONAL',
                'city' => 'Jakarta',
            ],
            'extraData' => [
                'doc_date' => '2026-08-07',
            ],
        ])->render();

        $this->assertStringContainsString('SURAT PAKTA INTEGRITAS', $html);
        $this->assertStringContainsString('PERNYATAAN KOMITMEN DAN TANGGUNG JAWAB', $html);
        $this->assertStringContainsString('<strong>Edy Loa</strong>', $html);
        $this->assertStringContainsString('<strong>3671050303630010</strong>', $html);
        $this->assertStringContainsString(
            'Sebagai karyawan dari <strong>PT Stein Perkasa Internasional</strong> (selanjutnya disebut Perusahaan)',
            $html
        );
        $this->assertStringContainsString('<strong>Berkomitmen</strong>', $html);
        $this->assertStringContainsString('pungutan liar (PUNGLI)', $html);
        $this->assertStringContainsString('Korupsi, Kolusi, dan Nepotisme (KKN)', $html);
        $this->assertStringContainsString('benturan kepentingan (conflict of interest)', $html);
        $this->assertStringContainsString('tidak menerima pembayaran uang kompensasi atas berakhirnya PKWT tersebut', $html);
        $this->assertStringContainsString('<strong>Jakarta, 07 Agustus 2026</strong>', $html);
        $this->assertStringContainsString('Yang menyatakan,', $html);
        $this->assertStringContainsString('class="num">1.</td>', $html);
        $this->assertStringContainsString('class="num">11.</td>', $html);
        $this->assertStringNotContainsString('<ol', $html);

        $pdf = Pdf::loadHTML($html)->setPaper('a4', 'portrait');
        $output = $pdf->output();
        $this->assertStringStartsWith('%PDF-', $output);
        $this->assertSame(1, $pdf->getDomPDF()->getCanvas()->get_page_count());
    }

    public function test_pakta_tanpa_tanggal_surat_tidak_memakai_hari_ini(): void
    {
        $html = view('pdf.surat-pakta-integritas', [
            'employee' => new EmployeeData(fullName: 'Edy Loa'),
            'company' => ['name' => 'PT STEIN PERKASA INTERNASIONAL', 'city' => 'Jakarta'],
            'extraData' => [],
        ])->render();

        $this->assertStringContainsString('<strong>Jakarta, -</strong>', $html);
        $this->assertStringNotContainsString('Agustus', $html);
    }
}
