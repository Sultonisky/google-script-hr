<?php

namespace App\Http\Requests\HR\Concerns;

use App\Enums\AssetCategory;
use Illuminate\Validation\Rule;

/**
 * Category-aware validation rules for asset detail fields.
 *
 * Only the fields belonging to the submitted category are validated.
 * Fields hidden in the UI are disabled and never sent, so non-active
 * category columns on an existing asset are safely preserved.
 */
trait ValidatesAssetCategory
{
    protected function categoryDetailRules(): array
    {
        $rules = [];

        if ($this->input('category') === AssetCategory::BUILDING->value) {
            $rules += [
                'property_type'       => ['nullable', Rule::in(AssetCategory::fieldOptions('property_type'))],
                'ownership_status'    => ['required', Rule::in(AssetCategory::fieldOptions('ownership_status'))],
                'address'             => ['nullable', 'string', 'max:500'],
                'area_sqm'            => ['nullable', 'numeric', 'min:0'],
                'land_area_sqm'       => ['nullable', 'numeric', 'min:0'],
                'floors'              => ['nullable', 'integer', 'min:1', 'max:500'],
                'certificate_number'  => ['nullable', 'string', 'max:120'],
                'maintenance_schedule' => ['nullable', 'string', 'max:255'],
            ];
        }

        if ($this->input('category') === AssetCategory::VEHICLE->value) {
            $rules += [
                'vehicle_type'    => ['nullable', Rule::in(AssetCategory::fieldOptions('vehicle_type'))],
                'brand'           => ['nullable', 'string', 'max:255'],
                'model'           => ['nullable', 'string', 'max:255'],
                'year'            => ['nullable', 'integer', 'min:1900', 'max:' . (date('Y') + 1)],
                'license_plate'   => ['nullable', 'string', 'max:20', 'regex:/^[A-Za-z]{1,2}\s?\d{1,4}(\s?[A-Za-z]{1,3})?$/'],
                'vin'             => ['nullable', 'string', 'max:100'],
                'engine_number'   => ['nullable', 'string', 'max:100'],
                'color'           => ['nullable', 'string', 'max:50'],
                'fuel_type'       => ['nullable', Rule::in(AssetCategory::fieldOptions('fuel_type'))],
                'transmission'    => ['nullable', Rule::in(AssetCategory::fieldOptions('transmission'))],
                'stnk_number'     => ['nullable', 'string', 'max:50'],
                'stnk_expiry'     => ['nullable', 'date'],
                'bpkb_number'     => ['nullable', 'string', 'max:50'],
                'last_service_date' => ['nullable', 'date'],
                'next_service_date' => ['nullable', 'date'],
                'mileage'         => ['nullable', 'integer', 'min:0', 'max:10000000'],
            ];
        }

        if ($this->input('category') === AssetCategory::OFFICE->value) {
            $rules += [
                'equipment_type'    => ['nullable', Rule::in(AssetCategory::fieldOptions('equipment_type'))],
                'brand'             => ['nullable', 'string', 'max:255'],
                'model'             => ['nullable', 'string', 'max:255'],
                'serial_number'     => ['nullable', 'string', 'max:255'],
                'purchase_warranty' => ['nullable', 'string', 'max:120'],
                'warranty_expiry'   => ['nullable', 'date', 'after_or_equal:warranty_start'],
                'warranty_start'    => ['nullable', 'date'],
                'maintenance_schedule' => ['nullable', 'string', 'max:255'],
                'supplier'          => ['nullable', 'string', 'max:255'],
            ];
        }

        if ($this->input('category') === AssetCategory::ELEKTRONIK->value) {
            $rules += [
                'device_type'    => ['nullable', Rule::in(AssetCategory::fieldOptions('device_type'))],
                'brand'          => ['nullable', 'string', 'max:255'],
                'model'          => ['nullable', 'string', 'max:255'],
                'serial_number'  => ['nullable', 'string', 'max:255'],
                'processor'      => ['nullable', 'string', 'max:120'],
                'ram'            => ['nullable', 'string', 'max:50'],
                'storage'        => ['nullable', 'string', 'max:50'],
                'storage_type'   => ['nullable', Rule::in(AssetCategory::fieldOptions('storage_type'))],
                'operating_system' => ['nullable', 'string', 'max:120'],
                'mac_address'    => ['nullable', 'string', 'regex:/^([0-9A-Fa-f]{2}[:-]){5}([0-9A-Fa-f]{2})$/'],
                'ip_address'     => ['nullable', 'ip'],
                'hostname'       => ['nullable', 'string', 'max:120'],
                'warranty_start' => ['nullable', 'date'],
                'warranty_expiry' => ['nullable', 'date', 'after_or_equal:warranty_start'],
            ];
        }

        return $rules;
    }
}
