<?php

namespace Database\Seeders;

use App\Enums\AssetCategory;
use App\Enums\AssetCondition;
use App\Enums\AssetStatus;
use App\Models\Asset;
use Illuminate\Database\Seeder;

/**
 * Idempotent demo data for Asset Management.
 * Re-running only updates existing rows (keyed by asset_code,
 * or by unique name when a record intentionally has no code).
 */
class AssetDummySeeder extends Seeder
{
    public function run(): void
    {
        $creator = 'DummySeeder';

        $rows = [
            // Building / Property (3)
            [
                'asset_code' => 'BLD-00001', 'category' => AssetCategory::BUILDING->value,
                'name' => 'Kantor Pusat MITO - Gedung A', 'description' => 'Gedung perkantoran utama 4 lantai.',
                'property_type' => 'Office', 'ownership_status' => 'Owned',
                'address' => 'Jl. Jend. Sudirman Kav. 21, Jakarta Selatan',
                'area_sqm' => 1250, 'land_area_sqm' => 800, 'floors' => 4,
                'certificate_number' => 'SHM-0123-JKT', 'maintenance_schedule' => 'Inspeksi struktur & listrik tiap tahun',
                'location' => 'Kantor Pusat', 'purchase_date' => '2016-03-14', 'purchase_price' => 25000000000,
                'status' => AssetStatus::AVAILABLE->value, 'condition_status' => AssetCondition::GOOD->value,
            ],
            [
                'asset_code' => 'BLD-00002', 'category' => AssetCategory::BUILDING->value,
                'name' => 'Gudang Logistik Cakung', 'description' => 'Gudang penyimpanan barang operasional.',
                'property_type' => 'Warehouse', 'ownership_status' => 'Owned',
                'address' => 'Kawasan Industri Cakung, Jakarta Timur',
                'area_sqm' => 3200, 'land_area_sqm' => 4000, 'floors' => 1,
                'certificate_number' => 'SHM-0456-JKT', 'maintenance_schedule' => 'Cek atap & drainase tiap 6 bulan',
                'location' => 'Gudang Cakung', 'purchase_date' => '2019-08-01', 'purchase_price' => 18500000000,
                'status' => AssetStatus::AVAILABLE->value, 'condition_status' => AssetCondition::FAIR->value,
            ],
            [
                'asset_code' => 'BLD-00003', 'category' => AssetCategory::BUILDING->value,
                'name' => 'Ruko Training Center BSD', 'description' => 'Ruko 2 lantai untuk pelatihan & ruang kelas.',
                'property_type' => 'Office', 'ownership_status' => 'Leased',
                'address' => 'BSD Green Office Park, Tangerang Selatan',
                'area_sqm' => 480, 'land_area_sqm' => 200, 'floors' => 2,
                'certificate_number' => null, 'maintenance_schedule' => 'Servis AC tiap 6 bulan',
                'location' => 'BSD', 'purchase_date' => null, 'purchase_price' => null,
                'status' => AssetStatus::AVAILABLE->value, 'condition_status' => AssetCondition::GOOD->value,
            ],

            // Vehicle (3)
            [
                'asset_code' => 'VHL-00001', 'category' => AssetCategory::VEHICLE->value,
                'name' => 'Toyota Innova Zenix', 'description' => 'Mobil operasional manajemen.',
                'brand' => 'Toyota', 'model' => 'Innova Zenix Q', 'vehicle_type' => 'Car',
                'license_plate' => 'B 1234 XYZ', 'year' => 2023, 'color' => 'Putih',
                'fuel_type' => 'Petrol', 'transmission' => 'Automatic',
                'vin' => 'MHFMA3BA3P0123456', 'engine_number' => '2NRV123456',
                'stnk_number' => 'STNK-B1234XYZ', 'stnk_expiry' => '2027-02-10',
                'bpkb_number' => 'BPKB-B1234XYZ', 'mileage' => 24800,
                'last_service_date' => '2026-06-15', 'next_service_date' => '2026-12-15',
                'location' => 'Kantor Pusat', 'purchase_date' => '2023-02-20', 'purchase_price' => 615000000,
                'status' => AssetStatus::AVAILABLE->value, 'condition_status' => AssetCondition::GOOD->value,
            ],
            [
                'asset_code' => 'VHL-00002', 'category' => AssetCategory::VEHICLE->value,
                'name' => 'Honda PCX 160', 'description' => 'Motor untuk kurir & keperluan harian.',
                'brand' => 'Honda', 'model' => 'PCX 160', 'vehicle_type' => 'Motorcycle',
                'license_plate' => 'B 5678 KLM', 'year' => 2024, 'color' => 'Hitam',
                'fuel_type' => 'Petrol', 'transmission' => 'CVT',
                'vin' => 'MH1KF4514RK123456', 'engine_number' => 'KF45E1234567',
                'stnk_number' => 'STNK-B5678KLM', 'stnk_expiry' => '2028-05-20',
                'bpkb_number' => 'BPKB-B5678KLM', 'mileage' => 5400,
                'last_service_date' => '2026-07-01', 'next_service_date' => '2027-01-01',
                'location' => 'Kantor Pusat', 'purchase_date' => '2024-05-25', 'purchase_price' => 33000000,
                'status' => AssetStatus::MAINTENANCE->value, 'condition_status' => AssetCondition::GOOD->value,
            ],
            [
                'asset_code' => 'VHL-00003', 'category' => AssetCategory::VEHICLE->value,
                'name' => 'Isuzu Elf Giga', 'description' => 'Truk pengangkut logistik antar cabang.',
                'brand' => 'Isuzu', 'model' => 'Elf Giga NLR', 'vehicle_type' => 'Truck',
                'license_plate' => 'B 9012 NOP', 'year' => 2020, 'color' => 'Biru',
                'fuel_type' => 'Diesel', 'transmission' => 'Manual',
                'vin' => 'MHFSUA1K1K0123456', 'engine_number' => '4HK1T123456',
                'stnk_number' => 'STNK-B9012NOP', 'stnk_expiry' => '2026-11-30',
                'bpkb_number' => 'BPKB-B9012NOP', 'mileage' => 124500,
                'last_service_date' => '2026-05-10', 'next_service_date' => '2026-11-10',
                'location' => 'Gudang Cakung', 'purchase_date' => '2020-11-11', 'purchase_price' => 780000000,
                'status' => AssetStatus::AVAILABLE->value, 'condition_status' => AssetCondition::GOOD->value,
            ],


            // Office Equipment (4)
            [
                'asset_code' => 'OFC-00001', 'category' => AssetCategory::OFFICE->value,
                'name' => 'Printer Epson L3210', 'description' => 'Printer multifungsi untuk admin keuangan.',
                'brand' => 'Epson', 'model' => 'L3210', 'equipment_type' => 'Printer',
                'serial_number' => 'X5FY123456', 'supplier' => 'PT Sinar Teknologi',
                'purchase_warranty' => '1 Year', 'warranty_start' => '2025-11-01', 'warranty_expiry' => '2026-11-01',
                'maintenance_schedule' => 'Servis head printer tiap 3 bulan',
                'location' => 'Kantor Pusat - Lantai 2', 'purchase_date' => '2025-11-10', 'purchase_price' => 3750000,
                'status' => AssetStatus::AVAILABLE->value, 'condition_status' => AssetCondition::GOOD->value,
            ],
            [
                'asset_code' => 'OFC-00002', 'category' => AssetCategory::OFFICE->value,
                'name' => 'AC Daikin 2 PK', 'description' => 'Unit AC untuk ruang rapat utama.',
                'brand' => 'Daikin', 'model' => 'FTKC50TVM4', 'equipment_type' => 'Air Conditioner',
                'serial_number' => 'ACDK-88231', 'supplier' => 'PT Sejuk Abadi',
                'purchase_warranty' => '2 Year', 'warranty_start' => '2024-01-20', 'warranty_expiry' => '2026-01-20',
                'maintenance_schedule' => 'Cuci AC tiap 3 bulan',
                'location' => 'Kantor Pusat - Lantai 1', 'purchase_date' => '2024-01-25', 'purchase_price' => 9500000,
                'status' => AssetStatus::MAINTENANCE->value, 'condition_status' => AssetCondition::FAIR->value,
            ],
            [
                'asset_code' => 'OFC-00003', 'category' => AssetCategory::OFFICE->value,
                'name' => 'Proyektor Epson EB-X06', 'description' => 'Proyektor untuk ruang training.',
                'brand' => 'Epson', 'model' => 'EB-X06', 'equipment_type' => 'Projector',
                'serial_number' => 'EPX-7745201', 'supplier' => 'PT Sinar Teknologi',
                'purchase_warranty' => '1 Year', 'warranty_start' => '2025-03-15', 'warranty_expiry' => '2026-03-15',
                'location' => 'Ruang Training', 'purchase_date' => '2025-03-20', 'purchase_price' => 7250000,
                'status' => AssetStatus::AVAILABLE->value, 'condition_status' => AssetCondition::GOOD->value,
            ],
            [
                'asset_code' => 'OFC-00004', 'category' => AssetCategory::OFFICE->value,
                'name' => 'Kursi Kantor Ergonomis', 'description' => 'Kursi kerja staff, tipe mesh.',
                'brand' => 'Informa', 'model' => 'Ergo Mesh', 'equipment_type' => 'Furniture',
                'serial_number' => null, 'supplier' => 'PT Kawan Lama',
                'location' => 'Kantor Pusat - Lantai 3', 'purchase_date' => '2023-06-05', 'purchase_price' => 1850000,
                'status' => AssetStatus::AVAILABLE->value, 'condition_status' => AssetCondition::GOOD->value,
            ],


            // Elektronik / IT (6)
            [
                'asset_code' => 'ELK-00001', 'category' => AssetCategory::ELEKTRONIK->value,
                'name' => 'Laptop Dell Latitude 5440', 'description' => 'Laptop kerja standard engineer.',
                'brand' => 'Dell', 'model' => 'Latitude 5440', 'device_type' => 'Laptop',
                'serial_number' => 'DL5440-88231', 'processor' => 'Intel Core i5-1345U',
                'ram' => '16 GB', 'storage' => '512 GB', 'storage_type' => 'SSD',
                'operating_system' => 'Windows 11 Pro', 'hostname' => 'MITO-LT-001',
                'mac_address' => 'F0:2F:74:1A:2B:3C', 'ip_address' => '192.168.10.21',
                'warranty_start' => '2025-09-01', 'warranty_expiry' => '2027-09-01',
                'location' => 'Kantor Pusat - Lantai 3', 'purchase_date' => '2025-09-10', 'purchase_price' => 18900000,
                'status' => AssetStatus::AVAILABLE->value, 'condition_status' => AssetCondition::GOOD->value,
            ],
            [
                'asset_code' => 'ELK-00002', 'category' => AssetCategory::ELEKTRONIK->value,
                'name' => 'MacBook Air M3', 'description' => 'Laptop untuk tim desain.',
                'brand' => 'Apple', 'model' => 'MacBook Air M3', 'device_type' => 'Laptop',
                'serial_number' => 'MAC-M3-77812', 'processor' => 'Apple M3', 'ram' => '16 GB',
                'storage' => '512 GB', 'storage_type' => 'SSD', 'operating_system' => 'macOS Sequoia',
                'hostname' => 'MITO-MAC-002', 'mac_address' => 'A4:83:E7:9F:1D:42',
                'warranty_start' => '2026-02-01', 'warranty_expiry' => '2028-02-01',
                'location' => 'Kantor Pusat - Lantai 2', 'purchase_date' => '2026-02-15', 'purchase_price' => 24500000,
                'status' => AssetStatus::MAINTENANCE->value, 'condition_status' => AssetCondition::GOOD->value,
            ],
            [
                'asset_code' => 'ELK-00003', 'category' => AssetCategory::ELEKTRONIK->value,
                'name' => 'Monitor LG 27" 4K', 'description' => 'Monitor external untuk designer.',
                'brand' => 'LG', 'model' => '27UP850', 'device_type' => 'Monitor',
                'serial_number' => 'LG27-55A1209', 'processor' => null, 'ram' => null,
                'storage' => null, 'storage_type' => null, 'operating_system' => null,
                'hostname' => null, 'mac_address' => null, 'ip_address' => null,
                'warranty_start' => '2025-08-01', 'warranty_expiry' => '2027-08-01',
                'location' => 'Kantor Pusat - Lantai 2', 'purchase_date' => '2025-08-20', 'purchase_price' => 4850000,
                'status' => AssetStatus::DAMAGED->value, 'condition_status' => AssetCondition::DAMAGED->value,
            ],
            [
                'asset_code' => 'ELK-00004', 'category' => AssetCategory::ELEKTRONIK->value,
                'name' => 'Server Dell PowerEdge R650', 'description' => 'Server internal untuk aplikasi HRIS.',
                'brand' => 'Dell', 'model' => 'PowerEdge R650', 'device_type' => 'Server',
                'serial_number' => 'PER650-003421', 'processor' => 'Intel Xeon Gold 6338 x2',
                'ram' => '128 GB', 'storage' => '4 TB', 'storage_type' => 'NVMe',
                'operating_system' => 'Ubuntu Server 22.04', 'hostname' => 'MITO-SRV-01',
                'mac_address' => '18:66:DA:11:44:77', 'ip_address' => '10.0.0.5',
                'warranty_start' => '2024-12-01', 'warranty_expiry' => '2027-12-01',
                'location' => 'Data Center', 'purchase_date' => '2024-12-20', 'purchase_price' => 185000000,
                'status' => AssetStatus::AVAILABLE->value, 'condition_status' => AssetCondition::GOOD->value,
            ],
            [
                'asset_code' => null, 'category' => AssetCategory::ELEKTRONIK->value,
                'name' => 'iPhone 15 (HRD)', 'description' => 'Unit khusus HRD, kode belum dibuat.',
                'brand' => 'Apple', 'model' => 'iPhone 15', 'device_type' => 'Smartphone',
                'serial_number' => 'IP15-88901', 'processor' => 'A16 Bionic', 'ram' => '6 GB',
                'storage' => '128 GB', 'storage_type' => 'SSD', 'operating_system' => 'iOS 18',
                'location' => 'Kantor Pusat - Lantai 2', 'purchase_date' => '2025-12-10', 'purchase_price' => 15500000,
                'status' => AssetStatus::AVAILABLE->value, 'condition_status' => AssetCondition::GOOD->value,
            ],
            [
                'asset_code' => null, 'category' => AssetCategory::ELEKTRONIK->value,
                'name' => 'Router Cisco Meraki MX', 'description' => 'Perangkat jaringan kantor, kode belum dibuat.',
                'brand' => 'Cisco', 'model' => 'Meraki MX68', 'device_type' => 'Network Device',
                'serial_number' => 'MX68-44512', 'hostname' => 'MITO-RTR-01',
                'mac_address' => 'E4:C7:22:9A:BB:10', 'ip_address' => '10.0.0.1',
                'location' => 'Data Center', 'purchase_date' => '2025-05-05', 'purchase_price' => 28500000,
                'status' => AssetStatus::AVAILABLE->value, 'condition_status' => AssetCondition::GOOD->value,
            ],
        ];

        foreach ($rows as $data) {
            $data['created_by'] = $creator;

            if (!empty($data['asset_code'])) {
                $asset = Asset::where('asset_code', $data['asset_code'])->first();
                if ($asset) {
                    $asset->update($data);
                    continue;
                }
            } else {
                $asset = Asset::where('name', $data['name'])->where('category', $data['category'])->first();
                if ($asset) {
                    $asset->update($data);
                    continue;
                }
            }

            Asset::create($data);
        }
    }
}

