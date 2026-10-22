{{-- Dokumen Pelengkap: NDA, Pakta Integritas, dan PB PHK bisa diunduh. Cetak saja, tidak mengubah data karyawan. --}}
<div class="modal fade" id="supplementaryDocsModal" tabindex="-1" aria-hidden="true" style="z-index:2100">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content" style="border-radius:16px">
            <div class="modal-header" style="background:#eb1c24;border-radius:16px 16px 0 0">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-file-earmark-text text-white fs-5"></i>
                    <h6 class="modal-title mb-0 text-white fw-bold">Dokumen Pelengkap</h6>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body p-4">
                <div class="p-3 rounded-3 mb-3" style="border:1px solid #eb1c24">
                    <div class="d-flex align-items-center gap-3">
                        <div id="suppDocsAvatar"
                            style="width:44px;height:44px;font-size:16px;flex-shrink:0;background:#eb1c24;color:#fff;border-radius:12px;display:flex;align-items:center;justify-content:center;font-weight:800">
                            ?</div>
                        <div class="flex-grow-1">
                            <div class="fw-bold" id="suppDocsName" style="font-size:15px;color:#eb1c24">-</div>
                            <div class="text-muted" style="font-size:12px">
                                <span id="suppDocsPosition">-</span>
                                <span class="mx-1">&bull;</span>
                                <span id="suppDocsDept">-</span>
                            </div>
                        </div>
                        <div class="text-end flex-shrink-0" style="font-size:11.5px">
                            <div class="text-muted">Employee ID</div>
                            <div class="fw-semibold" id="suppDocsEmpId" style="color:#eb1c24">-</div>
                        </div>
                    </div>
                </div>

                <label class="form-label fw-semibold" style="font-size:13px" for="suppDocsType">Jenis Dokumen <span class="text-danger">*</span></label>
                <select class="form-select form-select-sm" id="suppDocsType">
                    <option value="">— Pilih dokumen —</option>
                    <option value="nda">NDA — Surat Pernyataan Non-Disclosure</option>
                    <option value="pakta">Surat Pakta Integritas</option>
                    <option value="pb-phk">PB PHK Kartap — Perjanjian Bersama</option>
                </select>
                <div class="form-text mb-3" style="font-size:11px">NDA dan Pakta Integritas memakai data karyawan plus tanggal surat. PB PHK memakai data karyawan plus tanggal perjanjian, tanggal terakhir kerja, dan uang pisah. Data master tidak diubah.</div>

                <div id="suppDocsHrisPanel" style="display:none">
                    <p class="fw-bold mb-2" style="font-size:12px;text-transform:uppercase;letter-spacing:.05em;color:var(--color-text)">
                        <i class="bi bi-database me-1"></i>Data dari master karyawan
                    </p>
                    <div class="py-2 px-3 mb-3 rounded-3" id="suppDocsHrisNote"
                        style="font-size:12.5px;background:var(--color-bg);border:1px solid var(--color-border);color:var(--color-text)"></div>
                    <div class="row g-2" id="suppDocsHrisFields"></div>
                    <div id="suppDocsLetterDateWrap" class="mt-3" style="display:none">
                        <label class="form-label fw-semibold" style="font-size:13px" for="suppDocsLetterDate">Tanggal Surat <span class="text-danger">*</span></label>
                        <input type="date" class="form-control form-control-sm" id="suppDocsLetterDate" />
                        <div class="form-text" style="font-size:11px">Tanggal ini yang tercetak di kotak tanda tangan, bukan tanggal unduh.</div>
                    </div>
                </div>

                <div id="suppDocsPbPanel" style="display:none">
                    <p class="fw-bold mb-2" style="font-size:12px;text-transform:uppercase;letter-spacing:.05em;">
                        <i class="bi bi-person-vcard me-1"></i>Pihak II dari master karyawan
                    </p>
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <div class="cv-label text-muted" style="font-size:11px">Nama</div>
                            <div class="fw-semibold" id="suppDocsPbName" style="font-size:13px">-</div>
                        </div>
                        <div class="col-md-6">
                            <div class="cv-label text-muted" style="font-size:11px">Alamat</div>
                            <div class="fw-semibold" id="suppDocsPbAddress" style="font-size:13px">-</div>
                        </div>
                    </div>
                    <p class="fw-bold mb-2" style="font-size:12px;text-transform:uppercase;letter-spacing:.05em;color:#eb1c24">
                        <i class="bi bi-pencil-square me-1"></i>Isian perjanjian
                    </p>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="font-size:13px" for="suppDocsPbDate">Tanggal Perjanjian <span class="text-danger">*</span></label>
                            <input type="date" class="form-control form-control-sm" id="suppDocsPbDate" />
                            <div class="form-text" style="font-size:11px">Tanggal yang tercetak di perjanjian, bukan tanggal unduh.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="font-size:13px" for="suppDocsPbLastDate">Tanggal Terakhir Kerja <span class="text-danger">*</span></label>
                            <input type="date" class="form-control form-control-sm" id="suppDocsPbLastDate" />
                            <div class="form-text" style="font-size:11px">Terisi dari tanggal resign bila sudah ada.</div>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold" style="font-size:13px" for="suppDocsPbAmount">Uang Pisah <span class="text-danger">*</span></label>
                            <input type="text" inputmode="numeric" class="form-control form-control-sm" id="suppDocsPbAmount" placeholder="Contoh: 2644398" autocomplete="off" />
                            <div class="form-text" style="font-size:11px">Nominal kompensasi. Terbilang tercetak di PDF.</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-sm text-white fw-semibold" style="background:#0B2540" id="btnSuppDocsPreview" disabled>
                    <i class="bi bi-eye me-1"></i>Lihat Susunan Cetak
                </button>
            </div>
        </div>
    </div>
