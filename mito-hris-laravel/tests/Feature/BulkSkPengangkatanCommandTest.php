<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Models\EmployeeDocumentFile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;
use ZipArchive;

class BulkSkPengangkatanCommandTest extends TestCase
{
    use RefreshDatabase;

    private string $workDir;

    protected function setUp(): void
    {
        parent::setUp();
        config(['hris.data_driver' => 'pgsql']);
        (new \App\Providers\AppServiceProvider($this->app))->register();
        Carbon::setTestNow(Carbon::parse('2026-10-06 10:00:00', 'Asia/Jakarta'));

        $this->workDir = sys_get_temp_dir() . '/bulk-skp-test-' . uniqid();
        mkdir($this->workDir);

        foreach ([
            ['20200603', 'Yulia Budiasih', 'PT Mahakarya Sukses Indonesia', 'Admin Associate (Cibinong)', 'RnD & Aftersales'],
            ['20110901', 'Erizal Subiyanto', 'PT Stein Perkasa Internasional', 'Engineering Associate (Semarang)', 'RnD & Aftersales'],
            ['20190101', 'Sudah Tetap', 'PT Mahakarya Sukses Indonesia', 'Sales Associate', 'Sales'],
        ] as [$id, $name, $branch, $position, $department]) {
            Employee::query()->create([
                'employee_id' => $id,
                'full_name' => $name,
                'branch_name' => $branch,
                'job_position_location' => $position,
                'job_position' => $position,
                'department' => $department,
                'division' => $department,
                'status_employee' => 'Permanent',
            ]);
        }

        $this->document('DOC-20260101-001-PKWT', '20190101', 1, 'PKWT', '001/PKWT/MSI/I/2026');
        $this->document('DOC-20260102-001-SKP', '20190101', 1, 'SKP', '001/SKP/MSI/I/2026');
        $this->document('DOC-20260103-002-PKWT', 'EMP-LAIN', 2, 'PKWT', '002/PKWT/MSI/I/2026');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        foreach (glob($this->workDir . '/*') ?: [] as $file) {
            @unlink($file);
        }
        @rmdir($this->workDir);
        parent::tearDown();
    }

    private function document(string $id, string $employeeId, int $sequence, string $code, string $nomor): void
    {
        EmployeeDocument::query()->create([
            'document_id' => $id,
            'employee_id' => $employeeId,
            'sequence' => $sequence,
            'doc_type' => $code,
            'doc_code' => $code,
            'nomor' => $nomor,
            'entity' => 'MSI',
            'issued_at' => '2026-01-01 09:00:00',
            'issued_by' => 'HR Admin',
        ]);
    }

    private function workbook(): string
    {
        $book = new Spreadsheet();
        $sheet = $book->getActiveSheet();
        $sheet->setTitle('karyawan tetap');
        $sheet->fromArray([
            ['Employee ID', 'Full Name', 'Organization', 'Job Position', 'Job Level', 'Status Employee', 'Branch Name'],
            ['20200603', 'Yulia Budiasih', 'RnD & Aftersales', 'Admin Associate (Cibinong)', 'Associate', 'Permanent', 'PT Mahakarya Sukses Indonesia'],
            ['20110901', 'Erizal Subiyanto', 'RnD & Aftersales', 'Senior Engineer (Semarang)', 'Associate', 'Permanent', 'PT Stein Perkasa Internasional'],
            ['20190101', 'Sudah Tetap', 'Sales', 'Sales Associate', 'Associate', 'Permanent', 'PT Mahakarya Sukses Indonesia'],
            ['99999999', 'Tidak Ada', 'Sales', 'Sales Associate', 'Associate', 'Permanent', 'PT Mahakarya Sukses Indonesia'],
        ], null, 'A1', true);
        $sheet->setCellValueExplicit('A2', '20200603', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);

        $path = $this->workDir . '/karyawan-tetap.xlsx';
        (new Xlsx($book))->save($path);

        return $path;
    }

    public function test_dry_run_plans_numbers_from_last_sequence_without_writing(): void
    {
        $this->artisan('mito:bulk-sk-pengangkatan', [
            'file' => $this->workbook(),
            '--output' => $this->workDir,
            '--dry-run' => true,
        ])
            ->expectsOutputToContain('003/SKP/MSI/X/2026')
            ->expectsOutputToContain('004/SKP/SPI/X/2026')
            ->expectsOutputToContain('Employee ID tidak ditemukan di HRIS')
            ->expectsOutputToContain('Sudah punya SK Pengangkatan')
            ->assertSuccessful();

        $this->assertSame(3, EmployeeDocument::query()->count());
        $this->assertSame(0, EmployeeDocumentFile::query()->count());
        $reports = glob($this->workDir . '/*_dry-run.csv');
        $this->assertCount(1, $reports);
        $this->assertStringContainsString('Jabatan HRIS: Engineering Associate (Semarang)', file_get_contents($reports[0]));
        $this->assertCount(0, glob($this->workDir . '/*.zip'));
    }

    public function test_issues_numbers_archives_pdfs_and_builds_zip_without_changing_status(): void
    {
        $this->artisan('mito:bulk-sk-pengangkatan', [
            'file' => $this->workbook(),
            '--output' => $this->workDir,
            '--issued-by' => 'hr@mito.id',
            '--force' => true,
        ])->assertFailed();

        $issued = EmployeeDocument::query()->where('doc_code', 'SKP')->where('employee_id', '!=', '20190101')
            ->orderBy('sequence')->get();
        $this->assertSame(['003/SKP/MSI/X/2026', '004/SKP/SPI/X/2026'], $issued->pluck('nomor')->all());
        $this->assertSame(['20200603', '20110901'], $issued->pluck('employee_id')->all());
        $this->assertSame('hr@mito.id', $issued->first()->issued_by);
        $this->assertSame(1, EmployeeDocument::query()->where('employee_id', '20190101')->where('doc_code', 'SKP')->count());

        $files = EmployeeDocumentFile::query()->orderBy('nomor')->get();
        $this->assertSame($issued->pluck('document_id')->all(), $files->pluck('document_id')->all());
        $this->assertSame('export', $files->first()->source);
        $this->assertSame('SK_Pengangkatan_20200603.pdf', $files->first()->file_name);
        $this->assertStringStartsWith('%PDF', $files->first()->content());

        $employee = Employee::query()->where('employee_id', '20200603')->first();
        $this->assertSame('Permanent', $employee->status_employee);
        $this->assertSame('003/SKP/MSI/X/2026', $employee->nomor_sk);

        $zips = glob($this->workDir . '/*.zip');
        $this->assertCount(1, $zips);
        $zip = new ZipArchive();
        $zip->open($zips[0]);
        $this->assertSame(2, $zip->numFiles);
        $this->assertSame('003_SK_Pengangkatan_Yulia_Budiasih_20200603.pdf', $zip->getNameIndex(0));
        $this->assertSame($files->first()->content(), $zip->getFromIndex(0));
        $zip->close();
    }

    public function test_rerun_skips_employees_that_already_received_the_sk(): void
    {
        $args = ['file' => $this->workbook(), '--output' => $this->workDir, '--force' => true];
        $this->artisan('mito:bulk-sk-pengangkatan', $args);
        $this->artisan('mito:bulk-sk-pengangkatan', $args)
            ->expectsOutputToContain('LEWATI: 3');

        $this->assertSame(3, EmployeeDocument::query()->where('doc_code', 'SKP')->count());
        $this->assertSame(2, EmployeeDocumentFile::query()->count());
    }
}
