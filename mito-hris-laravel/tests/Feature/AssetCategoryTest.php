<?php

namespace Tests\Feature;

use App\Enums\AssetCondition;
use App\Enums\AssetStatus;
use App\DTOs\EmployeeData;
use App\Models\BuildingAsset;
use App\Models\ElectronicsAsset;
use App\Models\OfficeAsset;
use App\Models\VehicleAsset;
use App\Repositories\Contracts\EmployeeRepositoryInterface;
use App\Services\PermissionResolver;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Session;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AssetCategoryTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsAssetAdmin(): void
    {
        Session::put('asset_auth', [
            'email' => 'asset.admin@mito.id',
            'fullName' => 'Asset Admin',
            'role' => 'Super Admin',
            'auth_domain' => 'assets',
            'portal' => 'assets',
        ]);
    }

    private function actingAsLimitedAssetUser(): void
    {
        Session::put('asset_auth', [
            'email' => 'limited.asset.user@mito.id',
            'fullName' => 'Limited Asset User',
            'role' => 'User',
            'auth_domain' => 'assets',
            'portal' => 'assets',
        ]);
        $this->mock(PermissionResolver::class, function ($mock): void {
            $mock->shouldReceive('allows')
                ->andReturnUsing(fn (array $user, string $permission): bool => in_array($permission, [
                    'assets.access',
                    'assets.building.view',
                    'assets.office.view',
                ], true));
        });
    }

    private function createLegacyAssetTables(): void
    {
        if (!Schema::hasTable('assets')) {
            $migration = require database_path('migrations/2026_09_01_000001_create_assets_table.php');
            $migration->up();
        }
        if (!Schema::hasTable('asset_assignments')) {
            $migration = require database_path('migrations/2026_09_01_000002_create_asset_assignments_table.php');
            $migration->up();
        }
    }

    #[Test]
    public function assets_overview_links_to_each_separate_category_list(): void
    {
        $this->actingAsAssetAdmin();

        $this->get('http://' . config('hris.domains.assets') . '/')
            ->assertOk()
            ->assertSee('Assets Overview')
            ->assertSee('Building')
            ->assertSee('Vehicle')
            ->assertSee('Office')
            ->assertSee('Electronics')
            ->assertSee(route('assets.portal.building.index'), false)
            ->assertSee(route('assets.portal.vehicle.index'), false)
            ->assertSee(route('assets.portal.office.index'), false)
            ->assertSee(route('assets.portal.electronics.index'), false);
    }

    #[Test]
    public function assets_overview_summarizes_all_category_statuses_values_and_recent_assets(): void
    {
        $this->actingAsAssetAdmin();

        $building = BuildingAsset::query()->create([
            'asset_code' => 'BLD-DASH-1',
            'name' => 'Dashboard Building',
            'purchase_price' => 1250000,
            'condition_status' => AssetCondition::GOOD,
            'status' => AssetStatus::ASSIGNED,
        ]);
        $building->assignments()->create([
            'employee_id' => 'EMP-DASH',
            'employee_name' => 'Dashboard Employee',
            'assigned_date' => now()->toDateString(),
            'status' => 'active',
        ]);
        VehicleAsset::query()->create([
            'asset_code' => 'VEH-DASH-1',
            'name' => 'Dashboard Vehicle',
            'purchase_price' => 2500000,
            'condition_status' => AssetCondition::GOOD,
            'status' => AssetStatus::MAINTENANCE,
        ]);
        ElectronicsAsset::query()->create([
            'asset_code' => 'ELC-DASH-1',
            'name' => 'Dashboard Electronics',
            'condition_status' => AssetCondition::GOOD,
            'status' => AssetStatus::DAMAGED,
        ]);

        $this->get('http://' . config('hris.domains.assets') . '/')
            ->assertOk()
            ->assertSee('Kondisi Seluruh Aset')
            ->assertSee('Ringkasan per Kategori')
            ->assertSee('Aset Terakhir Diperbarui')
            ->assertSee('Total Aset')
            ->assertSee('3')
            ->assertSee('Pemeliharaan')
            ->assertSee('Rusak')
            ->assertSee('badge-status accepted', false)
            ->assertSee('badge-status hold', false)
            ->assertSee('badge-status pending', false)
            ->assertSee('badge-status blacklist', false)
            ->assertDontSee('text-bg-', false)
            ->assertSee('Rp 3.750.000')
            ->assertSee('Dashboard Building')
            ->assertSee('Dashboard Vehicle')
            ->assertSee('Dashboard Electronics')
            ->assertSee('Dashboard Employee');
    }

    #[Test]
    public function category_permissions_hide_unavailable_menus_and_limit_overview_data(): void
    {
        $this->actingAsLimitedAssetUser();
        BuildingAsset::query()->create([
            'asset_code' => 'BLD-LIMITED-1',
            'name' => 'Visible Building Asset',
            'condition_status' => AssetCondition::GOOD,
            'status' => AssetStatus::AVAILABLE,
        ]);
        VehicleAsset::query()->create([
            'asset_code' => 'VHL-LIMITED-1',
            'name' => 'Hidden Vehicle Asset',
            'condition_status' => AssetCondition::GOOD,
            'status' => AssetStatus::AVAILABLE,
        ]);
        OfficeAsset::query()->create([
            'asset_code' => 'OFC-LIMITED-1',
            'name' => 'Visible Office Asset',
            'condition_status' => AssetCondition::GOOD,
            'status' => AssetStatus::AVAILABLE,
        ]);
        ElectronicsAsset::query()->create([
            'asset_code' => 'ELK-LIMITED-1',
            'name' => 'Hidden Electronics Asset',
            'condition_status' => AssetCondition::GOOD,
            'status' => AssetStatus::AVAILABLE,
        ]);

        $this->get('http://' . config('hris.domains.assets') . '/')
            ->assertOk()
            ->assertSee('Visible Building Asset')
            ->assertSee('Visible Office Asset')
            ->assertDontSee('Hidden Vehicle Asset')
            ->assertDontSee('Hidden Electronics Asset')
            ->assertSee(route('assets.portal.building.index'), false)
            ->assertSee(route('assets.portal.office.index'), false)
            ->assertDontSee('Vehicle', false)
            ->assertDontSee('Electronics', false);

        $this->get('http://' . config('hris.domains.assets') . '/building')
            ->assertOk()
            ->assertSee('Visible Building Asset')
            ->assertDontSee('Hidden Vehicle Asset')
            ->assertDontSee(route('assets.portal.vehicle.index'), false);

        $this->get('http://' . config('hris.domains.assets') . '/vehicle')
            ->assertForbidden();
    }

    #[Test]
    public function category_pages_activate_only_the_matching_sidebar_link(): void
    {
        $this->actingAsAssetAdmin();

        $response = $this->get('http://' . config('hris.domains.assets') . '/building')->assertOk();
        $html = $response->getContent();

        preg_match('/<a href="' . preg_quote(route('assets.portal.index'), '/') . '"\s+class="nav-item([^"]*)"/', $html, $overviewLink);
        preg_match('/<a href="' . preg_quote(route('assets.portal.building.index'), '/') . '"\s+class="nav-item([^"]*)"/', $html, $buildingLink);

        $this->assertNotEmpty($overviewLink, 'Assets Overview sidebar link should be rendered.');
        $this->assertNotEmpty($buildingLink, 'Building sidebar link should be rendered.');
        $this->assertStringNotContainsString('active', $overviewLink[1]);
        $this->assertStringContainsString('active', $buildingLink[1]);
    }

    #[Test]
    public function each_category_creates_data_in_its_own_table_and_renders_its_own_list(): void
    {
        $this->actingAsAssetAdmin();

        $categories = [
            'building' => [
                'route' => 'assets.portal.building',
                'table' => 'building_assets',
                'generated_code' => 'BLD-00001',
                'name' => 'Building CRUD',
                'field' => ['ownership_status' => 'Owned'],
                'field_label' => 'Status Kepemilikan',
            ],
            'vehicle' => [
                'route' => 'assets.portal.vehicle',
                'table' => 'vehicle_assets',
                'generated_code' => 'VHL-00001',
                'name' => 'Vehicle CRUD',
                'field' => ['license_plate' => 'B 1234 XYZ'],
                'field_label' => 'Nomor Polisi',
            ],
            'office' => [
                'route' => 'assets.portal.office',
                'table' => 'office_assets',
                'generated_code' => 'OFC-00001',
                'name' => 'Office CRUD',
                'field' => ['equipment_type' => 'Furniture'],
                'field_label' => 'Tipe Peralatan',
            ],
            'electronics' => [
                'route' => 'assets.portal.electronics',
                'table' => 'electronics_assets',
                'generated_code' => 'ELK-00001',
                'name' => 'Electronics CRUD',
                'field' => ['device_type' => 'Laptop'],
                'field_label' => 'Tipe Perangkat',
            ],
        ];

        foreach ($categories as $path => $category) {
            $this->get('http://' . config('hris.domains.assets') . '/' . $path . '/create')
                ->assertOk()
                ->assertSee('Identitas Aset')
                ->assertSee('Kondisi &amp; Pengadaan', false)
                ->assertSee('Kosongkan untuk membuat kode aset otomatis saat disimpan.')
                ->assertSee($category['field_label']);

            $this->post('http://' . config('hris.domains.assets') . '/' . $path, array_merge([
                'name' => $category['name'],
                'status' => AssetStatus::AVAILABLE->value,
                'condition_status' => AssetCondition::GOOD->value,
            ], $category['field']))
                ->assertRedirect(route($category['route'] . '.index'));

            $this->assertDatabaseHas($category['table'], [
                'asset_code' => $category['generated_code'],
                'name' => $category['name'],
                ...$category['field'],
            ]);

            $this->get(route($category['route'] . '.index'))
                ->assertOk()
                ->assertSee($category['name'])
                ->assertSee('data-bs-target="#asset-disposal-confirm-modal"', false)
                ->assertSee('Konfirmasi Disposisi Aset')
                ->assertSee('tetap tersimpan dalam riwayat');
        }

        $this->assertFalse(Schema::hasTable('assets'));
    }

    #[Test]
    public function category_asset_form_preserves_enum_values_and_shows_validation_errors(): void
    {
        $this->actingAsAssetAdmin();
        $asset = BuildingAsset::query()->create([
            'asset_code' => 'BLD-FORM-1',
            'name' => 'Form Building',
            'ownership_status' => 'Owned',
            'condition_status' => AssetCondition::GOOD,
            'status' => AssetStatus::AVAILABLE,
        ]);
        $editUrl = route('assets.portal.building.edit', $asset->id);

        $this->get(route('assets.portal.building.index'))
            ->assertOk()
            ->assertSee($editUrl, false)
            ->assertSee('btn-outline-primary', false);

        $this->get($editUrl)
            ->assertOk()
            ->assertSee('value="Available" selected', false)
            ->assertSee('value="Good" selected', false);

        $this->from($editUrl)
            ->put(route('assets.portal.building.update', $asset->id), [
                'asset_code' => 'BLD-FORM-1',
                'name' => 'Edited Building',
                'ownership_status' => 'Invalid',
                'condition_status' => AssetCondition::GOOD->value,
                'status' => AssetStatus::AVAILABLE->value,
            ])
            ->assertRedirect($editUrl)
            ->assertSessionHasErrors('ownership_status');

        $this->get($editUrl)
            ->assertOk()
            ->assertSee('Edited Building')
            ->assertSee('class="form-select is-invalid"', false)
            ->assertSee('field-ownership_status-error', false);

        $this->put(route('assets.portal.building.update', $asset->id), [
            'asset_code' => 'BLD-FORM-1',
            'name' => 'Updated Building',
            'ownership_status' => 'Leased',
            'condition_status' => AssetCondition::FAIR->value,
            'status' => AssetStatus::MAINTENANCE->value,
            'location' => 'Jakarta',
        ])
            ->assertRedirect(route('assets.portal.building.index'))
            ->assertSessionHas('success', 'Building berhasil diperbarui.');

        $this->assertDatabaseHas('building_assets', [
            'id' => $asset->id,
            'name' => 'Updated Building',
            'ownership_status' => 'Leased',
            'condition_status' => AssetCondition::FAIR->value,
            'status' => AssetStatus::MAINTENANCE->value,
            'location' => 'Jakarta',
        ]);
    }

    #[Test]
    public function building_list_shows_key_columns_and_full_details_are_available_on_show_page(): void
    {
        $this->actingAsAssetAdmin();
        $asset = BuildingAsset::query()->create([
            'asset_code' => 'BLD-SHOW-1',
            'name' => 'Building Detail Example',
            'description' => 'Building description for detail page',
            'property_type' => 'Warehouse',
            'ownership_status' => 'Owned',
            'address' => 'Jl. Contoh No. 10, Jakarta',
            'area_sqm' => 1250,
            'land_area_sqm' => 1800,
            'floors' => 3,
            'certificate_number' => 'CERT-987654',
            'maintenance_schedule' => 'Annual inspection',
            'purchase_date' => '2024-06-15',
            'purchase_price' => 450000000,
            'condition_status' => AssetCondition::GOOD,
            'status' => AssetStatus::AVAILABLE,
            'location' => 'Jakarta',
            'notes' => 'Internal notes for building',
        ]);

        $this->get(route('assets.portal.building.index'))
            ->assertOk()
            ->assertSee('Building Detail Example')
            ->assertSee('Warehouse')
            ->assertSee('Lihat detail')
            ->assertDontSee('Nomor Sertifikat')
            ->assertDontSee('Jadwal Pemeliharaan');

        $this->get(route('assets.portal.building.show', $asset->id))
            ->assertOk()
            ->assertSee('Building description for detail page')
            ->assertSee('CERT-987654')
            ->assertSee('Annual inspection')
            ->assertSee('Rp 450.000.000')
            ->assertSee('Jl. Contoh No. 10, Jakarta')
            ->assertSee('Internal notes for building');
    }

    #[Test]
    public function vehicle_list_shows_key_columns_and_full_details_are_available_on_show_page(): void
    {
        $this->actingAsAssetAdmin();
        $asset = VehicleAsset::query()->create([
            'asset_code' => 'VHL-SHOW-1',
            'name' => 'Vehicle Detail Example',
            'description' => 'Vehicle description for detail page',
            'brand' => 'Toyota',
            'model' => 'Innova',
            'vehicle_type' => 'Car',
            'license_plate' => 'B 1234 XYZ',
            'year' => 2022,
            'vin' => 'VIN-EXAMPLE-123',
            'engine_number' => 'ENG-EXAMPLE-456',
            'color' => 'Black',
            'fuel_type' => 'Diesel',
            'transmission' => 'Automatic',
            'stnk_number' => 'STNK-EXAMPLE-789',
            'stnk_expiry' => '2027-06-30',
            'bpkb_number' => 'BPKB-EXAMPLE-987',
            'last_service_date' => '2026-05-10',
            'next_service_date' => '2026-11-10',
            'mileage' => 45000,
            'purchase_date' => '2024-06-15',
            'purchase_price' => 450000000,
            'condition_status' => AssetCondition::GOOD,
            'status' => AssetStatus::AVAILABLE,
            'location' => 'Jakarta',
            'notes' => 'Internal notes for vehicle',
        ]);

        $this->get(route('assets.portal.vehicle.index'))
            ->assertOk()
            ->assertSee('Vehicle Detail Example')
            ->assertSee('Tipe Kendaraan')
            ->assertSee('Nomor Polisi')
            ->assertSee('Car')
            ->assertSee('B 1234 XYZ')
            ->assertSee('Lihat detail')
            ->assertDontSee('Nomor Rangka (VIN)')
            ->assertDontSee('Servis Terakhir');

        $this->get(route('assets.portal.vehicle.show', $asset->id))
            ->assertOk()
            ->assertSee('Vehicle description for detail page')
            ->assertSee('Toyota')
            ->assertSee('Innova')
            ->assertSee('VIN-EXAMPLE-123')
            ->assertSee('ENG-EXAMPLE-456')
            ->assertSee('STNK-EXAMPLE-789')
            ->assertSee('BPKB-EXAMPLE-987')
            ->assertSee('Rp 450.000.000')
            ->assertSee('Internal notes for vehicle');

        $this->get(route('assets.portal.vehicle.edit', $asset->id))
            ->assertOk()
            ->assertSee('value="Toyota"', false)
            ->assertSee('value="Innova"', false)
            ->assertSee('value="B 1234 XYZ"', false);
    }

    #[Test]
    public function office_list_shows_key_columns_and_full_details_are_available_on_show_page(): void
    {
        $this->actingAsAssetAdmin();
        $asset = OfficeAsset::query()->create([
            'asset_code' => 'OFC-SHOW-1',
            'name' => 'Office Detail Example',
            'description' => 'Office description for detail page',
            'equipment_type' => 'Printer',
            'brand' => 'Canon',
            'model' => 'ImageRunner C3226i',
            'serial_number' => 'SN-EXAMPLE-123',
            'supplier' => 'Office Supplier',
            'purchase_warranty' => '3 Years',
            'purchase_date' => '2024-06-15',
            'purchase_price' => 12500000,
            'condition_status' => AssetCondition::GOOD,
            'status' => AssetStatus::AVAILABLE,
            'location' => 'Jakarta',
            'notes' => 'Internal notes for office equipment',
        ]);

        $this->get(route('assets.portal.office.index'))
            ->assertOk()
            ->assertSee('Office Detail Example')
            ->assertSee('Tipe Peralatan')
            ->assertSee('Merek')
            ->assertSee('Model')
            ->assertSee('Printer')
            ->assertSee('Canon')
            ->assertSee('ImageRunner C3226i')
            ->assertSee('Lihat detail')
            ->assertDontSee('Serial Number')
            ->assertDontSee('Garansi Pembelian');

        $this->get(route('assets.portal.office.show', $asset->id))
            ->assertOk()
            ->assertSee('Office description for detail page')
            ->assertSee('SN-EXAMPLE-123')
            ->assertSee('Office Supplier')
            ->assertSee('3 Years')
            ->assertSee('Rp 12.500.000')
            ->assertSee('Internal notes for office equipment');

        $this->get(route('assets.portal.office.edit', $asset->id))
            ->assertOk()
            ->assertSee('value="Printer" selected', false)
            ->assertSee('value="Canon"', false)
            ->assertSee('value="ImageRunner C3226i"', false)
            ->assertSee('value="SN-EXAMPLE-123"', false);
    }

    #[Test]
    public function electronics_list_shows_key_columns_and_full_details_are_available_on_show_page(): void
    {
        $this->actingAsAssetAdmin();
        $asset = ElectronicsAsset::query()->create([
            'asset_code' => 'ELK-SHOW-1',
            'name' => 'Electronics Detail Example',
            'description' => 'Electronics description for detail page',
            'device_type' => 'Laptop',
            'brand' => 'MITO',
            'model' => 'Z8',
            'serial_number' => 'SN-ELK-123',
            'processor' => 'Intel Core i7',
            'ram' => '16GB',
            'storage' => '1TB',
            'storage_type' => 'NVMe',
            'operating_system' => 'Windows 11',
            'mac_address' => '00:11:22:33:44:55',
            'ip_address' => '192.168.1.10',
            'hostname' => 'MITO-LAPTOP-01',
            'warranty_start' => '2025-01-01',
            'warranty_expiry' => '2027-01-01',
            'purchase_date' => '2025-01-01',
            'purchase_price' => 18500000,
            'condition_status' => AssetCondition::GOOD,
            'status' => AssetStatus::AVAILABLE,
            'location' => 'Jakarta',
            'notes' => 'Internal notes for electronics',
        ]);

        $this->get(route('assets.portal.electronics.index'))
            ->assertOk()
            ->assertSee('Electronics Detail Example')
            ->assertSee('Tipe Perangkat')
            ->assertSee('Merek')
            ->assertSee('Model')
            ->assertSee('Laptop')
            ->assertSee('MITO')
            ->assertSee('Z8')
            ->assertSee('Lihat detail')
            ->assertDontSee('Serial Number')
            ->assertDontSee('Operating System');

        $this->get(route('assets.portal.electronics.show', $asset->id))
            ->assertOk()
            ->assertSee('Electronics description for detail page')
            ->assertSee('SN-ELK-123')
            ->assertSee('Intel Core i7')
            ->assertSee('16GB')
            ->assertSee('1TB')
            ->assertSee('Windows 11')
            ->assertSee('MITO-LAPTOP-01')
            ->assertSee('192.168.1.10')
            ->assertSee('Rp 18.500.000')
            ->assertSee('Internal notes for electronics');

        $this->get(route('assets.portal.electronics.edit', $asset->id))
            ->assertOk()
            ->assertSee('value="Laptop" selected', false)
            ->assertSee('value="MITO"', false)
            ->assertSee('value="Z8"', false)
            ->assertSee('value="SN-ELK-123"', false);
    }

    #[Test]
    public function category_list_uses_hris_dot_badges_and_filters_by_condition(): void
    {
        $this->actingAsAssetAdmin();
        BuildingAsset::query()->create([
            'asset_code' => 'BLD-GOOD',
            'name' => 'Good Condition Building',
            'condition_status' => AssetCondition::GOOD,
            'status' => AssetStatus::AVAILABLE,
        ]);
        BuildingAsset::query()->create([
            'asset_code' => 'BLD-FAIR',
            'name' => 'Fair Condition Building',
            'condition_status' => AssetCondition::FAIR,
            'status' => AssetStatus::ASSIGNED,
        ]);
        BuildingAsset::query()->create([
            'name' => 'Building without code',
            'condition_status' => AssetCondition::GOOD,
            'status' => AssetStatus::AVAILABLE,
        ]);

        $this->get(route('assets.portal.building.index'))
            ->assertOk()
            ->assertSee('badge-status accepted', false)
            ->assertSee('badge-status hold', false)
            ->assertSee('Good')
            ->assertSee('Fair')
            ->assertSee('btn-outline-info', false)
            ->assertSee('btn-outline-secondary', false)
            ->assertSee('btn-outline-primary', false)
            ->assertSee('btn-outline-success', false)
            ->assertSee('btn-outline-warning', false)
            ->assertSee('btn-outline-danger', false)
            ->assertSee('aria-label="Cari aset"', false)
            ->assertSee('bi bi-search', false)
            ->assertSee('aria-label="Reset filter"', false)
            ->assertSee('class="btn btn-outline-danger asset-filter-icon"', false)
            ->assertSee('bi bi-arrow-counterclockwise', false)
            ->assertDontSee('>Cari</button>', false)
            ->assertDontSee('>Reset</a>', false);

        $this->get(route('assets.portal.building.index', ['condition_status' => AssetCondition::FAIR->value]))
            ->assertOk()
            ->assertSee('Fair Condition Building')
            ->assertDontSee('Good Condition Building')
            ->assertSee('name="condition_status"', false)
            ->assertSee('value="Fair" selected', false);
    }

    #[Test]
    public function category_list_shows_result_range_and_hris_pagination_preserves_filters(): void
    {
        $this->actingAsAssetAdmin();
        for ($index = 1; $index <= 17; $index++) {
            BuildingAsset::query()->create([
                'asset_code' => sprintf('BLD-PAGE-%02d', $index),
                'name' => sprintf('Paged Building %02d', $index),
                'condition_status' => AssetCondition::GOOD,
                'status' => AssetStatus::AVAILABLE,
            ]);
        }

        $nextPageUrl = e(route('assets.portal.building.index', [
            'search' => 'Paged',
            'status' => AssetStatus::AVAILABLE->value,
            'condition_status' => AssetCondition::GOOD->value,
            'page' => 2,
            'per_page' => 15,
        ]));

        $this->get(route('assets.portal.building.index', [
            'status' => AssetStatus::AVAILABLE->value,
            'condition_status' => AssetCondition::GOOD->value,
            'search' => 'Paged',
        ]))->assertOk()
            ->assertSee('Menampilkan 1–15 dari 17 aset')
            ->assertSee('pagination-ui', false)
            ->assertSee('data-page-url="' . $nextPageUrl . '"', false);

        $this->get(route('assets.portal.building.index', [
            'status' => AssetStatus::AVAILABLE->value,
            'condition_status' => AssetCondition::GOOD->value,
            'search' => 'Paged',
            'page' => 2,
        ]))->assertOk()
            ->assertSee('Menampilkan 16–17 dari 17 aset')
            ->assertSee('Paged Building 02')
            ->assertSee('Paged Building 01')
            ->assertDontSee('Paged Building 17');
    }

    #[Test]
    public function legacy_asset_management_routes_have_been_retired(): void
    {
        foreach ([
            'hr.assets.index',
            'hr.assets.store',
            'assets.portal.store',
            'assets.portal.assign',
            'assets.portal.return',
            'assets.portal.building.assign.form',
            'assets.portal.building.history',
        ] as $routeName) {
            $this->assertFalse(Route::has($routeName), "Legacy route {$routeName} should not be registered.");
        }
    }

    #[Test]
    public function category_asset_can_be_assigned_returned_and_view_its_history(): void
    {
        $this->actingAsAssetAdmin();
        $this->mock(EmployeeRepositoryInterface::class, function ($mock): void {
            $mock->shouldReceive('findById')
                ->once()
                ->with('EMP-100')
                ->andReturn(new EmployeeData(employeeId: 'EMP-100', fullName: 'Test Employee'));
        });

        $asset = BuildingAsset::query()->create([
            'asset_code' => 'BLD-00001',
            'name' => 'Test Building',
            'condition_status' => AssetCondition::GOOD,
            'status' => AssetStatus::AVAILABLE,
        ]);

        $this->get(route('assets.portal.building.index'))
            ->assertOk()
            ->assertSee('Tugaskan Aset')
            ->assertSee('data-bs-target="#asset-assign-' . $asset->id . '"', false)
            ->assertSee('id="asset-assign-' . $asset->id . '"', false)
            ->assertSee('id="asset-history-' . $asset->id . '"', false);

        $this->post(route('assets.portal.building.assign', $asset->id), [
            'employee_id' => 'EMP-100',
            'assigned_date' => '2026-10-01',
            'assignment_notes' => 'Initial assignment',
        ])->assertRedirect(route('assets.portal.building.index'));

        $this->assertDatabaseHas('building_assets', [
            'id' => $asset->id,
            'status' => AssetStatus::ASSIGNED->value,
        ]);
        $this->assertDatabaseHas('category_asset_assignments', [
            'asset_type' => BuildingAsset::class,
            'asset_id' => $asset->id,
            'employee_id' => 'EMP-100',
            'status' => 'active',
        ]);

        $this->post(route('assets.portal.building.return', $asset->id), [
            'return_date' => '2026-10-05',
            'return_notes' => 'Returned in good condition',
        ])->assertRedirect(route('assets.portal.building.index'));

        $this->assertDatabaseHas('building_assets', [
            'id' => $asset->id,
            'status' => AssetStatus::AVAILABLE->value,
        ]);
        $this->assertDatabaseHas('category_asset_assignments', [
            'asset_id' => $asset->id,
            'status' => 'returned',
        ]);
        $assignment = \App\Models\CategoryAssetAssignment::query()->where('asset_id', $asset->id)->firstOrFail();
        $this->assertSame('2026-10-05', $assignment->return_date->format('Y-m-d'));

        $this->get(route('assets.portal.building.index'))
            ->assertOk()
            ->assertSee('Test Employee')
            ->assertSee('Returned in good condition')
            ->assertSee('Dikembalikan');
    }

    #[Test]
    public function assets_in_every_category_can_be_assigned_to_an_employee(): void
    {
        $this->actingAsAssetAdmin();
        $this->mock(EmployeeRepositoryInterface::class, function ($mock): void {
            $mock->shouldReceive('findById')
                ->times(4)
                ->with('EMP-ASSIGN-ALL')
                ->andReturn(new EmployeeData(employeeId: 'EMP-ASSIGN-ALL', fullName: 'Assigned Employee'));
        });

        $categories = [
            [
                'route' => 'assets.portal.building',
                'model' => BuildingAsset::class,
                'table' => 'building_assets',
                'name' => 'Assign Building',
            ],
            [
                'route' => 'assets.portal.vehicle',
                'model' => VehicleAsset::class,
                'table' => 'vehicle_assets',
                'name' => 'Assign Vehicle',
            ],
            [
                'route' => 'assets.portal.office',
                'model' => OfficeAsset::class,
                'table' => 'office_assets',
                'name' => 'Assign Office Equipment',
            ],
            [
                'route' => 'assets.portal.electronics',
                'model' => ElectronicsAsset::class,
                'table' => 'electronics_assets',
                'name' => 'Assign Electronics',
            ],
        ];

        foreach ($categories as $category) {
            $asset = $category['model']::query()->create([
                'asset_code' => strtoupper(substr($category['route'], -3)) . '-ASSIGN',
                'name' => $category['name'],
                'condition_status' => AssetCondition::GOOD,
                'status' => AssetStatus::AVAILABLE,
            ]);

            $this->get(route($category['route'] . '.index'))
                ->assertOk()
                ->assertSee('data-bs-target="#asset-assign-' . $asset->id . '"', false)
                ->assertSee('id="asset-assign-' . $asset->id . '"', false)
                ->assertSee('id="employee-search-' . $asset->id . '"', false)
                ->assertSee('type="search"', false)
                ->assertSee('Ketik nama atau ID karyawan...', false)
                ->assertSee('data-employee-results', false)
                ->assertSee("setAttribute('role', 'option')", false)
                ->assertSee('name="employee_id"', false);

            $this->post(route($category['route'] . '.assign', $asset->id), [
                'employee_id' => 'EMP-ASSIGN-ALL',
                'assigned_date' => '2026-10-06',
                'assignment_notes' => 'Category assignment test',
            ])->assertRedirect(route($category['route'] . '.index'));

            $this->assertDatabaseHas($category['table'], [
                'id' => $asset->id,
                'status' => AssetStatus::ASSIGNED->value,
            ]);
            $this->assertDatabaseHas('category_asset_assignments', [
                'asset_type' => $category['model'],
                'asset_id' => $asset->id,
                'employee_id' => 'EMP-ASSIGN-ALL',
                'employee_name' => 'Assigned Employee',
                'status' => 'active',
            ]);
        }
    }

    #[Test]
    public function failed_employee_assignment_reopens_the_assign_modal_with_input(): void
    {
        $this->actingAsAssetAdmin();
        $this->mock(EmployeeRepositoryInterface::class, function ($mock): void {
            $mock->shouldReceive('findById')
                ->once()
                ->with('EMP-MISSING')
                ->andReturn(null);
        });
        $asset = VehicleAsset::query()->create([
            'asset_code' => 'VHL-ASSIGN-ERROR',
            'name' => 'Vehicle Assignment Error',
            'condition_status' => AssetCondition::GOOD,
            'status' => AssetStatus::AVAILABLE,
        ]);
        $indexUrl = route('assets.portal.vehicle.index');

        $this->from($indexUrl)
            ->post(route('assets.portal.vehicle.assign', $asset->id), [
                'employee_id' => 'EMP-MISSING',
                'assigned_date' => '2026-10-06',
            ])
            ->assertRedirect($indexUrl)
            ->assertSessionHasErrors('employee_id')
            ->assertSessionHas('open_asset_assign_modal', $asset->id);

        $this->get($indexUrl)
            ->assertOk()
            ->assertSee('id="asset-assign-' . $asset->id . '"', false)
            ->assertSee('value="EMP-MISSING"', false)
            ->assertSee('document.addEventListener(\'DOMContentLoaded\'', false)
            ->assertSee('data-initial-employee-id="EMP-MISSING"', false)
            ->assertSee('Karyawan sebelumnya tidak ditemukan.', false);
    }

    #[Test]
    public function asset_assignment_employee_lookup_finds_employees_by_name(): void
    {
        $this->actingAsAssetAdmin();
        $this->mock(EmployeeRepositoryInterface::class, function ($mock): void {
            $mock->shouldReceive('getAll')
                ->twice()
                ->andReturn(collect([
                    new EmployeeData(
                        employeeId: 'EMP-NAME-01',
                        fullName: 'Nadia Putri',
                        division: 'Operations',
                        department: 'Facilities',
                        jobPosition: 'Office Coordinator',
                    ),
                    new EmployeeData(
                        employeeId: 'EMP-OTHER-01',
                        fullName: 'Edy Loa',
                        division: 'Operations',
                        department: 'Nutritional Services',
                        jobPosition: 'Coordinator',
                    ),
                    new EmployeeData(
                        employeeId: 'EMP-NU-01',
                        fullName: 'Nunu Sari',
                        division: 'Operations',
                        department: 'Facilities',
                    ),
                ]));
        });

        $this->getJson(route('assets.portal.employees.lookup', ['q' => 'Nadia', 'limit' => 8, 'scope' => 'identity']))
            ->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonPath('data.0.employeeId', 'EMP-NAME-01')
            ->assertJsonPath('data.0.fullName', 'Nadia Putri')
            ->assertJsonPath('data.0.department', 'Facilities')
            ->assertJsonPath('data.0.jobPosition', 'Office Coordinator');

        $this->getJson(route('assets.portal.employees.lookup', ['q' => 'nu', 'limit' => 8, 'scope' => 'identity']))
            ->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonPath('data.0.employeeId', 'EMP-NU-01')
            ->assertJsonPath('data.0.fullName', 'Nunu Sari')
            ->assertJsonMissing(['employeeId' => 'EMP-OTHER-01']);
    }

    #[Test]
    public function category_asset_codes_are_generated_and_disposal_preserves_records(): void
    {
        $this->actingAsAssetAdmin();
        $asset = BuildingAsset::query()->create([
            'name' => 'Uncoded Building',
            'condition_status' => AssetCondition::GOOD,
            'status' => AssetStatus::AVAILABLE,
        ]);

        $this->from(route('assets.portal.building.index'))
            ->post(route('assets.portal.building.generate-bulk-codes'))
            ->assertRedirect(route('assets.portal.building.index'));
        $this->assertDatabaseHas('building_assets', [
            'id' => $asset->id,
            'asset_code' => 'BLD-00001',
        ]);

        $this->from(route('assets.portal.building.index'))
            ->delete(route('assets.portal.building.destroy', $asset->id))
            ->assertRedirect(route('assets.portal.building.index'));
        $this->assertDatabaseHas('building_assets', [
            'id' => $asset->id,
            'status' => AssetStatus::DISPOSED->value,
        ]);

        $assignedAsset = BuildingAsset::query()->create([
            'name' => 'Assigned Building',
            'condition_status' => AssetCondition::GOOD,
            'status' => AssetStatus::ASSIGNED,
        ]);
        $assignedAsset->assignments()->create([
            'employee_id' => 'EMP-300',
            'employee_name' => 'Assigned Employee',
            'assigned_date' => '2026-10-01',
            'status' => 'active',
        ]);

        $this->from(route('assets.portal.building.index'))
            ->delete(route('assets.portal.building.destroy', $assignedAsset->id))
            ->assertSessionHas('error');
        $this->assertDatabaseHas('building_assets', [
            'id' => $assignedAsset->id,
            'status' => AssetStatus::ASSIGNED->value,
        ]);
    }

    #[Test]
    public function legacy_assets_and_assignment_history_are_imported_idempotently(): void
    {
        $this->createLegacyAssetTables();
        $now = now()->toDateTimeString();
        $legacyIds = [];
        foreach ([
            ['Building', 'BLD-OLD-1', 'Old Building', ['address' => 'Jakarta', 'area_sqm' => 50]],
            ['Vehicle', 'VEH-OLD-1', 'Old Vehicle', ['vehicle_type' => 'Car', 'license_plate' => 'B 1234 XYZ', 'year' => 2020]],
            ['Office', 'OFF-OLD-1', 'Old Office', ['equipment_type' => 'Furniture']],
            ['Elektronik', 'ELC-OLD-1', 'Old Electronics', ['equipment_type' => 'Laptop', 'brand' => 'MITO']],
        ] as [$category, $code, $name, $details]) {
            $legacyIds[$category] = DB::table('assets')->insertGetId(array_merge([
                'asset_code' => $code,
                'category' => $category,
                'name' => $name,
                'condition_status' => AssetCondition::GOOD->value,
                'status' => $category === 'Elektronik' ? AssetStatus::ASSIGNED->value : AssetStatus::AVAILABLE->value,
                'created_at' => $now,
                'updated_at' => $now,
            ], $details));
        }

        DB::table('asset_assignments')->insert([
            'asset_id' => $legacyIds['Elektronik'],
            'employee_id' => 'EMP-200',
            'employee_name' => 'Legacy Employee',
            'assigned_date' => '2026-09-01',
            'assigned_by' => 'admin@mito.id',
            'assignment_notes' => 'Imported assignment',
            'status' => 'active',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $migration = require database_path('migrations/2026_10_06_000006_import_legacy_assets_to_category_tables.php');
        $migration->up();
        $migration->up();

        $this->assertDatabaseHas('building_assets', [
            'legacy_asset_id' => $legacyIds['Building'],
            'address' => 'Jakarta',
            'area_sqm' => 50,
        ]);
        $this->assertDatabaseHas('vehicle_assets', [
            'legacy_asset_id' => $legacyIds['Vehicle'],
            'license_plate' => 'B 1234 XYZ',
            'year' => 2020,
        ]);
        $this->assertDatabaseHas('office_assets', [
            'legacy_asset_id' => $legacyIds['Office'],
            'equipment_type' => 'Furniture',
        ]);
        $electronicsAsset = DB::table('electronics_assets')->where('legacy_asset_id', $legacyIds['Elektronik'])->first();
        $this->assertNotNull($electronicsAsset);
        $this->assertSame('Laptop', $electronicsAsset->device_type);
        $this->assertDatabaseHas('category_asset_assignments', [
            'legacy_assignment_id' => 1,
            'asset_type' => \App\Models\ElectronicsAsset::class,
            'asset_id' => $electronicsAsset->id,
            'employee_id' => 'EMP-200',
            'status' => 'active',
        ]);
        $this->assertSame(1, DB::table('electronics_assets')->where('legacy_asset_id', $legacyIds['Elektronik'])->count());
        $this->assertSame(1, DB::table('category_asset_assignments')->where('legacy_assignment_id', 1)->count());
        $this->assertSame(4, DB::table('assets')->count());

        $newAssignmentId = DB::table('category_asset_assignments')->insertGetId([
            'asset_type' => \App\Models\BuildingAsset::class,
            'asset_id' => DB::table('building_assets')->where('legacy_asset_id', $legacyIds['Building'])->value('id'),
            'employee_id' => 'EMP-NEW',
            'assigned_date' => '2026-10-06',
            'status' => 'active',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $migration->down();
        $this->assertDatabaseHas('building_assets', ['legacy_asset_id' => $legacyIds['Building']]);
        $this->assertDatabaseMissing('vehicle_assets', ['legacy_asset_id' => $legacyIds['Vehicle']]);
        $this->assertDatabaseHas('category_asset_assignments', ['id' => $newAssignmentId, 'employee_id' => 'EMP-NEW']);
        $this->assertDatabaseMissing('category_asset_assignments', ['legacy_assignment_id' => 1]);
    }

    #[Test]
    public function legacy_asset_tables_are_dropped_after_every_record_is_verified_in_category_tables(): void
    {
        $this->createLegacyAssetTables();
        $now = now()->toDateTimeString();
        $legacyAssetId = DB::table('assets')->insertGetId([
            'asset_code' => 'BLD-LEGACY-1',
            'category' => 'Building',
            'name' => 'Legacy Building',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $legacyAssignmentId = DB::table('asset_assignments')->insertGetId([
            'asset_id' => $legacyAssetId,
            'employee_id' => 'EMP-400',
            'employee_name' => 'Legacy Employee',
            'assigned_date' => '2026-10-01',
            'status' => 'active',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $import = require database_path('migrations/2026_10_06_000006_import_legacy_assets_to_category_tables.php');
        $import->up();
        $drop = require database_path('migrations/2026_10_06_000007_drop_legacy_asset_tables.php');
        $drop->up();

        $this->assertFalse(\Illuminate\Support\Facades\Schema::hasTable('assets'));
        $this->assertFalse(\Illuminate\Support\Facades\Schema::hasTable('asset_assignments'));
        $this->assertDatabaseHas('building_assets', [
            'legacy_asset_id' => $legacyAssetId,
            'name' => 'Legacy Building',
        ]);
        $this->assertDatabaseHas('category_asset_assignments', [
            'legacy_assignment_id' => $legacyAssignmentId,
            'employee_id' => 'EMP-400',
        ]);
    }

    #[Test]
    public function legacy_asset_tables_are_kept_when_any_legacy_record_is_missing_from_category_tables(): void
    {
        $this->createLegacyAssetTables();
        DB::table('assets')->insert([
            'asset_code' => 'VEH-UNIMPORTED',
            'category' => 'Vehicle',
            'name' => 'Unimported Vehicle',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $drop = require database_path('migrations/2026_10_06_000007_drop_legacy_asset_tables.php');

        try {
            $drop->up();
            $this->fail('Expected the legacy cleanup migration to refuse incomplete imports.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('not been imported', $exception->getMessage());
        }

        $this->assertTrue(\Illuminate\Support\Facades\Schema::hasTable('assets'));
        $this->assertTrue(\Illuminate\Support\Facades\Schema::hasTable('asset_assignments'));
        $this->assertDatabaseHas('assets', ['name' => 'Unimported Vehicle']);
    }
}
