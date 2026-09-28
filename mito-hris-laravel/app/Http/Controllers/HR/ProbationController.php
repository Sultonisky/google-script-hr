<?php

namespace App\Http\Controllers\HR;

use App\Enums\ProbationDecisionType;
use App\Http\Controllers\Controller;
use App\Http\Requests\HR\SubmitProbationEvaluationRequest;
use App\Repositories\Contracts\EmployeeRepositoryInterface;
use App\Services\ProbationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class ProbationController extends Controller
{
    protected EmployeeRepositoryInterface $employeeRepo;
    protected ProbationService $probationService;

    public function __construct(
        EmployeeRepositoryInterface $employeeRepo,
        ProbationService $probationService
    ) {
        $this->employeeRepo     = $employeeRepo;
        $this->probationService = $probationService;
    }

    // ==========================================================
    // INDEX — Probation list with latest eval enrichment
    // ==========================================================

    public function index(Request $request): View
    {
        $perPage = max(1, (int) $request->query('per_page', 10));
        $currentPage = max(1, (int) $request->query('page', 1));

        // Population = all employees who are currently on probation (Contract/PKWT)
        // PLUS all employees who ever had a probation evaluation record, regardless of
        // their current status (PKWTT, Terminated, etc.).
        // This prevents Lulus/Tidak Lulus outcomes from removing rows from the page.
        $employeeRows  = $this->employeeRepo->getAll();
        $latestEvals   = $this->probationService->latestEvalByEmployee();

        // Collect every Employee ID that has at least one probation evaluation row.
        $evaluatedIds = $latestEvals->keys()
            ->map(fn($id) => strtoupper(ltrim(trim((string) $id), "'")))
            ->flip()   // flip to use as a fast lookup set
            ->all();

        $allProbations = $employeeRows
            ->filter(function ($employee) use ($evaluatedIds) {
                $status = strtolower(trim((string) ($employee->statusEmployee ?? '')));
                $isActiveContract = in_array($status, ['contract', 'pkwt', 'probation'], true);

                // Also include employees who have a probation history row, even if
                // their status has since changed to PKWTT or Terminated.
                $empIdUpper = strtoupper(ltrim(trim((string) ($employee->employeeId ?? '')), "'"));
                $hasProbationHistory = $empIdUpper !== '' && isset($evaluatedIds[$empIdUpper]);

                return $isActiveContract || $hasProbationHistory;
            })
            ->map(function ($employee) use ($latestEvals) {
                $record = (object) get_object_vars($employee);
                $employeeId = ltrim(trim((string) ($employee->employeeId ?? '')), "'");
                $eval = $latestEvals->get($employeeId);

                if ($eval) {
                    $record->lastEvalId = $eval['evalId'] ?? null;
                    $record->lastOverallTotal = $eval['overallTotal'] ?? null;
                    $record->lastCategory = $eval['category'] ?? null;
                    $record->lastDecision = $eval['decision'] ?? null;
                    $record->lastEvalDate = $eval['evalDate'] ?? null;
                    $record->lastEvaluator = $eval['evaluator'] ?? null;
                }

                $record->can_evaluate = $this->probationService->canEvaluate($employeeId);
                return $record;
            })
            ->filter(fn($record) => !empty($record->employeeId))
            ->values();

        $probations = $allProbations;
        if ($request->filled('search')) {
            $search     = strtolower(trim($request->query('search')));
            $probations = $probations->filter(
                fn($e) =>
                str_contains(strtolower($e->fullName   ?? ''), $search)
                    || str_contains(strtolower($e->employeeId ?? ''), $search)
                    || str_contains(strtolower($e->jobPosition ?? ''), $search)
                    || str_contains(strtolower($e->department  ?? ''), $search)
            )->values();
        }
        $statusFilter = $request->query('status');
        if ($statusFilter !== null && $statusFilter !== '') {
            $probations = $probations->filter(function ($e) use ($statusFilter) {
                $decision = ProbationDecisionType::fromDecisionString($e->lastDecision ?? '');

                return match ($statusFilter) {
                    'lulus' => $decision?->isPass(),
                    'tidak_lulus' => $decision?->isFail(),
                    'extend' => $decision?->isExtend(),
                    default => true,
                };
            })->values();
        }

        $scoreFilter = $request->query('score');
        if ($scoreFilter !== null && $scoreFilter !== '') {
            $probations = $probations->filter(
                fn($e) => strtolower(trim((string) ($e->lastCategory ?? ''))) === strtolower(trim($scoreFilter))
            )->values();
        }

        $sort = $request->query('sort', 'newest');
        $probations = match ($sort) {
            'name_asc'  => $probations->sortBy('fullName')->values(),
            'name_desc' => $probations->sortByDesc('fullName')->values(),
            'oldest'    => $probations->sortBy('joinDate')->values(),
            default     => $probations->sortByDesc('joinDate')->values(),
        };

        $total = $probations->count();
        $offset = ($currentPage - 1) * $perPage;
        $probations = $probations->slice($offset, $perPage)->values();

        // Stats based on latest evaluation status
        $evaluated = $allProbations->filter(fn($e) => !empty($e->lastEvalDate))->count();

        $passed = $allProbations->filter(function ($e) {
            $dt = ProbationDecisionType::fromDecisionString($e->lastDecision ?? '');
            return $dt?->isPass();
        })->count();

        $failed = $allProbations->filter(function ($e) {
            $dt = ProbationDecisionType::fromDecisionString($e->lastDecision ?? '');
            return $dt?->isFail();
        })->count();

        $extended = $allProbations->filter(function ($e) {
            $dt = ProbationDecisionType::fromDecisionString($e->lastDecision ?? '');
            return $dt?->isExtend();
        })->count();

        // 'onboarding' now means total employees with any probation record (historical + active)
        $stats = [
            'onboarding' => $allProbations->count(),
            'evaluated'  => $evaluated,
            'passed'     => $passed,
            'failed'     => $failed,
            'extended'   => $extended,
        ];

        return view('hr.probation.index', compact(
            'probations',
            'allProbations',
            'stats',
            'statusFilter',
            'scoreFilter',
            'total',
            'currentPage',
            'perPage'
        ));
    }

    // ==========================================================
    // EVALUATE — Submit evaluation, classify decision, build PDF URLs
    //
    // PDF rules (on-demand — same convention as hr/export PDF functions):
    //   PASS   → SK Pengangkatan PDF + Performance Review PDF
    //   FAIL   → Paklaring PDF + Performance Review PDF
    //   EXTEND → NO PDF generated (evaluation + duration saved only)
    // ==========================================================

    public function evaluate(SubmitProbationEvaluationRequest $request, string $id): RedirectResponse|JsonResponse
    {
        $isAjax = $request->ajax() || $request->wantsJson();

        // Server-side validation: check if evaluation is allowed
        if (!$this->probationService->canEvaluate($id)) {
            $msg = 'Kandidat sudah menyelesaikan evaluasi probation dan tidak dapat dievaluasi kembali.';
            if ($isAjax) {
                return response()->json(['success' => false, 'message' => $msg], 422);
            }
            return redirect()->back()->withInput()->with('error', $msg);
        }

        $evalData = [
            'decision'   => $request->input('decision'),
            'indicators' => [
                'integrity_1' => (string) $request->input('indicators.integrity_1'),
                'integrity_2' => (string) $request->input('indicators.integrity_2'),
                'integrity_3' => (string) $request->input('indicators.integrity_3'),
                'integrity_4' => (string) $request->input('indicators.integrity_4'),
                'ci_1'        => (string) $request->input('indicators.ci_1'),
                'ci_2'        => (string) $request->input('indicators.ci_2'),
                'ci_3'        => (string) $request->input('indicators.ci_3'),
                'ci_4'        => (string) $request->input('indicators.ci_4'),
                'ee_1'        => (string) $request->input('indicators.ee_1'),
                'ee_2'        => (string) $request->input('indicators.ee_2'),
                'tw_1'        => (string) $request->input('indicators.tw_1'),
                'tw_2'        => (string) $request->input('indicators.tw_2'),
                'tw_3'        => (string) $request->input('indicators.tw_3'),
            ],
            'extension_duration' => $request->input('extension_duration', ''),
            'extension_start'    => $request->input('extension_start',    ''),
            'extension_end'      => $request->input('extension_end',      ''),
            'notes'              => $request->input('notes',              ''),
            'recruitment_id'     => $request->input('recruitment_id',     ''),
            'reviewer_name'      => $request->input('reviewer_name',      ''),
            'approval_dept'      => $request->input('approval_dept',      ''),
            'approval_dept_name' => $request->input('approval_dept_name', ''),
            'approval_dept_date' => $request->input('approval_dept_date', ''),
            'approval_hrbp'      => $request->input('approval_hrbp',      ''),
            'approval_hrbp_name' => $request->input('approval_hrbp_name', ''),
            'approval_hrbp_date' => $request->input('approval_hrbp_date', ''),
        ];

        $user = $this->hrUserName();

        try {
            $result = $this->probationService->evaluateProbation($id, $evalData, $user);
        } catch (RuntimeException $e) {
            if ($isAjax) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
            }
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }

        // ── Classify decision using Enum ─────────────────────────
        $decisionType = ProbationDecisionType::fromDecisionString($result['decision']);
        $successMsg   = $result['message'];

        // ── Build on-demand PDF URLs (hr.export.* streams the download) ──
        //
        // PASS   → pdfUrl = SK Pengangkatan  + evalPdfUrl = Performance Review
        // FAIL   → pdfUrl = Paklaring        + evalPdfUrl = Performance Review
        // EXTEND → pdfUrl = null             + evalPdfUrl = null  (no PDF at all)
        $pdfUrl     = null;
        $evalPdfUrl = null;

        if ($decisionType?->isPass()) {
            $pdfUrl = route('hr.export.sk-pengangkatan', [
                $id,
                'sk_number' => $result['skNumber'],
            ]);
            $evalPdfUrl = route('hr.export.performance-review', array_merge(
                [$id],
                $this->buildEvalPdfParams($result['evalId'], $evalData)
            ));
        } elseif ($decisionType?->isFail()) {
            $pdfUrl = route('hr.export.paklaring', [
                $id,
                'letter_number'     => $result['skNumber'],
                'evalId'            => $result['evalId'],
                'last_working_date' => now()->timezone('Asia/Jakarta')->format('Y-m-d'),
            ]);
            $evalPdfUrl = route('hr.export.performance-review', array_merge(
                [$id],
                $this->buildEvalPdfParams($result['evalId'], $evalData)
            ));
        }

        if ($isAjax) {
            return response()->json([
                'success'        => true,
                'message'        => $successMsg,
                'documentsGenerated' => ($pdfUrl !== null || $evalPdfUrl !== null),
                'evalId'         => $result['evalId'],
                'employeeId'     => $id,
                'decision'       => $result['decision'],
                'decisionType'   => $decisionType?->value,    // 'pass' | 'fail' | 'extend'
                'documentLabel'  => $decisionType?->documentLabel(),
                'overallTotal'   => $result['overallTotal'],
                'category'       => $result['category'],
                'integrityTotal' => $result['integrityTotal'],
                'ciTotal'        => $result['ciTotal'],
                'eeTotal'        => $result['eeTotal'],
                'twTotal'        => $result['twTotal'],
                'previewUrl'     => route('hr.probation.preview', [$id]) . '?eval_id=' . urlencode($result['evalId']),
                'pdfUrl'            => $pdfUrl,      // null for EXTEND
                'evalPdfUrl'        => $evalPdfUrl,  // null for EXTEND
                'skNumber'          => $result['skNumber'],
                // Extension duration label derived from manual start/end dates.
                'extensionDuration' => $result['extensionDuration'] ?? '',
            ]);
        }

        // Legacy form POST fallback
        if ($pdfUrl) {
            return redirect()->to($pdfUrl)->with('success', $successMsg);
        }
        return redirect()->route('hr.probation.index')->with('success', $successMsg);
    }

    // ==========================================================
    // PREVIEW — Show Performance Review HTML (same template as PDF)
    // ==========================================================

    /**
     * Preview Performance Review for a given employee + evalId.
     * Uses data from Google Sheet — NOT from query params.
     * Authorization: must have manage_probation permission.
     */
    public function previewPerformanceReview(Request $request, string $id): \Illuminate\View\View|\Illuminate\Http\Response
    {
        $employee = $this->employeeRepo->findById($id);
        if (!$employee) {
            abort(404, 'Data karyawan tidak ditemukan.');
        }

        $evalId  = $request->query('eval_id', '');
        $history = $this->probationService->getEvalHistory($id);
        $evalData = [];

        if (!empty($history)) {
            if ($evalId) {
                foreach ($history as $h) {
                    if (($h['evalId'] ?? '') === $evalId) {
                        $evalData = $h;
                        break;
                    }
                }
            }
            if (empty($evalData)) {
                $evalData = $history[0];
            }
        }

        if (empty($evalData)) {
            abort(404, 'Data evaluasi tidak ditemukan.');
        }

        // Resolve company
        $branchName = $employee->branchName ?? '';
        $extraData  = ['branch_name' => $branchName];

        // Use the same performance-review blade (shared with PDF)
        return response()->view('pdf.performance-review', [
            'employee'  => $employee,
            'evalData'  => $evalData,
            'extraData' => $extraData,
            'company'   => $this->resolveCompany($branchName),
        ]);
    }

    // ==========================================================
    // EVAL HISTORY — AJAX endpoint
    // ==========================================================

    public function evalHistory(Request $request, string $id): JsonResponse
    {
        $history = $this->probationService->getEvalHistory($id);
        return response()->json(['history' => $history]);
    }

    /** Build query params array for Performance Review PDF URL. */
    private function buildEvalPdfParams(string $evalId, array $evalData): array
    {
        $indicators = $evalData['indicators'] ?? [];
        $integrityTotal = $this->countChecked($indicators, ['integrity_1', 'integrity_2', 'integrity_3', 'integrity_4']);
        $ciTotal = $this->countChecked($indicators, ['ci_1', 'ci_2', 'ci_3', 'ci_4']);
        $eeTotal = $this->countChecked($indicators, ['ee_1', 'ee_2']);
        $twTotal = $this->countChecked($indicators, ['tw_1', 'tw_2', 'tw_3']);
        $overallTotal = $integrityTotal + $ciTotal + $eeTotal + $twTotal;
        $category = $this->calculateCategory($overallTotal);
        return [
            'eval_id'            => $evalId,
            'reviewer_name'      => $evalData['reviewer_name']      ?? '',
            'approval_dept'      => $evalData['approval_dept']      ?? '',
            'approval_dept_name' => $evalData['approval_dept_name'] ?? '',
            'approval_dept_date' => $evalData['approval_dept_date'] ?? '',
            'approval_hrbp'      => $evalData['approval_hrbp']      ?? '',
            'approval_hrbp_name' => $evalData['approval_hrbp_name'] ?? '',
            'approval_hrbp_date' => $evalData['approval_hrbp_date'] ?? '',
            'overall_total'      => (string) $overallTotal,
            'category'           => $category,
            'integrity_total'    => (string) $integrityTotal,
            'ci_total'           => (string) $ciTotal,
            'ee_total'           => (string) $eeTotal,
            'tw_total'           => (string) $twTotal,
            'ind_integrity_1'    => (string) ($indicators['integrity_1'] ?? '0'),
            'ind_integrity_2'    => (string) ($indicators['integrity_2'] ?? '0'),
            'ind_integrity_3'    => (string) ($indicators['integrity_3'] ?? '0'),
            'ind_integrity_4'    => (string) ($indicators['integrity_4'] ?? '0'),
            'ind_ci_1'           => (string) ($indicators['ci_1'] ?? '0'),
            'ind_ci_2'           => (string) ($indicators['ci_2'] ?? '0'),
            'ind_ci_3'           => (string) ($indicators['ci_3'] ?? '0'),
            'ind_ci_4'           => (string) ($indicators['ci_4'] ?? '0'),
            'ind_ee_1'           => (string) ($indicators['ee_1'] ?? '0'),
            'ind_ee_2'           => (string) ($indicators['ee_2'] ?? '0'),
            'ind_tw_1'           => (string) ($indicators['tw_1'] ?? '0'),
            'ind_tw_2'           => (string) ($indicators['tw_2'] ?? '0'),
            'ind_tw_3'           => (string) ($indicators['tw_3'] ?? '0'),
        ];
    }

    /** Resolve company profile — mirrors PdfGeneratorService::resolveCompany(). */
    private function resolveCompany(string $branchName = ''): array
    {
        $b = strtolower($branchName);
        if (str_contains($b, 'stein')) {
            return ['name' => 'PT STEIN PERKASA INTERNASIONAL', 'address' => 'Rukan Mangga Dua Square Blok H No. 18-21, Jl. Gunung Sahari Raya Nomor 1, Kel. Ancol, Kec. Pademangan, Kota Jakarta Utara, DKI Jakarta - 14430', 'city' => 'Jakarta', 'code' => 'SPI'];
        }
        if (str_contains($b, 'injeksi')) {
            return ['name' => 'PT PERKASA INJEKSI INDONESIA', 'address' => 'Jl. Gajah Tunggal, Kp. Gembor, RT.004/RW.001, Kel. Pasir Jaya, Kec. Jatiuwung, Kota Tangerang, Banten 15135', 'city' => 'Tangerang', 'code' => 'PII'];
        }
        if (str_contains($b, 'mitra') || str_contains($b, 'elektro')) {
            return ['name' => 'PT MITRA ELEKTRO PERKASA', 'address' => 'Rukan Mangga Dua Square Blok H No. 18-21, Jln. Gunung Sahari Raya Nomor 1, Kel. Ancol/Kec. Pademangan, Kota Jakarta Utara, DKI Jakarta', 'city' => 'Jakarta', 'code' => 'MEP'];
        }
        return ['name' => 'PT MAHAKARYA SUKSES INDONESIA', 'address' => 'Jl. Gajah Tunggal, Kp. Gembor, RT.004/RW.001, Kel. Pasir Jaya, Kec. Jatiuwung, Kota Tangerang, Banten 15135', 'city' => 'Tangerang', 'code' => 'MSI'];
    }

    private function countChecked(array $indicators, array $keys): int
    {
        $count = 0;
        foreach ($keys as $key) {
            if (($indicators[$key] ?? '') === '1') {
                $count++;
            }
        }
        return $count;
    }

    private function calculateCategory(int $total): string
    {
        return match (true) {
            $total >= 11 => 'Sangat Baik',
            $total >= 8  => 'Baik',
            $total >= 6  => 'Cukup',
            default      => 'Kurang',
        };
    }
}
