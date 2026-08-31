<!-- partials/EntityModals.html — OFFBOARDING, IMPORT CSV/EXCEL & PROMOTE PROBATION MODALS (1:1 from GAS) -->

<style>
    #offboardingModal .modal-content {
        max-height: calc(100vh - 32px);
    }

    #offboardingModal #formOffboarding {
        display: flex;
        flex: 1 1 auto;
        flex-direction: column;
        min-height: 0;
    }

    #offboardingModal #formOffboarding .modal-body {
        flex: 1 1 auto;
        min-height: 0;
        overflow-y: auto;
    }

    #offboardingModal #formOffboarding .modal-footer {
        flex: 0 0 auto;
    }
</style>

<!-- 1. OFFBOARDING MODAL -->
<div class="modal fade" id="offboardingModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content" style="border-radius:16px">
            <div class="modal-header" style="background:#eb1c24;border-radius:16px 16px 0 0">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-box-arrow-right text-white fs-5"></i>
                    <h6 class="modal-title mb-0 text-white fw-bold">Proses Offboarding Karyawan</h6>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            {{-- enctype="multipart/form-data" required for file attachments --}}
            <form method="POST" id="formOffboarding" enctype="multipart/form-data">
                @csrf
                <div class="modal-body p-4">
                    <input type="hidden" id="offEmployeeId" name="employee_id" />

                    <!-- STEP 1: Live Search karyawan -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold" style="font-size:13px">
                            <i class="bi bi-search me-1"></i>Cari Karyawan Aktif <span class="text-primary">*</span>
                        </label>
                        <div class="position-relative">
                            <input type="text" class="form-control" id="offEmpSearch"
                                placeholder="Ketik nama atau Employee ID..." autocomplete="off"
                                style="font-size:13px;padding-right:36px" />
                            <i class="bi bi-x-circle-fill position-absolute" id="offEmpSearchClear"
                                style="right:10px;top:50%;transform:translateY(-50%);cursor:pointer;color:#aaa;display:none"></i>
                        </div>
                        <div id="offEmpDropdown" class="border rounded-3 mt-1 shadow-sm"
                            style="display:none;max-height:220px;overflow-y:auto;z-index:9999;position:relative">
                        </div>
                    </div>

                    <!-- STEP 2: Preview karyawan terpilih + form -->
                    <div id="offEmpPreview" style="display:none">
                        <div class="p-3 rounded-3 mb-4" style="border:1px solid #eb1c24">
                            <div class="d-flex align-items-center gap-3">
                                <div id="offEmpAvatar"
                                    style="width:44px;height:44px;font-size:16px;flex-shrink:0;background:#eb1c24;color:#fff;border-radius:12px;display:flex;align-items:center;justify-content:center;font-weight:800">
                                    ?</div>
                                <div class="flex-grow-1">
                                    <div class="fw-bold text-primary" id="offEmpName" style="font-size:15px">-</div>
                                    <div class="text-muted" style="font-size:12px">
                                        <span id="offEmpPosition">-</span> <span class="mx-1">&bull;</span> <span
                                            id="offEmpDept">-</span>
                                    </div>
                                </div>
                                <div class="text-end flex-shrink-0" style="font-size:11.5px">
                                    <div class="text-muted">Employee ID</div>
                                    <div class="fw-semibold text-primary" id="offEmpIdDisp">-</div>
                                    <div class="text-muted mt-1">Status Saat Ini</div>
                                    <div class="fw-semibold" id="offEmpStatusDisp">-</div>
                                </div>
                            </div>
                        </div>

                        <!-- Form offboarding -->
                        <div id="offFormSection">
                            <p class="fw-bold mb-3"
                                style="font-size:12px;text-transform:uppercase;letter-spacing:0.05em;color:#eb1c24">
                                <i class="bi bi-info-circle me-1"></i>Detail Offboarding
                            </p>
                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    {{--
                    Offboarding types 1:1 GAS _OFFB_DOC_TYPES:
                    Resignation / Termination / Retirement / Death
                  --}}
                                    <label class="form-label fw-semibold" style="font-size:13px">Tipe Offboarding <span
                                            class="text-primary">*</span></label>
                                    <select class="form-select form-select-sm" name="offboarding_type" id="offType"
                                        required>
                                        <option value="">— Pilih Tipe —</option>
                                        <option value="Resignation">Pengunduran Diri (Resignation)</option>
                                        <option value="Termination">Pemutusan Hubungan Kerja (Termination)</option>
                                        <option value="Retirement">Pensiun (Retirement)</option>
                                        <option value="Death">Meninggal Dunia (Death)</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold" style="font-size:13px">Tanggal Efektif <span
                                            class="text-primary">*</span></label>
                                    <input type="date" class="form-control form-control-sm" name="last_working_date"
                                        id="offEffectiveDate" value="{{ date('Y-m-d') }}" required />
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label fw-semibold" style="font-size:13px">Alasan Offboarding
                                        <span class="text-primary">*</span></label>
                                    <textarea class="form-control form-control-sm" name="reason" id="offReason" rows="2"
                                        placeholder="Tuliskan alasan pengunduran diri / pemutusan hubungan kerja..." required></textarea>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold" style="font-size:13px">No. BPJS
                                        Ketenagakerjaan</label>
                                    <input type="text" class="form-control form-control-sm" name="bpjs_tk"
                                        id="offBpjsTk" placeholder="Untuk proses klaim JHT" />
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold" style="font-size:13px">No. BPJS
                                        Kesehatan</label>
                                    <input type="text" class="form-control form-control-sm" name="bpjs_kes"
                                        id="offBpjsKes" placeholder="Untuk proses nonaktivasi" />
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label fw-semibold" style="font-size:13px">Approved By</label>
                                    <input type="text" class="form-control form-control-sm" name="approved_by"
                                        id="offApprovedBy" value="Human Resources Department" />
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label fw-semibold" style="font-size:13px">Catatan
                                        Tambahan</label>
                                    <textarea class="form-control form-control-sm" name="notes" id="offNotes" rows="2"
                                        placeholder="Catatan internal tim HR..."></textarea>
                                </div>
                            </div>

                            <!-- DYNAMIC ATTACHMENT SECTION — 1:1 GAS _OFFB_DOC_TYPES -->
                            <div id="offAttachmentSection" style="display:none;">
                                <hr class="my-3">
                                <p class="fw-bold mb-3"
                                    style="font-size:12px;text-transform:uppercase;letter-spacing:.05em;color:#eb1c24">
                                    <i class="bi bi-paperclip me-1"></i>Lampiran Dokumen
                                </p>
                                <div id="offAttachmentRequired" class="mb-2"></div>
                                <div id="offAttachmentOptional" class="mb-2"></div>
                            </div>

                            <!-- Validation feedback -->
                            <div id="offAttachmentError" class="alert alert-primary py-2 d-none"
                                style="font-size:13px" role="alert">
                                <i class="bi bi-exclamation-triangle-fill me-1"></i>
                                <span id="offAttachmentErrorMsg"></span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary btn-sm"
                        data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-sm text-white fw-semibold" style="background:#eb1c24"
                        id="btnConfirmOffboarding" disabled>
                        <span id="btnOffbText"><i class="bi bi-box-arrow-right me-1"></i>Proses Offboarding</span>
                        <span id="btnOffbLoading" class="d-none"><span
                                class="spinner-border spinner-border-sm me-1"></span>Memproses...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ================================================================
     OFFBOARDING MODAL JS
     handleOffEmpSearch / selectOffEmployee / clearOffEmpSearch
     + formOffboarding AJAX submit
     + promoteToProbationModal JS + btnDrawerPromoteProbation wiring
     ================================================================ -->
