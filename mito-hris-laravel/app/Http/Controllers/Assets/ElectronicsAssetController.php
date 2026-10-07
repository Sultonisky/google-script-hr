<?php

namespace App\Http\Controllers\Assets;

use Illuminate\Validation\Rule;

class ElectronicsAssetController extends CategoryAssetController
{
    protected string $modelClass = \App\Models\ElectronicsAsset::class;
    protected string $category = 'Electronics';
    protected string $routeName = 'assets.portal.electronics';
    protected string $viewName = 'hr.assets.categories.electronics';
    protected string $codePrefix = 'ELK';
    protected array $listFieldNames = ['device_type', 'brand', 'model'];

    protected array $details = [
        ['name' => 'device_type', 'label' => 'Tipe Perangkat', 'type' => 'select', 'options' => ['Laptop', 'Desktop', 'Monitor', 'Printer', 'Server', 'Network Device', 'Smartphone', 'Tablet', 'Other']],
        ['name' => 'brand', 'label' => 'Merek', 'type' => 'text'],
        ['name' => 'model', 'label' => 'Model', 'type' => 'text'],
        ['name' => 'serial_number', 'label' => 'Serial Number', 'type' => 'text'],
        ['name' => 'processor', 'label' => 'Processor', 'type' => 'text'],
        ['name' => 'ram', 'label' => 'RAM', 'type' => 'text'],
        ['name' => 'storage', 'label' => 'Storage', 'type' => 'text'],
        ['name' => 'storage_type', 'label' => 'Tipe Storage', 'type' => 'select', 'options' => ['SSD', 'HDD', 'NVMe']],
        ['name' => 'operating_system', 'label' => 'Operating System', 'type' => 'text'],
        ['name' => 'mac_address', 'label' => 'MAC Address', 'type' => 'text'],
        ['name' => 'ip_address', 'label' => 'IP Address', 'type' => 'text'],
        ['name' => 'hostname', 'label' => 'Hostname', 'type' => 'text'],
        ['name' => 'warranty_start', 'label' => 'Mulai Garansi', 'type' => 'date'],
        ['name' => 'warranty_expiry', 'label' => 'Garansi Berakhir', 'type' => 'date'],
    ];

    protected function detailRules(): array
    {
        return [
            'device_type' => ['nullable', Rule::in(['Laptop', 'Desktop', 'Monitor', 'Printer', 'Server', 'Network Device', 'Smartphone', 'Tablet', 'Other'])],
            'brand' => ['nullable', 'string', 'max:255'],
            'model' => ['nullable', 'string', 'max:255'],
            'serial_number' => ['nullable', 'string', 'max:255'],
            'processor' => ['nullable', 'string', 'max:120'],
            'ram' => ['nullable', 'string', 'max:50'],
            'storage' => ['nullable', 'string', 'max:50'],
            'storage_type' => ['nullable', Rule::in(['SSD', 'HDD', 'NVMe'])],
            'operating_system' => ['nullable', 'string', 'max:120'],
            'mac_address' => ['nullable', 'string', 'max:30'],
            'ip_address' => ['nullable', 'ip'],
            'hostname' => ['nullable', 'string', 'max:120'],
            'warranty_start' => ['nullable', 'date'],
            'warranty_expiry' => ['nullable', 'date', 'after_or_equal:warranty_start'],
        ];
    }
}
