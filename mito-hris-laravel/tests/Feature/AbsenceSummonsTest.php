<?php

namespace Tests\Feature;

use App\DTOs\EmployeeData;
use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Models\EmployeeDocumentFile;
use App\Providers\AppServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Session;
use Tests\TestCase;

class AbsenceSummonsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['hris.data_driver' => 'pgsql']);
        (new AppServiceProvider($this->app))->register();

        Employee::query()->create([
            'employee_id' => 'EMP-SPM-1',
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
            'employee_id' => 'EMP-SPM-1',
            'sequence' => 7,
            'doc_type' => 'SK Pengangkatan',
            'doc_code' => 'SKP',
            'nomor' => '007/SKP/MSI/III/2026',
            'entity' => 'MSI',
            'issued_at' => '2026-03-10 09:00:00',
            'issued_by' => 'HR Admin',
        ]);
    }

    private function actingAsAdmin(): void
    {
        Session::put('hr_user', $this->migratedTestUser([
            'email' => 'admin@mito.id',
            'fullName' => 'Admin User',
            'role' => 'Admin',
            'permissions' => config('hris.auth.role_permissions.Admin', []),
            'auth_domain' => 'users',
            'entities' => [],
            'branch' => '',
        ]));
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'level' => 'SPM1',
            'doc_date' => '2026-09-29',
            'absence_start_date' => '2026-09-22',
            'absence_end_date' => '2026-09-23',
            'absence_second_start_date' => '2026-09-25',
            'absence_second_end_date' => '2026-09-26',
            'meeting_date' => '2026-10-15',
            'meeting_time' => '13:00',
            'meeting_location' => 'Kantor HR Jakarta',
            'meeting_agenda' => 'Klarifikasi Ketidakhadiran/Mangkir',
        ], $overrides);
    }

    public function test_generate_issues_spm_number_archives_pdf_and_tracks_document(): void
    {
        $this->actingAsAdmin();

        $response = $this->postJson('/hr/employees/EMP-SPM-1/absence-summons', $this->payload())
            ->assertCreated()
            ->assertJson(['success' => true, 'nomor' => '007/SPM/MSI/IX/2026']);

        $document = EmployeeDocument::query()->where('employee_id', 'EMP-SPM-1')->where('doc_code', 'SPM')->firstOrFail();
        $this->assertSame('Surat Penggilan Mangkir', $document->doc_type);
        $this->assertSame('Panggilan Kerja I', $document->reference);
        $this->assertSame('Periode mangkir: 2026-09-22 s.d. 2026-09-23; 2026-09-25 s.d. 2026-09-26', $document->notes);
        $this->assertSame(
            '007/SKP/MSI/III/2026',
            Employee::query()->where('employee_id', 'EMP-SPM-1')->value('nomor_sk')
        );

        $archive = EmployeeDocumentFile::query()->where('document_id', $document->document_id)->firstOrFail();
        $download = $this->get($response->json('pdf_url'))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');
        $this->assertSame(hash('sha256', $download->getContent()), $archive->checksum_sha256);

        $tracking = $this->get('/hr/documents/'.$document->document_id.'/download')
            ->assertOk()
            ->assertHeader('X-Document-Source', 'export');
        $this->assertSame($download->getContent(), $tracking->getContent());

        $this->get('/hr/documents?search=EMP-SPM-1')
            ->assertOk()
            ->assertSee('Surat Penggilan Mangkir')
            ->assertSee('007/SPM/MSI/IX/2026');
    }

    public function test_absence_summons_validation_and_employee_eligibility_are_enforced(): void
    {
        $this->actingAsAdmin();

        $this->postJson('/hr/employees/EMP-SPM-1/absence-summons', $this->payload([
            'absence_start_date' => '2026-10-01',
            'absence_end_date' => '2026-09-22',
            'absence_second_start_date' => '2026-10-01',
            'absence_second_end_date' => '2026-09-30',
            'meeting_date' => 'invalid',
        ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['absence_end_date', 'meeting_date']);

        $this->postJson('/hr/employees/EMP-SPM-1/absence-summons', $this->payload([
            'level' => 'SPM3',
        ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['level']);

        $this->postJson('/hr/employees/EMP-SPM-1/absence-summons', $this->payload([
            'level' => 'SPM2',
        ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['working_days']);

        Employee::query()->where('employee_id', 'EMP-SPM-1')->update(['status_employee' => 'Outsource']);
        $this->postJson('/hr/employees/EMP-SPM-1/absence-summons', $this->payload())
            ->assertStatus(422)
            ->assertJson(['success' => false]);
        $this->assertSame(0, EmployeeDocument::query()->where('doc_code', 'SPM')->count());
    }

    public function test_absence_dates_may_be_after_letter_date_and_pdf_uses_static_template_content(): void
    {
        $this->actingAsAdmin();

        $response = $this->postJson('/hr/employees/EMP-SPM-1/absence-summons', $this->payload([
            'doc_date' => '2026-09-29',
            'absence_start_date' => '2026-10-01',
            'absence_end_date' => '2026-10-03',
        ]))
            ->assertCreated()
            ->assertJson(['success' => true]);

        $this->get($response->json('pdf_url'))->assertOk();

        $html = view('pdf.surat-pemanggilan-mangkir', [
            'employee' => EmployeeData::fromSheetRow([
                'Employee ID' => 'EMP-SPM-1',
                'Full Name' => 'Rina Kartika',
                'Branch Name' => 'PT Mahakarya Sukses Indonesia',
                'Job Position' => 'Staff Finance',
                'Department' => 'Finance',
                'Status Employee' => 'PKWTT',
            ]),
            'extraData' => [
                'sk_number' => '007/SPM/MSI/IX/2026',
                'doc_date' => '2026-09-29',
                'absence_start_date' => '2026-10-01',
                'absence_end_date' => '2026-10-03',
                'absence_second_start_date' => '2026-10-06',
                'absence_second_end_date' => '2026-10-08',
                'meeting_date' => '2026-10-15',
                'meeting_time' => '13:00',
                'meeting_location' => 'Kantor HR Jakarta',
                'meeting_agenda' => 'Klarifikasi Ketidakhadiran/Mangkir',
            ],
            'company' => ['name' => 'PT MAHAKARYA SUKSES INDONESIA', 'city' => 'Tangerang'],
        ])->render();

        $identityHtml = view('pdf.surat-pemanggilan-mangkir', [
            'employee' => EmployeeData::fromSheetRow([
                'Employee ID' => 'EMP-SPM-1',
                'Full Name' => 'Rina Kartika',
                'Branch Name' => 'PT Mahakarya Sukses Indonesia',
                'Job Position (Location)' => 'Staff Finance (Jakarta)',
                'Job Position' => 'Staff Finance',
                'NIK - NPWP 16 digit' => "'3603126603950003",
                'Residential Address' => 'Jl. Mawar No. 10, Jakarta',
                'Citizen ID Address' => 'Alamat KTP',
            ]),
            'extraData' => ['sk_number' => '007/SPM/MSI/IX/2026'],
            'company' => ['name' => 'PT MAHAKARYA SUKSES INDONESIA', 'city' => 'Tangerang', 'code' => 'MSI'],
        ])->render();

        $this->assertStringContainsString('SURAT PANGGILAN KERJA I', $html);
        $this->assertStringContainsString('1 Oktober 2026 sampai dengan 3 Oktober 2026', $html);
        $this->assertStringContainsString('6 Oktober 2026 sampai dengan 8 Oktober 2026', $html);
        $this->assertStringContainsString('Kamis, 15 Oktober 2026', $html);
        $this->assertStringContainsString('13.00 WIB', $html);
        $this->assertStringContainsString('Tangerang, 29 September 2026', $html);
        $this->assertStringNotContainsString('09 September 2026', $html);
        $this->assertStringContainsString('Kantor HR Jakarta', $html);
        $this->assertStringContainsString('Klarifikasi Ketidakhadiran/Mangkir', $html);
        $this->assertStringContainsString('Arsip/Personal File', $html);
        $this->assertStringContainsString('PT MAHAKARYA SUKSES INDONESIA', $html);
        $this->assertStringNotContainsString('stein-pdf.png', $html);
        $this->assertStringContainsString('Nomor</td>', $identityHtml);
        $this->assertStringContainsString('007/SPM/MSI/IX/2026', $identityHtml);
        $this->assertStringContainsString('Rina Kartika', $identityHtml);
        $this->assertStringContainsString('Staff Finance (Jakarta)', $identityHtml);
        $this->assertStringContainsString('3603 1266 0395 0003', $identityHtml);
        $this->assertStringContainsString('Jl. Mawar No. 10, Jakarta', $identityHtml);
        $this->assertStringNotContainsString('Mila Hermawati', $identityHtml);
    }

    public function test_second_absence_period_is_optional_but_requires_valid_start_and_end_dates(): void
    {
        $this->actingAsAdmin();

        $this->postJson('/hr/employees/EMP-SPM-1/absence-summons', $this->payload([
            'absence_second_start_date' => '2026-10-06',
        ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['absence_second_end_date']);

        $html = view('pdf.surat-pemanggilan-mangkir', [
            'employee' => EmployeeData::fromSheetRow(['Employee ID' => 'EMP-SPM-1', 'Full Name' => 'Rina Kartika']),
            'extraData' => [
                'absence_start_date' => '2026-09-22',
                'absence_end_date' => '2026-09-23',
            ],
            'company' => ['name' => 'PT MAHAKARYA SUKSES INDONESIA', 'city' => 'Tangerang', 'code' => 'MSI'],
        ])->render();

        $this->assertStringContainsString('22 September 2026 sampai dengan 23 September 2026', $html);
        $this->assertStringNotContainsString('kembali tercatat tidak hadir', $html);
    }

    public function test_entity_follows_employee_branch_and_ignores_submitted_entity(): void
    {
        $this->actingAsAdmin();

        $this->postJson('/hr/employees/EMP-SPM-1/absence-summons', $this->payload([
            'company_entity' => 'SPI',
        ]))
            ->assertCreated()
            ->assertJson(['success' => true, 'nomor' => '007/SPM/MSI/IX/2026']);

        Employee::query()->where('employee_id', 'EMP-SPM-1')->update(['branch_name' => 'PT Stein Perkasa Internasional']);
        $response = $this->postJson('/hr/employees/EMP-SPM-1/absence-summons', $this->payload([
            'company_entity' => 'MSI',
        ]))
            ->assertCreated()
            ->assertJson(['success' => true, 'nomor' => '007/SPM/SPI/IX/2026']);

        $pdf = $this->get($response->json('pdf_url'))->assertOk();
        $this->assertStringStartsWith('%PDF', $pdf->getContent());

        $html = view('pdf.surat-pemanggilan-mangkir', [
            'employee' => EmployeeData::fromSheetRow([
                'Employee ID' => 'EMP-SPM-1',
                'Full Name' => 'Rina Kartika',
                'Branch Name' => 'PT Mahakarya Sukses Indonesia',
                'Job Position' => 'Staff Finance',
                'Department' => 'Finance',
                'Status Employee' => 'PKWTT',
            ]),
            'extraData' => [
                'sk_number' => '007/SPM/SPI/IX/2026',
                'doc_date' => '2026-09-29',
                'company_entity' => 'SPI',
            ],
            'company' => config('hris.mpr.companies.SPI'),
            'companyEntity' => config('hris.mpr.companies.SPI')['name'],
        ])->render();

        $this->assertStringContainsString('PT STEIN PERKASA INTERNASIONAL', $html);
        $this->assertStringContainsString('Stein Cookware', $html);
    }

    public function test_second_absence_summons_uses_second_template_and_is_tracked_separately(): void
    {
        $this->actingAsAdmin();

        $this->postJson('/hr/employees/EMP-SPM-1/absence-summons', $this->payload())
            ->assertCreated();
        $firstDocument = EmployeeDocument::query()
            ->where('employee_id', 'EMP-SPM-1')
            ->where('doc_type', 'Surat Penggilan Mangkir')
            ->firstOrFail();

        $this->postJson('/hr/employees/EMP-SPM-1/absence-summons', $this->payload([
            'level' => 'SPM2',
            'working_days' => '8',
            'absence_start_date' => '2026-09-30',
        ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['absence_start_date']);

        $secondPayload = $this->payload([
            'level' => 'SPM2',
            'working_days' => '8',
        ]);
        unset($secondPayload['absence_end_date'], $secondPayload['meeting_agenda']);
        $response = $this->postJson('/hr/employees/EMP-SPM-1/absence-summons', $secondPayload)
            ->assertCreated()
            ->assertJson(['success' => true, 'nomor' => '007/SPM/MSI/IX/2026']);

        $document = EmployeeDocument::query()
            ->where('employee_id', 'EMP-SPM-1')
            ->where('doc_type', 'Surat Penggilan Mangkir II')
            ->firstOrFail();
        $this->assertSame('Surat Penggilan Mangkir II', $document->doc_type);
        $this->assertSame('Panggilan Kerja II', $document->reference);
        $this->assertSame('Periode mangkir: 2026-09-22 s.d. 2026-09-29', $document->notes);
        $this->assertStringContainsString('-SPM-SPM2-', $document->document_id);
        $this->assertNotSame($firstDocument->document_id, $document->document_id);
        $this->assertSame(2, EmployeeDocument::query()->where('employee_id', 'EMP-SPM-1')->where('doc_code', 'SPM')->count());
        $this->assertSame($document->document_id, $response->json('document_id'));

        $this->get($response->json('pdf_url'))->assertOk();
        $html = view('pdf.surat-pemanggilan-mangkir-kedua', [
            'employee' => EmployeeData::fromSheetRow([
                'Employee ID' => 'EMP-SPM-1',
                'Full Name' => 'Rina Kartika',
                'Job Position' => 'Staff Finance',
            ]),
            'extraData' => [
                'sk_number' => '007/SPM/MSI/IX/2026',
                'doc_date' => '2026-09-29',
                'absence_start_date' => '2026-09-22',
                'absence_end_date' => '2026-09-23',
                'meeting_date' => '2026-10-15',
                'meeting_time' => '13:00',
                'meeting_location' => 'Kantor HR Jakarta',
                'meeting_agenda' => 'Agenda Tidak Dipakai',
                'first_summons_number' => $firstDocument->nomor,
                'first_summons_date' => '2026-09-09',
                'working_days' => 8,
            ],
            'company' => ['name' => 'PT MAHAKARYA SUKSES INDONESIA', 'city' => 'Tangerang', 'code' => 'MSI'],
        ])->render();

        $this->assertStringContainsString('SURAT PANGGILAN KERJA II', $html);
        $this->assertStringContainsString('Panggilan Kedua/Terakhir', $html);
        $this->assertStringContainsString('Tangerang, 29 September 2026', $html);
        $this->assertMatchesRegularExpression('/Lampiran<\/td>\s*<td class="colon">:<\/td>\s*<td>-<\/td>/', $html);
        $this->assertStringContainsString('Panggilan Kerja I Nomor '.$firstDocument->nomor.' tanggal 09 September 2026', $html);
        $this->assertMatchesRegularExpression('/sejak tanggal\s+22 September 2026 sampai dengan tanggal surat ini/', $html);
        $this->assertStringContainsString('8 hari kerja berturut-turut', $html);
        $this->assertStringNotContainsString('14 hari kerja berturut-turut', $html);
        $this->assertStringContainsString('Rina Kartika', $html);
        $this->assertMatchesRegularExpression('/Kamis, 15 Oktober 2026, pukul\s+13\.00 WIB, bertempat di Kantor HR Jakarta; dan/', $html);
        $this->assertStringContainsString('(HRD) PT Mahakarya Sukses Indonesia', $html);
        $this->assertStringNotContainsString('Agenda Tidak Dipakai', $html);
        $this->assertStringContainsString('<strong>Pasal 154A ayat (1) huruf j', $html);
        $this->assertStringContainsString('Dalam hal hubungan kerja berakhir', $html);
        $this->assertStringContainsString('untuk menjadi perhatian dan dilaksanakan sebagaimana mestinya', $html);
    }

    public function test_first_summons_pdf_follows_template_salutation_attachment_and_signature(): void
    {
        $render = fn (string $gender, array $extra = []) => view('pdf.surat-pemanggilan-mangkir', [
            'employee' => EmployeeData::fromSheetRow([
                'Employee ID' => 'EMP-SPM-1',
                'Full Name' => 'Rina Kartika',
                'Gender' => $gender,
            ]),
            'extraData' => $extra + [
                'doc_date' => '2026-09-09',
                'absence_start_date' => '2026-08-31',
                'absence_end_date' => '2026-09-04',
                'meeting_date' => '2026-09-10',
                'meeting_time' => '13:00',
                'meeting_location' => 'Kantor HR',
                'meeting_agenda' => 'Klarifikasi Ketidakhadiran/Mangkir',
            ],
            'company' => config('hris.mpr.companies.SPI'),
        ])->render();

        $female = $render('Perempuan');
        $this->assertStringContainsString('Jakarta, 09 September 2026', $female);
        $this->assertMatchesRegularExpression('/kepada\s+Saudari untuk:/', $female);
        $this->assertStringContainsString('kerja sama Saudari, kami ucapkan', $female);
        $this->assertStringContainsString('Saudara/Saudari tercatat', $female);
        $this->assertStringContainsString('<strong>31 Agustus 2026 sampai dengan 4 September 2026</strong>.', $female);
        $this->assertStringContainsString('PT Stein Perkasa Internasional', $female);
        $this->assertStringNotContainsString('draft-watermark">DRAFT', $female);

        $male = $render('Laki-laki', ['attachment_count' => 0, 'draft' => true]);
        $this->assertStringContainsString('kerja sama Saudara, kami ucapkan', $male);
        $this->assertMatchesRegularExpression('/Lampiran<\/td>\s*<td class="colon">:<\/td>\s*<td>-<\/td>/', $male);
        $this->assertStringContainsString('draft-watermark">DRAFT', $male);

        $unknown = $render('');
        $this->assertStringContainsString('kerja sama Saudara/Saudari, kami ucapkan', $unknown);
        $this->assertMatchesRegularExpression('/Lampiran<\/td>\s*<td class="colon">:<\/td>\s*<td>-<\/td>/', $unknown);
    }

    public function test_preview_renders_draft_without_issuing_number_and_is_private_to_requester(): void
    {
        $this->actingAsAdmin();

        $response = $this->postJson('/hr/employees/EMP-SPM-1/absence-summons/preview', $this->payload())
            ->assertOk()
            ->assertJson(['success' => true]);

        $previewUrl = $response->json('preview_url');
        $this->assertStringContainsString('/hr/employees/EMP-SPM-1/absence-summons/preview/', $previewUrl);
        $this->assertSame(0, EmployeeDocument::query()->where('doc_code', 'SPM')->count());
        $this->assertSame(0, EmployeeDocumentFile::query()->count());

        $pdf = $this->get($previewUrl)
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $pdf->getContent());
        $this->assertStringContainsString('inline', (string) $pdf->headers->get('Content-Disposition'));
        $this->assertStringContainsString('DRAFT_Surat_Panggilan_Kerja_I.pdf', (string) $pdf->headers->get('Content-Disposition'));

        $this->get(str_replace('EMP-SPM-1', 'EMP-OTHER', $previewUrl))->assertNotFound();

        Session::put('hr_user', $this->migratedTestUser([
            'email' => 'super.admin@mito.id',
            'fullName' => 'Super Admin User',
            'role' => 'Super Admin',
            'permissions' => [],
            'auth_domain' => 'users',
            'entities' => [],
            'branch' => '',
        ]));
        $this->get($previewUrl)->assertNotFound();
    }

    public function test_image_attachments_are_appended_as_pdf_pages_and_counted(): void
    {
        $this->actingAsAdmin();

        $response = $this->post('/hr/employees/EMP-SPM-1/absence-summons', $this->payload([
            'attachments' => [
                UploadedFile::fake()->image('rekap-absensi.jpg', 2400, 1200),
                UploadedFile::fake()->image('resi.png', 600, 900),
            ],
            'attachment_labels' => ['Rekap Absensi', ''],
        ]), ['Accept' => 'application/json'])
            ->assertCreated()
            ->assertJson(['success' => true]);

        $pdf = $this->get($response->json('pdf_url'))->assertOk()->getContent();
        $this->assertSame(3, preg_match_all('/\/Type\s*\/Page[^s]/', $pdf));

        $html = view('pdf.surat-pemanggilan-mangkir', [
            'employee' => EmployeeData::fromSheetRow(['Employee ID' => 'EMP-SPM-1', 'Full Name' => 'Rina Kartika']),
            'extraData' => [
                'attachment_count' => 2,
                'attachments' => [
                    ['label' => 'Rekap Absensi', 'src' => 'data:image/jpeg;base64,AAAA', 'width' => 1600, 'height' => 800],
                    ['label' => '', 'src' => 'data:image/jpeg;base64,BBBB', 'width' => 600, 'height' => 900],
                ],
            ],
            'company' => config('hris.mpr.companies.SPI'),
        ])->render();
        $this->assertMatchesRegularExpression('/Lampiran<\/td>\s*<td class="colon">:<\/td>\s*<td>2<\/td>/', $html);
        $this->assertStringContainsString('Lampiran 1 - Rekap Absensi', $html);
        $this->assertStringContainsString('>Lampiran 2</div>', $html);
        $this->assertStringContainsString('width: 690px; height: 345px;', $html);
        $this->assertStringContainsString('width: 566px; height: 850px;', $html);
        $this->assertSame(3, substr_count($html, 'alt="Stein Cookware"'));

        $copiesAt = strpos($html, 'Tembusan:');
        $firstAttachmentAt = strpos($html, 'class="attachment-page" style="page-break-before: always;');
        $this->assertNotFalse($firstAttachmentAt);
        $this->assertGreaterThan($copiesAt, $firstAttachmentAt);
        $this->assertGreaterThan($firstAttachmentAt, strpos($html, 'Lampiran 1 - Rekap Absensi'));
        $this->assertSame(2, substr_count($html, 'class="attachment-page"'));
    }

    public function test_attachment_images_are_downscaled_to_jpeg_and_invalid_images_rejected(): void
    {
        $service = app(\App\Services\LetterAttachmentImageService::class);

        $image = $service->prepare(UploadedFile::fake()->image('besar.png', 2400, 1200));
        $this->assertSame(1600, $image['width']);
        $this->assertSame(800, $image['height']);
        $this->assertStringStartsWith('data:image/jpeg;base64,', $image['src']);

        $small = $service->prepare(UploadedFile::fake()->image('kecil.jpg', 800, 600));
        $this->assertSame([800, 600], [$small['width'], $small['height']]);

        $this->expectException(\RuntimeException::class);
        $service->prepare(UploadedFile::fake()->createWithContent('palsu.jpg', 'bukan gambar'));
    }

    public function test_attachment_validation_rejects_non_images_oversized_and_too_many_files(): void
    {
        $this->actingAsAdmin();

        $this->post('/hr/employees/EMP-SPM-1/absence-summons/preview', $this->payload([
            'attachments' => [
                UploadedFile::fake()->create('dokumen.pdf', 100, 'application/pdf'),
                UploadedFile::fake()->image('besar.jpg')->size(6000),
            ],
        ]), ['Accept' => 'application/json'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['attachments.0', 'attachments.1']);

        $this->post('/hr/employees/EMP-SPM-1/absence-summons/preview', $this->payload([
            'attachments' => array_map(fn ($i) => UploadedFile::fake()->image("foto-{$i}.jpg", 50, 50), range(1, 6)),
        ]), ['Accept' => 'application/json'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['attachments']);

        $this->assertSame(0, EmployeeDocument::query()->where('doc_code', 'SPM')->count());
    }

    public function test_preview_uses_same_validation_and_second_summons_rules(): void
    {
        $this->actingAsAdmin();

        $this->postJson('/hr/employees/EMP-SPM-1/absence-summons/preview', $this->payload([
            'meeting_date' => '2026-09-28',
        ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['meeting_date']);

        $this->postJson('/hr/employees/EMP-SPM-1/absence-summons/preview', $this->payload([
            'level' => 'SPM2',
            'working_days' => 7,
        ]))
            ->assertStatus(422)
            ->assertJson(['success' => false]);
    }

    public function test_summons_history_is_exposed_to_modal_for_prefill(): void
    {
        $this->actingAsAdmin();
        $service = app(\App\Services\AbsenceSummonsService::class);
        $this->assertArrayNotHasKey('EMP-SPM-1', $service->historyByEmployee());

        $this->get('/hr/employees')
            ->assertOk()
            ->assertSee('id="asHistory"', false)
            ->assertSee('"summons":null', false);

        $first = $this->postJson('/hr/employees/EMP-SPM-1/absence-summons', $this->payload())->assertCreated();
        $history = $service->historyByEmployee()['EMP-SPM-1'];
        $this->assertSame('SPM1', $history['latest']['level']);
        $this->assertSame($first->json('nomor'), $history['latest']['nomor']);
        $this->assertSame(['nomor' => $first->json('nomor'), 'absence_start_date' => '2026-09-22'], [
            'nomor' => $history['first']['nomor'],
            'absence_start_date' => $history['first']['absence_start_date'],
        ]);
        $this->assertStringStartsWith('2026-09-29', $history['first']['issued_at']);

        $this->get('/hr/employees')
            ->assertOk()
            ->assertSee('"summons":{"latest":{"level":"SPM1"', false)
            ->assertSee('"absence_start_date":"2026-09-22"', false);

        $this->postJson('/hr/employees/EMP-SPM-1/absence-summons', $this->payload([
            'level' => 'SPM2',
            'working_days' => 8,
            'absence_start_date' => '2026-09-21',
        ]))->assertCreated();
        $history = $service->historyByEmployee()['EMP-SPM-1'];
        $this->assertSame('SPM2', $history['latest']['level']);
        $this->assertSame('2026-09-22', $history['first']['absence_start_date']);
    }

    public function test_second_absence_summons_requires_an_existing_first_summons(): void
    {
        $this->actingAsAdmin();

        $this->postJson('/hr/employees/EMP-SPM-1/absence-summons', $this->payload([
            'level' => 'SPM2',
            'working_days' => 7,
        ]))
            ->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Panggilan Kerja II hanya dapat diterbitkan setelah Panggilan Kerja I tercatat untuk karyawan ini.',
            ]);

        $this->assertSame(0, EmployeeDocument::query()->where('doc_code', 'SPM')->count());
    }

    public function test_employee_master_data_offers_absence_summons_action(): void
    {
        $this->actingAsAdmin();

        $this->get('/hr/employees')
            ->assertOk()
            ->assertSee('id="btnAbsenceSummons"', false)
            ->assertSee('id="absenceSummonsModal"', false)
            ->assertSee('Surat Penggilan Mangkir')
            ->assertDontSee('name="company_entity"', false)
            ->assertSee('id="asCompanyEntity" readonly', false)
            ->assertSee('"companyEntity":"PT MAHAKARYA SUKSES INDONESIA"', false)
            ->assertSee('name="level"', false)
            ->assertSee('name="working_days"', false)
            ->assertSee('Panggilan Kerja II (Terakhir)')
            ->assertSee('type="date" lang="id-ID" class="form-control form-control-sm" name="doc_date"', false)
            ->assertSee('type="date" lang="id-ID" class="form-control form-control-sm" name="absence_start_date"', false)
            ->assertSee('type="date" lang="id-ID" class="form-control form-control-sm" name="absence_end_date"', false)
            ->assertSee('name="absence_second_start_date"', false)
            ->assertSee('name="absence_second_end_date"', false)
            ->assertSee('name="meeting_date"', false)
            ->assertSee('name="meeting_time"', false)
            ->assertSee('name="meeting_location"', false)
            ->assertSee('name="meeting_agenda"', false)
            ->assertDontSee('name="attachment_count"', false)
            ->assertSee('id="btnAddAsAttachment"', false)
            ->assertSee('id="btnPreviewAbsenceSummons"', false);
    }
}
