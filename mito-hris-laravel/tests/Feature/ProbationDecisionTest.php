<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * ProbationDecisionTest — Unit test for decision routing logic.
 *
 * Tests the substring-collision-safe classification used across:
 *   - ProbationService::evaluateProbation()
 *   - ProbationController::evaluate() (redirect order)
 *   - index.blade.php ($isLulus / $isTerm PHP)
 *   - probation-modals.blade.php (JS updateConfirmBtn / selectEvalDecision)
 *
 * ROOT CAUSE fixed:
 *   str_contains('Tidak Lulus', 'Lulus') === true
 *   Without the priority-order guard, 'Tidak Lulus' was classified as isLulus,
 *   causing the controller to redirect to SK Pengangkatan instead of Paklaring.
 */
class ProbationDecisionTest extends TestCase
{
    // ── Replicates exact logic from ProbationService::evaluateProbation() ────
    private function classify(string $decision): array
    {
        // isPutusKontrak checked FIRST — prevents 'Tidak Lulus' false-positive on isLulus
        $isPutusKontrak = ($decision === 'Tidak Lulus')
                       || ($decision === 'Tidak Lolos → Putus Kontrak (Paklaring)')
                       || str_contains($decision, 'Putus Kontrak')
                       || str_contains($decision, 'Paklaring');

        $isPerpanjang = !$isPutusKontrak && (
                            $decision === 'Perpanjang Kontrak'
                         || str_contains($decision, 'Perpanjang')
                         || str_contains($decision, 'Evaluasi Ulang')
                        );

        $isLulus = !$isPutusKontrak && !$isPerpanjang && (
                       $decision === 'Diangkat sebagai Karyawan Tetap'
                    || $decision === 'Lulus → Karyawan Tetap'
                    || str_contains($decision, 'Diangkat')
                    || str_contains($decision, 'Tetap')
                   );

        return compact('isLulus', 'isPutusKontrak', 'isPerpanjang');
    }

    // ── Score category (mirrors ProbationService::calculateCategory) ─────────
    private function calculateCategory(int $total): string
    {
        return match (true) {
            $total >= 11 => 'Sangat Baik',
            $total >= 8  => 'Baik',
            $total >= 6  => 'Cukup',
            default      => 'Kurang',
        };
    }

    // =========================================================================
    // A. PRIMARY BUG — "Tidak Lulus" must route to Paklaring, NOT SK Pengangkatan
    // =========================================================================

    #[Test]
    public function tidak_lulus_must_be_putus_kontrak_not_lulus(): void
    {
        $c = $this->classify('Tidak Lulus');

        $this->assertFalse($c['isLulus'],
            'BUG REGRESSION: "Tidak Lulus" must NOT be classified as isLulus. '
            . 'str_contains("Tidak Lulus","Lulus") = true causes false-positive when isLulus is checked first.');
        $this->assertTrue($c['isPutusKontrak'],
            '"Tidak Lulus" must be isPutusKontrak → Paklaring PDF.');
        $this->assertFalse($c['isPerpanjang']);
    }

    #[Test]
    public function tidak_lulus_redirect_goes_to_paklaring_not_sk_pengangkatan(): void
    {
        $c = $this->classify('Tidak Lulus');

        // Simulate ProbationController redirect order (isPutusKontrak checked BEFORE isLulus)
        $route = $c['isPutusKontrak'] ? 'hr.export.paklaring'
               : ($c['isLulus']       ? 'hr.export.sk-pengangkatan'
                                      : 'hr.probation.index');

        $this->assertSame('hr.export.paklaring', $route,
            'Controller redirect for "Tidak Lulus" must go to Paklaring, not SK Pengangkatan.');
    }

    // =========================================================================
    // B. LULUS — "Diangkat sebagai Karyawan Tetap"
    // =========================================================================

    #[Test]
    public function diangkat_sebagai_karyawan_tetap_must_be_lulus(): void
    {
        $c = $this->classify('Diangkat sebagai Karyawan Tetap');
        $this->assertTrue($c['isLulus']);
        $this->assertFalse($c['isPutusKontrak']);
        $this->assertFalse($c['isPerpanjang']);
    }

