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
        $response->assertSee('data-person-name', false);
        $this->assertSame(10, substr_count($response->getContent(), 'data-person-name'));
        $response->assertSee('placeholder="Sesuai KTP / dokumen kepegawaian" required', false);
        $response->assertSee('name="full_name" maxlength="80"', false);
        $response->assertSee('name="position" maxlength="60" autocomplete="off" required', false);
        $response->assertSee('name="division" maxlength="60" autocomplete="off" required', false);
        $response->assertDontSee('NIK', false);
        $response->assertDontSee('name="nik"', false);
        $response->assertDontSee('Employee ID', false);
        $response->assertDontSee('name="employee_id"', false);
        $response->assertSee('value="msi" required', false);
        $response->assertSee('name="last_working_date" required', false);
        $response->assertSee('data-money-status="petty"', false);
        $response->assertSee('data-money-status="loan"', false);
        $response->assertSee('data-money-fields="petty"', false);
        $response->assertSee('data-money-fields="loan"', false);
        $response->assertSee('data-person-name', false);
        $response->assertSee("field.value.replace(/[^\\p{L}'. ]/gu, '')", false);
        $response->assertSee('inputmode="numeric" pattern="[0-9]{1,3}(,[0-9]{3})*|[0-9]{1,12}"', false);
        $response->assertSee('maxlength="15" disabled', false);
        $response->assertSee('digits.replace(/\\B(?=(\\d{3})+(?!\\d))/g, \',\')', false);
        $response->assertSee('name="it[phone]" maxlength="15" inputmode="numeric" pattern="[0-9]{8,15}"', false);
        $response->assertSee('name="delegated_to" maxlength="80"', false);
        $response->assertSee('name="handover_other" maxlength="100"', false);
        $response->assertSee('name="ga[other]" maxlength="80"', false);
        $response->assertSee('name="it[email]" maxlength="100"', false);
        $response->assertSee('name="it[password]" maxlength="60"', false);
        $response->assertSee('name="it[laptop]" maxlength="80"', false);
        $response->assertSee('name="it[other]" maxlength="80"', false);
        $response->assertSee('name="division_assets[devices]" maxlength="100"', false);
        $response->assertSee('name="petty_to"', false);
        $response->assertSee('name="loan_to"', false);
        $response->assertSee('maxlength="80" disabled', false);
        $response->assertSee('name="accounting_other" maxlength="120"', false);
        $response->assertSee('name="approvers[employee][name]" maxlength="40"', false);
        $response->assertSee('data-digits-only', false);
        $response->assertSee("field.value = field.value.replace(/\\D/g, '').slice(0, 15);", false);
        $response->assertSee('field.disabled = !isAvailable;', false);
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

        $response->assertSessionHasErrors(['position', 'division']);
    }

    public function test_pdf_publik_exit_clearance(): void
    {
        $response = $this->post('/exit-clearance', [
            'full_name'         => 'Sari Wulandari',
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
            'position'          => 'Staff Finance',
            'division'          => 'Finance & Accounting',
            'company'           => 'msi',
            'last_working_date' => '2026-10-26',
        ]);

        $response->assertOk();
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }
}
