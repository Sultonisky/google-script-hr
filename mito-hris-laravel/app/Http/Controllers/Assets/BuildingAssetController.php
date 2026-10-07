<?php

namespace App\Http\Controllers\Assets;

use Illuminate\Validation\Rule;

class BuildingAssetController extends CategoryAssetController
{
    protected string $modelClass = \App\Models\BuildingAsset::class;
    protected string $category = 'Building';
    protected string $routeName = 'assets.portal.building';
    protected string $viewName = 'hr.assets.categories.building';
    protected string $codePrefix = 'BLD';
    protected array $listFieldNames = ['property_type'];

    protected array $details = [
        ['name' => 'property_type', 'label' => 'Tipe Properti', 'type' => 'select', 'options' => ['Building', 'Office', 'Warehouse', 'Land', 'Facility', 'Other']],
        ['name' => 'ownership_status', 'label' => 'Status Kepemilikan', 'type' => 'select', 'required' => true, 'options' => ['Owned', 'Leased', 'Rented']],
        ['name' => 'address', 'label' => 'Alamat', 'type' => 'text'],
        ['name' => 'area_sqm', 'label' => 'Luas Bangunan (m²)', 'type' => 'number', 'step' => '0.01'],
        ['name' => 'land_area_sqm', 'label' => 'Luas Tanah (m²)', 'type' => 'number', 'step' => '0.01'],
        ['name' => 'floors', 'label' => 'Jumlah Lantai', 'type' => 'number'],
        ['name' => 'certificate_number', 'label' => 'Nomor Sertifikat', 'type' => 'text'],
        ['name' => 'maintenance_schedule', 'label' => 'Jadwal Pemeliharaan', 'type' => 'text'],
    ];

    protected function detailRules(): array
    {
        return [
            'property_type' => ['nullable', Rule::in(['Building', 'Office', 'Warehouse', 'Land', 'Facility', 'Other'])],
            'ownership_status' => ['required', Rule::in(['Owned', 'Leased', 'Rented'])],
            'address' => ['nullable', 'string', 'max:500'],
            'area_sqm' => ['nullable', 'numeric', 'min:0'],
            'land_area_sqm' => ['nullable', 'numeric', 'min:0'],
            'floors' => ['nullable', 'integer', 'min:1', 'max:500'],
            'certificate_number' => ['nullable', 'string', 'max:120'],
            'maintenance_schedule' => ['nullable', 'string', 'max:255'],
        ];
    }
}
