<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Services\Google\GoogleSheetsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class RefreshController extends Controller
{
    protected GoogleSheetsService $sheets;

    public function __construct(GoogleSheetsService $sheets)
    {
        $this->sheets = $sheets;
    }

    public function refreshData(): JsonResponse
    {
        try {
            $sheetNames = ['Employee', 'data_kandidat', 'MPR'];
            $health = $this->sheets->healthCheck($sheetNames);

            if (! $health['success']) {
                return response()->json([
                    'success' => false,
                    'status' => 'unhealthy',
                    'message' => $health['message'] ?? 'Gagal mengambil data terbaru dari Google Sheets.',
                ], 503);
            }

            $summary = [];
            $healthySheets = $health['sheets'] ?? $sheetNames;

            foreach ($healthySheets as $sheetName) {
                $this->sheets->clearCache($sheetName);
                $values = $this->sheets->getRange($sheetName, 'A:ZZ', false);
                $summary[$sheetName] = [
                    'rows' => is_array($values) ? count($values) : 0,
                    'has_header' => is_array($values) && ! empty($values[0]),
                ];
            }

            return response()->json([
                'success' => true,
                'status' => 'healthy',
                'message' => 'Data berhasil diperbarui.',
                'data' => [
                    'sheets' => $healthySheets,
                    'summary' => $summary,
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('RefreshController::refreshData failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'status' => 'unhealthy',
                'message' => 'Gagal mengambil data terbaru dari Google Sheets.',
            ], 503);
        }
    }
}