<script>
    // ── OFFBOARDING: Attachment config 1:1 GAS _OFFB_DOC_TYPES ─────────────
    var _OFFB_DOC_TYPES = {
        'Resignation': {
            required: ['Surat Resign'],
            optional: ['Dokumen Lainnya']
        },
        'Termination': {
            required: [],
            optional: ['SK PHK', 'Dokumen Lainnya']
        },
        'Retirement': {
            required: [],
            optional: ['SK Pensiun', 'Dokumen Lainnya']
        },
        'Death': {
            required: ['Surat Kematian'],
            optional: ['Dokumen Lainnya']
        }
    };
    var _OFFB_MAX_BYTES = 5 * 1024 * 1024;
    var _OFFB_ACCEPT = '.pdf,.jpg,.jpeg,.png,.doc,.docx';
    var _offActiveEmp = null;

    function _escHtml(str) {
        return String(str || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g,
            '&quot;');
    }

    // ── ATTACHMENT UI ────────────────────────────────────────────────────────
    function _renderOffAttachments(offbType) {
        var section = document.getElementById('offAttachmentSection');
        var reqEl = document.getElementById('offAttachmentRequired');
        var optEl = document.getElementById('offAttachmentOptional');
        if (!section || !reqEl || !optEl) return;
        var config = _OFFB_DOC_TYPES[offbType];
        if (!config) {
            section.style.display = 'none';
            reqEl.innerHTML = '';
            optEl.innerHTML = '';
            return;
        }

        reqEl.innerHTML = '';
        optEl.innerHTML = '';

        config.required.forEach(function(docType) {
            var inputName = 'attachment_' + docType.replace(/\s+/g, '_');
            reqEl.innerHTML +=
                '<div class="mb-3">' +
                '<label class="form-label fw-semibold" style="font-size:13px">' +
                '<span class="text-primary me-1">*</span>' + _escHtml(docType) +
                ' <span class="badge bg-primary ms-1" style="font-size:10px">Wajib</span>' +
                '</label>' +
                '<input type="file" class="form-control form-control-sm offb-attach-input" ' +
                'name="' + inputName + '" accept="' + _OFFB_ACCEPT + '" ' +
                'data-doc-type="' + _escHtml(docType) + '" data-required="1">' +
                '<div class="form-text text-muted" style="font-size:11px">Format: PDF, JPG, PNG, DOC, DOCX — maks 5 MB</div>' +
                '</div>';
        });

        if (config.optional.length) {
            optEl.innerHTML +=
                '<div class="text-muted mb-2" style="font-size:12px;font-weight:600">Lampiran Opsional:</div>';
            config.optional.forEach(function(docType) {
                var inputName = 'attachment_' + docType.replace(/\s+/g, '_');
                optEl.innerHTML +=
                    '<div class="mb-2">' +
                    '<label class="form-label" style="font-size:13px">' + _escHtml(docType) +
                    ' <span class="text-muted" style="font-size:11px">(opsional)</span></label>' +
                    '<input type="file" class="form-control form-control-sm offb-attach-input" ' +
                    'name="' + inputName + '" accept="' + _OFFB_ACCEPT + '" ' +
                    'data-doc-type="' + _escHtml(docType) + '" data-required="0">' +
                    '</div>';
            });
        }

        section.style.display = 'block';
        document.querySelectorAll('#offAttachmentSection .offb-attach-input').forEach(function(inp) {
            inp.addEventListener('change', function() {
                _hideOffAttachError();
                _updateOffConfirmBtn();
            });
        });
    }

    function _hideOffAttachError() {
        var err = document.getElementById('offAttachmentError');
        if (err) err.classList.add('d-none');
    }

    function _showOffAttachError(msg) {
        var err = document.getElementById('offAttachmentError');
        var msgEl = document.getElementById('offAttachmentErrorMsg');
        if (err) err.classList.remove('d-none');
        if (msgEl) msgEl.textContent = msg || '';
    }

    function _requiredOffAttachmentsPresent() {
        var type = (document.getElementById('offType') || {}).value || '';
        var config = _OFFB_DOC_TYPES[type];
        if (!config) return true;
        return (config.required || []).every(function(docType) {
            var inputName = 'attachment_' + docType.replace(/\s+/g, '_');
            var input = document.querySelector('#formOffboarding [name="' + inputName + '"]');
            return !!(input && input.files && input.files.length);
        });
    }

    function _validateOffAttachments() {
        var type = (document.getElementById('offType') || {}).value || '';
        var config = _OFFB_DOC_TYPES[type];
        if (!config) return true;
        var required = config.required || [];
        for (var i = 0; i < required.length; i++) {
            var docType = required[i];
            var inputName = 'attachment_' + docType.replace(/\s+/g, '_');
            var input = document.querySelector('#formOffboarding [name="' + inputName + '"]');
            if (!input || !input.files || !input.files.length) {
                _showOffAttachError(docType + ' wajib diunggah untuk tipe ' + type + '.');
                return false;
            }
            if (input.files[0].size > _OFFB_MAX_BYTES) {
                _showOffAttachError('File "' + input.files[0].name + '" melebihi batas 5 MB.');
                return false;
            }
        }
        var optInputs = document.querySelectorAll('#offAttachmentSection .offb-attach-input[data-required="0"]');
        for (var j = 0; j < optInputs.length; j++) {
            var optInput = optInputs[j];
            if (optInput.files && optInput.files.length && optInput.files[0].size > _OFFB_MAX_BYTES) {
                _showOffAttachError('File "' + optInput.files[0].name + '" melebihi batas 5 MB.');
                return false;
            }
        }
        _hideOffAttachError();
        return true;
    }

    // ── OFFBOARDING SEARCH ───────────────────────────────────────────────────
    function handleOffEmpSearch(query) {
        var q = (query || '').toLowerCase().trim();
        var dropdown = document.getElementById('offEmpDropdown');
        var clearBtn = document.getElementById('offEmpSearchClear');
        if (clearBtn) clearBtn.style.display = q ? 'block' : 'none';
        if (!q) {
            if (dropdown) dropdown.style.display = 'none';
            return;
        }
        var source = window.__allEmployees || window.__allEmployeesForPdf || [];
        var matched = source.filter(function(e) {
            var s = (e.statusEmployee || '').trim().toLowerCase();
            var ok = s !== 'resigned' && s !== 'terminated' && s !== 'retired' &&
                s !== 'deceased' && s !== 'inactive' && s !== 'contract finished' && s !== '';
            return ok && (
                (e.fullName || '').toLowerCase().indexOf(q) !== -1 ||
                (e.employeeId || '').toLowerCase().indexOf(q) !== -1
            );
        }).slice(0, 8);
        if (!matched.length) {
            dropdown.innerHTML =
                '<div class="p-3 text-muted text-center" style="font-size:13px">Karyawan tidak ditemukan</div>';
            dropdown.style.display = 'block';
            return;
        }
        dropdown.innerHTML = matched.map(function(emp) {
            return '<div class="p-2 border-bottom d-flex align-items-center gap-2 hover-item off-search-item" style="cursor:pointer;" data-emp-id="' +
                _escHtml(emp.employeeId || '') + '">' +
                '<div style="width:32px;height:32px;background:#991b1b;color:#fff;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700">' +
                _escHtml((emp.fullName || 'E').substring(0, 2).toUpperCase()) + '</div>' +
                '<div class="flex-grow-1" style="font-size:12.5px;"><div class="fw-semibold">' + _escHtml(emp
                    .fullName || '-') + '</div>' +
                '<div class="text-muted" style="font-size:11px">' + _escHtml(emp.employeeId || '') +
                ' &bull; ' + _escHtml(emp.jobPosition || '-') + '</div></div></div>';
        }).join('');
        dropdown.querySelectorAll('.off-search-item').forEach(function(item) {
            item.addEventListener('click', function() {
                var empId = item.getAttribute('data-emp-id');
                var found = (window.__allEmployees || window.__allEmployeesForPdf || []).find(function(
                    e) {
                    return e.employeeId === empId;
                });
                if (found) selectOffEmployee(found);
            });
        });
        dropdown.style.display = 'block';
    }

    function selectOffEmployee(emp) {
        _offActiveEmp = emp;
        var dropdown = document.getElementById('offEmpDropdown');
        var searchEl = document.getElementById('offEmpSearch');
        var clearBtn = document.getElementById('offEmpSearchClear');
        if (dropdown) dropdown.style.display = 'none';
        if (searchEl) searchEl.value = emp.fullName + ' (' + emp.employeeId + ')';
        if (clearBtn) clearBtn.style.display = 'block';
        var els = {
            offEmpAvatar: (emp.fullName || 'E').substring(0, 2).toUpperCase(),
            offEmpName: emp.fullName || '-',
            offEmpPosition: emp.jobPosition || emp.jobPositionLocation || '-',
            offEmpDept: emp.department || '-',
            offEmpIdDisp: emp.employeeId || '-',
            offEmpStatusDisp: emp.statusEmployee || '-',
        };
        Object.keys(els).forEach(function(id) {
            var el = document.getElementById(id);
            if (el) el.textContent = els[id];
        });
        // Pre-fill BPJS
        var bpjsTkEl = document.getElementById('offBpjsTk');
        var bpjsKesEl = document.getElementById('offBpjsKes');
        if (bpjsTkEl && !bpjsTkEl.value) bpjsTkEl.value = (emp.bpjsKetenagakerjaan || '').replace(/^'/, '');
        if (bpjsKesEl && !bpjsKesEl.value) bpjsKesEl.value = (emp.bpjsKesehatan || '').replace(/^'/, '');
        var dateEl = document.getElementById('offEffectiveDate');
        if (dateEl && !dateEl.value) dateEl.value = new Date().toISOString().split('T')[0];
        var preview = document.getElementById('offEmpPreview');
        if (preview) preview.style.display = 'block';
        _updateOffConfirmBtn();
    }

    function clearOffEmpSearch() {
        _offActiveEmp = null;
        var searchEl = document.getElementById('offEmpSearch');
        var clearBtn = document.getElementById('offEmpSearchClear');
        var dropdown = document.getElementById('offEmpDropdown');
        var preview = document.getElementById('offEmpPreview');
        if (searchEl) searchEl.value = '';
        if (clearBtn) clearBtn.style.display = 'none';
        if (dropdown) dropdown.style.display = 'none';
        if (preview) preview.style.display = 'none';
        _updateOffConfirmBtn();
    }

    function _updateOffConfirmBtn() {
        var btn = document.getElementById('btnConfirmOffboarding');
        if (!btn) return;
        var type = (document.getElementById('offType') || {}).value || '';
        var date = (document.getElementById('offEffectiveDate') || {}).value || '';
        var reason = ((document.getElementById('offReason') || {}).value || '').trim();
        var baseOk = !!(_offActiveEmp && type && date && reason);
        btn.disabled = !(baseOk && _requiredOffAttachmentsPresent());
    }

    document.addEventListener('DOMContentLoaded', function() {
        // Wire offType change → render dynamic attachments
        var offTypeEl = document.getElementById('offType');
        if (offTypeEl) {
            offTypeEl.addEventListener('change', function() {
                _renderOffAttachments(this.value);
                _updateOffConfirmBtn();
            });
        }
        ['offEffectiveDate', 'offReason'].forEach(function(id) {
            var el = document.getElementById(id);
            if (el) {
                el.addEventListener('input', _updateOffConfirmBtn);
                el.addEventListener('change', _updateOffConfirmBtn);
            }
        });

        // Reset modal on close
        var offModal = document.getElementById('offboardingModal');
        if (offModal) {
            offModal.addEventListener('hidden.bs.modal', function() {
                clearOffEmpSearch();
                var form = document.getElementById('formOffboarding');
                if (form) form.reset();
                var reqEl = document.getElementById('offAttachmentRequired');
                var optEl = document.getElementById('offAttachmentOptional');
                var sec = document.getElementById('offAttachmentSection');
                if (reqEl) reqEl.innerHTML = '';
                if (optEl) optEl.innerHTML = '';
                if (sec) sec.style.display = 'none';
                _hideOffAttachError();
                ['offBpjsTk', 'offBpjsKes'].forEach(function(id) {
                    var el = document.getElementById(id);
                    if (el) el.value = '';
                });
                _updateOffConfirmBtn();
            });
        }

        // ── AJAX submit — multipart FormData with file attachments ─────────────
        var offForm = document.getElementById('formOffboarding');
        var offBtn = document.getElementById('btnConfirmOffboarding');
        if (offForm && offBtn) {
            offForm.addEventListener('submit', function(e) {
                e.preventDefault();
                if (!_offActiveEmp) return;
                var empId = _offActiveEmp.employeeId;
                var type = (document.getElementById('offType') || {}).value || '';
                var date = (document.getElementById('offEffectiveDate') || {}).value || '';
                var reason = ((document.getElementById('offReason') || {}).value || '').trim();
                if (!empId || !type || !date || !reason) return;
                if (!_validateOffAttachments()) return;

                var txtEl = document.getElementById('btnOffbText');
                var ldEl = document.getElementById('btnOffbLoading');
                offBtn.disabled = true;
                if (txtEl) txtEl.classList.add('d-none');
                if (ldEl) ldEl.classList.remove('d-none');

                var formData = new FormData(offForm);
                if (!formData.get('last_working_date') && date) formData.set('last_working_date', date);

                fetch('/hr/employees/' + empId + '/offboard', {
                        method: 'POST',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') ||
                                {}).content || ''
                        },
                        body: formData
                    })
                    .then(function(r) {
                        return r.json();
                    })
                    .then(function(res) {
                        offBtn.disabled = false;
                        if (txtEl) txtEl.classList.remove('d-none');
                        if (ldEl) ldEl.classList.add('d-none');
                        if (res && res.success) {
                            if (typeof showToast === 'function') {
                                var uploadWarning = res.drive_upload_success === false ?
                                    ' Lampiran gagal disimpan ke Google Drive: ' + _escHtml(res
                                        .drive_upload_error || 'folder tidak dapat diakses.') :
                                    '';
                                showToast('Offboarding <strong>' + _escHtml((_offActiveEmp || {})
                                        .fullName || '') + '</strong> berhasil diproses.' +
                                    uploadWarning,
                                    uploadWarning ? 'warning' : 'success', uploadWarning ?
                                    9000 : 6000);
                            }
                            // Download one server-generated bundle so the browser does not block
                            // the second and third files as multiple automatic downloads.
                            var pdfUrls = res.pdf_urls || {};
                            var bundleUrl = pdfUrls.bundle;
                            if (bundleUrl) {
                                fetch(bundleUrl, {
                                        credentials: 'same-origin'
                                    })
                                    .then(function(response) {
                                        if (!response.ok) throw new Error(
                                            'offboarding bundle HTTP ' + response.status
                                        );
                                        return response.blob();
                                    })
                                    .then(function(blob) {
                                        var blobUrl = URL.createObjectURL(blob);
                                        var anchor = document.createElement('a');
                                        anchor.href = blobUrl;
                                        anchor.download = 'Dokumen_Offboarding.zip';
                                        anchor.style.display = 'none';
                                        document.body.appendChild(anchor);
                                        anchor.click();
                                        anchor.remove();
                                        setTimeout(function() {
                                            URL.revokeObjectURL(blobUrl);
                                        }, 1000);
                                    })
                                    .catch(function(error) {
                                        if (typeof showToast === 'function') {
                                            showToast(
                                                'Bundle PDF offboarding gagal diunduh. Silakan gunakan menu export.',
                                                'warning', 8000);
                                        }
                                    });
                            } else {
                                if (typeof showToast === 'function') {
                                    showToast(
                                        'Bundle PDF offboarding tidak tersedia. Silakan gunakan menu export.',
                                        'warning', 8000);
                                }
                            }
                            var modal = bootstrap.Modal.getInstance(document.getElementById(
                                'offboardingModal'));
                            if (modal) modal.hide();
                        } else {
                            showToast('Gagal: ' + (res ? (res.message || 'Error') :
                                'Tidak ada respon'), 'error');
                        }
                    })
                    .catch(function(err) {
                        offBtn.disabled = false;
                        if (txtEl) txtEl.classList.remove('d-none');
                        if (ldEl) ldEl.classList.add('d-none');
                        showToast('Error: ' + (err ? err.message : 'Network error'), 'error');
                    });
            });
        }

        // ── PROMOTE TO PROBATION — btnDrawerPromoteProbation wiring ──────────
        // Wire the drawer button to open #promoteToProbationModal
        // and pre-fill employee data from the active employee drawer
        var btnProb = document.getElementById('btnDrawerPromoteProbation');
        if (btnProb) {
            btnProb.addEventListener('click', function() {
                // Get current employee data from the drawer
                var empIdEl = document.getElementById('empDrEmployeeId');
                var empNameEl = document.getElementById('drawerCandidateName');
                var empPosEl = document.getElementById('empDrPosition');
                var empDeptEl = document.getElementById('empDrDept');
                var empStatEl = document.getElementById('empDrStatusEmployee');

                var empId = empIdEl ? (empIdEl.innerText || '').trim() : '';
                var empName = empNameEl ? (empNameEl.innerText || '').trim() : '';
                var empPos = empPosEl ? (empPosEl.innerText || '').trim() : '';
                var empDept = empDeptEl ? (empDeptEl.innerText || '').trim() : '';
                var empStat = empStatEl ? (empStatEl.innerText || '').trim() : 'Contract';

                if (!empId) return;

                // Fill modal fields
                var empIdInput = document.getElementById('promoteProbEmpId');
                var nameEl = document.getElementById('promoteProbEmpName');
                var posEl = document.getElementById('promoteProbPosition');
                var avatarEl = document.getElementById('promoteProbAvatar');
                var badgeEl = document.getElementById('promoteProbBadge');
                var formEl = document.getElementById('formPromoteProbation');

                if (empIdInput) empIdInput.value = empId;
                if (nameEl) nameEl.textContent = empName || '-';
                if (posEl) posEl.textContent = (empPos || '-') + (empDept ? ' · ' + empDept : '');
                if (avatarEl) avatarEl.textContent = (empName || 'K').substring(0, 2).toUpperCase();
                if (badgeEl) badgeEl.textContent = empStat;
                if (formEl) formEl.action = '/hr/employees/' + empId + '/promote-probation';

                // Set default start date
                var startEl = document.getElementById('promoteProbStart');
                if (startEl) startEl.value = new Date().toISOString().split('T')[0];

                // Show modal
                var modal = bootstrap.Modal.getOrCreateInstance(document.getElementById(
                    'promoteToProbationModal'));
                modal.show();
            });
        }

        // promoteToProbationModal AJAX submit
        var probForm = document.getElementById('formPromoteProbation');
        if (probForm) {
            probForm.addEventListener('submit', function(e) {
                e.preventDefault();
                var empId = (document.getElementById('promoteProbEmpId') || {}).value || '';
                if (!empId) return;

                var submitBtn = probForm.querySelector('button[type="submit"]');
                var origHtml = submitBtn ? submitBtn.innerHTML : '';
                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.innerHTML =
                        '<span class="spinner-border spinner-border-sm me-1"></span>Memproses...';
                }

                var formData = new FormData(probForm);
                // probation_end is not in form, compute from start + duration
                var startVal = (document.getElementById('promoteProbStart') || {}).value || '';
                var durVal = (document.getElementById('promoteProbDuration') || {}).value || '3 Bulan';
                if (startVal) {
                    var months = parseInt((durVal.match(/\d+/) || ['3'])[0], 10);
                    var endDate = new Date(startVal);
                    endDate.setMonth(endDate.getMonth() + months);
                    formData.set('probation_end', endDate.toISOString().split('T')[0]);
                }

                fetch('/hr/employees/' + empId + '/promote-probation', {
                        method: 'POST',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') ||
                                {}).content || ''
                        },
                        body: formData
                    })
                    .then(function(r) {
                        return r.json();
                    })
                    .then(function(res) {
                        if (submitBtn) {
                            submitBtn.disabled = false;
                            submitBtn.innerHTML = origHtml;
                        }
                        var modal = bootstrap.Modal.getInstance(document.getElementById(
                            'promoteToProbationModal'));
                        if (modal) modal.hide();
                        if (res && res.success) {
                            showToast(res.message || 'Berhasil didaftarkan ke Probation.',
                                'success', 5000);
                            setTimeout(function() {
                                window.location.reload();
                            }, 800);
                        } else {
                            showToast('Gagal: ' + (res ? (res.message || 'Error') : 'Error'),
                                'error');
                        }
                    })
                    .catch(function(err) {
                        if (submitBtn) {
                            submitBtn.disabled = false;
                            submitBtn.innerHTML = origHtml;
                        }
                        showToast('Error: ' + (err ? err.message : 'Network error'), 'error');
                    });
            });
        }
    });
