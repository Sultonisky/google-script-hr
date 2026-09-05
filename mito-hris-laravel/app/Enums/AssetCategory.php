<?php

namespace App\Enums;

enum AssetCategory: string
{
    case BUILDING   = 'Building';
    case VEHICLE    = 'Vehicle';
    case OFFICE     = 'Office';
    case ELEKTRONIK = 'Elektronik';

    public function prefix(): string
    {
        return match ($this) {
            self::BUILDING   => 'BLD',
            self::VEHICLE    => 'VHL',
            self::OFFICE     => 'OFC',
            self::ELEKTRONIK => 'ELK',
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::BUILDING   => 'Building',
            self::VEHICLE    => 'Vehicle',
            self::OFFICE     => 'Office',
            self::ELEKTRONIK => 'Elektronik',
        };
    }

    public static function tryFromPrefix(string $prefix): ?self
    {
        foreach (self::cases() as $case) {
            if (strcasecmp($case->prefix(), $prefix) === 0) {
                return $case;
            }
        }
        return null;
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::BUILDING   => 'bg-secondary text-white',
            self::VEHICLE    => 'bg-primary text-white',
            self::OFFICE     => 'bg-info text-white',
            self::ELEKTRONIK => 'bg-success text-white',
        };
    }

    /**
     * Option lists for category-specific asset fields.
     * Single source shared by forms + validation.
     */
    public static function fieldOptions(string $field): array
    {
        return match ($field) {
            'property_type'    => ['Building', 'Office', 'Warehouse', 'Land', 'Facility', 'Other'],
            'ownership_status' => ['Owned', 'Leased', 'Rented'],
            'vehicle_type'     => ['Car', 'Motorcycle', 'Truck', 'Van', 'Bus', 'Other'],
            'fuel_type'        => ['Petrol', 'Diesel', 'Electric', 'Hybrid'],
            'transmission'     => ['Manual', 'Automatic', 'CVT'],
            'equipment_type'   => ['Furniture', 'Printer', 'Projector', 'Air Conditioner', 'Refrigerator', 'Telephone', 'Other'],
            'device_type'      => ['Laptop', 'Desktop', 'Monitor', 'Printer', 'Server', 'Network Device', 'Smartphone', 'Tablet', 'Other'],
            'storage_type'     => ['SSD', 'HDD', 'NVMe'],
            default            => [],
        };
    }
}
