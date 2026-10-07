<?php

namespace App\Http\Controllers\Assets;

use Illuminate\Validation\Rule;

class OfficeAssetController extends CategoryAssetController
{
    protected string $modelClass = \App\Models\OfficeAsset::class;
    protected string $category = 'Office';
    protected string $routeName = 'assets.portal.office';
    protected string $viewName = 'hr.assets.categories.office';
    protected string $codePrefix = 'OFC';
    protected array $listFieldNames = ['equipment_type', 'brand', 'model'];

    protected array $details = [
        ['name' => 'equipment_type', 'label' => 'Tipe Peralatan', 'type' => 'select', 'options' => ['Furniture', 'Printer', 'Projector', 'Air Conditioner', 'Refrigerator', 'Telephone', 'Other']],
        ['name' => 'brand', 'label' => 'Merek', 'type' => 'text'],
        ['name' => 'model', 'label' => 'Model', 'type' => 'text'],
        ['name' => 'serial_number', 'label' => 'Serial Number', 'type' => 'text'],
        ['name' => 'supplier', 'label' => 'Supplier', 'type' => 'text'],
        ['name' => 'purchase_warranty', 'label' => 'Garansi Pembelian', 'type' => 'text'],
    ];

    protected function detailRules(): array
    {
        return [
            'equipment_type' => ['nullable', Rule::in(['Furniture', 'Printer', 'Projector', 'Air Conditioner', 'Refrigerator', 'Telephone', 'Other'])],
            'brand' => ['nullable', 'string', 'max:255'],
            'model' => ['nullable', 'string', 'max:255'],
            'serial_number' => ['nullable', 'string', 'max:255'],
            'supplier' => ['nullable', 'string', 'max:255'],
            'purchase_warranty' => ['nullable', 'string', 'max:120'],
        ];
    }
}
