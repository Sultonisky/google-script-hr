<?php

namespace Tests\Feature;

use App\DTOs\EmployeeData;
use App\Enums\SkDocumentType;
use App\Enums\WarningLetterLevel;
use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Models\EmployeeDocumentFile;
use App\Services\CompanyRegulationBankService;
use App\Services\PdfGeneratorService;
use App\Services\SkNumberService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
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
            ->assertJson(['success' => true, 'nomor' => '001/SP-1/MSI/IX/2026'])
            ->assertJsonPath('level', 'SP1')
            ->assertJsonPath('issued_at', '2026-09-29')
            ->assertJsonPath('short_label', 'SP-1')
            ->assertJsonPath('valid_until', '2027-03-28')
            ->assertJsonPath('valid_until_label', '28 Mar 2027');

        $document = EmployeeDocument::query()->where('employee_id', 'EMP-SP-1')->where('doc_code', 'SP')->firstOrFail();
        $this->assertSame('001/SP-1/MSI/IX/2026', $document->nomor);
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

    public function test_each_sp_letter_takes_the_next_free_number(): void
    {
        $this->actingAsRole('Admin');

        $this->postJson('/hr/employees/EMP-SP-1/warning-letter', $this->payload())
            ->assertCreated()
            ->assertJson(['nomor' => '001/SP-1/MSI/IX/2026']);

        $this->postJson('/hr/employees/EMP-SP-1/warning-letter', $this->payload([
            'doc_date' => '2026-10-15',
        ]))
            ->assertCreated()
            ->assertJson(['nomor' => '002/SP-1/MSI/X/2026']);

        $this->get('/hr/sp-tracking?search=EMP-SP-1')
            ->assertOk()
            ->assertSee('001/SP-1/MSI/IX/2026')
            ->assertSee('002/SP-1/MSI/X/2026')
            ->assertSee('Hangus (0)', false);

        $this->get('/hr/employees?search=Rina')
            ->assertOk()
            ->assertSee('SP-1 · s.d. 14 Apr 2027')
            ->assertSee('sp-sanction-more', false)
            ->assertSee('>+1<', false)
            ->assertDontSee('28 Mar 2027');

        $this->getJson('/hr/employees/EMP-SP-1/json')
            ->assertOk()
            ->assertJsonPath('warningLetter.label', 'SP-1 · s.d. 14 Apr 2027')
            ->assertJsonPath('warningLetter.extra', 1);

        $this->postJson('/hr/employees/EMP-SP-1/warning-letter', $this->payload([
            'level' => 'SP2',
            'doc_date' => '2026-10-20',
        ]))
            ->assertCreated()
            ->assertJson(['nomor' => '003/SP-2/MSI/X/2026']);

        $this->postJson('/hr/employees/EMP-SP-1/warning-letter', $this->payload([
            'level' => 'SP1T',
            'doc_date' => '2027-01-03',
        ]))
            ->assertCreated()
            ->assertJson(['nomor' => '004/SP-F/MSI/I/2027']);

        $this->assertSame(
            ['SP1', 'SP1', 'SP2', 'SP1T'],
            EmployeeDocument::query()->where('employee_id', 'EMP-SP-1')->where('doc_code', 'SP')->orderBy('issued_at')->pluck('reference')->all()
        );
        $this->assertSame(
            '007/SKP/MSI/III/2026',
            Employee::query()->where('employee_id', 'EMP-SP-1')->value('nomor_sk')
        );

        Employee::query()->create([
            'employee_id' => 'EMP-SP-2',
            'full_name' => 'Dimas Pratama',
            'branch_name' => 'PT Mahakarya Sukses Indonesia',
            'job_position' => 'Operator',
            'department' => 'Produksi',
            'status_employee' => 'PKWTT',
        ]);

        $this->postJson('/hr/employees/EMP-SP-2/warning-letter', $this->payload([
            'level' => 'SP3',
            'doc_date' => '2026-10-01',
        ]))
            ->assertCreated()
            ->assertJson(['nomor' => '005/SP-3/MSI/X/2026']);

        $this->postJson('/hr/employees/EMP-SP-2/warning-letter', $this->payload([
            'level' => 'SP1',
            'doc_date' => '2026-11-02',
        ]))
            ->assertCreated()
            ->assertJson(['nomor' => '006/SP-1/MSI/XI/2026']);

        $sk = $this->app->make(SkNumberService::class);
        $this->assertSame('007/SKM/MSI/IX/2026', $sk->issue(
            employeeId: 'EMP-SP-1',
            type: SkDocumentType::MUTASI,
            branchName: 'PT Mahakarya Sukses Indonesia',
            issuedBy: 'HR Admin',
            issuedAt: \Illuminate\Support\Carbon::parse('2026-09-29', 'Asia/Jakarta'),
        )['nomor']);
        $this->assertSame('008/SKP/MSI/X/2026', $sk->issue(
            employeeId: 'EMP-SP-2',
            type: SkDocumentType::PENGANGKATAN,
            branchName: 'PT Mahakarya Sukses Indonesia',
            issuedBy: 'HR Admin',
            issuedAt: \Illuminate\Support\Carbon::parse('2026-10-01', 'Asia/Jakarta'),
        )['nomor']);
    }

    public function test_next_sp_number_fills_the_gap_instead_of_continuing_from_an_sk_sized_number(): void
    {
        $this->actingAsRole('Admin');
        EmployeeDocument::query()->create([
            'document_id' => 'DOC-20260901-028-SP',
            'employee_id' => 'EMP-SP-1',
            'sequence' => 28,
            'doc_type' => 'Surat Peringatan Pertama (SP-1)',
            'doc_code' => 'SP',
            'nomor' => '028/SP-1/MSI/IX/2026',
            'entity' => 'MSI',
            'issued_at' => '2026-09-01 09:00:00',
            'reference' => 'SP1',
        ]);

        Employee::query()->create([
            'employee_id' => 'EMP-SP-2',
            'full_name' => 'Sinta Jumiati',
            'branch_name' => 'PT Mahakarya Sukses Indonesia',
            'job_position' => 'Staff',
            'department' => 'Finance',
            'status_employee' => 'PKWTT',
        ]);

        $this->postJson('/hr/employees/EMP-SP-2/warning-letter', $this->payload())
            ->assertCreated()
            ->assertJson(['nomor' => '001/SP-1/MSI/IX/2026']);

        Employee::query()->create([
            'employee_id' => 'EMP-SP-3',
            'full_name' => 'Karyawan Berikutnya',
            'branch_name' => 'PT Mahakarya Sukses Indonesia',
            'job_position' => 'Staff',
            'department' => 'Finance',
            'status_employee' => 'PKWTT',
        ]);

        $this->postJson('/hr/employees/EMP-SP-3/warning-letter', $this->payload(['doc_date' => '2026-10-01']))
            ->assertCreated()
            ->assertJson(['nomor' => '002/SP-1/MSI/X/2026']);

        $this->postJson('/hr/employees/EMP-SP-1/warning-letter', $this->payload([
            'level' => 'SP2',
            'doc_date' => '2026-10-01',
        ]))
            ->assertCreated()
            ->assertJson(['nomor' => '003/SP-2/MSI/X/2026']);
    }

    public function test_expired_warning_letter_is_marked_finished_and_does_not_badge_the_employee(): void
    {
        $this->actingAsRole('Admin');
        EmployeeDocument::query()->create([
            'document_id' => 'DOC-20250716-001-SP',
            'employee_id' => 'EMP-SP-1',
            'sequence' => 1,
            'doc_type' => 'Surat Peringatan Pertama (SP-1)',
            'doc_code' => 'SP',
            'nomor' => '001/SP-1/MSI/VII/2025',
            'entity' => 'MSI',
            'issued_at' => '2025-07-16 09:00:00',
            'reference' => 'SP1',
            'notes' => 'Pasal 12 — berlaku s.d. 2026-01-15',
        ]);

        $this->get('/hr/sp-tracking?search=EMP-SP-1')
            ->assertOk()
            ->assertSee('s.d. 15 Jan 2026')
            ->assertSee('Berakhir')
            ->assertSee('id="spStatActive">0', false);

        $this->get('/hr/employees?search=Rina')
            ->assertOk()
            ->assertDontSee('SP-1 · s.d.');
    }

    public function test_newer_warning_letter_voids_the_previous_sanction_window(): void
    {
        $this->actingAsRole('Admin');
        EmployeeDocument::query()->create([
            'document_id' => 'DOC-20260115-001-SP',
            'employee_id' => 'EMP-SP-1',
            'sequence' => 1,
            'doc_type' => 'Surat Peringatan Pertama (SP-1)',
            'doc_code' => 'SP',
            'nomor' => '001/SP-1/MSI/I/2026',
            'entity' => 'MSI',
            'issued_at' => '2026-01-15 09:00:00',
            'reference' => 'SP1',
            'notes' => 'Pasal 12 — berlaku s.d. 2026-07-14',
        ]);
        EmployeeDocument::query()->create([
            'document_id' => 'DOC-20260310-001-SP',
            'employee_id' => 'EMP-SP-1',
            'sequence' => 1,
            'doc_type' => 'Surat Peringatan Kedua (SP-2)',
            'doc_code' => 'SP',
            'nomor' => '001/SP-2/MSI/III/2026',
            'entity' => 'MSI',
            'issued_at' => '2026-03-10 09:00:00',
            'reference' => 'SP2',
            'notes' => 'Pasal 12 — berlaku s.d. 2027-03-09',
        ]);

        \Illuminate\Support\Carbon::setTestNow(\Illuminate\Support\Carbon::parse('2026-05-01', 'Asia/Jakarta'));
        try {
            $this->get('/hr/sp-tracking?search=EMP-SP-1')
                ->assertOk()
                ->assertSeeInOrder(['001/SP-2/MSI/III/2026', 'Aktif', '001/SP-1/MSI/I/2026', 'Hangus'])
                ->assertSee('id="spStatActive">1', false);

            $this->get('/hr/employees?search=Rina')
                ->assertOk()
                ->assertSee('SP-2 · s.d. 09 Mar 2027')
                ->assertDontSee('SP-1 · s.d. 14 Jul 2026');

            \Illuminate\Support\Carbon::setTestNow(\Illuminate\Support\Carbon::parse('2027-04-01', 'Asia/Jakarta'));

            $this->get('/hr/sp-tracking?search=EMP-SP-1')
                ->assertOk()
                ->assertSeeInOrder(['001/SP-2/MSI/III/2026', 'Berakhir', '001/SP-1/MSI/I/2026', 'Hangus'])
                ->assertSee('id="spStatActive">0', false);

            $this->get('/hr/employees?search=Rina')
                ->assertOk()
                ->assertDontSee('SP-2 · s.d.')
                ->assertDontSee('SP-1 · s.d.');
        } finally {
            \Illuminate\Support\Carbon::setTestNow();
        }
    }

    public function test_employee_status_filter_lists_everyone_who_has_a_warning_letter(): void
    {
        $this->actingAsRole('Admin');
        EmployeeDocument::query()->create([
            'document_id' => 'DOC-20250716-001-SP',
            'employee_id' => 'EMP-SP-1',
            'sequence' => 1,
            'doc_type' => 'Surat Peringatan Pertama (SP-1)',
            'doc_code' => 'SP',
            'nomor' => '001/SP-1/MSI/VII/2025',
            'entity' => 'MSI',
            'issued_at' => '2025-07-16 09:00:00',
            'reference' => 'SP1',
            'notes' => 'Pasal 12 — berlaku s.d. 2026-01-15',
        ]);
        EmployeeDocument::query()->create([
            'document_id' => 'DOC-20250801-001-SP',
            'employee_id' => 'EMP-SP-1',
            'sequence' => 1,
            'doc_type' => 'Surat Peringatan Kedua (SP-2)',
            'doc_code' => 'SP',
            'nomor' => '001/SP-2/MSI/VIII/2025',
            'entity' => 'MSI',
            'issued_at' => '2025-08-01 09:00:00',
            'reference' => 'SP2',
            'notes' => 'Pasal 12 — berlaku s.d. 2026-07-31',
        ]);
        Employee::query()->create([
            'employee_id' => 'EMP-SP-2',
            'full_name' => 'Sinta Jumiati',
            'branch_name' => 'PT Mahakarya Sukses Indonesia',
            'job_position' => 'Staff',
            'department' => 'Finance',
            'status_employee' => 'PKWTT',
        ]);
        EmployeeDocument::query()->create([
            'document_id' => 'DOC-20261001-002-SP',
            'employee_id' => 'EMP-SP-2',
            'sequence' => 2,
            'doc_type' => 'Surat Peringatan Pertama (SP-1)',
            'doc_code' => 'SP',
            'nomor' => '002/SP-1/MSI/X/2026',
            'entity' => 'MSI',
            'issued_at' => '2026-10-01 09:00:00',
            'reference' => 'SP1',
            'notes' => 'Pasal 12 — berlaku s.d. 2027-03-31',
        ]);
        Employee::query()->create([
            'employee_id' => 'EMP-SP-CLEAR',
            'full_name' => 'Karyawan Tanpa Sanksi',
            'branch_name' => 'PT Mahakarya Sukses Indonesia',
            'job_position' => 'Staff',
            'department' => 'Finance',
            'status_employee' => 'PKWTT',
        ]);

        $response = $this->get('/hr/employees?status=warning_letter')
            ->assertOk()
            ->assertSee('Surat Peringatan (2)', false)
            ->assertSee('value="warning_letter" selected', false)
            ->assertSee('dari 2 data');

        preg_match('/<tbody id="empTableBody">.*?<\/tbody>/s', $response->getContent(), $table);
        $this->assertStringContainsString('Rina Kartika', $table[0] ?? '');
        $this->assertStringContainsString('Sinta Jumiati', $table[0] ?? '');
        $this->assertStringNotContainsString('Karyawan Tanpa Sanksi', $table[0] ?? '');
    }

    public function test_pdf_is_rendered_with_employee_branch_letterhead(): void
    {
        $this->actingAsRole('Admin');
        Employee::query()->where('employee_id', 'EMP-SP-1')->update(['branch_name' => 'PT Stein Perkasa Internasional']);

        $pdfService = Mockery::mock(PdfGeneratorService::class)->makePartial();
        $pdfService->shouldReceive('generateWarningLetterPdf')
            ->once()
            ->withArgs(fn (EmployeeData $employee, array $extraData) => $employee->branchName === 'PT Stein Perkasa Internasional'
                && $extraData['sk_number'] === '001/SP-2/SPI/IX/2026'
                && $extraData['level'] === 'SP2'
                && $extraData['validity_months'] === 12
                && $extraData['valid_until'] === '2027-09-28')
            ->andReturn(Pdf::loadHTML('<p>SP-2</p>'));
        $this->app->instance(PdfGeneratorService::class, $pdfService);

        // Masa berlaku statis per tingkat; nilai dari klien diabaikan.
        $this->postJson('/hr/employees/EMP-SP-1/warning-letter', $this->payload(['level' => 'SP2', 'validity_months' => 3]))
            ->assertCreated()
            ->assertJson(['nomor' => '001/SP-2/SPI/IX/2026']);
    }

    public function test_sp1_final_is_valid_for_one_year_and_recorded_as_its_own_level(): void
    {
        $this->actingAsRole('Admin');

        $response = $this->postJson('/hr/employees/EMP-SP-1/warning-letter', $this->payload(['level' => 'SP1T']))
            ->assertCreated()
            ->assertJson(['success' => true, 'nomor' => '001/SP-F/MSI/IX/2026']);

        $document = EmployeeDocument::query()->where('employee_id', 'EMP-SP-1')->where('doc_code', 'SP')->firstOrFail();
        $this->assertSame('Surat Peringatan Pertama dan Terakhir (SP-1 & Terakhir)', $document->doc_type);
        $this->assertSame('SP1T', $document->reference);
        $this->assertSame('001/SP-F/MSI/IX/2026', $document->nomor);
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
                'sk_number' => '001/SP/MSI/IX/2026',
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
        $this->assertStringContainsString('color: #eb1c24', $html);
        $this->assertStringContainsString('border-top: 1.5px solid #000000', $html);
        $this->assertStringNotContainsString('top: -90px', $html);
        $this->assertStringContainsString('Nomor: 001/SP/MSI/IX/2026', $html);
        $this->assertStringContainsString('Dengan ini diberikan Surat Peringatan Tertulis Pertama dan Terakhir (SP1 dan Terakhir) kepada:', $plainText);
        $this->assertStringContainsString('Berdasarkan pelanggaran-pelanggaran tersebut, Perusahaan memberikan Surat Peringatan Tertulis Pertama dan Terakhir (SP1 dan Terakhir) kepada Saudara sebagai bentuk pembinaan dan penegakan disiplin kerja.', $plainText);
        $this->assertStringContainsString('berlaku selama 1 (satu) tahun sesuai dengan ketentuan Pasal 47 ayat (1) Peraturan Perusahaan.', $plainText);
        $this->assertStringContainsString('Saudara wajib memperbaiki kedisiplinan, mematuhi waktu kerja yang telah ditentukan', $plainText);
        $this->assertStringContainsString('termasuk Pemutusan Hubungan Kerja (PHK)', $plainText);
        $this->assertStringContainsString('Demikian Surat Peringatan Tertulis Pertama dan Terakhir ini diberikan untuk menjadi perhatian dan dilaksanakan dengan penuh tanggung jawab.', $plainText);
        $this->assertStringContainsString('1 (satu) tahun', $html);
        $this->assertStringContainsString('Pemutusan Hubungan Kerja (PHK)', $html);
        $this->assertStringNotContainsString('Surat Peringatan Tertulis ke 1', $html);
        $this->assertStringContainsString('Human Resources Manager', $html);
        $this->assertStringContainsString('<strong>Surat Peringatan Tertulis Pertama dan Terakhir (SP1 dan Terakhir)</strong>', $html);
        $this->assertStringContainsString('<strong>Surat Peringatan Tertulis Pertama dan Terakhir</strong>', $html);
        $this->assertStringContainsString('<strong>1 (satu) tahun</strong>', $html);
        $this->assertStringContainsString('<strong>Pasal 47 ayat (1) Peraturan Perusahaan</strong>', $html);
    }

    private function finalViolationsPayload(): array
    {
        return [
            [
                'description' => 'Tidak melaksanakan perintah dan/atau instruksi Atasan dalam penyelesaian permasalahan pekerjaan.',
                'references' => [
                    [
                        'regulation_type' => 'Peraturan Perusahaan',
                        'article_number' => '40',
                        'paragraph_number' => '12',
                        'article_letter' => '',
                        'article_text' => 'Karyawan wajib mematuhi perintah dan/atau instruksi baik secara lisan maupun tertulis dari Atasannya atau Direksi demi kelancaran pekerjaan.',
                    ],
                ],
            ],
            [
                'description' => 'Tidak melaksanakan tugas sesuai dengan waktu yang telah ditentukan',
                'references' => [
                    [
                        'regulation_type' => 'Peraturan Perusahaan',
                        'article_number' => '40',
                        'paragraph_number' => '2',
                        'article_text' => 'Setiap Karyawan wajib melaksanakan pekerjaan dengan penuh tanggung jawab.',
                    ],
                    [
                        'regulation_type' => 'Peraturan Perusahaan',
                        'article_number' => '40',
                        'paragraph_number' => '3',
                        'article_text' => 'Setiap Karyawan wajib hadir di tempat kerja dan mulai bekerja tepat pada waktu yang telah ditentukan.',
                    ],
                    ['regulation_type' => '', 'article_number' => '', 'paragraph_number' => '', 'article_text' => ''],
                ],
            ],
        ];
    }

    public function test_sp1_final_accepts_violation_items_with_article_quotes(): void
    {
        $this->actingAsRole('Admin');

        $pdfService = Mockery::mock(PdfGeneratorService::class)->makePartial();
        $pdfService->shouldReceive('generateWarningLetterPdf')
            ->once()
            ->withArgs(fn (EmployeeData $employee, array $extraData) => $extraData['level'] === 'SP1T'
                && count($extraData['violations']) === 2
                && count($extraData['violations'][1]['references']) === 2
                && $extraData['violations'][0]['references'][0]['article_text'] !== ''
                && str_starts_with($extraData['violation_description'], 'Tidak melaksanakan perintah'))
            ->andReturn(Pdf::loadHTML('<p>SP-1 & Terakhir</p>'));
        $this->app->instance(PdfGeneratorService::class, $pdfService);

        $this->postJson('/hr/employees/EMP-SP-1/warning-letter', [
            'level' => 'SP1T',
            'doc_date' => '2026-09-29',
            'violation_category' => '',
            'superior_position' => 'Branch Manager Jabo',
            'violations' => $this->finalViolationsPayload(),
        ])->assertCreated();

        $document = EmployeeDocument::query()->where('employee_id', 'EMP-SP-1')->where('doc_code', 'SP')->firstOrFail();
        $this->assertStringContainsString(
            'Pasal 40 ayat (12) Peraturan Perusahaan; Pasal 40 ayat (2) Peraturan Perusahaan; Pasal 40 ayat (3) Peraturan Perusahaan',
            (string) $document->notes
        );
    }

    public function test_sp1_final_violation_items_require_description_and_article(): void
    {
        $this->actingAsRole('Admin');

        $response = $this->postJson('/hr/employees/EMP-SP-1/warning-letter', [
            'level' => 'SP1T',
            'doc_date' => '2026-09-29',
            'superior_position' => 'Branch Manager Jabo',
            'violations' => [
                [
                    'description' => 'pendek',
                    'references' => [
                        ['regulation_type' => 'Peraturan Perusahaan', 'article_number' => '', 'article_text' => 'Bunyi pasal'],
                    ],
                ],
                ['description' => 'Tidak hadir tanpa keterangan selama dua hari kerja.', 'references' => []],
            ],
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'violations.0.description',
                'violations.0.references.0.article_number',
                'violations.1.references',
            ]);
        $errors = $response->json('errors');
        $this->assertSame('Isi nomor pasal pada pelanggaran ke-1, pasal ke-1.', $errors['violations.0.references.0.article_number'][0]);
        $this->assertSame('Pelanggaran ke-2 wajib memiliki minimal satu pasal.', $errors['violations.1.references'][0]);

        $this->assertSame(0, EmployeeDocument::query()->where('doc_code', 'SP')->count());
    }

    public function test_sp1_final_template_renders_violation_items_like_provided_letter(): void
    {
        $html = view('pdf.surat-peringatan-pertama', [
            'employee' => $this->employeeData(),
            'extraData' => [
                'sk_number' => '001/SP/MSI/IX/2026',
                'doc_date' => '2026-09-29',
                'level' => 'SP1T',
                'violation_description' => 'tidak dipakai bila rincian tersedia',
                'regulation_reference' => 'Pasal 40 ayat (12) Peraturan Perusahaan',
                'violations' => $this->finalViolationsPayload(),
                'validity_months' => 12,
                'superior_position' => 'Branch Manager Jabo',
            ],
            'company' => [],
        ])->render();

        $plainText = preg_replace('/\s+/', ' ', strip_tags($html));
        $this->assertStringContainsString('Tata Tertib dan Peraturan Perusahaan, berupa:', $plainText);
        $this->assertStringContainsString(
            'Tidak melaksanakan perintah dan/atau instruksi Atasan dalam penyelesaian permasalahan pekerjaan, sebagaimana diatur dalam Pasal 40 ayat (12) Peraturan Perusahaan, yang menyatakan:',
            $plainText
        );
        $this->assertStringContainsString('<strong>Pasal 40 ayat (12)</strong> Peraturan Perusahaan', $html);
        $this->assertStringContainsString('"Karyawan wajib mematuhi perintah dan/atau instruksi', $html);
        $this->assertStringContainsString(
            'sebagaimana diatur dalam Pasal 40 ayat (2) dan Pasal 40 ayat (3) Peraturan Perusahaan, yang menyatakan:',
            $plainText
        );
        $this->assertStringContainsString('<div class="article-heading">Pasal 40 ayat (2):</div>', $html);
        $this->assertStringContainsString('<div class="article-heading">Pasal 40 ayat (3):</div>', $html);
        $this->assertStringNotContainsString('tidak dipakai bila rincian tersedia', $html);
        $this->assertStringNotContainsString('PT MAHAKARYA SUKSES INDONESIA<br>', $html);
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
        $this->assertStringStartsWith(
            'Pasal 46 ayat (1) huruf e; Pasal 47 ayat (1) — berlaku',
            (string) $document->notes
        );
    }

    public function test_sp1_uses_superior_name_from_form_and_omits_regulation_type(): void
    {
        $this->actingAsRole('Admin');

        $pdfService = Mockery::mock(PdfGeneratorService::class)->makePartial();
        $pdfService->shouldReceive('generateWarningLetterPdf')
            ->once()
            ->withArgs(fn (EmployeeData $employee, array $extraData) => $extraData['superior_name'] === 'M. Sigit Trisetyo'
                && $extraData['superior_position'] === 'Branch Manager Lampung'
                && $extraData['regulation_reference'] === 'Pasal 46 ayat (1) huruf e')
            ->andReturn(Pdf::loadHTML('<p>SP-1</p>'));
        $this->app->instance(PdfGeneratorService::class, $pdfService);

        $this->postJson('/hr/employees/EMP-SP-1/warning-letter', $this->payload([
            'regulation_reference' => '',
            'regulation_references' => [
                ['article_number' => '46', 'paragraph_number' => '1', 'article_letter' => 'e'],
            ],
            'superior_name' => 'M. Sigit Trisetyo',
            'superior_position' => 'Branch Manager Lampung',
        ]))->assertCreated();

        $html = view('pdf.surat-peringatan-pertama', [
            'employee' => $this->employeeData(),
            'extraData' => [
                'level' => 'SP1',
                'doc_date' => '2026-09-29',
                'violation_description' => 'Hasil kerja tidak memenuhi kualifikasi yang ditentukan.',
                'regulation_reference' => 'Pasal 46 ayat (1) huruf e',
                'superior_name' => 'M. Sigit Trisetyo',
                'superior_position' => 'Branch Manager Lampung',
            ],
            'company' => [],
        ])->render();

        $this->assertStringContainsString('<strong>Pasal 46 ayat (1) huruf e:</strong>', $html);
        $this->assertStringNotContainsString('Peraturan Perusahaan:', $html);
        $this->assertStringContainsString('M. Sigit Trisetyo', $html);
        $this->assertStringNotContainsString('Budi Santoso', $html);
    }

    public function test_bank_selected_citation_is_canonicalized_before_generating_sp(): void
    {
        $this->actingAsRole('Admin');
        Storage::fake('local');
        Storage::disk('local')->put('company-data/peraturan-perusahaan.json', json_encode([
            'bab' => [[
                'nomor' => 'IX',
                'judul' => 'Tata Tertib Uji',
                'pasal' => [[
                    'nomor' => '46',
                    'judul' => 'Tindakan Disiplin Uji',
                    'ayat' => [[
                        'type' => 'ayat',
                        'nomor' => '1',
                        'teks' => 'Teks ayat resmi.',
                        'sub' => [['type' => 'ayat', 'nomor' => 'a', 'teks' => 'Teks subhuruf resmi.']],
                    ]],
                ]],
            ]],
        ], JSON_THROW_ON_ERROR));

        $pdfService = Mockery::mock(PdfGeneratorService::class)->makePartial();
        $pdfService->shouldReceive('generateWarningLetterPdf')
            ->once()
            ->withArgs(fn (EmployeeData $employee, array $extraData) => $extraData['regulation_reference']
                === 'Bab IX (Tata Tertib Uji) Pasal 46 (Tindakan Disiplin Uji) ayat (1) huruf a')
            ->andReturn(Pdf::loadHTML('<p>SP-1</p>'));
        $this->app->instance(PdfGeneratorService::class, $pdfService);

        $this->postJson('/hr/employees/EMP-SP-1/warning-letter', $this->payload([
            'regulation_reference' => '',
            'regulation_references' => [[
                'chapter_number' => 'IX',
                'chapter_title' => 'Judul Bab Palsu',
                'article_number' => '46',
                'article_title' => 'Judul Pasal Palsu',
                'paragraph_number' => '1',
                'article_letter' => 'a',
            ]],
        ]))->assertCreated();
    }

    public function test_regulation_citations_support_bab_titles_and_lettered_paragraphs(): void
    {
        $this->actingAsRole('Admin');
        Storage::fake('local');
        Storage::disk('local')->put('company-data/peraturan-perusahaan.json', json_encode([
            'bab' => [[
                'nomor' => 'IX',
                'judul' => 'Tata Tertib dan Aturan Kedisiplinan',
                'pasal' => [[
                    'nomor' => '46',
                    'judul' => 'Perbuatan Yang Berakibat Surat Peringatan',
                    'ayat' => [['type' => 'ayat', 'nomor' => 'a', 'teks' => 'Contoh pelanggaran uji.']],
                ]],
            ]],
        ], JSON_THROW_ON_ERROR));

        $pdfService = Mockery::mock(PdfGeneratorService::class)->makePartial();
        $pdfService->shouldReceive('generateWarningLetterPdf')
            ->once()
            ->withArgs(fn (EmployeeData $employee, array $extraData) => $extraData['regulation_reference']
                === 'Bab IX (Tata Tertib dan Aturan Kedisiplinan) Pasal 46 (Perbuatan Yang Berakibat Surat Peringatan) huruf a')
            ->andReturn(Pdf::loadHTML('<p>SP-1</p>'));
        $this->app->instance(PdfGeneratorService::class, $pdfService);

        $this->postJson('/hr/employees/EMP-SP-1/warning-letter', $this->payload([
            'regulation_reference' => '',
            'regulation_references' => [[
                'chapter_number' => 'IX',
                'chapter_title' => 'Tata Tertib dan Aturan Kedisiplinan',
                'article_number' => '46',
                'article_title' => 'Perbuatan Yang Berakibat Surat Peringatan',
                'paragraph_number' => 'a',
            ]],
        ]))->assertCreated();

        $html = view('pdf.surat-peringatan-pertama', [
            'employee' => $this->employeeData(),
            'extraData' => [
                'level' => 'SP1T',
                'doc_date' => '2026-09-29',
                'violations' => [[
                    'description' => 'Pelanggaran terhadap ketentuan perusahaan yang berlaku.',
                    'references' => [[
                        'regulation_type' => 'Peraturan Perusahaan',
                        'chapter_number' => 'IX',
                        'chapter_title' => 'Tata Tertib dan Aturan Kedisiplinan',
                        'article_number' => '46',
                        'article_title' => 'Perbuatan Yang Berakibat Surat Peringatan',
                        'paragraph_number' => '1',
                        'article_letter' => 'e',
                        'article_text' => 'Meninggalkan tempat dan/atau area kerja tanpa izin atasan.',
                    ]],
                ]],
                'validity_months' => 12,
            ],
            'company' => [],
        ])->render();

        $this->assertStringContainsString(
            '<strong>Bab IX (Tata Tertib dan Aturan Kedisiplinan) Pasal 46 (Perbuatan Yang Berakibat Surat Peringatan) ayat (1) huruf e</strong>',
            $html
        );
    }

    public function test_nested_numbered_pp_reference_is_canonicalized_and_rendered_in_the_pdf(): void
    {
        $this->actingAsRole('Admin');
        Storage::fake('local');
        Storage::disk('local')->put('company-data/peraturan-perusahaan.json', json_encode([
            'bab' => [[
                'nomor' => 'II',
                'judul' => 'Tata Tertib',
                'pasal' => [[
                    'nomor' => '6',
                    'judul' => 'Kewajiban Karyawan',
                    'ayat' => [[
                        'type' => 'ayat',
                        'nomor' => '2',
                        'teks' => 'Karyawan wajib mematuhi ketentuan berikut:',
                        'sub' => [[
                            'type' => 'ayat',
                            'nomor' => 'b',
                            'sub' => [[
                                'type' => 'ayat',
                                'nomor' => '7',
                                'teks' => 'Ketentuan huruf b nomor 7.',
                            ]],
                        ]],
                    ]],
                ]],
            ]],
        ], JSON_THROW_ON_ERROR));

        $pdfService = Mockery::mock(PdfGeneratorService::class)->makePartial();
        $pdfService->shouldReceive('generateWarningLetterPdf')
            ->once()
            ->withArgs(fn (EmployeeData $employee, array $extraData) => $extraData['regulation_reference']
                === 'Bab II (Tata Tertib) Pasal 6 (Kewajiban Karyawan) ayat (2) huruf b nomor 7 Peraturan Perusahaan'
                && $extraData['violations'][0]['references'][0]['article_text'] === 'Ketentuan huruf b nomor 7.')
            ->andReturn(Pdf::loadHTML('<p>SP-1 & Terakhir</p>'));
        $this->app->instance(PdfGeneratorService::class, $pdfService);

        $this->postJson('/hr/employees/EMP-SP-1/warning-letter', [
            'level' => 'SP1T',
            'doc_date' => '2026-09-29',
            'superior_position' => 'Branch Manager Jabo',
            'violations' => [[
                'description' => 'Tidak mematuhi ketentuan perusahaan yang berlaku.',
                'references' => [[
                    'regulation_type' => 'Peraturan Perusahaan',
                    'chapter_number' => 'II',
                    'chapter_title' => 'Judul Bab Palsu',
                    'article_number' => '6',
                    'article_title' => 'Judul Pasal Palsu',
                    'paragraph_number' => '2',
                    'article_letter' => 'b',
                    'subparagraph_number' => '7',
                    'article_text' => 'Teks palsu dari browser.',
                ]],
            ]],
        ])->assertCreated();

        $html = view('pdf.surat-peringatan-pertama', [
            'employee' => $this->employeeData(),
            'extraData' => [
                'level' => 'SP1T',
                'doc_date' => '2026-09-29',
                'violations' => [[
                    'description' => 'Tidak mematuhi ketentuan perusahaan yang berlaku.',
                    'references' => [[
                        'regulation_type' => 'Peraturan Perusahaan',
                        'chapter_number' => 'II',
                        'chapter_title' => 'Tata Tertib',
                        'article_number' => '6',
                        'article_title' => 'Kewajiban Karyawan',
                        'paragraph_number' => '2',
                        'article_letter' => 'b',
                        'subparagraph_number' => '7',
                        'article_text' => 'Ketentuan huruf b nomor 7.',
                    ]],
                ]],
                'validity_months' => 12,
            ],
            'company' => [],
        ])->render();

        $this->assertStringContainsString(
            '<strong>Bab II (Tata Tertib) Pasal 6 (Kewajiban Karyawan) ayat (2) huruf b nomor 7</strong>',
            $html
        );
    }

    public function test_sp1_sp2_and_sp3_resolve_violation_quotes_from_the_pp_bank(): void
    {
        $this->actingAsRole('Admin');
        Storage::fake('local');
        Storage::disk('local')->put('company-data/peraturan-perusahaan.json', json_encode([
            'bab' => [[
                'nomor' => 'II',
                'judul' => 'Tata Tertib',
                'pasal' => [[
                    'nomor' => '6',
                    'judul' => 'Kewajiban Karyawan',
                    'ayat' => [[
                        'type' => 'ayat',
                        'nomor' => '2',
                        'teks' => 'Karyawan wajib mematuhi ketentuan berikut:',
                        'sub' => [[
                            'type' => 'ayat',
                            'nomor' => 'b',
                            'sub' => [[
                                'type' => 'ayat',
                                'nomor' => '7',
                                'teks' => 'Ketentuan huruf b nomor 7.',
                            ]],
                        ]],
                    ]],
                ]],
            ]],
        ], JSON_THROW_ON_ERROR));

        $pdfService = Mockery::mock(PdfGeneratorService::class)->makePartial();
        $pdfService->shouldReceive('generateWarningLetterPdf')
            ->times(3)
            ->withArgs(function (EmployeeData $employee, array $extraData) {
                $reference = $extraData['violations'][0]['references'][0] ?? [];

                return in_array($extraData['level'], ['SP1', 'SP2', 'SP3'], true)
                    && ($reference['chapter_title'] ?? '') === 'Tata Tertib'
                    && ($reference['article_title'] ?? '') === 'Kewajiban Karyawan'
                    && ($reference['article_text'] ?? '') === 'Ketentuan huruf b nomor 7.'
                    && ($extraData['regulation_reference'] ?? '') === 'Bab II (Tata Tertib) Pasal 6 (Kewajiban Karyawan) ayat (2) huruf b nomor 7 Peraturan Perusahaan';
            })
            ->andReturn(Pdf::loadHTML('<p>SP</p>'));
        $this->app->instance(PdfGeneratorService::class, $pdfService);

        foreach (['SP1', 'SP2', 'SP3'] as $level) {
            $this->postJson('/hr/employees/EMP-SP-1/warning-letter', [
                'level' => $level,
                'doc_date' => '2026-09-29',
                'superior_name' => 'Budi Santoso',
                'superior_position' => 'Branch Manager',
                'violations' => [[
                    'description' => 'Tidak mematuhi ketentuan perusahaan yang berlaku.',
                    'references' => [[
                        'regulation_type' => 'Peraturan Perusahaan',
                        'chapter_number' => 'II',
                        'chapter_title' => 'Judul Bab Palsu',
                        'article_number' => '6',
                        'article_title' => 'Judul Pasal Palsu',
                        'paragraph_number' => '2',
                        'article_letter' => 'b',
                        'subparagraph_number' => '7',
                        'article_text' => 'Teks palsu dari browser.',
                    ]],
                ]],
            ])->assertCreated();
        }

        $html = view('pdf.surat-peringatan-pertama', [
            'employee' => $this->employeeData(),
            'extraData' => [
                'level' => 'SP2',
                'doc_date' => '2026-09-29',
                'violations' => [[
                    'description' => 'Tidak mematuhi ketentuan perusahaan yang berlaku.',
                    'references' => [[
                        'regulation_type' => 'Peraturan Perusahaan',
                        'chapter_number' => 'II',
                        'chapter_title' => 'Tata Tertib',
                        'article_number' => '6',
                        'article_title' => 'Kewajiban Karyawan',
                        'paragraph_number' => '2',
                        'article_letter' => 'b',
                        'subparagraph_number' => '7',
                        'article_text' => 'Ketentuan huruf b nomor 7.',
                    ]],
                ]],
                'validity_months' => 12,
                'superior_position' => 'Branch Manager',
            ],
            'company' => [],
        ])->render();

        $this->assertStringContainsString('Surat Peringatan Tertulis ke 2', $html);
        $this->assertStringContainsString('Ketentuan huruf b nomor 7.', $html);
        $this->assertStringContainsString(
            '<strong>Bab II (Tata Tertib) Pasal 6 (Kewajiban Karyawan) ayat (2) huruf b nomor 7</strong>',
            $html
        );
    }

    public function test_preview_renders_draft_without_issuing_number_or_archiving(): void
    {
        $this->actingAsRole('Admin');

        $response = $this->postJson('/hr/employees/EMP-SP-1/warning-letter/preview', [
            'level' => 'SP1T',
            'doc_date' => '2026-09-29',
            'superior_name' => 'Mardiansyah Matondang',
            'superior_position' => 'Branch Manager Jabo',
            'violations' => $this->finalViolationsPayload(),
        ])
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertSame(0, EmployeeDocument::query()->where('doc_code', 'SP')->count());
        $this->assertSame(0, EmployeeDocumentFile::query()->count());

        $previewUrl = (string) $response->json('preview_url');
        $this->assertStringContainsString('/hr/employees/EMP-SP-1/warning-letter/preview/', $previewUrl);

        $pdf = $this->get($previewUrl)
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('inline;', (string) $pdf->headers->get('Content-Disposition'));
        $this->assertStringStartsWith('%PDF', $pdf->getContent());

        $this->actingAsRole('Super Admin');
        $this->get($previewUrl)->assertNotFound();
        $this->get('/hr/employees/EMP-SP-1/warning-letter/preview/token-tidak-ada')->assertNotFound();

        // Nomor pertama tetap tersedia untuk penerbitan sebenarnya.
        $this->actingAsRole('Admin');
        $this->postJson('/hr/employees/EMP-SP-1/warning-letter', $this->payload())
            ->assertCreated()
            ->assertJson(['nomor' => '001/SP-1/MSI/IX/2026']);
    }

    public function test_preview_returns_validation_errors_and_forbids_other_roles(): void
    {
        $this->actingAsRole('Admin');
        $this->postJson('/hr/employees/EMP-SP-1/warning-letter/preview', $this->payload([
            'violation_description' => 'pendek',
        ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['violation_description']);

        $this->actingAsRole('User');
        $this->postJson('/hr/employees/EMP-SP-1/warning-letter/preview', $this->payload())->assertForbidden();
    }

    public function test_draft_templates_show_watermark_without_number(): void
    {
        foreach (['pdf.surat-peringatan-pertama' => 'SP1', 'pdf.surat-peringatan' => 'SP2'] as $view => $level) {
            $html = view($view, [
                'employee' => $this->employeeData(),
                'extraData' => [
                    'sk_number' => '',
                    'doc_date' => '2026-09-29',
                    'level' => $level,
                    'violation_category' => 'Kedisiplinan & Kehadiran',
                    'violation_description' => 'Terlambat masuk kerja lebih dari 30 menit.',
                    'regulation_reference' => 'Pasal 46 ayat (1) huruf e',
                    'valid_until' => '2027-09-28',
                    'draft' => true,
                ],
                'company' => [],
            ])->render();

            $this->assertStringContainsString('<div class="draft-watermark">DRAFT</div>', $html, $view);
            $this->assertStringNotContainsString('Nomor:', $html, $view);
        }
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
                'article_number',
                'paragraph_number',
                'article_letter',
            ])
            ->assertJsonMissingValidationErrors(['regulation_type']);

        $this->postJson('/hr/employees/EMP-SP-1/warning-letter', $this->payload([
            'level' => 'SP1T',
            'regulation_reference' => '',
            'regulation_type' => 'Aturan tidak dikenal',
            'article_number' => '46',
        ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['regulation_type']);

        $this->assertSame(0, EmployeeDocument::query()->where('doc_code', 'SP')->count());
    }

    public function test_sp2_formats_structured_regulation_reference_without_type(): void
    {
        $this->actingAsRole('Admin');

        $pdfService = Mockery::mock(PdfGeneratorService::class)->makePartial();
        $pdfService->shouldReceive('generateWarningLetterPdf')
            ->once()
            ->withArgs(fn (EmployeeData $employee, array $extraData) => $extraData['regulation_reference'] === 'Pasal 40 ayat (2)')
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
                'sk_number' => '001/SP/SPI/IX/2026',
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
        $this->assertStringContainsString('Nomor: 001/SP/SPI/IX/2026', $html);
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
                'sk_number' => '001/SP/MSI/IX/2026',
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
        $this->assertStringContainsString('color: #eb1c24', $html);
        $this->assertStringNotContainsString('top: -90px', $html);
        $this->assertStringContainsString('Nomor: 001/SP/MSI/IX/2026', $html);
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

        foreach (['SP2', 'SP3'] as $level) {
            $this->postJson('/hr/employees/EMP-SP-1/warning-letter', $this->payload([
                'level' => $level,
                'violation_category' => '',
                'regulation_reference' => '',
                'superior_position' => '',
            ]))
                ->assertStatus(422)
                ->assertJsonValidationErrors(['regulation_reference', 'superior_position'])
                ->assertJsonMissingValidationErrors(['violation_category']);
        }
    }

    public function test_sp2_and_sp3_follow_sp1_letter_format_with_level_specific_consequence(): void
    {
        $render = fn (string $level) => preg_replace('/\s+/', ' ', strip_tags(view('pdf.surat-peringatan-pertama', [
            'employee' => $this->employeeData(),
            'extraData' => [
                'sk_number' => '001/SP/MSI/IX/2026',
                'doc_date' => '2026-09-29',
                'level' => $level,
                'violation_description' => 'Tidak hadir tanpa keterangan selama dua hari kerja.',
                'regulation_reference' => 'Pasal 46 ayat (1) huruf e',
                'validity_months' => 12,
                'superior_name' => 'Budi Santoso',
                'superior_position' => 'Branch Manager',
            ],
            'company' => [],
        ])->render()));

        $sp2 = $render('SP2');
        $this->assertStringContainsString('SURAT PERINGATAN TERTULIS', $sp2);
        $this->assertStringContainsString('Dengan ini diberikan Surat Peringatan Tertulis ke 2 Kepada:', $sp2);
        $this->assertStringContainsString('Pasal 46 ayat (1) huruf e: Tidak hadir tanpa keterangan', $sp2);
        $this->assertStringContainsString('Surat Peringatan Tertulis ke 2 ini berlaku 1 (satu) tahun', $sp2);
        $this->assertStringContainsString('maka Perusahaan dapat memberikan Surat Peringatan Tertulis ke 3 sesuai dengan', $sp2);
        $this->assertStringContainsString('Human Resources &amp; Legal Manager', $sp2);
        $this->assertStringNotContainsString('Tindakan Perbaikan', $sp2);

        $sp3 = $render('SP3');
        $this->assertStringContainsString('Dengan ini diberikan Surat Peringatan Tertulis ke 3 Kepada:', $sp3);
        $this->assertStringContainsString('Surat Peringatan Tertulis ke 3 ini merupakan peringatan terakhir dan berlaku 1 (satu) tahun', $sp3);
        $this->assertStringContainsString('maka Perusahaan dapat melakukan Pemutusan Hubungan Kerja (PHK) sesuai dengan', $sp3);
    }

    public function test_sp3_is_generated_with_shared_letter_template(): void
    {
        $this->actingAsRole('Admin');

        $pdfService = Mockery::mock(PdfGeneratorService::class)->makePartial();
        $pdfService->shouldReceive('generateWarningLetterPdf')
            ->once()
            ->withArgs(fn (EmployeeData $employee, array $extraData) => $extraData['level'] === 'SP3'
                && $extraData['regulation_reference'] === 'Pasal 46 ayat (1)'
                && $extraData['superior_name'] === 'Budi Santoso')
            ->andReturn(Pdf::loadHTML('<p>SP-3</p>'));
        $this->app->instance(PdfGeneratorService::class, $pdfService);

        $this->postJson('/hr/employees/EMP-SP-1/warning-letter', $this->payload([
            'level' => 'SP3',
            'violation_category' => '',
            'regulation_reference' => '',
            'regulation_references' => [
                ['regulation_type' => 'Peraturan Perusahaan', 'article_number' => '46', 'paragraph_number' => '1'],
            ],
            'superior_name' => 'Budi Santoso',
        ]))->assertCreated();

        $document = EmployeeDocument::query()->where('employee_id', 'EMP-SP-1')->where('doc_code', 'SP')->firstOrFail();
        $this->assertSame('SP3', $document->reference);
        $this->assertStringStartsWith('Pasal 46 ayat (1) — berlaku', (string) $document->notes);
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
                    'message' => 'Surat Peringatan hanya dapat diterbitkan untuk karyawan berstatus Contract atau Permanent.',
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
        $regulationBank = Mockery::mock(CompanyRegulationBankService::class);
        $regulationBank->shouldReceive('chaptersOrEmpty')->atLeast()->once()->andReturn([[
            'number' => 'IX',
            'title' => 'Bab Disiplin Uji',
            'articles' => [[
                'number' => '46',
                'title' => 'Pasal Uji',
                'text' => 'Teks ayat uji.',
                'paragraphs' => [[
                    'number' => '1',
                    'text' => 'Teks ayat uji.',
                    'sub' => [],
                ]],
            ]],
        ]]);
        $this->app->instance(CompanyRegulationBankService::class, $regulationBank);

        $this->get('/hr/employees')
            ->assertOk()
            ->assertSeeInOrder(['id="wlDescriptionField"', 'id="wlRegulationField"'], false)
            ->assertSee('id="btnWarningLetter"', false)
            ->assertSee('id="warningLetterModal"', false)
            ->assertSee('id="wlCorrectiveSection"', false)
            ->assertSee('id="wlCategoryField"', false)
            ->assertSee('id="wlSuperiorPosition"', false)
            ->assertSee('id="wlSuperiorName"', false)
            ->assertSee('id="btnPreviewWarningLetter"', false)
            ->assertSee('id="wlSuperiorOptions"', false)
            ->assertSee('data-regulation-field="regulation_type"', false)
            ->assertSee('data-regulation-field="chapter_number"', false)
            ->assertSee('data-pp-field="chapter"', false)
            ->assertSee('data-regulation-field="chapter_title"', false)
            ->assertSee('data-regulation-field="article_number"', false)
            ->assertSee('data-pp-field="article"', false)
            ->assertSee('data-regulation-field="article_title"', false)
            ->assertSee('data-regulation-field="paragraph_number"', false)
            ->assertSee('data-pp-field="paragraph"', false)
            ->assertSee('data-regulation-field="article_letter"', false)
            ->assertSee('data-pp-field="subparagraph"', false)
            ->assertSee('data-regulation-field="subparagraph_number"', false)
            ->assertSee('id="wlAddRegulation"', false)
            ->assertSee('id="wlViolationSection"', false)
            ->assertSee('data-ref-field="chapter_number"', false)
            ->assertSee('data-ref-field="subparagraph_number"', false)
            ->assertSee('data-ref-field="chapter_title"', false)
            ->assertSee('data-ref-field="article_title"', false)
            ->assertSee('var ppChapters =', false)
            ->assertSee('Bab Disiplin Uji', false)
            ->assertSee('data-ref-field="article_text"', false)
            ->assertSee('data-pp-field="clause-text"', false)
            ->assertSee("articleText.value = nested ? nested.text : (sub ? sub.text : (node ? node.text : (selected ? selected.text : '')));", false)
            ->assertSee('id="wlAddViolation"', false)
            ->assertSee('Bab, judul, ayat/huruf dan kutipan opsional.', false)
            ->assertDontSee("setFieldsDisabled(el('wlViolationSection'), !isFinalLevel(el('wlLevel').value));", false)
            ->assertSee('id="wlEmpLocation"', false)
            ->assertDontSee('id="wlRegulationType"', false)
            ->assertDontSee('id="wlArticleNumber"', false)
            ->assertDontSee('id="wlParagraphNumber"', false)
            ->assertDontSee('id="wlArticleLetter"', false)
            ->assertSee('Surat Peringatan Pertama (SP-1)')
            ->assertSee('rememberIssuedLetter', false);

        $this->postJson('/hr/employees/EMP-SP-1/warning-letter', $this->payload())->assertCreated();

        $this->get('/hr/sp-tracking?search=EMP-SP-1')
            ->assertOk()
            ->assertSee('Surat Peringatan Pertama (SP-1)')
            ->assertSee('001/SP-1/MSI/IX/2026')
            ->assertSee('Masa Berlaku')
            ->assertSee('s.d. 28 Mar 2027')
            ->assertSee('Aktif')
            ->assertSee('id="spStatActive">1', false);

        $employeePage = $this->get('/hr/employees?search=Rina')
            ->assertOk()
            ->assertSee('SP-1 · s.d. 28 Mar 2027')
            ->assertSee('badge-status blacklist sp-sanction', false)
            ->assertSee('Surat Peringatan<strong>', false);
        preg_match('/<tbody id="empTableBody">(.*?)<\/tbody>/s', $employeePage->getContent(), $tableBody);
        $this->assertStringNotContainsString('sp-sanction-more', $tableBody[1] ?? '');

        $this->getJson('/hr/employees/EMP-SP-1/json')
            ->assertOk()
            ->assertJsonPath('warningLetter.label', 'SP-1 · s.d. 28 Mar 2027')
            ->assertJsonPath('warningLetter.extra', 0);

        Employee::query()->create([
            'employee_id' => 'EMP-SP-CLEAR',
            'full_name' => 'Karyawan Tanpa Sanksi',
            'branch_name' => 'PT Mahakarya Sukses Indonesia',
            'job_position' => 'Staff',
            'department' => 'Finance',
            'status_employee' => 'PKWTT',
        ]);

        $this->get('/hr/employees?search=Karyawan+Tanpa+Sanksi')
            ->assertOk()
            ->assertSee('Karyawan Tanpa Sanksi')
            ->assertDontSee('s.d. 28 Mar 2027');

        $this->get('/hr/documents?search=EMP-SP-1')
            ->assertOk()
            ->assertDontSee('Surat Peringatan Pertama (SP-1)')
            ->assertDontSee('001/SP-1/MSI/IX/2026');
    }
}
