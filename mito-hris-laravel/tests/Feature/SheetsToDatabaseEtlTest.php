<?php

namespace Tests\Feature;

use App\Models\Candidate;
use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Models\MprRequestor;
use App\Models\Permission;
use App\Models\User;
use App\Models\UserPermission;
use App\Services\Etl\SheetsToDatabaseEtlService;
use App\Services\Google\GoogleSheetsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

/**
 * ETL Sheets → DB is read-only on Spreadsheet (getRowsAsAssoc only).
 */
class SheetsToDatabaseEtlTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function bindSheets(array $bySheetName): void
    {
        $sheets = Mockery::mock(GoogleSheetsService::class);
        $sheets->shouldReceive('getRowsAsAssoc')
            ->andReturnUsing(function (string $sheetName, bool $useCache = true) use ($bySheetName) {
                return $bySheetName[$sheetName] ?? [];
            });
        // Hard guarantee: write APIs must never be called.
        $sheets->shouldReceive('appendRow')->never();
        $sheets->shouldReceive('updateRow')->never();
        $sheets->shouldReceive('clearAllSheets')->never();
        $sheets->shouldReceive('ensureSheetHeaders')->never();

        $this->app->instance(GoogleSheetsService::class, $sheets);
    }

    public function test_dry_run_counts_without_writing(): void
    {
        $this->bindSheets([
            'Employee' => [[
                'Employee ID' => 'EMP-1',
                'Full Name' => 'Budi',
                'Status Employee' => 'Contract',
                'NIK - NPWP 16 digit' => '3175010101010001',
            ]],
            'Users' => [[
                'Email' => 'hr@mito.id',
                'Full Name' => 'HR',
                'Role' => 'Admin',
                'Status' => 'Active',
                'Password Hash' => '$2y$10$example',
            ]],
        ]);

        $this->artisan('mito:etl-sheets-to-db', [
            '--dry-run' => true,
            '--only' => 'employees,users',
        ])->assertSuccessful();

        $this->assertSame(0, Employee::count());
        $this->assertSame(0, User::count());
    }

    public function test_import_upserts_employees_users_permissions_candidates(): void
    {
        $this->bindSheets([
            'Employee' => [[
                'Employee ID' => 'EMP-1',
                'Full Name' => 'Budi Santoso',
                'Department' => 'IT',
                'Status Employee' => 'Contract',
                'NIK - NPWP 16 digit' => "'3175010101010001",
            ]],
            'Users' => [[
                'Email' => 'hr@mito.id',
                'Full Name' => 'HR Admin',
                'Role' => 'Super Admin',
                'Status' => 'Active',
                'Password Hash' => '$2y$10$keepme',
            ]],
            'Permissions' => [[
                'Permission Key' => 'view_employees',
                'Name' => 'View employees',
                'Description' => 'desc',
                'Group' => 'Employees',
                'Status' => 'active',
            ]],
            'User_Permissions' => [[
                'User Email' => 'hr@mito.id',
                'Permission Key' => 'view_employees',
                'Granted' => 'TRUE',
                'Granted By' => 'system',
            ]],
            'data_kandidat' => [[
                'Recruitment ID' => 'REC-1',
                'Full Name' => 'Andi',
                'NIK' => "'3175010101010002",
                'Status' => 'Pending',
            ]],
            'kandidat_hold' => [],
            'kandidat_blacklist' => [],
            'kandidat_accepted' => [],
            'kandidat_probation' => [],
        ]);

        $this->artisan('mito:etl-sheets-to-db', [
            '--only' => 'employees,users,permissions,user_permissions,candidates',
        ])->assertSuccessful();

        $emp = Employee::where('employee_id', 'EMP-1')->first();
        $this->assertNotNull($emp);
        $this->assertSame('Budi Santoso', $emp->full_name);
        $this->assertSame('3175010101010001', $emp->nik_npwp);

        $user = User::where('email', 'hr@mito.id')->first();
        $this->assertNotNull($user);
        $this->assertSame('$2y$10$keepme', $user->getAttributes()['password'] ?? null);

        $this->assertSame(1, Permission::count());
        $this->assertTrue(UserPermission::where('user_email', 'hr@mito.id')->where('granted', true)->exists());

        $candidate = Candidate::where('recruitment_id', 'REC-1')->first();
        $this->assertNotNull($candidate);
        $this->assertSame('pending', $candidate->lifecycle_status);
        $this->assertSame('3175010101010002', $candidate->nik);
    }

    public function test_mpr_requestors_dedupe_duplicate_requestor_ids(): void
    {
        $this->bindSheets([
            'mpr_requestor' => [
                [
                    'Requestor ID' => 'MPR-REQ-001',
                    'Email' => 'alice@mito.co.id',
                    'Username' => 'alice',
                    'Full Name' => 'Alice',
                    'Job Position' => 'Manager',
                    'Role' => 'Manpower',
                    'Status' => 'Active',
                    'Password Hash' => '$2y$10$aaa',
                ],
                [
                    'Requestor ID' => 'MPR-REQ-001',
                    'Email' => 'bob@mito.co.id',
                    'Username' => 'bob',
                    'Full Name' => 'Bob',
                    'Job Position' => 'Manager',
                    'Role' => 'Manpower',
                    'Status' => 'Active',
                    'Password Hash' => '$2y$10$bbb',
                ],
                [
                    'Requestor ID' => 'MPR-REQ-001',
                    'Email' => 'cara@mito.co.id',
                    'Username' => 'cara',
                    'Full Name' => 'Cara',
                    'Job Position' => 'Manager',
                    'Role' => 'Manpower',
                    'Status' => 'Active',
                    'Password Hash' => '$2y$10$ccc',
                ],
            ],
        ]);

        $this->artisan('mito:etl-sheets-to-db', [
            '--only' => 'mpr_requestors',
        ])->assertSuccessful();

        $this->assertSame(3, MprRequestor::count());
        $this->assertSame('MPR-REQ-001', MprRequestor::where('email', 'alice@mito.co.id')->value('requestor_id'));
        $this->assertSame('MPR-REQ-002', MprRequestor::where('email', 'bob@mito.co.id')->value('requestor_id'));
        $this->assertSame('MPR-REQ-003', MprRequestor::where('email', 'cara@mito.co.id')->value('requestor_id'));
        $this->assertCount(3, MprRequestor::query()->pluck('requestor_id')->unique());
    }

    public function test_documents_keep_duplicate_ids_and_original_created_at(): void
    {
        $doc = fn (string $nomor, string $createdAt) => [
            'Document ID' => 'DOC-20260901-007-PKTAD',
            'Employee ID' => "'EMP-7",
            'Sequence' => '7',
            'Doc Type' => 'Kontrak PKWT TAD',
            'Doc Code' => 'PKTAD',
            'Nomor' => $nomor,
            'Entity' => 'MSI',
            'Issued At' => $createdAt,
            'Issued By' => 'HR',
            'Created At' => $createdAt,
        ];
        $this->bindSheets([
            'Employee_Documents' => [
                $doc('007/PKTAD/MSI/IX/2026', '2026-09-01 09:00:00'),
                $doc('007/PKTAD/MSI/IX/2026', '2026-09-01 14:30:00'),
            ],
        ]);

        $this->artisan('mito:etl-sheets-to-db', ['--only' => 'documents'])->assertSuccessful();
        $this->artisan('mito:etl-sheets-to-db', ['--only' => 'documents'])->assertSuccessful();

        $ids = EmployeeDocument::query()->orderBy('id')->pluck('document_id')->all();
        $this->assertSame(['DOC-20260901-007-PKTAD', 'DOC-20260901-007-PKTAD-2'], $ids);
        $this->assertSame('EMP-7', EmployeeDocument::query()->value('employee_id'));
        $this->assertSame(
            '2026-09-01 14:30:00',
            EmployeeDocument::query()->where('document_id', 'DOC-20260901-007-PKTAD-2')
                ->first()->created_at->timezone('Asia/Jakarta')->format('Y-m-d H:i:s')
        );
    }

    public function test_service_rejects_unknown_domain(): void
    {
        $this->bindSheets([]);
        $this->expectException(\RuntimeException::class);
        $this->app->make(SheetsToDatabaseEtlService::class)->run(['not-a-domain']);
    }
}
