<?php

namespace App\Services;

use App\DTOs\EmployeeData;
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

    /**
     * Header sheet kandidat_probation — backward-compat dengan GAS PROBATION_HEADERS (Config.gs).
     * Kolom lama (Score Performance … Average Score) dipertahankan untuk data historis.
     * Kolom baru (Integrity Total … ind_tw_3) di-append setelah kolom lama.
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
        // OLD score columns — tetap ada untuk backward compat (data lama)
        'Score Performance', 'Score Discipline', 'Score Communication', 'Score Initiative', 'Score Teamwork',
        'Average Score',
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
     *     averageScore: string,
     *     isLulus: bool,
     *     isPutusKontrak: bool,
     *     isPerpanjang: bool,
     *     skNumber: string,
     *     message: string
     * }
     */
    public function evaluateProbation(string $employeeId, array $evalData, ?string $user = null): array
    {
        $employee = $this->employeeRepo->findById($employeeId);
        if (!$employee) {
            throw new RuntimeException("Karyawan probation {$employeeId} tidak ditemukan.");
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

        // ── 2. Decision classification (GAS-compatible) ──────────
        //
        // IMPORTANT: Order matters here. We must check isPutusKontrak BEFORE
        // isLulus because "Tidak Lulus" contains the substring "Lulus".
        // Using str_contains('Tidak Lulus', 'Lulus') would incorrectly return true.
        //
        // Priority order (1:1 GAS submitProbationEvaluation logic):
        //   1. isPutusKontrak — checked first to prevent "Tidak Lulus" false-positive
        //   2. isPerpanjang   — checked second
        //   3. isLulus        — only if neither of the above
        //
        // NEW decision values (Laravel modal):
        //   "Diangkat sebagai Karyawan Tetap" → isLulus
        //   "Tidak Lulus"                      → isPutusKontrak
        //   "Perpanjang Kontrak"               → isPerpanjang
        //
        // LEGACY decision values (GAS modal, backward compat):
        //   "Lulus → Karyawan Tetap"                          → isLulus
        //   "Tidak Lolos → Putus Kontrak (Paklaring)"         → isPutusKontrak
        //   "Tidak Lolos → Perpanjang Probation (Evaluasi Ulang)" → isPerpanjang
        $decision = (string) ($evalData['decision'] ?? '');

        // isPutusKontrak — MUST be evaluated BEFORE isLulus to avoid
        // str_contains('Tidak Lulus', 'Lulus') = true false-positive.
        $isPutusKontrak = ($decision === 'Tidak Lulus')
                       || ($decision === 'Tidak Lolos → Putus Kontrak (Paklaring)')
                       || str_contains($decision, 'Putus Kontrak')
                       || str_contains($decision, 'Paklaring');

        // isPerpanjang — evaluated before isLulus for the same safety reason
        $isPerpanjang = !$isPutusKontrak
                     && (
                         $decision === 'Perpanjang Kontrak'
                         || str_contains($decision, 'Perpanjang')
                         || str_contains($decision, 'Evaluasi Ulang')
                     );

        // isLulus — only if neither putus kontrak nor perpanjang
        $isLulus = !$isPutusKontrak
                && !$isPerpanjang
                && (
                    $decision === 'Diangkat sebagai Karyawan Tetap'
                    || $decision === 'Lulus → Karyawan Tetap'
                    || str_contains($decision, 'Diangkat')
                    || str_contains($decision, 'Tetap')
                    // NOTE: We do NOT check str_contains($decision, 'Lulus') here
                    // because 'Tidak Lulus' contains 'Lulus' — handled by isPutusKontrak above.
                );

        if (!$isLulus && !$isPutusKontrak && !$isPerpanjang) {
            throw new RuntimeException('Keputusan evaluasi tidak valid: "' . $decision . '". Pilih salah satu keputusan yang tersedia.');
        }

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
        $this->appendProbationEvalRow([
            'Probation ID'       => $this->generateProbationId($now),
            'Employee ID'        => $employeeId,
            'Recruitment ID'     => $evalData['recruitment_id'] ?? '',
            'Join Date'          => $employee->joinDate ?? '',
            'Status'             => $probStatus,
            'Eval ID'            => $evalId,
            'Eval Date'          => $nowStr,
            // OLD score columns — empty for new evaluations (backward compat)
            'Score Performance'  => '',
            'Score Discipline'   => '',
            'Score Communication'=> '',
            'Score Initiative'   => '',
            'Score Teamwork'     => '',
            'Average Score'      => '',
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
            // NEW: individual indicators (stored as 1/0)
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
        ]);

        // ── 5. Audit log ──────────────────────────────────────────
        $action = $isLulus ? 'Probation Lulus' : ($isPutusKontrak ? 'Probation Putus Kontrak' : 'Probation Diperpanjang');
        $this->auditRepo->log(
            recruitmentId: $employeeId,
            action: $action,
            field: 'Employment Status',
            oldValue: $employee->statusEmployee ?? 'Probation',
            newValue: "{$decision} (total {$overallTotal}/13, {$category})" . ($skNumber ? " — {$skNumber}" : ''),
            user: $user
        );

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
            'averageScore'   => '',   // empty for new evals; kept for backward compat
            'isLulus'        => $isLulus,
            'isPutusKontrak' => $isPutusKontrak,
            'isPerpanjang'   => $isPerpanjang,
            'skNumber'       => $skNumber,
            'message'        => $isLulus
                ? 'Karyawan lulus probation & diangkat menjadi karyawan tetap (PKWTT).'
                : ($isPutusKontrak
                    ? 'Kontrak diakhiri. Paklaring diterbitkan.'
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
     * Returns both new fields (overallTotal, category, competency totals)
     * AND old fields (averageScore) for backward compat with old data.
     */
    public function getEvalHistory(string $employeeId): array
    {
        $rows    = $this->sheets->getRowsAsAssoc($this->probationSheet());
        $history = [];

        foreach ($rows as $row) {
            if (($row['Employee ID'] ?? '') !== $employeeId) continue;
            if (empty($row['Eval Date'])) continue;

            $history[] = [
                // Legacy fields (old evaluations)
                'evalId'           => $row['Eval ID']           ?? '',
                'evalDate'         => $row['Eval Date']          ?? '',
                'scorePerformance' => $row['Score Performance']  ?? '',
                'scoreDiscipline'  => $row['Score Discipline']   ?? '',
                'scoreCommunication' => $row['Score Communication'] ?? '',
                'scoreInitiative'  => $row['Score Initiative']   ?? '',
                'scoreTeamwork'    => $row['Score Teamwork']     ?? '',
                'averageScore'     => $row['Average Score']      ?? '',
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
            ];
        }

        // Urut terbaru dulu
        usort($history, fn($a, $b) => strcmp($b['evalDate'], $a['evalDate']));
        return $history;
    }

    /**
     * Peta Employee ID → evaluasi terakhir (untuk kolom Last Score & status di tabel).
     * Returns Collection with key = Employee ID.
     * Includes new fields (overallTotal, category) AND old avgScore for backward compat.
     */
    public function latestEvalByEmployee(): Collection
    {
        $rows   = $this->sheets->getRowsAsAssoc($this->probationSheet());
        $latest = [];

        foreach ($rows as $row) {
            $empId = $row['Employee ID'] ?? '';
            if ($empId === '' || empty($row['Eval Date'])) continue;

            $date = $row['Eval Date'];
            if (!isset($latest[$empId]) || strcmp($date, $latest[$empId]['evalDate']) > 0) {
                $latest[$empId] = [
                    'avgScore'    => $row['Average Score']   ?? '',
                    'overallTotal' => $row['Overall Total']  ?? '',
                    'category'    => $row['Category']        ?? '',
                    'decision'    => $row['Decision']        ?? '',
                    'evalDate'    => $date,
                    'evaluator'   => $row['Evaluator']       ?? '',
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
     * Count how many indicators from $keys are true/1 in $indicators.
     */
    private function countChecked(array $indicators, array $keys): int
    {
        $count = 0;
        foreach ($keys as $key) {
            if (!empty($indicators[$key])) {
                $count++;
            }
        }
        return $count;
    }

    /**
     * Return 1 or 0 for a single indicator (for sheet storage).
     */
    private function indVal(array $indicators, string $key): int
    {
        return !empty($indicators[$key]) ? 1 : 0;
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
        $branch6 = strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $branchName ?: 'MITO'), 0, 6));
        $roman   = ['I','II','III','IV','V','VI','VII','VIII','IX','X','XI','XII'][$now->month - 1];
        $seq     = $this->nextCounter('SK', $now);
        return sprintf('%03d/%s/%s/%s/%d', $seq, $code, $branch6, $roman, $now->year);
    }

    private function generatePaklaringNumber(string $branchName, string $evalId, \Illuminate\Support\Carbon $now): string
    {
        $branch6 = strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $branchName ?: 'MITO'), 0, 6));
        return sprintf('SKK/HRD/%s/%d/%s', $branch6, $now->year, $evalId);
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
