<?php

namespace App\Models;

use App\Enums\CertStatus;
use App\Enums\CertType;
use Illuminate\Database\Eloquent\Model;

class Certification extends Model
{
    protected $fillable = [
        'cert_code',
        'cert_type',
        'name',
        'product_scope',
        'brand',
        'description',
        'issuing_organization',
        'certificate_number',
        'issue_date',
        'expiry_date',
        'status',
        'employee_id',
        'employee_name',
        'division',
        'department',
        'notes',
        'attachment_path',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'cert_type'   => CertType::class,
            'status'      => CertStatus::class,
            'issue_date'  => 'date',
            'expiry_date' => 'date',
        ];
    }

    // ── Scopes ─────────────────────────────────────────────────────

    public function scopeSearch($query, ?string $search)
    {
        if (!$search) return $query;

        $term = strtolower("%{$search}%");

        return $query->where(function ($q) use ($term) {
            $q->whereRaw('LOWER(cert_code) LIKE ?', [$term])
              ->orWhereRaw('LOWER(name) LIKE ?', [$term])
              ->orWhereRaw('LOWER(employee_id) LIKE ?', [$term])
              ->orWhereRaw('LOWER(employee_name) LIKE ?', [$term])
              ->orWhereRaw('LOWER(issuing_organization) LIKE ?', [$term])
              ->orWhereRaw('LOWER(certificate_number) LIKE ?', [$term])
              ->orWhereRaw('LOWER(product_scope) LIKE ?', [$term])
              ->orWhereRaw('LOWER(brand) LIKE ?', [$term]);
        });
    }

    public function scopeOfType($query, ?string $type)
    {
        if (!$type) return $query;
        return $query->where('cert_type', $type);
    }

    public function scopeOfStatus($query, ?string $status)
    {
        if (!$status) return $query;
        return $query->where('status', $status);
    }

    public function scopeOfEmployee($query, ?string $employeeId)
    {
        if (!$employeeId) return $query;
        return $query->where('employee_id', $employeeId);
    }

    public function scopeExpiringSoon($query, int $days = 30)
    {
        return $query->whereNotNull('expiry_date')
            ->where('expiry_date', '>=', now())
            ->where('expiry_date', '<=', now()->addDays($days));
    }
}
