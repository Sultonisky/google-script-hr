{{--
  Modal Import Excel Karyawan Outsource.
  Memakai OutsourceXlsxImportService yang sama dengan `php artisan mito:outsource:import-xlsx`.
--}}
<div class="modal fade" id="outsourceImportModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content" style="border-radius:16px">
            <div class="modal-header" style="background:#198754;border-radius:16px 16px 0 0">
                <div class="d-flex align-items-center gap-2 text-white">
                    <i class="bi bi-file-earmark-spreadsheet-fill fs-5"></i>
                    <h6 class="modal-title mb-0 fw-bold">Import Data Outsource (Excel)</h6>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body p-4">
                <div class="alert alert-light border py-2 mb-3" style="font-size:12.5px">
                    <div class="fw-semibold mb-1"><i class="bi bi-info-circle-fill text-primary me-1"></i>Ketentuan file</div>
                    <ul class="mb-0 ps-3">
                        <li>Format <strong>.xlsx</strong>, maksimal 10 MB. Baris 1 = header, data mulai baris 2.</li>
                        <li>Urutan kolom A–V: ID Karyawan, Nama, Alamat KTP, Tgl Lahir, Kota Kelahiran, Pendidikan, No WA, Email,
                            Jabatan, Lokasi Kerja, Kota Lokasi Kerja, No Rekening BCA, Tgl Join Mito, Tgl Awal Kontrak (Damarindo),
                            Tgl Akhir Kontrak (StaffInc), Cabang (Cost Center), Entity, Skema Penggajian, Nominal UMK,
                            Gaji Pokok, Insentif, Remarks.</li>
                        <li>Data dengan ID yang sudah ada akan <strong>diperbarui</strong>; sel kosong tidak menimpa data lama.</li>
                        <li>Baris tanpa ID dicocokkan lewat No WA/email; jika tidak ada, dibuat baru dengan ID berikutnya.</li>
                        @cannot('manage_outsource_compensation')
                            <li class="text-danger">Kolom Gaji Pokok dan Insentif (T–U) akan diabaikan karena Anda tidak memiliki izin mengelola kolom tersebut.</li>
                        @endcannot
                    </ul>
                </div>

                <form id="osImportForm" novalidate>
                    <div class="row g-2">
                        <div class="col-12">
                            <label class="form-label fw-semibold" for="osiFile" style="font-size:12.5px">File Excel <span class="text-danger">*</span></label>
                            <input type="file" class="form-control form-control-sm" id="osiFile" name="file"
                                accept=".xlsx,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" required />
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="osiVendor" style="font-size:12.5px">Vendor <span class="text-danger">*</span></label>
                            <select class="form-select form-select-sm" id="osiVendor" name="vendor" required>
                                @foreach (config('hris.outsource.vendors', []) as $vendor)
                                    <option value="{{ $vendor }}">{{ $vendor }}</option>
                                @endforeach
                            </select>
                            <div class="form-text" style="font-size:11.5px">Diterapkan ke seluruh baris di file.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="osiSheet" style="font-size:12.5px">Nama Sheet</label>
                            <input type="text" class="form-control form-control-sm" id="osiSheet" name="sheet" value="RAW DATA" maxlength="100" />
                        </div>
                    </div>
                </form>

                <div id="osiResult" class="mt-3" style="display:none">
                    <div class="d-flex flex-wrap gap-2 mb-2" id="osiSummary"></div>
                    <div id="osiErrors" class="alert alert-danger py-2 mb-2" style="display:none;font-size:12.5px"></div>
                    <div id="osiWarnings" style="display:none">
                        <div class="fw-semibold mb-1" style="font-size:12.5px"><i class="bi bi-exclamation-triangle-fill text-warning me-1"></i>Peringatan</div>
                        <ul class="border rounded py-2 pe-2 mb-0" id="osiWarningList"
                            style="max-height:180px;overflow-y:auto;font-size:12px;padding-left:1.75rem"></ul>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Tutup</button>
                <button type="button" class="btn btn-outline-success btn-sm fw-semibold" id="btnOsiPreview">
                    <i class="bi bi-eye me-1"></i>Preview
                </button>
                <button type="button" class="btn btn-success btn-sm fw-semibold" id="btnOsiImport" disabled
                    title="Jalankan Preview terlebih dahulu">
                    <i class="bi bi-upload me-1"></i>Import
                </button>
            </div>
        </div>
    </div>
</div>

<script nonce="{{ request()->attributes->get('csp_nonce') }}">
    (function() {
        var modalEl = document.getElementById('outsourceImportModal');
        var form = document.getElementById('osImportForm');
        if (!modalEl || !form) return;

        var importUrl = @json(route('hr.outsource.import'));
        var btnPreview = document.getElementById('btnOsiPreview');
        var btnImport = document.getElementById('btnOsiImport');
        var resultEl = document.getElementById('osiResult');
        var imported = false;

        function escapeHtml(s) {
            var div = document.createElement('div');
            div.textContent = String(s);
            return div.innerHTML;
        }

        function badge(label, value, cls) {
            return '<span class="badge ' + cls + '" style="font-size:12px;padding:6px 10px">' + escapeHtml(label) + ': ' + value + '</span>';
        }

        function resetResult() {
            btnImport.disabled = true;
            resultEl.style.display = 'none';
        }

        function renderResult(data) {
            var s = data.summary || {};
            var dry = !!data.dry_run;
            document.getElementById('osiSummary').innerHTML =
                badge('Dibaca', s.read || 0, 'bg-secondary') +
                badge(dry ? 'Akan dibuat' : 'Dibuat', s.created || 0, 'bg-success') +
                badge(dry ? 'Akan diperbarui' : 'Diperbarui', s.updated || 0, 'bg-primary') +
                badge('Peringatan', (data.warnings || []).length, 'bg-warning text-dark') +
                badge('Error', (data.errors || []).length, 'bg-danger');

            var errorsEl = document.getElementById('osiErrors');
            var errors = data.errors || [];
            errorsEl.style.display = errors.length ? '' : 'none';
            errorsEl.innerHTML = errors.map(escapeHtml).join('<br>');

            var warnings = data.warnings || [];
            document.getElementById('osiWarnings').style.display = warnings.length ? '' : 'none';
            document.getElementById('osiWarningList').innerHTML = warnings.map(function(w) {
                return '<li>' + escapeHtml(w) + '</li>';
            }).join('');

            resultEl.style.display = '';
        }

        function submit(dryRun) {
            var fileInput = document.getElementById('osiFile');
            if (!fileInput.files.length) {
                showToast('Pilih file Excel terlebih dahulu.', 'warning');
                return;
            }

            var fd = new FormData(form);
            fd.append('dry_run', dryRun ? '1' : '0');

            var btn = dryRun ? btnPreview : btnImport;
            var origHtml = btn.innerHTML;
            btnPreview.disabled = true;
            btnImport.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>' + (dryRun ? 'Membaca...' : 'Mengimpor...');

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

                    if (!dryRun && data.summary) imported = true;

                    if (r.ok && data.success) {
                        showToast(data.message, 'success');
                        if (dryRun) btnImport.disabled = false;
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
            if (!confirm('Simpan data dari file ini ke data outsource?')) return;
            submit(false);
        });
        form.addEventListener('submit', function(e) { e.preventDefault(); });
        form.addEventListener('change', resetResult);

        modalEl.addEventListener('hidden.bs.modal', function() {
            if (imported) {
                window.location.reload();
                return;
            }
            form.reset();
            resetResult();
        });
    })();
</script>
