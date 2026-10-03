<?php

namespace Tests\Feature;

use App\DTOs\EmployeeData;
use App\Enums\WarningLetterLevel;
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
            'violation_category' => 'Kedisiplinan & Kehadiran',
            'incident_date' => '2026-09-22',
            'violation_description' => 'Terlambat masuk kerja lebih dari 30 menit sebanyak 5 kali dalam satu bulan.',
            'regulation_reference' => 'Peraturan Perusahaan Pasal 12 ayat (3)',
            'superior_position' => 'Atasan Langsung',
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
                && $extraData['validity_months'] === 12
                && $extraData['valid_until'] === '2027-09-28')
            ->andReturn(Pdf::loadHTML('<p>SP-2</p>'));
        $this->app->instance(PdfGeneratorService::class, $pdfService);

        // Masa berlaku statis per tingkat; nilai dari klien diabaikan.
        $this->postJson('/hr/employees/EMP-SP-1/warning-letter', $this->payload(['level' => 'SP2', 'validity_months' => 3]))
            ->assertCreated()
            ->assertJson(['nomor' => '007/SP/SPI/IX/2026']);
    }

    public function test_sp1_final_is_valid_for_one_year_and_recorded_as_its_own_level(): void
    {
        $this->actingAsRole('Admin');

        $response = $this->postJson('/hr/employees/EMP-SP-1/warning-letter', $this->payload(['level' => 'SP1T']))
            ->assertCreated()
            ->assertJson(['success' => true, 'nomor' => '007/SP/MSI/IX/2026']);

        $document = EmployeeDocument::query()->where('employee_id', 'EMP-SP-1')->where('doc_code', 'SP')->firstOrFail();
        $this->assertSame('Surat Peringatan Pertama dan Terakhir (SP-1 & Terakhir)', $document->doc_type);
        $this->assertSame('SP1T', $document->reference);
        $this->assertSame('007/SP/MSI/IX/2026', $document->nomor);
        $this->assertStringContainsString('Peraturan Perusahaan Pasal 12 ayat (3)', (string) $document->notes);
        $this->assertStringContainsString('berlaku s.d. 2027-09-28', (string) $document->notes);
        $this->assertSame('Surat_Peringatan_SP1T_Rina_Kartika_Employee_EMP-SP-1.pdf', $response->json('file_name'));

        $this->postJson('/hr/employees/EMP-SP-1/warning-letter', $this->payload([
            'level' => 'SP1T',
            'violation_category' => '',
            'regulation_reference' => '',
            'superior_position' => '',
        ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['regulation_reference', 'superior_position'])
            ->assertJsonMissingValidationErrors(['violation_category']);
    }

    public function test_sp1_final_template_states_final_warning_valid_one_year(): void
    {
        $html = view('pdf.surat-peringatan-pertama', [
            'employee' => $this->employeeData(),
            'extraData' => [
                'sk_number' => '007/SP/MSI/IX/2026',
                'doc_date' => '2026-09-29',
                'level' => 'SP1T',
                'violation_description' => 'Menggunakan aset perusahaan untuk kepentingan pribadi.',
                'regulation_reference' => 'Pasal 46 ayat 2',
                'validity_months' => 12,
                'superior_position' => 'Branch Manager',
            ],
            'company' => [],
        ])->render();

        $plainText = preg_replace('/\s+/', ' ', strip_tags($html));
        $this->assertStringContainsString('<td colspan="2" class="signature-date">Tangerang, 29 September 2026</td>', $html);
        $this->assertStringContainsString('<td class="employee-sign">', $html);
        $this->assertLessThan(strpos($html, 'Yang Bersangkutan'), strpos($html, 'class="signature-date"'));
        $this->assertStringContainsString('SURAT PERINGATAN TERTULIS', $html);
        $this->assertStringContainsString('Nomor: 007/SP/MSI/IX/2026', $html);
        $this->assertStringContainsString('Dengan ini diberikan Surat Peringatan Tertulis Pertama dan Terakhir (SP1 dan Terakhir) kepada:', $html);
        $this->assertStringContainsString('Berdasarkan pelanggaran-pelanggaran tersebut, Perusahaan memberikan Surat Peringatan Tertulis Pertama dan Terakhir (SP1 dan Terakhir) kepada Saudara sebagai bentuk pembinaan dan penegakan disiplin kerja.', $plainText);
        $this->assertStringContainsString('berlaku selama 1 (satu) tahun sesuai dengan ketentuan Pasal 47 ayat (1) Peraturan Perusahaan.', $plainText);
        $this->assertStringContainsString('Saudara wajib memperbaiki kedisiplinan, mematuhi waktu kerja yang telah ditentukan', $plainText);
        $this->assertStringContainsString('termasuk Pemutusan Hubungan Kerja (PHK)', $plainText);
        $this->assertStringContainsString('Demikian Surat Peringatan Tertulis Pertama dan Terakhir ini diberikan untuk menjadi perhatian dan dilaksanakan dengan penuh tanggung jawab.', $plainText);
        $this->assertStringContainsString('1 (satu) tahun', $html);
        $this->assertStringContainsString('Pemutusan Hubungan Kerja (PHK)', $html);
        $this->assertStringNotContainsString('Surat Peringatan Tertulis ke 1', $html);
        $this->assertStringContainsString('Human Resources Manager', $html);
    }

    public function test_structured_regulation_fields_are_formatted_for_warning_letter(): void
    {
        $this->actingAsRole('Admin');

        $payload = $this->payload([
            'regulation_reference' => '',
            'regulation_references' => [
                [
                    'regulation_type' => 'Peraturan Perusahaan',
                    'article_number' => '46',
                    'paragraph_number' => '1',
                    'article_letter' => 'E',
                ],
                [
                    'regulation_type' => 'Peraturan Perusahaan',
                    'article_number' => '47',
                    'paragraph_number' => '1',
                    'article_letter' => '',
                ],
            ],
        ]);

        $this->postJson('/hr/employees/EMP-SP-1/warning-letter', $payload)
            ->assertCreated();

        $document = EmployeeDocument::query()->where('employee_id', 'EMP-SP-1')->where('doc_code', 'SP')->firstOrFail();
        $this->assertStringContainsString(
            'Pasal 46 ayat (1) huruf e Peraturan Perusahaan; Pasal 47 ayat (1) Peraturan Perusahaan',
            (string) $document->notes
        );
    }

    public function test_structured_regulation_fields_reject_invalid_values(): void
    {
        $this->actingAsRole('Admin');

        $this->postJson('/hr/employees/EMP-SP-1/warning-letter', $this->payload([
            'regulation_reference' => '',
            'regulation_type' => 'Aturan tidak dikenal',
            'article_number' => '0',
            'paragraph_number' => '1.5',
            'article_letter' => 'ab',
        ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'regulation_type',
                'article_number',
                'paragraph_number',
                'article_letter',
            ]);

        $this->assertSame(0, EmployeeDocument::query()->where('doc_code', 'SP')->count());
    }

    public function test_sp2_accepts_optional_structured_regulation_reference(): void
    {
        $this->actingAsRole('Admin');

        $pdfService = Mockery::mock(PdfGeneratorService::class)->makePartial();
        $pdfService->shouldReceive('generateWarningLetterPdf')
            ->once()
            ->withArgs(fn (EmployeeData $employee, array $extraData) => $extraData['regulation_reference'] === 'Pasal 40 ayat (2) Peraturan Perusahaan')
            ->andReturn(Pdf::loadHTML('<p>SP-2</p>'));
        $this->app->instance(PdfGeneratorService::class, $pdfService);

        $this->postJson('/hr/employees/EMP-SP-1/warning-letter', $this->payload([
            'level' => 'SP2',
            'regulation_reference' => '',
            'regulation_references' => [
                [
                    'regulation_type' => 'Peraturan Perusahaan',
                    'article_number' => '40',
                    'paragraph_number' => '2',
                    'article_letter' => '',
                ],
            ],
        ]))
            ->assertCreated();
    }

    public function test_sp3_requires_complete_reference_when_structured_reference_is_started(): void
    {
        $this->actingAsRole('Admin');

        $this->postJson('/hr/employees/EMP-SP-1/warning-letter', $this->payload([
            'level' => 'SP3',
            'regulation_reference' => '',
            'regulation_references' => [
                [
                    'regulation_type' => 'Peraturan Perusahaan',
                    'article_number' => '',
                    'paragraph_number' => '1.5',
                    'article_letter' => 'ab',
                ],
            ],
        ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'regulation_references.0.article_number',
                'regulation_references.0.paragraph_number',
                'regulation_references.0.article_letter',
            ]);
    }

    public function test_validity_is_static_per_level(): void
    {
        $this->assertSame(6, WarningLetterLevel::SP1->validityMonths());
        $this->assertSame(12, WarningLetterLevel::SP1_FINAL->validityMonths());
        $this->assertSame(12, WarningLetterLevel::SP2->validityMonths());
        $this->assertSame(12, WarningLetterLevel::SP3->validityMonths());
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
                'regulation_reference' => 'Pasal 40 ayat (2) Peraturan Perusahaan',
                'corrective_actions' => "- Hadir tepat waktu\n2. Melapor ke atasan",
                'validity_months' => 12,
                'valid_until' => '2027-09-28',
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
        $this->assertStringContainsString('Pasal 40 ayat (2) Peraturan Perusahaan', $html);
        $this->assertStringContainsString('<li>Hadir tepat waktu</li>', $html);
        $this->assertStringContainsString('<li>Melapor ke atasan</li>', $html);
        $this->assertStringContainsString('1 (satu) tahun', $html);
        $this->assertStringContainsString('28 September 2027', $html);
        $this->assertStringContainsString('Surat Peringatan Ketiga (SP-3)', $html);
        $this->assertStringContainsString('Jakarta, 29 September 2026', $html);
        $this->assertStringContainsString('Tanggal: 29 September 2026', $html);
        $this->assertStringNotContainsString('Tanggal: ____', $html);
        $this->assertStringContainsString('Hisar Hesti', $html);
        $this->assertStringContainsString('Atasan Langsung (Budi Santoso)', $html);
        $this->assertStringNotContainsString('hr-sign-img', $html);
    }

    public function test_sp1_template_matches_provided_letter_and_renders_dynamic_employee_and_violation_data(): void
    {
        $company = [
            'name' => 'PT MAHAKARYA SUKSES INDONESIA',
            'address' => 'Jl. Gajah Tunggal, Tangerang',
            'city' => 'Tangerang',
            'code' => 'MSI',
        ];
        $employee = EmployeeData::fromSheetRow([
            'Employee ID' => 'EMP-SP-1',
            'Full Name' => 'Willy Yohan',
            'Job Position' => 'GT MT Sales Associate',
            'Lokasi Kerja' => 'Lampung',
            'NIK - NPWP 16 digit' => "'1871090706790002",
            'Direct Superior' => 'M. Sigit Trisetyo',
        ]);
        $html = view('pdf.surat-peringatan-pertama', [
            'employee' => $employee,
            'extraData' => [
                'sk_number' => '007/SP/MSI/IX/2026',
                'doc_date' => '2026-09-29',
                'level' => 'SP1',
                'violation_category' => 'Kinerja & Target Kerja',
                'incident_date' => '2026-09-22',
                'violation_description' => 'Hasil kerja tidak memenuhi kualifikasi yang ditentukan.',
                'regulation_reference' => 'Pasal 46 ayat 1 huruf e',
                'validity_months' => 6,
                'superior_position' => 'Branch Manager Lampung',
            ],
            'company' => $company,
        ])->render();

        $this->assertStringContainsString('SURAT PERINGATAN TERTULIS', $html);
        $this->assertStringContainsString('Nomor: 007/SP/MSI/IX/2026', $html);
        $this->assertStringContainsString('Dengan ini diberikan Surat Peringatan Tertulis ke 1 Kepada:', $html);
        $this->assertStringContainsString('Willy Yohan', $html);
        $this->assertStringContainsString('1871090706790002', $html);
        $this->assertStringContainsString('GT MT Sales Associate', $html);
        $this->assertStringContainsString('Lampung', $html);
        $this->assertStringContainsString('Pasal 46 ayat 1 huruf e', $html);
        $this->assertStringContainsString('Hasil kerja tidak memenuhi kualifikasi yang ditentukan.', $html);
        $this->assertStringContainsString('Tanggal kejadian: 22 September 2026', $html);
        $this->assertStringContainsString('6 (enam) bulan', $html);
        $this->assertStringContainsString('Tangerang, 29 September 2026', $html);
        $this->assertStringContainsString('<td colspan="2" class="signature-date">Tangerang, 29 September 2026</td>', $html);
        $this->assertStringContainsString('<td class="employee-sign">', $html);
        $this->assertLessThan(
            strpos($html, 'Yang Bersangkutan'),
            strpos($html, 'class="signature-date"')
        );
        $this->assertStringContainsString('M. Sigit Trisetyo', $html);
        $this->assertStringContainsString('Hisar Hesti', $html);
        $this->assertStringContainsString('Branch Manager Lampung', $html);
        $this->assertStringNotContainsString('Employee ID', $html);
        $this->assertStringNotContainsString('III. Masa Berlaku dan Konsekuensi', $html);
        $this->assertStringNotContainsString('hr-sign-img', $html);
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
        ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['level', 'violation_category', 'violation_description', 'incident_date']);

        $this->assertSame(0, EmployeeDocument::query()->where('doc_code', 'SP')->count());
    }

    public function test_sp1_requires_article_and_superior_position_but_not_sp_category(): void
    {
        $this->actingAsRole('Admin');

        $this->postJson('/hr/employees/EMP-SP-1/warning-letter', $this->payload([
            'violation_category' => '',
            'regulation_reference' => '',
            'superior_position' => '',
        ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['regulation_reference', 'superior_position'])
            ->assertJsonMissingValidationErrors(['violation_category']);

        $this->postJson('/hr/employees/EMP-SP-1/warning-letter', $this->payload([
            'level' => 'SP2',
            'violation_category' => '',
            'regulation_reference' => '',
            'superior_position' => '',
        ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['violation_category']);
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
            ->assertSee('id="wlCorrectiveSection"', false)
            ->assertSee('id="wlCategoryField"', false)
            ->assertSee('id="wlSuperiorPosition"', false)
            ->assertSee('data-regulation-field="regulation_type"', false)
            ->assertSee('data-regulation-field="article_number"', false)
            ->assertSee('data-regulation-field="paragraph_number"', false)
            ->assertSee('data-regulation-field="article_letter"', false)
            ->assertSee('id="wlAddRegulation"', false)
            ->assertSee('SP-2/SP-3: dasar ketentuan opsional', false)
            ->assertSee('id="wlEmpLocation"', false)
            ->assertDontSee('id="wlRegulationType"', false)
            ->assertDontSee('id="wlArticleNumber"', false)
            ->assertDontSee('id="wlParagraphNumber"', false)
            ->assertDontSee('id="wlArticleLetter"', false)
            ->assertSee('Surat Peringatan Pertama (SP-1)');

        $this->postJson('/hr/employees/EMP-SP-1/warning-letter', $this->payload())->assertCreated();

        $this->get('/hr/documents?search=EMP-SP-1')
            ->assertOk()
            ->assertSee('Surat Peringatan Pertama (SP-1)')
            ->assertSee('007/SP/MSI/IX/2026');
    }
}
