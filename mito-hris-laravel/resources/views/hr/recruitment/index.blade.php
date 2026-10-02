@extends('layouts.hr')

@section('title', 'Recruitment - MITO HRIS')
@section('page-title', 'Recruitment')
@section('page-subtitle', 'Manajemen pelamar dan pipeline seleksi')

@section('content')
    <!-- partials/RecruitmentPage.html — RECRUITMENT IN-PAGE SECTION (1:1 from GAS) -->
    <section class="page-section active" id="pageRecruitment">

        <!-- Stat card: kandidat baru (pending) -->
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-3">
                <div class="stat-card">
                    <div class="stat-icon bg-gold"><i class="bi bi-hourglass-split"></i></div>
                    <div>
                        <div class="stat-label">Kandidat Baru (Pending)</div>
                        <div class="stat-value" id="recStatPending">{{ $counts['new'] ?? 0 }}</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Table panel -->
        <div class="panel" id="tablePanel">
            <div class="panel-header">
                <div>
                    <h6>Data Kandidat</h6>
                    <div class="panel-subtitle" id="panelSubtitle">Menampilkan {{ $candidates->count() }} data kandidat
                    </div>
                </div>
                <div class="export-btns">
                    @can('manage_recruitment')
                        <a href="{{ route('hr.export.candidates-csv') }}" class="btn btn-outline-success btn-export-csv">
                            <i class="bi bi-file-earmark-spreadsheet" aria-hidden="true"></i>Export CSV
                        </a>
                    @endcan
                    <button class="btn-refresh" id="btnRefresh" type="button" title="Muat ulang data"
                        aria-label="Muat ulang data" data-refresh="page">
                        <i class="bi bi-arrow-repeat" aria-hidden="true"></i>
                    </button>
                </div>
            </div>

            <!-- FILTER BAR — basic -->
            <form action="{{ route('hr.recruitment.index') }}" method="GET" id="filterForm">
                <div class="filter-bar recruitment-filter-bar">
                    <div class="table-search">
                        <i class="bi bi-search"></i>
                        <input type="text" name="search" id="searchInput"
                            placeholder="Cari ID, nama, HP, email, posisi, kota..." value="{{ request('search') }}" />
                    </div>
                    <select class="filter-select" name="position" id="positionFilter" data-auto-submit="true">
                        <option value="">Semua Posisi</option>
                        @foreach ($positions ?? [] as $pos)
                            <option value="{{ $pos }}" {{ request('position') === $pos ? 'selected' : '' }}>
                                {{ $pos }}</option>
                        @endforeach
                    </select>
                    <select class="filter-select" name="sort" id="sortSelect" data-auto-submit="true">
                        <option value="newest" {{ request('sort', 'newest') === 'newest' ? 'selected' : '' }}>Terbaru
                        </option>
                        <option value="oldest" {{ request('sort') === 'oldest' ? 'selected' : '' }}>Terlama</option>
                    </select>
                    <select class="filter-select" name="gender" id="genderFilter" data-auto-submit="true">
                        <option value="">Semua Gender</option>
                        <option value="Laki-laki" {{ request('gender') === 'Laki-laki' ? 'selected' : '' }}>Laki-laki
                        </option>
                        <option value="Perempuan" {{ request('gender') === 'Perempuan' ? 'selected' : '' }}>Perempuan
                        </option>
                    </select>
                </div>
            </form>

            @can('update_candidates')
                <!-- BULK ACTION BAR -->
                <div class="bulk-action-bar d-none" id="bulkActionBar">
                    <div class="bulk-action-info">
                        <span id="bulkSelectedCount">0</span> kandidat dipilih
                    </div>
                    <div class="bulk-action-buttons">
                        @canany(['create_offering', 'manage_hold_blacklist'])
                            <div class="dropdown">
                                <button class="btn btn-sm btn-primary dropdown-toggle" type="button"
                                    data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="bi bi-arrow-repeat"></i> Ubah Status
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    @can('create_offering')
                                        <li><a class="dropdown-item bulk-status-btn" href="#" data-status="Accepted">
                                                <i class="bi bi-check-circle text-success me-1"></i> Accepted</a></li>
                                    @endcan
                                    @can('manage_hold_blacklist')
                                        <li><a class="dropdown-item bulk-status-btn" href="#" data-status="Hold">
                                                <i class="bi bi-pause-circle text-primary me-1"></i> Hold</a></li>
                                        <li><a class="dropdown-item bulk-status-btn" href="#" data-status="Blacklist">
                                                <i class="bi bi-x-circle text-danger me-1"></i> Blacklist</a></li>
                                    @endcan
                                </ul>
                            </div>
                        @endcanany
                        <button class="btn btn-sm btn-outline-secondary" id="bulkDeselectBtn" type="button">
                            <i class="bi bi-x-lg"></i> Batal Pilih
                        </button>
                    </div>
                </div>
            @endcan

            <!-- TABLE -->
            <div class="table-responsive">
                <table class="table hr-table">
                    <thead>
                        <tr>
                            <th class="col-check">
                                <input type="checkbox" class="form-check-input" id="selectAll" title="Pilih semua" />
                            </th>
                            <th>Avatar</th>
                            <th>Recruitment ID</th>
                            <th>Kandidat</th>
                            <th>Usia</th>
                            <th>Gender</th>
                            <th>Posisi</th>
                            <th>Status</th>
                            <th>Tanggal Daftar</th>
                        </tr>
                    </thead>
                    <tbody id="tableBody">
                        @forelse($paginatedCandidates as $candidate)
                            <tr data-drawer-type="candidate" data-drawer-id="{{ $candidate->recruitmentId }}"
                                style="cursor:pointer;">
                                <td class="col-check" data-stop-row-propagation="true">
                                    <input type="checkbox" class="form-check-input row-check"
                                        value="{{ $candidate->recruitmentId }}" />
                                </td>
                                <td>
                                    <div class="avatar-sm">
                                        {{ strtoupper(substr($candidate->fullName ?? 'U', 0, 2)) }}
                                    </div>
                                </td>
                                <td class="id-mono">
                                    {{ $candidate->recruitmentId }}
                                </td>
                                <td>
                                    <div class="cand-name  fw-bold text-primary text-decoration-underline">
                                        {{ $candidate->fullName }}</div>
                                    <div class="cand-sub font-monospace">{{ $candidate->email }}</div>
                                </td>
                                <td>{{ $candidate->age ? "{$candidate->age} th" : '-' }}</td>
                                <td>{{ $candidate->gender ?? '-' }}</td>
                                <td class="fw-semibold text-navy">{{ $candidate->positionApplied }}</td>
                                <td>
                                    <x-badge-status :status="$candidate->status" />
                                </td>
                                <td class="id-mono">{{ $candidate->createdDate ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9">
                                    <div class="table-empty">
                                        <i class="bi bi-inbox"></i>
                                        <p>Belum ada data kandidat.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="panel-footer">
                <span id="footerCount">Menampilkan
                    {{ $paginatedCandidates->count() > 0 ? ($currentPage - 1) * $perPage + 1 . '–' . min($currentPage * $perPage, $total) : 0 }}
                    dari {{ $total }} data</span>
                <x-pagination :currentPage="$currentPage" :total="$total" :perPage="$perPage" :route="'hr.recruitment.index'" :queryParams="[
                    'search' => $searchFilter,
                    'position' => $positionFilter,
                    'sort' => $sortFilter,
                    'gender' => $genderFilter,
                ]" />
            </div>
        </div>

    </section>

    @can('update_candidates')
        <div class="modal fade" id="bulkStatusModal" tabindex="-1" aria-labelledby="bulkStatusModalTitle" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content" style="border-radius: 16px">
                    <div class="modal-header">
                        <h6 class="modal-title mb-0" id="bulkStatusModalTitle">Ubah Status Massal</h6>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>
                    <div class="modal-body">
                        <p class="mb-3" style="font-size: 13.5px">
                            Ubah status <strong id="bulkStatusCount">0</strong> kandidat terpilih menjadi
                            <strong id="bulkStatusTargetLabel">-</strong>?
                        </p>
                        <div class="mb-0 d-none" id="bulkStatusReasonWrap">
                            <label class="form-label fw-semibold" style="font-size: 13px" for="bulkStatusReason">Alasan <span
                                    class="text-danger">*</span></label>
                            <textarea class="form-control" id="bulkStatusReason" rows="3" maxlength="500"
                                placeholder="Alasan ini dipakai untuk semua kandidat terpilih..."></textarea>
                            <div class="invalid-feedback">Alasan wajib diisi.</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-outline-secondary" data-bs-dismiss="modal" type="button">Batal</button>
                        <button class="btn text-white" style="background: var(--color-primary)" id="btnConfirmBulkStatus"
                            type="button">
                            <i class="bi bi-arrow-repeat me-1"></i>Simpan Perubahan
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endcan
@endsection

@section('scripts')
    <script>
        // Drawer click handler for candidates
        document.querySelectorAll('#tableBody tr[data-drawer-type="candidate"]').forEach(function(row) {
            row.addEventListener('click', function(e) {
                e.stopPropagation();
                if (e.target.type === 'checkbox') return;
                var id = this.getAttribute('data-drawer-id');
                if (id && typeof openCandidateDrawer === 'function') {
                    openCandidateDrawer(id);
                }
            });
        });

        document.addEventListener('DOMContentLoaded', () => {
            const selectAll = document.getElementById('selectAll');
            const checkboxes = Array.from(document.querySelectorAll('.row-check'));
            const bulkBar = document.getElementById('bulkActionBar');
            const bulkCount = document.getElementById('bulkSelectedCount');

            const selectedIds = () => checkboxes.filter(cb => cb.checked).map(cb => cb.value);

            const updateBulkBar = () => {
                const total = selectedIds().length;
                if (bulkCount) bulkCount.textContent = total;
                if (bulkBar) bulkBar.classList.toggle('d-none', total === 0);
                if (selectAll) {
                    selectAll.checked = total > 0 && total === checkboxes.length;
                    selectAll.indeterminate = total > 0 && total < checkboxes.length;
                }
            };

            if (selectAll) {
                selectAll.addEventListener('change', () => {
                    checkboxes.forEach(cb => cb.checked = selectAll.checked);
                    updateBulkBar();
                });
            }
            checkboxes.forEach(cb => cb.addEventListener('change', updateBulkBar));

            const deselectBtn = document.getElementById('bulkDeselectBtn');
            if (deselectBtn) {
                deselectBtn.addEventListener('click', () => {
                    checkboxes.forEach(cb => cb.checked = false);
                    updateBulkBar();
                });
            }

            const modalEl = document.getElementById('bulkStatusModal');
            if (!modalEl) return;

            const reasonWrap = document.getElementById('bulkStatusReasonWrap');
            const reasonEl = document.getElementById('bulkStatusReason');
            const confirmBtn = document.getElementById('btnConfirmBulkStatus');
            const needsReason = status => status === 'Hold' || status === 'Blacklist';
            let bulkTarget = '';

            document.querySelectorAll('.bulk-status-btn').forEach(btn => {
                btn.addEventListener('click', e => {
                    e.preventDefault();
                    const ids = selectedIds();
                    if (!ids.length) return;
                    bulkTarget = btn.dataset.status;
                    document.getElementById('bulkStatusCount').textContent = ids.length;
                    document.getElementById('bulkStatusTargetLabel').textContent = bulkTarget;
                    reasonEl.value = '';
                    reasonEl.classList.remove('is-invalid');
                    reasonWrap.classList.toggle('d-none', !needsReason(bulkTarget));
                    bootstrap.Modal.getOrCreateInstance(modalEl).show();
                });
            });

            reasonEl.addEventListener('input', () => {
                if (reasonEl.value.trim()) reasonEl.classList.remove('is-invalid');
            });

            confirmBtn.addEventListener('click', () => {
                const ids = selectedIds();
                if (!ids.length || !bulkTarget) return;
                const reason = needsReason(bulkTarget) ? reasonEl.value.trim() : '';
                if (needsReason(bulkTarget) && !reason) {
                    reasonEl.classList.add('is-invalid');
                    reasonEl.focus();
                    return;
                }

                confirmBtn.disabled = true;
                fetch('/hr/recruitment/bulk-status', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': getCsrfToken(),
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            recruitment_ids: ids,
                            status: bulkTarget,
                            reason: reason
                        })
                    })
                    .then(res => res.json())
                    .then(result => {
                        confirmBtn.disabled = false;
                        const message = (result && result.message) || 'Gagal mengubah status kandidat.';
                        if (result && result.updated > 0) {
                            bootstrap.Modal.getOrCreateInstance(modalEl).hide();
                            if (typeof showToast === 'function') showToast(message, result.failed > 0 ? 'warning' : 'success');
                            setTimeout(() => location.reload(), 800);
                        } else if (typeof showToast === 'function') {
                            showToast('Gagal: ' + message, 'error');
                        }
                    })
                    .catch(err => {
                        confirmBtn.disabled = false;
                        if (typeof showToast === 'function') showToast('Error: ' + err.message, 'error');
                    });
            });
        });
    </script>
@endsection
