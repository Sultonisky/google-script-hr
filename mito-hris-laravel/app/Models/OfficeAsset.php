<?php

namespace App\Models;

class OfficeAsset extends CategoryAsset
{
    protected $table = 'office_assets';
    protected array $searchableColumns = ['asset_code', 'name', 'brand', 'model', 'serial_number', 'location'];
}
