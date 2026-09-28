<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeDocument extends Model
{
    protected $table = 'employee_documents';

    protected $fillable = [
        'document_id',
        'employee_id',
        'sequence',
        'doc_type',
        'doc_code',
        'nomor',
        'entity',
        'issued_at',
        'issued_by',
        'reference',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'sequence' => 'integer',
        ];
    }
}
