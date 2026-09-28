<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeDocumentFile extends Model
{
    public const SOURCE_EXPORT = 'export';
    public const SOURCE_REGENERATED = 'regenerated';

    protected $table = 'employee_document_files';

    protected $fillable = [
        'document_id',
        'employee_id',
        'doc_code',
        'nomor',
        'file_name',
        'mime_type',
        'size_bytes',
        'checksum_sha256',
        'content_base64',
        'source',
        'archived_by',
        'archived_at',
    ];

    protected $hidden = [
        'content_base64',
    ];

    protected function casts(): array
    {
        return [
            'size_bytes' => 'integer',
            'archived_at' => 'datetime',
        ];
    }

    public function content(): string
    {
        return (string) base64_decode((string) $this->content_base64, true);
    }
}
