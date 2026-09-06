<?php

namespace App\Services;

use App\DTOs\EmployeeData;
use App\Enums\ProbationDecisionType;
use App\Repositories\Contracts\AuditLogRepositoryInterface;
use App\Repositories\Contracts\EmployeeRepositoryInterface;
use App\Services\Google\GoogleSheetsService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use RuntimeException;

class ProbationService
{
    protected EmployeeRepositoryInterface $employeeRepo;
    protected AuditLogRepositoryInterface $auditRepo;
    protected GoogleSheetsService $sheets;

    /** @var array<int, array<string,string>>|null Request-scoped memo of kandidat_probation rows. */
    private ?array $probationRowsCache = null;

    /**
     * Header sheet kandidat_probation — backward-compat dengan GAS PROBATION_HEADERS (Config.gs).
     * LEGACY kolom skor lama (Score Performance … Average Score) sudah DIHAPUS dari definisi
     * ini — tidak lagi ditulis/dibaca oleh aplikasi (lihat audit cleanup kandidat_probation).
     * Data historis pada sheet tetap utuh; append row selalu dipetakan by header name
     * sehingga posisi fisik kolom lama tidak mengganggu.
     */
    private const PROBATION_HEADERS = [
        // -- Identitas
        'Probation ID', 'Employee ID', 'Recruitment ID',
        // -- Kontrak Probation
        'Contract Number', 'Contract Duration', 'Contract Start', 'Contract End', 'Join Date',
        // -- Status & Onboarding
        'Status', 'Onboarding Date', 'Onboarding By',
        // -- Evaluasi
        'Eval ID', 'Eval Date',
        // Decision
        'Decision',
        // -- Perpanjangan
        'Extension Duration', 'New Contract Start', 'New Contract End',
        // -- Catatan & SK
        'Evaluator Notes', 'Evaluator', 'SK Status', 'Notes',
        // -- Audit
        'Created At', 'Updated At',
        // ── NEW columns (Performance Review 2026) ────────────────
        // Competency totals
        'Integrity Total', 'CI Total', 'EE Total', 'Teamwork Total',
        'Overall Total', 'Category',
        // Individual indicators (13)
        'ind_integrity_1', 'ind_integrity_2', 'ind_integrity_3', 'ind_integrity_4',
        'ind_ci_1', 'ind_ci_2', 'ind_ci_3', 'ind_ci_4',
        'ind_ee_1', 'ind_ee_2',
        'ind_tw_1', 'ind_tw_2', 'ind_tw_3',
        // Approval sign-off (Performance Review Section F)
        'Reviewer Name', 'Approval Dept', 'Approval Dept Name',
        'Approval Dept Date', 'Approval HRBP', 'Approval HRBP Name',
        'Approval HRBP Date',
    ];

    /** 13 indicator keys in canonical order (Performance Review 2026). */
    private const INDICATOR_KEYS = [
        'integrity_1', 'integrity_2', 'integrity_3', 'integrity_4',
        'ci_1', 'ci_2', 'ci_3', 'ci_4',
        'ee_1', 'ee_2',
        'tw_1', 'tw_2', 'tw_3',
    ];

    public function __construct(
        EmployeeRepositoryInterface $employeeRepo,
        AuditLogRepositoryInterface $auditRepo,
        GoogleSheetsService $sheets
    ) {
        $this->employeeRepo = $employeeRepo;
        $this->auditRepo    = $auditRepo;
        $this->sheets       = $sheets;
    }

    private function probationSheet(): string
    {
        return config('google.sheets.candidates_probation', 'kandidat_probation');
    }

    /**
     * Request-scoped memoization of the kandidat_probation sheet rows.
     *
     * Production issue: EmployeeController/ProbationController loops called
     * getEvalHistory()/getAllProbationRecords()/latestEvalByEmployee() per
     * employee. Each call previously did clearCache() + getRowsAsAssoc(),
     * issuing one full-sheet Google Sheets read PER employee -> HTTP 429
     * (Read requests per minute per user = 60) -> nginx 504.
     *
     * This memo loads the sheet ONCE per request; all read methods reuse it.
     * Persistent GoogleSheetsService cache semantics are unchanged -- writes
     * still bump the version stamp via invalidateProbationRowsCache().
     */
    private function getProbationRows(): array
    {
        if ($this->probationRowsCache !== null) {
            return $this->probationRowsCache;
        }

        $this->probationRowsCache = $this->sheets->getRowsAsAssoc($this->probationSheet());

        return $this->probationRowsCache;
    }

