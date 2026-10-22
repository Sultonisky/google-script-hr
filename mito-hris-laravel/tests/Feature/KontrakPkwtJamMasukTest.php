<?php

namespace Tests\Feature;

use Tests\TestCase;

class KontrakPkwtJamMasukTest extends TestCase
{
    public function test_pasal_enam_mencetak_tiga_ketentuan_jam_masuk(): void
    {
        $cases = [
            'mulai pukul 08.00 WIB' => [
                'clause' => 'Ketentuan jam masuk kerja adalah mulai pukul 08.00 WIB',
                'schedule' => '5 (lima) hari kerja',
            ],
            'mulai pukul 08.30 WIB' => [
                'clause' => 'Ketentuan jam masuk kerja adalah mulai pukul 08.30 WIB',
                'schedule' => '5 (lima) hari kerja',
            ],
            'mengikuti shift sesuai penempatan' => [
                'clause' => 'Ketentuan jam masuk kerja adalah mengikuti shift sesuai penempatan',
                'schedule' => 'pola shift khusus',
            ],
            '08:30' => [
                'clause' => 'Ketentuan jam masuk kerja adalah mulai pukul 08.30 WIB',
                'schedule' => '5 (lima) hari kerja',
            ],
            'shift sesuai penempatan' => [
                'clause' => 'Ketentuan jam masuk kerja adalah mengikuti shift sesuai penempatan',
                'schedule' => 'pola shift khusus',
            ],
        ];

        foreach ($cases as $jamMasuk => $expected) {
            $html = $this->renderKontrak(['jam_masuk' => $jamMasuk, 'work_schedule' => 'Normal']);

            $this->assertStringContainsString($expected['clause'], $html, $jamMasuk);
            $this->assertStringContainsString($expected['schedule'], $html, $jamMasuk);
        }
    }

    public function test_jam_masuk_kosong_memakai_pukul_08_00(): void
    {
        $html = $this->renderKontrak(['work_schedule' => 'Normal']);

        $this->assertStringContainsString(
            'Ketentuan jam masuk kerja adalah mulai pukul 08.00 WIB',
            $html
        );
    }

    /**
     * @param  array<string, string>  $extraData
     */
    private function renderKontrak(array $extraData): string
    {
        return view('pdf.kontrak-pkwt', [
            'employee' => (object) [
                'fullName' => 'Test Karyawan',
                'jobPosition' => 'Staff',
                'department' => 'HR',
                'division' => 'People',
            ],
            'candidate' => null,
            'company' => [
                'name' => 'PT MAHAKARYA SUKSES INDONESIA',
                'address' => 'Jakarta',
                'city' => 'Jakarta',
            ],
            'extraData' => $extraData + [
                'contract_number' => 'PKWT-TEST',
                'doc_date' => '2026-10-22',
                'contract_duration' => '12 Bulan',
                'join_date' => '2026-10-22',
                'contract_end' => '2027-10-21',
                'position' => 'Staff',
                'department' => 'HR',
                'division' => 'People',
                'direct_superior' => 'Atasan',
            ],
        ])->render();
    }
}
