<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Models\EmployeeDocumentFile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class SkPengangkatanCommandTest extends TestCase
{
    use RefreshDatabase;

    private string $workDir;

    protected function setUp(): void
    {
        parent::setUp();
        config(['hris.data_driver' => 'pgsql']);
        (new \App\Providers\AppServiceProvider($this->app))->register();
        Carbon::setTestNow(Carbon::parse('2026-10-12 10:00:00', 'Asia/Jakarta'));

        $this->workDir = sys_get_temp_dir() . '/skp-test-' . uniqid();
        mkdir($this->workDir);

        Employee::query()->create([
            'employee_id' => '20260702',
            'full_name' => 'Ivan Chandra Wijaya',
            'nik_npwp' => '3171012904990001',
            'branch_name' => 'PT Mahakarya Sukses Indonesia',
            'job_position_location' => 'Human Resource Business Partner & Corporate Affairs Leader',
            'job_position' => 'Human Resource Business Partner & Corporate Affairs Leader',
            'department' => 'HR & Legal',
            'division' => 'HR & Legal',
            'status_employee' => 'Contract',
        ]);

        foreach ([[1, 'EMP-A'], [130, 'EMP-B']] as [$sequence, $employeeId]) {
            EmployeeDocument::query()->create([
                'document_id' => sprintf('DOC-20260101-%03d-PKWT', $sequence),
                'employee_id' => $employeeId,
                'sequence' => $sequence,
                'doc_type' => 'PKWT',
                'doc_code' => 'PKWT',
                'nomor' => sprintf('%03d/PKWT/MSI/I/2026', $sequence),
                'entity' => 'MSI',
                'issued_at' => '2026-01-01 09:00:00',
                'issued_by' => 'HR Admin',
            ]);
        }
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

    public function test_dry_run_by_nik_plans_next_sequence_without_writing(): void
    {
        $this->artisan('mito:sk-pengangkatan', ['employee' => '3171012904990001', '--dry-run' => true])
            ->expectsOutputToContain('131/SKP/MSI/X/2026')
            ->assertSuccessful();

        $this->assertSame(0, EmployeeDocument::query()->where('doc_code', 'SKP')->count());
    }

    public function test_issues_next_sequence_archives_and_writes_pdf(): void
    {
        $this->artisan('mito:sk-pengangkatan', [
            'employee' => '20260702',
            '--output' => $this->workDir,
            '--issued-by' => 'hr@mito.id',
            '--force' => true,
        ])->assertSuccessful();

        $doc = EmployeeDocument::query()->where('doc_code', 'SKP')->sole();
        $this->assertSame('131/SKP/MSI/X/2026', $doc->nomor);
        $this->assertSame(131, (int) $doc->sequence);
        $this->assertSame('131/SKP/MSI/X/2026', Employee::query()->where('employee_id', '20260702')->value('nomor_sk'));
        $this->assertSame(1, EmployeeDocumentFile::query()->where('document_id', $doc->document_id)->count());

        $path = $this->workDir . '/131_SK_Pengangkatan_Ivan_Chandra_Wijaya_20260702.pdf';
        $this->assertFileExists($path);
        $this->assertStringStartsWith('%PDF', file_get_contents($path));
    }

    public function test_rerun_reprints_with_existing_number(): void
    {
        $args = ['employee' => '20260702', '--output' => $this->workDir, '--force' => true];
        $this->artisan('mito:sk-pengangkatan', $args)->assertSuccessful();
        $this->artisan('mito:sk-pengangkatan', $args)
            ->expectsOutputToContain('cetak ulang')
            ->assertSuccessful();

        $this->assertSame(1, EmployeeDocument::query()->where('doc_code', 'SKP')->count());
    }
}
