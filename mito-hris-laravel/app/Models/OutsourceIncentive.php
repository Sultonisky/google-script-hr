<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OutsourceIncentive extends Model
{
    protected $table = 'outsource_incentives';

    protected $fillable = [
        'period',
        'outsource_id',
        'full_name',
        'vendor',
        'umk_amount',
        'incentive_amount',
        'source_file',
        'imported_by',
    ];

    protected function casts(): array
    {
        return [
            'umk_amount' => 'decimal:2',
            'incentive_amount' => 'decimal:2',
        ];
    }
}
