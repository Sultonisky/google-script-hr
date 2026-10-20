<?php

namespace App\Enums;

/**
 * ProbationDecisionType — single source of truth for decision classification.
 *
 * Replaces scattered str_contains() logic in:
 *   - ProbationService::evaluateProbation()
 *   - ProbationController::evaluate()
 *   - probation/index.blade.php
 *   - probation-modals.blade.php (JS mirrors this logic)
 *
 * PDF generation rules (on-demand, streamed directly to the browser — same
 * convention as the other PDF functions in hr/export):
 *   PASS   → Performance Review PDF + SK Pengangkatan PDF
 *   FAIL   → Performance Review PDF + Paklaring PDF
 *   EXTEND → Kontrak PKWT perpanjangan (akhiran /RNW1–RNW3), maksimal 3 kali
 *
 * Detection priority: FAIL must be checked BEFORE PASS to prevent
 * str_contains('Tidak Lulus', 'Lulus') = true false-positive.
 */
enum ProbationDecisionType: string
{
    case PASS   = 'pass';    // Lulus → Karyawan Tetap → SK Pengangkatan
    case FAIL   = 'fail';    // Tidak Lulus → Putus Kontrak → Paklaring
    case EXTEND = 'extend';  // Perpanjang Kontrak → Kontrak PKWT /RNW1–RNW3

    // ── Classification ────────────────────────────────────────────

    /**
     * Classify a raw decision string into a ProbationDecisionType.
     * Returns null if the string does not match any known decision.
     *
     * IMPORTANT: FAIL is checked BEFORE PASS (see class docblock).
     */
    public static function fromDecisionString(string $decision): ?self
    {
        // FAIL — checked FIRST to prevent 'Tidak Lulus' matching PASS via str_contains
        if (
            $decision === 'Tidak Lulus'
            || $decision === 'Tidak Lolos → Putus Kontrak (Paklaring)'
            || str_contains($decision, 'Putus Kontrak')
            || str_contains($decision, 'Paklaring')
        ) {
            return self::FAIL;
        }

        // EXTEND — checked before PASS (safety)
        if (
            $decision === 'Extend'
            || $decision === 'Perpanjang Kontrak'
            || str_contains($decision, 'Perpanjang')
            || str_contains($decision, 'Evaluasi Ulang')
        ) {
            return self::EXTEND;
        }

        // PASS — only if neither FAIL nor EXTEND
        if (
            $decision === 'Lulus'
            || $decision === 'Diangkat sebagai Karyawan Tetap'
            || $decision === 'Lulus → Karyawan Tetap'
            || str_contains($decision, 'Diangkat')
            || str_contains($decision, 'Tetap')
        ) {
            return self::PASS;
        }

        return null;
    }

    // ── Helpers ──────────────────────────────────────────────────

    public function isPass(): bool   { return $this === self::PASS; }
    public function isFail(): bool   { return $this === self::FAIL; }
    public function isExtend(): bool { return $this === self::EXTEND; }

    /**
     * Whether this decision triggers PDF generation.
     */
    public function hasPdf(): bool
    {
        return match($this) {
            self::PASS, self::FAIL, self::EXTEND => true,
        };
    }

    /**
     * Export route name for the decision-specific document.
     */
    public function documentType(): ?string
    {
        return match($this) {
            self::PASS   => 'sk_pengangkatan',
            self::FAIL   => 'paklaring',
            self::EXTEND => 'kontrak_pkwt',
        };
    }

    /** Human-readable label for the decision-specific document. */
    public function documentLabel(): string
    {
        return match($this) {
            self::PASS   => 'SK Pengangkatan',
            self::FAIL   => 'Paklaring',
            self::EXTEND => 'Kontrak PKWT Perpanjangan',
        };
    }

    /** Human-readable label in Indonesian. */
    public function label(): string
    {
        return match($this) {
            self::PASS   => 'Lulus — Diangkat sebagai Karyawan Tetap',
            self::FAIL   => 'Tidak Lulus — Putus Kontrak (Paklaring)',
            self::EXTEND => 'Perpanjang Kontrak',
        };
    }

    /**
     * Export route name for the decision-specific document.
     */
    public function exportRouteName(): ?string
    {
        return match($this) {
            self::PASS   => 'hr.export.sk-pengangkatan',
            self::FAIL   => 'hr.export.paklaring',
            self::EXTEND => 'hr.export.kontrak-pkwt',
        };
    }

    /**
     * Toast/success message suffix after evaluation saved.
     */
    public function successMessage(string $skNumber = '', string $extDuration = ''): string
    {
        return match($this) {
            self::PASS   => "Karyawan lulus probation & diangkat menjadi karyawan tetap (PKWTT). SK: {$skNumber}",
            self::FAIL   => "Kontrak diakhiri. Paklaring dan Surat BPJS diterbitkan. No. Paklaring: {$skNumber}",
            self::EXTEND => "Masa probation diperpanjang ({$extDuration}). Kontrak: {$skNumber}",
        };
    }
}
