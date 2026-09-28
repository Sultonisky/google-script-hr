<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Models\Candidate;
use App\Models\Employee;
use App\Models\MprRequest;
use App\Repositories\Contracts\CandidateRepositoryInterface;
use App\Repositories\Contracts\EmployeeRepositoryInterface;
use App\Repositories\Contracts\MprRepositoryInterface;
use App\Services\Google\GoogleSheetsService;
use App\Support\HrisDataDriver;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class RefreshController extends Controller
{
    public function __construct(
        protected GoogleSheetsService $sheets,
        protected EmployeeRepositoryInterface $employees,
        protected CandidateRepositoryInterface $candidates,
        protected MprRepositoryInterface $mpr,
    ) {}

    public function refreshData(): JsonResponse
    {
        try {
            if (HrisDataDriver::usesSheets()) {
                return $this->refreshFromSheets();
            }

            return $this->refreshFromRepositories();
        } catch (\Throwable $e) {
            Log::error('RefreshController::refreshData failed: '.$e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'driver' => HrisDataDriver::current(),
            ]);

            $source = HrisDataDriver::usesSheets() ? 'Google Sheets' : 'database';

            return response()->json([
                'success' => false,
                'status' => 'unhealthy',
                'message' => "Gagal mengambil data terbaru dari {$source}.",
            ], 503);
        }
    }

    private function refreshFromRepositories(): JsonResponse
    {
        if (HrisDataDriver::usesPgsql()) {
            return $this->refreshFromDatabase();
        }

        $summary = [];
        $sources = [
            'employees' => fn () => $this->employees->getAll()->count(),
            'candidates' => fn () => $this->candidates->getAll()->count(),
            'mpr' => fn () => $this->mpr->getAll()->count(),
        ];

        foreach ($sources as $key => $loader) {
            try {
                $summary[$key] = ['rows' => $loader()];
            } catch (\Throwable $e) {
                // Local driver still binds some domains to Sheets adapters; don't fail the whole refresh.
                $summary[$key] = ['rows' => null, 'warning' => $e->getMessage()];
            }
        }

        return response()->json([
            'success' => true,
            'status' => 'healthy',
            'message' => 'Data berhasil diperbarui.',
            'data' => [
                'driver' => HrisDataDriver::current(),
                'summary' => $summary,
            ],
        ]);
    }

    /**
     * DB reads are uncached, so refresh only verifies connectivity with cheap COUNT queries.
     * A DB failure propagates to refreshData() and yields 503.
     */
    private function refreshFromDatabase(): JsonResponse
    {
        $summary = [
            'employees' => ['rows' => Employee::query()->count()],
            'candidates' => ['rows' => Candidate::query()->count()],
            'mpr' => ['rows' => MprRequest::query()->count()],
        ];

        return response()->json([
            'success' => true,
            'status' => 'healthy',
            'message' => 'Data berhasil diperbarui dari database.',
            'data' => [
                'driver' => HrisDataDriver::PGSQL,
                'summary' => $summary,
            ],
        ]);
    }

    private function refreshFromSheets(): JsonResponse
    {
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
                'driver' => HrisDataDriver::SHEETS,
                'sheets' => $healthySheets,
                'summary' => $summary,
            ],
        ]);
    }
}
