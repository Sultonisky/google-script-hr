@extends('layouts.hr')

@section('title', 'Employee Probation - MITO HRIS')
@section('page-title', 'Employee Probation')
@section('page-subtitle', 'Monitoring evaluasi masa percobaan karyawan')

@section('content')
    <!-- partials/ProbationPage.html — Halaman Employee Probation (1:1 from GAS) -->
    <section class="page-section active" id="pageProbation">

        <!-- Stat cards (1:1 GAS _updateProbStats) -->
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-3">
                <div class="stat-card">
                    <div class="stat-icon bg-purple"><i class="bi bi-person-fill-check"></i></div>
                    <div>
                        <div class="stat-label">Total Probation</div>
                        <div class="stat-value" id="probStatWorkflow">{{ $stats['onboarding'] ?? 0 }}</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card">
                    <div class="stat-icon bg-gold"><i class="bi bi-clipboard-check"></i></div>
                    <div>
                        <div class="stat-label">Sudah Dievaluasi</div>
                        <div class="stat-value" id="probStatEvaluated">{{ $stats['evaluated'] ?? 0 }}</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card">
                    <div class="stat-icon bg-green"><i class="bi bi-person-check-fill"></i></div>
                    <div>
                        <div class="stat-label">Lulus Tetap</div>
                        <div class="stat-value" id="probStatPassed">{{ $stats['passed'] ?? 0 }}</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card">
                    <div class="stat-icon bg-red"><i class="bi bi-arrow-repeat"></i></div>
                    <div>
                        <div class="stat-label">Diperpanjang</div>
                        <div class="stat-value" id="probStatExtended">{{ $stats['extended'] ?? 0 }}</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Table panel -->
        <div class="panel" id="probTablePanel">
            <div class="panel-header">
                <div>
                    <h6>Karyawan Probation</h6>
                    <div class="panel-subtitle" id="probPanelSubtitle">
                        Menampilkan {{ $probations->count() }} dari {{ $stats['onboarding'] }} data
                    </div>
                </div>
                <div class="export-btns">
                    <button class="btn-refresh" id="btnProbRefresh" type="button" title="Muat ulang"
                        aria-label="Muat ulang data" data-refresh="page">
                        <i class="bi bi-arrow-clockwise"></i>
                    </button>
                    <button class="btn btn-sm text-white ms-2 fw-semibold"
                        style="background:#eb1c24;border:none;border-radius:8px;padding:6px 14px;font-size:13px"
                        id="btnOpenEvalModal" type="button" data-bs-toggle="modal" data-bs-target="#probationEvalModal">
                        <i class="bi bi-clipboard-check me-1"></i>Evaluasi Karyawan
                    </button>
                </div>
            </div>

            <!-- Filter bar -->
            <form action="{{ route('hr.probation.index') }}" method="GET" id="probFilterForm">
                <div class="filter-bar">
                    <div class="table-search">
                        <i class="bi bi-search"></i>
                        <input type="text" name="search" id="probSearchInput"
                            placeholder="Cari nama, ID, posisi, departemen..." value="{{ request('search') }}" data-submit-on-enter="true" />
                    </div>
                    <select class="filter-select" name="status" id="probStatusFilter" data-auto-submit="true">
                        <option value="">Status</option>
                        <option value="lulus" {{ request('status') === 'lulus' ? 'selected' : '' }}>Lulus</option>
                        <option value="tidak_lulus" {{ request('status') === 'tidak_lulus' ? 'selected' : '' }}>Tidak Lulus
                        </option>
                        <option value="extend" {{ request('status') === 'extend' ? 'selected' : '' }}>Extend</option>
                    </select>
                    <select class="filter-select" name="score" id="probScoreFilter" data-auto-submit="true">
                        <option value="">Kategori Score</option>
                        <option value="Sangat Baik" {{ request('score') === 'Sangat Baik' ? 'selected' : '' }}>Sangat Baik
                        </option>
                        <option value="Baik" {{ request('score') === 'Baik' ? 'selected' : '' }}>Baik</option>
                        <option value="Cukup" {{ request('score') === 'Cukup' ? 'selected' : '' }}>Cukup</option>
                        <option value="Kurang" {{ request('score') === 'Kurang' ? 'selected' : '' }}>Kurang</option>
                    </select>
                    <select class="filter-select" name="sort" id="probSortSelect" data-auto-submit="true">
                        <option value="newest" {{ request('sort', 'newest') === 'newest' ? 'selected' : '' }}>Terbaru
                        </option>
                        <option value="oldest" {{ request('sort') === 'oldest' ? 'selected' : '' }}>Terlama</option>
                        <option value="name_asc" {{ request('sort') === 'name_asc' ? 'selected' : '' }}>Nama A-Z</option>
                        <option value="name_desc" {{ request('sort') === 'name_desc' ? 'selected' : '' }}>Nama Z-A</option>
                    </select>
                    <a href="{{ route('hr.probation.index') }}" class="btn-reset-filter text-decoration-none">
                        <i class="bi bi-arrow-counterclockwise"></i> Reset
                    </a>
                </div>
            </form>

            <!-- Success / Error flash messages -->
            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show mx-3 mt-2" role="alert"
                    style="font-size:13px">
                    <i class="bi bi-check-circle-fill me-2"></i>{!! session('success') !!}
                    <button type="button" class="btn-close btn-sm" data-bs-dismiss="alert"></button>
                </div>
            @endif
            @if (session('error'))
                <div class="alert alert-danger alert-dismissible fade show mx-3 mt-2" role="alert"
                    style="font-size:13px">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>{{ session('error') }}
                    <button type="button" class="btn-close btn-sm" data-bs-dismiss="alert"></button>
                </div>
            @endif
            @if ($errors->any())
                <div class="alert alert-danger alert-dismissible fade show mx-3 mt-2" role="alert"
                    style="font-size:13px">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    <ul class="mb-0">
                        @foreach ($errors->all() as $err)
                            <li>{{ $err }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close btn-sm" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <!-- Table (1:1 GAS _renderProbTable) -->
            <div class="table-responsive">
                <table class="table hr-table">
                    <thead>
                        <tr>
                            <th>Avatar</th>
                            <th>Employee ID</th>
                            <th>Employee</th>
                            <th>Position / Dept</th>
                            <th>Probation Start</th>
                            <th>Probation End</th>
                            <th>Score / Kategori</th>
                            <th>Evaluation Status</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="probTableBody">
                        @forelse($probations as $prob)
                            @php
                                $lastCategory = $prob->lastCategory ?? null;
                                $lastTotal = $prob->lastOverallTotal ?? null;
                                $keputusan = $prob->lastDecision ?? '';

                                // IMPORTANT: Check isTerm BEFORE isLulus.
                                // 'Tidak Lulus' contains 'Lulus' as substring — wrong order causes
                                // str_contains('Tidak Lulus','Lulus') = true false-positive.
                                $isTerm =
                                    $keputusan === 'Tidak Lulus' ||
                                    $keputusan === 'Tidak Lolos → Putus Kontrak (Paklaring)' ||
                                    str_contains($keputusan, 'Putus Kontrak') ||
                                    str_contains($keputusan, 'Paklaring');
                                $isExt =
                                    !$isTerm &&
                                    (str_contains($keputusan, 'Perpanjang') ||
                                        str_contains($keputusan, 'Evaluasi Ulang'));
                                // isLulus: only if neither terminated nor extended
                                $isLulus =
                                    !$isTerm &&
                                    !$isExt &&
                                    $keputusan !== '' &&
                                    ($keputusan === 'Lulus' ||
                                        $keputusan === 'Diangkat sebagai Karyawan Tetap' ||
                                        $keputusan === 'Lulus → Karyawan Tetap' ||
                                        str_contains($keputusan, 'Pass') ||
                                        str_contains($keputusan, 'Tetap') ||
                                        str_contains($keputusan, 'Diangkat'));

                                // Score badge class — category-based (Performance Review 2026)
                                $scoreCls = '';
                                if (!empty($lastCategory)) {
                                    $scoreCls = match ($lastCategory) {
                                        'Sangat Baik', 'Baik' => 'accepted',
                                        'Cukup' => 'hold',
                                        'Kurang' => 'blacklist',
                                        default => '',
                                    };
                                }
                            @endphp
                            <tr>
                                <td>
                                    <div class="avatar-sm">
                                        {{ strtoupper(substr(str_replace(' ', '', $prob->fullName ?? 'P'), 0, 1)) }}{{ strtoupper(substr(explode(' ', $prob->fullName ?? 'P')[1] ?? '', 0, 1)) }}
                                    </div>
                                </td>
                                <td><span class="id-mono" style="font-size:11px">{{ $prob->employeeId }}</span></td>
                                <td>
                                    <div class="fw-semibold" style="font-size:13px">{{ $prob->fullName }}</div>
                                    <div class="cand-sub">{{ $prob->personalEmail }}</div>
                                </td>
                                <td>
                                    <div style="font-size:13px">
                                        {{ $prob->jobPositionLocation ?: $prob->jobPosition ?: '-' }}</div>
                                    <div class="cand-sub">{{ $prob->department ?? '-' }}</div>
                                </td>
                                <td><small>{{ $prob->joinDate ?? '-' }}</small></td>
                                <td><small>{{ $prob->endDateContract ?? '-' }}</small></td>
                                <td>
                                    @if (!empty($lastCategory))
                                        <div>
                                            <span class="badge-status {{ $scoreCls }}"
                                                style="font-size:11px;padding:2px 8px">
                                                {{ $lastTotal }}/13
                                            </span>
                                        </div>
                                        <div style="font-size:10.5px;color:#6b7280;margin-top:2px">{{ $lastCategory }}
                                        </div>
                                    @else
                                        <span style="color:#9ca3af;font-size:12px">-</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($keputusan === '')
                                        <span style="color:#9ca3af;font-size:12px">Not yet evaluated</span>
                                    @elseif($isLulus)
                                        <span class="badge-status accepted" style="font-size:10px;padding:2px 8px">
                                            <i class="bi bi-check-circle-fill me-1"></i>Passed
                                        </span>
                                    @elseif($isTerm)
                                        <span class="badge-status blacklist" style="font-size:10px;padding:2px 8px">
                                            <i class="bi bi-x-circle-fill me-1"></i>Terminated
                                        </span>
                                    @else
                                        <span class="badge-status hold" style="font-size:10px;padding:2px 8px">
                                            <i class="bi bi-arrow-repeat me-1"></i>Extended
                                        </span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    @if ($prob->can_evaluate ?? false)
                                        <button class="btn btn-sm prob-btn-eval"
                                            style="background:#eb1c24;color:#f5f3ff;;border-radius:6px;padding:4px 8px"
                                            type="button" title="Evaluasi" data-bs-toggle="modal"
                                            data-bs-target="#probationEvalModal"
                                            data-employee-id="{{ $prob->employeeId }}">
                                            <i class="bi bi-clipboard-check"></i>
                                        </button>
                                    @endif
                                    <button class="btn btn-sm prob-btn-history ms-1"
                                        style="color:#1063b1;border:1px solid #1063b1;border-radius:6px;padding:4px 8px"
                                        type="button" title="Riwayat Evaluasi"
                                        data-employee-id="{{ $prob->employeeId }}">
                                        <i class="bi bi-clock-history"></i>
                                    </button>
                                    @if (!empty($prob->lastEvalId))
                                        <a href="{{ route('hr.export.performance-review', ['id' => $prob->employeeId, 'eval_id' => $prob->lastEvalId]) }}"
                                            class="btn btn-sm ms-1"
                                            style="color:#eb1c24;border:1px solid #eb1c24;border-radius:6px;padding:4px 8px"
                                            target="_blank" title="Download Performance Review">
                                            <i class="bi bi-download"></i>
                                        </a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9">
                                    <div class="table-empty">
                                        <i class="bi bi-hourglass"></i>
                                        <p>Belum ada karyawan dengan status Probation.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="panel-footer">
                <span id="probFooterCount">
                    Menampilkan {{ $probations->count() }} dari {{ $stats['onboarding'] }} data
                </span>
            </div>
        </div>

    </section>

    {{-- Eval History Modal (1:1 GAS evalHistoryModal) --}}
    <div class="modal fade" id="evalHistoryModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
            <div class="modal-content" style="border-radius:16px">
                <div class="modal-header">
                    <div>
                        <h6 class="modal-title mb-0 fw-bold">Evaluation History</h6>
                        <small class="text-muted" id="evalHistoryEmpName">-</small>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-3" id="evalHistoryBody">
                    <div class="text-center text-muted py-4" style="font-size:13px">Loading history...</div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        // All probation employees — passed to modal for live search (1:1 GAS _probData)
        window.__allProbationEmployees = @json($allProbations->values());

        // ============================================================
        // Pre-fill eval modal when clicking Evaluasi button in table row
        // (1:1 GAS _attachProbRowHandlers → openEvalModal(emp))
        // ============================================================
        function prefillEvalEmployee(employeeId) {
            resetProbationEvalModal();
            const emp = (window.__allProbationEmployees || []).find(e =>
                (e.employeeId || '').replace(/^'+/, '') === String(employeeId).replace(/^'+/, '')
            );
            if (emp) {
                setTimeout(() => selectEvalEmployee(emp), 150);
            }
        }

        // ============================================================
        // Reset eval modal to initial state
        // Delegates to resetProbationEvalModal() defined in probation-modals.blade.php
        // ============================================================
        function resetProbationEvalModal() {
            // The actual implementation lives in probation-modals.blade.php (IIFE).
            // This function is called from index.blade.php (button onclick + modal hidden event).
            // If probation-modals has already been loaded, call its version; otherwise no-op.
            if (typeof window.__resetProbationEvalModalImpl === 'function') {
                window.__resetProbationEvalModalImpl();
            } else {
                // Fallback — basic reset if modal JS not yet initialised
                const ids = ['evalEmpSearch', 'evalEmployeeId', 'evalRecruitmentId', 'evalDecisionValue',
                    'evalExtDuration', 'evalExtStart', 'evalExtEnd', 'evalCatatan'
                ];
                ids.forEach(id => {
                    const el = document.getElementById(id);
                    if (el) el.value = '';
                });
                ['evalEmpSearchClear', 'evalEmpDropdown', 'evalEmpPreview', 'evalExtendSection']
                .forEach(id => {
                    const el = document.getElementById(id);
                    if (el) el.style.display = 'none';
                });
                const btn = document.getElementById('btnConfirmProbationEval');
                if (btn) btn.disabled = true;
                const form = document.getElementById('probationEvalForm');
                if (form) form.action = '';
            }
        }

        // ============================================================
        // Eval History Modal (1:1 GAS openEvalHistoryModal)
        // ============================================================
        function openEvalHistoryModal(employeeId, empName) {
            const nameEl = document.getElementById('evalHistoryEmpName');
            const bodyEl = document.getElementById('evalHistoryBody');
            if (nameEl) nameEl.textContent = empName || '-';
            if (bodyEl) bodyEl.innerHTML =
                '<div class="text-center text-muted py-4" style="font-size:13px"><span class="spinner-border spinner-border-sm me-2"></span>Memuat riwayat...</div>';

            const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('evalHistoryModal'));
            modal.show();

            fetch(`/hr/probation/${employeeId}/eval-history`, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                })
                .then(r => r.json())
                .then(data => {
                    if (!bodyEl) return;
                    const history = data.history || [];
                    if (!history.length) {
                        bodyEl.innerHTML =
                            '<div class="text-center text-muted py-4" style="font-size:13px">Belum ada riwayat evaluasi untuk karyawan ini.</div>';
                        return;
                    }
                    bodyEl.innerHTML = history.map((ev, idx) => {
                        const keputusan = ev.decision || '';

                        // IMPORTANT: check isTerm BEFORE isLulus.
                        // 'Tidak Lulus' contains 'Lulus' — wrong check order causes misclassification.
                        const isTerm = keputusan === 'Tidak Lulus' ||
                            keputusan === 'Tidak Lolos → Putus Kontrak (Paklaring)' ||
                            keputusan.includes('Putus Kontrak') ||
                            keputusan.includes('Paklaring');
                        const isExt = !isTerm && (
                            keputusan.includes('Perpanjang') ||
                            keputusan.includes('Evaluasi Ulang')
                        );
                        const isLulus = !isTerm && !isExt && keputusan !== '' && (
                            keputusan === 'Diangkat sebagai Karyawan Tetap' ||
                            keputusan === 'Lulus → Karyawan Tetap' ||
                            keputusan.includes('Pass') ||
                            keputusan.includes('Tetap') ||
                            keputusan.includes('Diangkat')
                        );

                        const kepClass = isLulus ? 'accepted' : (isTerm ? 'blacklist' : 'hold');
                        const kepLabel = isLulus ? 'Passed → Permanent' : (isTerm ? 'Terminated → Paklaring' :
                            'Extended');
                        const sDur = ev.extensionDuration || '';
                        const sStart = ev.newContractStart || '-';
                        const sEnd = ev.newContractEnd || '-';
                        const sNote = ev.evaluatorNotes || '';

                        // Determine if this is a Performance Review 2026 (indicator-based) evaluation
                        const hasNewData = ev.overallTotal !== '' && ev.overallTotal !== null && ev
                            .overallTotal !== undefined;

                        let scoreHtml = '';
                        if (hasNewData) {
                            // Performance Review 2026 — show competency totals
                            const total = ev.overallTotal || 0;
                            const cat = ev.category || '-';
                            const catColor = getCategoryColor(cat);
                            scoreHtml = `
                    <div class="row g-2 mb-2" style="font-size:12px">
                        <div class="col-3"><div class="text-muted">Integrity</div><strong>${ev.integrityTotal ?? '-'}/4</strong></div>
                        <div class="col-3"><div class="text-muted">Cont. Improvement</div><strong>${ev.ciTotal ?? '-'}/4</strong></div>
                        <div class="col-3"><div class="text-muted">Exec. Excellence</div><strong>${ev.eeTotal ?? '-'}/2</strong></div>
                        <div class="col-3"><div class="text-muted">Teamwork</div><strong>${ev.twTotal ?? '-'}/3</strong></div>
                        <div class="col-6 mt-1">
                            <div class="text-muted">Total Indikator</div>
                            <strong style="font-size:16px">${total}/13</strong>
                        </div>
                        <div class="col-6 mt-1">
                            <div class="text-muted">Kategori</div>
                            <span class="badge rounded-pill px-2 py-1" style="background:${catColor.bg};color:${catColor.color};font-size:12px">${cat}</span>
                        </div>
                    </div>`;
                        } else {
                            scoreHtml =
                                '<div class="text-muted mb-2" style="font-size:12px">Data skor tidak tersedia.</div>';
                        }

                        // ── Decision-specific document actions ───────────────
                        // PDF dibuat on-demand dan langsung di-download lewat route
                        // hr.export.* (konvensi fungsi PDF lainnya).
                        // EXTEND tidak punya dokumen — hanya info durasi.
                        const evalIdQ = encodeURIComponent(ev.evalId || '');

                        let docBtnLabel = '',
                            docIcon = '',
                            docUrl = '';
                        if (isLulus) {
                            docBtnLabel = 'Download SK Pengangkatan';
                            docIcon = 'bi-patch-check';
                            docUrl = '/hr/export/sk-pengangkatan/' + encodeURIComponent(employeeId);
                        } else if (isTerm) {
                            docBtnLabel = 'Download Paklaring';
                            docIcon = 'bi-file-earmark-text';
                            docUrl = '/hr/export/paklaring/' + encodeURIComponent(employeeId) +
                                '?eval_id=' + evalIdQ;
                        }

                        const previewBtn = `
                    <a class="btn btn-sm" target="_blank"
                       style="background:#f0f7ff;color:#0b4a86;border:1px solid #c7dff7;border-radius:6px;font-size:12px"
                       href="/hr/probation/${encodeURIComponent(employeeId)}/preview?eval_id=${evalIdQ}">
                       <i class="bi bi-eye me-1"></i>Preview Performance Review
                    </a>`;
                        const prPdfBtn = (isLulus || isTerm) ? `
                    <a class="btn btn-sm" target="_blank"
                       style="background:#f5f3ff;color:#7c3aed;border:1px solid #ddd6fe;border-radius:6px;font-size:12px"
                       href="/hr/export/performance-review/${encodeURIComponent(employeeId)}?eval_id=${evalIdQ}">
                       <i class="bi bi-file-earmark-pdf me-1"></i>Download Performance Review PDF
                    </a>` : '';
                        const decDocBtn = docUrl ? `
                    <a class="btn btn-sm" target="_blank"
                       style="background:#ecfdf5;color:#065f46;border:1px solid #a7f3d0;border-radius:6px;font-size:12px"
                       href="${docUrl}">
                       <i class="bi ${docIcon} me-1"></i>${docBtnLabel}
                    </a>` : '';

                        const docActions = (isLulus || isTerm) ?
                            `<div class="d-flex flex-wrap gap-2 mt-2 pt-2" style="border-top:1px dashed #e5e7eb">${previewBtn}${prPdfBtn}${decDocBtn}</div>` :
                            '';

                        return `<div class="p-3 rounded-3 mb-3" style="background:#f9fafb;border:1px solid #e5e7eb">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <span class="fw-bold" style="font-size:13px">#${idx+1} — ${ev.evalDate || '-'}</span>
                            <span class="text-muted ms-2" style="font-size:11px">by ${ev.evaluator || '-'}</span>
                        </div>
                        <span class="badge-status ${kepClass}" style="font-size:10px;padding:2px 8px">${kepLabel}</span>
                    </div>
                    ${scoreHtml}
                    ${sNote ? `<div class="text-muted" style="font-size:11px;font-style:italic">"${sNote}"</div>` : ''}
                    ${sDur  ? `<div style="font-size:11px;color:#d97706" class="mt-1"><i class="bi bi-calendar-range me-1"></i>Extended ${sDur} (${sStart} – ${sEnd})</div>` : ''}
                    ${docActions}
                </div>`;
                    }).join('');
                })
                .catch(() => {
                    if (bodyEl) bodyEl.innerHTML =
                        '<div class="text-center text-muted py-4" style="font-size:13px">Gagal memuat riwayat.</div>';
                });
        }

        // Helper: get category badge colors for history modal
        function getCategoryColor(cat) {
            const map = {
                'Sangat Baik': {
                    bg: '#d1fae5',
                    color: '#166534'
                },
                'Baik': {
                    bg: '#dbeafe',
                    color: '#1e40af'
                },
                'Cukup': {
                    bg: '#fef3c7',
                    color: '#92400e'
                },
                'Kurang': {
                    bg: '#fee2e2',
                    color: '#991b1b'
                },
            };
            return map[cat] || {
                bg: '#e5e7eb',
                color: '#374151'
            };
        }

        // Reset modal on close
        document.addEventListener('DOMContentLoaded', function() {
            const modalEl = document.getElementById('probationEvalModal');
            if (modalEl) {
                modalEl.addEventListener('hidden.bs.modal', function() {
                    if (typeof resetProbationEvalModal === 'function') resetProbationEvalModal();
                });
            }
        });
    </script>
@endsection