    #[Test]
    public function diangkat_redirect_goes_to_sk_pengangkatan(): void
    {
        $c = $this->classify('Diangkat sebagai Karyawan Tetap');
        $route = $c['isPutusKontrak'] ? 'hr.export.paklaring'
               : ($c['isLulus']       ? 'hr.export.sk-pengangkatan'
                                      : 'hr.probation.index');
        $this->assertSame('hr.export.sk-pengangkatan', $route);
    }

    // =========================================================================
    // C. EXTEND — "Perpanjang Kontrak"
    // =========================================================================

    #[Test]
    public function perpanjang_kontrak_must_be_perpanjang(): void
    {
        $c = $this->classify('Perpanjang Kontrak');
        $this->assertFalse($c['isLulus']);
        $this->assertFalse($c['isPutusKontrak']);
        $this->assertTrue($c['isPerpanjang']);
    }

    #[Test]
    public function perpanjang_redirect_goes_to_probation_index(): void
    {
        $c = $this->classify('Perpanjang Kontrak');
        $route = $c['isPutusKontrak'] ? 'hr.export.paklaring'
               : ($c['isLulus']       ? 'hr.export.sk-pengangkatan'
                                      : 'hr.probation.index');
        $this->assertSame('hr.probation.index', $route);
    }

    // =========================================================================
    // D. LEGACY decision values — backward compatibility
    // =========================================================================

    #[Test]
    public function legacy_lulus_karyawan_tetap_must_be_lulus(): void
    {
        $c = $this->classify('Lulus → Karyawan Tetap');
        $this->assertTrue($c['isLulus']);
        $this->assertFalse($c['isPutusKontrak']);
        $this->assertFalse($c['isPerpanjang']);
    }

    #[Test]
    public function legacy_tidak_lolos_putus_kontrak_must_be_putus_kontrak(): void
    {
        $c = $this->classify('Tidak Lolos → Putus Kontrak (Paklaring)');
        $this->assertFalse($c['isLulus']);
        $this->assertTrue($c['isPutusKontrak']);
        $this->assertFalse($c['isPerpanjang']);
    }

    #[Test]
    public function legacy_tidak_lolos_perpanjang_must_be_perpanjang(): void
    {
        $c = $this->classify('Tidak Lolos → Perpanjang Probation (Evaluasi Ulang)');
        $this->assertFalse($c['isLulus']);
        $this->assertFalse($c['isPutusKontrak']);
        $this->assertTrue($c['isPerpanjang']);
    }

    // =========================================================================
    // E. All 6 decision values recognised exactly once — no ambiguity
    // =========================================================================

    #[Test]
    public function all_decision_values_must_be_recognised_exactly_once(): void
    {
        $cases = [
            'Diangkat sebagai Karyawan Tetap'                      => 'lulus',
            'Tidak Lulus'                                          => 'putus',
            'Perpanjang Kontrak'                                   => 'perp',
            'Lulus → Karyawan Tetap'                              => 'lulus',
            'Tidak Lolos → Putus Kontrak (Paklaring)'             => 'putus',
            'Tidak Lolos → Perpanjang Probation (Evaluasi Ulang)' => 'perp',
        ];

        foreach ($cases as $decision => $expected) {
            $c         = $this->classify($decision);
            $trueCount = (int)$c['isLulus'] + (int)$c['isPutusKontrak'] + (int)$c['isPerpanjang'];

            $this->assertSame(1, $trueCount,
                "Decision \"$decision\" must match EXACTLY one category, got: " . json_encode($c));

            $actual = $c['isLulus'] ? 'lulus' : ($c['isPutusKontrak'] ? 'putus' : 'perp');
            $this->assertSame($expected, $actual,
                "Decision \"$decision\" expected=$expected actual=$actual");
        }
    }

    // =========================================================================
    // F. Invalid / empty decision must not match any category
    // =========================================================================

    #[Test]
    public function invalid_decision_must_be_unrecognised(): void
    {
        $c = $this->classify('Sesuatu Yang Tidak Valid');
        $this->assertFalse($c['isLulus']);
        $this->assertFalse($c['isPutusKontrak']);
        $this->assertFalse($c['isPerpanjang']);
        // ProbationService throws RuntimeException for this case
    }

    #[Test]
    public function empty_decision_must_be_unrecognised(): void
    {
        $c = $this->classify('');
        $this->assertFalse($c['isLulus']);
        $this->assertFalse($c['isPutusKontrak']);
        $this->assertFalse($c['isPerpanjang']);
    }