    /**
     * Invalidate BOTH the request-local memo AND the persistent
     * GoogleSheetsService cache for kandidat_probation.
     *
     * Call ONLY after an actual write/update/delete to the probation sheet
     * (appendProbationEvalRow). Never call on the read path.
     */
    private function invalidateProbationRowsCache(): void
    {
        $this->probationRowsCache = null;
        $this->sheets->clearCache($this->probationSheet());
    }

    // ==========================================================
    // PUBLIC: Evaluate Probation (Performance Review 2026)
    // ==========================================================

    /**
     * Evaluasi Probation — NEW: 13 behavioral indicators (Performance Review 2026).
     *
     * Decision strings (GAS-compatible):
     *  - "Diangkat sebagai Karyawan Tetap"           → isLulus path → SK Pengangkatan
     *  - "Tidak Lulus"                               → isPutusKontrak path → Paklaring
     *  - "Perpanjang Kontrak"                        → isPerpanjang path → no PDF
     *
     * OLD decision strings still detected for backward compat:
     *  - "Lulus → Karyawan Tetap"                   → isLulus
     *  - "Tidak Lolos → Putus Kontrak (Paklaring)"  → isPutusKontrak
     *  - "Tidak Lolos → Perpanjang Probation …"     → isPerpanjang
     *
     * NOTE: avg >= 7.0 validation REMOVED — not in GAS business logic.
     *
     * @param  string       $employeeId
     * @param  array        $evalData  {
     *     decision: string,
     *     indicators: array<string, bool>,   // 13 keys
     *     extension_duration: string,
     *     extension_start: string,
     *     extension_end: string,
     *     notes: string,
     *     recruitment_id: string,
     * }
     * @param  string|null  $user
     * @return array{
     *     success: bool,
     *     evalId: string,
     *     employeeId: string,
     *     decision: string,
     *     overallTotal: int,
     *     category: string,
     *     integrityTotal: int,
     *     ciTotal: int,
     *     eeTotal: int,
     *     twTotal: int,
     *     isLulus: bool,
     *     isPutusKontrak: bool,
     *     isPerpanjang: bool,
     *     skNumber: string,
     *     hasPdf: bool,
     *     message: string
     * }
     */
    public function evaluateProbation(string $employeeId, array $evalData, ?string $user = null): array
    {
        $employee = $this->employeeRepo->findById($employeeId);
        if (!$employee) {
            throw new RuntimeException("Karyawan probation {$employeeId} tidak ditemukan.");
        }

        // Normalize Employee ID — strip leading apostrophe that GAS sometimes prepends
        $employeeId = ltrim(trim($employeeId), "'");

        // Re-read the latest evaluation from the source of truth before any mutation.
        // PASS/FAIL are terminal; after EXTEND only PASS/FAIL are valid next decisions.
        $history = $this->getEvalHistory($employeeId);
        $latest = $history[0] ?? null;
        if ($latest) {
            $latestType = ProbationDecisionType::fromDecisionString((string) ($latest['decision'] ?? ''));
            if ($latestType?->isPass() || $latestType?->isFail()) {
                throw new RuntimeException('Kandidat sudah menyelesaikan evaluasi probation dan tidak dapat dievaluasi kembali.');
            }
        }

        $user   = $user ?: 'HR Administrator';
        $now    = now()->timezone('Asia/Jakarta');
        $nowStr = $now->format('Y-m-d H:i:s');
        $evalId = $this->generateEvalId($now);

        // ── 1. Server-side indicator calculation ─────────────────
        $indicators = $evalData['indicators'] ?? [];

        $integrityTotal = $this->countChecked($indicators, ['integrity_1', 'integrity_2', 'integrity_3', 'integrity_4']);
        $ciTotal        = $this->countChecked($indicators, ['ci_1', 'ci_2', 'ci_3', 'ci_4']);
        $eeTotal        = $this->countChecked($indicators, ['ee_1', 'ee_2']);
        $twTotal        = $this->countChecked($indicators, ['tw_1', 'tw_2', 'tw_3']);
        $overallTotal   = $integrityTotal + $ciTotal + $eeTotal + $twTotal;
        $category       = $this->calculateCategory($overallTotal);

        // ── 2. Decision classification using Enum ────────────────
        $decision = (string) ($evalData['decision'] ?? '');
        $decisionType = ProbationDecisionType::fromDecisionString($decision);
        if (!$decisionType) {
            throw new RuntimeException('Keputusan evaluasi tidak valid: "' . $decision . '". Pilih salah satu keputusan yang tersedia.');
        }
        if ($latest && $decisionType->isExtend()) {
            throw new RuntimeException('Keputusan Extend tidak tersedia pada evaluasi ulang setelah perpanjangan. Pilih Lulus atau Tidak Lulus.');
        }
        $isLulus = $decisionType->isPass();
        $isPutusKontrak = $decisionType->isFail();
        $isPerpanjang = $decisionType->isExtend();

        $extDuration = $evalData['extension_duration'] ?? '';
        $extStart    = $evalData['extension_start']    ?? '';
        $extEnd      = $evalData['extension_end']      ?? '';
        $notes       = $evalData['notes']              ?? '';

        if ($isPerpanjang && (empty($extDuration) || empty($extStart))) {
            throw new RuntimeException('Perpanjangan probation memerlukan durasi dan tanggal mulai kontrak baru.');
        }

        // Validate extension duration — only 3 / 6 / 12 Bulan (per template)
        if ($isPerpanjang && !in_array($extDuration, ['3 Bulan', '6 Bulan', '12 Bulan'], true)) {
            throw new RuntimeException("Durasi perpanjangan tidak valid. Pilih 3, 6, atau 12 Bulan.");
        }

        $branchName = $employee->branchName ?? '';
        $skNumber   = '';

        // ── 3. Branch per keputusan → update Employee sheet ──────
        if ($isLulus) {
            $skNumber = $this->generateSuratNumber('HRD-PK', $branchName, $now);
            $this->employeeRepo->update($employeeId, [
                'Status Employee' => 'PKWTT',
                'Nomor SK'        => $skNumber,
                'HR Notes'        => $this->appendNote(
                    $employee->hrNotes,
                    "[{$nowStr}] Lulus Probation (total {$overallTotal}/13, {$category}) — SK {$skNumber} by {$user}"
                ),
                'Updated At'      => $nowStr,
            ]);
            $probStatus = 'Completed - Passed';
            $skStatus   = 'SK Diterbitkan';

        } elseif ($isPutusKontrak) {
            $skNumber = $this->generatePaklaringNumber($branchName, $evalId, $now);
            $this->employeeRepo->update($employeeId, [
                'Status Employee'     => 'Terminated',
                'Resign Date'         => $now->format('Y-m-d'),
                'Offboarding Type'    => 'End of Probation',
                'Offboarding Reason'  => 'Tidak Lolos Evaluasi Probation',
                'Nomor SK'            => $skNumber,
                'HR Notes'            => $this->appendNote(
                    $employee->hrNotes,
                    "[{$nowStr}] Tidak Lulus Probation (total {$overallTotal}/13, {$category}) — Paklaring {$skNumber} by {$user}"
                ),
                'Updated At'          => $nowStr,
            ]);
            $probStatus = 'Terminated';
            $skStatus   = 'Paklaring Diterbitkan';

        } else {
            // Perpanjang (1:1 GAS isPerpanjang branch)
            $updates = [
                'End Date (Contract)'   => $extEnd,
                'HR Notes'              => $this->appendNote(
                    $employee->hrNotes,
                    "[{$nowStr}] Probation diperpanjang {$extDuration} ({$extStart} s/d {$extEnd}) — total {$overallTotal}/13, {$category} by {$user}"
                ),
                'Updated At'            => $nowStr,
            ];
            if (!empty($extStart)) {
                $updates['Start Date (Contract)'] = $extStart;
            }
            if (!empty($extDuration)) {
                $updates['Contract Duration'] = $extDuration;
            }
            $this->employeeRepo->update($employeeId, $updates);
            $probStatus = 'Extended';
            $skStatus   = 'Diperpanjang';
        }

        // ── 4. Tulis baris evaluasi ke kandidat_probation ─────────
        //
        // CATATAN: TIDAK ada PDF yang di-generate di sini.
        //   - PASS/FAIL → PDF (SK Pengangkatan / Paklaring + Performance Review)
        //     dibuat on-demand dan langsung di-download oleh controller/frontend
        //     lewat route hr.export.* (konvensi fungsi PDF lainnya).
        //   - EXTEND → tidak ada dokumen sama sekali; hanya data evaluasi +
        //     durasi perpanjangan yang disimpan ke sheet.
        $this->appendProbationEvalRow([
            'Probation ID'       => $this->generateProbationId($now),
            'Employee ID'        => $employeeId,
            'Recruitment ID'     => $evalData['recruitment_id'] ?? '',
            'Join Date'          => $employee->joinDate ?? '',
            'Status'             => $probStatus,
            'Eval ID'            => $evalId,
            'Eval Date'          => $nowStr,
            // Decision
            'Decision'           => $decision,
            // Extension
            'Extension Duration' => $isPerpanjang ? $extDuration : '',
            'New Contract Start' => $isPerpanjang ? $extStart    : '',
            'New Contract End'   => $isPerpanjang ? $extEnd      : '',
            // Notes & SK
            'Evaluator Notes'    => $notes,
            'Evaluator'          => $user,
            'SK Status'          => $skStatus,
            // Audit
            'Created At'         => $nowStr,
            'Updated At'         => $nowStr,
            // NEW: competency totals
            'Integrity Total'    => $integrityTotal,
            'CI Total'           => $ciTotal,
            'EE Total'           => $eeTotal,
            'Teamwork Total'     => $twTotal,
            'Overall Total'      => $overallTotal,
            'Category'           => $category,
            // NEW: individual indicators — stored as "1" (✓) or "0" (X) string
            // This allows eval history to reconstruct exact ✓/X display per indicator.
            'ind_integrity_1'    => $this->indVal($indicators, 'integrity_1'),
            'ind_integrity_2'    => $this->indVal($indicators, 'integrity_2'),
            'ind_integrity_3'    => $this->indVal($indicators, 'integrity_3'),
            'ind_integrity_4'    => $this->indVal($indicators, 'integrity_4'),
            'ind_ci_1'           => $this->indVal($indicators, 'ci_1'),
            'ind_ci_2'           => $this->indVal($indicators, 'ci_2'),
            'ind_ci_3'           => $this->indVal($indicators, 'ci_3'),
            'ind_ci_4'           => $this->indVal($indicators, 'ci_4'),
            'ind_ee_1'           => $this->indVal($indicators, 'ee_1'),
            'ind_ee_2'           => $this->indVal($indicators, 'ee_2'),
            'ind_tw_1'           => $this->indVal($indicators, 'tw_1'),
            'ind_tw_2'           => $this->indVal($indicators, 'tw_2'),
            'ind_tw_3'           => $this->indVal($indicators, 'tw_3'),
            // Approval fields
            'Reviewer Name'      => $evalData['reviewer_name'] ?? '',
            'Approval Dept'      => $evalData['approval_dept'] ?? '',
            'Approval Dept Name' => $evalData['approval_dept_name'] ?? '',
            'Approval Dept Date' => $evalData['approval_dept_date'] ?? '',
            'Approval HRBP'      => $evalData['approval_hrbp'] ?? '',
            'Approval HRBP Name' => $evalData['approval_hrbp_name'] ?? '',
            'Approval HRBP Date' => $evalData['approval_hrbp_date'] ?? '',
        ]);

        // ── 5. Audit log ──────────────────────────────────────────
        $action = $isLulus ? 'Probation Lulus' : ($isPutusKontrak ? 'Probation Putus Kontrak' : 'Probation Diperpanjang');
        $this->auditRepo->log(
            entityType: 'Probation',
            entityId: $employeeId,
            action: $action,
            field: 'Employment Status',
            oldValue: $employee->statusEmployee ?? 'Probation',
            newValue: "{$decision} (total {$overallTotal}/13, {$category})" . ($skNumber ? " — {$skNumber}" : ''),
            user: $user,
            source: 'Dashboard'
        );

        $decisionType = ProbationDecisionType::fromDecisionString($decision);

        return [
            'success'        => true,
            'evalId'         => $evalId,
            'employeeId'     => $employeeId,
            'decision'       => $decision,
            'overallTotal'   => $overallTotal,
            'category'       => $category,
            'integrityTotal' => $integrityTotal,
            'ciTotal'        => $ciTotal,
            'eeTotal'        => $eeTotal,
            'twTotal'        => $twTotal,
            'isLulus'        => $isLulus,
            'isPutusKontrak' => $isPutusKontrak,
            'isPerpanjang'   => $isPerpanjang,
            'skNumber'       => $skNumber,
            // hasPdf = false untuk EXTEND → controller tidak membangun URL PDF
            'hasPdf'         => $decisionType?->hasPdf() ?? false,
            'message'        => $isLulus
                ? "Karyawan lulus probation & diangkat menjadi karyawan tetap (PKWTT). SK: {$skNumber}"
                : ($isPutusKontrak
                    ? "Kontrak diakhiri. Paklaring diterbitkan. No: {$skNumber}"
                    : "Masa probation diperpanjang ({$extDuration})."),
        ];
    }

