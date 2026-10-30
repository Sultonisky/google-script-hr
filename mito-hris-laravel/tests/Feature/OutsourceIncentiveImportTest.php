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

    private const HEADERS = ['Outsource ID', 'Nama', 'Incentive Pokok', 'Incentive', 'Promotor & Product Race', 'Total'];

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

    private function workbook(array $rows, array $headers = self::HEADERS): UploadedFile
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray($headers, null, 'A1');
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
            'incentive_pokok_amount' => 1000000,
            'incentive_amount' => 1250000,
            'promotor_product_race_amount' => 400000,
            'total_amount' => 2250000,
        ]);
        OutsourceIncentive::query()->create([
            'period' => '2026-09',
            'outsource_id' => 'DM20260002',
            'full_name' => 'Citra Lestari',
            'vendor' => 'StaffInc',
            'umk_amount' => null,
            'incentive_pokok_amount' => null,
            'incentive_amount' => 900000,
            'total_amount' => null,
        ]);

        $response = $this->get('/hr/outsource-incentives/export?period=2026-09&search=Bayu')
            ->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
            ->assertHeader('content-disposition');
        $path = tempnam(sys_get_temp_dir(), 'oincexp') . '.xlsx';
        $this->tempFiles[] = $path;
        file_put_contents($path, $response->streamedContent());
        $rows = IOFactory::load($path)->getSheet(0)->toArray();

        $this->assertSame(['Outsource ID', 'Nama', 'Vendor', 'Incentive Pokok', 'Incentive', 'Promotor & Product Race', 'Total'], $rows[0]);
        $this->assertSame(['DM20260001', 'Bayu Saputra', 'Damarindo', '1,000,000', '1,250,000', '400,000', '2,250,000'], $rows[1]);
        $this->assertCount(2, $rows);

        $response = $this->get('/hr/outsource-incentives/export?period=2026-09&search=Citra');
        $response->assertOk();
        $path = tempnam(sys_get_temp_dir(), 'oincexp') . '.xlsx';
        $this->tempFiles[] = $path;
        file_put_contents($path, $response->streamedContent());
        $rows = IOFactory::load($path)->getSheet(0)->toArray();
        $this->assertNull($rows[1][3]);
        $this->assertSame('900,000', $rows[1][4]);
        $this->assertNull($rows[1][5]);
        $this->assertNull($rows[1][6]);

        $this->get('/hr/outsource-incentives?period=2026-09&search=Citra')
            ->assertOk()
            ->assertSee('Incentive Pokok')
            ->assertSee('Promotor & Product Race')
            ->assertSee('Total')
            ->assertSee('Rp 900.000');
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
                'incentive_pokok_amount' => 1000000,
                'incentive_amount' => 100000,
                'promotor_product_race_amount' => 50000,
                'total_amount' => 1100000,
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
        $this->get('/hr/outsource-incentives?period=2026-09')
            ->assertOk()
            ->assertSee('Incentive Pokok')
            ->assertSee('Promotor & Product Race')
            ->assertSee('Total')
            ->assertDontSee('UMK</th>', false)
            ->assertSee('nilai Total diimpor apa adanya dan tidak dihitung otomatis', false);

        $response = $this->get('/hr/outsource-incentives/template')->assertOk();
        $path = tempnam(sys_get_temp_dir(), 'ointpl') . '.xlsx';
        $this->tempFiles[] = $path;
        file_put_contents($path, $response->streamedContent());
        $rows = IOFactory::load($path)->getSheet(0)->toArray();
        $this->assertSame(self::HEADERS, $rows[0]);
        $this->assertSame(['DM20260001', 'Bayu Saputra'], array_slice($rows[1], 0, 2));
        $this->assertNull($rows[1][2]);
        $this->assertNull($rows[1][3]);
        $this->assertNull($rows[1][4]);
        $this->assertNull($rows[1][5]);

        $file = $this->workbook([
            ['DM20260001', 'Bayu', 1000000, 'Rp 1.250.000', 300000, 2250000],
            ['DM29999999', 'Tidak dikenal', 0, 500000, 0, 500000],
            ['DM20260001', 'Duplikat', 1000000, 100, 0, 100],
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

        // All imported amounts come from the workbook, not the master record.
        $file = $this->workbook([['DM20260001', 'Bayu', 1, 'Rp 1.250.000', 500000, 9750000]]);
        $this->post('/hr/outsource-incentives/import', [
            'file' => $file,
            'period' => '2026-09',
            'dry_run' => '1',
        ], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('summary.created', 1)
            ->assertJsonPath('rows.0.incentive_pokok_amount', 1)
            ->assertJsonPath('rows.0.incentive_amount', 1250000)
            ->assertJsonPath('rows.0.promotor_product_race_amount', 500000)
            ->assertJsonPath('rows.0.total_amount', 9750000);
        $this->assertSame(0, OutsourceIncentive::count());

        $file = $this->workbook([['DM20260001', 'Bayu', 1, 'Rp 1.250.000', 500000, 9750000]]);
        $this->post('/hr/outsource-incentives/import', [
            'file' => $file,
            'period' => '2026-09',
        ], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('summary.created', 1);
        $saved = OutsourceIncentive::query()->firstOrFail();
        $this->assertSame(1.0, (float) $saved->incentive_pokok_amount);
        $this->assertSame(1250000.0, (float) $saved->incentive_amount);
        $this->assertSame(500000.0, (float) $saved->promotor_product_race_amount);
        $this->assertSame(9750000.0, (float) $saved->total_amount);
        $this->assertNull($saved->umk_amount);
        $saved->update(['total_amount' => null]);
        $this->post('/hr/outsource-incentives/import', [
            'file' => $this->workbook([['DM20260001', 'Bayu', 1, 1250000, 500000, 0]]),
            'period' => '2026-09',
            'dry_run' => '1',
        ], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('rows.0.status', 'updated');
        $saved->update(['total_amount' => 9750000]);

        $master = OutsourceEmployee::query()->where('outsource_id', 'DM20260001')->firstOrFail();
        $this->assertSame(4500000.0, (float) $master->umk_amount);
        $this->assertSame(875000.0, (float) $master->incentive_amount);
        $this->get('/hr/outsource-incentives?period=2026-09')
            ->assertOk()
            ->assertSee('Rp 1')
            ->assertSee('Rp 9.750.000')
            ->assertSee('Incentive Pokok')
            ->assertSee('Promotor & Product Race')
            ->assertSee('Rp 500.000')
            ->assertSee('Rp 1.250.000');
    }

    public function test_import_rejects_changed_or_extra_headers_without_saving(): void
    {
        $this->actingWithPermissions(['view_outsource_incentive', 'manage_outsource_incentive']);

        $this->post('/hr/outsource-incentives/import', [
            'file' => $this->workbook(
                [['DM20260001', 'Bayu', 4500000, 1250000, 2250000]],
                ['Outsource ID', 'Nama', 'UMK', 'Incentive', 'Total'],
            ),
            'period' => '2026-09',
        ], ['Accept' => 'application/json'])
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', "Gagal membaca file: Format file tidak sesuai template: kolom C1 harus berisi 'Incentive Pokok'.");

        $this->post('/hr/outsource-incentives/import', [
            'file' => $this->workbook(
                [['DM20260001', 'Bayu', 4500000, 1250000, 2250000, 'Damarindo']],
                [...self::HEADERS, 'Vendor'],
            ),
            'period' => '2026-09',
        ], ['Accept' => 'application/json'])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Gagal membaca file: Format file tidak sesuai template: kolom G1 tidak diharapkan.');

        $this->assertSame(0, OutsourceIncentive::count());
    }

    public function test_import_requires_each_amount_to_be_entered_in_the_file(): void
    {
        $this->actingWithPermissions(['view_outsource_incentive', 'manage_outsource_incentive']);

        $this->post('/hr/outsource-incentives/import', [
            'file' => $this->workbook([['DM20260001', 'Bayu', 1000000, 1250000, 250000, null]]),
            'period' => '2026-09',
            'dry_run' => '1',
        ], ['Accept' => 'application/json'])
            ->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonFragment(['baris 2 (DM20260001): Total wajib diisi.']);

        $this->post('/hr/outsource-incentives/import', [
            'file' => $this->workbook([['DM20260001', 'Bayu', 1000000, 1250000, null, 2250000]]),
            'period' => '2026-09',
            'dry_run' => '1',
        ], ['Accept' => 'application/json'])
            ->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonFragment(['baris 2 (DM20260001): Promotor & Product Race wajib diisi.']);

        $this->assertSame(0, OutsourceIncentive::count());
    }

    public function test_index_sorts_by_name_and_outsource_id(): void
    {
        $this->actingWithPermissions(['view_outsource_incentive']);
        foreach ([
            ['DM20260010', 'Citra Lestari'],
            ['DM20260002', 'Bayu Saputra'],
            ['DM20260001', 'Andi Wijaya'],
        ] as [$outsourceId, $fullName]) {
            OutsourceIncentive::query()->create([
                'period' => '2026-09',
                'outsource_id' => $outsourceId,
                'full_name' => $fullName,
                'vendor' => 'Damarindo',
                'incentive_amount' => 100000,
            ]);
        }

        $this->get('/hr/outsource-incentives?period=2026-09')
            ->assertOk()
            ->assertSee('Nama A-Z')
            ->assertSee('Nomor Z-A')
            ->assertSeeInOrder(['Andi Wijaya', 'Bayu Saputra', 'Citra Lestari']);

        $this->get('/hr/outsource-incentives?period=2026-09&sort=name_desc')
            ->assertOk()
            ->assertSeeInOrder(['Citra Lestari', 'Bayu Saputra', 'Andi Wijaya']);

        $this->get('/hr/outsource-incentives?period=2026-09&sort=nomor_asc')
            ->assertOk()
            ->assertSeeInOrder(['DM20260001', 'DM20260002', 'DM20260010']);

        $this->get('/hr/outsource-incentives?period=2026-09&sort=nomor_desc')
            ->assertOk()
            ->assertSeeInOrder(['DM20260010', 'DM20260002', 'DM20260001']);

        $this->get('/hr/outsource-incentives?period=2026-09&sort=invalid')
            ->assertOk()
            ->assertSeeInOrder(['Andi Wijaya', 'Bayu Saputra', 'Citra Lestari']);
    }
}
