{{--
  Modal Import Payslip Outsource (Excel). Preview dulu, lalu simpan.
  Memakai OutsourcePayslipImportService; kolom A–G mengikuti template.
--}}
<div class="modal fade" id="outsourcePayslipImportModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content" style="border-radius:16px">
            <div class="modal-header" style="background:#198754;border-radius:16px 16px 0 0">
                <div class="d-flex align-items-center gap-2 text-white">
                    <i class="bi bi-file-earmark-spreadsheet-fill fs-5"></i>
                    <h6 class="modal-title mb-0 fw-bold">Import Payslip Outsource (Excel)</h6>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body p-4">
                <div class="alert alert-light border py-2 mb-3" style="font-size:12.5px">
                    <div class="fw-semibold mb-1"><i class="bi bi-info-circle-fill text-primary me-1"></i>Ketentuan file</div>
                    <ul class="mb-2 ps-3">
                        <li>Format <strong>.xlsx</strong>, maksimal 10 MB. Sheet pertama dipakai; baris 1 = header, data mulai baris 2.</li>
                        <li>Urutan kolom A–G: Outsource ID, Nama, Total HKE, Gaji Pokok, Potongan BPJS Kesehatan, Potongan Pinjaman, THP.</li>
                        <li>Total HKE, Gaji Pokok, dan THP wajib diisi. Potongan yang kosong dianggap 0. THP disimpan sesuai file.</li>
                        <li>Baris tanpa nominal diabaikan, dan Outsource ID yang tidak ada di master data outsource dilewati.</li>
                        <li>Import ulang pada periode yang sama: data yang <strong>sama</strong> dilewati, data yang <strong>berbeda</strong> diperbarui sesuai file.</li>
                    </ul>
                    <a href="{{ route('hr.outsource-payslips.template') }}" class="btn btn-primary btn-sm fw-semibold"
                        id="btnOpiTemplate">
                        <i class="bi bi-download me-1"></i>Download Template
                    </a>
                </div>

                <form id="opsImportForm" novalidate>
                    <div class="row g-2">
                        <div class="col-md-8">
                            <label class="form-label fw-semibold" for="opiFile" style="font-size:12.5px">File Excel <span class="text-danger">*</span></label>
                            <input type="file" class="form-control form-control-sm" id="opiFile" name="file"
                                accept=".xlsx,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" required />
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold" for="opiPeriod" style="font-size:12.5px">Periode <span class="text-danger">*</span></label>
                            <input type="month" class="form-control form-control-sm" id="opiPeriod" name="period"
                                value="{{ $defaultPeriod ?? now()->timezone('Asia/Jakarta')->format('Y-m') }}" required />
                        </div>
                    </div>
                </form>

                <div id="opiResult" class="mt-3" style="display:none">
                    <div class="d-flex flex-wrap gap-2 mb-2" id="opiSummary"></div>
                    <div id="opiErrors" class="alert alert-danger py-2 mb-2" style="display:none;font-size:12.5px;max-height:180px;overflow-y:auto"></div>
                    <div id="opiWarnings" class="mb-2" style="display:none">
                        <div class="fw-semibold mb-1" style="font-size:12.5px"><i class="bi bi-exclamation-triangle-fill text-warning me-1"></i>Peringatan</div>
                        <ul class="border rounded py-2 pe-2 mb-0" id="opiWarningList"
                            style="max-height:140px;overflow-y:auto;font-size:12px;padding-left:1.75rem"></ul>
                    </div>
                    <div id="opiPreview" class="table-responsive border rounded" style="display:none;max-height:320px">
                        <table class="table table-sm mb-0" style="font-size:12px">
                            <thead class="table-light" style="position:sticky;top:0">
                                <tr>
                                    <th>Outsource ID</th>
                                    <th>Nama</th>
                                    <th>Vendor</th>
                                    <th class="text-end">HKE</th>
                                    <th class="text-end">Gaji Pokok</th>
                                    <th class="text-end">Pot. BPJS Kes</th>
                                    <th class="text-end">Pot. Pinjaman</th>
                                    <th class="text-end">THP</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody id="opiPreviewBody"></tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Tutup</button>
                <button type="button" class="btn btn-outline-success btn-sm fw-semibold" id="btnOpiPreview">
                    <i class="bi bi-eye me-1"></i>Preview
                </button>
                <button type="button" class="btn btn-success btn-sm fw-semibold" id="btnOpiImport" disabled
                    title="Jalankan Preview terlebih dahulu">
                    <i class="bi bi-upload me-1"></i>Simpan Payslip
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Konfirmasi simpan; tampil di atas modal import (lihat .modal-stacked di _modal.scss). --}}
<div class="modal fade modal-stacked" id="outsourcePayslipConfirmModal" tabindex="-1" aria-hidden="true"
    aria-labelledby="opcTitle" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title text-success" id="opcTitle"><i class="bi bi-upload me-1"></i>Simpan Payslip</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="mb-3">Payslip periode <strong data-opc="period">-</strong> akan disimpan. Hanya payslip baru dan yang
                    nominalnya <strong>berbeda</strong> yang ditulis; data yang sama tidak diubah.</p>
                <div class="border rounded p-3 bg-light">
                    <div class="row g-2">
                        <div class="col-4 text-muted small">File</div>
                        <div class="col-8 fw-semibold text-break" data-opc="file">-</div>
                        <div class="col-4 text-muted small">Payslip baru</div>
                        <div class="col-8 fw-semibold" data-opc="created">0</div>
                        <div class="col-4 text-muted small">Diperbarui</div>
                        <div class="col-8 fw-semibold" data-opc="updated">0</div>
                        <div class="col-4 text-muted small">Tidak berubah</div>
                        <div class="col-8" data-opc="unchanged">0</div>
                        <div class="col-4 text-muted small">Dilewati</div>
                        <div class="col-8" data-opc="skipped">0</div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-success" id="btnOpcConfirm"><i class="bi bi-check2-circle me-1"></i>Ya, Simpan</button>
            </div>
        </div>
    </div>