    // ==========================================================
    // PUBLIC: Eval History
    // ==========================================================

    /**
     * Riwayat evaluasi untuk satu karyawan dari kandidat_probation,
     * urut terbaru dulu. (1:1 GAS getProbationEvalHistory)
     *
     * Returns new fields (overallTotal, category, competency totals).
     */
    public function getEvalHistory(string $employeeId): array
    {
        $rows    = $this->getProbationRows();
        $history = [];

        // Normalize query ID: trim, strip leading apostrophe, extract numeric part
        $queryEmpId = ltrim(trim($employeeId), "'");
        $queryNumeric = preg_replace('/[^0-9]/', '', $queryEmpId);

        foreach ($rows as $row) {
            // Normalize row Employee ID similarly
            $rowEmpId = ltrim(trim($row['Employee ID'] ?? ''), "'");
            $rowNumeric = preg_replace('/[^0-9]/', '', $rowEmpId);

            // Match if exact or numeric-only matches
            if ($rowEmpId !== $queryEmpId && $rowNumeric !== $queryNumeric) {
                continue;
            }
            if (empty($row['Eval Date'])) continue;

            $history[] = [
                'evalId'           => $row['Eval ID']           ?? '',
                'evalDate'         => $row['Eval Date']          ?? '',
                // New fields (Performance Review 2026)
                'overallTotal'     => $row['Overall Total']      ?? '',
                'category'         => $row['Category']           ?? '',
                'integrityTotal'   => $row['Integrity Total']    ?? '',
                'ciTotal'          => $row['CI Total']           ?? '',
                'eeTotal'          => $row['EE Total']           ?? '',
                'twTotal'          => $row['Teamwork Total']     ?? '',
                // Individual indicators
                'ind_integrity_1'  => $row['ind_integrity_1']    ?? '',
                'ind_integrity_2'  => $row['ind_integrity_2']    ?? '',
                'ind_integrity_3'  => $row['ind_integrity_3']    ?? '',
                'ind_integrity_4'  => $row['ind_integrity_4']    ?? '',
                'ind_ci_1'         => $row['ind_ci_1']           ?? '',
                'ind_ci_2'         => $row['ind_ci_2']           ?? '',
                'ind_ci_3'         => $row['ind_ci_3']           ?? '',
                'ind_ci_4'         => $row['ind_ci_4']           ?? '',
                'ind_ee_1'         => $row['ind_ee_1']           ?? '',
                'ind_ee_2'         => $row['ind_ee_2']           ?? '',
                'ind_tw_1'         => $row['ind_tw_1']           ?? '',
                'ind_tw_2'         => $row['ind_tw_2']           ?? '',
                'ind_tw_3'         => $row['ind_tw_3']           ?? '',
                // Decision & extension
                'decision'         => $row['Decision']           ?? '',
                'extensionDuration' => $row['Extension Duration'] ?? '',
                'newContractStart' => $row['New Contract Start']  ?? '',
                'newContractEnd'   => $row['New Contract End']    ?? '',
                'evaluatorNotes'   => $row['Evaluator Notes']    ?? '',
                'evaluator'        => $row['Evaluator']          ?? '',
                'skStatus'         => $row['SK Status']          ?? '',
                // Approval fields
                'reviewer_name'      => $row['Reviewer Name']      ?? '',
                'approval_dept'      => $row['Approval Dept']      ?? '',
                'approval_dept_name' => $row['Approval Dept Name'] ?? '',
                'approval_dept_date' => $row['Approval Dept Date'] ?? '',
                'approval_hrbp'      => $row['Approval HRBP']      ?? '',
                'approval_hrbp_name' => $row['Approval HRBP Name'] ?? '',
                'approval_hrbp_date' => $row['Approval HRBP Date'] ?? '',
            ];
        }

        // Urut terbaru dulu
        usort($history, fn($a, $b) => strcmp($b['evalDate'], $a['evalDate']));
        return $history;
    }

