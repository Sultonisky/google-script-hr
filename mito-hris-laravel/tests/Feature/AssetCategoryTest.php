<?php

namespace Tests\Feature;

use App\Enums\AssetCategory;
use App\Enums\AssetStatus;
use App\Models\Asset;
use App\Models\AssetAssignment;
use Database\Seeders\AssetDummySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Session;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AssetCategoryTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsAdmin(): static
    {
        Session::put('hr_user', [
            'email'       => 'asset.admin@mito.id',
            'fullName'    => 'Asset Admin',
            'role'        => 'Admin',
            'permissions' => config('hris.auth.role_permissions')['Admin'] ?? [],
            'auth_domain' => 'users',
            'entities'    => [],
            'branch'      => '',
        ]);

        return $this;
    }

    private function payload(string $category): array
    {
        return [
            'category' => $category,
            'name'     => 'Aset Test ' . $category,
            'status'   => AssetStatus::AVAILABLE->value,
            'asset_code' => null,
        ];
    }

    // ── Category detail persistence via HTTP store ─────────────────

    #[Test]
    public function building_requires_and_saves_ownership_status(): void
    {
        $this->actingAsAdmin();

        $this->postJson('/hr/assets', $this->payload(AssetCategory::BUILDING->value))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['ownership_status']);

        $this->postJson('/hr/assets', array_merge($this->payload(AssetCategory::BUILDING->value), [
            'property_type'    => 'Office',
            'ownership_status' => 'Owned',
            'area_sqm'         => 1200.5,
            'floors'           => 4,
            'certificate_number' => 'SHM-0001',
        ]))->assertCreated();

        $this->assertDatabaseHas('assets', [
            'category'          => AssetCategory::BUILDING->value,
            'ownership_status'  => 'Owned',
            'property_type'     => 'Office',
            'area_sqm'          => 1200.5,
            'floors'            => 4,
            'certificate_number' => 'SHM-0001',
        ]);
    }

    #[Test]
    public function vehicle_detail_fields_persist(): void
    {
        $this->actingAsAdmin();

        $this->postJson('/hr/assets', array_merge($this->payload(AssetCategory::VEHICLE->value), [
            'vehicle_type' => 'Car', 'license_plate' => 'B 1234 XYZ',
            'fuel_type' => 'Petrol', 'transmission' => 'Automatic',
            'year' => 2023, 'mileage' => 12000,
        ]))->assertCreated();

        $this->assertDatabaseHas('assets', [
            'name' => 'Aset Test ' . AssetCategory::VEHICLE->value,
            'vehicle_type' => 'Car', 'license_plate' => 'B 1234 XYZ',
            'fuel_type' => 'Petrol', 'transmission' => 'Automatic',
            'year' => 2023, 'mileage' => 12000,
        ]);
    }

    #[Test]
    public function category_rules_validate_their_own_fields(): void
    {
        $this->actingAsAdmin();

        // license_plate regex lives in the Vehicle rule set
        $this->postJson('/hr/assets', array_merge($this->payload(AssetCategory::VEHICLE->value), [
            'license_plate' => '!!BUKAN-PLAT!!',
        ]))->assertUnprocessable()->assertJsonValidationErrors(['license_plate']);

        // ownership_status is required only for Building assets
        $this->postJson('/hr/assets', $this->payload(AssetCategory::ELEKTRONIK->value))
            ->assertCreated();
    }

    #[Test]
    public function elektronik_detail_fields_persist(): void
    {
        $this->actingAsAdmin();

        $this->postJson('/hr/assets', array_merge($this->payload(AssetCategory::ELEKTRONIK->value), [
            'device_type' => 'Laptop', 'processor' => 'Intel Core i5',
            'ram' => '16 GB', 'storage' => '512 GB', 'storage_type' => 'SSD',
            'operating_system' => 'Windows 11', 'hostname' => 'TEST-LT-01',
            'mac_address' => 'AA:BB:CC:DD:EE:FF', 'ip_address' => '192.168.1.5',
        ]))->assertCreated();

        $this->assertDatabaseHas('assets', [
            'name' => 'Aset Test ' . AssetCategory::ELEKTRONIK->value,
            'device_type' => 'Laptop', 'storage_type' => 'SSD',
            'mac_address' => 'AA:BB:CC:DD:EE:FF', 'ip_address' => '192.168.1.5',
            'hostname' => 'TEST-LT-01',
        ]);
    }

    // ── Scopes / search / filters ──────────────────────────────────

    #[Test]
    public function search_finds_category_detail_fields(): void
    {
        Asset::create([
            'category' => AssetCategory::VEHICLE->value,
            'name' => 'Mobil Dinas',
            'status' => AssetStatus::AVAILABLE->value,
            'license_plate' => 'D 9999 XX',
            'created_by' => 'test@mito.id',
        ]);

        $this->assertSame(1, Asset::search('D 9999')->count());
        $this->assertSame(0, Asset::search('tidak ada')->count());
    }

    #[Test]
    public function location_and_assignment_filters_work(): void
    {
        $kantor = Asset::create([
            'category' => AssetCategory::OFFICE->value,
            'name' => 'Printer Kantor',
            'status' => AssetStatus::AVAILABLE->value,
            'location' => 'Kantor Pusat - Lantai 1',
            'created_by' => 'test@mito.id',
        ]);
        Asset::create([
            'category' => AssetCategory::OFFICE->value,
            'name' => 'Mesin Absen Gudang',
            'status' => AssetStatus::AVAILABLE->value,
            'location' => 'Gudang Cakung',
            'created_by' => 'test@mito.id',
        ]);

        $this->assertSame(1, Asset::ofLocation('lantai 1')->count());
        $this->assertSame('Printer Kantor', Asset::ofLocation('lantai 1')->first()->name);

        $this->assertSame(2, Asset::ofAssignment('unassigned')->count());
        AssetAssignment::create([
            'asset_id' => $kantor->id, 'employee_id' => 'EMP-X',
            'assigned_date' => '2026-01-01', 'assigned_by' => 'test@mito.id', 'status' => 'active',
        ]);
        $kantor->update(['status' => AssetStatus::ASSIGNED]);
        $this->assertSame(1, Asset::ofAssignment('assigned')->count());
        $this->assertSame(1, Asset::ofAssignment('unassigned')->count());
    }

    // ── Dummy seeder idempotency ───────────────────────────────────

    #[Test]
    public function asset_dummy_seeder_is_idempotent_and_produces_16_rows(): void
    {
        $this->seed(AssetDummySeeder::class);
        $this->seed(AssetDummySeeder::class);

        $this->assertDatabaseCount('assets', 16);
        $this->assertSame(3, Asset::where('category', AssetCategory::BUILDING->value)->count());
        $this->assertSame(3, Asset::where('category', AssetCategory::VEHICLE->value)->count());
        $this->assertSame(4, Asset::where('category', AssetCategory::OFFICE->value)->count());
        $this->assertSame(6, Asset::where('category', AssetCategory::ELEKTRONIK->value)->count());
    }

    // ── Index page renders seeded rows ─────────────────────────────

    #[Test]
    public function asset_index_renders_seeded_data(): void
    {
        $this->actingAsAdmin();
        $this->seed(AssetDummySeeder::class);

        $this->get('/hr/assets')
            ->assertOk()
            ->assertSee('Daftar Aset')
            ->assertSee('Router Cisco Meraki MX')
            ->assertSee('Semua Lokasi')
            ->assertSee('Semua Penugasan');
    }
}

