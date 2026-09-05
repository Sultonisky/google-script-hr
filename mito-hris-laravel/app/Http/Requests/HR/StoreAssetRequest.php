<?php

namespace App\Http\Requests\HR;

use App\Enums\AssetCategory;
use App\Enums\AssetCondition;
use App\Enums\AssetStatus;
use App\Http\Requests\HR\Concerns\ValidatesAssetCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAssetRequest extends FormRequest
{
    use ValidatesAssetCategory;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $base = [
            'asset_code'      => ['nullable', 'string', 'max:50', 'unique:assets,asset_code'],
            'category'        => ['required', Rule::in(array_column(AssetCategory::cases(), 'value'))],
            'name'            => ['required', 'string', 'max:255'],
            'description'     => ['nullable', 'string', 'max:1000'],
            'brand'           => ['nullable', 'string', 'max:255'],
            'model'           => ['nullable', 'string', 'max:255'],
            'serial_number'   => ['nullable', 'string', 'max:255'],
            'purchase_date'   => ['nullable', 'date'],
            'purchase_price'  => ['nullable', 'numeric', 'min:0'],
            'condition_status' => ['nullable', Rule::in(array_column(AssetCondition::cases(), 'value'))],
            'status'          => ['nullable', Rule::in(array_column(AssetStatus::cases(), 'value'))],
            'location'        => ['nullable', 'string', 'max:255'],
            'notes'           => ['nullable', 'string', 'max:2000'],
        ];

        return array_merge($base, $this->categoryDetailRules());
    }

    public function messages(): array
    {
        return [
            'category.required'   => 'Kategori aset wajib dipilih.',
            'name.required'       => 'Nama aset wajib diisi.',
            'asset_code.unique'   => 'Kode aset sudah digunakan.',
            'purchase_price.min'  => 'Harga pembelian tidak boleh negatif.',
            'year.min'            => 'Tahun tidak valid.',
            'year.max'            => 'Tahun tidak valid.',
            'ownership_status.required' => 'Status kepemilikan wajib diisi untuk aset bangunan/properti.',
            'license_plate.regex' => 'Format plat nomor tidak valid (contoh: B 1234 XYZ).',
            'area_sqm.min'        => 'Luas bangunan tidak boleh negatif.',
            'land_area_sqm.min'   => 'Luas tanah tidak boleh negatif.',
            'floors.integer'      => 'Jumlah lantai harus berupa angka.',
            'mileage.min'         => 'Kilometer tidak boleh negatif.',
            'mac_address.regex'   => 'Format MAC Address tidak valid (contoh: AA:BB:CC:DD:EE:FF).',
            'ip_address.ip'       => 'Format IP Address tidak valid.',
            'warranty_expiry.after_or_equal' => 'Tanggal berakhir garansi tidak boleh sebelum tanggal mulai.',
        ];
    }
}