    /**
     * Peta Employee ID → evaluasi terakhir (untuk kolom Last Score & status di tabel).
     * Returns Collection with key = Employee ID.
     */
    public function getAllProbationRecords(): Collection
    {
        $rows = $this->getProbationRows();
        // Group by Employee ID, take latest by Eval Date (or Created At if no eval)
        $grouped = [];
        foreach ($rows as $row) {
            $empId = ltrim(trim($row['Employee ID'] ?? ''), "'");
            if ($empId === '') continue;
            $date = $row['Eval Date'] ?? $row['Created At'] ?? '';
            if (!isset($grouped[$empId]) || $date > $grouped[$empId]['_date']) {
                $grouped[$empId] = array_merge($row, ['_date' => $date]);
            }
        }
        return collect(array_values($grouped));
    }

    public function canEvaluate(string $employeeId): bool
    {
        $history = $this->getEvalHistory($employeeId);
        $count = count($history);
        if ($count === 0) {
            return true;
        }
        $latest = $history[0]; // newest first
        $decision = $latest['decision'] ?? '';
        $type = ProbationDecisionType::fromDecisionString($decision);
        if ($type === null) {
            return true; // no valid decision yet
        }
        if ($type->isPass() || $type->isFail()) {
            return false;
        }
        if ($type->isExtend()) {
            // An extension keeps the employee eligible for another evaluation.
            return true;
        }
        return true;
    }

