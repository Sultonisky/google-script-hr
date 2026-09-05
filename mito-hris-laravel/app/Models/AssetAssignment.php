<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetAssignment extends Model
{
    protected $fillable = [
        'asset_id',
        'employee_id',
        'employee_name',
        'assigned_date',
        'return_date',
        'assigned_by',
        'return_by',
        'assignment_notes',
        'return_notes',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'assigned_date' => 'date',
            'return_date'   => 'date',
        ];
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    /**
     * Check if this is an active (non-returned) assignment.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