</div>

<script nonce="{{ request()->attributes->get('csp_nonce') }}">
    (function() {
        var modalEl = document.getElementById('outsourcePayslipImportModal');
        var form = document.getElementById('opsImportForm');
        if (!modalEl || !form) return;

        var importUrl = @json(route('hr.outsource-payslips.import'));
        var indexUrl = @json(route('hr.outsource-payslips.index'));
        var btnPreview = document.getElementById('btnOpiPreview');
        var btnImport = document.getElementById('btnOpiImport');
        var resultEl = document.getElementById('opiResult');
        var confirmEl = document.getElementById('outsourcePayslipConfirmModal');
        var btnConfirm = document.getElementById('btnOpcConfirm');
        var savedPeriod = null;
        var lastPreview = null;

        function periodLabel(value) {
            var parts = String(value || '').split('-');
            if (parts.length !== 2) return value || '-';
            return new Date(Number(parts[0]), Number(parts[1]) - 1, 1)
                .toLocaleDateString('id-ID', { month: 'long', year: 'numeric' });
        }

        function setConfirm(key, value) {
            var el = confirmEl.querySelector('[data-opc="' + key + '"]');
            if (el) el.textContent = value;
        }

        function escapeHtml(s) {
            var div = document.createElement('div');
            div.textContent = String(s == null ? '' : s);
            return div.innerHTML;
        }

        function rupiah(v) {
            return 'Rp ' + Number(v || 0).toLocaleString('id-ID', { maximumFractionDigits: 0 });
        }

        function badge(label, value, cls) {
            return '<span class="badge ' + cls + '" style="font-size:12px;padding:6px 10px">' + escapeHtml(label) + ': ' + value + '</span>';
        }

        function statusBadge(status) {
            if (status === 'updated') return '<span class="badge bg-primary">Diperbarui</span>';
            if (status === 'unchanged') return '<span class="badge bg-light text-dark border">Tidak berubah</span>';
            return '<span class="badge bg-success">Baru</span>';
        }

        function resetResult() {
            btnImport.disabled = true;
            lastPreview = null;
            resultEl.style.display = 'none';
        }

        function renderResult(data) {
            var s = data.summary || {};
            var dry = !!data.dry_run;
            document.getElementById('opiSummary').innerHTML =
                badge('Dibaca', s.read || 0, 'bg-secondary') +
                badge(dry ? 'Akan dibuat' : 'Dibuat', s.created || 0, 'bg-success') +
                badge(dry ? 'Akan diperbarui' : 'Diperbarui', s.updated || 0, 'bg-primary') +
                badge('Tidak berubah', s.unchanged || 0, 'bg-light text-dark border') +
                badge('Dilewati', s.skipped || 0, 'bg-warning text-dark') +
                badge('Error', (data.errors || []).length, 'bg-danger');

            var errors = data.errors || [];
            var errorsEl = document.getElementById('opiErrors');
            errorsEl.style.display = errors.length ? '' : 'none';
            errorsEl.innerHTML = errors.map(escapeHtml).join('<br>');

            var warnings = data.warnings || [];
            document.getElementById('opiWarnings').style.display = warnings.length ? '' : 'none';
            document.getElementById('opiWarningList').innerHTML = warnings.map(function(w) {
                return '<li>' + escapeHtml(w) + '</li>';
            }).join('');

            var rows = data.rows || [];
            document.getElementById('opiPreview').style.display = rows.length ? '' : 'none';
            document.getElementById('opiPreviewBody').innerHTML = rows.map(function(r) {
                return '<tr>' +
                    '<td class="fw-semibold">' + escapeHtml(r.outsource_id) + '</td>' +
                    '<td>' + escapeHtml(r.full_name || '-') + '</td>' +
                    '<td>' + escapeHtml(r.vendor || '-') + '</td>' +
                    '<td class="text-end">' + escapeHtml(Number(r.hke).toLocaleString('id-ID')) + '</td>' +
                    '<td class="text-end">' + rupiah(r.basic_salary) + '</td>' +
                    '<td class="text-end">' + rupiah(r.bpjs_kesehatan_deduction) + '</td>' +
                    '<td class="text-end">' + rupiah(r.loan_deduction) + '</td>' +
                    '<td class="text-end fw-semibold">' + rupiah(r.take_home_pay) + '</td>' +
                    '<td>' + statusBadge(r.status) + '</td>' +
                    '</tr>';
            }).join('');

            resultEl.style.display = '';
        }

        function submit(dryRun) {
            var fileInput = document.getElementById('opiFile');
            if (!fileInput.files.length) {
                showToast('Pilih file Excel terlebih dahulu.', 'warning');
                return;
            }
            if (!document.getElementById('opiPeriod').value) {
                showToast('Pilih periode payslip terlebih dahulu.', 'warning');
                return;
            }

            var fd = new FormData(form);
            fd.append('dry_run', dryRun ? '1' : '0');

            var btn = dryRun ? btnPreview : btnImport;
            var origHtml = btn.innerHTML;
            btnPreview.disabled = true;
            btnImport.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>' + (dryRun ? 'Membaca...' : 'Menyimpan...');

            fetch(importUrl, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') || {}).content || ''
                    },
                    body: fd
                })
                .then(function(res) {
                    return res.json().catch(function() { return {}; }).then(function(data) {
                        return { ok: res.ok, data: data };
                    });
                })
                .then(function(r) {
                    var data = r.data || {};
                    if (data.summary) renderResult(data);

                    if (r.ok && data.success) {
                        showToast(data.message, 'success');
                        if (dryRun) {
                            lastPreview = data;
                            btnImport.disabled = (data.summary.created || 0) + (data.summary.updated || 0) === 0;
                        } else {
                            savedPeriod = data.period;
                            try { sessionStorage.setItem('opsImportToast', data.message); } catch (e) {}
                            bootstrap.Modal.getOrCreateInstance(modalEl).hide();
                        }
                        return;
                    }

                    var msg = data.message || 'Terjadi kesalahan. Coba lagi.';
                    if (data.errors && !Array.isArray(data.errors)) msg = Object.values(data.errors).flat().join(' ');
                    showToast(msg, 'error');
                })
                .catch(function() {
                    showToast('Gagal menghubungi server. Coba lagi.', 'error');
                })
                .finally(function() {
                    btnPreview.disabled = false;
                    btn.innerHTML = origHtml;
                });
        }

        btnPreview.addEventListener('click', function() { submit(true); });
        btnImport.addEventListener('click', function() {
            var s = (lastPreview && lastPreview.summary) || {};
            var fileInput = document.getElementById('opiFile');
            setConfirm('period', periodLabel(document.getElementById('opiPeriod').value));
            setConfirm('file', fileInput.files.length ? fileInput.files[0].name : '-');
            setConfirm('created', s.created || 0);
            setConfirm('updated', s.updated || 0);
            setConfirm('unchanged', s.unchanged || 0);
            setConfirm('skipped', s.skipped || 0);
            bootstrap.Modal.getOrCreateInstance(confirmEl).show();
        });
        btnConfirm.addEventListener('click', function() {
            bootstrap.Modal.getOrCreateInstance(confirmEl).hide();
            submit(false);
        });
        // Menutup modal konfirmasi menghapus .modal-open dari body walau modal import masih terbuka.
        confirmEl.addEventListener('hidden.bs.modal', function() {
            if (modalEl.classList.contains('show')) document.body.classList.add('modal-open');
        });
        form.addEventListener('submit', function(e) { e.preventDefault(); });
        form.addEventListener('change', resetResult);

        modalEl.addEventListener('hidden.bs.modal', function() {
            if (savedPeriod) {
                window.location.href = indexUrl + '?period=' + encodeURIComponent(savedPeriod);
                return;
            }
            form.reset();
            resetResult();
        });
    })();
</script>