    public function latestEvalByEmployee(): Collection
    {
        $rows   = $this->getProbationRows();
        $latest = [];

        foreach ($rows as $row) {
            $empId = ltrim(trim($row['Employee ID'] ?? ''), "'");
            if ($empId === '' || empty($row['Eval Date'])) continue;

            $date = $row['Eval Date'];
            if (!isset($latest[$empId]) || strcmp($date, $latest[$empId]['evalDate']) > 0) {
                $latest[$empId] = [
                    'evalId'       => $row['Eval ID']         ?? '',
                    'overallTotal' => $row['Overall Total']  ?? '',
                    'category'     => $row['Category']        ?? '',
                    'decision'     => $row['Decision']        ?? '',
                    'evalDate'     => $date,
                    'evaluator'    => $row['Evaluator']       ?? '',
                ];
            }
        }

        return collect($latest);
    }


    // ==========================================================
    // Private helpers
    // ==========================================================

    /**
     * Calculate category from overall total (server-side only).
     * 11–13: Sangat Baik | 8–10: Baik | 6–7: Cukup | 3–5: Kurang
     */
    private function calculateCategory(int $total): string
    {
        return match (true) {
            $total >= 11 => 'Sangat Baik',
            $total >= 8  => 'Baik',
            $total >= 6  => 'Cukup',
            default      => 'Kurang',
        };
    }

