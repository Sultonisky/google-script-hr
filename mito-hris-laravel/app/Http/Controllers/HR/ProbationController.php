<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Repositories\Contracts\EmployeeRepositoryInterface;
use App\Services\ProbationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
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

    /**
     * Show probation list — 1:1 with GAS getProbationList().
     * Filter Employee sheet by statusEmployee = "Probation" only.
     * Also enrich each employee with latest evaluation data from kandidat_probation.
     */
    public function index(Request $request): View
    {
        // 1) Semua karyawan berstatus Probation (tanpa filter search/dept) untuk:
        //    - stats, dropdown filter dept, dan data modal search (1:1 GAS getProbationList)
        $allProbations = $this->employeeRepo->getAll(['status' => 'Probation']);

        // 2) Enrich SEMUA probation dengan data evaluasi terakhir dari kandidat_probation
        $latestEvals   = $this->probationService->latestEvalByEmployee();
        $allProbations = $allProbations->map(function ($emp) use ($latestEvals) {
            if ($latestEvals->has($emp->employeeId)) {
                $eval = $latestEvals->get($emp->employeeId);
                // New fields (Performance Review 2026)
                $emp->lastOverallTotal = $eval['overallTotal'] ?? null;
                $emp->lastCategory     = $eval['category']     ?? null;
                // Legacy field — backward compat for old evaluations
                $emp->lastAvgScore     = $eval['avgScore']     ?? null;
                $emp->lastDecision     = $eval['decision']     ?? null;
                $emp->lastEvalDate     = $eval['evalDate']     ?? null;
                $emp->lastEvaluator    = $eval['evaluator']    ?? null;
            }
            return $emp;
        });

        // 3) Terapkan filter search + dept hanya ke tampilan tabel
        $probations = $allProbations;
        if ($request->filled('search')) {
            $search     = strtolower(trim($request->query('search')));
            $probations = $probations->filter(function ($e) use ($search) {
                return str_contains(strtolower($e->fullName   ?? ''), $search)
                    || str_contains(strtolower($e->employeeId ?? ''), $search)
                    || str_contains(strtolower($e->jobPosition ?? ''), $search)
                    || str_contains(strtolower($e->department  ?? ''), $search);
            })->values();
        }
        if ($request->filled('department')) {
            $dept       = strtolower(trim($request->query('department')));
            $probations = $probations->filter(function ($e) use ($dept) {
                return strtolower(trim($e->department ?? '')) === $dept;
            })->values();
        }

        // 4) Sort (1:1 GAS _probApplyFilter sort options)
        $sort       = $request->query('sort', 'newest');
        $probations = match ($sort) {
            'name_asc'  => $probations->sortBy('fullName')->values(),
            'name_desc' => $probations->sortByDesc('fullName')->values(),
            'oldest'    => $probations->sortBy('joinDate')->values(),
            default     => $probations->sortByDesc('joinDate')->values(), // newest
        };

        // 5) Departments list dari SEMUA probation
        $departments = $allProbations->pluck('department')->filter()->unique()->sort()->values();

        // 6) Stats — 1:1 GAS _updateProbStats()
        $evaluated = $allProbations->filter(fn($e) => !empty($e->lastEvalDate))->count();
        $passed    = $allProbations->filter(function ($e) {
            $k = $e->lastDecision ?? '';
            // IMPORTANT: check putus kontrak first to avoid 'Tidak Lulus' matching 'Lulus'
            $isTerm = ($k === 'Tidak Lulus')
                   || str_contains($k, 'Putus Kontrak')
                   || str_contains($k, 'Paklaring');
            $isExt  = !$isTerm && (str_contains($k, 'Perpanjang') || str_contains($k, 'Evaluasi Ulang'));
            return !$isTerm && !$isExt && (
                $k === 'Diangkat sebagai Karyawan Tetap'
                || $k === 'Lulus → Karyawan Tetap'
                || str_contains($k, 'Tetap')
                || str_contains($k, 'Diangkat')
                || str_contains($k, 'Pass')
            );
        })->count();
        $extended  = $allProbations->filter(function ($e) {
            $k = $e->lastDecision ?? '';
            return str_contains($k, 'Perpanjang')
                || str_contains($k, 'Evaluasi Ulang')
                || str_contains($k, 'Extend');
        })->count();

        $stats = [
            'onboarding' => $allProbations->count(),
            'evaluated'  => $evaluated,
            'passed'     => $passed,
            'extended'   => $extended,
        ];

        return view('hr.probation.index', compact('probations', 'allProbations', 'departments', 'stats'));
    }

    /**
     * Evaluate probation employee — Performance Review 2026.
     *
     * Supports two modes:
     *  - AJAX (X-Requested-With: XMLHttpRequest) → returns JSON with pdfUrl
     *  - Legacy form POST → returns RedirectResponse to PDF (backward compat)
     *
     * POST fields:
     *   employee_id, recruitment_id, decision,
     *   indicators[integrity_1..4], indicators[ci_1..4], indicators[ee_1..2], indicators[tw_1..3],
     *   extension_duration, extension_start, extension_end, notes
     */
    public function evaluate(Request $request, string $id): RedirectResponse|JsonResponse
    {
        $isAjax = $request->ajax() || $request->wantsJson();

        // ── Validation ────────────────────────────────────────────
        try {
            $request->validate([
                'decision' => [
                    'required',
                    'string',
                    function ($attribute, $value, $fail) {
                        $isPutusKontrak = ($value === 'Tidak Lulus')
                                       || ($value === 'Tidak Lolos → Putus Kontrak (Paklaring)')
                                       || str_contains($value, 'Putus Kontrak')
                                       || str_contains($value, 'Paklaring');
                        $isPerpanjang   = !$isPutusKontrak && (
                                           $value === 'Perpanjang Kontrak'
                                           || str_contains($value, 'Perpanjang')
                                           || str_contains($value, 'Evaluasi Ulang')
                                       );
                        $isLulus        = !$isPutusKontrak && !$isPerpanjang && (
                                           $value === 'Diangkat sebagai Karyawan Tetap'
                                           || $value === 'Lulus → Karyawan Tetap'
                                           || str_contains($value, 'Diangkat')
                                           || str_contains($value, 'Tetap')
                                       );
                        if (!$isPutusKontrak && !$isPerpanjang && !$isLulus) {
                            $fail('Keputusan evaluasi tidak valid. Pilih: Diangkat sebagai Karyawan Tetap, Tidak Lulus, atau Perpanjang Kontrak.');
                        }
                    },
                ],
                'indicators.integrity_1' => 'nullable|boolean',
                'indicators.integrity_2' => 'nullable|boolean',
                'indicators.integrity_3' => 'nullable|boolean',
                'indicators.integrity_4' => 'nullable|boolean',
                'indicators.ci_1'        => 'nullable|boolean',
                'indicators.ci_2'        => 'nullable|boolean',
                'indicators.ci_3'        => 'nullable|boolean',
                'indicators.ci_4'        => 'nullable|boolean',
                'indicators.ee_1'        => 'nullable|boolean',
                'indicators.ee_2'        => 'nullable|boolean',
                'indicators.tw_1'        => 'nullable|boolean',
                'indicators.tw_2'        => 'nullable|boolean',
                'indicators.tw_3'        => 'nullable|boolean',
                'extension_duration' => 'nullable|string|in:3 Bulan,6 Bulan,12 Bulan',
                'extension_start'    => 'nullable|date',
                'extension_end'      => 'nullable|date',
                'notes'              => 'nullable|string|max:1000',
                // Approval sign-off (Performance Review template — Section F)
                // Stored as metadata; no backend workflow change.
                'reviewer_name'  => 'nullable|string|max:200',
                'approval_dept'  => 'nullable|string|in:Setuju,Tidak',
                'approval_dept_name' => 'nullable|string|max:200',
                'approval_dept_date' => 'nullable|date',
                'approval_hrbp'  => 'nullable|string|in:Setuju,Tidak',
                'approval_hrbp_name' => 'nullable|string|max:200',
                'approval_hrbp_date' => 'nullable|date',
            ]);
        } catch (\Illuminate\Validation\ValidationException $ve) {
            if ($isAjax) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validasi gagal.',
                    'errors'  => $ve->errors(),
                ], 422);
            }
            throw $ve;
        }

        $evalData = [
            'decision'   => $request->input('decision'),
            'indicators' => [
                'integrity_1' => (bool) $request->input('indicators.integrity_1', 0),
                'integrity_2' => (bool) $request->input('indicators.integrity_2', 0),
                'integrity_3' => (bool) $request->input('indicators.integrity_3', 0),
                'integrity_4' => (bool) $request->input('indicators.integrity_4', 0),
                'ci_1'        => (bool) $request->input('indicators.ci_1', 0),
                'ci_2'        => (bool) $request->input('indicators.ci_2', 0),
                'ci_3'        => (bool) $request->input('indicators.ci_3', 0),
                'ci_4'        => (bool) $request->input('indicators.ci_4', 0),
                'ee_1'        => (bool) $request->input('indicators.ee_1', 0),
                'ee_2'        => (bool) $request->input('indicators.ee_2', 0),
                'tw_1'        => (bool) $request->input('indicators.tw_1', 0),
                'tw_2'        => (bool) $request->input('indicators.tw_2', 0),
                'tw_3'        => (bool) $request->input('indicators.tw_3', 0),
            ],
            'extension_duration' => $request->input('extension_duration', ''),
            'extension_start'    => $request->input('extension_start',    ''),
            'extension_end'      => $request->input('extension_end',      ''),
            'notes'              => $request->input('notes',              ''),
            'recruitment_id'     => $request->input('recruitment_id',     ''),
            // Approval sign-off metadata (Performance Review template Section F)
            'reviewer_name'      => $request->input('reviewer_name',      ''),
            'approval_dept'      => $request->input('approval_dept',      ''),
            'approval_dept_name' => $request->input('approval_dept_name', ''),
            'approval_dept_date' => $request->input('approval_dept_date', ''),
            'approval_hrbp'      => $request->input('approval_hrbp',      ''),
            'approval_hrbp_name' => $request->input('approval_hrbp_name', ''),
            'approval_hrbp_date' => $request->input('approval_hrbp_date', ''),
        ];

        $user = auth()->user()?->name ?? 'HR Team';

        try {
            $result = $this->probationService->evaluateProbation($id, $evalData, $user);
        } catch (RuntimeException $e) {
            if ($isAjax) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }

        // ── CRITICAL: isPutusKontrak checked BEFORE isLulus (substring collision guard)
        // Build PDF URL — used by both AJAX (window.open in JS) and legacy redirect
        $pdfUrl = null;
        $successMsg = '';

        if ($result['isPutusKontrak']) {
            $pdfUrl = route('hr.export.paklaring', [
                $id,
                'letter_number'     => $result['skNumber'],
                'evalId'            => $result['evalId'],
                'last_working_date' => now()->timezone('Asia/Jakarta')->format('Y-m-d'),
            ]);
            $successMsg = "Kontrak karyawan {$id} diakhiri. Paklaring: {$result['skNumber']}";
        } elseif ($result['isLulus']) {
            $pdfUrl = route('hr.export.sk-pengangkatan', [
                $id,
                'sk_number' => $result['skNumber'],
            ]);
            $successMsg = "Karyawan {$id} lulus probation & diangkat menjadi karyawan tetap (PKWTT). SK: {$result['skNumber']}";
        } else {
            $successMsg = "Masa probation karyawan {$id} diperpanjang ({$evalData['extension_duration']}).";
        }

        // Performance Review eval PDF — always generated alongside consequential PDF
        $evalPdfUrl = route('hr.export.performance-review', [
            $id,
            'eval_id'            => $result['evalId'],
            'reviewer_name'      => $evalData['reviewer_name']      ?? '',
            'approval_dept'      => $evalData['approval_dept']      ?? '',
            'approval_dept_name' => $evalData['approval_dept_name'] ?? '',
            'approval_dept_date' => $evalData['approval_dept_date'] ?? '',
            'approval_hrbp'      => $evalData['approval_hrbp']      ?? '',
            'approval_hrbp_name' => $evalData['approval_hrbp_name'] ?? '',
            'approval_hrbp_date' => $evalData['approval_hrbp_date'] ?? '',
        ]);

        // ── AJAX mode: return JSON (modal stays open, JS handles PDF + close) ──
        if ($isAjax) {
            return response()->json([
                'success'        => true,
                'message'        => $successMsg,
                'evalId'         => $result['evalId'],
                'decision'       => $result['decision'],
                'overallTotal'   => $result['overallTotal'],
                'category'       => $result['category'],
                'isLulus'        => $result['isLulus'],
                'isPutusKontrak' => $result['isPutusKontrak'],
                'isPerpanjang'   => $result['isPerpanjang'],
                'pdfUrl'         => $pdfUrl,
                'evalPdfUrl'     => $evalPdfUrl,
                'skNumber'       => $result['skNumber'],
            ]);
        }

        // ── Legacy form POST: redirect (kept for non-JS fallback) ──────────────
        if ($result['isPutusKontrak']) {
            return redirect()->to($pdfUrl)->with('success', $successMsg);
        }
        if ($result['isLulus']) {
            return redirect()->to($pdfUrl)->with('success', $successMsg);
        }
        return redirect()->route('hr.probation.index')->with('success', $successMsg);
    }

    /**
     * Return eval history JSON for a given employee (1:1 GAS getProbationEvalHistory).
     * Called via AJAX from the Eval History modal in probation index.
     */
    public function evalHistory(Request $request, string $id): \Illuminate\Http\JsonResponse
    {
        $history = $this->probationService->getEvalHistory($id);
        return response()->json(['history' => $history]);
    }
}