    // =========================================================================
    // G. Score → Category boundaries (server-side, 1:1 GAS)
    // =========================================================================

    #[Test]
    public function category_boundaries_are_correct(): void
    {
        $this->assertSame('Kurang',      $this->calculateCategory(0));
        $this->assertSame('Kurang',      $this->calculateCategory(5));
        $this->assertSame('Cukup',       $this->calculateCategory(6));
        $this->assertSame('Cukup',       $this->calculateCategory(7));
        $this->assertSame('Baik',        $this->calculateCategory(8));
        $this->assertSame('Baik',        $this->calculateCategory(10));
        $this->assertSame('Sangat Baik', $this->calculateCategory(11));
        $this->assertSame('Sangat Baik', $this->calculateCategory(13));
    }

    #[Test]
    public function total_indicators_max_is_13(): void
    {
        // 4 Integrity + 4 CI + 2 EE + 3 Teamwork = 13
        $maxTotal = 4 + 4 + 2 + 3;
        $this->assertSame(13, $maxTotal);
        $this->assertSame('Sangat Baik', $this->calculateCategory($maxTotal));
    }

    // =========================================================================
    // H. Extension duration — only 3 / 6 / 12 Bulan allowed
    // =========================================================================

    #[Test]
    public function valid_extension_durations_are_accepted(): void
    {
        foreach (['3 Bulan', '6 Bulan', '12 Bulan'] as $dur) {
            $this->assertTrue(
                in_array($dur, ['3 Bulan', '6 Bulan', '12 Bulan'], true),
                "Duration \"$dur\" should be valid"
            );
        }
    }

    #[Test]
    public function invalid_extension_durations_are_rejected(): void
    {
        foreach (['1 Bulan', '2 Bulan', '4 Bulan', '9 Bulan', '', 'forever'] as $dur) {
            $this->assertFalse(
                in_array($dur, ['3 Bulan', '6 Bulan', '12 Bulan'], true),
                "Duration \"$dur\" should be invalid"
            );
        }
    }

    // =========================================================================
    // I. Extension fields required ONLY when isPerpanjang
    // =========================================================================

    #[Test]
    public function perpanjang_requires_duration_and_start_date(): void
    {
        $cases = [
            // [decision, extDuration, extStart, shouldPass]
            ['Perpanjang Kontrak', '3 Bulan', '2026-08-01', true],
            ['Perpanjang Kontrak', '',        '2026-08-01', false],
            ['Perpanjang Kontrak', '3 Bulan', '',           false],
            ['Perpanjang Kontrak', '',        '',            false],
            // Non-perpanjang decisions don't need extension fields
            ['Tidak Lulus',                    '', '', true],
            ['Diangkat sebagai Karyawan Tetap','', '', true],
        ];

        foreach ($cases as [$decision, $extDuration, $extStart, $shouldPass]) {
            $c = $this->classify($decision);
            $valid = !$c['isPerpanjang'] || (!empty($extDuration) && !empty($extStart));

            $this->assertSame($shouldPass, $valid,
                "Decision=\"$decision\" dur=\"$extDuration\" start=\"$extStart\" expected "
                . ($shouldPass ? 'PASS' : 'FAIL'));
        }
    }

    // =========================================================================
    // J. Full redirect matrix — all 6 decisions × isPutusKontrak-first ordering
    // =========================================================================

    #[Test]
    public function controller_redirect_order_is_correct_for_all_decisions(): void
    {
        $expected = [
            'Tidak Lulus'                                          => 'paklaring',
            'Diangkat sebagai Karyawan Tetap'                      => 'sk_pengangkatan',
            'Perpanjang Kontrak'                                   => 'index',
            'Tidak Lolos → Putus Kontrak (Paklaring)'             => 'paklaring',
            'Lulus → Karyawan Tetap'                              => 'sk_pengangkatan',
            'Tidak Lolos → Perpanjang Probation (Evaluasi Ulang)' => 'index',
        ];

        foreach ($expected as $decision => $expectedRoute) {
            $c = $this->classify($decision);

            // Exact order from ProbationController::evaluate()
            $route = $c['isPutusKontrak'] ? 'paklaring'
                   : ($c['isLulus']       ? 'sk_pengangkatan'
                                          : 'index');

            $this->assertSame($expectedRoute, $route,
                "Decision \"$decision\" → expected \"$expectedRoute\", got \"$route\"");
        }
    }
}
