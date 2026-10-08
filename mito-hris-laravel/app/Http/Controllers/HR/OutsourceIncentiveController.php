<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Models\OutsourceIncentive;
use App\Repositories\Contracts\AuditLogRepositoryInterface;
use App\Services\OutsourceIncentiveImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx as XlsxWriter;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\View\View;

class OutsourceIncentiveController extends Controller
{
    private const PERIOD_REGEX = '/^\d{4}-(0[1-9]|1[0-2])$/';

    public function __construct(
        protected OutsourceIncentiveImportService $importer,
        protected AuditLogRepositoryInterface $auditRepo
    ) {}

    public function index(Request $request): View
    {
        $periods = OutsourceIncentive::query()->distinct()->orderByDesc('period')->pluck('period');
        $period = (string) $request->query('period', '');
        if (!preg_match(self::PERIOD_REGEX, $period)) {
            $period = (string) ($periods->first() ?? now()->timezone('Asia/Jakarta')->format('Y-m'));
        }

        $search = trim((string) $request->query('search', ''));
        $incentives = $this->incentiveQuery($period, $search)->paginate(15)->withQueryString();

        return view('hr.outsource-incentive.index', compact('incentives', 'periods', 'period', 'search'));
    }

    public function export(Request $request): StreamedResponse
    {
        $period = (string) $request->query('period', '');
        if (!preg_match(self::PERIOD_REGEX, $period)) {
            $period = (string) (OutsourceIncentive::query()->orderByDesc('period')->value('period') ?? now()->timezone('Asia/Jakarta')->format('Y-m'));
        }
        $search = trim((string) $request->query('search', ''));
        $incentives = $this->incentiveQuery($period, $search)->orderBy('full_name')->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Outsource Incentive');
        $headers = ['Outsource ID', 'Nama', 'Vendor', 'UMK', 'Incentive'];
        foreach ($headers as $index => $header) {
            $column = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($index + 1);
            $sheet->setCellValue($column . '1', $header);
        }
        $sheet->getStyle('A1:E1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF005BAC']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->freezePane('A2');

        foreach ($incentives as $index => $incentive) {
            $row = $index + 2;
            $sheet->getCell('A' . $row)->setValueExplicit((string) $incentive->outsource_id, DataType::TYPE_STRING);
            $sheet->setCellValue('B' . $row, $incentive->full_name ?? '');
            $sheet->setCellValue('C' . $row, $incentive->vendor ?? '');
            $sheet->setCellValue('D' . $row, (float) ($incentive->umk_amount ?? 0));
            $sheet->setCellValue('E' . $row, (float) ($incentive->incentive_amount ?? 0));
        }
        foreach (range('A', 'E') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }
        $sheet->getStyle('D2:E' . max(2, $incentives->count() + 1))->getNumberFormat()->setFormatCode('#,##0');
        $spreadsheet->getProperties()->setCreator('MITO HRIS')->setTitle('Outsource Incentive ' . $period);

        if (Gate::allows('view_reports')) {
            $this->auditRepo->log(
                'Outsource',
                'INCENTIVE_' . $period,
                'exported',
                'Outsource Incentive',
                '-',
                ['format' => 'XLSX', 'total_records' => $incentives->count()],
                $this->hrUserName(),
                'HR Dashboard'
            );
        }

        $filename = 'Outsource_Incentive_' . $period . '_' . now()->timezone('Asia/Jakarta')->format('Ymd_His') . '.xlsx';

        return response()->stream(function () use ($spreadsheet): void {
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

    private function incentiveQuery(string $period, string $search): \Illuminate\Database\Eloquent\Builder
    {
        return OutsourceIncentive::query()
            ->where('period', $period)
            ->when($search !== '', function ($query) use ($search): void {
                $like = '%' . mb_strtolower($search) . '%';
                $query->where(fn ($where) => $where->whereRaw('LOWER(outsource_id) LIKE ?', [$like])->orWhereRaw('LOWER(full_name) LIKE ?', [$like]));
            });
    }

    public function template(): StreamedResponse
    {
        $spreadsheet = $this->importer->template();
        $filename = 'Template_Outsource_Incentive_' . now()->timezone('Asia/Jakarta')->format('Ymd') . '.xlsx';

        return response()->stream(function () use ($spreadsheet): void {
            (new XlsxWriter($spreadsheet))->save('php://output');
            $spreadsheet->disconnectWorksheets();
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
        ]);
    }

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

        $period = $validated['period'];
        $user = $this->hrUserName();
        $sourceFile = mb_substr((string) $request->file('file')->getClientOriginalName(), 0, 255);
        try {
            $result = $this->importer->import($request->file('file')->getRealPath(), $period, $request->boolean('dry_run'), $user, $sourceFile);
        } catch (\RuntimeException $exception) {
            return response()->json(['success' => false, 'message' => 'Gagal membaca file: ' . $exception->getMessage()], 422);
        } catch (\Throwable $exception) {
            report($exception);

            return response()->json(['success' => false, 'message' => 'Gagal membaca file. Pastikan file .xlsx valid dan tidak terproteksi password.'], 422);
        }

        $ok = $result['errors'] === [];
        $changed = $result['created'] + $result['updated'] > 0;
        if (!$request->boolean('dry_run') && $ok && $changed) {
            $this->auditRepo->log(
                'Outsource',
                'INCENTIVE_' . $period,
                'imported',
                'Outsource Incentive',
                '-',
                "Periode {$period}: dibuat {$result['created']}, diperbarui {$result['updated']}, tidak berubah {$result['unchanged']}, dilewati {$result['skipped']}",
                $user,
                'HR Dashboard'
            );
        }

        return response()->json([
            'success' => $ok,
            'dry_run' => $request->boolean('dry_run'),
            'period' => $period,
            'message' => !$ok
                ? 'Ditemukan ' . count($result['errors']) . ' error. Perbaiki file lalu coba lagi; belum ada data yang disimpan.'
                : ($changed
                    ? "Incentive periode {$period}: {$result['created']} baru, {$result['updated']} diperbarui, {$result['unchanged']} tidak berubah."
                    : "Tidak ada perubahan untuk incentive periode {$period}."),
            'summary' => [
                'read' => $result['read'],
                'created' => $result['created'],
                'updated' => $result['updated'],
                'unchanged' => $result['unchanged'],
                'skipped' => $result['skipped'],
            ],
            'rows' => $request->boolean('dry_run') ? $result['rows'] : [],
            'warnings' => $result['warnings'],
            'errors' => $result['errors'],
        ], $ok ? 200 : 422);
    }
}