</script>

<!-- 2. IMPORT CSV / EXCEL MODAL — Master Data & Employee (1:1 from GAS Modals.html / js/import.html) -->
{{--
  Full 3-step import flow:
  Step 1 — File select / drag-drop (CSV or XLSX/XLS), client-side parse with XLSX.js
  Step 2 — Preview table: per-row status (new / duplicate / invalid), summary counts
  Step 3 — Result: import summary, errors list, close button

  Data flow:
  1. User selects file → client-side parse (CSV or XLSX) → empImportRows[]
  2. POST /hr/employees/import/preview (JSON: {employees:[...]}) → preview result
  3. User clicks Import → POST /hr/employees/import (JSON: {employees:[new rows only]})
  4. Show step3 result with counts and per-row errors

  NO write to Google Sheets happens in steps 1–2.
  Backend always re-validates in step 3.
  RBAC: can:manage_employees (enforced in route + backend).
--}}
<div class="modal fade" id="empImportModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content" style="border-radius:16px">
            <div class="modal-header" style="background:#166534;color:#fff;border-radius:16px 16px 0 0">
                <h6 class="modal-title mb-0 fw-bold"><i class="bi bi-upload me-2"></i>Import Karyawan (CSV / Excel)
                </h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body p-4">

                {{-- ====== STEP 1: File Select ====== --}}
                <div id="empImportStep1">
                    <p style="font-size:13px;color:var(--color-text-soft)">
                        Upload file <strong>CSV</strong> atau <strong>Excel (.xlsx/.xls)</strong>. Kolom
                        <strong>wajib</strong>:
                        <code>Full Name</code>. Semua kolom lain opsional.
                    </p>

                    {{-- Drop zone --}}
                    <div class="border rounded-3 p-4 text-center" id="empImportDropZone"
                        style="cursor:pointer;border-style:dashed!important;transition:background .2s;background:var(--color-bg)">
                        <i class="bi bi-file-earmark-spreadsheet fs-1 text-success"></i>
                        <p class="mb-1 mt-2 fw-semibold" style="font-size:14px">Klik atau seret file CSV / Excel ke
                            sini</p>
                        <p class="mb-0 text-muted" style="font-size:12px">.csv &bull; .xlsx &bull; .xls — maks 10 MB
                            &bull; maks 500 baris</p>
                        <input type="file" id="empImportFileInput" accept=".csv,.xlsx,.xls" class="d-none" />
                    </div>

                    {{-- File info (shown after selection) --}}
                    <div class="mt-3 d-none" id="empImportFileInfo">
                        <div class="d-flex align-items-center gap-2 p-2 rounded" style="background:var(--color-bg)">
                            <i class="bi bi-file-earmark-check fs-5 text-success"></i>
                            <div class="flex-grow-1">
                                <div class="fw-semibold" style="font-size:13px" id="empImportFileName">-</div>
                                <div class="text-muted" style="font-size:12px" id="empImportFileSize">-</div>
                            </div>
                            <button class="btn btn-sm btn-outline-primary" type="button" id="btnEmpImportClearFile">
                                <i class="bi bi-x"></i>
                            </button>
                        </div>
                    </div>

                    {{-- Recognized columns reference --}}
                    <div class="mt-3 p-3 rounded"
                        style="background:var(--color-bg);border:1px solid var(--color-border);font-size:11.5px">
                        <div class="fw-semibold mb-1" style="font-size:12px"><i
                                class="bi bi-info-circle me-1"></i>Header yang dikenali (case-insensitive,
                            spasi/underscore diabaikan)</div>
                        <div class="mb-1"><span class="badge bg-primary me-1">Wajib</span><code>Full Name</code> /
                            <code>fullName</code> / <code>Nama Lengkap</code>
                        </div>
                        <div style="color:var(--color-text-soft)">
                            <strong>Identitas:</strong> <code>NIK</code>, <code>NPWP</code>, <code>Tempat Lahir</code>,
                            <code>Tanggal Lahir</code>, <code>Jenis Kelamin</code>, <code>Agama</code>, <code>Status
                                Pernikahan</code>, <code>Golongan Darah</code><br>
                            <strong>Kontak:</strong> <code>Alamat KTP</code>, <code>Alamat Domisili</code>, <code>No
                                HP</code>, <code>Email Pribadi</code>, <code>Email Kantor</code><br>
                            <strong>Bank &amp; BPJS:</strong> <code>Nama Bank</code>, <code>Nomor Rekening</code>,
                            <code>Atas Nama Rekening</code>, <code>BPJS Ketenagakerjaan</code>, <code>BPJS
                                Kesehatan</code><br>
                            <strong>Organisasi:</strong> <code>Branch Name</code>, <code>Division</code>,
                            <code>Department</code>, <code>Job Position</code>, <code>Job Level</code>,
                            <code>Grade</code>, <code>Area Kerja</code>, <code>Lokasi Kerja</code><br>
                            <strong>Status &amp; Kontrak:</strong> <code>Status Employee</code> (<em>Permanent /
                                Contract / Probation / Outsource</em>), <code>Join Date</code>, <code>End Date
                                Contract</code>
                        </div>
                    </div>
                </div>
                {{-- /STEP 1 --}}

                {{-- ====== STEP 2: Preview ====== --}}
                <div id="empImportStep2" class="d-none">
                    {{-- Summary bar --}}
                    <div class="d-flex flex-wrap gap-2 align-items-center mb-3" id="empImportSummaryBar">
                        <span class="badge bg-secondary" style="font-size:13px" id="empImportBadgeTotal">Total:
                            0</span>
                        <span class="badge bg-success" style="font-size:13px" id="empImportBadgeNew">Baru: 0</span>
                        <span class="badge bg-warning text-dark" style="font-size:13px"
                            id="empImportBadgeExist">Sudah Ada: 0</span>
                        <span class="badge bg-primary" style="font-size:13px" id="empImportBadgeInvalid">Invalid:
                            0</span>
                        <span class="badge bg-info text-dark" style="font-size:13px"
                            id="empImportBadgeDupFile">Duplikat File: 0</span>
                    </div>

                    {{-- Loading spinner saat preview --}}
                    <div id="empImportPreviewLoading" class="text-center py-4 d-none">
                        <span class="spinner-border spinner-border-sm text-success me-2"></span>
                        <span style="font-size:13px">Memvalidasi data...</span>
                    </div>

                    {{-- Preview table --}}
                    <div class="table-responsive" id="empImportPreviewTableWrap"
                        style="max-height:360px;overflow-y:auto">
                        <table class="table table-sm table-bordered align-middle mb-0" style="font-size:12px">
                            <thead class="table-light sticky-top">
                                <tr>
                                    <th style="width:40px">#</th>
                                    <th>Employee ID</th>
                                    <th>Nama Lengkap</th>
                                    <th>Departemen</th>
                                    <th>Jabatan</th>
                                    <th>Status Karyawan</th>
                                    <th>Join Date</th>
                                    <th style="width:160px">Hasil Validasi</th>
                                </tr>
                            </thead>
                            <tbody id="empImportPreviewBody"></tbody>
                        </table>
                    </div>

                    {{-- Warning if nothing to import --}}
                    <div id="empImportNoNewAlert" class="alert alert-warning mt-3 d-none" style="font-size:13px">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        Tidak ada baris baru yang dapat diimport. Semua baris sudah ada di sheet atau invalid.
                    </div>
                </div>
                {{-- /STEP 2 --}}

                {{-- ====== STEP 3: Result ====== --}}
                <div id="empImportStep3" class="d-none text-center py-3">
                    <i id="empImportResultIcon" class="bi bi-check-circle-fill fs-1 text-success"></i>
                    <h6 id="empImportResultTitle" class="mt-3 mb-1">Import Selesai</h6>
                    <p id="empImportResultMessage" class="text-muted" style="font-size:13px"></p>

                    {{-- Error list (shown if partial errors) --}}
                    <div id="empImportResultErrors" class="text-start d-none mt-3">
                        <div class="fw-semibold mb-1" style="font-size:12px;color:#991b1b">
                            <i class="bi bi-exclamation-circle me-1"></i>Detail baris yang dilewati:
                        </div>
                        <ul id="empImportErrorList" class="list-unstyled mb-0"
                            style="font-size:12px;max-height:200px;overflow-y:auto;background:#fff5f5;padding:8px 12px;border-radius:8px;border:1px solid #fecaca">
                        </ul>
                    </div>
                </div>
                {{-- /STEP 3 --}}

            </div>{{-- /modal-body --}}

            <div class="modal-footer" id="empImportFooter">
                {{-- Step 1 footer --}}
                <div id="empImportFooterStep1" class="d-flex gap-2 w-100">
                    <a href="{{ route('hr.employees.import.template') }}" class="btn btn-outline-secondary btn-sm"
                        id="btnEmpDownloadTemplate">
                        <i class="bi bi-download me-1"></i>Download Template CSV
                    </a>
                    <button type="button" class="btn btn-outline-secondary btn-sm ms-auto"
                        data-bs-dismiss="modal">Tutup</button>
                    <button type="button" class="btn btn-sm text-white fw-semibold" style="background:#166534"
                        id="btnEmpImportPreview" disabled>
                        <span id="btnPreviewText"><i class="bi bi-eye me-1"></i>Preview &amp; Validasi</span>
                        <span id="btnPreviewLoading" class="d-none"><span
                                class="spinner-border spinner-border-sm me-1"></span>Memvalidasi...</span>
                    </button>
                </div>

                {{-- Step 2 footer --}}
                <div id="empImportFooterStep2" class="d-flex gap-2 w-100 d-none">
                    <button type="button" class="btn btn-outline-secondary btn-sm" id="btnEmpImportBack">
                        <i class="bi bi-arrow-left me-1"></i>Kembali
                    </button>
                    <button type="button" class="btn btn-outline-secondary btn-sm ms-auto"
                        data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-sm text-white fw-semibold" style="background:#166534"
                        id="btnEmpImportConfirm" disabled>
                        <span id="btnImportText"><i class="bi bi-cloud-arrow-up-fill me-1"></i>Import <span
                                id="btnImportCount">0</span> Karyawan Baru</span>
                        <span id="btnImportLoading" class="d-none"><span
                                class="spinner-border spinner-border-sm me-1"></span>Mengimport...</span>
                    </button>
                </div>

                {{-- Step 3 footer --}}
                <div id="empImportFooterStep3" class="d-flex gap-2 w-100 d-none">
                    <button type="button" class="btn btn-sm text-white fw-semibold ms-auto"
                        style="background:#166534" data-bs-dismiss="modal" id="btnEmpImportClose">
                        <i class="bi bi-check me-1"></i>Selesai
                    </button>
                </div>
            </div>{{-- /modal-footer --}}

        </div>
    </div>
