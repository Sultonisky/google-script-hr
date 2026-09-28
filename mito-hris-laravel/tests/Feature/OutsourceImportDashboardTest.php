<?php

namespace Tests\Feature;

use App\Models\OutsourceEmployee;
use App\Repositories\Contracts\UserPermissionRepositoryInterface;
use App\Services\PermissionResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Session;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class OutsourceImportDashboardTest extends TestCase
{
    use RefreshDatabase;

    private array $tempFiles = [];

    protected function setUp(): void
    {
        parent::setUp();
        config(['hris.data_driver' => 'pgsql']);
        (new \App\Providers\AppServiceProvider($this->app))->register();
        Carbon::setTestNow(Carbon::parse('2026-09-28 10:00', 'Asia/Jakarta'));

        OutsourceEmployee::query()->create([
            'outsource_id' => 'DM20260001',
            'full_name' => 'Bayu Saputra',
            'whatsapp_number' => '+6281234567890',
            'email' => 'bayu@example.com',
            'remarks' => 'Catatan HR',
            'vendor' => 'Damarindo',
            'created_by' => 'test',
        ]);
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
        $email = 'custom.user@mito.id';
        $repository = app(UserPermissionRepositoryInterface::class);
        foreach ($permissions as $permission) {
            $repository->upsert($email, $permission, true, 'test');
        }
        app(PermissionResolver::class)->forget($email);

        Session::put('hr_user', [
            'email' => $email,
            'fullName' => 'Custom User',
            'role' => 'User',
            'permissions' => $permissions,
            'auth_domain' => 'users',
            'entities' => [],
            'branch' => '',
        ]);
    }

    private function workbook(): UploadedFile
    {
        $spreadsheet = new Spreadsheet();
        $raw = $spreadsheet->getActiveSheet();
        $raw->setTitle('RAW DATA');
        $raw->fromArray(['ID Karyawan (Auto Generate)', 'Nama Karyawan (sesuai KTP)', 'Alamat sesuai KTP', 'Tanggal Lahir',
            'Kota Kelahiran', 'Pendidikan Terakhir', 'No WA (Aktif)', 'Alamat Email (Aktif)'], null, 'A1');
        // Existing ID: blank cells must not wipe stored values.
        $raw->fromArray(['DM20260001', 'Bayu Saputra Update', null, null, 'Bekasi'], null, 'A2', true);
        // No ID, placed before an explicit ID: must get the next number after every explicit one.
        $raw->fromArray(['-', 'Rina Tanpa ID', null, null, null, null, '081311112222', 'rina@example.com'], null, 'A3', true);
        $raw->fromArray(['DM20260002', 'Tono Prasetyo', null, null, null, null, '081333334444', 'tono@example.com'], null, 'A4', true);
        $raw->fromArray([2788800, 1195200, 'Remarks dari Excel'], null, 'T2', true);

        $path = tempnam(sys_get_temp_dir(), 'osimp') . '.xlsx';
        (new Xlsx($spreadsheet))->save($path);
        $this->tempFiles[] = $path;

        return new UploadedFile($path, 'Data_OS.xlsx', null, null, true);
    }

    public function test_view_only_users_cannot_import(): void
    {
        $this->actingWithPermissions(['view_outsource']);

        $this->get('/hr/outsource')->assertOk()
            ->assertDontSee('id="btnImportOutsource"', false)
            ->assertDontSee('id="outsourceImportModal"', false);
        $this->post('/hr/outsource/import', ['file' => $this->workbook(), 'vendor' => 'Damarindo'], ['Accept' => 'application/json'])
            ->assertForbidden();
    }

    public function test_preview_then_import_matches_artisan_handling(): void
    {
        $this->actingWithPermissions(['view_outsource', 'manage_outsource']);

        $this->get('/hr/outsource')->assertOk()
            ->assertSee('id="btnImportOutsource"', false)
            ->assertSee('id="outsourceImportModal"', false)
            ->assertSee('akan diabaikan karena Anda tidak memiliki izin');

        $this->post('/hr/outsource/import', ['file' => $this->workbook(), 'vendor' => 'StaffInc', 'dry_run' => '1'], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('dry_run', true)
            ->assertJsonPath('summary', ['read' => 3, 'created' => 2, 'updated' => 1])
            ->assertJsonFragment(["baris 3: ID '-' tidak valid, akan dibuat otomatis."]);
        $this->assertSame(1, OutsourceEmployee::count());

        $this->post('/hr/outsource/import', ['file' => $this->workbook(), 'vendor' => 'StaffInc'], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('dry_run', false)
            ->assertJsonPath('summary', ['read' => 3, 'created' => 2, 'updated' => 1])
            ->assertJsonFragment(['Kolom Gaji Pokok dan Insentif diabaikan karena Anda tidak memiliki izin mengelola kolom tersebut.']);

        $this->assertSame(3, OutsourceEmployee::count());
        $bayu = OutsourceEmployee::query()->where('outsource_id', 'DM20260001')->firstOrFail();
        $this->assertSame('Bayu Saputra Update', $bayu->full_name);
        $this->assertSame('Remarks dari Excel', $bayu->remarks);
        $this->assertNull($bayu->basic_salary);
        $this->assertNull($bayu->incentive_amount);
        $this->assertSame('+6281234567890', $bayu->whatsapp_number);
        $this->assertSame('Tono Prasetyo', OutsourceEmployee::query()->where('outsource_id', 'DM20260002')->value('full_name'));
        $rina = OutsourceEmployee::query()->where('outsource_id', 'DM20260003')->firstOrFail();
        $this->assertSame('Rina Tanpa ID', $rina->full_name);
        $this->assertSame('StaffInc', $rina->vendor);
        $this->assertSame('Custom User', $rina->created_by);

        // Re-import: row without ID is matched by WA/email instead of duplicated.
        $this->post('/hr/outsource/import', ['file' => $this->workbook(), 'vendor' => 'StaffInc'], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('summary', ['read' => 3, 'created' => 0, 'updated' => 3]);
        $this->assertSame(3, OutsourceEmployee::count());
    }

    public function test_import_writes_compensation_columns_only_with_permission(): void
    {
        $this->actingWithPermissions(['view_outsource', 'manage_outsource', 'view_outsource_compensation', 'manage_outsource_compensation']);

        $this->get('/hr/outsource')->assertOk()->assertDontSee('akan diabaikan karena Anda tidak memiliki izin');

        $response = $this->post('/hr/outsource/import', ['file' => $this->workbook(), 'vendor' => 'Damarindo'], ['Accept' => 'application/json'])
            ->assertOk();
        $this->assertNotContains(
            'Kolom Gaji Pokok dan Insentif diabaikan karena Anda tidak memiliki izin mengelola kolom tersebut.',
            $response->json('warnings')
        );

        $bayu = OutsourceEmployee::query()->where('outsource_id', 'DM20260001')->firstOrFail();
        $this->assertEquals(2788800, (float) $bayu->basic_salary);
        $this->assertEquals(1195200, (float) $bayu->incentive_amount);
        $this->assertSame('Remarks dari Excel', $bayu->remarks);
    }

    public function test_invalid_upload_is_rejected(): void
    {
        $this->actingWithPermissions(['view_outsource', 'manage_outsource']);

        $this->post('/hr/outsource/import', ['vendor' => 'Damarindo'], ['Accept' => 'application/json'])
            ->assertStatus(422)->assertJsonValidationErrors(['file']);
        $this->post('/hr/outsource/import', ['file' => UploadedFile::fake()->create('data.csv', 5, 'text/csv'), 'vendor' => 'Damarindo'], ['Accept' => 'application/json'])
            ->assertStatus(422)->assertJsonValidationErrors(['file']);
        $this->post('/hr/outsource/import', ['file' => $this->workbook(), 'vendor' => 'Lain'], ['Accept' => 'application/json'])
            ->assertStatus(422)->assertJsonValidationErrors(['vendor']);
        $this->post('/hr/outsource/import', ['file' => $this->workbook(), 'vendor' => 'Damarindo', 'sheet' => 'Sheet Lain'], ['Accept' => 'application/json'])
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', "Gagal membaca file: Sheet 'Sheet Lain' tidak ditemukan di file.");
        $this->assertSame(1, OutsourceEmployee::count());
    }
}
