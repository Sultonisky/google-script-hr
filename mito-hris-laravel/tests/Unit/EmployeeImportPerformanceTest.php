<?php

namespace Tests\Unit;

use App\Repositories\Contracts\AuditLogRepositoryInterface;
use App\Repositories\Contracts\EmployeeRepositoryInterface;
use App\Services\EmployeeIdGenerator;
use App\Services\EmployeeService;
use App\Services\Google\GoogleDriveService;
use App\Services\Google\GoogleSheetsService;
use App\Services\PdfGeneratorService;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\Test;

class EmployeeImportPerformanceTest extends \Tests\TestCase
{
    #[Test]
    public function it_batches_employee_rows_and_logs_once_for_import(): void
    {
        $employeeRepo = $this->createMock(EmployeeRepositoryInterface::class);
        $auditRepo = $this->createMock(AuditLogRepositoryInterface::class);
        $idGenerator = $this->createMock(EmployeeIdGenerator::class);
        $driveService = $this->createMock(GoogleDriveService::class);
        $pdfService = $this->createMock(PdfGeneratorService::class);
        $sheets = $this->createMock(GoogleSheetsService::class);

        $employeeRepo->method('getAll')->willReturn(new Collection());
        $idGenerator->method('generateBatch')->willReturn(['2026010101', '2026010102']);

        $sheets->expects($this->once())
            ->method('appendRows')
            ->with('Employee', $this->callback(function ($rows) {
                return is_array($rows)
                    && count($rows) === 2
                    && trim((string) $rows[0][6]) === 'Staff IT'
                    && trim((string) $rows[0][35]) === 'CC-100';
            }))
            ->willReturn(true);

        $auditRepo->expects($this->once())
            ->method('log')
            ->with(
                'Employee',
                $this->stringContains('IMPORT-'),
                'Import',
                'Status Employee',
                '-',
                $this->stringContains('2 karyawan'),
                $this->anything(),
                'Dashboard'
            );

        $service = new EmployeeService(
            $employeeRepo,
            $auditRepo,
            $idGenerator,
            $driveService,
            $pdfService,
            $sheets
        );

        $result = $service->importEmployees([
            [
                'fullName' => 'Budi Santoso',
                'employeeId' => 'EMP-001',
                'statusEmployee' => 'Contract',
                'jobPositionLocation' => 'Staff IT',
                'positionNoLocCurrent' => 'Staff IT',
                'costCenter' => 'CC-100',
            ],
            [
                'fullName' => 'Siti Rahmawati',
                'employeeId' => 'EMP-002',
                'statusEmployee' => 'Permanent',
                'jobPositionLocation' => 'Senior Analyst',
                'positionNoLocCurrent' => 'Senior Analyst',
                'costCenter' => 'CC-200',
            ],
        ], 'Tester');

        $this->assertTrue($result['success']);
        $this->assertSame(2, $result['imported']);
    }
}
