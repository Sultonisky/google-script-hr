<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\EmployeeDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Session;
use Tests\TestCase;

class DocumentTrackingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['hris.data_driver' => 'pgsql']);
        (new \App\Providers\AppServiceProvider($this->app))->register();

        Employee::query()->create([
            'employee_id' => 'EMP-DOC-1',
            'full_name' => 'Citra Lestari',
            'job_position' => 'Staff HR',
            'department' => 'Human Capital',
            'status_employee' => 'Contract',
        ]);
        Employee::query()->create([
            'employee_id' => 'EMP-DOC-2',
            'full_name' => 'Dimas Pratama',
            'job_position' => 'Operator',
            'department' => 'Produksi',
            'status_employee' => 'PKWTT',
        ]);

        $this->document('DOC-20260901-001-PKWT', 'EMP-DOC-1', 'PKWT', 'Kontrak PKWT', '001/PKWT/MSI/IX/2026', 'MSI', '2026-09-01 09:00:00');
        $this->document('DOC-20260915-002-SKP', 'EMP-DOC-2', 'SKP', 'SK Pengangkatan', '002/SKP/SPI/IX/2026', 'SPI', '2026-09-15 10:30:00');
        $this->document('DOC-20260920-001-SP', 'EMP-DOC-1', 'SP', 'Surat Peringatan Kedua', '001/SP/MSI/IX/2026', 'MSI', '2026-09-20 11:15:00');
    }

    private function document(string $id, string $employeeId, string $code, string $type, string $nomor, string $entity, string $issuedAt): void
    {
        EmployeeDocument::query()->create([
            'document_id' => $id,
            'employee_id' => $employeeId,
            'sequence' => 1,
            'doc_type' => $type,
            'doc_code' => $code,
            'nomor' => $nomor,
            'entity' => $entity,
            'issued_at' => $issuedAt,
            'issued_by' => 'HR Admin',
            'reference' => 'REF-' . $code,
        ]);
    }

    private function actingAsRole(string $role): void
    {
        Session::put('hr_user', $this->migratedTestUser([
            'email' => strtolower(str_replace(' ', '.', $role)) . '@mito.id',
            'fullName' => $role . ' User',
            'role' => $role,
            'permissions' => config('hris.auth.role_permissions')[$role] ?? [],
            'auth_domain' => 'users',
            'entities' => [],
            'branch' => '',
        ]));
    }

    public function test_lists_documents_joined_with_employee_data(): void
    {
        $this->actingAsRole('Admin');

        $this->get('/hr/documents')
            ->assertOk()
            ->assertSee('Document Tracking')
            ->assertSee('001/PKWT/MSI/IX/2026')
            ->assertSee('002/SKP/SPI/IX/2026')
            ->assertSee('Citra Lestari')
            ->assertSee('Dimas Pratama')
            ->assertSee('09:00')
            ->assertSee('10:30')
            ->assertDontSee('001/SP/MSI/IX/2026')
            ->assertDontSee('Surat Peringatan Kedua')
            ->assertSee('href="' . route('hr.documents.index') . '"', false);
    }

    public function test_midnight_issue_time_uses_the_creation_clock(): void
    {
        $this->actingAsRole('Admin');
        EmployeeDocument::query()->where('document_id', 'DOC-20260901-001-PKWT')->update([
            'issued_at' => '2026-10-26 00:00:00',
            'created_at' => '2026-10-26 18:30:28',
        ]);

        $this->get('/hr/documents')
            ->assertOk()
            ->assertSeeInOrder(['26 Oct 2026', '18:30']);
    }

    public function test_filters_by_type_entity_and_search(): void
    {
        $this->actingAsRole('Admin');

        $this->get('/hr/documents?type=SKP')
            ->assertOk()
            ->assertSee('002/SKP/SPI/IX/2026')
            ->assertDontSee('001/PKWT/MSI/IX/2026');

        $this->get('/hr/documents?entity=MSI')
            ->assertOk()
            ->assertSee('001/PKWT/MSI/IX/2026')
            ->assertDontSee('002/SKP/SPI/IX/2026');

        $this->get('/hr/documents?search=dimas')
            ->assertOk()
            ->assertSee('002/SKP/SPI/IX/2026')
            ->assertDontSee('001/PKWT/MSI/IX/2026');

        $this->get('/hr/documents?type=SP')
            ->assertOk()
            ->assertDontSee('001/SP/MSI/IX/2026')
            ->assertDontSee('Surat Peringatan Kedua');
    }

    public function test_user_without_view_documents_is_forbidden(): void
    {
        $this->actingAsRole('User');

        $this->get('/hr/documents')->assertForbidden();
    }

    public function test_sp_tracking_lists_only_warning_letters_and_shows_sidebar_link(): void
    {
        $this->actingAsRole('Admin');

        $this->get('/hr/sp-tracking')
            ->assertOk()
            ->assertSee('SP Tracking')
            ->assertSee('001/SP/MSI/IX/2026')
            ->assertSee('Surat Peringatan Kedua')
            ->assertSee('Citra Lestari')
            ->assertSee('id="spTrackingSort"', false)
            ->assertSee('id="spPoints"', false)
            ->assertSee('Reset Filter')
            ->assertDontSee('>Cari</button>', false)
            ->assertSee('class="export-btns"', false)
            ->assertSee(route('hr.employees.warning-letter.download', [
                'id' => 'EMP-DOC-1',
                'documentId' => 'DOC-20260920-001-SP',
            ]), false)
            ->assertDontSee('001/PKWT/MSI/IX/2026')
            ->assertDontSee('002/SKP/SPI/IX/2026')
            ->assertSee('href="' . route('hr.sp-tracking') . '"', false);
    }

    public function test_sp_tracking_applies_sort_order(): void
    {
        $this->actingAsRole('Admin');
        $this->document('DOC-20260921-002-SP', 'EMP-DOC-2', 'SP', 'Surat Peringatan Pertama', '002/SP/SPI/IX/2026', 'SPI', '2026-09-21 11:15:00');

        $this->get('/hr/sp-tracking?sort=nomor_asc')
            ->assertOk()
            ->assertSeeInOrder([
                '001/SP/MSI/IX/2026',
                '002/SP/SPI/IX/2026',
            ]);

        $this->get('/hr/sp-tracking?sort=issued_asc')
            ->assertOk()
            ->assertSeeInOrder([
                '001/SP/MSI/IX/2026',
                '002/SP/SPI/IX/2026',
            ]);
    }

    public function test_sp_tracking_filters_by_sanction_status_and_level(): void
    {
        $this->actingAsRole('Admin');
        EmployeeDocument::query()->create([
            'document_id' => 'DOC-20260801-003-SP',
            'employee_id' => 'EMP-DOC-1',
            'sequence' => 3,
            'doc_type' => 'Surat Peringatan Pertama (SP-1)',
            'doc_code' => 'SP',
            'nomor' => '003/SP-1/MSI/VIII/2026',
            'entity' => 'MSI',
            'issued_at' => '2026-08-01 09:00:00',
            'reference' => 'SP1',
            'notes' => 'Pasal 12 — berlaku s.d. 2027-01-31',
        ]);
        EmployeeDocument::query()->create([
            'document_id' => 'DOC-20260929-004-SP',
            'employee_id' => 'EMP-DOC-2',
            'sequence' => 4,
            'doc_type' => 'Surat Peringatan Pertama (SP-1)',
            'doc_code' => 'SP',
            'nomor' => '004/SP-1/SPI/IX/2026',
            'entity' => 'SPI',
            'issued_at' => '2026-09-29 09:00:00',
            'reference' => 'SP1',
            'notes' => 'Pasal 12 — berlaku s.d. 2027-03-28',
        ]);

        $this->get('/hr/sp-tracking')
            ->assertOk()
            ->assertSee('id="spTrackingStatus"', false)
            ->assertSee('id="spTrackingLevel"', false)
            ->assertSee('Aktif (1)', false)
            ->assertSee('Hangus (1)', false)
            ->assertSee('SP-1 (2)', false)
            ->assertSee('SP-2 (1)', false);

        $this->get('/hr/sp-tracking?status=active')
            ->assertOk()
            ->assertSee('004/SP-1/SPI/IX/2026')
            ->assertSee('badge-status accepted">Aktif', false)
            ->assertDontSee('003/SP-1/MSI/VIII/2026')
            ->assertDontSee('001/SP/MSI/IX/2026');

        $this->get('/hr/sp-tracking?status=superseded')
            ->assertOk()
            ->assertSee('003/SP-1/MSI/VIII/2026')
            ->assertSee('badge-status blacklist">Hangus', false)
            ->assertDontSee('004/SP-1/SPI/IX/2026');

        $this->get('/hr/sp-tracking?level=SP2')
            ->assertOk()
            ->assertSee('001/SP/MSI/IX/2026')
            ->assertDontSee('004/SP-1/SPI/IX/2026')
            ->assertDontSee('003/SP-1/MSI/VIII/2026');

        $this->get('/hr/sp-tracking?status=active&level=SP1')
            ->assertOk()
            ->assertSee('004/SP-1/SPI/IX/2026')
            ->assertDontSee('003/SP-1/MSI/VIII/2026')
            ->assertDontSee('001/SP/MSI/IX/2026');
    }

    public function test_sp_tracking_requires_warning_letter_permission(): void
    {
        $this->actingAsRole('User');

        $this->get('/hr/sp-tracking')->assertForbidden();
    }
}