</div>{{-- /empImportModal --}}

<script>
    // ============================================================
    // IMPORT EMPLOYEE MODAL — Full 3-step flow
    // 1:1 behavior with GAS js/import.html
    // Step 1: file select → client-side parse (CSV/XLSX)
    // Step 2: POST preview endpoint → display per-row validation
    // Step 3: POST import endpoint (new rows only) → show result
    // ============================================================
    (function() {
        'use strict';

        // ── State ──────────────────────────────────────────────────
        var _file = null; // File object
        var _parsedRows = []; // all rows parsed from file
        var _previewRows = []; // rows from backend preview response
        var _newCount = 0; // importable row count

        // ── DOM helpers ────────────────────────────────────────────
        function el(id) {
            return document.getElementById(id);
        }

        function show(id) {
            var e = el(id);
            if (e) {
                e.classList.remove('d-none');
            }
        }

        function hide(id) {
            var e = el(id);
            if (e) {
                e.classList.add('d-none');
            }
        }

        function setText(id, txt) {
            var e = el(id);
            if (e) e.textContent = txt;
        }

        function esc(str) {
            return String(str || '')
                .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
        }

        function csrfToken() {
            var m = document.querySelector('meta[name="csrf-token"]');
            return m ? m.content : '';
        }

        // ── Step navigation ────────────────────────────────────────
        function showStep(n) {
            [1, 2, 3].forEach(function(s) {
                var body = el('empImportStep' + s);
                var footer = el('empImportFooterStep' + s);
                if (body) {
                    if (s === n) body.classList.remove('d-none');
                    else body.classList.add('d-none');
                }
                if (footer) {
                    if (s === n) footer.classList.remove('d-none');
                    else footer.classList.add('d-none');
                }
            });
        }

        // ── Reset to step 1 ────────────────────────────────────────
        function resetModal() {
            _file = null;
            _parsedRows = [];
            _previewRows = [];
            _newCount = 0;
            var fi = el('empImportFileInput');
            if (fi) fi.value = '';
            hide('empImportFileInfo');
            show('empImportDropZone');
            var prevBtn = el('btnEmpImportPreview');
            if (prevBtn) {
                prevBtn.disabled = true;
            }
            // Reset step 2 badges
            ['Total', 'New', 'Exist', 'Invalid', 'DupFile'].forEach(function(k) {
                var e = el('empImportBadge' + k);
                if (e) e.textContent = k + ': 0';
            });
            var tbody = el('empImportPreviewBody');
            if (tbody) tbody.innerHTML = '';
            hide('empImportNoNewAlert');
            // Reset step 3
            hide('empImportResultErrors');
            var errList = el('empImportErrorList');
            if (errList) errList.innerHTML = '';
            showStep(1);
        }

        // ── File selection ─────────────────────────────────────────
        function handleFile(file) {
            if (!file) return;
            var name = (file.name || '').toLowerCase();
            if (!name.endsWith('.csv') && !name.endsWith('.xlsx') && !name.endsWith('.xls')) {
                showToast('Hanya file CSV atau Excel (.csv/.xlsx/.xls) yang didukung.', 'error');
                return;
            }
            if (file.size > 10 * 1024 * 1024) {
                showToast('Ukuran file maksimal 10 MB.', 'error');
                return;
            }
            _file = file;
            setText('empImportFileName', file.name);
            setText('empImportFileSize', (file.size / 1024).toFixed(1) + ' KB');
            hide('empImportDropZone');
            show('empImportFileInfo');

            // Parse file client-side
            if (name.endsWith('.csv')) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    try {
                        _parsedRows = parseCSV(e.target.result);
                        afterParse();
                    } catch (err) {
                        if (typeof showToast === 'function') showToast('Gagal memproses CSV: ' + err.message,
                            'error');
                        resetModal();
                    }
                };
                reader.onerror = function() {
                    if (typeof showToast === 'function') showToast('Gagal membaca file CSV.', 'error');
                    resetModal();
                };
                reader.readAsText(file, 'UTF-8');
            } else {
                parseExcel(file, function(rows) {
                    _parsedRows = rows;
                    afterParse();
                });
            }
        }

        function afterParse() {
            if (!_parsedRows || _parsedRows.length === 0) {
                if (typeof showToast === 'function') showToast('File kosong atau format kolom tidak dikenali.',
                    'error');
                resetModal();
                return;
            }
            if (_parsedRows.length > 500) {
                if (typeof showToast === 'function') showToast('Maksimal 500 baris per import. File memiliki ' +
                    _parsedRows.length + ' baris.', 'error');
                resetModal();
                return;
            }
            var prevBtn = el('btnEmpImportPreview');
            if (prevBtn) prevBtn.disabled = false;
        }

        // ── CSV parser (identical to GAS js/import.html) ───────────
        function detectDelimiter(lines) {
            var first = lines[0] || '';
            var c = (first.match(/,/g) || []).length;
            var s = (first.match(/;/g) || []).length;
            var t = (first.match(/\t/g) || []).length;
            if (s > c && s > t) return ';';
            if (t > c && t > s) return '\t';
            return ',';
        }

        function splitLine(line, delim) {
            var result = [],
                cur = '',
                inQ = false;
            for (var i = 0; i < line.length; i++) {
                var ch = line[i];
                if (ch === '"') {
                    if (inQ && line[i + 1] === '"') {
                        cur += '"';
                        i++;
                    } else {
                        inQ = !inQ;
                    }
                } else if (ch === delim && !inQ) {
                    result.push(cur);
                    cur = '';
                } else {
                    cur += ch;
                }
            }
            result.push(cur);
            return result;
        }

        function parseCSV(text) {
            text = (text || '').replace(/^\uFEFF/, '');
            var lines = text.split(/\r?\n/).filter(function(l) {
                return l.trim() !== '';
            });
            if (lines.length < 2) return [];
            var delim = detectDelimiter(lines);
            var matrix = lines.map(function(l) {
                return splitLine(l, delim);
            });
            return rowsFromMatrix(matrix);
        }

        // ── Excel parser (uses XLSX.js CDN, lazy-loaded) ───────────
        function parseExcel(file, cb) {
            if (typeof XLSX === 'undefined') {
                var s = document.createElement('script');
                s.src = 'https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js';
                document.head.appendChild(s);
                s.onload = function() {
                    parseExcel(file, cb);
                };
                s.onerror = function() {
                    if (typeof showToast === 'function') showToast(
                        'Gagal memuat library Excel. Periksa koneksi internet.', 'error');
                    if (cb) cb([]);
                };
                return;
            }
            var fr = new FileReader();
            fr.onload = function(e) {
                try {
                    var wb = XLSX.read(new Uint8Array(e.target.result), {
                        type: 'array',
                        raw: false
                    });
                    var sheetName = wb.SheetNames[0];
                    var preferred = ['edited', 'data', 'karyawan', 'employee', 'import'];
                    for (var si = 0; si < wb.SheetNames.length; si++) {
                        var sn = wb.SheetNames[si].toLowerCase().trim();
                        for (var pi = 0; pi < preferred.length; pi++) {
                            if (sn === preferred[pi] || sn.indexOf(preferred[pi]) !== -1) {
                                sheetName = wb.SheetNames[si];
                                break;
                            }
                        }
                        if (sheetName !== wb.SheetNames[0]) break;
                    }
                    var ws = wb.Sheets[sheetName];
                    if (!ws) {
                        if (cb) cb([]);
                        return;
                    }
                    var matrix = XLSX.utils.sheet_to_json(ws, {
                        header: 1,
                        raw: false,
                        defval: ''
                    });
                    // Strip leading apostrophe Excel text prefix
                    matrix = matrix.map(function(row) {
                        return row.map(function(cell) {
                            return String(cell == null ? '' : cell).replace(/^'+/, '');
                        });
                    });
                    if (cb) cb(rowsFromMatrix(matrix));
                } catch (err) {
                    if (typeof showToast === 'function') showToast('Gagal membaca file Excel: ' + err.message,
                        'error');
                    if (cb) cb([]);
                }
            };
            fr.onerror = function() {
                if (typeof showToast === 'function') showToast('Gagal membaca file Excel.', 'error');
                if (cb) cb([]);
            };
            fr.readAsArrayBuffer(file);
        }

        // ── Field mapping (1:1 with GAS empImportFieldMap) ─────────
        var FIELD_MAP = {
            // Identitas
            'employeeid': 'employeeId',
            'idkaryawan': 'employeeId',
            'fullname': 'fullName',
            'namalengkap': 'fullName',
            'nama': 'fullName',
            'name': 'fullName',
            'namakaryawan': 'fullName',
            'karyawan': 'fullName',
            'employeename': 'fullName',
            'nik': 'nik',
            'niknpwp16digit': 'nik',
            'niknpwp': 'nik',
            'nomorinduk': 'nik',
            'ktp': 'nik',
            'noktp': 'nik',
            'nomorktp': 'nik',
            'npwp': 'npwp',
            'nonpwp': 'npwp',
            'tempatlahir': 'birthPlace',
            'birthplace': 'birthPlace',
            'kotalahir': 'birthPlace',
            'tanggallahir': 'birthDate',
            'birthdate': 'birthDate',
            'tgllahir': 'birthDate',
            // Pribadi
            'jeniskelamin': 'gender',
            'gender': 'gender',
            'jk': 'gender',
            'sex': 'gender',
            'agama': 'religion',
            'religion': 'religion',
            'statuspernikahan': 'maritalStatus',
            'maritalstatus': 'maritalStatus',
            'statusnikah': 'maritalStatus',
            'golongandarah': 'bloodType',
            'bloodtype': 'bloodType',
            'goldarah': 'bloodType',
            'statusptkp': 'ptkpStatus',
            'ptkp': 'ptkpStatus',
            'ptkpstatus': 'ptkpStatus',
            'alamatktp': 'citizenIdAddress',
            'alamatsesuaiktp': 'citizenIdAddress',
            'citizenidaddress': 'citizenIdAddress',
            'address': 'citizenIdAddress',
            'alamat': 'citizenIdAddress',
            'alamatdomisili': 'residentialAddress',
            'residentialaddress': 'residentialAddress',
            'domisili': 'residentialAddress',
            'nohp': 'mobilePhone',
            'hp': 'mobilePhone',
            'nomortelepon': 'mobilePhone',
            'telepon': 'mobilePhone',
            'phone': 'mobilePhone',
            'mobilephone': 'mobilePhone',
            'notelp': 'mobilePhone',
            'whatsapp': 'mobilePhone',
            'wa': 'mobilePhone',
            'emailpribadi': 'personalEmail',
            'personalemail': 'personalEmail',
            'email': 'personalEmail',
            'emailkantor': 'workingEmail',
            'workingemail': 'workingEmail',
            'emailkerja': 'workingEmail',
            // Bank & BPJS
            'namabank': 'bankName',
            'bankname': 'bankName',
            'bank': 'bankName',
            'nomorrekening': 'bankAccount',
            'bankaccount': 'bankAccount',
            'rekening': 'bankAccount',
            'norek': 'bankAccount',
            'atasnama': 'bankAccountHolder',
            'bankaccountholder': 'bankAccountHolder',
            'namarekening': 'bankAccountHolder',
            'nomorbpjsketenagakerjaan': 'bpjsKetenagakerjaan',
            'bpjsketenagakerjaan': 'bpjsKetenagakerjaan',
            'bpjstk': 'bpjsKetenagakerjaan',
            'kpj': 'bpjsKetenagakerjaan',
            'nomorbpjskesehatan': 'bpjsKesehatan',
            'bpjskesehatan': 'bpjsKesehatan',
            'bpjskes': 'bpjsKesehatan',
            'kis': 'bpjsKesehatan',
            // Organisasi
            'cabang': 'branchName',
            'branchname': 'branchName',
            'branch': 'branchName',
            'namacabang': 'branchName',
            'entitas': 'branchName',
            'perusahaan': 'branchName',
            'divisi': 'division',
            'division': 'division',
            'namadivisi': 'division',
            'departemen': 'department',
            'department': 'department',
            'dept': 'department',
            'bagian': 'department',
            'unit': 'department',
            'namadepartemen': 'department',
            'jabatan': 'positionCurrent',
            'jobpositionlocation': 'positionCurrent',
            'positioncurrent': 'positionCurrent',
            'posisi': 'positionCurrent',
            'jobpositionlocaction': 'positionCurrent',
            'jobposition': 'positionNoLocCurrent',
            'position': 'positionNoLocCurrent',
            'positionnoloccurrent': 'positionNoLocCurrent',
            'joblevel': 'jobLevel',
            'level': 'jobLevel',
            'leveljabatan': 'jobLevel',
            'grade': 'grade',
            'areakerja': 'areaKerja',
            'kecamatan': 'areaKerja',
            'district': 'areaKerja',
            'lokasikerja': 'lokasiKerja',
            'kota': 'lokasiKerja',
            'city': 'lokasiKerja',
            'costcenter': 'costCenter',
            'pusatbiaya': 'costCenter',
            'atasanlangsung': 'directSuperior',
            'directsuperior': 'directSuperior',
            'atasantidaklangsung': 'indirectSuperior',
            'indirectsuperior': 'indirectSuperior',
            // Status & Kontrak
            'statuskaryawan': 'statusEmployee',
            'statusemployee': 'statusEmployee',
            'status': 'statusEmployee',
            'statuskepegawaian': 'statusEmployee',
            'employmentstatus': 'statusEmployee',
            'tanggalmasuk': 'joinDate',
            'joindate': 'joinDate',
            'tglmasuk': 'joinDate',
            'akhirkontrak': 'endDateContract',
            'enddatecontract': 'endDateContract',
            'contractend': 'endDateContract',
            'vendoroutsource': 'outsourceVendor',
            'outsourcevendor': 'outsourceVendor',
            'vendor': 'outsourceVendor',
            // Karier
            'jabatansebelumnya': 'positionFormer',
            'positionformer': 'positionFormer',
            'jobpositionformer': 'positionFormer',
            'jenisrotasi': 'typeOfRotation',
            'typeofrotation': 'typeOfRotation',
            'tanggalmutasi': 'mutasiDate',
            'mutasidate': 'mutasiDate',
            'nomorsk': 'nomorSk',
            'nosk': 'nomorSk',
            'tanggalresign': 'resignDate',
            'resigndate': 'resignDate',
            // Catatan
            'catatan': 'hrNotes',
            'notes': 'hrNotes',
            'hrnotes': 'hrNotes',
            'keterangan': 'hrNotes',
        };

        function normalizeHeader(h) {
            return String(h || '').trim().replace(/^["']|["']$/g, '').toLowerCase().replace(/[\s_\-\/\.\(\)]/g, '');
        }

        function rowsFromMatrix(matrix) {
            if (!matrix || matrix.length < 2) return [];
            var headers = (matrix[0] || []).map(normalizeHeader);
            var rows = [];
            for (var i = 1; i < matrix.length; i++) {
                var vals = matrix[i] || [];
                var any = vals.some(function(v) {
                    return v !== '' && v !== null && v !== undefined;
                });
                if (!any) continue; // skip blank rows
                var row = {};
                for (var j = 0; j < headers.length; j++) {
                    var hNorm = headers[j];
                    var key = FIELD_MAP[hNorm] || hNorm;
                    if (!key) continue;
                    var val = vals[j];
                    row[key] = String(val == null ? '' : val).trim().replace(/^["']|["']$/g, '');
                }
                rows.push(row);
            }
            return rows;
        }

        // ── Step 2: call preview endpoint ──────────────────────────
        function runPreview() {
            if (!_parsedRows || _parsedRows.length === 0) return;

            var prevBtn = el('btnEmpImportPreview');
            var prevText = el('btnPreviewText');
            var prevLoad = el('btnPreviewLoading');

            if (prevBtn) prevBtn.disabled = true;
            if (prevText) prevText.classList.add('d-none');
            if (prevLoad) prevLoad.classList.remove('d-none');

            showStep(2);
            show('empImportPreviewLoading');
            hide('empImportPreviewTableWrap');

            fetch('{{ route('hr.employees.import.preview') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': csrfToken(),
                    },
                    body: JSON.stringify({
                        employees: _parsedRows
                    }),
                })
                .then(function(r) {
                    return r.json();
                })
                .then(function(res) {
                    if (prevBtn) prevBtn.disabled = false;
                    if (prevText) prevText.classList.remove('d-none');
                    if (prevLoad) prevLoad.classList.add('d-none');
                    hide('empImportPreviewLoading');
                    show('empImportPreviewTableWrap');

                    if (!res || !res.success) {
                        if (typeof showToast === 'function') showToast('Preview gagal: ' + ((res && res
                            .message) || 'Error'), 'error');
                        showStep(1);
                        return;
                    }

                    _previewRows = res.rows || [];
                    _newCount = res.new || 0;

                    // Update summary badges
                    setText('empImportBadgeTotal', 'Total: ' + (res.total || 0));
                    setText('empImportBadgeNew', 'Baru: ' + (res.new || 0));
                    setText('empImportBadgeExist', 'Sudah Ada: ' + (res.existing || 0));
                    setText('empImportBadgeInvalid', 'Invalid: ' + (res.invalid || 0));
                    setText('empImportBadgeDupFile', 'Duplikat File: ' + (res.duplicate_internal || 0));

                    // Render preview table
                    var tbody = el('empImportPreviewBody');
                    if (tbody) {
                        tbody.innerHTML = _previewRows.map(function(r) {
                            var statusCls, statusLabel, rowCls;
                            if (r.status === 'new') {
                                statusCls = 'bg-success';
                                statusLabel = 'BARU';
                                rowCls = '';
                            } else if (r.status === 'duplicate_existing') {
                                statusCls = 'bg-warning text-dark';
                                statusLabel = 'SUDAH ADA';
                                rowCls = 'table-warning';
                            } else if (r.status === 'duplicate_internal') {
                                statusCls = 'bg-info text-dark';
                                statusLabel = 'DUPLIKAT FILE';
                                rowCls = 'table-info';
                            } else {
                                statusCls = 'bg-primary';
                                statusLabel = 'INVALID';
                                rowCls = 'table-primary';
                            }
                            var issues = (r.issues && r.issues.length) ?
                                '<br><span style="font-size:10.5px;color:#888">' + esc(r.issues.join(
                                    ' | ')) + '</span>' :
                                '';
                            return '<tr class="' + rowCls + '">' +
                                '<td>' + r.row + '</td>' +
                                '<td class="id-mono" style="font-size:11px">' + esc(r.employeeId ||
                                    '(auto)') + '</td>' +
                                '<td class="fw-semibold">' + esc(r.fullName || '-') + '</td>' +
                                '<td style="font-size:11px">' + esc(r.department || '-') + '</td>' +
                                '<td style="font-size:11px">' + esc(r.jobPosition || '-') + '</td>' +
                                '<td style="font-size:11px">' + esc(r.statusEmployee || 'Contract') +
                                '</td>' +
                                '<td style="font-size:11px">' + esc(r.joinDate || '-') + '</td>' +
                                '<td><span class="badge ' + statusCls + '" style="font-size:10px">' +
                                statusLabel + '</span>' + issues + '</td>' +
                                '</tr>';
                        }).join('');
                    }

                    // Enable/disable import button
                    var confirmBtn = el('btnEmpImportConfirm');
                    if (confirmBtn) {
                        confirmBtn.disabled = (_newCount === 0);
                    }
                    setText('btnImportCount', _newCount);

                    // Show/hide no-new alert
                    if (_newCount === 0) {
                        show('empImportNoNewAlert');
                    } else {
                        hide('empImportNoNewAlert');
                    }
                })
                .catch(function(err) {
                    if (prevBtn) prevBtn.disabled = false;
                    if (prevText) prevText.classList.remove('d-none');
                    if (prevLoad) prevLoad.classList.add('d-none');
                    hide('empImportPreviewLoading');
                    if (typeof showToast === 'function') showToast('Error: ' + (err ? err.message :
                        'Network error'), 'error');
                    showStep(1);
                });
        }

        // ── Step 3: execute import ──────────────────────────────────
        function runImport() {
            // Only send rows classified as 'new'
            var newRows = (_previewRows || [])
                .filter(function(r) {
                    return r.status === 'new';
                })
                .map(function(r) {
                    // Find matching original parsed row by index (row numbers are 1-based)
                    return _parsedRows[r.row - 1] || {};
                });

            if (newRows.length === 0) {
                if (typeof showToast === 'function') showToast('Tidak ada data baru untuk diimport.', 'error');
                return;
            }

            var confirmBtn = el('btnEmpImportConfirm');
            var importText = el('btnImportText');
            var importLoad = el('btnImportLoading');
            var backBtn = el('btnEmpImportBack');

            if (confirmBtn) confirmBtn.disabled = true;
            if (importText) importText.classList.add('d-none');
            if (importLoad) importLoad.classList.remove('d-none');
            if (backBtn) backBtn.disabled = true;

            fetch('{{ route('hr.employees.import') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': csrfToken(),
                    },
                    body: JSON.stringify({
                        employees: newRows
                    }),
                })
                .then(function(r) {
                    return r.json();
                })
                .then(function(res) {
                    if (importText) importText.classList.remove('d-none');
                    if (importLoad) importLoad.classList.add('d-none');
                    if (backBtn) backBtn.disabled = false;

                    showStep(3);

                    var icon = el('empImportResultIcon');
                    var title = el('empImportResultTitle');
                    var msg = el('empImportResultMessage');

                    if (res && res.success) {
                        if (icon) {
                            icon.className = 'bi bi-check-circle-fill fs-1 text-success';
                        }
                        if (title) {
                            title.textContent = 'Import Berhasil!';
                            title.className = 'mt-3 mb-1 text-success';
                        }
                        if (typeof showToast === 'function') showToast((res.message || 'Import selesai.'),
                            'success', 6000);
                    } else {
                        if (icon) {
                            icon.className = 'bi bi-exclamation-triangle-fill fs-1 text-warning';
                        }
                        if (title) {
                            title.textContent = 'Import Selesai dengan Catatan';
                            title.className = 'mt-3 mb-1';
                        }
                        if (typeof showToast === 'function') showToast((res && res.message) ||
                            'Import selesai dengan catatan.', 'warning', 6000);
                    }

                    var imported = (res && res.imported) ? res.imported : 0;
                    var errors = (res && res.errors && res.errors.length) ? res.errors : [];
                    var skipped = (res && res.errors) ? res.errors.length : 0;

                    if (msg) {
                        msg.textContent = 'Berhasil diimport: ' + imported + ' karyawan.' +
                            (skipped > 0 ? ' ' + skipped + ' baris dilewati.' : '');
                    }

                    if (errors.length > 0) {
                        show('empImportResultErrors');
                        var errList = el('empImportErrorList');
                        if (errList) {
                            errList.innerHTML = errors.map(function(e) {
                                return '<li class="py-1 border-bottom"><i class="bi bi-x-circle text-primary me-1"></i>' +
                                    esc(e) + '</li>';
                            }).join('');
                        }
                    }

                    // Reload table after short delay
                    setTimeout(function() {
                        window.location.reload();
                    }, 2500);
                })
                .catch(function(err) {
                    if (importText) importText.classList.remove('d-none');
                    if (importLoad) importLoad.classList.add('d-none');
                    if (confirmBtn) confirmBtn.disabled = false;
                    if (backBtn) backBtn.disabled = false;
                    if (typeof showToast === 'function') showToast('Error: ' + (err ? err.message :
                        'Network error'), 'error');
                });
        }

        // ── Wire up events after DOM ready ─────────────────────────
        document.addEventListener('DOMContentLoaded', function() {
            var modal = el('empImportModal');
            var dropZone = el('empImportDropZone');
            var fileInp = el('empImportFileInput');
            var clearBtn = el('btnEmpImportClearFile');
            var prevBtn = el('btnEmpImportPreview');
            var backBtn = el('btnEmpImportBack');
            var confirmBtn = el('btnEmpImportConfirm');

            // Reset on modal close
            if (modal) {
                modal.addEventListener('hidden.bs.modal', resetModal);
            }

            // Drag-drop
            if (dropZone) {
                dropZone.addEventListener('click', function() {
                    if (fileInp) fileInp.click();
                });
                dropZone.addEventListener('dragover', function(e) {
                    e.preventDefault();
                    dropZone.style.background = '#e8f5e9';
                });
                dropZone.addEventListener('dragleave', function() {
                    dropZone.style.background = '';
                });
                dropZone.addEventListener('drop', function(e) {
                    e.preventDefault();
                    dropZone.style.background = '';
                    if (e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files.length) {
                        handleFile(e.dataTransfer.files[0]);
                    }
                });
            }

            // File input change
            if (fileInp) {
                fileInp.addEventListener('change', function() {
                    if (fileInp.files && fileInp.files.length) {
                        handleFile(fileInp.files[0]);
                    }
                });
            }

            // Clear file button
            if (clearBtn) {
                clearBtn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    resetModal();
                });
            }

            // Preview button
            if (prevBtn) {
                prevBtn.addEventListener('click', function() {
                    if (_parsedRows && _parsedRows.length > 0) runPreview();
                });
            }

            // Back button
            if (backBtn) {
                backBtn.addEventListener('click', function() {
                    _previewRows = [];
                    _newCount = 0;
                    showStep(1);
                });
            }

            // Confirm import button
            if (confirmBtn) {
                confirmBtn.addEventListener('click', function() {
                    if (_newCount > 0) runImport();
                });
            }
        });
    })();
