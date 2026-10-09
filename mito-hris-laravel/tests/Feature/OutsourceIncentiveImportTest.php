<?php

namespace Tests\Feature;

use App\Models\OutsourceEmployee;
use App\Models\OutsourceIncentive;
use App\Repositories\Contracts\UserPermissionRepositoryInterface;
use App\Services\PermissionResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Session;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class OutsourceIncentiveImportTest extends TestCase
{
    use RefreshDatabase;

    private array $tempFiles = [];

    protected function setUp(): void
    {
        parent::setUp();
        config(['hris.data_driver' => 'pgsql']);
        (new \App\Providers\AppServiceProvider($this->app))->register();
        OutsourceEmployee::query()->create([
            'outsource_id' => 'DM20260001',
            'full_name' => 'Bayu Saputra',
            'vendor' => 'Damarindo',
            'umk_amount' => 4500000,
            'incentive_amount' => 875000,
            'created_by' => 'test',
        ]);
    }

    protected function tearDown(): void
    {
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

    private function workbook(array $rows): UploadedFile
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray(['Outsource ID', 'Nama', 'UMK', 'Incentive'], null, 'A1');
        foreach ($rows as $index => $row) {
            $sheet->fromArray($row, null, 'A' . ($index + 2), true);
        }
        $path = tempnam(sys_get_temp_dir(), 'oincentive') . '.xlsx';
        (new Xlsx($spreadsheet))->save($path);
        $this->tempFiles[] = $path;

        return new UploadedFile($path, 'Outsource_Incentive.xlsx', null, null, true);
    }

    public function test_access_and_template_require_incentive_permissions(): void
    {
        $this->actingWithPermissions(['view_outsource']);
        $this->get('/hr/outsource-incentives')->assertForbidden();
        $this->get('/hr/outsource-incentives/export')->assertForbidden();

        $this->actingWithPermissions(['view_outsource_incentive']);
        $this->get('/hr/outsource-incentives')->assertOk()
            ->assertSee('Outsource Incentive')
            ->assertDontSee('id="outsourceIncentiveImportModal"', false);
        $this->get('/hr/outsource-incentives/template')->assertForbidden();
    }

    public function test_export_downloads_filtered_incentive_rows_as_xlsx(): void
    {
        $this->actingWithPermissions(['view_outsource_incentive']);
        OutsourceIncentive::query()->create([
            'period' => '2026-09',
            'outsource_id' => 'DM20260001',
            'full_name' => 'Bayu Saputra',
            'vendor' => 'Damarindo',
            'umk_amount' => 4500000,
            'incentive_amount' => 1250000,
        ]);
        OutsourceIncentive::query()->create([
            'period' => '2026-09',
            'outsource_id' => 'DM20260002',
            'full_name' => 'Citra Lestari',
            'vendor' => 'StaffInc',
            'umk_amount' => 4200000,
            'incentive_amount' => 900000,
        ]);

        $response = $this->get('/hr/outsource-incentives/export?period=2026-09&search=Bayu')
            ->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
            ->assertHeader('content-disposition');
        $path = tempnam(sys_get_temp_dir(), 'oincexp') . '.xlsx';
        $this->tempFiles[] = $path;
        file_put_contents($path, $response->streamedContent());
        $rows = IOFactory::load($path)->getSheet(0)->toArray();

        $this->assertSame(['Outsource ID', 'Nama', 'Vendor', 'UMK', 'Incentive'], $rows[0]);
        $this->assertSame(['DM20260001', 'Bayu Saputra', 'Damarindo', '4,500,000', '1,250,000'], $rows[1]);
        $this->assertCount(2, $rows);
    }

    public function test_incentive_table_shows_fifteen_rows_per_page(): void
    {
        $this->actingWithPermissions(['view_outsource_incentive']);
        for ($index = 1; $index <= 16; $index++) {
            OutsourceIncentive::query()->create([
                'period' => '2026-09',
                'outsource_id' => sprintf('ID%05d', $index),
                'full_name' => sprintf('Employee %02d', $index),
                'vendor' => 'Damarindo',
                'umk_amount' => 4500000,
                'incentive_amount' => 100000,
            ]);
        }

        $this->get('/hr/outsource-incentives?period=2026-09')
            ->assertOk()
            ->assertSee('id="outsourceIncentivePanel"', false)
            ->assertSee('ID00015')
            ->assertDontSee('ID00016')
            ->assertSee('Menampilkan 1–15 dari 16 data');
    }

    public function test_template_and_import_preview_and_save_work(): void
    {
        $this->actingWithPermissions(['view_outsource_incentive', 'manage_outsource_incentive']);

        $response = $this->get('/hr/outsource-incentives/template')->assertOk();
        $path = tempnam(sys_get_temp_dir(), 'ointpl') . '.xlsx';
        $this->tempFiles[] = $path;
        file_put_contents($path, $response->streamedContent());
        $rows = IOFactory::load($path)->getSheet(0)->toArray();
        $this->assertSame(['Outsource ID', 'Nama', 'UMK', 'Incentive'], $rows[0]);
        $this->assertSame(['DM20260001', 'Bayu Saputra'], array_slice($rows[1], 0, 2));
        $this->assertSame('4,500,000', $rows[1][2]);

        $file = $this->workbook([
            ['DM20260001', 'Bayu', 4500000, 'Rp 1.250.000'],
            ['DM29999999', 'Tidak dikenal', 0, 500000],
            ['DM20260001', 'Duplikat', 4500000, 100],
        ]);
        $this->post('/hr/outsource-incentives/import', [
            'file' => $file,
            'period' => '2026-09',
            'dry_run' => '1',
        ], ['Accept' => 'application/json'])
            ->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonPath('summary.created', 1)
            ->assertJsonFragment(['baris 4: Outsource ID DM20260001 duplikat dengan baris 2.']);
        $this->assertSame(0, OutsourceIncentive::count());

        // The uploaded UMK value is deliberately different from the master value.
        $file = $this->workbook([['DM20260001', 'Bayu', 1, 'Rp 1.250.000']]);
        $this->post('/hr/outsource-incentives/import', [
            'file' => $file,
            'period' => '2026-09',
            'dry_run' => '1',
        ], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('summary.created', 1)
            ->assertJsonPath('rows.0.umk_amount', 4500000);
        $this->assertSame(0, OutsourceIncentive::count());

        $file = $this->workbook([['DM20260001', 'Bayu', 1, 'Rp 1.250.000']]);
        $this->post('/hr/outsource-incentives/import', [
            'file' => $file,
            'period' => '2026-09',
        ], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('summary.created', 1);
        $saved = OutsourceIncentive::query()->firstOrFail();
        $this->assertSame(1250000.0, (float) $saved->incentive_amount);
        $this->assertSame(4500000.0, (float) $saved->umk_amount);
        $master = OutsourceEmployee::query()->where('outsource_id', 'DM20260001')->firstOrFail();
        $this->assertSame(4500000.0, (float) $master->umk_amount);
        $this->assertSame(875000.0, (float) $master->incentive_amount);
        $this->get('/hr/outsource-incentives?period=2026-09')
            ->assertOk()
            ->assertSee('Rp 4.500.000')
            ->assertSee('Rp 1.250.000');
    }
}
