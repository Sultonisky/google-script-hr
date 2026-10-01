<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Models\OutsourcePayslip;
use App\Repositories\Contracts\AuditLogRepositoryInterface;
use App\Services\OutsourcePayslipImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx as XlsxWriter;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OutsourcePayslipController extends Controller
{
    private const PERIOD_REGEX = '/^\d{4}-(0[1-9]|1[0-2])$/';

    private const PER_PAGE = 15;

    public function __construct(
        protected OutsourcePayslipImportService $importer,
        protected AuditLogRepositoryInterface $auditRepo
    ) {}

    /**
     * GET /hr/outsource-payslips
     * Requires: can:view_outsource_payslip
     */
    public function index(Request $request): View
    {
        $perPage = self::PER_PAGE;
        $currentPage = max(1, (int) $request->query('page', 1));

        $periods = OutsourcePayslip::query()->distinct()->orderByDesc('period')->pluck('period');
        $periodFilter = (string) $request->query('period', '');
        if (!preg_match(self::PERIOD_REGEX, $periodFilter)) {
            $periodFilter = (string) ($periods->first() ?? now()->timezone('Asia/Jakarta')->format('Y-m'));
        }

        $vendorFilter = (string) $request->query('vendor', '');
        $vendorFilter = in_array($vendorFilter, config('hris.outsource.vendors', []), true) ? $vendorFilter : '';
        $searchFilter = trim((string) $request->query('search', ''));

        $query = OutsourcePayslip::query()
            ->where('period', $periodFilter)
            ->when($vendorFilter !== '', fn ($q) => $q->where('vendor', $vendorFilter))
            ->when($searchFilter !== '', function ($q) use ($searchFilter) {
                $like = '%' . mb_strtolower($searchFilter) . '%';
                $q->where(fn ($w) => $w->whereRaw('LOWER(outsource_id) LIKE ?', [$like])->orWhereRaw('LOWER(full_name) LIKE ?', [$like]));
            });

        $total = (clone $query)->count();
        $payslips = $query->orderBy('full_name')->forPage($currentPage, $perPage)->get();

        return view('hr.outsource-payslip.index', compact(
            'payslips',
            'periods',
            'total',
            'currentPage',
            'perPage',
            'periodFilter',
            'vendorFilter',
            'searchFilter'
        ));
    }

    /**
     * Template Excel berisi Outsource ID + nama dari master outsource.
     *
     * GET /hr/outsource-payslips/template?vendor=...
     * Requires: can:manage_outsource_payslip
     */
    public function template(Request $request): StreamedResponse
    {
        $vendor = (string) $request->query('vendor', '');
        $vendor = in_array($vendor, config('hris.outsource.vendors', []), true) ? $vendor : null;

        $spreadsheet = $this->importer->template($vendor);
        $filename = 'Template_Payslip_Outsource' . ($vendor ? "_{$vendor}" : '') . '_' . now()->timezone('Asia/Jakarta')->format('Ymd') . '.xlsx';

        return response()->stream(function () use ($spreadsheet) {
            (new XlsxWriter($spreadsheet))->save('php://output');
            $spreadsheet->disconnectWorksheets();
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }

    /**
     * Preview (dry_run=1) lalu simpan (dry_run=0) payslip dari file Excel.
     *
     * POST /hr/outsource-payslips/import
     * Requires: can:manage_outsource_payslip
     */
    public function import(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'file' => ['required', 'file', 'max:10240', 'extensions:xlsx', 'mimes:xlsx,zip'],
            'period' => ['required', 'string', 'regex:' . self::PERIOD_REGEX],
            'dry_run' => ['nullable', 'boolean'],
        ], [
            'file.required' => 'File Excel wajib dipilih.',
            'file.max' => 'Ukuran file maksimal 10 MB.',
            'file.extensions' => 'File harus berformat .xlsx.',
            'file.mimes' => 'File harus berformat .xlsx.',
            'period.required' => 'Periode wajib dipilih.',
            'period.regex' => 'Periode tidak valid. Gunakan format YYYY-MM.',
        ]);

        $dryRun = $request->boolean('dry_run');
        $period = $validated['period'];
        $user = $this->hrUserName();
        $sourceFile = mb_substr((string) $request->file('file')->getClientOriginalName(), 0, 255);

        @set_time_limit(300);

        try {
            $result = $this->importer->import($request->file('file')->getRealPath(), $period, $dryRun, $user, $sourceFile);
        } catch (\RuntimeException $e) {
            return response()->json(['success' => false, 'message' => 'Gagal membaca file: ' . $e->getMessage()], 422);
        } catch (\Throwable $e) {
            report($e);

            return response()->json(['success' => false, 'message' => 'Gagal membaca file. Pastikan file .xlsx valid dan tidak terproteksi password.'], 422);
        }

        $ok = $result['errors'] === [];
        $changed = $result['created'] + $result['updated'] > 0;

        if (!$dryRun && $ok && $changed) {
            $this->auditRepo->log(
                'Outsource',
                'PAYSLIP_' . $period,
                'imported',
                'Payslip Outsource',
                '-',
                "Periode {$period}: dibuat {$result['created']}, diperbarui {$result['updated']}, tidak berubah {$result['unchanged']}, dilewati {$result['skipped']}",
                $user,
                'HR Dashboard'
            );
        }

        $message = match (true) {
            !$ok => 'Ditemukan ' . count($result['errors']) . ' error. Perbaiki file lalu coba lagi; belum ada data yang disimpan.',
            !$changed => "Tidak ada perubahan: semua payslip periode {$period} di file sama dengan data tersimpan.",
            $dryRun => "Preview periode {$period}: {$result['read']} baris dibaca, {$result['created']} baru, {$result['updated']} diperbarui, {$result['unchanged']} tidak berubah, {$result['skipped']} dilewati.",
            default => "Payslip periode {$period} tersimpan: {$result['created']} baru, {$result['updated']} diperbarui, {$result['unchanged']} tidak berubah.",
        };

        return response()->json([
            'success' => $ok,
            'dry_run' => $dryRun,
            'period' => $period,
            'message' => $message,
            'summary' => [
                'read' => $result['read'],
                'created' => $result['created'],
                'updated' => $result['updated'],
                'unchanged' => $result['unchanged'],
                'skipped' => $result['skipped'],
            ],
            'rows' => $dryRun ? $result['rows'] : [],
            'warnings' => $result['warnings'],
            'errors' => $result['errors'],
        ], $ok ? 200 : 422);
    }
}
