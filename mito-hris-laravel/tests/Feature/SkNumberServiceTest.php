<?php

namespace Tests\Feature;

use App\DTOs\EmployeeData;
use App\Enums\SkDocumentType;
use App\Repositories\Contracts\EmployeeDocumentRepositoryInterface;
use App\Repositories\Contracts\EmployeeRepositoryInterface;
use App\Repositories\Local\ArrayEmployeeDocumentRepository;
use App\Services\SkNumberService;
use Illuminate\Support\Carbon;
use Mockery;
use Tests\TestCase;

class SkNumberServiceTest extends TestCase
{
    private ArrayEmployeeDocumentRepository $documents;
    /** @var array<string, string> */
    private array $nomorByEmployee = [];
    private SkNumberService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->documents = new ArrayEmployeeDocumentRepository();
        $this->nomorByEmployee = [];

        $employees = Mockery::mock(EmployeeRepositoryInterface::class);
        $employees->shouldReceive('findById')->andReturnUsing(function (string $id) {
            return new EmployeeData(
                employeeId: $id,
                fullName: 'Karyawan ' . $id,
                statusEmployee: 'Contract',
                branchName: $id === 'EMP-B' ? 'Stein Packing' : 'Head Office MSI',
                nomorSk: $this->nomorByEmployee[$id] ?? null,
            );
        })->byDefault();
        $employees->shouldReceive('update')->andReturnUsing(function (string $id, array $attrs) {
            if (isset($attrs['Nomor SK'])) {
                $this->nomorByEmployee[$id] = (string) $attrs['Nomor SK'];
            }
            return true;
        })->byDefault();

        $this->app->instance(EmployeeDocumentRepositoryInterface::class, $this->documents);
        $this->app->instance(EmployeeRepositoryInterface::class, $employees);

        $this->service = $this->app->make(SkNumberService::class);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_first_employee_gets_sequence_001_and_skp_code(): void
    {
        $issued = $this->service->issue(
            employeeId: 'EMP-A',
            type: SkDocumentType::PENGANGKATAN,
            branchName: 'Head Office MSI',
            issuedBy: 'HR',
            issuedAt: Carbon::parse('2026-09-15', 'Asia/Jakarta'),
        );

        $this->assertSame(1, $issued['sequence']);
        $this->assertSame('SKP', $issued['doc_code']);
        $this->assertSame('001/SKP/MSI/IX/2026', $issued['nomor']);
        $this->assertSame('001/SKP/MSI/IX/2026', $this->nomorByEmployee['EMP-A'] ?? null);
        $this->assertCount(1, $this->documents->getByEmployeeId('EMP-A'));
    }

    public function test_same_employee_keeps_fixed_sequence_across_document_types(): void
    {
        $this->service->issue('EMP-A', SkDocumentType::PENGANGKATAN, 'MSI', 'HR', issuedAt: Carbon::parse('2026-09-01', 'Asia/Jakarta'));
        $mutasi = $this->service->issue('EMP-A', SkDocumentType::MUTASI, 'MSI', 'HR', issuedAt: Carbon::parse('2026-10-01', 'Asia/Jakarta'));
        $paklaring = $this->service->issue('EMP-A', SkDocumentType::PAKLARING, 'MSI', 'HR', issuedAt: Carbon::parse('2026-11-01', 'Asia/Jakarta'));

        $this->assertSame(1, $mutasi['sequence']);
        $this->assertSame(1, $paklaring['sequence']);
        $this->assertSame('001/SKM/MSI/X/2026', $mutasi['nomor']);
        $this->assertSame('001/SPAK/MSI/XI/2026', $paklaring['nomor']);
        $this->assertSame('001/SPAK/MSI/XI/2026', $this->nomorByEmployee['EMP-A'] ?? null);
        $this->assertCount(3, $this->documents->getByEmployeeId('EMP-A'));
    }

    public function test_next_employee_gets_next_global_sequence(): void
    {
        $this->service->issue('EMP-A', SkDocumentType::PENGANGKATAN, 'MSI', 'HR');
        $b = $this->service->issue('EMP-B', SkDocumentType::OFFBOARDING, 'Stein Packing', 'HR', issuedAt: Carbon::parse('2026-09-20', 'Asia/Jakarta'));

        $this->assertSame(2, $b['sequence']);
        $this->assertSame('002/SKO/SPI/IX/2026', $b['nomor']);
    }

    public function test_resolve_for_pdf_prefers_history_by_type(): void
    {
        $this->service->issue('EMP-A', SkDocumentType::PENGANGKATAN, 'MSI', 'HR', issuedAt: Carbon::parse('2026-09-01', 'Asia/Jakarta'));
        $this->service->issue('EMP-A', SkDocumentType::MUTASI, 'MSI', 'HR', issuedAt: Carbon::parse('2026-10-01', 'Asia/Jakarta'));

        $this->assertSame(
            '001/SKP/MSI/IX/2026',
            $this->service->resolveForPdf('EMP-A', SkDocumentType::PENGANGKATAN)
        );
        $this->assertSame(
            '001/SKM/MSI/X/2026',
            $this->service->resolveForPdf('EMP-A', SkDocumentType::MUTASI)
        );
    }

    public function test_rotation_type_codes(): void
    {
        $this->assertSame(SkDocumentType::PROMOSI, SkDocumentType::fromRotationType('Promosi'));
        $this->assertSame(SkDocumentType::DEMOSI, SkDocumentType::fromRotationType('Demosi'));
        $this->assertSame(SkDocumentType::MUTASI, SkDocumentType::fromRotationType('Mutasi'));
    }

    public function test_pkwt_contract_uses_same_fixed_sequence_family(): void
    {
        $pkwt = $this->service->issue(
            'EMP-A',
            SkDocumentType::PKWT,
            'Head Office MSI',
            'HR',
            issuedAt: Carbon::parse('2026-05-01', 'Asia/Jakarta')
        );
        $skp = $this->service->issue(
            'EMP-A',
            SkDocumentType::PENGANGKATAN,
            'Head Office MSI',
            'HR',
            issuedAt: Carbon::parse('2026-11-01', 'Asia/Jakarta')
        );

        $this->assertSame(1, $pkwt['sequence']);
        $this->assertSame(1, $skp['sequence']);
        $this->assertSame('001/PKWT/MSI/V/2026', $pkwt['nomor']);
        $this->assertSame('001/SKP/MSI/XI/2026', $skp['nomor']);
        // Nomor SK terakhir = dokumen terbaru (SKP)
        $this->assertSame('001/SKP/MSI/XI/2026', $this->nomorByEmployee['EMP-A'] ?? null);
        $history = $this->documents->getByEmployeeId('EMP-A');
        $this->assertCount(2, $history);
        $this->assertSame('PKWT', $history[0]['Doc Code']);
        $this->assertSame('SKP', $history[1]['Doc Code']);
    }

    public function test_pktad_uses_pktad_code(): void
    {
        $issued = $this->service->issue(
            'EMP-B',
            SkDocumentType::PKTAD,
            'Stein Packing',
            'HR',
            issuedAt: Carbon::parse('2026-09-10', 'Asia/Jakarta')
        );

        $this->assertSame('001/PKTAD/SPI/IX/2026', $issued['nomor']);
        $this->assertTrue(SkDocumentType::PKTAD->isContract());
        $this->assertTrue(SkDocumentType::PKWT->isContract());
        $this->assertFalse(SkDocumentType::PENGANGKATAN->isContract());
    }
}