</div>

<script nonce="{{ request()->attributes->get('csp_nonce') }}">
    document.addEventListener('DOMContentLoaded', function() {
        var modalEl = document.getElementById('supplementaryDocsModal');
        var typeEl = document.getElementById('suppDocsType');
        var hrisPanel = document.getElementById('suppDocsHrisPanel');
        var pbPanel = document.getElementById('suppDocsPbPanel');
        var previewBtn = document.getElementById('btnSuppDocsPreview');
        var openBtn = document.getElementById('btnDrawerSupplementaryDocs');
        if (!modalEl || !typeEl || !hrisPanel || !pbPanel || !previewBtn || !openBtn) return;

        function textOrDash(value) {
            var text = value === null || value === undefined ? '' : String(value).trim();
            return text || '-';
        }

        function escapeHtml(value) {
            return textOrDash(value)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;');
        }

        function employeeAddress(emp) {
            return textOrDash(emp.citizenIdAddress || emp.residentialAddress);
        }

        function fieldCard(label, value) {
            return '<div class="col-md-6"><div class="p-2 rounded-3 h-100" style="background:var(--color-bg);border:1px solid var(--color-border);color:var(--color-text)">' +
                '<div style="font-size:11px;color:var(--color-text-soft)">' + label + '</div>' +
                '<div class="fw-semibold" style="font-size:13px;color:var(--color-text)">' + escapeHtml(value) + '</div></div></div>';
        }

        function renderHrisFields(type, emp) {
            var note = document.getElementById('suppDocsHrisNote');
            var fields = document.getElementById('suppDocsHrisFields');
            var rows = type === 'nda' ? [
                ['Nama', emp.fullName],
                ['No KTP', emp.nikNpwp],
                ['Alamat', employeeAddress(emp)],
                ['Jabatan', emp.jobPosition || emp.jobPositionLocation],
                ['Entitas', emp.branchName]
            ] : [
                ['Nama', emp.fullName],
                ['NIK', emp.nikNpwp],
                ['Jabatan', emp.jobPosition || emp.jobPositionLocation],
                ['Entitas', emp.branchName]
            ];
            if (note) {
                note.textContent = 'Identitas di bawah diambil dari data karyawan. Tanggal surat diisi sendiri.';
            }
            if (fields) fields.innerHTML = rows.map(function(row) {
                return fieldCard(row[0], row[1]);
            }).join('');
        }

        function toDateInput(value) {
            if (!value) return '';
            var text = String(value).trim();
            if (/^\d{4}-\d{2}-\d{2}/.test(text)) return text.substring(0, 10);
            return '';
        }

        function pbReady() {
            var date = (document.getElementById('suppDocsPbDate') || {}).value;
            var last = (document.getElementById('suppDocsPbLastDate') || {}).value;
            var amount = ((document.getElementById('suppDocsPbAmount') || {}).value || '').replace(/\D/g, '').replace(/^0+/, '');
            return !!(date && last && amount);
        }

        function refreshPreviewState() {
            var emp = window._activeDrawerEmployee;
            var type = typeEl.value;
            var letterDateWrap = document.getElementById('suppDocsLetterDateWrap');
            var needsLetterDate = type === 'nda' || type === 'pakta';
            hrisPanel.style.display = needsLetterDate ? 'block' : 'none';
            if (letterDateWrap) letterDateWrap.style.display = needsLetterDate ? 'block' : 'none';
            pbPanel.style.display = type === 'pb-phk' ? 'block' : 'none';
            if (needsLetterDate && emp) renderHrisFields(type, emp);
            if (type === 'pb-phk' && emp) {
                document.getElementById('suppDocsPbName').textContent = textOrDash(emp.fullName);
                document.getElementById('suppDocsPbAddress').textContent = employeeAddress(emp);
            }
            var letterDate = (document.getElementById('suppDocsLetterDate') || {}).value || '';
            var downloadable = needsLetterDate || type === 'pb-phk';
            previewBtn.disabled = !type || (needsLetterDate && !letterDate) || (type === 'pb-phk' && !pbReady());
            previewBtn.innerHTML = downloadable
                ? '<i class="bi bi-download me-1"></i>Unduh PDF'
                : '<i class="bi bi-eye me-1"></i>Lihat Susunan Cetak';
        }

        function downloadStatementPdf(emp, spec) {
            var previous = previewBtn.innerHTML;
            previewBtn.disabled = true;
            previewBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Menyiapkan PDF...';
            var params = spec.params ? spec.params() : {
                doc_date: (document.getElementById('suppDocsLetterDate') || {}).value || ''
            };
            var missing = Object.keys(params).some(function(key) { return !params[key]; });
            if (missing) {
                if (typeof window.showToast === 'function') window.showToast('Lengkapi isian surat terlebih dahulu.', 'error');
                refreshPreviewState();
                return;
            }
            var query = Object.keys(params).map(function(key) {
                return encodeURIComponent(key) + '=' + encodeURIComponent(params[key]);
            }).join('&');
            var url = spec.path + encodeURIComponent(emp.employeeId || '') + '?' + query;

            fetch(url, {
                    headers: {
                        'Accept': 'application/pdf',
                        'X-CSRF-TOKEN': typeof getCsrfToken === 'function' ? getCsrfToken() : ''
                    }
                })
                .then(function(res) {
                    if (!res.ok) {
                        return res.text().then(function(errText) {
                            throw new Error(errText ? errText.substring(0, 180) : ('Gagal unduh PDF (' + res.status + ')'));
                        });
                    }
                    var contentType = res.headers.get('content-type') || '';
                    if (contentType.indexOf('pdf') === -1) {
                        throw new Error('Response bukan PDF.');
                    }
                    return res.blob();
                })
                .then(function(blob) {
                    var safeName = String(emp.employeeId || 'karyawan').replace(/[^a-zA-Z0-9_-]/g, '_');
                    var blobUrl = window.URL.createObjectURL(blob);
                    var link = document.createElement('a');
                    link.href = blobUrl;
                    link.download = spec.file + safeName + '.pdf';
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                    window.URL.revokeObjectURL(blobUrl);
                    if (typeof window.showToast === 'function') {
                        window.showToast(spec.success, 'success');
                    }
                })
                .catch(function(err) {
                    if (typeof window.showToast === 'function') {
                        window.showToast(err && err.message ? err.message : spec.error, 'error');
                    }
                })
                .finally(function() {
                    previewBtn.innerHTML = previous;
                    refreshPreviewState();
                });
        }

        function openSupplementaryDocsModal() {
            var emp = window._activeDrawerEmployee;
            if (!emp) {
                if (typeof window.showToast === 'function') window.showToast('Data karyawan belum dimuat.', 'error');
                return;
            }
            document.getElementById('suppDocsName').textContent = textOrDash(emp.fullName);
            document.getElementById('suppDocsPosition').textContent = textOrDash(emp.jobPosition || emp.jobPositionLocation);
            document.getElementById('suppDocsDept').textContent = textOrDash(emp.department);
            document.getElementById('suppDocsEmpId').textContent = textOrDash(emp.employeeId);
            document.getElementById('suppDocsAvatar').textContent = (emp.fullName || 'K').substring(0, 2).toUpperCase();
            typeEl.value = '';
            document.getElementById('suppDocsLetterDate').value = '';
            document.getElementById('suppDocsPbDate').value = '';
            document.getElementById('suppDocsPbLastDate').value = toDateInput(emp.resignDate);
            document.getElementById('suppDocsPbAmount').value = '';
            refreshPreviewState();
            bootstrap.Modal.getOrCreateInstance(modalEl).show();
        }

        openBtn.addEventListener('click', openSupplementaryDocsModal);
        typeEl.addEventListener('change', refreshPreviewState);
        ['suppDocsLetterDate', 'suppDocsPbDate', 'suppDocsPbLastDate', 'suppDocsPbAmount'].forEach(function(id) {
            var el = document.getElementById(id);
            if (!el) return;
            el.addEventListener('input', refreshPreviewState);
            el.addEventListener('change', refreshPreviewState);
        });
        modalEl.addEventListener('shown.bs.modal', function() {
            var backdrops = document.querySelectorAll('.modal-backdrop');
            var backdrop = backdrops[backdrops.length - 1];
            if (backdrop) backdrop.style.zIndex = '2050';
        });
        previewBtn.addEventListener('click', function() {
            if (previewBtn.disabled) return;
            var emp = window._activeDrawerEmployee;
            var downloads = {
                nda: {
                    path: '/hr/export/surat-nda/',
                    file: 'Surat_NDA_',
                    success: 'Surat NDA berhasil diunduh.',
                    error: 'Gagal mengunduh Surat NDA.'
                },
                pakta: {
                    path: '/hr/export/surat-pakta-integritas/',
                    file: 'Surat_Pakta_Integritas_',
                    success: 'Surat Pakta Integritas berhasil diunduh.',
                    error: 'Gagal mengunduh Surat Pakta Integritas.'
                },
                'pb-phk': {
                    path: '/hr/export/surat-pb-phk/',
                    file: 'Perjanjian_Bersama_PHK_',
                    success: 'Perjanjian Bersama PHK berhasil diunduh.',
                    error: 'Gagal mengunduh Perjanjian Bersama PHK.',
                    params: function() {
                        return {
                            doc_date: (document.getElementById('suppDocsPbDate') || {}).value || '',
                            last_date: (document.getElementById('suppDocsPbLastDate') || {}).value || '',
                            amount: ((document.getElementById('suppDocsPbAmount') || {}).value || '').replace(/\D/g, '')
                        };
                    }
                }
            };
            if (downloads[typeEl.value]) {
                if (!emp || !emp.employeeId) {
                    if (typeof window.showToast === 'function') window.showToast('Data karyawan belum dimuat.', 'error');
                    return;
                }
                downloadStatementPdf(emp, downloads[typeEl.value]);
                return;
            }
            var label = typeEl.options[typeEl.selectedIndex].text;
            if (typeof window.showToast === 'function') {
                window.showToast('Prototype UI. "' + label + '" belum dicetak.', 'info');
            }
        });
    });
</script>
