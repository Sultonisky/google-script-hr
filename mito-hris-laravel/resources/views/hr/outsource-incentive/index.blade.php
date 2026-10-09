@extends('layouts.hr')

@section('title', 'Outsource Incentive - MITO HRIS')
@section('page-title', 'Outsource Incentive')
@section('page-subtitle', 'Import dan rekap incentive karyawan outsource per periode')

@section('content')
    <section class="page-section active">
        <div class="panel mt-2" id="outsourceIncentivePanel">
            <div class="panel-header">
                <div>
                    <h6>Outsource Incentive &middot; {{ \Illuminate\Support\Carbon::createFromFormat('Y-m', $period)->locale('id')->translatedFormat('F Y') }}</h6>
                    <div class="panel-subtitle">{{ $incentives->total() }} data incentive</div>
                </div>
                <div class="export-btns d-flex flex-wrap gap-2">
                    <a class="btn btn-sm fw-semibold text-white"
                        style="background:#005bac;border:none;border-radius:8px;padding:6px 14px;font-size:13px"
                        href="{{ route('hr.outsource-incentives.export', ['period' => $period, 'search' => $search]) }}">
                        <i class="bi bi-download me-1"></i>Export Excel
                    </a>
                    @can('manage_outsource_incentive')
                        <button class="btn btn-sm fw-semibold text-white"
                            style="background:#198754;border:none;border-radius:8px;padding:6px 14px;font-size:13px"
                            type="button"
                            data-bs-toggle="modal"
                            data-bs-target="#outsourceIncentiveImportModal">
                            <i class="bi bi-file-earmark-spreadsheet me-1"></i>Import Excel
                        </button>
                    @endcan
                </div>
            </div>

            <form action="{{ route('hr.outsource-incentives.index') }}" method="GET">
                <div class="filter-bar">
                    <div class="table-search">
                        <i class="bi bi-search"></i>
                        <input type="search" name="search" placeholder="Cari Outsource ID atau nama..." value="{{ $search }}">
                    </div>
                    <select class="filter-select" name="period" onchange="this.form.submit()">
                        @foreach ($periods->contains($period) ? $periods : $periods->prepend($period) as $periodOption)
                            <option value="{{ $periodOption }}" @selected($periodOption === $period)>
                                {{ \Illuminate\Support\Carbon::createFromFormat('Y-m', $periodOption)->locale('id')->translatedFormat('F Y') }}
                            </option>
                        @endforeach
                    </select>
                    <button class="btn-reset-filter" type="submit"><i class="bi bi-search"></i> Cari</button>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table hr-table">
                    <thead>
                        <tr>
                            <th>Outsource ID</th>
                            <th>Nama</th>
                            <th>Vendor</th>
                            <th>UMK</th>
                            <th>Incentive</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($incentives as $incentive)
                            <tr>
                                <td class="id-mono fw-bold">{{ $incentive->outsource_id }}</td>
                                <td>{{ $incentive->full_name ?? '-' }}</td>
                                <td>{{ $incentive->vendor ?? '-' }}</td>
                                <td class="id-mono">Rp {{ number_format((float) $incentive->umk_amount, 0, ',', '.') }}</td>
                                <td class=" id-mono fw-semibold">Rp {{ number_format((float) $incentive->incentive_amount, 0, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5">
                                    <div class="table-empty">
                                        <i class="bi bi-inbox"></i>
                                        <p>Belum ada data incentive untuk periode ini.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="panel-footer">
                <span>Menampilkan {{ $incentives->firstItem() ?? 0 }}–{{ $incentives->lastItem() ?? 0 }} dari {{ $incentives->total() }} data</span>
                @if ($incentives->total() > $incentives->perPage())
                    <x-pagination :currentPage="$incentives->currentPage()" :total="$incentives->total()" :perPage="$incentives->perPage()"
                        :route="'hr.outsource-incentives.index'" :queryParams="['search' => $search, 'period' => $period]" />
                @endif
            </div>
        </div>
    </section>

    @can('manage_outsource_incentive')
        <div class="modal fade" id="outsourceIncentiveImportModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content" style="border-radius:16px">
                    <div class="modal-header bg-success text-white">
                        <h6 class="modal-title fw-bold"><i class="bi bi-file-earmark-spreadsheet me-2"></i>Import Outsource Incentive</h6>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-light border small">
                            <div>Gunakan file <strong>.xlsx</strong> maksimal 10 MB. Header dan urutan kolom A–D harus sesuai template: Outsource ID, Nama, UMK (dari master outsource), dan Incentive. Jangan mengganti nama atau memindahkan kolom. Nilai UMK pada file bukan sumber import; pastikan UMK sudah diisi di master outsource. Isi nominal Incentive secara manual untuk baris yang akan diimpor; ID yang tidak ada di master outsource dilewati.</div>
                            <a href="{{ route('hr.outsource-incentives.template') }}" class="btn btn-primary btn-sm mt-2">
                                <i class="bi bi-download me-1"></i>Download Template
                            </a>
                        </div>
                        <form id="outsourceIncentiveForm">
                            <div class="row g-2">
                                <div class="col-md-8">
                                    <label class="form-label" for="outsourceIncentiveFile">File Excel</label>
                                    <input class="form-control" type="file" id="outsourceIncentiveFile" name="file" accept=".xlsx" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label" for="outsourceIncentivePeriod">Periode</label>
                                    <input class="form-control" type="month" id="outsourceIncentivePeriod" name="period" value="{{ $period }}" required>
                                </div>
                            </div>
                        </form>
                        <div id="outsourceIncentiveResult" class="mt-3" hidden></div>
                        <div id="outsourceIncentiveUmkWarning" class="alert alert-warning mt-3 mb-0 small" role="alert" hidden></div>
                        <div id="outsourceIncentivePreviewRows" class="table-responsive mt-3" hidden>
                            <table class="table table-sm mb-0">
                                <thead>
                                    <tr>
                                        <th>Outsource ID</th>
                                        <th>Nama</th>
                                        <th>Vendor</th>
                                        <th class="text-end">UMK</th>
                                        <th class="text-end">Incentive</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody id="outsourceIncentivePreviewBody"></tbody>
                            </table>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-outline-secondary btn-sm" type="button" data-bs-dismiss="modal">Tutup</button>
                        <button class="btn btn-outline-success btn-sm" id="outsourceIncentivePreview" type="button">Preview</button>
                        <button class="btn btn-success btn-sm" id="outsourceIncentiveSave" type="button" disabled>Simpan</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade modal-stacked" id="outsourceIncentiveConfirmModal" tabindex="-1" aria-hidden="true"
            aria-labelledby="outsourceIncentiveConfirmTitle" data-bs-backdrop="static">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h6 class="modal-title text-success" id="outsourceIncentiveConfirmTitle">
                            <i class="bi bi-upload me-1"></i>Simpan Outsource Incentive
                        </h6>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>
                    <div class="modal-body">
                        <p class="mb-3">Data incentive periode <strong data-oic="period">-</strong> akan disimpan. Perubahan hanya berlaku pada data Outsource Incentive, tidak mengubah UMK maupun incentive di master outsource.</p>
                        <div id="outsourceIncentiveConfirmUmkWarning" class="alert alert-warning small" role="alert" hidden></div>
                        <div class="border rounded p-3 bg-light">
                            <div class="row g-2">
                                <div class="col-4 text-muted small">File</div>
                                <div class="col-8 fw-semibold text-break" data-oic="file">-</div>
                                <div class="col-4 text-muted small">Data baru</div>
                                <div class="col-8 fw-semibold" data-oic="created">0</div>
                                <div class="col-4 text-muted small">Diperbarui</div>
                                <div class="col-8 fw-semibold" data-oic="updated">0</div>
                                <div class="col-4 text-muted small">Tidak berubah</div>
                                <div class="col-8" data-oic="unchanged">0</div>
                                <div class="col-4 text-muted small">Dilewati</div>
                                <div class="col-8" data-oic="skipped">0</div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="button" class="btn btn-success" id="outsourceIncentiveConfirmSave">
                            <i class="bi bi-check2-circle me-1"></i>Ya, Simpan
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endcan
@endsection

@section('scripts')
    @can('manage_outsource_incentive')
        <script nonce="{{ request()->attributes->get('csp_nonce') }}">
            (function() {
                var form = document.getElementById('outsourceIncentiveForm');
                if (!form) return;
                var resultEl = document.getElementById('outsourceIncentiveResult');
                var umkWarningEl = document.getElementById('outsourceIncentiveUmkWarning');
                var confirmUmkWarningEl = document.getElementById('outsourceIncentiveConfirmUmkWarning');
                var previewRowsEl = document.getElementById('outsourceIncentivePreviewRows');
                var previewBody = document.getElementById('outsourceIncentivePreviewBody');
                var previewButton = document.getElementById('outsourceIncentivePreview');
                var saveButton = document.getElementById('outsourceIncentiveSave');
                var confirmModalEl = document.getElementById('outsourceIncentiveConfirmModal');
                var confirmButton = document.getElementById('outsourceIncentiveConfirmSave');
                var lastPreview = null;
                var endpoint = @json(route('hr.outsource-incentives.import'));

                function periodLabel(value) {
                    var parts = String(value || '').split('-');
                    if (parts.length !== 2) return value || '-';
                    return new Date(Number(parts[0]), Number(parts[1]) - 1, 1)
                        .toLocaleDateString('id-ID', { month: 'long', year: 'numeric' });
                }

                function setConfirm(key, value) {
                    var element = confirmModalEl.querySelector('[data-oic="' + key + '"]');
                    if (element) element.textContent = value;
                }

                function escapeHtml(value) {
                    var element = document.createElement('span');
                    element.textContent = String(value == null ? '' : value);
                    return element.innerHTML;
                }

                function render(data) {
                    var summary = data.summary || {};
                    var errors = Array.isArray(data.errors) ? data.errors : Object.values(data.errors || {}).flat();
                    var warnings = Array.isArray(data.warnings) ? data.warnings : [];
                    var rows = Array.isArray(data.rows) ? data.rows : [];
                    var missingUmkRows = rows.filter(function(row) {
                        return row.umk_amount == null || row.umk_amount === '' || !Number.isFinite(Number(row.umk_amount)) || Number(row.umk_amount) <= 0;
                    });
                    resultEl.innerHTML = '<div class="alert ' + (data.success ? 'alert-info' : 'alert-danger') + '">' +
                        escapeHtml(data.message || 'Import gagal.') +
                        '<div class="mt-1">Dibaca: ' + (summary.read || 0) + ', baru: ' + (summary.created || 0) +
                        ', diperbarui: ' + (summary.updated || 0) + ', tidak berubah: ' + (summary.unchanged || 0) +
                        ', dilewati: ' + (summary.skipped || 0) + '</div>' +
                        (errors.length ? '<hr><div>' + errors.map(escapeHtml).join('<br>') + '</div>' : '') +
                        (warnings.length ? '<hr><div>' + warnings.map(escapeHtml).join('<br>') + '</div>' : '') +
                        '</div>';
                    resultEl.hidden = false;
                    if (missingUmkRows.length) {
                        var missingUmkPeople = missingUmkRows.map(function(row) {
                            return escapeHtml(row.outsource_id) + ' (' + escapeHtml(row.full_name || '-') + ')';
                        }).join(', ');
                        var warning = 'UMK belum diatur atau bernilai 0 di Master Outsource untuk: ' + missingUmkPeople +
                            '. Lengkapi UMK di master data outsource sebelum menyimpan/generate incentive. Nilai UMK dari file Excel tidak digunakan.';
                        umkWarningEl.textContent = warning;
                        umkWarningEl.hidden = false;
                    } else {
                        umkWarningEl.textContent = '';
                        umkWarningEl.hidden = true;
                    }
                    previewBody.innerHTML = rows.map(function(row) {
                        var umk = 'Rp ' + Number(row.umk_amount || 0).toLocaleString('id-ID', { maximumFractionDigits: 0 });
                        var incentive = 'Rp ' + Number(row.incentive_amount || 0).toLocaleString('id-ID', { maximumFractionDigits: 0 });
                        var status = row.status === 'updated' ? 'Diperbarui' : (row.status === 'unchanged' ? 'Tidak berubah' : 'Baru');
                        return '<tr><td>' + escapeHtml(row.outsource_id) + '</td><td>' + escapeHtml(row.full_name || '-') +
                            '</td><td>' + escapeHtml(row.vendor || '-') + '</td><td class="text-end">' + escapeHtml(umk) +
                            '</td><td class="text-end">' + escapeHtml(incentive) + '</td><td>' + status + '</td></tr>';
                    }).join('');
                    previewRowsEl.hidden = rows.length === 0;
                    saveButton.disabled = !data.success || (summary.created || 0) + (summary.updated || 0) === 0;
                }

                function send(dryRun) {
                    if (!form.reportValidity()) return;
                    var data = new FormData(form);
                    data.append('dry_run', dryRun ? '1' : '0');
                    previewButton.disabled = true;
                    saveButton.disabled = true;
                    fetch(endpoint, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') || {}).content || ''
                        },
                        body: data
                    }).then(function(response) {
                        return response.json().then(function(body) {
                            if (!response.ok && !body.message) throw new Error('Import gagal. Periksa file dan coba lagi.');
                            return body;
                        });
                    }).then(function(body) {
                        render(body);
                        if (dryRun) {
                            lastPreview = body;
                        } else if (body.success) {
                            window.location.href = @json(route('hr.outsource-incentives.index')) + '?period=' + encodeURIComponent(body.period);
                        }
                    }).catch(function(error) {
                        resultEl.innerHTML = '<div class="alert alert-danger">' + escapeHtml(error.message || 'Gagal menghubungi server.') + '</div>';
                        resultEl.hidden = false;
                    }).finally(function() {
                        previewButton.disabled = false;
                        if (!lastPreview || !lastPreview.success) saveButton.disabled = true;
                    });
                }

                previewButton.addEventListener('click', function() {
                    lastPreview = null;
                    send(true);
                });
                saveButton.addEventListener('click', function() {
                    if (!lastPreview || !lastPreview.success) return;
                    var summary = lastPreview.summary || {};
                    var fileInput = document.getElementById('outsourceIncentiveFile');
                    setConfirm('period', periodLabel(document.getElementById('outsourceIncentivePeriod').value));
                    setConfirm('file', fileInput.files.length ? fileInput.files[0].name : '-');
                    setConfirm('created', summary.created || 0);
                    setConfirm('updated', summary.updated || 0);
                    setConfirm('unchanged', summary.unchanged || 0);
                    setConfirm('skipped', summary.skipped || 0);
                    confirmUmkWarningEl.textContent = umkWarningEl.textContent;
                    confirmUmkWarningEl.hidden = umkWarningEl.hidden;
                    bootstrap.Modal.getOrCreateInstance(confirmModalEl).show();
                });
                confirmButton.addEventListener('click', function() {
                    bootstrap.Modal.getOrCreateInstance(confirmModalEl).hide();
                    send(false);
                });
                confirmModalEl.addEventListener('hidden.bs.modal', function() {
                    if (document.getElementById('outsourceIncentiveImportModal').classList.contains('show')) {
                        document.body.classList.add('modal-open');
                    }
                });
                form.addEventListener('change', function() {
                    lastPreview = null;
                    saveButton.disabled = true;
                });
            })();
        </script>
    @endcan
@endsection
