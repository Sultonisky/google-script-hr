<?php

namespace App\Models;

use App\Enums\AssetCategory;
use App\Enums\AssetCondition;
use App\Enums\AssetStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Asset extends Model
{
    protected $fillable = [
        'asset_code',
        'category',
        'name',
        'description',
        'brand',
        'model',
        'serial_number',
        'purchase_date',
        'purchase_price',
        'condition_status',
        'status',
        'location',
        'notes',
        'license_plate',
        'vehicle_type',
        'year',
        'address',
        'area_sqm',
        'equipment_type',
        'property_type',
        'ownership_status',
        'land_area_sqm',
        'floors',
        'certificate_number',
        'maintenance_schedule',
        'vin',
        'engine_number',
        'color',
        'fuel_type',
        'transmission',
        'stnk_number',
        'stnk_expiry',
        'bpkb_number',
        'last_service_date',
        'next_service_date',
        'mileage',
        'purchase_warranty',
        'supplier',
        'device_type',
        'processor',
        'ram',
        'storage',
        'storage_type',
        'operating_system',
        'mac_address',
        'ip_address',
        'hostname',
        'warranty_start',
        'warranty_expiry',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'category'       => AssetCategory::class,
            'status'         => AssetStatus::class,
            'condition_status' => AssetCondition::class,
            'purchase_date'  => 'date',
            'purchase_price' => 'decimal:2',
            'year'           => 'integer',
            'area_sqm'       => 'decimal:2',
            'land_area_sqm'  => 'decimal:2',
            'floors'         => 'integer',
            'mileage'        => 'integer',
            'stnk_expiry'    => 'date',
            'last_service_date' => 'date',
            'next_service_date' => 'date',
            'warranty_start' => 'date',
            'warranty_expiry' => 'date',
        ];
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(AssetAssignment::class);
    }

    public function activeAssignment(): HasOne
    {
        return $this->hasOne(AssetAssignment::class)->where('status', 'active');
    }

    /**
     * Check if this asset currently has an active assignment.
     */
    public function isCurrentlyAssigned(): bool
    {
        return $this->status === AssetStatus::ASSIGNED;
    }

    /**
     * Check if asset can be assigned.
     */
    public function canBeAssigned(): bool
    {
        return $this->status->isAssignable() && !$this->activeAssignment()->exists();
    }

    /**
     * Scope: search by multiple fields.
     */
    public function scopeSearch($query, ?string $search)
    {
        if (!$search) {
            return $query;
        }

        $term = strtolower("%{$search}%");

        return $query->where(function ($q) use ($term) {
            $q->whereRaw('LOWER(asset_code) LIKE ?', [$term])
              ->orWhereRaw('LOWER(name) LIKE ?', [$term])
              ->orWhereRaw('LOWER(serial_number) LIKE ?', [$term])
              ->orWhereRaw('LOWER(brand) LIKE ?', [$term])
              ->orWhereRaw('LOWER(model) LIKE ?', [$term])
              ->orWhereRaw('LOWER(license_plate) LIKE ?', [$term])
              ->orWhereRaw('LOWER(hostname) LIKE ?', [$term])
              ->orWhereRaw('LOWER(ip_address) LIKE ?', [$term])
              ->orWhereRaw('LOWER(mac_address) LIKE ?', [$term])
              ->orWhereRaw('LOWER(vin) LIKE ?', [$term])
              ->orWhereRaw('LOWER(stnk_number) LIKE ?', [$term]);
        });
    }

    /**
     * Scope: filter by category.
     */
    public function scopeOfCategory($query, ?string $category)
    {
        if (!$category) {
            return $query;
        }

        return $query->where('category', $category);
    }

    /**
     * Scope: filter by status.
     */
    public function scopeOfStatus($query, ?string $status)
    {
        if (!$status) {
            return $query;
        }

        return $query->where('status', $status);
    }

    /**
     * Scope: filter by condition.
     */
    public function scopeOfCondition($query, ?string $condition)
    {
        if (!$condition) {
            return $query;
        }

        return $query->where('condition_status', $condition);
    }

    /**
     * Scope: filter by location.
     */
    public function scopeOfLocation($query, ?string $location)
    {
        if (!$location) {
            return $query;
        }

        return $query->whereRaw('LOWER(location) LIKE ?', ["%{$location}%"]);
    }

    /**
     * Scope: filter by assignment state (assigned | unassigned).
     */
    public function scopeOfAssignment($query, ?string $state)
    {
        if (!$state) {
            return $query;
        }

        if ($state === 'assigned') {
            return $query->whereHas('activeAssignment');
        }

        return $query->whereDoesntHave('activeAssignment');
    }
}
