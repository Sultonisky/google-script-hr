<?php

namespace App\Models;

class VehicleAsset extends CategoryAsset
{
    protected $table = 'vehicle_assets';
    protected array $searchableColumns = ['asset_code', 'name', 'brand', 'model', 'license_plate', 'location'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'stnk_expiry' => 'date',
            'last_service_date' => 'date',
            'next_service_date' => 'date',
            'year' => 'integer',
            'mileage' => 'integer',
        ]);
    }
}