    /**
     * Count how many indicators from $keys equal "1" (✓ terpenuhi).
     * 3-state: "1" = ✓ terpenuhi, "0" = X tidak terpenuhi, "" = belum dinilai.
     * Only "1" counts toward the score. "0" and "" are both not counted.
     */
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

    /**
     * Return stored value for a single indicator in the sheet.
     * "1" = ✓ terpenuhi, "0" = X tidak terpenuhi.
     * Stores the explicit string so eval history can reconstruct ✓/X display.
     */
    private function indVal(array $indicators, string $key): string
    {
        $v = $indicators[$key] ?? '';
        // Only accept "1" or "0" — anything else (incl. bool) normalises to "0"
        return $v === '1' ? '1' : '0';
    }

    private function appendProbationEvalRow(array $data): void
    {
        $sheetName = $this->probationSheet();

        // Ensure all headers exist — adds missing new columns to the sheet
        // if the sheet was created by GAS with only 29 columns.
        // ensureSheetHeaders is idempotent: only writes row 1 if it is empty.
        // For an existing GAS sheet we use ensureExtraColumns_ logic:
        // read current headers, append any missing ones at the end.
        $headerRow = $this->sheets->getRange($sheetName, '1:1', false)[0] ?? [];
        if (empty($headerRow) || empty(array_filter($headerRow))) {
            // Sheet is empty — write full header set
            $this->sheets->ensureSheetHeaders($sheetName, self::PROBATION_HEADERS);
            $headers = self::PROBATION_HEADERS;
        } else {
            $existingHeaders = array_map('trim', $headerRow);
            $missing = array_diff(self::PROBATION_HEADERS, $existingHeaders);
            if (!empty($missing)) {
                // Append missing headers immediately after the last existing column
                $nextCol = count(array_filter($existingHeaders, fn($h) => $h !== '')) + 1;
                $colLetter = $this->colIndexToLetter($nextCol);
                $this->sheets->updateRange(
                    $sheetName,
                    $colLetter . '1',
                    [array_values($missing)]
                );
                // Re-read headers after update
                $headerRow = $this->sheets->getRange($sheetName, '1:1', false)[0] ?? [];
            }
            $headers = array_map('trim', $headerRow);
        }

        $row = [];
        foreach ($headers as $h) {
            $row[] = $data[$h] ?? '';
        }
        $this->sheets->appendRow($sheetName, $row);
        $this->invalidateProbationRowsCache();
    }

