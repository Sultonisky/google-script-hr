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

class DocumentDownloadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['hris.data_driver' => 'pgsql']);
        (new \App\Providers\AppServiceProvider($this->app))->register();

        Employee::query()->create([
            'employee_id' => 'EMP-DL-1',
            'full_name' => 'Rina Kartika',
            'branch_name' => 'PT Mahakarya Sukses Indonesia',
            'job_position' => 'Staff Finance',
            'department' => 'Finance',
            'division' => 'Finance & Accounting',
            'status_employee' => 'PKWTT',
            'join_date' => '2025-01-06',
            'resign_date' => '2026-03-31',
        ]);
    }

    private function document(string $id, string $code, string $type, string $nomor, string $issuedAt, string $employeeId = 'EMP-DL-1'): void
    {
        EmployeeDocument::query()->create([
            'document_id' => $id,
            'employee_id' => $employeeId,
            'sequence' => 7,
            'doc_type' => $type,
            'doc_code' => $code,
            'nomor' => $nomor,
            'entity' => 'MSI',
            'issued_at' => $issuedAt,
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

    public function test_export_archives_first_pdf_and_tracking_download_returns_identical_file(): void
    {
        $this->actingAsRole('Admin');
        $this->document('DOC-20260310-007-SKP', 'SKP', 'SK Pengangkatan', '007/SKP/MSI/III/2026', '2026-03-10 09:00:00');

        $first = $this->get('/hr/export/sk-pengangkatan/EMP-DL-1')->assertOk();
        $original = $first->getContent();

        $archive = EmployeeDocumentFile::query()->where('document_id', 'DOC-20260310-007-SKP')->firstOrFail();
        $this->assertSame('export', $archive->source);
        $this->assertSame(hash('sha256', $original), $archive->checksum_sha256);
        $this->assertSame('SK_Pengangkatan_EMP-DL-1.pdf', $archive->file_name);

        // Re-exporting later must not replace the first archived file.
        $this->get('/hr/export/sk-pengangkatan/EMP-DL-1')->assertOk();
        $this->assertSame(1, EmployeeDocumentFile::query()->count());
        $this->assertSame(hash('sha256', $original), $archive->fresh()->checksum_sha256);

        $download = $this->get('/hr/documents/DOC-20260310-007-SKP/download')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf')
            ->assertHeader('X-Document-Source', 'export');

        $this->assertSame($original, $download->getContent());
        $this->assertStringContainsString('SK_Pengangkatan_EMP-DL-1.pdf', (string) $download->headers->get('Content-Disposition'));
    }

    public function test_download_without_archive_regenerates_once_and_keeps_it(): void
    {
        $this->actingAsRole('Admin');
        $this->document('DOC-20260331-007-SPAK', 'SPAK', 'Paklaring', '007/SPAK/MSI/III/2026', '2026-03-31 16:00:00');

        $first = $this->get('/hr/documents/DOC-20260331-007-SPAK/download')
            ->assertOk()
            ->assertHeader('X-Document-Source', 'regenerated');

        $archive = EmployeeDocumentFile::query()->where('document_id', 'DOC-20260331-007-SPAK')->firstOrFail();
        $this->assertSame('regenerated', $archive->source);
        $this->assertSame('Paklaring_Rina_Kartika_Employee_EMP-DL-1.pdf', $archive->file_name);

        $second = $this->get('/hr/documents/DOC-20260331-007-SPAK/download')->assertOk();
        $this->assertSame($first->getContent(), $second->getContent());
    }

    public function test_regeneration_uses_document_nomor_and_issue_date(): void
    {
        $this->actingAsRole('Admin');
        $this->document('DOC-20260115-007-SKM', 'SKM', 'SK Mutasi', '007/SKM/MSI/I/2026', '2026-01-15 10:00:00');

        $pdfService = Mockery::mock(PdfGeneratorService::class);
        $pdfService->shouldReceive('generateSkRotationPdf')
            ->once()
            ->withArgs(fn (EmployeeData $employee, array $extraData) => $employee->employeeId === 'EMP-DL-1'
                && $extraData['sk_number'] === '007/SKM/MSI/I/2026'
                && $extraData['doc_date'] === '2026-01-15'
                && $extraData['rotation_type'] === 'Mutasi')
            ->andReturn(Pdf::loadHTML('<p>SK Mutasi</p>'));
        $this->app->instance(PdfGeneratorService::class, $pdfService);

        $this->get('/hr/documents/DOC-20260115-007-SKM/download')
            ->assertOk()
            ->assertHeader('X-Document-Source', 'regenerated');
    }

    public function test_regenerated_tad_contract_uses_tad_template(): void
    {
        $this->actingAsRole('Admin');
        EmployeeDocument::query()->create([
            'document_id' => 'DOC-20260901-007-PKWT',
            'employee_id' => 'EMP-DL-1',
            'sequence' => 7,
            'doc_type' => 'Kontrak PKWT TAD',
            'doc_code' => 'PKWT',
            'nomor' => '007/PKWT/MSI/IX/2026',
            'entity' => 'MSI',
            'issued_at' => '2026-09-01 09:00:00',
            'reference' => \App\Services\OutsourceContractService::TAD_REFERENCE,
        ]);

        $pdfService = Mockery::mock(PdfGeneratorService::class);
        $pdfService->shouldReceive('generateKontrakPkwtTadPdf')
            ->once()
            ->withArgs(fn (EmployeeData $employee, array $extraData) => $extraData['contract_number'] === '007/PKWT/MSI/IX/2026'
                && $extraData['doc_date'] === '2026-09-01')
            ->andReturn(Pdf::loadHTML('<p>PKWT TAD</p>'));
        $pdfService->shouldNotReceive('generateKontrakPkwtPdf');
        $this->app->instance(PdfGeneratorService::class, $pdfService);

        $download = $this->get('/hr/documents/DOC-20260901-007-PKWT/download')->assertOk();
        $this->assertStringContainsString('PKWT_TAD_Rina_Kartika_EMP-DL-1.pdf', (string) $download->headers->get('Content-Disposition'));
    }

    public function test_sk_rotation_prints_type_aware_codes(): void
    {
        $employee = EmployeeData::fromSheetRow([
            'Employee ID' => 'EMP-DL-1',
            'Full Name' => 'Rina Kartika',
            'Branch Name' => 'PT Mahakarya Sukses Indonesia',
        ]);
        $render = fn (array $extraData) => view('pdf.sk-rotation', [
            'employee' => $employee,
            'extraData' => $extraData,
            'company' => [],
        ])->render();

        $this->assertStringContainsString('Nomor: 007/SKPR/MSI/IX/2026', $render(['rotation_type' => 'Promosi', 'sk_number' => '007/SKPR/MSI/IX/2026']));
        $this->assertStringContainsString('Nomor: 007/SKD/MSI/IX/2026', $render(['rotation_type' => 'Demosi', 'sk_number' => '007/SKD/MSI/IX/2026']));
        $this->assertStringContainsString('Nomor: 007/SKM/MSI/IX/2026', $render(['rotation_type' => 'Mutasi', 'sk_number' => '007/SKM/MSI/IX/2026']));
        // Legacy HR-SK* numbers are normalized to the current code.
        $this->assertStringContainsString('Nomor: 007/SKPR/MSI/IX/2026', $render(['rotation_type' => 'Promosi', 'sk_number' => '007/HR-SKP/MSI/IX/2026']));
    }

    public function test_sk_templates_sign_with_doc_date_when_given(): void
    {
        $employee = EmployeeData::fromSheetRow([
            'Employee ID' => 'EMP-DL-1',
            'Full Name' => 'Rina Kartika',
            'Branch Name' => 'PT Mahakarya Sukses Indonesia',
        ]);

        $html = view('pdf.sk-pengangkatan', [
            'employee' => $employee,
            'extraData' => ['sk_number' => '007/SKP/MSI/III/2026', 'doc_date' => '2026-03-10'],
            'company' => ['name' => 'PT MAHAKARYA SUKSES INDONESIA', 'address' => 'Kota Tangerang', 'city' => 'Tangerang', 'code' => 'MSI'],
        ])->render();

        $this->assertStringContainsString('10 Maret 2026', $html);
        $this->assertStringContainsString('007/SKP/MSI/III/2026', $html);
    }

    public function test_paklaring_prints_only_spak_number(): void
    {
        $employee = EmployeeData::fromSheetRow([
            'Employee ID' => 'EMP-DL-1',
            'Full Name' => 'Rina Kartika',
            'Branch Name' => 'PT Mahakarya Sukses Indonesia',
            'Join Date' => '2025-01-06',
            'Resign Date' => '2026-03-31',
        ]);
        $company = ['name' => 'PT MAHAKARYA SUKSES INDONESIA', 'address' => 'Kota Tangerang', 'city' => 'Tangerang', 'code' => 'MSI'];

        $html = view('pdf.paklaring', [
            'employee' => $employee,
            'extraData' => ['sk_number' => '007/SPAK/MSI/III/2026', 'doc_date' => '2026-03-31'],
            'company' => $company,
        ])->render();
        $this->assertStringContainsString('Nomor: 007/SPAK/MSI/III/2026', $html);
        $this->assertStringContainsString('31 Maret 2026', $html);

        $legacy = view('pdf.paklaring', [
            'employee' => $employee,
            'extraData' => ['sk_number' => '007/SKO/MSI/III/2026'],
            'company' => $company,
        ])->render();
        $this->assertStringNotContainsString('007/SKO/MSI/III/2026', $legacy);
        $this->assertStringNotContainsString('Nomor:', $legacy);
    }

    public function test_surat_bpjs_never_prints_a_letter_number(): void
    {
        $employee = EmployeeData::fromSheetRow([
            'Employee ID' => 'EMP-DL-1',
            'Full Name' => 'Rina Kartika',
            'Branch Name' => 'PT Mahakarya Sukses Indonesia',
            'Join Date' => '2025-01-06',
            'Resign Date' => '2026-03-31',
        ]);

        $html = view('pdf.surat-bpjs', [
            'employee' => $employee,
            'extraData' => ['sk_number' => '007/SKO/MSI/III/2026', 'letter_number' => '007/SPAK/MSI/III/2026'],
            'company' => [],
        ])->render();

        $this->assertStringContainsString('SURAT KETERANGAN', $html);
        $this->assertStringNotContainsString('Nomor:', $html);
        $this->assertStringNotContainsString('007/SKO/MSI/III/2026', $html);
        $this->assertStringNotContainsString('007/SPAK/MSI/III/2026', $html);
    }

    public function test_regeneration_failure_redirects_back_with_message(): void
    {
        $this->actingAsRole('Admin');
        $this->document('DOC-20260201-009-SKO', 'SKO', 'SK Offboarding', '009/SKO/MSI/II/2026', '2026-02-01 08:00:00', 'EMP-MISSING');

        $this->get('/hr/documents/DOC-20260201-009-SKO/download')
            ->assertRedirect(route('hr.documents.index'))
            ->assertSessionHas('document_download_error');

        $this->assertSame(0, EmployeeDocumentFile::query()->count());
    }

    public function test_unknown_document_returns_404(): void
    {
        $this->actingAsRole('Admin');

        $this->get('/hr/documents/DOC-UNKNOWN/download')->assertNotFound();
    }

    public function test_user_without_manage_employees_cannot_download(): void
    {
        $this->document('DOC-20260310-007-SKP', 'SKP', 'SK Pengangkatan', '007/SKP/MSI/III/2026', '2026-03-10 09:00:00');
        $this->actingAsRole('User');

        $this->get('/hr/documents/DOC-20260310-007-SKP/download')->assertForbidden();
    }

    public function test_outsource_pkwt_tad_is_archived_at_generation(): void
    {
        $this->actingAsRole('Admin');
        Employee::query()->create([
            'employee_id' => 'EMP-OS-1',
            'full_name' => 'Bayu Saputra',
            'branch_name' => 'PT Mahakarya Sukses Indonesia',
            'job_position' => 'Operator',
            'status_employee' => 'Outsource',
        ]);

        $response = $this->postJson('/hr/outsource/kontrak-pkwt-tad', [
            'employee_id' => 'EMP-OS-1',
            'perusahaan' => 'PT Mitra Penempatan',
            'beralamat_di' => 'Jl. Industri No. 1, Tangerang',
            'mulai_tanggal' => '2026-09-01',
            'pendidikan' => 'SMA',
        ])->assertOk();

        $document = EmployeeDocument::query()->where('employee_id', 'EMP-OS-1')->where('doc_code', 'PKWT')->firstOrFail();
        $this->assertSame($response->json('contract_number'), $document->nomor);
        $this->assertMatchesRegularExpression('#^\d{3}/PKWT/MSI/[IVX]+/\d{4}$#', $document->nomor);
        $this->assertSame('Kontrak PKWT TAD', $document->doc_type);

        $archive = EmployeeDocumentFile::query()->where('document_id', $document->document_id)->firstOrFail();
        $this->assertSame('export', $archive->source);
        $this->assertSame('PKWT_TAD_Bayu_Saputra_EMP-OS-1.pdf', $archive->file_name);

        $this->get('/hr/documents/' . $document->document_id . '/download')
            ->assertOk()
            ->assertHeader('X-Document-Source', 'export');
    }

    public function test_offboarding_tracks_sko_paklaring_and_surat_bpjs(): void
    {
        $this->actingAsRole('Admin');
        Employee::query()->create([
            'employee_id' => 'EMP-OFF-1',
            'full_name' => 'Yuri Ismawan',
            'branch_name' => 'PT Mahakarya Sukses Indonesia',
            'job_position' => 'Staff Gudang',
            'status_employee' => 'PKWTT',
            'join_date' => '2024-02-01',
        ]);

        $this->postJson('/hr/employees/EMP-OFF-1/offboard', [
            'offboarding_type' => 'Resignation',
            'reason' => 'Melanjutkan studi',
            'last_working_date' => '2026-09-30',
        ])->assertOk()->assertJson(['success' => true]);

        $docs = EmployeeDocument::query()->where('employee_id', 'EMP-OFF-1')->orderBy('id')->get();
        $this->assertSame(['SKO', 'SPAK', 'BPJS'], $docs->pluck('doc_code')->all());
        $bpjs = $docs->firstWhere('doc_code', 'BPJS');
        $this->assertSame('', (string) $bpjs->nomor);
        $this->assertSame('Surat Keterangan BPJS', $bpjs->doc_type);
        $this->assertStringContainsString('/SPAK/', (string) Employee::query()->where('employee_id', 'EMP-OFF-1')->value('nomor_sk'));

        $this->get('/hr/export/offboarding-bundle/EMP-OFF-1')->assertOk();
        $this->assertEqualsCanonicalizing(
            $docs->pluck('document_id')->all(),
            EmployeeDocumentFile::query()->where('employee_id', 'EMP-OFF-1')->where('source', 'export')->pluck('document_id')->all()
        );

        $this->get('/hr/documents?search=EMP-OFF-1')
            ->assertOk()
            ->assertSee('SK Offboarding')
            ->assertSee('Paklaring')
            ->assertSee('Surat Keterangan BPJS')
            ->assertSee(route('hr.documents.download', ['documentId' => $bpjs->document_id]), false);

        $this->get('/hr/documents/' . $bpjs->document_id . '/download')
            ->assertOk()
            ->assertHeader('X-Document-Source', 'export')
            ->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_backfill_records_surat_bpjs_for_past_offboarding_once(): void
    {
        $this->document('DOC-20260928-001-SKO', 'SKO', 'SK Offboarding', '001/SKO/MSI/IX/2026', '2026-09-28 11:15:20');
        $this->document('DOC-20260928-001-SPAK', 'SPAK', 'Paklaring', '001/SPAK/MSI/IX/2026', '2026-09-28 11:15:20');
        $this->document('DOC-20260310-007-SKP', 'SKP', 'SK Pengangkatan', '007/SKP/MSI/III/2026', '2026-03-10 09:00:00', 'EMP-OTHER');

        $this->artisan('mito:backfill-offboarding-bpjs', ['--dry-run' => true])->assertSuccessful();
        $this->assertSame(0, EmployeeDocument::query()->where('doc_code', 'BPJS')->count());

        $this->artisan('mito:backfill-offboarding-bpjs')->assertSuccessful();
        $this->artisan('mito:backfill-offboarding-bpjs')->assertSuccessful();

        $bpjs = EmployeeDocument::query()->where('doc_code', 'BPJS')->get();
        $this->assertCount(1, $bpjs);
        $this->assertSame('EMP-DL-1', $bpjs[0]->employee_id);
        $this->assertSame('DOC-20260928-007-BPJS', $bpjs[0]->document_id);
        $this->assertSame('2026-09-28 11:15:20', $bpjs[0]->issued_at);
        $this->assertSame('', (string) $bpjs[0]->nomor);
    }

    public function test_tracking_page_shows_download_button_and_archive_status(): void
    {
        $this->actingAsRole('Admin');
        $this->document('DOC-20260310-007-SKP', 'SKP', 'SK Pengangkatan', '007/SKP/MSI/III/2026', '2026-03-10 09:00:00');

        $this->get('/hr/documents')
            ->assertOk()
            ->assertSee(route('hr.documents.download', ['documentId' => 'DOC-20260310-007-SKP']), false)
            ->assertSee('Belum diarsipkan');

        $this->get('/hr/export/sk-pengangkatan/EMP-DL-1')->assertOk();

        $this->get('/hr/documents')
            ->assertOk()
            ->assertSee('Arsip asli');
    }
}
