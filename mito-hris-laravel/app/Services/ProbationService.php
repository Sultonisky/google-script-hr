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

    /** Header sheet kandidat_probation — 1:1 dengan GAS PROBATION_HEADERS (Config.gs). */
    private const PROBATION_HEADERS = [
        'Probation ID', 'Employee ID', 'Recruitment ID',
        'Contract Number', 'Contract Duration', 'Contract Start', 'Contract End', 'Join Date',
        'Status', 'Onboarding Date', 'Onboarding By',
        'Eval ID', 'Eval Date',
        'Score Performance', 'Score Discipline', 'Score Communication', 'Score Initiative', 'Score Teamwork',
        'Average Score', 'Decision',
        'Extension Duration', 'New Contract Start', 'New Contract End',
        'Evaluator Notes', 'Evaluator', 'SK Status', 'Notes',
        'Created At', 'Updated At',
    ];

    public function __construct(
        EmployeeRepositoryInterface $employeeRepo,
        AuditLogRepositoryInterface $auditRepo,
        GoogleSheetsService $sheets
    ) {
        $this->employeeRepo = $employeeRepo;
        $this->auditRepo = $auditRepo;
        $this->sheets = $sheets;
    }

    private function probationSheet(): string
    {
        return config('google.sheets.candidates_probation', 'kandidat_probation');
    }

    /**
     * Evaluasi Probation — 1:1 dengan GAS saveProbationEval (backend/Employee.gs).
     * Decision (exact string dari modal):
     *  - "Lulus → Karyawan Tetap"                        → PKWTT + SK Pengangkatan
     *  - "Tidak Lolos → Putus Kontrak (Paklaring)"       → Terminated + Paklaring
     *  - "Tidak Lolos → Perpanjang Probation (Evaluasi Ulang)" → perpanjang kontrak, tanpa PDF
     *
     * @return array{success:bool, evalId:string, employeeId:string, decision:string, averageScore:float, isLulus:bool, isPutusKontrak:bool, isPerpanjang:bool, skNumber:string, message:string}
     */
    public function evaluateProbation(string $employeeId, array $evalData, ?string $user = null): array
    {
        $employee = $this->employeeRepo->findById($employeeId);
        if (!$employee) {
            throw new RuntimeException("Karyawan probation {$employeeId} tidak ditemukan.");
        }

        $user = $user ?: 'HR Administrator';
        $now = now()->timezone('Asia/Jakarta');
        $nowStr = $now->format('Y-m-d H:i:s');
        $evalId = $this->generateEvalId($now);

        // -- Skor & rata-rata (1 desimal, sama seperti GAS) -------
        $sp = (float) ($evalData['score_performance']   ?? 5);
        $sd = (float) ($evalData['score_discipline']    ?? 5);
        $sc = (float) ($evalData['score_communication'] ?? 5);
        $si = (float) ($evalData['score_initiative']    ?? 5);
        $st = (float) ($evalData['score_teamwork']      ?? 5);
        $avg = round(($sp + $sd + $sc + $si + $st) / 5, 1);

        // -- Klasifikasi keputusan (port GAS) ---------------------
        $decision = (string) ($evalData['decision'] ?? '');
        $isLulus = str_contains($decision, 'Lulus') || str_contains($decision, 'Tetap');
        $isPutusKontrak = str_contains($decision, 'Putus Kontrak') || str_contains($decision, 'Paklaring');
        $isPerpanjang = !$isLulus && !$isPutusKontrak
            && (str_contains($decision, 'Perpanjang') || str_contains($decision, 'Evaluasi Ulang'));

        if (!$isLulus && !$isPutusKontrak && !$isPerpanjang) {
            throw new RuntimeException('Keputusan evaluasi tidak valid.');
        }

        // -- Validasi skor server-side (1:1 GAS): Lulus butuh avg ≥ 7.0
        if ($isLulus && $avg < 7.0) {
            throw new RuntimeException("Keputusan 'Lulus → Karyawan Tetap' memerlukan skor rata-rata minimal 7.0. Skor saat ini: {$avg}.");
        }

        $extDuration = $evalData['extension_duration'] ?? '';
        $extStart = $evalData['extension_start'] ?? '';
        $extEnd = $evalData['extension_end'] ?? '';
        $notes = $evalData['notes'] ?? '';

        if ($isPerpanjang && (empty($extDuration) || empty($extStart))) {
            throw new RuntimeException('Perpanjangan probation memerlukan durasi dan tanggal mulai kontrak baru.');
        }

        $branchName = $employee->branchName ?? '';
        $skNumber = '';

        // -- Cabang per keputusan → update Employee sheet ---------
        if ($isLulus) {
            $skNumber = $this->generateSuratNumber('HRD-PK', $branchName, $now);
            $this->employeeRepo->update($employeeId, [
                'Status Employee' => 'PKWTT',
                'Nomor SK' => $skNumber,
                'HR Notes' => $this->appendNote($employee->hrNotes, "[{$nowStr}] Lulus Probation (avg {$avg}) — SK {$skNumber} by {$user}"),
                'Updated At' => $nowStr,
            ]);
            $probStatus = 'Completed - Passed';
            $skStatus = 'SK Diterbitkan';
        } elseif ($isPutusKontrak) {
            $skNumber = $this->generatePaklaringNumber($branchName, $evalId, $now);
            $this->employeeRepo->update($employeeId, [
                'Status Employee' => 'Terminated',
                'Resign Date' => $now->format('Y-m-d'),
                'Offboarding Type' => 'End of Probation',
                'Offboarding Reason' => 'Tidak Lolos Evaluasi Probation',
                'Nomor SK' => $skNumber,
                'HR Notes' => $this->appendNote($employee->hrNotes, "[{$nowStr}] Tidak Lolos Probation (avg {$avg}) — Paklaring {$skNumber} by {$user}"),
                'Updated At' => $nowStr,
            ]);
            $probStatus = 'Terminated';
            $skStatus = 'Paklaring Diterbitkan';
        } else { // perpanjang
            $this->employeeRepo->update($employeeId, [
                'End Date (Contract)' => $extEnd,
                'HR Notes' => $this->appendNote($employee->hrNotes, "[{$nowStr}] Probation diperpanjang {$extDuration} ({$extStart} s/d {$extEnd}) — avg {$avg} by {$user}"),
                'Updated At' => $nowStr,
            ]);
            $probStatus = 'Extended';
            $skStatus = 'Diperpanjang';
        }

        // -- Tulis baris evaluasi ke kandidat_probation (history) --
        $this->appendProbationEvalRow([
            'Probation ID' => $this->generateProbationId($now),
            'Employee ID' => $employeeId,
            'Recruitment ID' => $evalData['recruitment_id'] ?? '',
            'Join Date' => $employee->joinDate ?? '',
            'Status' => $probStatus,
            'Eval ID' => $evalId,
            'Eval Date' => $nowStr,
            'Score Performance' => $sp,
            'Score Discipline' => $sd,
            'Score Communication' => $sc,
            'Score Initiative' => $si,
            'Score Teamwork' => $st,
            'Average Score' => $avg,
            'Decision' => $decision,
            'Extension Duration' => $isPerpanjang ? $extDuration : '',
            'New Contract Start' => $isPerpanjang ? $extStart : '',
            'New Contract End' => $isPerpanjang ? $extEnd : '',
            'Evaluator Notes' => $notes,
            'Evaluator' => $user,
            'SK Status' => $skStatus,
            'Created At' => $nowStr,
            'Updated At' => $nowStr,
        ]);

        // -- Audit log --------------------------------------------
        $action = $isLulus ? 'Probation Lulus' : ($isPutusKontrak ? 'Probation Putus Kontrak' : 'Probation Diperpanjang');
        $this->auditRepo->log(
            recruitmentId: $employeeId,
            action: $action,
            field: 'Employment Status',
            oldValue: $employee->statusEmployee ?? 'Probation',
            newValue: "{$decision} (avg {$avg})" . ($skNumber ? " — {$skNumber}" : ''),
            user: $user
        );

        return [
            'success' => true,
            'evalId' => $evalId,
            'employeeId' => $employeeId,
            'decision' => $decision,
            'averageScore' => $avg,
            'isLulus' => $isLulus,
            'isPutusKontrak' => $isPutusKontrak,
            'isPerpanjang' => $isPerpanjang,
            'skNumber' => $skNumber,
            'message' => $isLulus
                ? 'Karyawan lulus probation & diangkat menjadi karyawan tetap (PKWTT).'
                : ($isPutusKontrak
                    ? 'Kontrak diakhiri. Paklaring diterbitkan.'
                    : "Masa probation diperpanjang ({$extDuration})."),
        ];
    }

    /**
     * Ambil riwayat evaluasi untuk seorang karyawan dari kandidat_probation,
     * hanya baris yang punya Eval Date, urut terbaru dulu. (1:1 GAS getProbationEvalHistory)
     */
    public function getEvalHistory(string $employeeId): array
    {
        $rows = $this->sheets->getRowsAsAssoc($this->probationSheet());
        $history = [];
        foreach ($rows as $row) {
            if (($row['Employee ID'] ?? '') !== $employeeId) continue;
            if (empty($row['Eval Date'])) continue;
            $history[] = [
                'evalId' => $row['Eval ID'] ?? '',
                'evalDate' => $row['Eval Date'] ?? '',
                'scorePerformance' => $row['Score Performance'] ?? '',
                'scoreDiscipline' => $row['Score Discipline'] ?? '',
                'scoreCommunication' => $row['Score Communication'] ?? '',
                'scoreInitiative' => $row['Score Initiative'] ?? '',
                'scoreTeamwork' => $row['Score Teamwork'] ?? '',
                'averageScore' => $row['Average Score'] ?? '',
                'decision' => $row['Decision'] ?? '',
                'extensionDuration' => $row['Extension Duration'] ?? '',
                'newContractStart' => $row['New Contract Start'] ?? '',
                'newContractEnd' => $row['New Contract End'] ?? '',
                'evaluatorNotes' => $row['Evaluator Notes'] ?? '',
                'evaluator' => $row['Evaluator'] ?? '',
                'skStatus' => $row['SK Status'] ?? '',
            ];
        }
        // Urut terbaru dulu (by evalDate desc)
        usort($history, fn($a, $b) => strcmp($b['evalDate'], $a['evalDate']));
        return $history;
    }

    /**
     * Peta Employee ID → evaluasi terakhir (untuk kolom Last Score & status di tabel).
     * Mengembalikan Collection dengan key = Employee ID.
     */
    public function latestEvalByEmployee(): Collection
    {
        $rows = $this->sheets->getRowsAsAssoc($this->probationSheet());
        $latest = [];
        foreach ($rows as $row) {
            $empId = $row['Employee ID'] ?? '';
            if ($empId === '' || empty($row['Eval Date'])) continue;
            $date = $row['Eval Date'];
            if (!isset($latest[$empId]) || strcmp($date, $latest[$empId]['evalDate']) > 0) {
                $latest[$empId] = [
                    'avgScore' => $row['Average Score'] ?? '',
                    'decision' => $row['Decision'] ?? '',
                    'evalDate' => $date,
                    'evaluator' => $row['Evaluator'] ?? '',
                ];
            }
        }
        return collect($latest);
    }

    // ==========================================================
    // Helpers
    // ==========================================================

    private function appendProbationEvalRow(array $data): void
    {
        $sheetName = $this->probationSheet();
        // Bangun baris sesuai urutan header. Jika sheet punya header kustom,
        // pakai header aktual; kalau kosong, pakai PROBATION_HEADERS.
        $headerRow = $this->sheets->getRange($sheetName, '1:1', false)[0] ?? [];
        $headers = !empty($headerRow) ? array_map('trim', $headerRow) : self::PROBATION_HEADERS;

        $row = [];
        foreach ($headers as $h) {
            $row[] = $data[$h] ?? '';
        }
        $this->sheets->appendRow($sheetName, $row);
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

    /** Nomor SK bulanan: NNN/{code}/{BRANCH6}/{RomawiBulan}/{tahun} (port generateSuratNumber_). */
    private function generateSuratNumber(string $code, string $branchName, \Illuminate\Support\Carbon $now): string
    {
        $branch6 = strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $branchName ?: 'MITO'), 0, 6));
        $roman = ['I','II','III','IV','V','VI','VII','VIII','IX','X','XI','XII'][$now->month - 1];
        $seq = $this->nextCounter('SK', $now);
        return sprintf('%03d/%s/%s/%s/%d', $seq, $code, $branch6, $roman, $now->year);
    }

    /** Nomor Paklaring: SKK/HRD/{BRANCH6}/{tahun}/{evalId} (port GAS). */
    private function generatePaklaringNumber(string $branchName, string $evalId, \Illuminate\Support\Carbon $now): string
    {
        $branch6 = strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $branchName ?: 'MITO'), 0, 6));
        return sprintf('SKK/HRD/%s/%d/%s', $branch6, $now->year, $evalId);
    }

    /** Counter harian per-prefix via Cache lock (analog PropertiesService di GAS). */
    private function nextCounter(string $prefix, \Illuminate\Support\Carbon $now): int
    {
        $key = "{$prefix}_COUNTER_" . $now->format('Ymd');
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
