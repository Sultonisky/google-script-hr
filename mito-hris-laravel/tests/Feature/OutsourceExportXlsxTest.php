<?php

namespace Tests\Feature;

use App\DTOs\OutsourceEmployeeData;
use App\Repositories\Contracts\AuditLogRepositoryInterface;
use App\Repositories\Contracts\OutsourceEmployeeRepositoryInterface;
use App\Repositories\Contracts\UserPermissionRepositoryInterface;
use App\Services\PermissionResolver;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Session;
use Mockery;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Outsource XLSX Export — Structure & Content Tests
 *
 * Mengikuti pola EmployeeExportXlsxTest: repository di-mock sehingga tidak
 * memerlukan koneksi Google Sheets / database.
 */
class OutsourceExportXlsxTest extends TestCase
{
    private const URL = '/hr/export/outsource-xlsx';

    private const FULL_HEADERS = [
        'A' => 'Outsource ID',
        'B' => 'Full Name',
        'C' => 'Citizen ID Address',
        'D' => 'Birth Date',
        'E' => 'Birth Place',
        'F' => 'Last Education',
        'G' => 'WhatsApp Number',
        'H' => 'Email',
        'I' => 'Job Title',
        'J' => 'Work Location',
        'K' => 'Work City',
        'L' => 'BCA Account Number',
        'M' => 'Mito Join Date',
        'N' => 'Contract Start Date',
        'O' => 'Contract End Date',
        'P' => 'Branch (Cost Center)',
        'Q' => 'Entity',
        'R' => 'Payroll Scheme',
        'S' => 'UMK Amount',
        'T' => 'Basic Salary',
        'U' => 'Incentive Amount (30%)',
        'V' => 'Remarks',
        'W' => 'Vendor',
    ];

    private function actingAsRole(string $role): static
    {
        Session::put('hr_user', $this->migratedTestUser([
            'email'       => strtolower(str_replace(' ', '.', $role)) . '@mito.id',
            'fullName'    => $role . ' User',
            'role'        => $role,
            'permissions' => config('hris.auth.role_permissions')[$role] ?? [],
            'auth_domain' => 'users',
            'entities'    => [],
            'branch'      => '',
        ]));
        return $this;
    }

    private function revokeCompensation(string $email): void
    {
        $repository = app(UserPermissionRepositoryInterface::class);
        foreach (['view_outsource_compensation', 'manage_outsource_compensation'] as $permission) {
            $repository->upsert($email, $permission, false, 'migration-test');
        }
        app(PermissionResolver::class)->forget($email);
    }

    private function makeSampleOutsources(): Collection
    {
        return collect([
            new OutsourceEmployeeData(
                outsourceId:       'OS-002',
                fullName:          'Sari Dewi',
                whatsappNumber:    '+6282198765432',
                email:             'sari@example.com',
                jobTitle:          'Kasir',
                bankAccount:       '0009876543',
                basicSalary:       4000000.0,
                incentiveAmount:   1200000.0,
                vendor:            'StaffInc',
            ),
            new OutsourceEmployeeData(
                outsourceId:       'OS-001',
                fullName:          'Budi Santoso',
                citizenIdAddress:  'Jl. Merdeka No. 1',
                birthDate:         '1995-05-20',
                birthPlace:        'Surabaya',
                lastEducation:     'SMA',
                whatsappNumber:    '+6281234567890',
                email:             'budi@example.com',
                jobTitle:          'Sales Promotor',
                workLocation:      'Toko Pusat',
                workCity:          'Jakarta',
                bankAccount:       '0123456789',
                mitoJoinDate:      '2025-01-15',
                contractStartDate: '2025-01-15',
                contractEndDate:   '2026-01-14',
                costCenter:        'Jakarta HO',
                entity:            'PT Mito',
                payrollScheme:     'Monthly',
                umkAmount:         5396000.0,
                basicSalary:       5396000.0,
                incentiveAmount:   1618800.0,
                remarks:           'Catatan',
                vendor:            'Damarindo',
                createdBy:         'HR Admin',
                createdAt:         '2025-01-10 10:00:00',
            ),
        ]);
    }

    private function mockOutsourceRepo(Collection $outsources): void
    {
        $mock = Mockery::mock(OutsourceEmployeeRepositoryInterface::class);
        $mock->shouldReceive('getAll')->andReturn($outsources);
        $this->app->instance(OutsourceEmployeeRepositoryInterface::class, $mock);

        $auditMock = Mockery::mock(AuditLogRepositoryInterface::class);
        $auditMock->shouldReceive('log')->andReturn(true);
        $this->app->instance(AuditLogRepositoryInterface::class, $auditMock);
    }

