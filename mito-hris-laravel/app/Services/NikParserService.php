<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;

class NikParserService
{
    protected array $provinces = [];
    protected array $cities = [];
    protected array $districts = [];

    public function __construct()
    {
        $wilayahPath = database_path('data/master_wilayah.json');
        if (File::exists($wilayahPath)) {
            $json = json_decode(File::get($wilayahPath), true);
            $this->provinces = $json['provinces'] ?? [];
            $this->cities = $json['cities'] ?? [];
        }

        $kecamatanPath = database_path('data/kecamatan_all.json');
        if (File::exists($kecamatanPath)) {
            $this->districts = json_decode(File::get($kecamatanPath), true) ?? [];
        }
    }

    /**
     * Parse 16-digit Indonesian NIK to extract demographic and geographic details.
     */
    public function parse(string $nik): array
    {
        $nik = trim($nik);

        if (strlen($nik) !== 16 || !ctype_digit($nik)) {
            return [
                'valid'   => false,
                'message' => 'NIK harus terdiri dari 16 digit angka.',
            ];
        }

        $provCode   = substr($nik, 0, 2);
        $cityCode   = substr($nik, 0, 4);
        $distCode   = substr($nik, 0, 6);
        $rawDay     = (int) substr($nik, 6, 2);
        $month      = (int) substr($nik, 8, 2);
        $year2Digit = (int) substr($nik, 10, 2);
        $sequence   = substr($nik, 12, 4);

        // Determine gender and actual day of birth
        $gender = 'Laki-laki';
        $day = $rawDay;
        if ($rawDay > 40) {
            $gender = 'Perempuan';
            $day = $rawDay - 40;
        }

        // Determine century (assumes working age 17-70)
        $currentYear2Digit = (int) date('y');
        $fullYear = ($year2Digit > $currentYear2Digit) ? (1900 + $year2Digit) : (2000 + $year2Digit);

        $birthDateStr = sprintf('%04d-%02d-%02d', $fullYear, $month, $day);
        
        $age = null;
        $isValidDate = checkdate($month, $day, $fullYear);
        if ($isValidDate) {
            $age = Carbon::parse($birthDateStr)->age;
        }

        // Get Province Name
        $provinsi = $this->provinces[$provCode] ?? 'Tidak Diketahui';

        // Get City Name
        $cityData = $this->cities[$cityCode] ?? null;
        $kotaKab = is_array($cityData) ? ($cityData['name'] ?? 'Tidak Diketahui') : ($cityData ?? 'Tidak Diketahui');

        return [
            'valid'          => true,
            'nik'            => $nik,
            'provinsi_kode'  => $provCode,
            'provinsi_nama'  => $provinsi,
            'kota_kode'      => $cityCode,
            'kota_nama'      => $kotaKab,
            'kecamatan_kode' => $distCode,
            'jenis_kelamin'  => $gender,
            'tanggal_lahir'  => $birthDateStr,
            'usia'           => $age,
            'nomor_urut'     => $sequence,
            'tanggal_valid'  => $isValidDate,
        ];
    }
}
