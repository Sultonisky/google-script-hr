@extends('layouts.hr')

@section('title', 'Karyawan Outsource - MITO HRIS')
@section('page-title', 'Karyawan Outsource')
@section('page-subtitle', 'Manajemen tenaga kerja alih daya (Outsource)')

@section('content')
    <section class="page-section active" id="pageOutsource">

        <div class="row g-3 mb-3 mt-2" id="outsourceStats">
            <div class="col-6 col-md-3">
                <div class="stat-card">
                    <div class="stat-icon bg-cyan"><i class="bi bi-people-fill"></i></div>
                    <div>
                        <div class="stat-label">Total Outsource</div>
                        <div class="stat-value text-navy" id="osStatTotal">{{ $stats['total'] ?? 0 }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="panel mt-2" id="outsourcePanel">
            <div class="panel-header">
                <div>
                    <h6>Data Karyawan Outsource</h6>
                    <div class="panel-subtitle" id="osPanelSubtitle">
                        @if ($total > 0)
                            Menampilkan {{ ($currentPage - 1) * $perPage + 1 }}–{{ min($currentPage * $perPage, $total) }}
                            dari {{ $total }} data
                        @else
                            Tidak ada data karyawan outsource
                        @endif
                    </div>
                </div>
                {{-- Export XLSX — server-side dilindungi can:view_outsource di route --}}
                @can('view_outsource')
                    <div class="export-btns d-flex flex-wrap gap-2">
                        @can('manage_outsource')
                            <button class="btn btn-sm fw-semibold text-white"
                                style="background:#005BAC;border:none;border-radius:8px;padding:6px 14px;font-size:13px"
                                type="button"
                                id="btnAddOutsource">
                                <i class="bi bi-person-plus-fill me-1"></i>Tambah Outsource
                            </button>
                            <button class="btn btn-sm fw-semibold text-white"
                                style="background:#6f42c1;border:none;border-radius:8px;padding:6px 14px;font-size:13px"
                                type="button"
                                id="btnSyncOutsourceAttendance"
                                data-url="{{ route('hr.outsource.push-attendance') }}"
                                data-count="{{ $stats['total'] ?? 0 }}">
                                <i class="bi bi-arrow-repeat me-1"></i>Sync ke Attendance
                            </button>
                            <button class="btn btn-sm fw-semibold text-white"
                                style="background:#0d6efd;border:none;border-radius:8px;padding:6px 14px;font-size:13px"
                                type="button"
                                data-bs-toggle="modal"
                                data-bs-target="#outsourceContractModal"
                                id="btnProsesKontrakOutsource">
                                <i class="bi bi-file-earmark-text me-1"></i>Proses Kontrak
                            </button>
                            <button class="btn btn-sm fw-semibold text-white"
                                style="background:#198754;border:none;border-radius:8px;padding:6px 14px;font-size:13px"
                                type="button"
                                data-bs-toggle="modal"
                                data-bs-target="#outsourceImportModal"
                                id="btnImportOutsource">
                                <i class="bi bi-file-earmark-spreadsheet me-1"></i>Import Excel
                            </button>
                        @endcan
                        <a href="{{ route('hr.export.outsource-xlsx') }}"
                            class="btn btn-sm fw-semibold text-white"
                            style="background:#005BAC;border:none;border-radius:8px;padding:6px 14px;font-size:13px"
                            id="btnExportOutsourceXlsx"
                            title="Export seluruh data karyawan outsource ke Excel (XLSX)">
                            <i class="bi bi-download me-1"></i>Export
                        </a>
                    </div>
                @endcan
            </div>

            <!-- Filter bar -->
            <form action="{{ route('hr.outsource.index') }}" method="GET" id="osFilterForm">
                {{-- Reset page to 1 on any filter change --}}
                <input type="hidden" name="page" value="1">
                <div class="filter-bar">
                    <div class="table-search">
                        <i class="bi bi-search"></i>
                        <input type="text" name="search" id="osSearchInput"
                            placeholder="Cari nama, ID, jabatan, lokasi..." value="{{ $searchFilter ?? '' }}" />
                    </div>
                    <select class="filter-select" name="vendor" id="osVendorSelect" data-auto-submit="true">
                        <option value="">Semua Vendor</option>
                        @foreach (config('hris.outsource.vendors', []) as $vendorName)
                            <option value="{{ $vendorName }}" {{ ($vendorFilter ?? '') === $vendorName ? 'selected' : '' }}>
                                {{ $vendorName }}</option>
                        @endforeach
                    </select>
                    <select class="filter-select" name="sort" id="osSortSelect" data-auto-submit="true">
                        <option value="name_asc" {{ ($sortFilter ?? 'name_asc') === 'name_asc' ? 'selected' : '' }}>Nama A-Z
                        </option>
                        <option value="name_desc" {{ ($sortFilter ?? 'name_asc') === 'name_desc' ? 'selected' : '' }}>Nama
                            Z-A</option>
                    </select>
                    <select class="filter-select" name="order" id="osOrderSelect" data-auto-submit="true">
                        <option value="" {{ empty($orderFilter) ? 'selected' : '' }}>Urutan</option>
                        <option value="newest" {{ ($orderFilter ?? '') === 'newest' ? 'selected' : '' }}>Terbaru</option>
                        <option value="oldest" {{ ($orderFilter ?? '') === 'oldest' ? 'selected' : '' }}>Terlama</option>
                    </select>
                    <a href="{{ route('hr.outsource.index') }}" class="btn-reset-filter text-decoration-none"
                        id="osBtnResetFilter">
                        <i class="bi bi-arrow-counterclockwise"></i> Reset
                    </a>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table hr-table">
                    <thead>
                        <tr>
                            <th>Avatar</th>
                            <th>Outsource ID</th>
                            <th>Nama</th>
                            <th>Jabatan / Lokasi</th>
                            <th>Vendor</th>
                            <th>Tgl Join Mito</th>
                            <th>Tgl Akhir Kontrak (StaffInc)</th>
                        </tr>
                    </thead>
                    <tbody id="osTableBody">
                        @forelse($outsources as $os)
                            <tr data-drawer-type="outsource" data-drawer-id="{{ $os->outsourceId }}"
                                style="cursor:pointer;">
                                <td>
                                    <div class="avatar-sm">
                                        {{ strtoupper(substr($os->fullName ?? 'O', 0, 2)) }}
                                    </div>
                                </td>
                                <td class="id-mono fw-bold">{{ $os->outsourceId }}</td>
                                <td>
                                    <div class="cand-name fw-bold text-primary text-decoration-underline ">
                                        {{ $os->fullName }}</div>
                                    <div class="cand-sub">{{ $os->email }}</div>
                                </td>
                                <td>{{ $os->jobTitle ?? '-' }} <small
                                        class="text-muted d-block">{{ collect([$os->workLocation, $os->workCity])->filter()->implode(' · ') ?: '-' }}</small></td>
                                <td>{{ $os->vendor ?? '-' }}</td>
                                <td class="id-mono">{{ $os->mitoJoinDate ?? '-' }}</td>
                                <td class="id-mono">{{ $os->contractEndDate ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7">
                                    <div class="table-empty">
                                        <i class="bi bi-inbox"></i>
                                        <p>Belum ada data karyawan outsource.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="panel-footer">
                <span id="osFooterCount">
                    @if ($total > 0)
                        Menampilkan {{ ($currentPage - 1) * $perPage + 1 }}–{{ min($currentPage * $perPage, $total) }}
                        dari {{ $total }} data
                    @else
                        Tidak ada data
                    @endif
                </span>
                @if ($total > $perPage)
                    <x-pagination :currentPage="$currentPage" :total="$total" :perPage="$perPage" :route="'hr.outsource.index'"
                        :queryParams="['search' => $searchFilter, 'vendor' => $vendorFilter, 'sort' => $sortFilter, 'per_page' => $perPage]" />
                @endif
            </div>
        </div>

    </section>

    @can('manage_outsource')
        @include('hr.partials.outsource-contract-modal')
        @include('hr.partials.outsource-form-modal')
        @include('hr.partials.outsource-import-modal')

        <div class="modal fade" id="outsourceAttendanceSyncModal" tabindex="-1"
            aria-labelledby="outsourceAttendanceSyncTitle" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <div>
                            <h5 class="modal-title fw-bold" id="outsourceAttendanceSyncTitle">
                                <i class="bi bi-arrow-repeat text-primary me-2"></i>Sync Outsource ke Attendance
                            </h5>
                            <div class="small text-muted mt-1">Preview memakai dry-run; belum ada data yang ditulis.</div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-info small" role="alert">
                            Data yang dikirim hanya Outsource ID dan nama. Person baru akan dibuat <strong>inactive</strong>,
                            tanpa assignment cabang/pin, dengan PIN default <strong>123456</strong>. Data yang sudah ada
                            tidak diubah. Status konflik bukan berarti data terhapus: ID tersebut sudah dipakai di Attendance,
                            tetapi namanya berbeda atau record Attendance-nya sudah dihapus. Baris konflik tidak akan dibuat,
                            ditimpa, atau dipulihkan otomatis; periksa ID dan orangnya di kedua sistem sebelum mengambil tindakan.
                        </div>
                        <div id="outsourceAttendanceSyncResult" class="alert small" role="alert" aria-live="assertive" hidden></div>
                        <div id="outsourceAttendanceSyncError" class="alert alert-danger small" role="alert" hidden></div>
                        <div class="d-flex flex-wrap gap-2 mb-3" id="outsourceAttendanceSyncSummary" aria-live="polite">
                            <span class="badge text-bg-secondary">Menunggu preview</span>
                        </div>
                        <div class="table-responsive border rounded" style="max-height:50vh">
                            <table class="table table-sm table-hover align-middle mb-0">
                                <thead class="table-light sticky-top">
                                    <tr>
                                        <th scope="col">Outsource ID</th>
                                        <th scope="col">Nama</th>
                                        <th scope="col">Status dan penjelasan</th>
                                    </tr>
                                </thead>
                                <tbody id="outsourceAttendanceSyncRows">
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-4">Klik tombol sync untuk memuat preview.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="button" class="btn btn-outline-primary" id="outsourceAttendanceSyncRetry" hidden>
                            <i class="bi bi-arrow-clockwise me-1"></i>Ulangi Preview
                        </button>
                        <button type="button" class="btn btn-primary" id="outsourceAttendanceSyncConfirm" disabled>
                            <i class="bi bi-check2-circle me-1"></i>Konfirmasi dan Sync
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endcan
@endsection

@section('scripts')
    <script>
        // Drawer click handler for outsource
        document.querySelectorAll('#osTableBody tr[data-drawer-type="outsource"]').forEach(function(row) {
            row.addEventListener('click', function(e) {
                if (e.target.tagName === 'BUTTON' || e.target.closest('button')) return;
                var id = this.getAttribute('data-drawer-id');
                if (id && typeof openOutsourceDrawer === 'function') {
                    openOutsourceDrawer(id);
                }
            });
        });

        // Search submit on Enter
        var osSearchInput = document.getElementById('osSearchInput');
        if (osSearchInput) {
            osSearchInput.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    document.getElementById('osFilterForm').submit();
                }
            });
        }

        var syncAttendanceButton = document.getElementById('btnSyncOutsourceAttendance');
        if (syncAttendanceButton) {
            var syncModal = document.getElementById('outsourceAttendanceSyncModal');
            var syncModalInstance = null;
            var syncRows = document.getElementById('outsourceAttendanceSyncRows');
            var syncSummary = document.getElementById('outsourceAttendanceSyncSummary');
            var syncResultNotice = document.getElementById('outsourceAttendanceSyncResult');
            var syncError = document.getElementById('outsourceAttendanceSyncError');
            var syncConfirm = document.getElementById('outsourceAttendanceSyncConfirm');
            var syncRetry = document.getElementById('outsourceAttendanceSyncRetry');
            var syncRunning = false;
            var syncCanExecute = false;

            function setSyncBusy(busy, label) {
                syncRunning = busy;
                syncAttendanceButton.disabled = busy;
                syncConfirm.disabled = busy || !syncCanExecute;
                syncRetry.hidden = busy || syncRows.dataset.ready === 'true';
                syncConfirm.innerHTML = busy
                    ? '<span class="spinner-border spinner-border-sm me-1"></span>' + label
                    : '<i class="bi bi-check2-circle me-1"></i>Konfirmasi dan Sync';
            }

            function addSyncBadge(container, count, label, color) {
                var badge = document.createElement('span');
                badge.className = 'badge text-bg-' + color;
                badge.textContent = label + ': ' + count;
                container.appendChild(badge);
            }

            function showSyncResultNotice(message, type) {
                syncResultNotice.className = 'alert alert-' + type + ' small';
                syncResultNotice.textContent = message;
                syncResultNotice.hidden = false;
            }

            function renderSyncResult(result, isPreview) {
                syncRows.replaceChildren();
                syncSummary.replaceChildren();
                syncError.hidden = true;
                syncRows.dataset.ready = isPreview ? 'true' : 'false';
                syncCanExecute = isPreview && result.meta.processed > 0;

                addSyncBadge(syncSummary, result.meta.processed, 'Total', 'secondary');
                if (isPreview) {
                    addSyncBadge(syncSummary, result.meta.would_create, 'Belum ada · akan dibuat', 'primary');
                } else {
                    addSyncBadge(syncSummary, result.meta.created, 'Dibuat', 'success');
                }
                addSyncBadge(syncSummary, result.meta.skipped, 'Sudah ada · dilewati', 'success');
                addSyncBadge(syncSummary, result.meta.conflict, 'Perlu pemeriksaan · konflik', 'warning');

                if (result.data.length === 0) {
                    var emptyRow = syncRows.insertRow();
                    var emptyCell = emptyRow.insertCell();
                    emptyCell.colSpan = 4;
                    emptyCell.className = 'text-center text-muted py-4';
                    emptyCell.textContent = 'Tidak ada data Outsource yang dapat dikirim.';
                }

                result.data.forEach(function(record) {
                    var row = syncRows.insertRow();
                    var idCell = row.insertCell();
                    var nameCell = row.insertCell();
                    var statusCell = row.insertCell();
                    var explanationCell = row.insertCell();
                    idCell.className = 'font-monospace fw-semibold';
                    idCell.textContent = record.outsource_id;
                    nameCell.textContent = record.full_name;

                    var badge = document.createElement('span');
                    var statusLabels = {
                        would_create: {
                            label: 'Belum ada · akan dibuat',
                            color: 'primary',
                            explanation: 'ID belum ditemukan di Attendance. Jika dilanjutkan, person dibuat inactive tanpa assignment cabang/pin.'
                        },
                        created: {
                            label: 'Dibuat',
                            color: 'success',
                            explanation: 'Person berhasil dibuat di Attendance dengan status inactive, tanpa assignment cabang/pin.'
                        },
                        skipped: {
                            label: 'Sudah ada · dilewati',
                            color: 'success',
                            explanation: 'ID dan nama sudah cocok. Tidak dibuat duplikat dan data Attendance tidak diubah.'
                        },
                        conflict: {
                            label: 'Konflik · tidak diubah',
                            color: 'warning',
                            explanation: 'ID ini sudah memiliki record di Attendance. Data tidak ditimpa, dibuat ulang, atau dipulihkan. Periksa ID dan identitas orangnya dengan admin Attendance sebelum mengambil tindakan.'
                        }
                    };
                    var status = statusLabels[record.status] || {
                        label: 'Status tidak dikenal',
                        color: 'secondary',
                        explanation: 'Periksa respons atau log sinkronisasi sebelum melanjutkan.'
                    };
                    badge.className = 'badge text-bg-' + status.color;
                    badge.textContent = status.label;
                    statusCell.appendChild(badge);
                    explanationCell.className = 'small text-muted';
                    if (record.status === 'conflict' && record.conflict_reason === 'name_mismatch') {
                        explanationCell.textContent = 'ID yang sama sudah dipakai oleh person aktif di Attendance, tetapi nama di Attendance berbeda dari nama HRIS. Periksa apakah ini orang yang sama. Jika hanya typo, minta admin Attendance membetulkan nama agar sesuai HRIS, lalu ulangi preview. Tidak ada data yang diubah oleh sync ini.';
                    } else if (record.status === 'conflict' && record.conflict_reason === 'deleted_record') {
                        explanationCell.textContent = 'ID ini masih tercatat pada person yang sudah dihapus di Attendance. Sync tidak memulihkan atau membuat ulang record tersebut. Minta admin Attendance memeriksa riwayat dan memastikan tindakan yang benar sebelum melanjutkan.';
                    } else {
                        explanationCell.textContent = status.explanation;
                    }
                    if (record.status === 'conflict') row.className = 'table-warning';
                });

                syncConfirm.disabled = !isPreview || result.meta.processed === 0 || syncRunning;
                syncRetry.hidden = true;
            }

            async function requestSync(dryRun) {
                if (dryRun) syncCanExecute = false;
                setSyncBusy(true, dryRun ? 'Memuat preview...' : 'Menyinkronkan...');
                syncResultNotice.hidden = true;
                syncError.hidden = true;
                syncRetry.hidden = true;

                try {
                    var response = await fetch(syncAttendanceButton.dataset.url, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') || {}).content || ''
                        },
                        body: JSON.stringify({ dry_run: dryRun })
                    });
                    var result;
                    try {
                        result = await response.json();
                    } catch (_) {
                        throw new Error('Server mengembalikan respons yang tidak valid (HTTP ' + response.status + ').');
                    }

                    if (!response.ok || !result.success) {
                        var message = result.message || 'Sinkronisasi ke Attendance gagal.';
                        if (!dryRun && result.meta && result.meta.processed > 0) {
                            message += ' Hasil sebagian: ' + result.meta.created + ' dibuat, ' +
                                result.meta.skipped + ' sudah ada, ' + result.meta.conflict + ' konflik.';
                        }
                        throw new Error(message);
                    }

                    renderSyncResult(result, dryRun);
                    if (!dryRun) {
                        syncAttendanceButton.dataset.count = String(result.meta.processed);
                        var resultMessage = 'Proses sync selesai. ' + result.meta.created + ' person dibuat, ' +
                            result.meta.skipped + ' sudah ada dan dilewati, ' + result.meta.conflict +
                            ' perlu pemeriksaan (tidak diubah).';
                        showSyncResultNotice(
                            resultMessage,
                            result.meta.conflict > 0 ? 'warning' : 'success'
                        );
                    }
                } catch (error) {
                    syncRows.dataset.ready = 'false';
                    syncConfirm.disabled = true;
                    syncRetry.hidden = false;
                    var errorMessage = error.message || 'Sinkronisasi ke Attendance gagal.';
                    if (!dryRun) {
                        showSyncResultNotice(errorMessage, 'danger');
                    } else {
                        syncError.textContent = errorMessage;
                        syncError.hidden = false;
                    }
                } finally {
                    setSyncBusy(false);
                }
            }

            syncAttendanceButton.addEventListener('click', function() {
                if (!syncModal || !window.bootstrap || !window.bootstrap.Modal) {
                    showToast('Komponen modal belum siap. Muat ulang halaman lalu coba lagi.', 'error');
                    return;
                }

                syncRows.dataset.ready = 'false';
                syncCanExecute = false;
                syncRows.replaceChildren();
                syncRows.insertAdjacentHTML('beforeend',
                    '<tr><td colspan="4" class="text-center text-muted py-4">' +
                    '<span class="spinner-border spinner-border-sm me-2"></span>Memeriksa data di Attendance...</td></tr>');
                syncSummary.innerHTML = '<span class="badge text-bg-secondary">Memuat preview...</span>';
                syncResultNotice.hidden = true;
                syncError.hidden = true;
                syncConfirm.disabled = true;
                syncModalInstance = window.bootstrap.Modal.getOrCreateInstance(syncModal);
                syncModalInstance.show();
                requestSync(true);
            });

            syncRetry.addEventListener('click', function() {
                requestSync(true);
            });

            syncConfirm.addEventListener('click', function() {
                if (syncRunning || syncRows.dataset.ready !== 'true') return;
                requestSync(false);
            });
        }
    </script>
@endsection