</script>
<div class="modal fade" id="promoteToProbationModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:16px">
            <div class="modal-header" style="background:#eb1c24;border-radius:16px 16px 0 0">
                <div class="d-flex align-items-center gap-2 text-white">
                    <i class="bi bi-person-up fs-5"></i>
                    <h6 class="modal-title mb-0 fw-bold">Ajukan Onboarding Probation</h6>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="" method="POST" id="formPromoteProbation">
                @csrf
                <div class="modal-body p-4">
                    <input type="hidden" id="promoteProbEmpId" name="employee_id" />

                    <!-- Info Karyawan -->
                    <div class="p-3 rounded-3 mb-3" style="background:#f0f7ff;border:1px solid #eb1c24">
                        <div class="d-flex align-items-center gap-3">
                            <div class="avatar-sm" id="promoteProbAvatar"
                                style="width:40px;height:40px;font-size:14px;flex-shrink:0;background:#eb1c24;color:#fff;border-radius:10px;display:flex;align-items:center;justify-content:center;font-weight:700">
                                ?</div>
                            <div class="flex-grow-1">
                                <div class="fw-bold text-primary" id="promoteProbEmpName" style="font-size:14px">-
                                </div>
                                <div class="text-muted" id="promoteProbPosition" style="font-size:12px">-</div>
                            </div>
                            <div class="text-end">
                                <span class="badge bg-primary" id="promoteProbBadge">Contract</span>
                            </div>
                        </div>
                    </div>

                    <p style="font-size:12.5px;color:var(--color-text-soft)">
                        Karyawan berkinerja baik ini akan didaftarkan ke <strong>Modul Evaluasi Probation</strong> untuk
                        proses penilaian menuju Karyawan Tetap (PKWTT).
                    </p>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label fw-semibold" style="font-size:12.5px">Mulai Probation <span
                                    class="text-primary">*</span></label>
                            <input type="date" class="form-control form-control-sm" name="probation_start"
                                id="promoteProbStart" value="{{ date('Y-m-d') }}" required />
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold" style="font-size:12.5px">Durasi Probation <span
                                    class="text-primary">*</span></label>
                            <select class="form-select form-select-sm" name="probation_duration"
                                id="promoteProbDuration" required>
                                <option value="1 Bulan">1 Bulan</option>
                                <option value="3 Bulan" selected>3 Bulan</option>
                                <option value="6 Bulan">6 Bulan</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold" style="font-size:12.5px">Catatan Rekomendasi HR /
                                Manager</label>
                            <textarea class="form-control form-control-sm" name="notes" id="promoteProbNotes" rows="2"
                                placeholder="Contoh: Kinerja melampaui target, direkomendasikan probation tetap..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary btn-sm"
                        data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-sm text-white fw-semibold" style="background:#eb1c24">
                        <i class="bi bi-person-check-fill me-1"></i>Daftarkan ke Probation
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="exportPdfModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content" style="border-radius:16px">
            <div class="modal-header" style="background:#eb1c24;border-radius:16px 16px 0 0">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-file-earmark-pdf text-white fs-5"></i>
                    <h6 class="modal-title mb-0 text-white fw-bold">Export PDF Dokumen Karyawan</h6>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <!-- STEP 1: Pilih karyawan -->
                <div class="mb-3">
                    <label class="form-label fw-semibold" style="font-size:13px">
                        <i class="bi bi-person me-1"></i>Cari Karyawan <span class="text-primary">*</span>
                    </label>
                    <div class="position-relative">
                        <input type="text" class="form-control" id="pdfEmpSearch"
                            placeholder="Ketik nama atau Employee ID..." autocomplete="off"
                            style="font-size:13px;padding-right:36px" />
                        <i class="bi bi-x-circle-fill position-absolute" id="pdfEmpSearchClear"
                            style="right:10px;top:50%;transform:translateY(-50%);cursor:pointer;color:#aaa;display:none"></i>
                    </div>
                    <div id="pdfEmpDropdown" class="border rounded-3 mt-1 shadow-sm"
                        style="display:none;max-height:220px;overflow-y:auto;background:#fff;z-index:9999;position:relative">
                    </div>
                </div>

                <!-- STEP 2: Preview + pilih jenis dokumen -->
                <div id="pdfEmpPreview" style="display:none">
                    <div class="p-3 rounded-3 mb-4" style="background:#f0f7ff;border:1px solid #b8d4ff">
                        <div class="d-flex align-items-center gap-3">
                            <div id="pdfEmpAvatar"
                                style="width:44px;height:44px;font-size:16px;flex-shrink:0;background:#eb1c24;color:#fff;border-radius:12px;display:flex;align-items:center;justify-content:center;font-weight:800">
                                ?</div>
                            <div class="flex-grow-1">
                                <div class="fw-bold text-primary" id="pdfEmpName" style="font-size:15px">-</div>
                                <div class="text-muted" style="font-size:12px">
                                    <span id="pdfEmpPosition">-</span> <span class="mx-1">&bull;</span> <span
                                        id="pdfEmpDept">-</span>
                                </div>
                            </div>
                            <div class="text-end flex-shrink-0" style="font-size:11.5px">
                                <div class="text-muted">Employee ID</div>
                                <div class="fw-semibold text-primary" id="pdfEmpIdDisp">-</div>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fw-semibold" style="font-size:13px">Pilih Jenis Dokumen <span
                                    class="text-primary">*</span></label>
                            <select class="form-select" id="pdfDocType">
                                <option value="">— Pilih Dokumen —</option>
                                <option value="sk-rotation">SK Rotasi / Mutasi</option>
                                <option value="sk-off">SK Offboarding / Resign</option>
                                <option value="surat-bpjs">Surat Keterangan BPJS</option>
                                <option value="paklaring">Paklaring (Surat Keterangan Kerja)</option>
                                <option value="sk-pengangkatan">SK Pengangkatan Karyawan Tetap</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary btn-sm"
                    data-bs-dismiss="modal">Batal</button>
                <button class="btn btn-sm text-white fw-semibold" style="background:#eb1c24" id="btnExportPdf"
                    disabled>
                    <i class="bi bi-file-earmark-pdf me-1"></i>Unduh PDF
                </button>
            </div>
        </div>
    </div>
