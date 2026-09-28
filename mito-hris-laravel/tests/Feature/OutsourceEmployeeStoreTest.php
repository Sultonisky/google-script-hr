<?php

namespace Tests\Feature;

use App\DTOs\OutsourceEmployeeData;
use App\Models\OutsourceEmployee;
use App\Repositories\Contracts\OutsourceEmployeeRepositoryInterface;
use App\Repositories\Database\OutsourceEmployeeDatabaseRepository;
use App\Services\Google\GoogleSheetsService;
use App\Services\OutsourceIdGenerator;
use App\Support\OutsourceEmployeeAttributeMap;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Mockery;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class OutsourceEmployeeStoreTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        config(['hris.data_driver' => 'pgsql']);
        (new \App\Providers\AppServiceProvider($this->app))->register();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_sheet_schema_matches_attribute_map_order(): void
    {
        $this->assertSame(config('hris.schemas.Outsource_Employees'), OutsourceEmployeeAttributeMap::headers());
        $this->assertCount(26, OutsourceEmployeeAttributeMap::headers());
        $this->assertSame('Outsource ID', OutsourceEmployeeAttributeMap::headers()[0]);
        $this->assertContains('Vendor', OutsourceEmployeeAttributeMap::headers());
    }

    public function test_amount_parsing_handles_indonesian_formats(): void
    {
        $this->assertSame(3984000.0, OutsourceEmployeeAttributeMap::parseAmount('3984000'));
        $this->assertSame(3984000.0, OutsourceEmployeeAttributeMap::parseAmount('3.984.000'));
        $this->assertSame(5396.0, OutsourceEmployeeAttributeMap::parseAmount('5.396'));
        $this->assertSame(3984000.0, OutsourceEmployeeAttributeMap::parseAmount('3984000.00'));
        $this->assertSame(3984000.5, OutsourceEmployeeAttributeMap::parseAmount('Rp 3.984.000,50'));
        $this->assertNull(OutsourceEmployeeAttributeMap::parseAmount(''));
    }

    public function test_id_generator_continues_from_existing_ids_per_year(): void
    {
        $generator = app(OutsourceIdGenerator::class);
        $now = Carbon::parse('2026-05-01', 'Asia/Jakarta');

        $this->assertSame('DM20260129', $generator->generate(['DM20260128', 'DM20250999', 'X'], $now));
        $this->assertSame('DM20260001', $generator->generate([], $now));
        $this->assertSame('DM20270001', $generator->generate(['DM20260130'], Carbon::parse('2027-01-02', 'Asia/Jakarta')));
    }

    public function test_new_ids_continue_from_stored_data_without_gaps(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-05-01 09:00', 'Asia/Jakarta'));
        Cache::put('OUTSOURCE_ID_COUNTER_DM2026', 500);

        try {
            $repo = app(OutsourceEmployeeRepositoryInterface::class);
            $repo->create(new OutsourceEmployeeData(outsourceId: 'DM20260127', fullName: 'Data Lama A'));
            $repo->create(new OutsourceEmployeeData(outsourceId: 'dm20260128', fullName: 'Data Lama B'));

            $fromForm = $repo->create(new OutsourceEmployeeData(fullName: 'Pelamar Form', vendor: 'Damarindo'));
            $fromDashboard = $repo->create(new OutsourceEmployeeData(fullName: 'Input HR', vendor: 'StaffInc'));

            $this->assertSame('DM20260129', $fromForm->outsourceId);
            $this->assertSame('DM20260130', $fromDashboard->outsourceId);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_database_repository_round_trips_and_finds_contacts(): void
    {
        $repo = app(OutsourceEmployeeRepositoryInterface::class);
        $this->assertInstanceOf(OutsourceEmployeeDatabaseRepository::class, $repo);

        $created = $repo->create(new OutsourceEmployeeData(
            fullName: 'Ayu Lestari',
            whatsappNumber: '+6281200001111',
            email: 'ayu@example.com',
            umkAmount: 3984000,
            vendor: 'StaffInc',
        ));

        $this->assertMatchesRegularExpression('/^DM\d{8}$/', (string) $created->outsourceId);
        $found = $repo->findById(strtolower((string) $created->outsourceId));
        $this->assertSame('Ayu Lestari', $found?->fullName);
        $this->assertSame(3984000.0, $found?->umkAmount);
        $this->assertSame($created->outsourceId, $repo->findByContact('081200001111', null)?->outsourceId);
        $this->assertSame($created->outsourceId, $repo->findByContact(null, 'AYU@example.com')?->outsourceId);
        $this->assertNull($repo->findByContact('0899', 'other@example.com'));

        $this->assertTrue($repo->update((string) $created->outsourceId, ['remarks' => 'Catatan HR', 'outsourceId' => 'HACK']));
        $this->assertSame('Catatan HR', $repo->findById((string) $created->outsourceId)?->remarks);
        $this->assertNull($repo->findById('HACK'));
        $this->assertCount(1, $repo->getAll(['vendor' => 'staffinc']));
        $this->assertCount(0, $repo->getAll(['vendor' => 'Damarindo']));
    }

    public function test_xlsx_import_reads_cached_values_and_upserts_by_id(): void
    {
        $path = $this->makeWorkbook();

        try {
            $this->artisan('mito:outsource:import-xlsx', ['file' => $path, '--dry-run' => true])->assertSuccessful();
            $this->assertSame(0, OutsourceEmployee::count());

            $this->artisan('mito:outsource:import-xlsx', ['file' => $path])->assertSuccessful();
            $this->assertSame(4, OutsourceEmployee::count());
            $this->assertSame('Rina Tanpa ID', OutsourceEmployee::query()->where('outsource_id', 'DM20260004')->value('full_name'));
            $this->assertSame('Budi Kolom Tiga', OutsourceEmployee::query()->where('outsource_id', 'DM20260003')->value('full_name'));
            $this->assertSame(0, OutsourceEmployee::query()->where('outsource_id', '-')->count());

            $row = OutsourceEmployee::query()->where('outsource_id', 'DM20260001')->firstOrFail();
            $this->assertSame('Sari Wulandari', $row->full_name);
            $this->assertSame('2000-02-15', $row->birth_date);
            $this->assertSame('+6285712345678', $row->whatsapp_number);
            $this->assertSame('sari@example.com', $row->email);
            $this->assertSame('0123456789', $row->bank_account);
            $this->assertSame('2026-09-15', $row->mito_join_date);
            $this->assertNull($row->contract_end_date);
            $this->assertSame('PT. Mahakarya Sukses Indonesia', $row->entity);
            $this->assertEquals(3984000, (float) $row->umk_amount);
            $this->assertEquals(2788800, (float) $row->basic_salary);
            $this->assertEquals(1195200, (float) $row->incentive_amount);
            $this->assertSame('Damarindo', $row->vendor);

            $second = OutsourceEmployee::query()->where('outsource_id', 'DM20260002')->firstOrFail();
            $this->assertNull($second->last_education);

            $row->update(['remarks' => 'Diedit HR']);
            $this->artisan('mito:outsource:import-xlsx', ['file' => $path])->assertSuccessful();
            $this->assertSame(4, OutsourceEmployee::count());
            $this->assertSame('Diedit HR', $row->fresh()->remarks);
        } finally {
            @unlink($path);
        }
    }

    public function test_xlsx_import_rejects_unknown_vendor(): void
    {
        $this->artisan('mito:outsource:import-xlsx', ['file' => 'missing.xlsx', '--vendor' => 'Lain'])->assertFailed();
    }

    public function test_etl_imports_and_mirrors_outsource_sheet(): void
    {
        $headers = OutsourceEmployeeAttributeMap::headers();
        $sheetRow = array_combine($headers, array_fill(0, count($headers), ''));
        $sheetRow['Outsource ID'] = 'dm20260005';
        $sheetRow['Full Name'] = 'Joko Susilo';
        $sheetRow['WhatsApp Number'] = "'+6281377778888";
        $sheetRow['UMK Amount'] = '5396761';
        $sheetRow['Vendor'] = 'StaffInc';

        $sheets = Mockery::mock(GoogleSheetsService::class);
        $sheets->shouldReceive('getRowsAsAssoc')->andReturnUsing(
            fn (string $name) => $name === 'Outsource_Employees' ? [$sheetRow] : []
        );
        $sheets->shouldReceive('replaceSheetData')
            ->once()
            ->withArgs(fn (string $sheet, array $h, array $rows) => $sheet === 'Outsource_Employees'
                && $h === $headers
                && $rows[0][0] === 'DM20260005'
                && $rows[0][array_search('UMK Amount', $headers, true)] === '5396761');
        $this->app->instance(GoogleSheetsService::class, $sheets);

        $this->artisan('mito:etl-sheets-to-db', ['--only' => 'outsource_employees'])->assertSuccessful();
        $row = OutsourceEmployee::query()->where('outsource_id', 'DM20260005')->firstOrFail();
        $this->assertSame('+6281377778888', $row->whatsapp_number);

        $this->artisan('mito:etl-db-to-sheets', ['--only' => 'outsource_employees'])->assertSuccessful();
    }

    private function makeWorkbook(): string
    {
        $spreadsheet = new Spreadsheet();
        $raw = $spreadsheet->getActiveSheet();
        $raw->setTitle('RAW DATA');
        $raw->fromArray([
            'ID Karyawan (Auto Generate)', 'Nama Karyawan (sesuai KTP)', 'Alamat sesuai KTP', 'Tanggal Lahir',
            'Kota Kelahiran', 'Pendidikan Terakhir', 'No WA (Aktif)', 'Alamat Email (Aktif)', 'Nama Jabatan',
            'Nama Lokasi kerja', 'Nama Kota/ Kabupaten Lokasi Kerja', 'No Rekening BCA (Aktif)', 'Tgl join di Mito',
            'Tgl awal kontrak (dg Damarindo)', 'Tgl akhir kontrak (StaffInc)', 'Nama Cabang (Cost Center)', 'Entity',
            'Skema Penggajian', 'Nominal UMK yang dipakai', 'Amount Gaji Pokok', 'Amount Insentif 30%', 'Remarks',
        ], null, 'A1');

        $join = ExcelDate::PHPToExcel(new \DateTime('2026-09-15'));
        $raw->fromArray([
            'DM20260001', 'Sari Wulandari', 'Jl. Melati No. 3, Bekasi', ExcelDate::PHPToExcel(new \DateTime('2000-02-15')),
            'Bekasi', 'SLTA', '085712345678', 'Sari@Example.com', 'SPB/SPG Toko', 'Toko Maju', 'Kota Bekasi',
            '0123456789', $join, $join, null, 'Jakarta', 'PT. Mahakarya Sukses Indonesia', '70/30', 3984000, 2788800, 1195200, null,
        ], null, 'A2', true);
        $raw->fromArray([
            'DM20260002', 'Tono Prasetyo', 'Jl. Kenanga No. 9, Depok', '#N/A', 'Depok', '#N/A', '6281298765432',
            'tono@example.com', 'GTM', 'Outlet Depok', 'Kota Depok', '9876543210', $join, $join, null, 'Jakarta',
            'PT. Stein Perkasa Internasional', 'Khusus', 3984000, null, null, 'Pindahan vendor',
        ], null, 'A3', true);
        $raw->fromArray(['-', 'Rina Tanpa ID', null, null, null, null, '081311112222', 'rina@example.com'], null, 'A4', true);
        $raw->fromArray(['DM20260003', 'Budi Kolom Tiga', null, null, null, null, '081333334444', 'budi@example.com'], null, 'A5', true);
        $raw->getCell('L2')->setValueExplicit('0123456789', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);

        $path = tempnam(sys_get_temp_dir(), 'osxlsx') . '.xlsx';
        (new Xlsx($spreadsheet))->save($path);

        return $path;
    }
}
