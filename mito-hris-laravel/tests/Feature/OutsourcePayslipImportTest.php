<?php

namespace Tests\Feature;

use App\Models\OutsourceEmployee;
use App\Models\OutsourcePayslip;
use App\Repositories\Contracts\UserPermissionRepositoryInterface;
use App\Services\PermissionResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Session;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class OutsourcePayslipImportTest extends TestCase
{
    use RefreshDatabase;

    private const HEADERS = ['Outsource ID', 'Nama', 'Total HKE', 'Gaji Pokok', 'Potongan BPJS Kesehatan', 'Potongan Pinjaman', 'THP'];

    private array $tempFiles = [];

    protected function setUp(): void
    {
        parent::setUp();
        config(['hris.data_driver' => 'pgsql']);
        (new \App\Providers\AppServiceProvider($this->app))->register();
        Carbon::setTestNow(Carbon::parse('2026-10-01 10:00', 'Asia/Jakarta'));

        foreach ([
            ['DM20260001', 'Bayu Saputra', 'Damarindo'],
            ['DM20260002', 'Citra Lestari', 'StaffInc'],
            ['DM20260003', 'Dedi Off', 'Damarindo'],
        ] as [$id, $name, $vendor]) {
            OutsourceEmployee::query()->create(['outsource_id' => $id, 'full_name' => $name, 'vendor' => $vendor, 'created_by' => 'test']);
        }
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        foreach ($this->tempFiles as $file) {
            @unlink($file);
        }
        parent::tearDown();
    }

    private function actingWithPermissions(array $permissions): void
    {
        $email = 'payroll.user@mito.id';
        $repository = app(UserPermissionRepositoryInterface::class);
        foreach ($permissions as $permission) {
            $repository->upsert($email, $permission, true, 'test');
        }
        app(PermissionResolver::class)->forget($email);

        Session::put('hr_user', [
            'email' => $email,
            'fullName' => 'Payroll User',
            'role' => 'User',
            'permissions' => $permissions,
            'auth_domain' => 'users',
            'entities' => [],
            'branch' => '',
        ]);
    }

    /**
     * @param  list<list<mixed>>  $rows
     */
    private function workbook(array $rows, array $headers = self::HEADERS): UploadedFile
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray($headers, null, 'A1');
        foreach ($rows as $i => $row) {
            $sheet->fromArray($row, null, 'A' . ($i + 2), true);
        }

        $path = tempnam(sys_get_temp_dir(), 'opsimp') . '.xlsx';
        (new Xlsx($spreadsheet))->save($path);
        $this->tempFiles[] = $path;

        return new UploadedFile($path, 'Payslip_Sept.xlsx', null, null, true);
    }

    private function validWorkbook(): UploadedFile
    {
        return $this->workbook([
            ['dm20260001', 'Bayu', 26, 3500000, 35000, 250000, 3215000],
            ['DM20260002', 'Citra', '24,5', 'Rp 3.200.000', 'Rp32.000', null, '3,168,000'],
            // Outsource yang sudah off: ada di template tapi tanpa nominal.
            ['DM20260003', 'Dedi Off'],
            ['DM29999999', 'Tidak Dikenal', 20, 3000000, 30000, 0, 2970000],
        ]);
    }

    private function import(UploadedFile $file, array $extra = [])
    {
        return $this->post('/hr/outsource-payslips/import', $extra + ['file' => $file, 'period' => '2026-09'], ['Accept' => 'application/json']);
    }

    public function test_access_requires_payslip_permissions(): void
    {
        $this->actingWithPermissions(['view_outsource']);
        $this->get('/hr/outsource-payslips')->assertForbidden();

        $this->actingWithPermissions(['view_outsource_payslip']);
        $this->get('/hr/outsource-payslips')->assertOk()
            ->assertSee('Payslip Outsource')
            ->assertSee('Export Excel')
            ->assertDontSee('opsVendorSelect')
            ->assertDontSee('id="btnImportOutsourcePayslip"', false)
            ->assertDontSee('id="outsourcePayslipImportModal"', false);
        $this->get('/hr/outsource-payslips/template')->assertForbidden();
        $this->import($this->validWorkbook())->assertForbidden();
    }

    public function test_export_downloads_filtered_payslips_as_xlsx(): void
    {
        $this->actingWithPermissions(['view_outsource_payslip']);
        OutsourcePayslip::query()->create([
            'period' => '2026-09',
            'outsource_id' => 'DM20260001',
            'full_name' => 'Bayu Saputra',
            'vendor' => 'Damarindo',
            'hke' => 26,
            'basic_salary' => 3500000,
            'bpjs_kesehatan_deduction' => 35000,
            'loan_deduction' => 250000,
            'take_home_pay' => 3215000,
        ]);
        OutsourcePayslip::query()->create([
            'period' => '2026-09',
            'outsource_id' => 'DM20260002',
            'full_name' => 'Citra Lestari',
            'vendor' => 'StaffInc',
            'hke' => 24,
            'basic_salary' => 3200000,
            'bpjs_kesehatan_deduction' => 32000,
            'loan_deduction' => 0,
            'take_home_pay' => 3168000,
        ]);

        $response = $this->get('/hr/outsource-payslips/export?period=2026-09&search=Bayu')
            ->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
            ->assertHeader('content-disposition');
        $path = tempnam(sys_get_temp_dir(), 'opsexp') . '.xlsx';
        $this->tempFiles[] = $path;
        file_put_contents($path, $response->streamedContent());
        $rows = IOFactory::load($path)->getSheet(0)->toArray();

        $this->assertSame(['Outsource ID', 'Nama', 'Vendor', 'Total HKE', 'Gaji Pokok', 'Potongan BPJS Kesehatan', 'Potongan Pinjaman', 'THP'], $rows[0]);
        $this->assertSame(['DM20260001', 'Bayu Saputra', 'Damarindo'], array_slice($rows[1], 0, 3));
        $this->assertSame('26.00', $rows[1][3]);
        $this->assertSame('3,500,000', $rows[1][4]);
        $this->assertSame('35,000', $rows[1][5]);
        $this->assertSame('250,000', $rows[1][6]);
        $this->assertSame('3,215,000', $rows[1][7]);
        $this->assertCount(2, $rows);
    }

    public function test_template_lists_master_outsource_ids(): void
    {
        $this->actingWithPermissions(['view_outsource_payslip', 'manage_outsource_payslip']);

        $response = $this->get('/hr/outsource-payslips/template?vendor=Damarindo')->assertOk();
        $path = tempnam(sys_get_temp_dir(), 'opstpl') . '.xlsx';
        $this->tempFiles[] = $path;
        file_put_contents($path, $response->streamedContent());

        $rows = IOFactory::load($path)->getSheet(0)->toArray();
        $this->assertSame(self::HEADERS, $rows[0]);
        $this->assertSame(['DM20260001', 'Bayu Saputra'], array_slice($rows[1], 0, 2));
        $this->assertSame(['DM20260003', 'Dedi Off'], array_slice($rows[2], 0, 2));
        $this->assertCount(3, $rows);
    }

    public function test_preview_then_import_stores_matched_rows_only(): void
    {
        $this->actingWithPermissions(['view_outsource_payslip', 'manage_outsource_payslip']);

        $this->get('/hr/outsource-payslips')->assertOk()
            ->assertSee('id="btnImportOutsourcePayslip"', false)
            ->assertSee('id="outsourcePayslipImportModal"', false)
            ->assertSee('id="outsourcePayslipConfirmModal"', false)
            ->assertDontSee('confirm(\'Simpan payslip', false);

        $this->import($this->validWorkbook(), ['dry_run' => '1'])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('dry_run', true)
            ->assertJsonPath('summary', ['read' => 3, 'created' => 2, 'updated' => 0, 'unchanged' => 0, 'skipped' => 1])
            ->assertJsonPath('rows.1.outsource_id', 'DM20260002')
            ->assertJsonPath('rows.1.loan_deduction', 0)
            ->assertJsonFragment(['baris 5: Outsource ID DM29999999 tidak ditemukan di master data outsource, dilewati.'])
            ->assertJsonFragment(['1 baris tanpa nominal diabaikan (tidak masuk payslip periode ini).']);
        $this->assertSame(0, OutsourcePayslip::count());

        $this->import($this->validWorkbook())
            ->assertOk()
            ->assertJsonPath('dry_run', false)
            ->assertJsonPath('summary', ['read' => 3, 'created' => 2, 'updated' => 0, 'unchanged' => 0, 'skipped' => 1]);

        $this->assertSame(2, OutsourcePayslip::count());
        $bayu = OutsourcePayslip::query()->where('outsource_id', 'DM20260001')->firstOrFail();
        $this->assertSame('2026-09', $bayu->period);
        $this->assertSame('Bayu Saputra', $bayu->full_name);
        $this->assertSame('Damarindo', $bayu->vendor);
        $this->assertEquals(26, (float) $bayu->hke);
        $this->assertEquals(250000, (float) $bayu->loan_deduction);
        $this->assertEquals(3215000, (float) $bayu->take_home_pay);
        $this->assertSame('Payroll User', $bayu->imported_by);
        $this->assertSame('Payslip_Sept.xlsx', $bayu->source_file);

        $citra = OutsourcePayslip::query()->where('outsource_id', 'DM20260002')->firstOrFail();
        $this->assertEquals(24.5, (float) $citra->hke);
        $this->assertEquals(3200000, (float) $citra->basic_salary);
        $this->assertEquals(32000, (float) $citra->bpjs_kesehatan_deduction);
        $this->assertEquals(0, (float) $citra->loan_deduction);
        $this->assertEquals(3168000, (float) $citra->take_home_pay);
        $this->assertSame('StaffInc', $citra->vendor);

        // Import ulang file yang sama: tidak ada yang ditulis ulang.
        $this->import($this->validWorkbook())
            ->assertOk()
            ->assertJsonPath('summary', ['read' => 3, 'created' => 0, 'updated' => 0, 'unchanged' => 2, 'skipped' => 1])
            ->assertJsonPath('message', 'Tidak ada perubahan: semua payslip periode 2026-09 di file sama dengan data tersimpan.');
        $this->assertSame(2, OutsourcePayslip::count());

        $this->get('/hr/outsource-payslips?period=2026-09')->assertOk()
            ->assertSee('DM20260001')
            ->assertSee('Rp 3.215.000')
            ->assertSee('Rp 0')
            ->assertSee('id="outsourcePayslipDetailModal"', false)
            ->assertSee('data-payslip-detail=', false)
            ->assertSee('&quot;totalDeduction&quot;:&quot;Rp 285.000&quot;', false)
            ->assertSee('&quot;createdAt&quot;:&quot;1 Oktober 2026&quot;', false)
            ->assertDontSee('Total THP')
            ->assertDontSee('DM20260003');
    }

    public function test_import_never_overwrites_master_outsource_data(): void
    {
        $this->actingWithPermissions(['view_outsource_payslip', 'manage_outsource_payslip']);

        OutsourceEmployee::query()->where('outsource_id', 'DM20260001')->update([
            'payroll_scheme' => '70/30',
            'umk_amount' => 5000000,
            'basic_salary' => 3500000,
            'incentive_amount' => 1500000,
            'bank_account' => '0123456789',
        ]);
        Carbon::setTestNow(Carbon::parse('2026-10-01 11:00', 'Asia/Jakarta'));
        $before = OutsourceEmployee::query()->orderBy('outsource_id')->get()->map->getAttributes()->all();

        $file = $this->workbook([
            ['DM20260001', 'Nama Beda Dari Master', 22, 9999999, 50000, 100000, 9849999],
            ['DM20260002', 'Citra Ganti', 20, 1234567, 0, 0, 1234567],
        ]);

        $this->import($file)->assertOk()->assertJsonPath('summary.created', 2);

        $after = OutsourceEmployee::query()->orderBy('outsource_id')->get()->map->getAttributes()->all();
        $this->assertSame($before, $after);

        $payslip = OutsourcePayslip::query()->where('outsource_id', 'DM20260001')->firstOrFail();
        $this->assertEquals(9999999, (float) $payslip->basic_salary);
        $this->assertEquals(9849999, (float) $payslip->take_home_pay);
        $this->assertEquals(3500000, (float) OutsourceEmployee::query()->where('outsource_id', 'DM20260001')->value('basic_salary'));
    }

    public function test_reimport_only_updates_rows_with_different_amounts(): void
    {
        $this->actingWithPermissions(['view_outsource_payslip', 'manage_outsource_payslip']);

        $this->import($this->workbook([
            ['DM20260001', 'Bayu', 26, 3500000, 35000, 250000, 3215000],
            ['DM20260002', 'Citra', 24, 3200000, 32000, 0, 3168000],
        ]))->assertOk();
        $citraBefore = OutsourcePayslip::query()->where('outsource_id', 'DM20260002')->firstOrFail();

        Carbon::setTestNow(Carbon::parse('2026-10-05 09:00', 'Asia/Jakarta'));
        $revision = $this->workbook([
            ['DM20260001', 'Bayu', 25, 3500000, 35000, 250000, 3080000],
            ['DM20260002', 'Citra', '24', 'Rp 3.200.000', 32000, null, 3168000],
        ]);

        $this->import($revision, ['dry_run' => '1'])
            ->assertOk()
            ->assertJsonPath('summary', ['read' => 2, 'created' => 0, 'updated' => 1, 'unchanged' => 1, 'skipped' => 0])
            ->assertJsonPath('rows.0.status', 'updated')
            ->assertJsonPath('rows.1.status', 'unchanged');

        $this->import($revision)->assertOk()->assertJsonPath('summary.updated', 1);

        $bayu = OutsourcePayslip::query()->where('outsource_id', 'DM20260001')->firstOrFail();
        $this->assertEquals(25, (float) $bayu->hke);
        $this->assertEquals(3080000, (float) $bayu->take_home_pay);
        $this->assertSame('2026-10-05', $bayu->updated_at->timezone('Asia/Jakarta')->format('Y-m-d'));

        $citraAfter = OutsourcePayslip::query()->where('outsource_id', 'DM20260002')->firstOrFail();
        $this->assertEquals($citraBefore->updated_at, $citraAfter->updated_at);
        $this->assertSame($citraBefore->source_file, $citraAfter->source_file);
    }

    public function test_index_shows_fifteen_payslips_per_page(): void
    {
        $this->actingWithPermissions(['view_outsource_payslip']);

        foreach (range(1, 16) as $i) {
            OutsourcePayslip::query()->create([
                'period' => '2026-09',
                'outsource_id' => sprintf('DM2026%04d', $i),
                'full_name' => sprintf('Karyawan %02d', $i),
                'vendor' => 'Damarindo',
                'hke' => 26,
                'basic_salary' => 3500000,
                'take_home_pay' => 3465000,
            ]);
        }

        $this->get('/hr/outsource-payslips?period=2026-09&per_page=100')->assertOk()
            ->assertSee('Menampilkan 1–15', false)
            ->assertSee('dari 16 data')
            ->assertSee('Karyawan 15')
            ->assertDontSee('Karyawan 16');

        $this->get('/hr/outsource-payslips?period=2026-09&page=2')->assertOk()
            ->assertSee('Menampilkan 16–16', false)
            ->assertSee('Karyawan 16')
            ->assertDontSee('Karyawan 15');
    }

    public function test_row_errors_block_the_whole_import(): void
    {
        $this->actingWithPermissions(['view_outsource_payslip', 'manage_outsource_payslip']);

        $file = $this->workbook([
            ['DM20260001', 'Bayu', 26, 3500000, 35000, 0, 3465000],
            ['DM20260001', 'Bayu lagi', 26, 3500000, 35000, 0, 3465000],
            ['DM20260002', 'Citra', 40, 3200000, 'abc', 0, null],
            [null, 'Tanpa ID', 20, 3000000, 0, 0, 3000000],
        ]);

        $this->import($file)
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonFragment(['baris 3: Outsource ID DM20260001 duplikat dengan baris 2.'])
            ->assertJsonFragment(['baris 4 (DM20260002): Total HKE maksimal 31 hari.'])
            ->assertJsonFragment(['baris 4 (DM20260002): Potongan BPJS Kesehatan bukan angka.'])
            ->assertJsonFragment(['baris 4 (DM20260002): THP wajib diisi.'])
            ->assertJsonFragment(['baris 5: Outsource ID kosong.']);
        $this->assertSame(0, OutsourcePayslip::count());
    }

    public function test_invalid_upload_is_rejected(): void
    {
        $this->actingWithPermissions(['view_outsource_payslip', 'manage_outsource_payslip']);

        $this->post('/hr/outsource-payslips/import', ['period' => '2026-09'], ['Accept' => 'application/json'])
            ->assertStatus(422)->assertJsonValidationErrors(['file']);
        $this->import($this->validWorkbook(), ['period' => '2026-13'])
            ->assertStatus(422)->assertJsonValidationErrors(['period']);
        $this->import($this->workbook([['DM20260001', 'Bayu', 26, 1, 0, 0, 1]], ['ID', 'Nama']))
            ->assertStatus(422)
            ->assertJsonPath('message', "Gagal membaca file: Format file tidak sesuai template: kolom A1 harus berisi 'Outsource ID'.");
        $this->assertSame(0, OutsourcePayslip::count());
    }

    public function test_import_rejects_changed_or_reordered_headers_without_saving(): void
    {
        $this->actingWithPermissions(['view_outsource_payslip', 'manage_outsource_payslip']);

        $this->import($this->workbook(
            [['DM20260001', 'Bayu', 26, 3500000, 35000, 0, 3465000]],
            ['Outsource ID', 'Nama', 'Hari Kerja Efektif', 'Gaji Pokok', 'Potongan BPJS Kesehatan', 'Potongan Pinjaman', 'THP'],
        ))
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', "Gagal membaca file: Format file tidak sesuai template: kolom C1 harus berisi 'Total HKE'.");

        $this->import($this->workbook(
            [['DM20260001', 'Bayu', 26, 3500000, 35000, 0, 3465000]],
            ['Outsource ID', 'Total HKE', 'Nama', 'Gaji Pokok', 'Potongan BPJS Kesehatan', 'Potongan Pinjaman', 'THP'],
        ))
            ->assertStatus(422)
            ->assertJsonPath('message', "Gagal membaca file: Format file tidak sesuai template: kolom B1 harus berisi 'Nama'.");

        $this->assertSame(0, OutsourcePayslip::count());
    }
}