    /**
     * Convert 1-based column index to letter(s): 1→A, 26→Z, 27→AA, etc.
     */
    private function colIndexToLetter(int $index): string
    {
        $letters = '';
        while ($index > 0) {
            $index--;
            $letters = chr(65 + ($index % 26)) . $letters;
            $index   = (int)($index / 26);
        }
        return $letters;
    }

    private function getEntityCode(string $branchName): string
    {
        $b = strtolower($branchName);
        if (str_contains($b, 'stein')) {
            return 'SPI';
        }
        if (str_contains($b, 'injeksi')) {
            return 'PII';
        }
        if (str_contains($b, 'mitra') || str_contains($b, 'elektro')) {
            return 'MEP';
        }
        return 'MSI';
    }

    private function appendNote(?string $existing, string $line): string
    {
        return ($existing ? $existing . "\n" : '') . $line;
    }

    private function generateEvalId(\Illuminate\Support\Carbon $now): string
    {
        return 'EVAL-' . $now->format('Ymd') . '-' . str_pad((string) $this->nextCounter('EVAL', $now), 4, '0', STR_PAD_LEFT);
    }

    private function generateProbationId(\Illuminate\Support\Carbon $now): string
    {
        return 'PROB-' . $now->format('Ymd') . '-' . str_pad((string) $this->nextCounter('PROB', $now), 4, '0', STR_PAD_LEFT);
    }

    private function generateSuratNumber(string $code, string $branchName, \Illuminate\Support\Carbon $now): string
    {
        $entity = $this->getEntityCode($branchName);
        $roman  = ['I','II','III','IV','V','VI','VII','VIII','IX','X','XI','XII'][$now->month - 1];
        $seq    = $this->nextCounter('SK', $now);
        return sprintf('%03d/%s/%s/%s/%d', $seq, $code, $entity, $roman, $now->year);
    }

    private function generatePaklaringNumber(string $branchName, string $evalId, \Illuminate\Support\Carbon $now): string
    {
        $entity = $this->getEntityCode($branchName);
        return sprintf('SKK/HRD/%s/%d/%s', $entity, $now->year, $evalId);
    }

    private function nextCounter(string $prefix, \Illuminate\Support\Carbon $now): int
    {
        $key  = "{$prefix}_COUNTER_" . $now->format('Ymd');
        $lock = Cache::lock("lock_{$key}", 10);
        try {
            $lock->block(10);
            $seq = (int) Cache::get($key, 0) + 1;
            Cache::put($key, $seq, $now->endOfDay());
            return $seq;
        } finally {
            $lock->release();
        }
    }
}