    private function loadSheet(string $content)
    {
        $this->assertNotEmpty($content, 'XLSX response body tidak boleh kosong');
        $this->assertSame('PK', substr($content, 0, 2), 'File harus diawali ZIP magic bytes (valid XLSX)');

        $path = tempnam(sys_get_temp_dir(), 'hris_os_xlsx_test_') . '.xlsx';
        file_put_contents($path, $content);
        try {
            return IOFactory::load($path)->getActiveSheet();
        } finally {
            @unlink($path);
        }
    }

    #[Test]
    public function super_admin_can_download_xlsx(): void
    {
        $this->actingAsRole('Super Admin');
        $this->mockOutsourceRepo(collect());

        $response = $this->get(self::URL);
        $response->assertStatus(200);
        $this->assertStringContainsString(
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            $response->headers->get('Content-Type', '')
        );
        $disposition = $response->headers->get('Content-Disposition', '');
        $this->assertStringContainsString('Data_Outsource_MITO_', $disposition);
        $this->assertStringContainsString('.xlsx', $disposition);
    }

    #[Test]
    public function admin_can_download_xlsx(): void
    {
        $this->actingAsRole('Admin');
        $this->mockOutsourceRepo(collect());

        $this->get(self::URL)->assertStatus(200);
    }

    #[Test]
    public function user_role_cannot_access_xlsx_export(): void
    {
        $this->actingAsRole('User');
        $this->get(self::URL)->assertStatus(403);
    }

    #[Test]
    public function unauthenticated_cannot_access_xlsx_export(): void
    {
        Session::forget('hr_user');
        $this->get(self::URL)->assertRedirect(route('login'));
    }

    #[Test]
    public function xlsx_has_canonical_headers_without_audit_metadata(): void
    {
        $this->actingAsRole('Super Admin');
        $this->mockOutsourceRepo(collect());

        $sheet = $this->loadSheet($this->get(self::URL)->streamedContent());

        $this->assertSame('Data Outsource', $sheet->getTitle());
        $this->assertSame(count(self::FULL_HEADERS), Coordinate::columnIndexFromString($sheet->getHighestDataColumn(1)));
        foreach (self::FULL_HEADERS as $col => $expected) {
            $this->assertSame($expected, $sheet->getCell($col . '1')->getValue(), "Header kolom {$col}1");
        }
        $this->assertSame(1, $sheet->getHighestDataRow(), 'Dataset kosong: hanya boleh ada header');
    }

    #[Test]
    public function xlsx_rows_are_sorted_by_name_and_identifiers_are_strings(): void
    {
        $this->actingAsRole('Super Admin');
        $this->mockOutsourceRepo($this->makeSampleOutsources());

        $sheet = $this->loadSheet($this->get(self::URL)->streamedContent());

        $this->assertSame('OS-001', $sheet->getCell('A2')->getValue());
        $this->assertSame('Budi Santoso', $sheet->getCell('B2')->getValue());
        $this->assertSame('Damarindo', $sheet->getCell('W2')->getValue());
        $this->assertEquals(5396000, $sheet->getCell('T2')->getValue());
        $this->assertSame('OS-002', $sheet->getCell('A3')->getValue());

        foreach (['G2' => '+6281234567890', 'L2' => '0123456789', 'L3' => '0009876543'] as $cellRef => $expected) {
            $cell = $sheet->getCell($cellRef);
            $this->assertSame($expected, (string) $cell->getValue(), "{$cellRef} nilai");
            $this->assertSame(DataType::TYPE_STRING, $cell->getDataType(), "{$cellRef} harus TYPE_STRING");
        }
    }

    #[Test]
    public function compensation_columns_hidden_without_compensation_permission(): void
    {
        $this->actingAsRole('Admin');
        $this->revokeCompensation('admin@mito.id');
        $this->mockOutsourceRepo($this->makeSampleOutsources());

        $sheet = $this->loadSheet($this->get(self::URL)->streamedContent());

        $headers = [];
        $lastCol = Coordinate::columnIndexFromString($sheet->getHighestDataColumn(1));
        for ($i = 1; $i <= $lastCol; $i++) {
            $headers[] = $sheet->getCell(Coordinate::stringFromColumnIndex($i) . '1')->getValue();
        }

        $this->assertNotContains('Basic Salary', $headers);
        $this->assertNotContains('Incentive Amount (30%)', $headers);
        $this->assertContains('UMK Amount', $headers);
        $this->assertCount(count(self::FULL_HEADERS) - 2, $headers);
    }
}
