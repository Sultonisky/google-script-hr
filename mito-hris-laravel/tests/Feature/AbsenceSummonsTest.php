<?php

namespace Tests\Feature;

use App\DTOs\EmployeeData;
use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Models\EmployeeDocumentFile;
use App\Providers\AppServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
            'company_entity' => 'MSI',
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

        $this->postJson('/hr/employees/EMP-SPM-1/absence-summons', $this->payload([
            'company_entity' => 'UNKNOWN',
        ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['company_entity']);

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
        $this->assertStringContainsString('13:00 WIB', $html);
        $this->assertStringContainsString('Kantor HR Jakarta', $html);
        $this->assertStringContainsString('Klarifikasi Ketidakhadiran/Mangkir', $html);
        $this->assertStringContainsString('Arsip/Personal File', $html);
        $this->assertStringContainsString('PT MAHAKARYA SUKSES INDONESIA', $html);
        $this->assertStringNotContainsString('stein-pdf.png', $html);
        $this->assertStringContainsString('Nomor</td>', $identityHtml);
        $this->assertStringContainsString('007/SPM/MSI/IX/2026', $identityHtml);
        $this->assertStringContainsString('Rina Kartika', $identityHtml);
        $this->assertStringContainsString('Staff Finance (Jakarta)', $identityHtml);
        $this->assertStringContainsString('3603126603950003', $identityHtml);
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

    public function test_selected_stein_entity_uses_stein_letterhead_and_number_entity_code(): void
    {
        $this->actingAsAdmin();

        $response = $this->postJson('/hr/employees/EMP-SPM-1/absence-summons', $this->payload([
            'company_entity' => 'SPI',
        ]))
            ->assertCreated()
            ->assertJson(['success' => true, 'nomor' => '007/SPM/SPI/IX/2026']);

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

        $response = $this->postJson('/hr/employees/EMP-SPM-1/absence-summons', $this->payload([
            'level' => 'SPM2',
            'working_days' => '8',
        ]))
            ->assertCreated()
            ->assertJson(['success' => true, 'nomor' => '007/SPM/MSI/IX/2026']);

        $document = EmployeeDocument::query()
            ->where('employee_id', 'EMP-SPM-1')
            ->where('doc_type', 'Surat Penggilan Mangkir II')
            ->firstOrFail();
        $this->assertSame('Surat Penggilan Mangkir II', $document->doc_type);
        $this->assertSame('Panggilan Kerja II', $document->reference);
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
                'meeting_agenda' => 'Panggilan Kerja II',
                'first_summons_number' => $firstDocument->nomor,
                'first_summons_date' => '2026-09-29',
                'working_days' => 8,
            ],
            'company' => ['name' => 'PT MAHAKARYA SUKSES INDONESIA', 'city' => 'Tangerang', 'code' => 'MSI'],
        ])->render();

        $this->assertStringContainsString('SURAT PANGGILAN KERJA II', $html);
        $this->assertStringContainsString('Panggilan Kedua/Terakhir', $html);
        $this->assertStringContainsString('Panggilan Kerja I Nomor', $html);
        $this->assertStringContainsString($firstDocument->nomor, $html);
        $this->assertStringContainsString('29 September 2026', $html);
        $this->assertStringContainsString('8 hari kerja berturut-turut', $html);
        $this->assertStringNotContainsString('14 hari kerja berturut-turut', $html);
        $this->assertStringContainsString('Rina Kartika', $html);
        $this->assertStringContainsString('Kamis, 15 Oktober 2026', $html);
        $this->assertStringContainsString('Pasal 154A', $html);
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
            ->assertSee('name="company_entity"', false)
            ->assertSee('name="level"', false)
            ->assertSee('name="working_days"', false)
            ->assertSee('Panggilan Kerja II (Terakhir)')
            ->assertSee('PT STEIN PERKASA INTERNASIONAL')
            ->assertSee('type="date" lang="id-ID" class="form-control form-control-sm" name="doc_date"', false)
            ->assertSee('type="date" lang="id-ID" class="form-control form-control-sm" name="absence_start_date"', false)
            ->assertSee('type="date" lang="id-ID" class="form-control form-control-sm" name="absence_end_date"', false)
            ->assertSee('name="absence_second_start_date"', false)
            ->assertSee('name="absence_second_end_date"', false)
            ->assertSee('name="meeting_date"', false)
            ->assertSee('name="meeting_time"', false)
            ->assertSee('name="meeting_location"', false)
            ->assertSee('name="meeting_agenda"', false);
    }
}
