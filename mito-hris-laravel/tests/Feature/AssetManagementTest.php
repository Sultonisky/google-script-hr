<?php

namespace Tests\Feature;

use App\Enums\AssetCategory;
use App\Enums\AssetStatus;
use App\Models\Asset;
use App\Repositories\Contracts\EmployeeRepositoryInterface;
use App\Repositories\Local\LocalEmployeeRepository;
use App\Services\AssetService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AssetManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // AssetService resolves assignee identity through the employee provider.
        // Tests use the isolated local dummy source (EmployeeSheetsRepository is
        // Google-bound and unavailable in CI).
        $this->app->instance(EmployeeRepositoryInterface::class, new LocalEmployeeRepository());
    }

    private function service(): AssetService
    {
        return app(AssetService::class);
    }

    private function makeAsset(array $attrs = []): Asset
    {
        return Asset::create(array_merge([
            'category'   => AssetCategory::ELEKTRONIK,
            'name'       => 'Laptop Dell',
            'status'     => AssetStatus::AVAILABLE,
            'created_by' => 'test@mito.id',
        ], $attrs));
    }

    // ============ Basic creation ============

    #[Test]
    public function asset_can_be_created_without_code(): void
    {
        $asset = $this->makeAsset(['asset_code' => null]);
        $this->assertNull($asset->asset_code);
        $this->assertDatabaseHas('assets', ['id' => $asset->id]);
    }

    #[Test]
    public function asset_can_be_created_with_manual_code(): void
    {
        $asset = $this->makeAsset(['asset_code' => 'ELK-IT-001']);
        $this->assertSame('ELK-IT-001', $asset->asset_code);
    }

    #[Test]
    public function duplicate_manual_code_is_rejected(): void
    {
        $this->makeAsset(['asset_code' => 'ELK-IT-001']);
        $this->expectException(\Illuminate\Database\UniqueConstraintViolationException::class);
        $this->makeAsset(['asset_code' => 'ELK-IT-001']);
    }

    // ============ Code generation ============

    #[Test]
    public function automatic_code_generation_works(): void
    {
        $this->makeAsset(['asset_code' => 'ELK-00001', 'name' => 'A']);
        $this->assertSame('ELK-00002', $this->service()->generateCode(AssetCategory::ELEKTRONIK));
    }

    #[Test]
    public function sequence_uses_highest_existing_numeric_code(): void
    {
        foreach (['ELK-00001', 'ELK-00002', 'ELK-00005'] as $i => $code) {
            $this->makeAsset(['asset_code' => $code, 'name' => "A{$i}"]);
        }
        $this->assertSame('ELK-00006', $this->service()->generateCode(AssetCategory::ELEKTRONIK));
    }

    #[Test]
    public function manual_custom_codes_do_not_interfere_with_numeric_sequence(): void
    {
        $this->makeAsset(['asset_code' => 'ELK-CUSTOM-001', 'name' => 'Manual']);
        $this->makeAsset(['asset_code' => 'ELK-00001', 'name' => 'A']);
        $this->makeAsset(['asset_code' => 'ELK-00002', 'name' => 'B']);
        $this->assertSame('ELK-00003', $this->service()->generateCode(AssetCategory::ELEKTRONIK));
    }

    #[Test]
    public function multiple_categories_generate_independent_sequences(): void
    {
        $this->makeAsset(['category' => AssetCategory::ELEKTRONIK, 'asset_code' => 'ELK-00001', 'name' => 'A']);
        $this->makeAsset(['category' => AssetCategory::VEHICLE, 'asset_code' => 'VHL-00007', 'name' => 'B']);
        $this->assertSame('ELK-00002', $this->service()->generateCode(AssetCategory::ELEKTRONIK));
        $this->assertSame('VHL-00008', $this->service()->generateCode(AssetCategory::VEHICLE));
    }
    // ============ Bulk generation ============

    #[Test]
    public function bulk_generation_only_fills_missing_codes(): void
    {
        $this->makeAsset(['name' => 'Laptop Dell', 'category' => AssetCategory::ELEKTRONIK, 'asset_code' => null]);
        $this->makeAsset(['name' => 'Monitor LG', 'category' => AssetCategory::ELEKTRONIK, 'asset_code' => null]);
        $this->makeAsset(['name' => 'Toyota Avanza', 'category' => AssetCategory::VEHICLE, 'asset_code' => null]);
        $this->makeAsset(['name' => 'Ada Kode', 'category' => AssetCategory::VEHICLE, 'asset_code' => 'VHL-MAN-001']);

        $summary = $this->service()->generateCodesForMissingAssets();

        $this->assertSame(3, $summary['total']);
        $this->assertSame(3, $summary['generated']);
        $this->assertSame(0, $summary['skipped']);

        $elks = Asset::where('category', AssetCategory::ELEKTRONIK->value)->orderBy('id')->pluck('asset_code');
        foreach ($elks as $code) {
            $this->assertStringStartsWith('ELK-', $code);
        }
        $this->assertSame('ELK-00001', $elks->values()[0]);
        $this->assertSame('ELK-00002', $elks->values()[1]);

        $vhls = Asset::where('category', AssetCategory::VEHICLE->value)->orderBy('id')->pluck('asset_code');
        $this->assertSame('VHL-00001', $vhls->values()[0]);
        $this->assertSame('VHL-MAN-001', $vhls->values()[1]);
    }

    #[Test]
    public function existing_codes_are_never_overwritten_in_bulk(): void
    {
        $this->makeAsset(['name' => 'Manual', 'asset_code' => 'ELK-IT-001']);
        $this->makeAsset(['name' => 'No Code', 'asset_code' => null]);
        $summary = $this->service()->generateCodesForMissingAssets();
        $this->assertSame(1, $summary['generated']);
        $this->assertSame('ELK-IT-001', Asset::where('name', 'Manual')->first()->asset_code);
    }
    // ============ Assignment / return / lifecycle ============

    #[Test]
    public function asset_assignment_works_and_marks_status_assigned(): void
    {
        $asset = $this->makeAsset(['name' => 'Laptop', 'asset_code' => 'ELK-00001']);
        $service = $this->service();
        $service->assignAsset($asset, '2019031401', '2026-01-10', 'Laptop kerja', 'admin@mito.id');
        $asset->refresh();
        $this->assertSame(AssetStatus::ASSIGNED->value, $asset->status->value);
        $this->assertDatabaseHas('asset_assignments', [
            'asset_id'      => $asset->id,
            'employee_id'   => '2019031401',
            'employee_name' => 'Budi Santoso',
            'status'        => 'active',
        ]);
    }

    #[Test]
    public function only_one_active_assignment_can_exist(): void
    {
        $asset = $this->makeAsset(['name' => 'Laptop', 'asset_code' => 'ELK-00001']);
        $service = $this->service();
        $service->assignAsset($asset, '2019031401', '2026-01-10');
        $this->expectException(\RuntimeException::class);
        $service->assignAsset($asset, '2021072301', '2026-01-11');
    }

    #[Test]
    public function asset_return_works_and_restores_available_status(): void
    {
        $asset = $this->makeAsset(['name' => 'Laptop', 'asset_code' => 'ELK-00001']);
        $service = $this->service();
        $service->assignAsset($asset, '2019031401', '2026-01-10');
        $service->returnAsset($asset, '2026-05-20', 'Kembali normal', 'admin@mito.id');
        $asset->refresh();
        $this->assertSame(AssetStatus::AVAILABLE->value, $asset->status->value);
        $this->assertDatabaseHas('asset_assignments', [
            'asset_id'    => $asset->id,
            'status'      => 'returned',
            'return_date' => '2026-05-20 00:00:00',
        ]);
    }

    #[Test]
    public function disposed_asset_cannot_be_assigned(): void
    {
        $asset = $this->makeAsset(['name' => 'Gedung', 'status' => AssetStatus::DISPOSED]);
        $this->expectException(\RuntimeException::class);
        $this->service()->assignAsset($asset, '2019031401', '2026-01-10');
    }

    #[Test]
    public function lost_asset_cannot_be_assigned(): void
    {
        $asset = $this->makeAsset(['name' => 'Laptop', 'status' => AssetStatus::LOST]);
        $this->expectException(\RuntimeException::class);
        $this->service()->assignAsset($asset, '2019031401', '2026-01-10');
    }

    // ============ Audit logging ============

    #[Test]
    public function audit_log_occurs_when_asset_is_created_and_assigned(): void
    {
        $audit = $this->createMock(\App\Repositories\Contracts\AuditLogRepositoryInterface::class);
        $audit->expects($this->atLeastOnce())->method('log');

        // Bind the mock so AssetService receives it.
        $this->app->instance(\App\Repositories\Contracts\AuditLogRepositoryInterface::class, $audit);
        $service = app(AssetService::class);

        $asset = $this->makeAsset(['name' => 'Laptop', 'asset_code' => 'ELK-00001']);
        $service->assignAsset($asset, '2019031401', '2026-01-10', null, 'test@mito.id');
    }
}
