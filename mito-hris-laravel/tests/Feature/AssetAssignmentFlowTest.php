<?php

namespace Tests\Feature;

use App\Enums\AssetCategory;
use App\Enums\AssetStatus;
use App\Models\Asset;
use App\Repositories\Contracts\AuditLogRepositoryInterface;
use App\Repositories\Contracts\EmployeeRepositoryInterface;
use App\Repositories\Local\LocalEmployeeRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Session;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AssetAssignmentFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app->instance(EmployeeRepositoryInterface::class, new LocalEmployeeRepository());
        $this->app->instance(
            AuditLogRepositoryInterface::class,
            $this->createMock(AuditLogRepositoryInterface::class),
        );
    }

    private function actingAsGaIt(): static
    {
        Session::put('hr_user', $this->migratedTestUser([
            'email'       => 'ga.it@mito.id',
            'fullName'    => 'GA IT User',
            'role'        => 'GA_IT',
            'permissions' => config('hris.auth.role_permissions')['GA_IT'] ?? [],
            'auth_domain' => 'users',
            'entities'    => [],
            'branch'      => '',
        ]));

        return $this;
    }

    private function makeAsset(): Asset
    {
        return Asset::create([
            'asset_code' => 'ELK-10001',
            'category'   => AssetCategory::ELEKTRONIK->value,
            'name'       => 'Laptop Dinas',
            'brand'      => 'Dell',
            'model'      => 'Latitude 5440',
            'serial_number' => 'SN-FLOW-001',
            'status'     => AssetStatus::AVAILABLE->value,
            'condition_status' => 'Good',
            'created_by' => 'ga.it@mito.id',
        ]);
    }

    #[Test]
    public function assign_to_picked_employee_persists_employee_id_and_name(): void
    {
        $this->actingAsGaIt();
        $asset = $this->makeAsset();

        $this->postJson('/hr/assets/' . $asset->id . '/assign', [
            'employee_id'   => '2019031401',
            'employee_name' => 'Tidak Dipercaya',
            'assigned_date' => '2026-09-01',
            'notes'         => 'Notebook kerja.',
        ])->assertOk()->assertJsonPath('success', true);

        $this->assertDatabaseHas('asset_assignments', [
            'asset_id'      => $asset->id,
            'employee_id'   => '2019031401',
            'employee_name' => 'Budi Santoso',   // provider value wins
            'status'        => 'active',
        ]);

        $this->assertSame(AssetStatus::ASSIGNED->value, $asset->fresh()->status->value);
    }

    #[Test]
    public function assign_with_unknown_employee_is_rejected(): void
    {
        $this->actingAsGaIt();
        $asset = $this->makeAsset();

        $this->postJson('/hr/assets/' . $asset->id . '/assign', [
            'employee_id'   => 'EMP-FAKE',
            'employee_name' => 'Fake',
            'assigned_date' => '2026-09-01',
        ])->assertUnprocessable()->assertJsonValidationErrors(['employee_id']);

        $this->assertDatabaseCount('asset_assignments', 0);
        $this->assertSame(AssetStatus::AVAILABLE->value, $asset->fresh()->status->value);
    }

    #[Test]
    public function return_clears_active_assignment_and_restores_available(): void
    {
        $this->actingAsGaIt();
        $asset = $this->makeAsset();

        $this->postJson('/hr/assets/' . $asset->id . '/assign', [
            'employee_id'   => '2021072301',
            'employee_name' => 'Siti Rahayu',
            'assigned_date' => '2026-08-01',
            'notes'         => 'Pemakaian sementara.',
        ])->assertOk();

        $this->assertSame(1, $asset->fresh()->activeAssignment()->count());

        $this->postJson('/hr/assets/' . $asset->id . '/return', [
            'return_date'  => '2026-09-02',
            'return_notes' => 'Dikembalikan baik.',
        ])->assertOk()->assertJsonPath('success', true);

        $this->assertSame(0, $asset->fresh()->activeAssignment()->count());
        $this->assertSame(AssetStatus::AVAILABLE->value, $asset->fresh()->status->value);

        // No lingering active assignment anywhere in this flow.
        $this->assertDatabaseMissing('asset_assignments', ['status' => 'active']);
    }
}
