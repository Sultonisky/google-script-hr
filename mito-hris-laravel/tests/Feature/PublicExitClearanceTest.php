<?php

namespace Tests\Feature;

use Tests\TestCase;

class PublicExitClearanceTest extends TestCase
{
    public function test_halaman_publik_exit_clearance_tanpa_login(): void
    {
        $response = $this->get('/exit-clearance');

        $response->assertOk();
        $response->assertSee('Identitas Anda', false);
        $response->assertSee('id="formExitClearance"', false);
        $response->assertSee('name="full_name"', false);
        $response->assertSee('placeholder="Sesuai KTP / dokumen kepegawaian" required', false);
        $response->assertSee('placeholder="Nomor Induk Kependudukan" required', false);
        $response->assertSee('placeholder="Contoh: 20210301" required', false);
        $response->assertSee('name="position" maxlength="160" autocomplete="off" required', false);
        $response->assertSee('name="division" maxlength="160" autocomplete="off" required', false);
        $response->assertSee('value="msi" required', false);
        $response->assertSee('name="last_working_date" style="max-width:220px" required', false);
        $response->assertSee('btnExitClearanceReset', false);
        $response->assertDontSee('id="btnExitClearancePdf" disabled', false);
        $response->assertDontSee('data-bs-dismiss="modal"', false);
    }

    public function test_identitas_exit_clearance_wajib_diisi_di_server(): void
    {
        $response = $this->post('/exit-clearance', [
            'full_name'         => 'Sari Wulandari',
            'company'           => 'msi',
            'last_working_date' => '2026-10-26',
        ]);

        $response->assertSessionHasErrors(['nik', 'employee_id', 'position', 'division']);
    }

    public function test_pdf_publik_exit_clearance(): void
    {
        $response = $this->post('/exit-clearance', [
            'full_name'         => 'Sari Wulandari',
            'nik'               => '3171234567890123',
            'employee_id'       => '2018110201',
            'position'          => 'Staff Finance',
            'division'          => 'Finance & Accounting',
            'company'           => 'msi',
            'last_working_date' => '2026-10-26',
        ]);

        $response->assertOk();
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_pdf_publik_tetap_jalan_saat_sesi_hr_berupa_array(): void
    {
        $response = $this->withSession([
            'hr_user' => [
                'email' => 'hr@mitogroup.test',
                'role' => 'HR Admin',
                'auth_domain' => 'users',
            ],
        ])->post('/exit-clearance', [
            'full_name'         => 'Sari Wulandari',
            'nik'               => '3171234567890123',
            'employee_id'       => '2018110201',
            'position'          => 'Staff Finance',
            'division'          => 'Finance & Accounting',
            'company'           => 'msi',
            'last_working_date' => '2026-10-26',
        ]);

        $response->assertOk();
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }
}