</div>

@php
    // Serialize employees untuk PDF modal search
    $allEmpForPdf = collect($all ?? ($employees ?? []))
        ->map(function ($e) {
            if (!is_object($e)) {
                return $e;
            }
            return [
                'employeeId' => $e->employeeId ?? null,
                'fullName' => $e->fullName ?? null,
                'statusEmployee' => $e->statusEmployee ?? null,
                'jobPosition' => $e->jobPosition ?? null,
                'jobPositionLocation' => $e->jobPositionLocation ?? null,
                'department' => $e->department ?? null,
                'branchName' => $e->branchName ?? null,
                'joinDate' => $e->joinDate ?? null,
            ];
        })
        ->values()
        ->all();
@endphp
<script>
    window.__allEmployeesForPdf = @json($allEmpForPdf);
    let _pdfSelectedEmployee = null;

    function handlePdfEmpSearch(query) {
        const q = (query || '').toLowerCase().trim();
        const dropdown = document.getElementById('pdfEmpDropdown');
        const clearBtn = document.getElementById('pdfEmpSearchClear');
        clearBtn.style.display = q ? 'block' : 'none';

        if (!q) {
            dropdown.style.display = 'none';
            return;
        }

        const matched = (window.__allEmployeesForPdf || []).filter(e => {
            const name = (e.fullName || '').toLowerCase();
            const id = (e.employeeId || '').toLowerCase();
            const pos = (e.jobPosition || '').toLowerCase();
            return name.includes(q) || id.includes(q) || pos.includes(q);
        }).slice(0, 8);

        if (matched.length === 0) {
            dropdown.innerHTML =
                '<div class="p-3 text-muted text-center" style="font-size:13px">Tidak ada karyawan yang cocok</div>';
            dropdown.style.display = 'block';
            return;
        }

        dropdown.innerHTML = matched.map(function(emp) {
            return '<div class="p-2 border-bottom d-flex align-items-center gap-2 hover-item pdf-search-item" style="cursor:pointer;" data-emp-id="' +
                (emp.employeeId || '') + '">' +
                '<div class="avatar-sm" style="width:32px;height:32px;background:#0B2540;color:#fff;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700">' +
                (emp.fullName || 'E').substring(0, 2).toUpperCase() +
                '</div>' +
                '<div class="flex-grow-1" style="font-size:12.5px;">' +
                '<div class="fw-semibold text-primary">' + (emp.fullName || '-') + '</div>' +
                '<div class="text-muted" style="font-size:11px">' + (emp.employeeId || '') + ' &bull; ' + (emp
                    .jobPosition || '-') + '</div>' +
                '</div></div>';
        }).join('');
        dropdown.querySelectorAll('.pdf-search-item').forEach(function(item) {
            item.addEventListener('click', function() {
                var empId = item.getAttribute('data-emp-id');
                var found = (window.__allEmployeesForPdf || []).find(function(e) {
                    return e.employeeId === empId;
                });
                if (found) selectPdfEmployee(found);
            });
        });
        dropdown.style.display = 'block';
    }

    function selectPdfEmployee(emp) {
        document.getElementById('pdfEmpDropdown').style.display = 'none';
        document.getElementById('pdfEmpSearch').value = `${emp.fullName} (${emp.employeeId})`;
        _pdfSelectedEmployee = emp;

        document.getElementById('pdfEmpName').textContent = emp.fullName || '-';
        document.getElementById('pdfEmpPosition').textContent = emp.jobPosition || '-';
        document.getElementById('pdfEmpDept').textContent = emp.department || '-';
        document.getElementById('pdfEmpIdDisp').textContent = emp.employeeId;
        document.getElementById('pdfEmpAvatar').textContent = (emp.fullName || 'E').substring(0, 2).toUpperCase();

        document.getElementById('pdfEmpPreview').style.display = 'block';
        document.getElementById('btnExportPdf').disabled = false;
    }

    function clearPdfEmpSearch() {
        document.getElementById('pdfEmpSearch').value = '';
        document.getElementById('pdfEmpDropdown').style.display = 'none';
        document.getElementById('pdfEmpSearchClear').style.display = 'none';
        document.getElementById('pdfEmpPreview').style.display = 'none';
        document.getElementById('btnExportPdf').disabled = true;
        _pdfSelectedEmployee = null;
    }

    function generatePdfFromModal() {
        const emp = _pdfSelectedEmployee;
        if (!emp) {
            showToast('Pilih karyawan terlebih dahulu.', 'error');
            return;
        }
        const docType = document.getElementById('pdfDocType').value;
        if (!docType) {
            showToast('Pilih jenis dokumen.', 'error');
            return;
        }

        const routes = {
            'sk-rotation': `/hr/export/sk-rotation/${emp.employeeId}`,
            'sk-off': `/hr/export/sk-off/${emp.employeeId}`,
            'surat-bpjs': `/hr/export/surat-bpjs/${emp.employeeId}`,
            'paklaring': `/hr/export/paklaring/${emp.employeeId}`,
            'sk-pengangkatan': `/hr/export/sk-pengangkatan/${emp.employeeId}`,
        };
        window.open(routes[docType], '_blank');
        // Optionally close modal
        // bootstrap.Modal.getInstance(document.getElementById('exportPdfModal')).hide();
    }

    // Reset modal on close
    document.getElementById('exportPdfModal').addEventListener('hidden.bs.modal', function() {
        clearPdfEmpSearch();
    });
</script>
