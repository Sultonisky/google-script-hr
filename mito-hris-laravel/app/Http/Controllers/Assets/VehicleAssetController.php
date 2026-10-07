<?php

namespace App\Http\Controllers\Assets;

use Illuminate\Validation\Rule;

class VehicleAssetController extends CategoryAssetController
{
    protected string $modelClass = \App\Models\VehicleAsset::class;
    protected string $category = 'Vehicle';
    protected string $routeName = 'assets.portal.vehicle';
    protected string $viewName = 'hr.assets.categories.vehicle';
    protected string $codePrefix = 'VHL';
    protected array $listFieldNames = ['vehicle_type', 'license_plate'];

    protected array $details = [
        ['name' => 'brand', 'label' => 'Merek', 'type' => 'text'],
        ['name' => 'model', 'label' => 'Model', 'type' => 'text'],
        ['name' => 'vehicle_type', 'label' => 'Tipe Kendaraan', 'type' => 'select', 'options' => ['Car', 'Motorcycle', 'Truck', 'Van', 'Bus', 'Other']],
        ['name' => 'license_plate', 'label' => 'Nomor Polisi', 'type' => 'text'],
        ['name' => 'year', 'label' => 'Tahun', 'type' => 'number'],
        ['name' => 'vin', 'label' => 'Nomor Rangka (VIN)', 'type' => 'text'],
        ['name' => 'engine_number', 'label' => 'Nomor Mesin', 'type' => 'text'],
        ['name' => 'color', 'label' => 'Warna', 'type' => 'text'],
        ['name' => 'fuel_type', 'label' => 'Bahan Bakar', 'type' => 'select', 'options' => ['Petrol', 'Diesel', 'Electric', 'Hybrid']],
        ['name' => 'transmission', 'label' => 'Transmisi', 'type' => 'select', 'options' => ['Manual', 'Automatic', 'CVT']],
        ['name' => 'stnk_number', 'label' => 'Nomor STNK', 'type' => 'text'],
        ['name' => 'stnk_expiry', 'label' => 'Masa Berlaku STNK', 'type' => 'date'],
        ['name' => 'bpkb_number', 'label' => 'Nomor BPKB', 'type' => 'text'],
        ['name' => 'last_service_date', 'label' => 'Servis Terakhir', 'type' => 'date'],
        ['name' => 'next_service_date', 'label' => 'Jadwal Servis Berikutnya', 'type' => 'date'],
        ['name' => 'mileage', 'label' => 'Kilometer', 'type' => 'number'],
    ];

    protected function detailRules(): array
    {
        return [
            'brand' => ['nullable', 'string', 'max:255'],
            'model' => ['nullable', 'string', 'max:255'],
            'vehicle_type' => ['nullable', Rule::in(['Car', 'Motorcycle', 'Truck', 'Van', 'Bus', 'Other'])],
            'license_plate' => ['nullable', 'string', 'max:20'],
            'year' => ['nullable', 'integer', 'min:1900', 'max:2100'],
            'vin' => ['nullable', 'string', 'max:100'],
            'engine_number' => ['nullable', 'string', 'max:100'],
            'color' => ['nullable', 'string', 'max:50'],
            'fuel_type' => ['nullable', Rule::in(['Petrol', 'Diesel', 'Electric', 'Hybrid'])],
            'transmission' => ['nullable', Rule::in(['Manual', 'Automatic', 'CVT'])],
            'stnk_number' => ['nullable', 'string', 'max:50'],
            'stnk_expiry' => ['nullable', 'date'],
            'bpkb_number' => ['nullable', 'string', 'max:50'],
            'last_service_date' => ['nullable', 'date'],
            'next_service_date' => ['nullable', 'date'],
            'mileage' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
