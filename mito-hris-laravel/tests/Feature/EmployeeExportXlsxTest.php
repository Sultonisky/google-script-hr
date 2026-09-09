<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Collection;
use Mockery;
use App\Repositories\Contracts\EmployeeRepositoryInterface;
use App\DTOs\EmployeeData;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;

/**
 * Employee XLSX Export — Structure & Content Tests
 *
 * Menggunakan mocked EmployeeRepositoryInterface agar tidak memerlukan
 * koneksi Google Sheets. Test ini memverifikasi bahwa file yang dihasilkan
 * benar-benar valid XLSX (bukan CSV), dengan 36 kolom header yang benar
 * dan data types yang tepat untuk field identifier.
 */
class EmployeeExportXlsxTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /** Build a session user array for a given role. */
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

    /**
     * Build two sample EmployeeData rows with realistic identifier values
     * that must survive as strings (not be converted to scientific notation).
     */
    private function makeSampleEmployees(): Collection
    {
        return collect([
            new EmployeeData(
                employeeId:          'EMP-001',
                fullName:            'Budi Santoso',
                branchName:          'Jakarta HO',
                division:            'Technology',
                department:          'Engineering',
                jobPositionLocation: 'Jakarta',
                jobPosition:         'Software Engineer',
                areaKerja:           'Pusat',
                lokasiKerja:         'Jakarta Selatan',
                jobLevel:            'Staff',
                grade:               'G3',
                joinDate:            '2022-01-15',
                statusEmployee:      'Permanent',
                directSuperior:      'Ahmad Direktur',
                indirectSuperior:    'Siti Manager',
                personalEmail:       'budi.santoso@gmail.com',
                workingEmail:        'budi.santoso@mito.id',
                endDateContract:     '',
                birthPlace:          'Surabaya',
                birthDate:           '1990-05-20',
                citizenIdAddress:    'Jl. Merdeka No. 1, Surabaya',
                residentialAddress:  'Jl. Kemang No. 5, Jakarta Selatan',
                nikNpwp:             '3301010520900001',   // 16-digit — must stay as string
                npwp:                '123456789012345',    // 15-digit — must stay as string
                ptkpStatus:          'TK/0',
                bankName:            'BCA',
                bankAccount:         '1234567890',         // leading zero risk — must stay as string
                bankAccountHolder:   'Budi Santoso',
                bpjsKetenagakerjaan: '10123456789012',     // 14-digit — must stay as string
                bpjsKesehatan:       '0001234567890',      // 13-digit — must stay as string
                mobilePhone:         '081234567890',       // leading zero — must stay as string
                religion:            'Islam',
                gender:              'L',
                maritalStatus:       'Menikah',
                bloodType:           'O',
                costCenter:          'CC-TECH-001',
            ),
            new EmployeeData(
                employeeId:          'EMP-002',
                fullName:            'Sari Dewi',
                branchName:          'Surabaya Branch',
                division:            'Finance',
                department:          'Accounting',
                jobPositionLocation: 'Surabaya',
                jobPosition:         'Accountant',
                areaKerja:           'Cabang',
                lokasiKerja:         'Surabaya Barat',
                jobLevel:            'Senior Staff',
                grade:               'G4',
                joinDate:            '2020-03-01',
                statusEmployee:      'Permanent',
                directSuperior:      'Hendra CFO',
                indirectSuperior:    '',
                personalEmail:       'sari.dewi@yahoo.com',
                workingEmail:        'sari.dewi@mito.id',
                endDateContract:     '',
                birthPlace:          'Bandung',
                birthDate:           '1988-11-10',
                citizenIdAddress:    'Jl. Diponegoro No. 22, Bandung',
                residentialAddress:  'Jl. Rungkut No. 15, Surabaya',
                nikNpwp:             '3204091110880002',
                npwp:                '987654321098765',
                ptkpStatus:          'K/1',
                bankName:            'Mandiri',
                bankAccount:         '0000987654321',
                bankAccountHolder:   'Sari Dewi',
                bpjsKetenagakerjaan: '10987654321098',
                bpjsKesehatan:       '0009876543210',
                mobilePhone:         '082198765432',
                religion:            'Kristen',
                gender:              'P',
                maritalStatus:       'Menikah',
                bloodType:           'A',
                costCenter:          'CC-FIN-001',
            ),
        ]);
    }

    /**
     * Bind a mocked EmployeeRepository that returns $employees.
     * Also mocks AuditLogRepositoryInterface so audit log doesn't throw.
     */
    private function mockEmployeeRepo(Collection $employees): void
    {
        $mock = Mockery::mock(EmployeeRepositoryInterface::class);
        $mock->shouldReceive('getAll')->andReturn($employees);
        $this->app->instance(EmployeeRepositoryInterface::class, $mock);

        $auditMock = Mockery::mock(\App\Repositories\Contracts\AuditLogRepositoryInterface::class);
        $auditMock->shouldReceive('log')->andReturn(true);
        $this->app->instance(\App\Repositories\Contracts\AuditLogRepositoryInterface::class, $auditMock);
    }

    // -------------------------------------------------------------------------
    // Authorization tests (route-level, tidak perlu real data)
    // -------------------------------------------------------------------------

    #[Test]
    public function old_csv_route_no_longer_exists(): void
    {
        // employees-csv route sudah dihapus — harus 404 (route tidak terdaftar)
        $this->actingAsRole('Super Admin');
        $this->get('/hr/export/employees-csv')->assertStatus(404);
    }

    #[Test]
    public function new_xlsx_route_exists_and_is_accessible_to_super_admin(): void
    {
        $this->actingAsRole('Super Admin');
        $this->mockEmployeeRepo(collect());
        $response = $this->get('/hr/export/employees-xlsx');
        $this->assertNotSame(404, $response->getStatusCode(), 'Route employees-xlsx harus terdaftar');
        $this->assertNotSame(403, $response->getStatusCode(), 'Super Admin harus diizinkan');
    }

    #[Test]
    public function user_role_cannot_access_xlsx_export(): void
    {
        $this->actingAsRole('User');
        $this->get('/hr/export/employees-xlsx')->assertStatus(403);
    }

    #[Test]
    public function unauthenticated_cannot_access_xlsx_export(): void
    {
        Session::forget('hr_user');
        $this->get('/hr/export/employees-xlsx')->assertRedirect(route('login'));
    }

    // -------------------------------------------------------------------------
    // XLSX content-type & filename tests
    // -------------------------------------------------------------------------

    #[Test]
    public function response_has_xlsx_content_type(): void
    {
        $this->actingAsRole('Super Admin');
        $this->mockEmployeeRepo(collect());

        $response = $this->get('/hr/export/employees-xlsx');
        $response->assertStatus(200);

        $contentType = $response->headers->get('Content-Type', '');
        $this->assertStringContainsString(
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            $contentType,
            'Content-Type harus XLSX MIME type'
        );
    }

    #[Test]
    public function response_has_xlsx_filename_not_csv(): void
    {
        $this->actingAsRole('Super Admin');
        $this->mockEmployeeRepo(collect());

        $response    = $this->get('/hr/export/employees-xlsx');
        $disposition = $response->headers->get('Content-Disposition', '');

        $this->assertStringContainsString('Data_Karyawan_MITO_', $disposition, 'Filename prefix harus Data_Karyawan_MITO_');
        $this->assertStringContainsString('.xlsx', $disposition, 'Filename harus berekstensi .xlsx');
        $this->assertStringNotContainsString('.csv', $disposition, 'Filename tidak boleh berekstensi .csv');
    }

    // -------------------------------------------------------------------------
    // XLSX ZIP magic bytes — bukan CSV
    // -------------------------------------------------------------------------

    #[Test]
    public function response_body_starts_with_zip_magic_bytes(): void
    {
        $this->actingAsRole('Super Admin');
        $this->mockEmployeeRepo(collect());

        $content = $this->get('/hr/export/employees-xlsx')->streamedContent();
        $this->assertNotEmpty($content, 'Response body tidak boleh kosong');

        // Valid XLSX adalah ZIP — harus diawali 0x50 0x4B ("PK")
        $this->assertSame(
            'PK',
            substr($content, 0, 2),
            'File harus diawali ZIP magic bytes PK — ini menunjukkan file adalah ZIP/XLSX native, bukan CSV'
        );
    }

    // -------------------------------------------------------------------------
    // XLSX structure — 36 header columns
    // -------------------------------------------------------------------------

    #[Test]
    public function xlsx_contains_worksheet_named_data_karyawan(): void
    {
        $this->actingAsRole('Super Admin');
        $this->mockEmployeeRepo(collect());

        $content = $this->get('/hr/export/employees-xlsx')->streamedContent();
        $tmpFile = $this->writeToTempXlsx($content);

        try {
            $spreadsheet = IOFactory::load($tmpFile);
            $this->assertSame('Data Karyawan', $spreadsheet->getActiveSheet()->getTitle());
            $spreadsheet->disconnectWorksheets();
        } finally {
            @unlink($tmpFile);
        }
    }

    #[Test]
    public function xlsx_has_exactly_36_header_columns(): void
    {
        $this->actingAsRole('Super Admin');
        $this->mockEmployeeRepo(collect());

        $content = $this->get('/hr/export/employees-xlsx')->streamedContent();
        $tmpFile = $this->writeToTempXlsx($content);

        try {
            $spreadsheet  = IOFactory::load($tmpFile);
            $sheet        = $spreadsheet->getActiveSheet();
            $highestColNo = Coordinate::columnIndexFromString($sheet->getHighestDataColumn(1));

            $this->assertSame(36, $highestColNo, 'Harus ada tepat 36 kolom header');
            $spreadsheet->disconnectWorksheets();
        } finally {
            @unlink($tmpFile);
        }
    }

    #[Test]
    public function xlsx_header_row_has_correct_column_values(): void
    {
        $this->actingAsRole('Super Admin');
        $this->mockEmployeeRepo(collect());

        $content = $this->get('/hr/export/employees-xlsx')->streamedContent();
        $tmpFile = $this->writeToTempXlsx($content);

        try {
            $sheet = IOFactory::load($tmpFile)->getActiveSheet();

            $expectedHeaders = [
                'A'  => 'Employee ID',
                'B'  => 'Full Name',
                'C'  => 'Branch Name',
                'D'  => 'Division',
                'E'  => 'Department',
                'F'  => 'Job Position (Location)',
                'G'  => 'Job Position',
                'H'  => 'Area Kerja',
                'I'  => 'Lokasi Kerja',
                'J'  => 'Job Level',
                'K'  => 'Grade',
                'L'  => 'Join Date',
                'M'  => 'Status Employee',
                'N'  => 'Direct Superior',
                'O'  => 'Indirect Superior',
                'P'  => 'Personal Email',
                'Q'  => 'Working Email',
                'R'  => 'End Date (Contract)',
                'S'  => 'Birth Place',
                'T'  => 'Birth Date',
                'U'  => 'Citizen ID Address',
                'V'  => 'Residential Address',
                'W'  => 'NIK - NPWP 16 digit',
                'X'  => 'NPWP',
                'Y'  => 'PTKP Status',
                'Z'  => 'Bank Name',
                'AA' => 'Bank Account',
                'AB' => 'Bank Account Holder',
                'AC' => 'BPJS Ketenagakerjaan',
                'AD' => 'BPJS Kesehatan',
                'AE' => 'Mobile Phone',
                'AF' => 'Religion',
                'AG' => 'Gender',
                'AH' => 'Marital Status',
                'AI' => 'Blood Type',
                'AJ' => 'Cost Center',
            ];

            foreach ($expectedHeaders as $col => $expected) {
                $actual = $sheet->getCell($col . '1')->getValue();
                $this->assertSame(
                    $expected,
                    $actual,
                    "Header kolom {$col}1: expected '{$expected}', got '{$actual}'"
                );
            }
        } finally {
            @unlink($tmpFile);
        }
    }

    // -------------------------------------------------------------------------
    // Data rows — correct values & string types for identifiers
    // -------------------------------------------------------------------------

    #[Test]
    public function xlsx_data_row_values_are_in_correct_columns(): void
    {
        $this->actingAsRole('Super Admin');
        $this->mockEmployeeRepo($this->makeSampleEmployees());

        $content = $this->get('/hr/export/employees-xlsx')->streamedContent();
        $tmpFile = $this->writeToTempXlsx($content);

        try {
            $sheet = IOFactory::load($tmpFile)->getActiveSheet();

            // Row 2 = first data row (EMP-001)
            $this->assertSame('EMP-001',               $sheet->getCell('A2')->getValue());
            $this->assertSame('Budi Santoso',           $sheet->getCell('B2')->getValue());
            $this->assertSame('Jakarta HO',             $sheet->getCell('C2')->getValue());
            $this->assertSame('Technology',             $sheet->getCell('D2')->getValue());
            $this->assertSame('Engineering',            $sheet->getCell('E2')->getValue());
            $this->assertSame('Software Engineer',      $sheet->getCell('G2')->getValue());
            $this->assertSame('Permanent',              $sheet->getCell('M2')->getValue());
            $this->assertSame('budi.santoso@mito.id',  $sheet->getCell('Q2')->getValue());
            $this->assertSame('CC-TECH-001',            $sheet->getCell('AJ2')->getValue());

            // Row 3 = second data row (EMP-002)
            $this->assertSame('EMP-002',  $sheet->getCell('A3')->getValue());
            $this->assertSame('Sari Dewi', $sheet->getCell('B3')->getValue());
        } finally {
            @unlink($tmpFile);
        }
    }

    #[Test]
    public function xlsx_identifier_columns_stored_as_string_not_number(): void
    {
        $this->actingAsRole('Super Admin');
        $this->mockEmployeeRepo($this->makeSampleEmployees());

        $content = $this->get('/hr/export/employees-xlsx')->streamedContent();
        $tmpFile = $this->writeToTempXlsx($content);

        try {
            $sheet = IOFactory::load($tmpFile)->getActiveSheet();

            // Identifier columns that MUST be stored as TYPE_STRING
            $identifierCells = [
                'W2' => ['value' => '3301010520900001', 'label' => 'NIK - NPWP 16 digit'],
                'X2' => ['value' => '123456789012345',  'label' => 'NPWP'],
                'AA2' => ['value' => '1234567890',       'label' => 'Bank Account'],
                'AC2' => ['value' => '10123456789012',   'label' => 'BPJS Ketenagakerjaan'],
                'AD2' => ['value' => '0001234567890',    'label' => 'BPJS Kesehatan'],
                'AE2' => ['value' => '081234567890',     'label' => 'Mobile Phone'],
            ];

            foreach ($identifierCells as $cellRef => $info) {
                $cell = $sheet->getCell($cellRef);

                // 1. Nilai harus benar
                $this->assertSame(
                    $info['value'],
                    (string) $cell->getValue(),
                    "{$info['label']} ({$cellRef}): nilai harus '{$info['value']}'"
                );

                // 2. DataType harus string
                $this->assertSame(
                    DataType::TYPE_STRING,
                    $cell->getDataType(),
                    "{$info['label']} ({$cellRef}): DataType harus TYPE_STRING"
                );

                // 3. Tidak boleh scientific notation
                $this->assertDoesNotMatchRegularExpression(
                    '/^\d+\.?\d*[eE][+\-]\d+$/i',
                    (string) $cell->getValue(),
                    "{$info['label']} ({$cellRef}): tidak boleh scientific notation"
                );
            }
        } finally {
            @unlink($tmpFile);
        }
    }

    #[Test]
    public function xlsx_with_empty_dataset_produces_valid_file_with_headers_only(): void
    {
        $this->actingAsRole('Super Admin');
        $this->mockEmployeeRepo(collect()); // empty

        $content = $this->get('/hr/export/employees-xlsx')->streamedContent();
        $tmpFile = $this->writeToTempXlsx($content);

        try {
            $sheet = IOFactory::load($tmpFile)->getActiveSheet();

            // Headers still present
            $this->assertSame('Employee ID', $sheet->getCell('A1')->getValue());
            $this->assertSame('Cost Center',  $sheet->getCell('AJ1')->getValue());

            // No data row — highest row should be 1 (header only)
            $highestRow = $sheet->getHighestDataRow();
            $this->assertSame(1, $highestRow, 'Dataset kosong: hanya boleh ada 1 row (header)');
        } finally {
            @unlink($tmpFile);
        }
    }

    // -------------------------------------------------------------------------
    // Helper
    // -------------------------------------------------------------------------

    /** Write raw XLSX content to a temp file and return the path. */
    private function writeToTempXlsx(string $content): string
    {
        $this->assertNotEmpty($content, 'XLSX response body tidak boleh kosong');
        $this->assertSame('PK', substr($content, 0, 2), 'File harus diawali ZIP magic bytes (valid XLSX)');

        $path = tempnam(sys_get_temp_dir(), 'hris_xlsx_test_') . '.xlsx';
        file_put_contents($path, $content);
        return $path;
    }
}
