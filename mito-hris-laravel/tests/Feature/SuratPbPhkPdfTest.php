<?php

namespace Tests\Feature;

use App\DTOs\EmployeeData;
use Tests\TestCase;

class SuratPbPhkPdfTest extends TestCase
{
    public function test_pb_phk_mengikuti_template(): void
    {
        $html = view('pdf.surat-pb-phk', [
            'employee' => new EmployeeData(
                fullName: 'Bagus Dwi Prihatmoko',
                citizenIdAddress: 'Jalan Krembangan Jaya Utara 7/7',
                branchName: 'PT Mahakarya Sukses Indonesia',
            ),
            'company' => [
                'name' => 'PT MAHAKARYA SUKSES INDONESIA',
                'address' => 'Rukan Golf Island Blok E Theme Park RGIE No. 92, RT.96-98/RW.112-116, Kamal Muara, Kecamatan Penjaringan, Daerah Khusus Ibukota Jakarta 14460',
            ],
            'extraData' => [
                'doc_date' => '2026-08-07',
                'last_date' => '2026-08-15',
                'amount' => 2644398,
            ],
        ])->render();

        $this->assertStringContainsString('PERJANJIAN BERSAMA', $html);
        $this->assertStringContainsString('Pada hari ini <strong>Jum&#039;at</strong>, tanggal <strong>07 Agustus 2026</strong>', $html);
        $this->assertStringContainsString('<strong>Hisar Hesti</strong> selaku Human Resources &amp; Legal Manager', $html);
        $this->assertStringContainsString('<strong>PT Mahakarya Sukses Indonesia</strong>', $html);
        $this->assertStringContainsString('Rukan Golf Island Blok E Theme Park RGIE No. 92', $html);
        $this->assertStringContainsString('selanjutnya disebut sebagai Pihak I,--------------------------', $html);
        $this->assertStringContainsString('<strong>Bagus Dwi Prihatmoko</strong> yang beralamat di Jalan Krembangan Jaya Utara 7/7', $html);
        $this->assertStringContainsString('selanjutnya disebut sebagai Pihak II,--------------------------', $html);
        $this->assertStringContainsString('musyawarah mufakat', $html);
        $this->assertStringContainsString('terhitung sejak tanggal <strong>15 Agustus 2026</strong> yang merupakan tanggal terakhir Pihak II bekerja.', $html);
        $this->assertStringContainsString('butir (1) diatas', $html);
        $this->assertStringContainsString('Rp. 2.644.398.00.-', $html);
        $this->assertStringContainsString('(Dua juta enam ratus empat puluh empat ribu tiga ratus sembilan puluh delapan rupiah)', $html);
        $this->assertStringContainsString('angka 1 dan 2 diatas.', $html);
        $this->assertStringContainsString('yang di perjanjian dalam perjanjian ini.', $html);
        $this->assertStringContainsString('pada tanggal <strong>07 Agustus 2026</strong> yang ditandatangani di dan bersepakat', $html);
        $this->assertStringContainsString('Pengadilan Negeri <strong>Jakarta Utara</strong>.', $html);
        $this->assertStringContainsString('sehat jasmani dan rohani tanpa adanya unsur paksaan dari pihak manapun.', $html);
        $this->assertStringNotContainsString('Materai', $html);
        $this->assertStringContainsString('Hisar Hesti', $html);
        $this->assertStringNotContainsString('07 Agustus 2026 yang merupakan tanggal terakhir', $html);
    }

    public function test_pb_phk_tanpa_tanggal_tidak_memakai_hari_ini(): void
    {
        $html = view('pdf.surat-pb-phk', [
            'employee' => new EmployeeData(fullName: 'Bagus Dwi Prihatmoko'),
            'company' => ['name' => 'PT MAHAKARYA SUKSES INDONESIA', 'address' => 'Jakarta'],
            'extraData' => [],
        ])->render();

        $this->assertStringContainsString('Pada hari ini <strong>-</strong>, tanggal <strong>-</strong>', $html);
        $this->assertStringContainsString('sejak tanggal <strong>-</strong>', $html);
        $this->assertStringContainsString('sebesar - (-)', $html);
        $this->assertStringNotContainsString('Agustus', $html);
    }
}
