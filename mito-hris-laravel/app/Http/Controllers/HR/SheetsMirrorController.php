<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Repositories\Contracts\AuditLogRepositoryInterface;
use App\Services\Etl\DatabaseToSheetsEtlService;
use App\Support\HrisDataDriver;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Manual one-way mirror DB → Google Sheets from the HR UI.
 * Equivalent to `php artisan mito:etl-db-to-sheets`; never reads Sheets back into the DB.
 */
class SheetsMirrorController extends Controller
{
    private const LOCK_KEY = 'hris:mirror-db-to-sheets';

    private const LOCK_SECONDS = 600;

    public function __construct(
        protected DatabaseToSheetsEtlService $etl,
        protected AuditLogRepositoryInterface $auditLogs,
    ) {}

    public function sync(): JsonResponse
    {
        if (! HrisDataDriver::usesPgsql()) {
            return response()->json([
                'success' => false,
                'message' => 'Sinkron ke Spreadsheet hanya tersedia saat database menjadi sumber data utama.',
            ], 409);
        }

        if (! config('google.enabled')) {
            return response()->json([
                'success' => false,
                'message' => 'Integrasi Google Sheets tidak aktif.',
            ], 409);
        }

        $lock = Cache::lock(self::LOCK_KEY, self::LOCK_SECONDS);
        if (! $lock->get()) {
            return response()->json([
                'success' => false,
                'message' => 'Sinkronisasi ke Spreadsheet sedang berjalan. Coba lagi beberapa saat.',
            ], 409);
        }

        $actor = (string) (session('hr_user.email') ?? 'System');

        try {
            @set_time_limit(self::LOCK_SECONDS);

            $summary = $this->etl->run(['all']);
        } catch (\Throwable $e) {
            Log::error('SheetsMirrorController::sync failed: '.$e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'user' => $actor,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal menyinkronkan data ke Spreadsheet.',
            ], 500);
        } finally {
            $lock->release();
        }

        $failed = array_keys(array_filter($summary, fn (array $row) => $row['errors'] !== []));
        $written = array_sum(array_column($summary, 'written'));

        foreach ($failed as $domain) {
            Log::warning("SheetsMirrorController::sync domain {$domain} failed", [
                'errors' => $summary[$domain]['errors'],
                'user' => $actor,
            ]);
        }

        $this->auditLogs->log(
            entityType: 'System',
            entityId: 'SHEETS-MIRROR-'.now()->format('YmdHis'),
            action: 'Sync',
            field: 'Spreadsheet',
            oldValue: '-',
            newValue: $failed === []
                ? "{$written} baris disinkronkan dari database"
                : 'Gagal sebagian: '.implode(', ', $failed),
            user: $actor,
            source: 'Dashboard'
        );

        $domains = collect($summary)
            ->map(fn (array $row) => ['written' => $row['written'], 'failed' => $row['errors'] !== []])
            ->all();

        if ($failed !== []) {
            return response()->json([
                'success' => false,
                'message' => 'Sebagian data gagal disinkronkan ke Spreadsheet: '.implode(', ', $failed).'.',
                'data' => ['domains' => $domains],
            ], 502);
        }

        return response()->json([
            'success' => true,
            'message' => 'Spreadsheet berhasil disinkronkan dari database.',
            'data' => ['domains' => $domains, 'written' => $written],
        ]);
    }
}
