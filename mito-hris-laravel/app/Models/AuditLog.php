<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    protected $table = 'audit_logs';

    protected $fillable = [
        'audit_id',
        'entity_type',
        'entity_id',
        'action',
        'field',
        'old_value',
        'new_value',
        'user',
        'source',
        'logged_at',
    ];

    protected function casts(): array
    {
        return [
            'logged_at' => 'datetime',
        ];
    }
}
