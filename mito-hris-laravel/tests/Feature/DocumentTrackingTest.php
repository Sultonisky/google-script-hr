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
            ->assertSee('href="' . route('hr.documents.index') . '"', false);
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
    }

    public function test_user_without_view_employees_is_forbidden(): void
    {
        $this->actingAsRole('User');

        $this->get('/hr/documents')->assertForbidden();
    }
}
