<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\NikParserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class RegionController extends Controller
{
    /**
     * Get all Indonesian provinces.
     */
    public function getProvinces(): JsonResponse
    {
        $path = database_path('data/master_wilayah.json');
        if (!File::exists($path)) {
            return response()->json([], 404);
        }

        $data = json_decode(File::get($path), true);
        return response()->json($data['provinces'] ?? []);
    }

    /**
     * Get cities by province code.
     */
    public function getCities(string $provinceCode): JsonResponse
    {
        $path = database_path('data/master_wilayah.json');
        if (!File::exists($path)) {
            return response()->json([], 404);
        }

        $data = json_decode(File::get($path), true);
        $allCities = $data['cities'] ?? [];

        $filtered = [];
        foreach ($allCities as $code => $city) {
            $cityProvince = is_array($city) ? ($city['province'] ?? '') : substr($code, 0, 2);
            if ($cityProvince === $provinceCode) {
                $filtered[$code] = is_array($city) ? ($city['name'] ?? '') : $city;
            }
        }

        return response()->json($filtered);
    }

    /**
     * Get districts (kecamatan) by city code.
     */
    public function getDistricts(string $cityCode): JsonResponse
    {
        $path = database_path('data/kecamatan_all.json');
        if (!File::exists($path)) {
            return response()->json([], 404);
        }

        $districts = json_decode(File::get($path), true);
        return response()->json($districts[$cityCode] ?? []);
    }

    /**
     * Parse 16-digit NIK and return full demographic data.
     */
    public function parseNik(Request $request, NikParserService $parser): JsonResponse
    {
        $nik = $request->input('nik', '');
        $result = $parser->parse($nik);

        return response()->json($result);
    }
}
