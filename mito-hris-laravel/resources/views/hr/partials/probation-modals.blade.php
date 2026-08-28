{{-- ============================================================
     hr/partials/probation-modals.blade.php
     Performance Review 2026 — Behavioral Indicators Checklist
     
     SUBMIT FLOW (AJAX):
       fetch POST /hr/probation/{id}/evaluate  (X-Requested-With: XMLHttpRequest)
         → JSON { success, pdfUrl, isLulus, isPutusKontrak, isPerpanjang, ... }
         → if pdfUrl: window.open(pdfUrl, '_blank')   ← PDF download in new tab
         → modal.hide()
         → show toast success
         → location.reload() to refresh probation table + stats
     
     NOT a native form submit — never does page redirect.
     Loading state is ALWAYS reset in the finally block.
     ============================================================ --}}

<div class="modal fade" id="probationEvalModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content" style="border-radius:16px;overflow:hidden">

            {{-- ── HEADER ──────────────────────────────────────────────── --}}
            <div class="modal-header py-3 px-4" style="background:#eb1c24;border-bottom:none">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-clipboard-check text-white" style="font-size:18px"></i>
                    <div>
                        <h6 class="modal-title mb-0 text-white fw-bold" style="font-size:15px">Evaluasi Probation</h6>
                        <div class="text-white opacity-75" style="font-size:11px">Performance Review 2026 - Behavioral
                            Indicators</div>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            {{-- ── HIDDEN FIELDS (not a real form submit — values read by JS fetch) --}}
            <input type="hidden" id="evalEmployeeId" value="" />
            <input type="hidden" id="evalRecruitmentId" value="" />
            <input type="hidden" id="evalDecisionValue" value="" />
            <input type="hidden" id="evalExtDuration" value="" />
            {{-- Approval sign-off hidden fields (Performance Review template Section F) --}}
            <input type="hidden" id="evalApprovalDept" value="" />
            <input type="hidden" id="evalApprovalDeptName" value="" />
            <input type="hidden" id="evalApprovalDeptDate" value="" />
            <input type="hidden" id="evalApprovalHrbp" value="" />
            <input type="hidden" id="evalApprovalHrbpName" value="" />
            <input type="hidden" id="evalApprovalHrbpDate" value="" />
            {{-- 13 indicator hidden fields — 3 states: "" = belum dinilai, "1" = , "0" = X tidak terpenuhi --}}
            <input type="hidden" id="ind_integrity_1" value="">
            <input type="hidden" id="ind_integrity_2" value="">
            <input type="hidden" id="ind_integrity_3" value="">
            <input type="hidden" id="ind_integrity_4" value="">
            <input type="hidden" id="ind_ci_1" value="">
            <input type="hidden" id="ind_ci_2" value="">
            <input type="hidden" id="ind_ci_3" value="">
            <input type="hidden" id="ind_ci_4" value="">
            <input type="hidden" id="ind_ee_1" value="">
            <input type="hidden" id="ind_ee_2" value="">
            <input type="hidden" id="ind_tw_1" value="">
            <input type="hidden" id="ind_tw_2" value="">
            <input type="hidden" id="ind_tw_3" value="">

            <div class="modal-body p-0">

                {{-- ═══════════════════════════════════════════════════════
             SECTION A — CARI KARYAWAN
             ═══════════════════════════════════════════════════════ --}}
                <div class="px-4 pt-4 pb-3" style="border-bottom:1px solid #f3f4f6">
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <span class="d-flex align-items-center justify-content-center text-white fw-bold rounded-circle"
                            style="width:22px;height:22px;font-size:11px;background:#eb1c24;flex-shrink:0">A</span>
                        <span class="fw-semibold" style="font-size:13px;color:#374151">Pilih Karyawan</span>
                    </div>
                    <div class="position-relative mt-2">
                        <i class="bi bi-search position-absolute"
                            style="left:10px;top:50%;transform:translateY(-50%);color:#9ca3af;font-size:13px"></i>
                        <input type="text" class="form-control form-control-sm" id="evalEmpSearch"
                            placeholder="Ketik nama atau Employee ID (status: Probation)…" autocomplete="off"
                            style="padding-left:30px;padding-right:32px;font-size:13px"
                            oninput="handleEvalEmpSearch(this.value)" />
                        <i class="bi bi-x-circle-fill position-absolute" id="evalEmpSearchClear"
                            style="right:10px;top:50%;transform:translateY(-50%);cursor:pointer;color:#aaa;display:none;font-size:14px"
                            onclick="clearEvalEmpSearch()"></i>
                    </div>
                    <div id="evalEmpDropdown" class="border rounded-3 mt-1 shadow-sm"
                        style="display:none;max-height:220px;overflow-y:auto;overflow-x:hidden;background:#fff;z-index:9999;position:relative">
                    </div>
                </div>

                {{-- ── Employee info card (hidden until employee selected) ── --}}
                <div id="evalEmpPreview" style="display:none">

                    {{-- ─── EMPLOYEE INFO CARD ───────────────────────────── --}}
                    <div class="mx-4 mt-3 p-3 rounded-3" style="background:#f5f3ff;border:1px solid #ddd6fe">
                        {{-- Row 1: avatar + name/position + IDs --}}
                        <div class="d-flex align-items-center gap-3 mb-2">
                            <div id="evalEmpAvatar"
                                style="width:44px;height:44px;border-radius:12px;background:#eb1c24;color:#fff;
                       font-size:16px;font-weight:800;display:flex;align-items:center;
                       justify-content:center;flex-shrink:0">
                                ?</div>
                            <div class="flex-grow-1 min-w-0">
                                <div class="fw-bold text-truncate" id="evalEmpName" style="font-size:14px">—</div>
                                <div class="text-muted text-truncate" style="font-size:12px">
                                    <span id="evalEmpPosition">—</span>
                                    <span class="mx-1 text-muted">·</span>
                                    <span id="evalEmpDept">—</span>
                                </div>
                                <div style="font-size:11px;color:#eb1c24;margin-top:2px">
                                    <span id="evalEmpLevel">—</span>
                                    <span class="mx-1 text-muted">·</span>
                                    <span id="evalEmpPT">—</span>
                                </div>
                            </div>
                            <div class="text-end flex-shrink-0" style="font-size:11px">
                                <div class="text-muted">Employee ID</div>
                                <div class="fw-bold" id="evalEmpIdDisp" style="color:#eb1c24">—</div>
                                <div class="text-muted mt-1">Masuk</div>
                                <div class="fw-semibold" id="evalEmpJoinDate">—</div>
                                <div class="text-muted mt-1">Kontrak s/d</div>
                                <div class="fw-semibold" id="evalEmpContractEnd">—</div>
                            </div>
                        </div>
                        {{-- Row 2: Nama Atasan Langsung (Reviewer) — from template field --}}
                        <div class="pt-2" style="border-top:1px solid #ede9fe">
                            <span style="font-size:11px;color:#6b7280">
                                <i class="bi bi-person-lines-fill me-1" style="color:#eb1c24"></i>
                                <strong>Nama Atasan Langsung (Reviewer):</strong>
                                <span id="evalEmpReviewer" style="color:#374151">—</span>
                            </span>
                        </div>
                    </div>

                    {{-- ═══════════════════════════════════════════════════════
               SECTION B — PENILAIAN BEHAVIORAL INDICATORS
               ═══════════════════════════════════════════════════════ --}}
                    <div class="px-4 pt-4 pb-2">
                        <div class="d-flex align-items-center gap-2 mb-3">
                            <span
                                class="d-flex align-items-center justify-content-center text-white fw-bold rounded-circle"
                                style="width:22px;height:22px;font-size:11px;background:#eb1c24;flex-shrink:0">B</span>
                            <span class="fw-semibold" style="font-size:13px;color:#374151">Penilaian Behavioral
                                Indicators</span>
                        </div>
                        <div class="alert py-2 px-3 mb-3 d-flex align-items-start gap-2"
                            style="background:#f0f9ff;border:1px solid #bae6fd;border-radius:8px;font-size:12px">
                            <i class="bi bi-info-circle-fill text-info mt-1" style="font-size:13px;flex-shrink:0"></i>
                            <span>
                                Berikan tanda <strong></strong> atau <strong>i</strong> pada
                                setiap Behavioral Indicator.
                                <strong>Semua indikator wajib dinilai</strong> sebelum dapat menyimpan evaluasi.
                                Total jumlah ✓ akan dihitung sebagai skor kompetensi.
                            </span>
                        </div>
                    </div>

                    {{-- ── COMPETENCY BLOCKS ─────────────────────────────── --}}
                    @php
                        $competencies = [
                            [
                                'key' => 'integrity',
                                'icon' => 'bi-shield-check',
                                'color' => '#eb1c24',
                                'bg' => '#faf5ff',
                                'border' => '#e9d5ff',
                                'no' => '1',
                                'title' => 'Integrity',
                                'action' => 'Take Accountability',
                                'desc' => 'Bertanggung jawab atas tindakan, keputusan & hasil kerja.',
                                'max' => 4,
                                'evidence' => 'Task/project tracker, Weekly/deadline report',
                                'items' => [
                                    'integrity_1' => 'Menyelesaikan minimal 90% tugas sesuai deadline.',
                                    'integrity_2' => 'Memberikan update progres pekerjaan secara rutin.',
                                    'integrity_3' => 'Menindaklanjuti permasalahan sesuai SLA.',
                                    'integrity_4' =>
                                        'Tidak terdapat pelanggaran prosedur, kebijakan, atau komitmen kerja.',
                                ],
                            ],
                            [
                                'key' => 'ci',
                                'icon' => 'bi-arrow-up-circle',
                                'color' => '#eb1c24',
                                'bg' => '#f0f9ff',
                                'border' => '#e9d5ff',
                                'no' => '2',
                                'title' => 'Continuous Improvement',
                                'action' => 'Proactive Contribution',
                                'desc' => 'Aktif mencari kesempatan belajar & meningkatkan cara kerja.',
                                'max' => 4,
                                'evidence' => 'Assignment project, Project report, Coaching form',
                                'items' => [
                                    'ci_1' => 'Mengusulkan minimal 1 improvement atau solusi selama masa probation.',
                                    'ci_2' => 'Berpartisipasi dalam minimal 1 project atau kegiatan tim/perusahaan.',
                                    'ci_3' =>
                                        'Mempelajari atau mengimplementasi proses, sistem, atau knowledge baru yang mendukung pekerjaan.',
                                    'ci_4' => 'Mengambil tindakan awal terhadap masalah sebelum dilakukan eskalasi.',
                                ],
                            ],
                            [
                                'key' => 'ee',
                                'icon' => 'bi-star',
                                'color' => '#eb1c24',
                                'bg' => '#f0f9ff',
                                'border' => '#e9d5ff',
                                'no' => '3',
                                'title' => 'Execution Excellence',
                                'action' => 'Deliver Quality Results',
                                'desc' => 'Menyelesaikan pekerjaan dengan kualitas yang baik.',
                                'max' => 2,
                                'evidence' => 'Dokumentasi kegiatan, bukti konkrit achievement',
                                'items' => [
                                    'ee_1' =>
                                        'Tingkat kesalahan atau rework tidak melebihi kesepakatan yang telah ditetapkan.',
                                    'ee_2' => 'Hasil pekerjaan dapat digunakan atau diselesaikan tanpa koreksi mayor.',
                                ],
                            ],
                            [
                                'key' => 'tw',
                                'icon' => 'bi-people',
                                'color' => '#eb1c24',
                                'bg' => '#f0f9ff',
                                'border' => '#e9d5ff',
                                'no' => '4',
                                'title' => 'Teamwork',
                                'action' => 'Supportive Collaboration',
                                'desc' => 'Berkolaborasi dan memberikan dukungan untuk mencapai tujuan bersama.',
                                'max' => 3,
                                'evidence' => 'Stakeholder feedback, Observasi dari atasan langsung',
                                'items' => [
                                    'tw_1' => 'Berpartisipasi aktif dalam meeting, diskusi, atau koordinasi.',
                                    'tw_2' => 'Menindaklanjuti permintaan stakeholder internal sesuai SLA.',
                                    'tw_3' =>
                                        'Tidak terdapat keluhan mayor terkait koordinasi atau kerja sama selama probation.',
                                ],
                            ],
                        ];
                    @endphp

                    @foreach ($competencies as $comp)
                        <div class="mx-4 mb-3 rounded-3"
                            style="border:1px solid {{ $comp['border'] }};overflow:hidden">
                            {{-- Competency header --}}
                            <div class="d-flex align-items-center justify-content-between px-3 py-2"
                                style="background:{{ $comp['bg'] }};border-bottom:1px solid {{ $comp['border'] }}">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="bi {{ $comp['icon'] }}"
                                        style="color:{{ $comp['color'] }};font-size:15px"></i>
                                    <div>
                                        <span class="fw-bold" style="font-size:12.5px;color:{{ $comp['color'] }}">
                                            Kompetensi {{ $comp['no'] }} - {{ $comp['title'] }}
                                        </span>
                                        <span class="text-muted ms-2" style="font-size:11px">
                                            Key Action: {{ $comp['action'] }}
                                        </span>
                                    </div>
                                </div>
                                <div class="text-center" style="min-width:48px">
                                    <span id="badge_{{ $comp['key'] }}" class="fw-bold"
                                        style="font-size:16px;color:{{ $comp['color'] }}">–</span>
                                    <span style="font-size:11px;color:#9ca3af"> / {{ $comp['max'] }}</span>
                                </div>
                            </div>
                            {{-- Indicator rows --}}

                            @foreach ($comp['items'] as $key => $label)
                                <div class="eval-ind-row px-3 py-2 d-flex align-items-center gap-2"
                                    style="border-bottom:1px solid #f9fafb">
                                    <div class="flex-grow-1" style="font-size:12.5px;line-height:1.5;color:#374151">
                                        {{ $label }}
                                    </div>
                                    <div class="d-flex gap-1 flex-shrink-0" role="group"
                                        aria-label="Penilaian indicator">
                                        <button type="button"
                                            class="ind-btn-check btn btn-sm d-inline-flex align-items-center justify-content-center"
                                            data-key="{{ $key }}" data-val="1"
                                            aria-label="Indikator terpenuhi" title="Terpenuhi"
                                            style="width:34px;height:32px;border:2px solid #d1d5db;background:#fff;color:#374151;font-size:15px;font-weight:600;border-radius:8px;padding:0;transition:all .15s"
                                            onclick="setIndicator('{{ $key }}', '1', this)">
                                            <i class="bi bi-check-circle" aria-hidden="true"></i>
                                        </button>
                                        <button type="button"
                                            class="ind-btn-cross btn btn-sm d-inline-flex align-items-center justify-content-center"
                                            data-key="{{ $key }}" data-val="0"
                                            aria-label="Indikator tidak terpenuhi" title="Tidak terpenuhi"
                                            style="width:34px;height:32px;border:2px solid #d1d5db;background:#fff;color:#374151;font-size:15px;font-weight:600;border-radius:8px;padding:0;transition:all .15s"
                                            onclick="setIndicator('{{ $key }}', '0', this)">
                                            <i class="bi bi-x-circle" aria-hidden="true"></i>
                                        </button>
                                    </div>
                                    <div class="ind-error" id="ind_err_{{ $key }}"
                                        style="display:none;font-size:11px;color:#dc2626;margin-top:3px">
                                        <i class="bi bi-exclamation-triangle-fill me-1"></i>Indikator ini belum
                                        dinilai.
                                    </div>
                                </div>
                            @endforeach
                            {{-- Evidence note --}}
                            <div class="px-3 py-2" style="background:#fafafa">
                                <span style="font-size:10.5px;color:#9ca3af">
                                    <i class="bi bi-paperclip me-1"></i><em>Contoh bukti: {{ $comp['evidence'] }}</em>
                                </span>
                            </div>
                        </div>
                    @endforeach

                    {{-- ═══════════════════════════════════════════════════════
               SCORE SUMMARY (selalu visible setelah indicator diisi)
               ═══════════════════════════════════════════════════════ --}}
                    <div class="mx-2 mx-sm-4 mb-4 p-3 rounded-3" style="background:#f5f3ff;border:2px solid #eb1c24">
                        <div class="row g-2 align-items-center">
                            <div class="col-6 col-sm-4 text-center border-end-sm"
                                style="border-right:1px solid #ddd6fe">
                                <div
                                    style="font-size:10px;color:#eb1c24;font-weight:700;text-transform:uppercase;letter-spacing:.05em;margin-bottom:4px">
                                    Total Score</div>
                                <div id="evalOverallTotal"
                                    style="font-size:32px;font-weight:900;color:#eb1c24;line-height:1">–</div>
                                <div style="font-size:10px;color:#9ca3af">dari 13 indikator</div>
                            </div>
                            <div class="col-6 col-sm-4 text-center border-end-sm"
                                style="border-right:1px solid #ddd6fe;padding:0 8px">
                                <div
                                    style="font-size:10px;color:#6b7280;font-weight:700;text-transform:uppercase;letter-spacing:.05em;margin-bottom:6px">
                                    Kategori</div>
                                <div id="evalCategoryBadge"
                                    class="d-inline-block px-2 px-sm-3 py-1 rounded-pill fw-bold"
                                    style="font-size:11.5px;background:#e5e7eb;color:#6b7280">—</div>
                            </div>
                            <div class="col-12 col-sm-4 ps-sm-3 pt-2 pt-sm-0 border-top border-top-sm-0"
                                style="border-color:#ddd6fe !important">
                                <table style="font-size:10px;width:100%;border-collapse:collapse">
                                    <tr>
                                        <td><span
                                                style="display:inline-block;width:34px;border-radius:4px;background:#d1fae5;color:#166534;text-align:center;font-weight:700;font-size:9.5px;padding:1px 0">11–13</span>
                                        </td>
                                        <td class="ps-1" style="color:#374151">Sangat Baik</td>
                                    </tr>
                                    <tr>
                                        <td><span
                                                style="display:inline-block;width:34px;border-radius:4px;background:#dbeafe;color:#1d4ed8;text-align:center;font-weight:700;font-size:9.5px;padding:1px 0">8–10</span>
                                        </td>
                                        <td class="ps-1" style="color:#374151">Baik</td>
                                    </tr>
                                    <tr>
                                        <td><span
                                                style="display:inline-block;width:34px;border-radius:4px;background:#fef3c7;color:#92400e;text-align:center;font-weight:700;font-size:9.5px;padding:1px 0">6–7</span>
                                        </td>
                                        <td class="ps-1" style="color:#374151">Cukup</td>
                                    </tr>
                                    <tr>
                                        <td><span
                                                style="display:inline-block;width:34px;border-radius:4px;background:#fee2e2;color:#991b1b;text-align:center;font-weight:700;font-size:9.5px;padding:1px 0">3–5</span>
                                        </td>
                                        <td class="ps-1" style="color:#374151">Kurang</td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                        <div id="evalScoreFeedback" class="mt-2 text-center" style="font-size:12px;color:#9ca3af">
                            Pilih ✓ atau ✗ pada setiap behavioral indicator di atas.
                        </div>
                    </div>

                    {{-- ═══════════════════════════════════════════════════════
               SECTION C — KEPUTUSAN EVALUASI
               ═══════════════════════════════════════════════════════ --}}
                    <div class="px-4 pb-2">
                        <div class="d-flex align-items-center gap-2 mb-3">
                            <span
                                class="d-flex align-items-center justify-content-center text-white fw-bold rounded-circle"
                                style="width:22px;height:22px;font-size:11px;background:#eb1c24;flex-shrink:0">C</span>
                            <span class="fw-semibold" style="font-size:13px;color:#374151">Keputusan Evaluasi <span
                                    class="text-danger">*</span></span>
                        </div>

                        <div class="d-flex flex-column gap-2 mb-1" id="evalDecisionOptions">

                            {{-- 1. LULUS --}}
                            <div class="eval-decision-opt rounded-3 p-3" data-value="Lulus"
                                style="border:2px solid #e5e7eb;cursor:pointer;transition:all .15s"
                                onclick="selectEvalDecision(this)">
                                <div class="d-flex align-items-start gap-3">
                                    <div class="eval-dec-dot flex-shrink-0 mt-1"
                                        style="width:18px;height:18px;border-radius:50%;border:2px solid #d1d5db;
                           display:flex;align-items:center;justify-content:center">
                                        <div class="eval-dec-inner"
                                            style="width:8px;height:8px;border-radius:50%;background:transparent;transition:background .15s">
                                        </div>
                                    </div>
                                    <div>
                                        <div class="fw-bold d-flex align-items-center gap-2" style="font-size:13px">
                                            <i class="bi bi-check-circle me-1" style="color:#166534"></i>
                                            LULUS - Diangkat sebagai Karyawan Tetap
                                        </div>
                                        <div class="text-muted mt-1" style="font-size:12px">
                                            Employee diproses menjadi karyawan tetap (PKWTT).
                                            <strong class="text-success">SK Pengangkatan Tetap</strong> akan
                                            diterbitkan.
                                        </div>
                                        <div id="evalLulusScoreGate" class="mt-1"
                                            style="display:none;font-size:11px;color:#dc2626">
                                            <i class="bi bi-exclamation-triangle-fill me-1"></i>
                                            Pilihan ini membutuhkan minimal 8 indikator terpenuhi (kategori Baik atau
                                            Sangat Baik).
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- 2. TIDAK LULUS / PAKLARING --}}
                            <div class="eval-decision-opt rounded-3 p-3" data-value="Tidak Lulus"
                                style="border:2px solid #e5e7eb;cursor:pointer;transition:all .15s"
                                onclick="selectEvalDecision(this)">
                                <div class="d-flex align-items-start gap-3">
                                    <div class="eval-dec-dot flex-shrink-0 mt-1"
                                        style="width:18px;height:18px;border-radius:50%;border:2px solid #d1d5db;
                           display:flex;align-items:center;justify-content:center">
                                        <div class="eval-dec-inner"
                                            style="width:8px;height:8px;border-radius:50%;background:transparent;transition:background .15s">
                                        </div>
                                    </div>
                                    <div>
                                        <div class="fw-bold d-flex align-items-center gap-2" style="font-size:13px">
                                            <i class="bi bi-x-octagon-fill text-danger"></i>
                                            TIDAK LULUS - Putus Kontrak (Paklaring)
                                        </div>
                                        <div class="text-muted mt-1" style="font-size:12px">
                                            Kontrak karyawan diakhiri.
                                            <strong class="text-danger">Surat Keterangan Kerja (Paklaring)</strong>
                                            akan diterbitkan.
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- 3. EXTEND PROBATION --}}
                            <div class="eval-decision-opt rounded-3 p-3" data-value="Extend"
                                style="border:2px solid #e5e7eb;cursor:pointer;transition:all .15s"
                                onclick="selectEvalDecision(this)">
                                <div class="d-flex align-items-start gap-3">
                                    <div class="eval-dec-dot flex-shrink-0 mt-1"
                                        style="width:18px;height:18px;border-radius:50%;border:2px solid #d1d5db;
                           display:flex;align-items:center;justify-content:center">
                                        <div class="eval-dec-inner"
                                            style="width:8px;height:8px;border-radius:50%;background:transparent;transition:background .15s">
                                        </div>
                                    </div>
                                    <div>
                                        <div class="fw-bold d-flex align-items-center gap-2" style="font-size:13px">
                                            <i class="bi bi-arrow-repeat text-warning"></i>
                                            EXTEND - Perpanjang Masa Probation
                                        </div>
                                        <div class="text-muted mt-1" style="font-size:12px">
                                            Masa probation diperpanjang untuk evaluasi ulang. <em>(Tidak ada dokumen
                                                PDF.)</em>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Inline validation error for decision --}}
                        <div id="evalDecisionError"
                            style="display:none;font-size:12px;color:#dc2626;margin-top:6px;margin-bottom:4px">
                            <i class="bi bi-exclamation-triangle-fill me-1"></i>
                            <span id="evalDecisionErrorMsg">Silakan pilih keputusan evaluasi.</span>
                        </div>
                    </div>

                    {{-- ═══════════════════════════════════════════════════════
               SECTION D — DETAIL PERPANJANGAN (conditional: Extend only)
               ═══════════════════════════════════════════════════════ --}}
                    <div id="evalExtendSection" style="display:none" class="mx-4 mb-3">
                        <div class="p-3 rounded-3" style="background:#fffbeb;border:1px solid #fde68a">
                            <div class="fw-semibold mb-3 d-flex align-items-center gap-2"
                                style="font-size:12.5px;color:#92400e">
                                <i class="bi bi-calendar-range"></i>
                                Detail Perpanjangan <span class="text-danger">*</span>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold" style="font-size:12px">
                                    Durasi Perpanjangan <span class="text-danger">*</span>
                                </label>
                                <div class="d-flex gap-2 flex-wrap">
                                    @foreach (['3 Bulan', '6 Bulan', '12 Bulan'] as $dur)
                                        <button type="button" class="ext-dur-btn btn btn-sm"
                                            data-dur="{{ $dur }}"
                                            style="border:2px solid #d1d5db;font-weight:600;font-size:13px;
                           padding:6px 16px;border-radius:8px;background:#fff;
                           transition:all .15s"
                                            onclick="selectExtDuration('{{ $dur }}', this)">
                                            {{ $dur }}
                                        </button>
                                    @endforeach
                                </div>
                                <div id="evalExtDurError"
                                    style="display:none;font-size:12px;color:#dc2626;margin-top:4px">
                                    <i class="bi bi-exclamation-triangle-fill me-1"></i>Durasi perpanjangan wajib
                                    dipilih.
                                </div>
                            </div>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold" style="font-size:12px">
                                        Tanggal Mulai Kontrak Baru <span class="text-danger">*</span>
                                    </label>
                                    <input type="date" class="form-control form-control-sm" id="evalExtStart"
                                        onchange="calcExtendEnd();updateConfirmBtn()" />
                                    <div id="evalExtStartError"
                                        style="display:none;font-size:12px;color:#dc2626;margin-top:4px">
                                        <i class="bi bi-exclamation-triangle-fill me-1"></i>Tanggal mulai wajib diisi.
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold" style="font-size:12px">Tanggal Akhir Kontrak
                                        Baru</label>
                                    <input type="date" class="form-control form-control-sm" id="evalExtEnd"
                                        readonly style="background:#f9fafb" />
                                    <div class="form-text" id="evalExtEndHint" style="font-size:11px">
                                        Auto-dihitung dari durasi yang dipilih.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- ═══════════════════════════════════════════════════════
               SECTION E — CATATAN EVALUATOR (optional)
               ═══════════════════════════════════════════════════════ --}}
                    <div class="px-4 pb-4">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <span
                                class="d-flex align-items-center justify-content-center text-white fw-bold rounded-circle"
                                style="width:22px;height:22px;font-size:11px;background:#eb1c24;flex-shrink:0">E</span>
                            <span class="fw-semibold" style="font-size:13px;color:#374151">
                                Catatan Evaluator
                                <span class="text-muted fw-normal">(opsional)</span>
                            </span>
                        </div>
                        <textarea class="form-control form-control-sm" id="evalCatatan" rows="2"
                            placeholder="Contoh: Karyawan menunjukkan peningkatan signifikan dalam koordinasi tim…"
                            style="font-size:13px;resize:vertical"></textarea>
                    </div>

                    {{-- ═══════════════════════════════════════════════════════
               SECTION F — PERSETUJUAN / APPROVAL (Performance Review template)
               Department Manager/Head + HRBP / HR & Legal Manager
               Stored as metadata alongside evaluation — no new approval workflow.
               ═══════════════════════════════════════════════════════ --}}
                    <div class="px-4 pb-4">
                        <div class="d-flex align-items-center gap-2 mb-3">
                            <span
                                class="d-flex align-items-center justify-content-center text-white fw-bold rounded-circle"
                                style="width:22px;height:22px;font-size:11px;background:#eb1c24;flex-shrink:0">F</span>
                            <span class="fw-semibold" style="font-size:13px;color:#374151">
                                Persetujuan
                                <span class="text-muted fw-normal">(opsional)</span>
                            </span>
                        </div>
                        <div class="p-3 rounded-3" style="background:#f8fafc;border:1px solid #e2e8f0">
                            <div class="row g-3">
                                {{-- Department Manager/Head --}}
                                <div class="col-md-6">
                                    <div class="fw-semibold mb-2"
                                        style="font-size:12px;color:#374151;border-bottom:1px solid #e2e8f0;padding-bottom:4px">
                                        <i class="bi bi-person-badge me-1 text-primary"></i>Department Manager / Head
                                    </div>
                                    <div class="d-flex gap-2 mb-2">
                                        <button type="button" class="approval-btn btn btn-sm flex-fill"
                                            data-target="evalApprovalDept" data-value="Setuju"
                                            style="border:2px solid #d1d5db;font-size:12px;font-weight:600;background:#fff"
                                            onclick="selectApproval('evalApprovalDept','Setuju',this)">
                                            <i class="bi bi-check-circle me-1"></i>Setuju
                                        </button>
                                        <button type="button" class="approval-btn btn btn-sm flex-fill"
                                            data-target="evalApprovalDept" data-value="Tidak"
                                            style="border:2px solid #d1d5db;font-size:12px;font-weight:600;background:#fff"
                                            onclick="selectApproval('evalApprovalDept','Tidak',this)">
                                            <i class="bi bi-x-circle me-1"></i>Tidak
                                        </button>
                                    </div>
                                    <input type="text" class="form-control form-control-sm mb-2"
                                        id="evalApprovalDeptNameInput" placeholder="Nama Department Manager/Head"
                                        style="font-size:12px"
                                        oninput="document.getElementById('evalApprovalDeptName').value=this.value" />
                                    <input type="date" class="form-control form-control-sm"
                                        id="evalApprovalDeptDateInput" style="font-size:12px"
                                        onchange="document.getElementById('evalApprovalDeptDate').value=this.value" />
                                </div>
                                {{-- HRBP / HR & Legal Manager --}}
                                <div class="col-md-6">
                                    <div class="fw-semibold mb-2"
                                        style="font-size:12px;color:#374151;border-bottom:1px solid #e2e8f0;padding-bottom:4px">
                                        <i class="bi bi-person-badge me-1 text-success"></i>HRBP / HR &amp; Legal
                                        Manager
                                    </div>
                                    <div class="d-flex gap-2 mb-2">
                                        <button type="button" class="approval-btn btn btn-sm flex-fill"
                                            data-target="evalApprovalHrbp" data-value="Setuju"
                                            style="border:2px solid #d1d5db;font-size:12px;font-weight:600;background:#fff"
                                            onclick="selectApproval('evalApprovalHrbp','Setuju',this)">
                                            <i class="bi bi-check-circle me-1"></i>Setuju
                                        </button>
                                        <button type="button" class="approval-btn btn btn-sm flex-fill"
                                            data-target="evalApprovalHrbp" data-value="Tidak"
                                            style="border:2px solid #d1d5db;font-size:12px;font-weight:600;background:#fff"
                                            onclick="selectApproval('evalApprovalHrbp','Tidak',this)">
                                            <i class="bi bi-x-circle me-1"></i>Tidak
                                        </button>
                                    </div>
                                    <input type="text" class="form-control form-control-sm mb-2"
                                        id="evalApprovalHrbpNameInput" placeholder="Nama HRBP / HR & Legal Manager"
                                        style="font-size:12px"
                                        oninput="document.getElementById('evalApprovalHrbpName').value=this.value" />
                                    <input type="date" class="form-control form-control-sm"
                                        id="evalApprovalHrbpDateInput" style="font-size:12px"
                                        onchange="document.getElementById('evalApprovalHrbpDate').value=this.value" />
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- ═══════════════════════════════════════════════════════
               PRE-SUBMIT SUMMARY (appears when decision selected)
               ═══════════════════════════════════════════════════════ --}}
                    <div id="evalSubmitSummary" class="mx-4 mb-4 p-3 rounded-3"
                        style="display:none;border:1px solid #e5e7eb;background:#f9fafb">
                        <div class="fw-semibold mb-2"
                            style="font-size:12px;color:#374151;text-transform:uppercase;letter-spacing:.04em">
                            <i class="bi bi-check2-all me-1 text-success"></i>Ringkasan Evaluasi
                        </div>
                        <div class="row g-2" style="font-size:12.5px">
                            <div class="col-5 text-muted">Karyawan</div>
                            <div class="col-7 fw-semibold" id="summaryEmpName">—</div>
                            <div class="col-5 text-muted">Total Score</div>
                            <div class="col-7 fw-semibold" id="summaryScore">—</div>
                            <div class="col-5 text-muted">Kategori</div>
                            <div class="col-7" id="summaryCategory">—</div>
                            <div class="col-5 text-muted">Keputusan</div>
                            <div class="col-7 fw-bold" id="summaryDecision">—</div>
                            <div class="col-5 text-muted" id="summaryExtLbl" style="display:none">Durasi Perpanjangan
                            </div>
                            <div class="col-7 fw-semibold" id="summaryExt" style="display:none">—</div>
                        </div>
                    </div>

                    {{-- ── Backend / server error banner ─────────────────── --}}
                    <div id="evalServerError"
                        class="mx-4 mb-3 alert alert-danger py-2 px-3 d-flex align-items-center gap-2"
                        style="display:none!important;font-size:12.5px;border-radius:8px">
                        <i class="bi bi-exclamation-triangle-fill" style="flex-shrink:0"></i>
                        <span id="evalServerErrorMsg">Terjadi kesalahan. Silakan coba lagi.</span>
                    </div>

                    {{-- ── Indicator incomplete warning ─────────────────── --}}
                    <div id="evalIndicatorError"
                        class="mx-4 mb-3 alert alert-warning py-2 px-3 d-flex align-items-center gap-2"
                        style="display:none;font-size:12.5px;border-radius:8px">
                        <i class="bi bi-exclamation-triangle-fill" style="flex-shrink:0;color:#d97706"></i>
                        <span>Semua indikator harus dinilai dengan memilih
                            <strong><i class="bi bi-check-circle me-1"></i></strong> atau <strong> <i
                                    class="bi bi-x-circle me-1"></i>
                            </strong>.</span>
                    </div>

                </div>{{-- /#evalEmpPreview --}}
            </div>{{-- /.modal-body --}}

            {{-- ── FOOTER ───────────────────────────────────────────────── --}}
            <div class="modal-footer px-4 py-3" style="border-top:1px solid #f3f4f6">
                <button type="button" class="btn btn-outline-secondary btn-sm px-4"
                    data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-sm text-white fw-semibold px-4" id="btnConfirmProbationEval"
                    style="background:#eb1c24;border:none;min-width:180px" disabled onclick="submitProbationEval()">
                    <span id="btnEvalText">
                        <i class="bi bi-clipboard-check me-1"></i>Simpan Evaluasi
                    </span>
                    <span id="btnEvalLoading" style="display:none">
                        <span class="spinner-border spinner-border-sm me-1" style="width:14px;height:14px"></span>
                        <span id="btnEvalLoadingText">Menyimpan…</span>
                    </span>
                </button>
            </div>

        </div>
    </div>
</div>

{{-- ================================================================
     JAVASCRIPT — Probation Eval Modal
     ================================================================ --}}
<script>
    (function() {
        'use strict';

        // ── Constants ──────────────────────────────────────────────────
        var COMPETENCIES = {
            integrity: ['integrity_1', 'integrity_2', 'integrity_3', 'integrity_4'],
            ci: ['ci_1', 'ci_2', 'ci_3', 'ci_4'],
            ee: ['ee_1', 'ee_2'],
            tw: ['tw_1', 'tw_2', 'tw_3'],
        };

        var ALL_KEYS = [
            'integrity_1', 'integrity_2', 'integrity_3', 'integrity_4',
            'ci_1', 'ci_2', 'ci_3', 'ci_4',
            'ee_1', 'ee_2',
            'tw_1', 'tw_2', 'tw_3',
        ];

        // ── Decision helpers (substring-collision safe — isPutus before isLulus) ──
        function classifyDecision(val) {
            var isPutus = val === 'Tidak Lulus' ||
                val === 'Tidak Lolos → Putus Kontrak (Paklaring)' ||
                val.indexOf('Putus Kontrak') !== -1 ||
                val.indexOf('Paklaring') !== -1;
            var isPerp = !isPutus && (
                val === 'Extend' ||
                val === 'Perpanjang Kontrak' ||
                val.indexOf('Perpanjang') !== -1 ||
                val.indexOf('Evaluasi Ulang') !== -1
            );
            var isLulus = !isPutus && !isPerp && (
                val === 'Lulus' ||
                val === 'Diangkat sebagai Karyawan Tetap' ||
                val === 'Lulus → Karyawan Tetap' ||
                val.indexOf('Diangkat') !== -1 ||
                val.indexOf('Tetap') !== -1
            );
            return {
                isLulus: isLulus,
                isPutus: isPutus,
                isPerp: isPerp
            };
        }

        // ── Category ────────────────────────────────────────────────────
        function categoryFromTotal(n) {
            if (n >= 11) return {
                label: 'Sangat Baik',
                bg: '#d1fae5',
                color: '#166534'
            };
            if (n >= 8) return {
                label: 'Baik',
                bg: '#dbeafe',
                color: '#1d4ed8'
            };
            if (n >= 6) return {
                label: 'Cukup',
                bg: '#fef3c7',
                color: '#92400e'
            };
            return {
                label: 'Kurang',
                bg: '#fee2e2',
                color: '#991b1b'
            };
        }

        // ── Set a single indicator to explicit ✓ (1) or ✗ (0) ────────
        // 3 states: "" = belum dinilai, "1" = ✓, "0" = X
        window.setIndicator = function(key, val, btnEl) {
            var hiddenEl = document.getElementById('ind_' + key);
            if (!hiddenEl) return;

            // Toggle off if same button already selected
            var isAlreadySelected = hiddenEl.value === val;
            var newVal = isAlreadySelected ? '' : val;
            hiddenEl.value = newVal;

            // Update button visual states
            var compBlock = btnEl.closest('.eval-ind-row');
            if (compBlock) {
                var checkBtn = compBlock.querySelector('.ind-btn-check');
                var crossBtn = compBlock.querySelector('.ind-btn-cross');

                // Reset both to neutral
                if (checkBtn) {
                    checkBtn.style.borderColor = '#d1d5db';
                    checkBtn.style.background = '#fff';
                    checkBtn.style.color = '#374151';
                }
                if (crossBtn) {
                    crossBtn.style.borderColor = '#d1d5db';
                    crossBtn.style.background = '#fff';
                    crossBtn.style.color = '#374151';
                }

                // Activate selected button (if not toggled off)
                if (newVal === '1' && checkBtn) {
                    checkBtn.style.borderColor = '#166534';
                    checkBtn.style.background = '#f0fdf4';
                    checkBtn.style.color = '#166534';
                } else if (newVal === '0' && crossBtn) {
                    crossBtn.style.borderColor = '#991b1b';
                    crossBtn.style.background = '#fef2f2';
                    crossBtn.style.color = '#991b1b';
                }
            }

            // Hide per-indicator error if now rated
            var errEl = document.getElementById('ind_err_' + key);
            if (errEl) errEl.style.display = 'none';

            recalcScores();
        };

        function recalcScores() {
            // Per-competency totals — only count explicit "1" (✓)
            // Show "–" if any indicator in competency not yet rated
            Object.keys(COMPETENCIES).forEach(function(comp) {
                var keys = COMPETENCIES[comp];
                var allRated = keys.every(function(k) {
                    var el = document.getElementById('ind_' + k);
                    return el && el.value !== '';
                });
                var count = keys.reduce(function(acc, k) {
                    var el = document.getElementById('ind_' + k);
                    return acc + (el && el.value === '1' ? 1 : 0);
                }, 0);
                var badge = document.getElementById('badge_' + comp);
                if (badge) badge.textContent = allRated ? count : '–';
            });

            // Overall total
            var allRatedGlobal = ALL_KEYS.every(function(k) {
                var el = document.getElementById('ind_' + k);
                return el && el.value !== '';
            });
            var total = ALL_KEYS.reduce(function(acc, k) {
                var el = document.getElementById('ind_' + k);
                return acc + (el && el.value === '1' ? 1 : 0);
            }, 0);

            var totalEl = document.getElementById('evalOverallTotal');
            if (totalEl) totalEl.textContent = allRatedGlobal ? total : '–';

            // Category badge — only show when all rated
            var cat = allRatedGlobal ? categoryFromTotal(total) : null;
            var catEl = document.getElementById('evalCategoryBadge');
            if (catEl) {
                if (cat) {
                    catEl.textContent = cat.label;
                    catEl.style.background = cat.bg;
                    catEl.style.color = cat.color;
                } else {
                    catEl.textContent = '—';
                    catEl.style.background = '#e5e7eb';
                    catEl.style.color = '#6b7280';
                }
            }

            // Score feedback
            var feedbackEl = document.getElementById('evalScoreFeedback');
            if (feedbackEl) {
                var ratedCount = ALL_KEYS.filter(function(k) {
                    var el = document.getElementById('ind_' + k);
                    return el && el.value !== '';
                }).length;
                if (ratedCount === 0) {
                    feedbackEl.textContent = 'Pilih ✓ atau ✗ pada setiap behavioral indicator di atas.';
                    feedbackEl.style.color = '#9ca3af';
                } else if (!allRatedGlobal) {
                    feedbackEl.textContent = ratedCount + ' dari 13 indikator sudah dinilai. ' + (13 - ratedCount) +
                        ' belum dinilai.';
                    feedbackEl.style.color = '#d97706';
                } else {
                    var cat2 = categoryFromTotal(total);
                    feedbackEl.textContent = total + ' dari 13 indikator terpenuhi (✓). Kategori: ' + cat2.label +
                        '.';
                    feedbackEl.style.color = cat2.color;
                }
            }

            // Lulus gate hint — only when all rated
            var gateEl = document.getElementById('evalLulusScoreGate');
            if (gateEl) gateEl.style.display = (allRatedGlobal && total < 8) ? 'block' : 'none';

            updateConfirmBtn();
            updateSummary();
        }

        // ── Decision card click ─────────────────────────────────────────
        window.selectEvalDecision = function(el) {
            var val = el.getAttribute('data-value');
            var cls = classifyDecision(val);

            // Update hidden field
            document.getElementById('evalDecisionValue').value = val;

            // Visual state for all cards
            var colorMap = {
                'Diangkat sebagai Karyawan Tetap': {
                    border: '#166534',
                    bg: '#f0fdf4'
                },
                'Tidak Lulus': {
                    border: '#991b1b',
                    bg: '#fef2f2'
                },
                'Perpanjang Kontrak': {
                    border: '#d97706',
                    bg: '#fffbeb'
                },
            };
            document.querySelectorAll('.eval-decision-opt').forEach(function(opt) {
                var isThis = (opt === el);
                var c = colorMap[opt.getAttribute('data-value')] || {};
                opt.style.borderColor = isThis ? (c.border || '#eb1c24') : '#e5e7eb';
                opt.style.background = isThis ? (c.bg || '#f5f3ff') : '';
                var dot = opt.querySelector('.eval-dec-dot');
                var inner = opt.querySelector('.eval-dec-inner');
                if (dot) dot.style.borderColor = isThis ? (c.border || '#eb1c24') : '#d1d5db';
                if (inner) inner.style.background = isThis ? (c.border || '#eb1c24') : 'transparent';
            });

            // Show / hide extend section
            var extSec = document.getElementById('evalExtendSection');
            if (extSec) extSec.style.display = cls.isPerp ? 'block' : 'none';

            // Auto-fill today as extension start if not set
            if (cls.isPerp) {
                var startEl = document.getElementById('evalExtStart');
                if (startEl && !startEl.value) {
                    startEl.value = new Date().toISOString().split('T')[0];
                    calcExtendEnd();
                }
            }

            // Clear decision error
            hideInlineError('evalDecisionError');

            updateConfirmBtn();
            updateSummary();
        };

        // ── Extension duration select ────────────────────────────────────
        window.selectExtDuration = function(dur, btnEl) {
            document.getElementById('evalExtDuration').value = dur;
            document.querySelectorAll('.ext-dur-btn').forEach(function(b) {
                if (b.getAttribute('data-dur') === dur) {
                    b.style.borderColor = '#d97706';
                    b.style.background = '#fef3c7';
                    b.style.color = '#92400e';
                } else {
                    b.style.borderColor = '#d1d5db';
                    b.style.background = '#fff';
                    b.style.color = '';
                }
            });
            hideInlineError('evalExtDurError');
            calcExtendEnd();
            updateConfirmBtn();
            updateSummary();
        };

        // ── Approval sign-off button (Section F) ─────────────────────────
        window.selectApproval = function(hiddenId, value, btnEl) {
            var hidden = document.getElementById(hiddenId);
            if (hidden) hidden.value = value;

            // Style all sibling buttons for this target
            var isSetuju = (value === 'Setuju');
            document.querySelectorAll('.approval-btn[data-target="' + hiddenId + '"]').forEach(function(b) {
                var bVal = b.getAttribute('data-value');
                var isThis = (b === btnEl);
                if (isThis && isSetuju) {
                    b.style.borderColor = '#166534';
                    b.style.background = '#f0fdf4';
                    b.style.color = '#166534';
                } else if (isThis && !isSetuju) {
                    b.style.borderColor = '#991b1b';
                    b.style.background = '#fef2f2';
                    b.style.color = '#991b1b';
                } else {
                    b.style.borderColor = '#d1d5db';
                    b.style.background = '#fff';
                    b.style.color = '';
                }
            });
        };

        // ── Auto-calculate extension end date ────────────────────────────
        window.calcExtendEnd = function() {
            var dur = document.getElementById('evalExtDuration').value;
            var startEl = document.getElementById('evalExtStart');
            var endEl = document.getElementById('evalExtEnd');
            var hintEl = document.getElementById('evalExtEndHint');
            if (!dur || !startEl || !endEl || !startEl.value) return;
            var m = dur.match(/^(\d+)\s*Bulan/i);
            if (!m) return;
            var d = new Date(startEl.value);
            d.setMonth(d.getMonth() + parseInt(m[1], 10));
            endEl.value = d.toISOString().split('T')[0];
            if (hintEl) hintEl.textContent = 'Kontrak baru berakhir ' + endEl.value + ' (' + dur + ').';
            updateConfirmBtn();
            updateSummary();
        };

        // ── Button state ────────────────────────────────────────────────
        window.updateConfirmBtn = function() {
            var btn = document.getElementById('btnConfirmProbationEval');
            var tEl = document.getElementById('btnEvalText');
            if (!btn) return;

            var hasEmp = !!document.getElementById('evalEmployeeId').value;
            var decVal = document.getElementById('evalDecisionValue').value || '';
            var cls = decVal ? classifyDecision(decVal) : {};

            // All 13 indicators MUST be explicitly rated ("1" or "0") — not ""
            var allRated = ALL_KEYS.every(function(k) {
                var el = document.getElementById('ind_' + k);
                return el && el.value !== '';
            });

            var total = ALL_KEYS.reduce(function(acc, k) {
                var el = document.getElementById('ind_' + k);
                return acc + (el && el.value === '1' ? 1 : 0);
            }, 0);

            var extValid = true;
            if (cls.isPerp) {
                extValid = !!(document.getElementById('evalExtDuration').value) &&
                    !!(document.getElementById('evalExtStart').value);
            }

            var lulusOk = !cls.isLulus || (allRated && total >= 8);

            // Button label + color
            if (tEl) {
                if (cls.isLulus) {
                    btn.style.background = (lulusOk && allRated) ? '#166534' : '#9ca3af';
                    tEl.innerHTML = '<i class="bi bi-file-earmark-check me-1"></i>Simpan & Terbitkan SK Tetap';
                } else if (cls.isPutus) {
                    btn.style.background = '#991b1b';
                    tEl.innerHTML = '<i class="bi bi-file-earmark-x me-1"></i>Simpan & Terbitkan Paklaring';
                } else if (cls.isPerp) {
                    btn.style.background = '#d97706';
                    tEl.innerHTML = '<i class="bi bi-calendar-plus me-1"></i>Simpan Perpanjangan';
                } else {
                    btn.style.background = '#eb1c24';
                    tEl.innerHTML = '<i class="bi bi-clipboard-check me-1"></i>Simpan Evaluasi';
                }
            }

            btn.disabled = !(hasEmp && !!decVal && allRated && extValid && lulusOk);
        };

        // ── Pre-submit summary panel ─────────────────────────────────────
        function updateSummary() {
            var decVal = document.getElementById('evalDecisionValue').value || '';
            var sumEl = document.getElementById('evalSubmitSummary');
            if (!sumEl) return;

            if (!decVal || !document.getElementById('evalEmployeeId').value) {
                sumEl.style.display = 'none';
                return;
            }

            // Compute total from hidden fields directly (not textContent which may be "–")
            var total = ALL_KEYS.reduce(function(acc, k) {
                var el = document.getElementById('ind_' + k);
                return acc + (el && el.value === '1' ? 1 : 0);
            }, 0);
            var allRated = ALL_KEYS.every(function(k) {
                var el = document.getElementById('ind_' + k);
                return el && el.value !== '';
            });
            var cat = categoryFromTotal(total);
            var cls = classifyDecision(decVal);

            var decLabel = cls.isLulus ? '✓ LULUS - SK Pengangkatan' :
                cls.isPutus ? '✗ TIDAK LULUS - Paklaring' :
                '↺ EXTEND - Perpanjang Probation';
            var decColor = cls.isLulus ? '#166534' : cls.isPutus ? '#991b1b' : '#d97706';

            var nameEl = document.getElementById('summaryEmpName');
            var scoreEl = document.getElementById('summaryScore');
            var catEl = document.getElementById('summaryCategory');
            var decEl = document.getElementById('summaryDecision');
            var extLbl = document.getElementById('summaryExtLbl');
            var extEl = document.getElementById('summaryExt');

            if (nameEl) nameEl.textContent = document.getElementById('evalEmpName').textContent || '—';
            if (scoreEl) scoreEl.textContent = allRated ? (total + ' / 13 (' + cat.label + ')') :
                '— / 13 (belum semua dinilai)';
            if (catEl) {
                catEl.innerHTML = allRated ?
                    '<span class="badge rounded-pill px-2" style="background:' + cat.bg + ';color:' + cat.color +
                    ';font-size:11px">' + cat.label + '</span>' :
                    '<span class="badge rounded-pill px-2" style="background:#f3f4f6;color:#9ca3af;font-size:11px">Belum lengkap</span>';
            }
            if (decEl) {
                decEl.textContent = decLabel;
                decEl.style.color = decColor;
            }
            if (extLbl && extEl) {
                var dur = document.getElementById('evalExtDuration').value || '';
                extLbl.style.display = cls.isPerp && dur ? '' : 'none';
                extEl.style.display = cls.isPerp && dur ? '' : 'none';
                extEl.textContent = dur;
            }
            sumEl.style.display = '';
        }

        // ── Inline error helpers ─────────────────────────────────────────
        function showInlineError(elId, msg) {
            var el = document.getElementById(elId);
            if (!el) return;
            if (msg) {
                var msgEl = el.querySelector('span') || el;
                if (msgEl !== el) msgEl.textContent = msg;
            }
            el.style.display = '';
        }

        function hideInlineError(elId) {
            var el = document.getElementById(elId);
            if (el) el.style.display = 'none';
        }

        function showServerError(msg) {
            var el = document.getElementById('evalServerError');
            var msgEl = document.getElementById('evalServerErrorMsg');
            if (msgEl) msgEl.textContent = msg || 'Terjadi kesalahan. Silakan coba lagi.';
            if (el) el.style.removeProperty('display'); // override display:none!important
        }

        function hideServerError() {
            var el = document.getElementById('evalServerError');
            if (el) el.style.setProperty('display', 'none', 'important');
        }

        // ── Set loading state ────────────────────────────────────────────
        function setLoading(state, label) {
            var btn = document.getElementById('btnConfirmProbationEval');
            var tEl = document.getElementById('btnEvalText');
            var lEl = document.getElementById('btnEvalLoading');
            var lblEl = document.getElementById('btnEvalLoadingText');
            if (!btn) return;
            btn.disabled = state;
            if (tEl) tEl.style.display = state ? 'none' : 'inline';
            if (lEl) lEl.style.display = state ? 'inline-flex' : 'none';
            if (state && lblEl) lblEl.textContent = label || 'Menyimpan…';
        }

        // ── MAIN SUBMIT via fetch (fixes infinite loading) ───────────────
        window.submitProbationEval = function() {
            // 1. Client-side validation
            var empId = document.getElementById('evalEmployeeId').value;
            var decVal = document.getElementById('evalDecisionValue').value;

            if (!empId) return;

            if (!decVal) {
                showInlineError('evalDecisionError', 'Silakan pilih keputusan evaluasi.');
                document.getElementById('evalDecisionOptions').scrollIntoView({
                    behavior: 'smooth',
                    block: 'center'
                });
                return;
            }

            // Validate ALL indicators must be rated ("1" or "0") — not ""
            var unratedKeys = ALL_KEYS.filter(function(k) {
                var el = document.getElementById('ind_' + k);
                return !el || el.value === '';
            });
            if (unratedKeys.length > 0) {
                // Show global indicator error
                var indErrEl = document.getElementById('evalIndicatorError');
                if (indErrEl) indErrEl.style.display = '';
                // Highlight each unrated indicator's row error
                unratedKeys.forEach(function(k) {
                    var errEl = document.getElementById('ind_err_' + k);
                    if (errEl) errEl.style.display = '';
                });
                // Scroll to first unrated
                var firstErr = document.getElementById('ind_err_' + unratedKeys[0]);
                if (firstErr) firstErr.scrollIntoView({
                    behavior: 'smooth',
                    block: 'center'
                });
                return;
            }
            // Hide indicator error if all rated
            var indErrEl = document.getElementById('evalIndicatorError');
            if (indErrEl) indErrEl.style.display = 'none';

            // Compute total from hidden fields (not textContent)
            var total = ALL_KEYS.reduce(function(acc, k) {
                var el = document.getElementById('ind_' + k);
                return acc + (el && el.value === '1' ? 1 : 0);
            }, 0);

            var cls = classifyDecision(decVal);

            if (cls.isLulus && total < 8) {
                showInlineError('evalDecisionError',
                    'Keputusan Lulus membutuhkan minimal 8 indikator terpenuhi (kategori Baik/Sangat Baik). Total saat ini: ' +
                    total + '/13.');
                return;
            }

            if (cls.isPerp) {
                var dur = document.getElementById('evalExtDuration').value;
                var start = document.getElementById('evalExtStart').value;
                var hasError = false;
                if (!dur) {
                    showInlineError('evalExtDurError');
                    hasError = true;
                }
                if (!start) {
                    showInlineError('evalExtStartError');
                    hasError = true;
                }
                if (hasError) {
                    document.getElementById('evalExtendSection').scrollIntoView({
                        behavior: 'smooth',
                        block: 'center'
                    });
                    return;
                }
            }

            hideServerError();

            // 2. Build FormData — indicators sent as "1" or "0" (string, explicit)
            var fd = new FormData();
            fd.append('_token', document.querySelector('meta[name="csrf-token"]').content);
            fd.append('decision', decVal);
            fd.append('recruitment_id', document.getElementById('evalRecruitmentId').value || '');
            fd.append('notes', document.getElementById('evalCatatan').value || '');

            ALL_KEYS.forEach(function(k) {
                var el = document.getElementById('ind_' + k);
                // Send explicit "1" or "0" — never send "" to backend (already validated above)
                fd.append('indicators[' + k + ']', el ? el.value : '0');
            });

            if (cls.isPerp) {
                fd.append('extension_duration', document.getElementById('evalExtDuration').value || '');
                fd.append('extension_start', document.getElementById('evalExtStart').value || '');
                fd.append('extension_end', document.getElementById('evalExtEnd').value || '');
            }

            // Approval sign-off fields (Performance Review template Section F — always sent)
            fd.append('reviewer_name', document.getElementById('evalEmpReviewer').textContent.trim() === '—' ?
                '' : document.getElementById('evalEmpReviewer').textContent.trim());
            fd.append('approval_dept', document.getElementById('evalApprovalDept').value || '');
            fd.append('approval_dept_name', document.getElementById('evalApprovalDeptName').value || '');
            fd.append('approval_dept_date', document.getElementById('evalApprovalDeptDate').value || '');
            fd.append('approval_hrbp', document.getElementById('evalApprovalHrbp').value || '');
            fd.append('approval_hrbp_name', document.getElementById('evalApprovalHrbpName').value || '');
            fd.append('approval_hrbp_date', document.getElementById('evalApprovalHrbpDate').value || '');

            // 3. POST via fetch — controller returns JSON when X-Requested-With is set
            var url = '/hr/probation/' + encodeURIComponent(empId) + '/evaluate';

            setLoading(true, 'Menyimpan data evaluasi…');

            fetch(url, {
                    method: 'POST',
                    body: fd,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                })
                .then(function(response) {
                    // Parse JSON regardless of HTTP status
                    return response.json().then(function(data) {
                        return {
                            status: response.status,
                            data: data
                        };
                    });
                })
                .then(function(res) {
                    var data = res.data;
                    var status = res.status;

                    if (status === 422) {
                        // Validation errors from backend
                        var msgs = [];
                        if (data.errors) {
                            Object.values(data.errors).forEach(function(arr) {
                                arr.forEach(function(m) {
                                    msgs.push(m);
                                });
                            });
                        }
                        showServerError(msgs.length ? msgs.join(' ') : (data.message || 'Validasi gagal.'));
                        setLoading(false);
                        return;
                    }

                    if (!data.success) {
                        showServerError(data.message || 'Evaluasi gagal disimpan.');
                        setLoading(false);
                        return;
                    }

                    // ── SUCCESS ──────────────────────────────────────────────
                    // Step A: trigger PDF downloads via anchor click (bypass popup blockers)
                    // For EXTEND both URLs are null → no PDF is generated at all.
                    function downloadPdf(url) {
                        if (!url) return;
                        var a = document.createElement('a');
                        a.href = url;
                        a.target = '_blank';
                        a.style.display = 'none';
                        document.body.appendChild(a);
                        a.click();
                        document.body.removeChild(a);
                    }

                    if (data.pdfUrl) {
                        setLoading(true, 'Membuat dokumen PDF…');
                        setTimeout(function() {
                            downloadPdf(data.pdfUrl);
                        }, 200);
                    }
                    if (data.evalPdfUrl) {
                        setLoading(true, 'Membuat dokumen PDF…');
                        setTimeout(function() {
                            downloadPdf(data.evalPdfUrl);
                        }, data.pdfUrl ? 600 : 200);
                    }

                    // Step B: close modal
                    var modalEl = document.getElementById('probationEvalModal');
                    var bsModal = modalEl ? bootstrap.Modal.getInstance(modalEl) : null;
                    if (bsModal) {
                        bsModal.hide();
                    }

                    // Step C: show toast
                    var msg = data.message || 'Evaluasi berhasil disimpan.';
                    if (data.pdfUrl) {
                        msg += data.decisionType === 'pass' ? ' SK Pengangkatan sedang diunduh.' :
                            data.decisionType === 'fail' ? ' Paklaring sedang diunduh.' :
                            '';
                    }
                    if (data.evalPdfUrl) {
                        msg += ' Formulir Performance Review sedang diunduh.';
                    }
                    if (typeof window.showToast === 'function') {
                        window.showToast(msg, 'success', 5000);
                    } else {
                        var flashArea = document.querySelector('.content-wrap .alert-success');
                        if (!flashArea) {
                            var div = document.createElement('div');
                            div.className = 'alert alert-success alert-dismissible fade show mx-3 mt-2';
                            div.style.fontSize = '13px';
                            div.innerHTML = '<i class="bi bi-check-circle-fill me-2"></i>' + msg +
                                '<button type="button" class="btn-close btn-sm" data-bs-dismiss="alert"></button>';
                            var wrap = document.querySelector('.content-wrap');
                            if (wrap) wrap.insertBefore(div, wrap.firstChild);
                        }
                    }

                    // Step D: reload page to refresh table + stats (after 600ms for UX)
                    setTimeout(function() {
                        window.location.reload();
                    }, 600);
                })
                .catch(function(err) {
                    // Network error or JSON parse failure
                    console.error('[ProbationEval] fetch error:', err);
                    showServerError('Koneksi gagal. Periksa jaringan dan coba lagi.');
                    setLoading(false);
                });
            // NOTE: no .finally() — loading is reset explicitly in each branch above
            // (success: page reloads so doesn't need reset; error: reset in catch/422)
        };

        // ── Employee search ──────────────────────────────────────────────
        window.handleEvalEmpSearch = function(query) {
            var q = (query || '').trim().toLowerCase();
            var dropdown = document.getElementById('evalEmpDropdown');
            var clearEl = document.getElementById('evalEmpSearchClear');
            if (clearEl) clearEl.style.display = q ? 'block' : 'none';
            if (!dropdown) return;
            if (!q) {
                dropdown.style.display = 'none';
                return;
            }

            var allEmps = window.__allProbationEmployees || [];
            var matched = allEmps.filter(function(e) {
                return (e.fullName || '').toLowerCase().indexOf(q) !== -1 ||
                    (e.employeeId || '').toLowerCase().indexOf(q) !== -1 ||
                    (e.jobPosition || '').toLowerCase().indexOf(q) !== -1;
            }).slice(0, 8);

            if (!matched.length) {
                dropdown.innerHTML = '<div class="px-3 py-2 text-muted" style="font-size:13px">' +
                    'Tidak ada karyawan probation ditemukan.</div>';
                dropdown.style.display = 'block';
                return;
            }

            dropdown.innerHTML = matched.map(function(e) {
                var initl = (e.fullName || 'E').replace(/\s+/g, ' ').trim().split(' ')
                    .map(function(w) {
                        return w[0];
                    }).join('').substring(0, 2).toUpperCase();
                var eJson = JSON.stringify(e).replace(/'/g, '&#39;').replace(/"/g, '&quot;');
                return '<div class="d-flex align-items-center gap-2 px-3 py-2 eval-search-row"' +
                    ' style="cursor:pointer;border-bottom:1px solid #f3f4f6;font-size:13px"' +
                    ' onmouseenter="this.style.background=\'#f5f3ff\'" onmouseleave="this.style.background=\'\'"' +
                    ' onclick=\'selectEvalEmployee(' + eJson.replace(/'/g, "&#39;") + ')\'>' +
                    '<div style="width:32px;height:32px;border-radius:8px;background:#eb1c24;color:#fff;' +
                    'font-size:11px;font-weight:700;flex-shrink:0;display:flex;align-items:center;justify-content:center">' +
                    initl + '</div>' +
                    '<div><div class="fw-semibold">' + (e.fullName || '—') + '</div>' +
                    '<div class="text-muted" style="font-size:11px">' +
                    (e.jobPositionLocation || e.jobPosition || '—') + ' · ' + (e.employeeId || '') +
                    '</div></div></div>';
            }).join('');
            dropdown.style.display = 'block';
        };

        window.clearEvalEmpSearch = function() {
            var s = document.getElementById('evalEmpSearch');
            var c = document.getElementById('evalEmpSearchClear');
            var d = document.getElementById('evalEmpDropdown');
            if (s) s.value = '';
            if (c) c.style.display = 'none';
            if (d) d.style.display = 'none';
            document.getElementById('evalEmpPreview').style.display = 'none';
            document.getElementById('evalEmployeeId').value = '';
            resetEvalFormState();
            updateConfirmBtn();
        };

        window.selectEvalEmployee = function(emp) {
            var dropdown = document.getElementById('evalEmpDropdown');
            var search = document.getElementById('evalEmpSearch');
            var clearEl = document.getElementById('evalEmpSearchClear');
            if (dropdown) dropdown.style.display = 'none';
            if (search) search.value = emp.fullName || '';
            if (clearEl) clearEl.style.display = 'block';

            document.getElementById('evalEmployeeId').value = emp.employeeId || '';
            document.getElementById('evalRecruitmentId').value = emp.recruitmentId || '';

            var tn = function(id, val) {
                var el = document.getElementById(id);
                if (el) el.textContent = val || '—';
            };
            tn('evalEmpName', emp.fullName);
            tn('evalEmpPosition', emp.jobPositionLocation || emp.jobPosition);
            tn('evalEmpDept', emp.department);
            tn('evalEmpLevel', emp.jobLevel);
            tn('evalEmpPT', emp.branchName);
            tn('evalEmpIdDisp', emp.employeeId);
            tn('evalEmpJoinDate', emp.joinDate);
            tn('evalEmpContractEnd', emp.endDateContract);
            tn('evalEmpReviewer', emp.directSuperior);

            var avatar = document.getElementById('evalEmpAvatar');
            if (avatar) {
                avatar.textContent = (emp.fullName || 'E').replace(/\s+/g, ' ').trim().split(' ')
                    .map(function(w) {
                        return w[0];
                    }).join('').substring(0, 2).toUpperCase();
            }

            document.getElementById('evalEmpPreview').style.display = 'block';
            resetEvalFormState();
            loadPreviousEvaluation(emp.employeeId || '');
        };

        function setPrefilledIndicator(key, value) {
            if (value !== '1' && value !== '0') return;
            var hiddenEl = document.getElementById('ind_' + key);
            var button = document.querySelector('.ind-btn-' + (value === '1' ? 'check' : 'cross') + '[data-key="' + key + '"]');
            if (!hiddenEl || !button) return;
            hiddenEl.value = value;
            var row = button.closest('.eval-ind-row');
            if (!row) return;
            var checkBtn = row.querySelector('.ind-btn-check');
            var crossBtn = row.querySelector('.ind-btn-cross');
            [checkBtn, crossBtn].forEach(function(btn) {
                if (btn) {
                    btn.style.borderColor = '#d1d5db';
                    btn.style.background = '#fff';
                    btn.style.color = '#374151';
                }
            });
            button.style.borderColor = value === '1' ? '#166534' : '#991b1b';
            button.style.background = value === '1' ? '#f0fdf4' : '#fef2f2';
            button.style.color = value === '1' ? '#166534' : '#991b1b';
        }

        function setPreviousDecisionOptions(canExtend) {
            var extendOption = document.querySelector('.eval-decision-opt[data-value="Extend"]');
            if (!extendOption) return;
            extendOption.style.display = canExtend ? '' : 'none';
            if (!canExtend && document.getElementById('evalDecisionValue').value === 'Extend') {
                document.getElementById('evalDecisionValue').value = '';
                document.getElementById('evalExtendSection').style.display = 'none';
            }
        }

        function loadPreviousEvaluation(employeeId) {
            setPreviousDecisionOptions(true);
            recalcScores();
            updateConfirmBtn();
            if (!employeeId) return;

            fetch('/hr/probation/' + encodeURIComponent(employeeId) + '/eval-history', {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            })
                .then(function(response) { return response.json(); })
                .then(function(data) {
                    var latest = (data.history || [])[0];
                    if (!latest) return;

                    var latestType = classifyDecision(latest.decision || '');
                    setPreviousDecisionOptions(!latestType.isPerp);
                    if (latestType.isLulus || latestType.isPutus) {
                        showServerError('Evaluasi probation ini sudah final dan tidak dapat dibuka kembali.');
                        return;
                    }

                    ALL_KEYS.forEach(function(key) {
                        setPrefilledIndicator(key, String(latest['ind_' + key] || ''));
                    });
                    var notes = document.getElementById('evalCatatan');
                    if (notes) notes.value = latest.evaluatorNotes || '';
                    var reviewer = document.getElementById('evalEmpReviewer');
                    if (reviewer && latest.reviewer_name) reviewer.textContent = latest.reviewer_name;
                    var approvalFields = [
                        ['evalApprovalDept', 'approval_dept'],
                        ['evalApprovalDeptName', 'approval_dept_name'],
                        ['evalApprovalDeptDate', 'approval_dept_date'],
                        ['evalApprovalHrbp', 'approval_hrbp'],
                        ['evalApprovalHrbpName', 'approval_hrbp_name'],
                        ['evalApprovalHrbpDate', 'approval_hrbp_date']
                    ];
                    approvalFields.forEach(function(field) {
                        var hidden = document.getElementById(field[0]);
                        if (hidden) hidden.value = latest[field[1]] || '';
                    });
                    [
                        ['evalApprovalDeptNameInput', latest.approval_dept_name],
                        ['evalApprovalDeptDateInput', latest.approval_dept_date],
                        ['evalApprovalHrbpNameInput', latest.approval_hrbp_name],
                        ['evalApprovalHrbpDateInput', latest.approval_hrbp_date]
                    ].forEach(function(field) {
                        var input = document.getElementById(field[0]);
                        if (input) input.value = field[1] || '';
                    });
                    document.querySelectorAll('.approval-btn').forEach(function(button) {
                        var target = button.getAttribute('data-target');
                        var selected = document.getElementById(target);
                        var active = selected && selected.value === button.getAttribute('data-value');
                        button.style.borderColor = active ? (selected.value === 'Setuju' ? '#166534' : '#991b1b') : '#d1d5db';
                        button.style.background = active ? (selected.value === 'Setuju' ? '#f0fdf4' : '#fef2f2') : '#fff';
                        button.style.color = active ? (selected.value === 'Setuju' ? '#166534' : '#991b1b') : '';
                    });
                    recalcScores();
                    updateConfirmBtn();
                })
                .catch(function(error) {
                    console.error('[ProbationEval] history fetch error:', error);
                    showServerError('Riwayat evaluasi tidak dapat dimuat. Silakan coba lagi.');
                });
        }

        // ── Full form state reset (indicators, decision, extension) ────
        function resetEvalFormState() {
            // Reset all indicators to "" (belum dinilai) and neutralize ✓/X buttons
            ALL_KEYS.forEach(function(k) {
                var hiddenEl = document.getElementById('ind_' + k);
                if (hiddenEl) hiddenEl.value = ''; // "" = belum dinilai (NOT "0" = X)

                // Reset ✓ button to neutral
                var checkBtn = document.querySelector('.ind-btn-check[data-key="' + k + '"]');
                if (checkBtn) {
                    checkBtn.style.borderColor = '#d1d5db';
                    checkBtn.style.background = '#fff';
                    checkBtn.style.color = '#374151';
                }
                // Reset ✗ button to neutral
                var crossBtn = document.querySelector('.ind-btn-cross[data-key="' + k + '"]');
                if (crossBtn) {
                    crossBtn.style.borderColor = '#d1d5db';
                    crossBtn.style.background = '#fff';
                    crossBtn.style.color = '#374151';
                }
                // Hide per-indicator error
                var errEl = document.getElementById('ind_err_' + k);
                if (errEl) errEl.style.display = 'none';
            });

            // Reset competency badges to "–"
            Object.keys(COMPETENCIES).forEach(function(comp) {
                var badge = document.getElementById('badge_' + comp);
                if (badge) badge.textContent = '–';
            });

            // Reset score summary
            var tot = document.getElementById('evalOverallTotal');
            if (tot) tot.textContent = '–';
            var catBadge = document.getElementById('evalCategoryBadge');
            if (catBadge) {
                catBadge.textContent = '—';
                catBadge.style.background = '#e5e7eb';
                catBadge.style.color = '#6b7280';
            }
            var feedbackEl = document.getElementById('evalScoreFeedback');
            if (feedbackEl) {
                feedbackEl.textContent = 'Pilih ✓ atau ✗ pada setiap behavioral indicator di atas.';
                feedbackEl.style.color = '#9ca3af';
            }

            // Reset indicator error banner
            var indErrEl = document.getElementById('evalIndicatorError');
            if (indErrEl) indErrEl.style.display = 'none';
            // Reset decision
            document.getElementById('evalDecisionValue').value = '';
            document.getElementById('evalExtDuration').value = '';
            setPreviousDecisionOptions(true);
            document.querySelectorAll('.eval-decision-opt').forEach(function(opt) {
                opt.style.borderColor = '#e5e7eb';
                opt.style.background = '';
                var dot = opt.querySelector('.eval-dec-dot');
                var inner = opt.querySelector('.eval-dec-inner');
                if (dot) dot.style.borderColor = '#d1d5db';
                if (inner) inner.style.background = 'transparent';
            });

            // Reset extend section
            var extSec = document.getElementById('evalExtendSection');
            if (extSec) extSec.style.display = 'none';
            document.querySelectorAll('.ext-dur-btn').forEach(function(b) {
                b.style.borderColor = '#d1d5db';
                b.style.background = '#fff';
                b.style.color = '';
            });
            var extStart = document.getElementById('evalExtStart');
            var extEnd = document.getElementById('evalExtEnd');
            if (extStart) extStart.value = '';
            if (extEnd) extEnd.value = '';

            // Reset notes
            var notes = document.getElementById('evalCatatan');
            if (notes) notes.value = '';

            // Reset approval section (Section F)
            var approvalHiddens = ['evalApprovalDept', 'evalApprovalDeptName', 'evalApprovalDeptDate',
                'evalApprovalHrbp', 'evalApprovalHrbpName', 'evalApprovalHrbpDate'
            ];
            approvalHiddens.forEach(function(id) {
                var el = document.getElementById(id);
                if (el) el.value = '';
            });
            var approvalInputs = ['evalApprovalDeptNameInput', 'evalApprovalDeptDateInput',
                'evalApprovalHrbpNameInput', 'evalApprovalHrbpDateInput'
            ];
            approvalInputs.forEach(function(id) {
                var el = document.getElementById(id);
                if (el) el.value = '';
            });
            document.querySelectorAll('.approval-btn').forEach(function(b) {
                b.style.borderColor = '#d1d5db';
                b.style.background = '#fff';
                b.style.color = '';
            });

            // Reset summary
            var sumEl = document.getElementById('evalSubmitSummary');
            if (sumEl) sumEl.style.display = 'none';

            // Reset errors
            ['evalDecisionError', 'evalExtDurError', 'evalExtStartError'].forEach(hideInlineError);
            var indErrElB = document.getElementById('evalIndicatorError');
            if (indErrElB) indErrElB.style.display = 'none';
            hideServerError();

            // Reset button
            var btn = document.getElementById('btnConfirmProbationEval');
            var tEl = document.getElementById('btnEvalText');
            if (btn) {
                btn.disabled = true;
                btn.style.background = '#eb1c24';
            }
            if (tEl) {
                tEl.innerHTML = '<i class="bi bi-clipboard-check me-1"></i>Simpan Evaluasi';
                tEl.style.display = 'inline';
            }
            var lEl = document.getElementById('btnEvalLoading');
            if (lEl) lEl.style.display = 'none';

            // Reset lulus gate hint
            var gateEl = document.getElementById('evalLulusScoreGate');
            if (gateEl) gateEl.style.display = 'none';
        }

        // ── Full modal reset (called on hidden.bs.modal) ────────────────
        window.resetProbationEvalModal = function() {
            var search = document.getElementById('evalEmpSearch');
            var clearEl = document.getElementById('evalEmpSearchClear');
            var dropEl = document.getElementById('evalEmpDropdown');
            var preview = document.getElementById('evalEmpPreview');
            if (search) search.value = '';
            if (clearEl) clearEl.style.display = 'none';
            if (dropEl) dropEl.style.display = 'none';
            if (preview) preview.style.display = 'none';

            document.getElementById('evalEmployeeId').value = '';
            document.getElementById('evalRecruitmentId').value = '';

            // Reset reviewer display
            var revEl = document.getElementById('evalEmpReviewer');
            if (revEl) revEl.textContent = '—';

            resetEvalFormState();
        };
        window.__resetProbationEvalModalImpl = window.resetProbationEvalModal;

        // ── Close dropdown on outside click ─────────────────────────────
        document.addEventListener('click', function(e) {
            var dropdown = document.getElementById('evalEmpDropdown');
            var search = document.getElementById('evalEmpSearch');
            if (dropdown && !dropdown.contains(e.target) && e.target !== search) {
                dropdown.style.display = 'none';
            }
        });

        // ── Reset on modal hidden ────────────────────────────────────────
        document.addEventListener('DOMContentLoaded', function() {
            var modalEl = document.getElementById('probationEvalModal');
            if (modalEl) {
                modalEl.addEventListener('hidden.bs.modal', window.resetProbationEvalModal);
            }
        });

    })();
</script>
