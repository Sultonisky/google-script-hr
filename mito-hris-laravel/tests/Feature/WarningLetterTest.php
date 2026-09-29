<?php

namespace Tests\Feature;

use App\DTOs\EmployeeData;
use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Models\EmployeeDocumentFile;
use App\Services\PdfGeneratorService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Session;
use Mockery;
use Tests\TestCase;

class WarningLetterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['hris.data_driver' => 'pgsql']);
        (new \App\Providers\AppServiceProvider($this->app))->register();

        Employee::query()->create([
            'employee_id' => 'EMP-SP-1',
            'full_name' => 'Rina Kartika',
            'branch_name' => 'PT Mahakarya Sukses Indonesia',
            'job_position' => 'Staff Finance',
            'department' => 'Finance',
            'status_employee' => 'PKWTT',
            'join_date' => '2025-01-06',
            'nomor_sk' => '007/SKP/MSI/III/2026',
        ]);
        EmployeeDocument::query()->create([
            'document_id' => 'DOC-20260310-007-SKP',
            'employee_id' => 'EMP-SP-1',
            'sequence' => 7,
            'doc_type' => 'SK Pengangkatan',
            'doc_code' => 'SKP',
            'nomor' => '007/SKP/MSI/III/2026',
            'entity' => 'MSI',
            'issued_at' => '2026-03-10 09:00:00',
            'issued_by' => 'HR Admin',
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

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'level' => 'SP1',
            'doc_date' => '2026-09-29',
            'validity_months' => 6,
            'violation_category' => 'Kedisiplinan & Kehadiran',
            'incident_date' => '2026-09-22',
            'violation_description' => 'Terlambat masuk kerja lebih dari 30 menit sebanyak 5 kali dalam satu bulan.',
            'regulation_reference' => 'Peraturan Perusahaan Pasal 12 ayat (3)',
            'corrective_actions' => "- Hadir tepat waktu sesuai jadwal kerja\n- Melapor ke atasan bila berhalangan",
        ], $overrides);
    }

    private function employeeData(string $branch = 'PT Mahakarya Sukses Indonesia'): EmployeeData
    {
        return EmployeeData::fromSheetRow([
            'Employee ID' => 'EMP-SP-1',
            'Full Name' => 'Rina Kartika',
            'Branch Name' => $branch,
            'Job Position' => 'Staff Finance',
            'Department' => 'Finance',
            'Status Employee' => 'PKWTT',
            'Join Date' => '2025-01-06',
            'Direct Superior' => 'Budi Santoso',
        ]);
    }

    public function test_generate_issues_sp_number_archives_pdf_and_keeps_nomor_sk(): void
    {
        $this->actingAsRole('Admin');

        $response = $this->postJson('/hr/employees/EMP-SP-1/warning-letter', $this->payload())
            ->assertCreated()
            ->assertJson(['success' => true, 'nomor' => '007/SP/MSI/IX/2026']);

        $document = EmployeeDocument::query()->where('employee_id', 'EMP-SP-1')->where('doc_code', 'SP')->firstOrFail();
        $this->assertSame('007/SP/MSI/IX/2026', $document->nomor);
        $this->assertSame('Surat Peringatan Pertama (SP-1)', $document->doc_type);
        $this->assertSame('SP1', $document->reference);
        $this->assertStringContainsString('berlaku s.d. 2027-03-28', (string) $document->notes);
        $this->assertStringStartsWith('2026-09-29', (string) $document->issued_at);

        $this->assertSame(
            '007/SKP/MSI/III/2026',
            Employee::query()->where('employee_id', 'EMP-SP-1')->value('nomor_sk')
        );

        $archive = EmployeeDocumentFile::query()->where('document_id', $document->document_id)->firstOrFail();
        $this->assertSame('Surat_Peringatan_SP1_Rina_Kartika_Employee_EMP-SP-1.pdf', $archive->file_name);

        $this->assertSame(
            route('hr.employees.warning-letter.download', ['id' => 'EMP-SP-1', 'documentId' => $document->document_id]),
            $response->json('pdf_url')
        );
        $download = $this->get($response->json('pdf_url'))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');
        $this->assertSame(hash('sha256', $download->getContent()), $archive->checksum_sha256);
        $this->assertStringContainsString($archive->file_name, (string) $download->headers->get('Content-Disposition'));

        $tracking = $this->get('/hr/documents/' . $document->document_id . '/download')
            ->assertOk()
            ->assertHeader('X-Document-Source', 'export');
        $this->assertSame($download->getContent(), $tracking->getContent());
    }

    public function test_pdf_is_rendered_with_employee_branch_letterhead(): void
    {
        $this->actingAsRole('Admin');
        Employee::query()->where('employee_id', 'EMP-SP-1')->update(['branch_name' => 'PT Stein Perkasa Internasional']);

        $pdfService = Mockery::mock(PdfGeneratorService::class)->makePartial();
        $pdfService->shouldReceive('generateWarningLetterPdf')
            ->once()
            ->withArgs(fn (EmployeeData $employee, array $extraData) => $employee->branchName === 'PT Stein Perkasa Internasional'
                && $extraData['sk_number'] === '007/SP/SPI/IX/2026'
                && $extraData['level'] === 'SP2'
                && $extraData['valid_until'] === '2026-12-28')
            ->andReturn(Pdf::loadHTML('<p>SP-2</p>'));
        $this->app->instance(PdfGeneratorService::class, $pdfService);

        $this->postJson('/hr/employees/EMP-SP-1/warning-letter', $this->payload(['level' => 'SP2', 'validity_months' => 3]))
            ->assertCreated()
            ->assertJson(['nomor' => '007/SP/SPI/IX/2026']);
    }

    public function test_template_renders_corporate_content(): void
    {
        $company = ['name' => 'PT STEIN PERKASA INTERNASIONAL', 'address' => 'Jakarta Utara', 'city' => 'Jakarta', 'code' => 'SPI'];
        $html = view('pdf.surat-peringatan', [
            'employee' => $this->employeeData('PT Stein Perkasa Internasional'),
            'extraData' => [
                'sk_number' => '007/SP/SPI/IX/2026',
                'doc_date' => '2026-09-29',
                'level' => 'SP2',
                'violation_category' => 'Kedisiplinan & Kehadiran',
                'incident_date' => '2026-09-22',
                'violation_description' => "Terlambat <b>5 kali</b>\nTanpa keterangan",
                'regulation_reference' => '',
                'corrective_actions' => "- Hadir tepat waktu\n2. Melapor ke atasan",
                'validity_months' => 6,
                'valid_until' => '2027-03-28',
            ],
            'company' => $company,
        ])->render();

        $this->assertStringContainsString('PT STEIN PERKASA INTERNASIONAL', $html);
        $this->assertStringContainsString('Surat Peringatan Kedua', $html);
        $this->assertStringContainsString('(SP-2)', $html);
        $this->assertStringContainsString('Nomor: 007/SP/SPI/IX/2026', $html);
        $this->assertStringContainsString('Karyawan Tetap (PKWTT)', $html);
        $this->assertStringContainsString('22 September 2026', $html);
        $this->assertStringContainsString('Terlambat &lt;b&gt;5 kali&lt;/b&gt;<br />', $html);
        $this->assertStringContainsString('Peraturan Perusahaan PT STEIN PERKASA INTERNASIONAL', $html);
        $this->assertStringContainsString('<li>Hadir tepat waktu</li>', $html);
        $this->assertStringContainsString('<li>Melapor ke atasan</li>', $html);
        $this->assertStringContainsString('6 (enam) bulan', $html);
        $this->assertStringContainsString('28 Maret 2027', $html);
        $this->assertStringContainsString('Surat Peringatan Ketiga (SP-3)', $html);
        $this->assertStringContainsString('Jakarta, 29 September 2026', $html);
        $this->assertStringContainsString('Tanggal: 29 September 2026', $html);
        $this->assertStringNotContainsString('Tanggal: ____', $html);
        $this->assertStringContainsString('Hisar Hesti', $html);
        $this->assertStringContainsString('Atasan Langsung (Budi Santoso)', $html);
    }

    public function test_sp3_template_states_final_warning_and_ignores_foreign_numbers(): void
    {
        $html = view('pdf.surat-peringatan', [
            'employee' => $this->employeeData(),
            'extraData' => [
                'sk_number' => '007/SKP/MSI/III/2026',
                'doc_date' => '2026-09-29',
                'level' => 'SP3',
                'violation_category' => 'Perilaku & Etika Kerja',
                'violation_description' => 'Bersikap tidak sopan kepada rekan kerja.',
                'validity_months' => 6,
                'valid_until' => '2027-03-28',
            ],
            'company' => [],
        ])->render();

        $this->assertStringContainsString('Surat Peringatan Ketiga', $html);
        $this->assertStringContainsString('peringatan terakhir', $html);
        $this->assertStringContainsString('Pemutusan Hubungan Kerja (PHK)', $html);
        $this->assertStringNotContainsString('Nomor:', $html);
        $this->assertStringNotContainsString('007/SKP/MSI/III/2026', $html);
    }

    public function test_validation_errors_return_422_in_indonesian(): void
    {
        $this->actingAsRole('Admin');

        $this->postJson('/hr/employees/EMP-SP-1/warning-letter', $this->payload([
            'level' => 'SP4',
            'violation_category' => 'Tidak ada',
            'violation_description' => 'pendek',
            'incident_date' => '2026-10-01',
            'validity_months' => 12,
        ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['level', 'violation_category', 'violation_description', 'incident_date', 'validity_months']);

        $this->assertSame(0, EmployeeDocument::query()->where('doc_code', 'SP')->count());
    }

    public function test_ineligible_status_is_rejected(): void
    {
        $this->actingAsRole('Admin');

        foreach (['Outsource', 'Resigned', 'Contract Finished'] as $status) {
            Employee::query()->where('employee_id', 'EMP-SP-1')->update(['status_employee' => $status]);

            $this->postJson('/hr/employees/EMP-SP-1/warning-letter', $this->payload())
                ->assertStatus(422)
                ->assertJson([
                    'success' => false,
                    'message' => 'Surat Peringatan hanya dapat diterbitkan untuk karyawan berstatus Contract/PKWT atau Permanent/PKWTT.',
                ]);
        }

        $this->postJson('/hr/employees/EMP-UNKNOWN/warning-letter', $this->payload())->assertNotFound();
        $this->assertSame(0, EmployeeDocument::query()->where('doc_code', 'SP')->count());
    }

    public function test_download_is_scoped_to_employee_and_sp_documents(): void
    {
        $this->actingAsRole('Admin');
        $documentId = $this->postJson('/hr/employees/EMP-SP-1/warning-letter', $this->payload())
            ->assertCreated()
            ->json('document_id');

        $this->get('/hr/employees/EMP-OTHER/warning-letter/' . $documentId)->assertNotFound();

        $this->get('/hr/export/sk-pengangkatan/EMP-SP-1')->assertOk();
        $this->get('/hr/employees/EMP-SP-1/warning-letter/DOC-20260310-007-SKP')->assertNotFound();
    }

    public function test_sp_without_archive_cannot_be_regenerated(): void
    {
        $this->actingAsRole('Admin');
        EmployeeDocument::query()->create([
            'document_id' => 'DOC-20260901-007-SP',
            'employee_id' => 'EMP-SP-1',
            'sequence' => 7,
            'doc_type' => 'Surat Peringatan Pertama (SP-1)',
            'doc_code' => 'SP',
            'nomor' => '007/SP/MSI/IX/2026',
            'entity' => 'MSI',
            'issued_at' => '2026-09-01 09:00:00',
            'reference' => 'SP1',
        ]);

        $this->get('/hr/documents/DOC-20260901-007-SP/download')
            ->assertRedirect(route('hr.documents.index'))
            ->assertSessionHas('document_download_error');
        $this->assertSame(0, EmployeeDocumentFile::query()->where('document_id', 'DOC-20260901-007-SP')->count());
    }

    public function test_user_without_manage_employees_is_forbidden(): void
    {
        $this->actingAsRole('User');

        $this->postJson('/hr/employees/EMP-SP-1/warning-letter', $this->payload())->assertForbidden();
        $this->get('/hr/employees/EMP-SP-1/warning-letter/DOC-20260310-007-SKP')->assertForbidden();
    }

    public function test_master_data_shows_button_modal_and_tracking_lists_sp(): void
    {
        $this->actingAsRole('Admin');

        $this->get('/hr/employees')
            ->assertOk()
            ->assertSee('id="btnWarningLetter"', false)
            ->assertSee('id="warningLetterModal"', false)
            ->assertSee('Surat Peringatan Pertama (SP-1)');

        $this->postJson('/hr/employees/EMP-SP-1/warning-letter', $this->payload())->assertCreated();

        $this->get('/hr/documents?search=EMP-SP-1')
            ->assertOk()
            ->assertSee('Surat Peringatan Pertama (SP-1)')
            ->assertSee('007/SP/MSI/IX/2026');
    }
}
