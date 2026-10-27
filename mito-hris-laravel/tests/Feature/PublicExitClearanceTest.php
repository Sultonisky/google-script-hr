<?php

namespace Tests\Feature;

use Tests\TestCase;

class PublicExitClearanceTest extends TestCase
{
    public function test_halaman_publik_exit_clearance_tanpa_login(): void
    {
        $response = $this->get('/exit-clearance');

        $response->assertOk();
        $response->assertSee('Cari Karyawan', false);
        $response->assertSee('id="formExitClearance"', false);
        $response->assertSee('exit-clearance/__ID__', false);
        $response->assertSee('btnExitClearanceReset', false);
        $response->assertDontSee('data-bs-dismiss="modal"', false);
    }

    public function test_pdf_publik_exit_clearance(): void
    {
        $response = $this->post('/exit-clearance/2018110201', [
            'company' => 'msi',
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
        ])->post('/exit-clearance/2018110201', [
            'company' => 'msi',
            'last_working_date' => '2026-10-26',
        ]);

        $response->assertOk();
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }
}
