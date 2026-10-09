<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class CategoryAssetAssignment extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'assigned_date' => 'date',
            'return_date' => 'date',
        ];
    }

    public function asset(): MorphTo
    {
        return $this->morphTo();
    }
}
