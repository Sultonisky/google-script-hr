<?php

namespace App\Models;

class ElectronicsAsset extends CategoryAsset
{
    protected $table = 'electronics_assets';
    protected array $searchableColumns = ['asset_code', 'name', 'brand', 'model', 'serial_number', 'hostname', 'location'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'warranty_start' => 'date',
            'warranty_expiry' => 'date',
        ]);
    }
}
