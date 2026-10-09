<?php

namespace App\Models;

class BuildingAsset extends CategoryAsset
{
    protected $table = 'building_assets';

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'area_sqm' => 'decimal:2',
            'land_area_sqm' => 'decimal:2',
        ]);
    }
}
