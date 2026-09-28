<?php

namespace Tests\Unit;

use App\DTOs\EmployeeData;
use App\Repositories\Contracts\AuditLogRepositoryInterface;
use App\Repositories\Contracts\EmployeeRepositoryInterface;
use App\Services\EmployeeIdGenerator;
use App\Services\EmployeeService;
use App\Services\Google\GoogleDriveService;
use App\Services\PdfGeneratorService;
use App\Services\SkNumberService;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\Test;

class EmployeeImportPerformanceTest extends \Tests\TestCase
{
    #[Test]
    public function it_creates_employees_via_repository_and_logs_once_for_import(): void
    {
        $employeeRepo = $this->createMock(EmployeeRepositoryInterface::class);
        $auditRepo = $this->createMock(AuditLogRepositoryInterface::class);
        $idGenerator = $this->createMock(EmployeeIdGenerator::class);
        $driveService = $this->createMock(GoogleDriveService::class);
        $pdfService = $this->createMock(PdfGeneratorService::class);
        $skNumbers = $this->createMock(SkNumberService::class);

        $employeeRepo->method('getAll')->willReturn(new Collection());
        $idGenerator->method('generateBatch')->willReturn(['2026010101', '2026010102']);

        $createCalls = [];
        $employeeRepo->expects($this->exactly(2))
            ->method('create')
            ->willReturnCallback(function (EmployeeData $data) use (&$createCalls) {
                $createCalls[] = $data;
                $this->assertSame(
                    $data->employeeId === 'EMP-001' ? 'Staff IT' : 'Senior Analyst',
                    trim((string) $data->jobPositionLocation)
                );
                $this->assertSame(
                    $data->employeeId === 'EMP-001' ? 'CC-100' : 'CC-200',
                    trim((string) $data->costCenter)
                );

                return $data;
            });

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
            $skNumbers
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
        $this->assertCount(2, $createCalls);
    }
}
