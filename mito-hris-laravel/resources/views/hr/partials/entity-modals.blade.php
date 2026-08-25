<!-- partials/EntityModals.html — OFFBOARDING, IMPORT CSV/EXCEL & PROMOTE PROBATION MODALS (1:1 from GAS) -->

<!-- 1. OFFBOARDING MODAL -->
<div class="modal fade" id="offboardingModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content" style="border-radius:16px">
      <div class="modal-header" style="background:#991b1b;border-radius:16px 16px 0 0">
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
              <i class="bi bi-search me-1"></i>Cari Karyawan Aktif <span class="text-danger">*</span>
            </label>
            <div class="position-relative">
              <input type="text" class="form-control" id="offEmpSearch"
                     placeholder="Ketik nama atau Employee ID..."
                     autocomplete="off" style="font-size:13px;padding-right:36px" oninput="handleOffEmpSearch(this.value)" />
              <i class="bi bi-x-circle-fill position-absolute" id="offEmpSearchClear"
                 style="right:10px;top:50%;transform:translateY(-50%);cursor:pointer;color:#aaa;display:none" onclick="clearOffEmpSearch()"></i>
            </div>
            <div id="offEmpDropdown" class="border rounded-3 mt-1 shadow-sm"
                 style="display:none;max-height:220px;overflow-y:auto;background:#fff;z-index:9999;position:relative">
            </div>
          </div>

          <!-- STEP 2: Preview karyawan terpilih + form -->
          <div id="offEmpPreview" style="display:none">
            <div class="p-3 rounded-3 mb-4" style="background:#fef2f2;border:1px solid #fecaca">
              <div class="d-flex align-items-center gap-3">
                <div id="offEmpAvatar" style="width:44px;height:44px;font-size:16px;flex-shrink:0;background:#991b1b;color:#fff;border-radius:12px;display:flex;align-items:center;justify-content:center;font-weight:800">?</div>
                <div class="flex-grow-1">
                  <div class="fw-bold text-navy" id="offEmpName" style="font-size:15px">-</div>
                  <div class="text-muted" style="font-size:12px">
                    <span id="offEmpPosition">-</span> <span class="mx-1">&bull;</span> <span id="offEmpDept">-</span>
                  </div>
                </div>
                <div class="text-end flex-shrink-0" style="font-size:11.5px">
                  <div class="text-muted">Employee ID</div>
                  <div class="fw-semibold text-danger" id="offEmpIdDisp">-</div>
                  <div class="text-muted mt-1">Status Saat Ini</div>
                  <div class="fw-semibold" id="offEmpStatusDisp">-</div>
                </div>
              </div>
            </div>

            <!-- Form offboarding -->
            <div id="offFormSection">
              <p class="fw-bold mb-3" style="font-size:12px;text-transform:uppercase;letter-spacing:0.05em;color:#991b1b">
                <i class="bi bi-info-circle me-1"></i>Detail Offboarding
              </p>
              <div class="row g-3 mb-3">
                <div class="col-md-6">
                  {{--
                    Offboarding types 1:1 GAS _OFFB_DOC_TYPES:
                    Resignation / Termination / Retirement / Death
                  --}}
                  <label class="form-label fw-semibold" style="font-size:13px">Tipe Offboarding <span class="text-danger">*</span></label>
                  <select class="form-select form-select-sm" name="offboarding_type" id="offType" required>
                    <option value="">— Pilih Tipe —</option>
                    <option value="Resignation">Pengunduran Diri (Resignation)</option>
                    <option value="Termination">Pemutusan Hubungan Kerja (Termination)</option>
                    <option value="Retirement">Pensiun (Retirement)</option>
                    <option value="Death">Meninggal Dunia (Death)</option>
                  </select>
                </div>
                <div class="col-md-6">
                  <label class="form-label fw-semibold" style="font-size:13px">Tanggal Efektif <span class="text-danger">*</span></label>
                  <input type="date" class="form-control form-control-sm" name="last_working_date" id="offEffectiveDate" value="{{ date('Y-m-d') }}" required />
                </div>
                <div class="col-md-12">
                  <label class="form-label fw-semibold" style="font-size:13px">Alasan Offboarding <span class="text-danger">*</span></label>
                  <textarea class="form-control form-control-sm" name="reason" id="offReason" rows="2" placeholder="Tuliskan alasan pengunduran diri / pemutusan hubungan kerja..." required></textarea>
                </div>
                <div class="col-md-6">
                  <label class="form-label fw-semibold" style="font-size:13px">No. BPJS Ketenagakerjaan</label>
                  <input type="text" class="form-control form-control-sm" name="bpjs_tk" id="offBpjsTk" placeholder="Untuk proses klaim JHT" />
                </div>
                <div class="col-md-6">
                  <label class="form-label fw-semibold" style="font-size:13px">No. BPJS Kesehatan</label>
                  <input type="text" class="form-control form-control-sm" name="bpjs_kes" id="offBpjsKes" placeholder="Untuk proses nonaktivasi" />
                </div>
                <div class="col-md-12">
                  <label class="form-label fw-semibold" style="font-size:13px">Approved By</label>
                  <input type="text" class="form-control form-control-sm" name="approved_by" id="offApprovedBy" value="Human Resources Department" />
                </div>
                <div class="col-md-12">
                  <label class="form-label fw-semibold" style="font-size:13px">Catatan Tambahan</label>
                  <textarea class="form-control form-control-sm" name="notes" id="offNotes" rows="2" placeholder="Catatan internal tim HR..."></textarea>
                </div>
              </div>

              <!-- DYNAMIC ATTACHMENT SECTION — 1:1 GAS _OFFB_DOC_TYPES -->
              <div id="offAttachmentSection" style="display:none;">
                <hr class="my-3">
                <p class="fw-bold mb-3" style="font-size:12px;text-transform:uppercase;letter-spacing:.05em;color:#991b1b">
                  <i class="bi bi-paperclip me-1"></i>Lampiran Dokumen
                </p>
                <div id="offAttachmentRequired" class="mb-2"></div>
                <div id="offAttachmentOptional" class="mb-2"></div>
              </div>

              <!-- Validation feedback -->
              <div id="offAttachmentError" class="alert alert-danger py-2 d-none" style="font-size:13px" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-1"></i>
                <span id="offAttachmentErrorMsg"></span>
              </div>
            </div>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-sm text-white fw-semibold" style="background:#991b1b" id="btnConfirmOffboarding" disabled>
            <span id="btnOffbText"><i class="bi bi-box-arrow-right me-1"></i>Proses Offboarding</span>
            <span id="btnOffbLoading" class="d-none"><span class="spinner-border spinner-border-sm me-1"></span>Memproses...</span>
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
    'Resignation': { required: [], optional: ['Surat Resign', 'Paklaring', 'Dokumen Lainnya'] },
    'Termination': { required: [], optional: ['SK PHK', 'Paklaring', 'Dokumen Lainnya'] },
    'Retirement':  { required: [], optional: ['SK Pensiun', 'Paklaring', 'Dokumen Lainnya'] },
    'Death':       { required: ['Surat Kematian'], optional: ['Dokumen Lainnya'] }
  };
  var _OFFB_MAX_BYTES = 5 * 1024 * 1024;
  var _OFFB_ACCEPT = '.pdf,.jpg,.jpeg,.png,.doc,.docx';
  var _offActiveEmp = null;

  function _escHtml(str) {
    return String(str || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
  }

  // ── ATTACHMENT UI ────────────────────────────────────────────────────────
  function _renderOffAttachments(offbType) {
    var section = document.getElementById('offAttachmentSection');
    var reqEl   = document.getElementById('offAttachmentRequired');
    var optEl   = document.getElementById('offAttachmentOptional');
    if (!section || !reqEl || !optEl) return;
    var config = _OFFB_DOC_TYPES[offbType];
    if (!config) { section.style.display = 'none'; reqEl.innerHTML = ''; optEl.innerHTML = ''; return; }

    reqEl.innerHTML = '';
    optEl.innerHTML = '';

    config.required.forEach(function(docType) {
      var inputName = 'attachment_' + docType.replace(/\s+/g, '_');
      reqEl.innerHTML +=
        '<div class="mb-3">' +
          '<label class="form-label fw-semibold" style="font-size:13px">' +
            '<span class="text-danger me-1">*</span>' + _escHtml(docType) +
            ' <span class="badge bg-danger ms-1" style="font-size:10px">Wajib</span>' +
          '</label>' +
          '<input type="file" class="form-control form-control-sm offb-attach-input" ' +
                 'name="' + inputName + '" accept="' + _OFFB_ACCEPT + '" ' +
                 'data-doc-type="' + _escHtml(docType) + '" data-required="1">' +
          '<div class="form-text text-muted" style="font-size:11px">Format: PDF, JPG, PNG, DOC, DOCX — maks 5 MB</div>' +
        '</div>';
    });

    if (config.optional.length) {
      optEl.innerHTML += '<div class="text-muted mb-2" style="font-size:12px;font-weight:600">Lampiran Opsional:</div>';
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
      inp.addEventListener('change', function() { _hideOffAttachError(); _updateOffConfirmBtn(); });
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
    if (!q) { if (dropdown) dropdown.style.display = 'none'; return; }
    var source = window.__allEmployees || window.__allEmployeesForPdf || [];
    var matched = source.filter(function(e) {
      var s = (e.statusEmployee || '').trim().toLowerCase();
      var ok = s !== 'resigned' && s !== 'terminated' && s !== 'retired'
             && s !== 'deceased' && s !== 'inactive' && s !== 'contract finished' && s !== '';
      return ok && (
        (e.fullName || '').toLowerCase().indexOf(q) !== -1 ||
        (e.employeeId || '').toLowerCase().indexOf(q) !== -1
      );
    }).slice(0, 8);
    if (!matched.length) {
      dropdown.innerHTML = '<div class="p-3 text-muted text-center" style="font-size:13px">Karyawan tidak ditemukan</div>';
      dropdown.style.display = 'block'; return;
    }
    dropdown.innerHTML = matched.map(function(emp) {
      return '<div class="p-2 border-bottom d-flex align-items-center gap-2 hover-item off-search-item" style="cursor:pointer;" data-emp-id="' + _escHtml(emp.employeeId || '') + '">' +
        '<div style="width:32px;height:32px;background:#991b1b;color:#fff;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700">' +
          _escHtml((emp.fullName || 'E').substring(0,2).toUpperCase()) + '</div>' +
        '<div class="flex-grow-1" style="font-size:12.5px;"><div class="fw-semibold">' + _escHtml(emp.fullName || '-') + '</div>' +
        '<div class="text-muted" style="font-size:11px">' + _escHtml(emp.employeeId || '') + ' &bull; ' + _escHtml(emp.jobPosition || '-') + '</div></div></div>';
    }).join('');
    dropdown.querySelectorAll('.off-search-item').forEach(function(item) {
      item.addEventListener('click', function() {
        var empId = item.getAttribute('data-emp-id');
        var found = (window.__allEmployees || window.__allEmployeesForPdf || []).find(function(e) { return e.employeeId === empId; });
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
      offEmpAvatar:    (emp.fullName || 'E').substring(0,2).toUpperCase(),
      offEmpName:      emp.fullName || '-',
      offEmpPosition:  emp.jobPosition || emp.jobPositionLocation || '-',
      offEmpDept:      emp.department || '-',
      offEmpIdDisp:    emp.employeeId || '-',
      offEmpStatusDisp: emp.statusEmployee || '-',
    };
    Object.keys(els).forEach(function(id) { var el = document.getElementById(id); if (el) el.textContent = els[id]; });
    // Pre-fill BPJS
    var bpjsTkEl  = document.getElementById('offBpjsTk');
    var bpjsKesEl = document.getElementById('offBpjsKes');
    if (bpjsTkEl  && !bpjsTkEl.value)  bpjsTkEl.value  = (emp.bpjsKetenagakerjaan || '').replace(/^'/,'');
    if (bpjsKesEl && !bpjsKesEl.value) bpjsKesEl.value = (emp.bpjsKesehatan || '').replace(/^'/,'');
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
    var preview  = document.getElementById('offEmpPreview');
    if (searchEl) searchEl.value = '';
    if (clearBtn) clearBtn.style.display = 'none';
    if (dropdown) dropdown.style.display = 'none';
    if (preview)  preview.style.display = 'none';
    _updateOffConfirmBtn();
  }

  function _updateOffConfirmBtn() {
    var btn = document.getElementById('btnConfirmOffboarding');
    if (!btn) return;
    var type   = (document.getElementById('offType') || {}).value || '';
    var date   = (document.getElementById('offEffectiveDate') || {}).value || '';
    var reason = ((document.getElementById('offReason') || {}).value || '').trim();
    var baseOk = !!(_offActiveEmp && type && date && reason);
    var deathOk = true;
    if (type === 'Death') {
      var deathInput = document.querySelector('#formOffboarding [name="attachment_Surat_Kematian"]');
      deathOk = !!(deathInput && deathInput.files && deathInput.files.length);
    }
    btn.disabled = !(baseOk && deathOk);
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
      if (el) { el.addEventListener('input', _updateOffConfirmBtn); el.addEventListener('change', _updateOffConfirmBtn); }
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
        var sec   = document.getElementById('offAttachmentSection');
        if (reqEl) reqEl.innerHTML = '';
        if (optEl) optEl.innerHTML = '';
        if (sec)   sec.style.display = 'none';
        _hideOffAttachError();
        ['offBpjsTk','offBpjsKes'].forEach(function(id) { var el = document.getElementById(id); if (el) el.value = ''; });
        _updateOffConfirmBtn();
      });
    }

    // ── AJAX submit — multipart FormData with file attachments ─────────────
    var offForm = document.getElementById('formOffboarding');
    var offBtn  = document.getElementById('btnConfirmOffboarding');
    if (offForm && offBtn) {
      offForm.addEventListener('submit', function(e) {
        e.preventDefault();
        if (!_offActiveEmp) return;
        var empId  = _offActiveEmp.employeeId;
        var type   = (document.getElementById('offType') || {}).value || '';
        var date   = (document.getElementById('offEffectiveDate') || {}).value || '';
        var reason = ((document.getElementById('offReason') || {}).value || '').trim();
        if (!empId || !type || !date || !reason) return;
        if (!_validateOffAttachments()) return;

        var txtEl = document.getElementById('btnOffbText');
        var ldEl  = document.getElementById('btnOffbLoading');
        offBtn.disabled = true;
        if (txtEl) txtEl.classList.add('d-none');
        if (ldEl)  ldEl.classList.remove('d-none');

        var formData = new FormData(offForm);
        if (!formData.get('last_working_date') && date) formData.set('last_working_date', date);

        fetch('/hr/employees/' + empId + '/offboard', {
          method: 'POST',
          headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') || {}).content || ''
          },
          body: formData
        })
        .then(function(r) { return r.json(); })
        .then(function(res) {
          offBtn.disabled = false;
          if (txtEl) txtEl.classList.remove('d-none');
          if (ldEl)  ldEl.classList.add('d-none');
          if (res && res.success) {
            if (typeof showToast === 'function') {
              showToast('Offboarding <strong>' + _escHtml((_offActiveEmp || {}).fullName || '') + '</strong> berhasil diproses.', 'success', 6000);
            }
            // Auto-download 3 PDFs via staggered hidden iframes (1:1 GAS behavior)
            var pdfUrls = res.pdf_urls || {};
            var delay = 400;
            ['sk_off', 'surat_bpjs', 'paklaring'].forEach(function(key) {
              var url = pdfUrls[key];
              if (!url) return;
              setTimeout(function() {
                var iframe = document.createElement('iframe');
                iframe.style.cssText = 'display:none;width:0;height:0;border:0';
                iframe.src = url;
                document.body.appendChild(iframe);
                setTimeout(function() { try { document.body.removeChild(iframe); } catch(_){} }, 20000);
              }, delay);
              delay += 1500;
            });
            var modal = bootstrap.Modal.getInstance(document.getElementById('offboardingModal'));
            if (modal) modal.hide();
            setTimeout(function() { window.location.reload(); }, 800);
          } else {
            if (typeof showToast === 'function') {
              showToast('Gagal: ' + (res ? (res.message || 'Error') : 'Tidak ada respon'), 'error');
            } else { alert('Gagal: ' + (res ? res.message : 'Error')); }
          }
        })
        .catch(function(err) {
          offBtn.disabled = false;
          if (txtEl) txtEl.classList.remove('d-none');
          if (ldEl)  ldEl.classList.add('d-none');
          if (typeof showToast === 'function') {
            showToast('Error: ' + (err ? err.message : 'Network error'), 'error');
          } else { alert('Error: ' + (err ? err.message : 'Network error')); }
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
        var empIdEl  = document.getElementById('empDrEmployeeId');
        var empNameEl = document.getElementById('drawerCandidateName');
        var empPosEl  = document.getElementById('empDrPosition');
        var empDeptEl = document.getElementById('empDrDept');
        var empStatEl = document.getElementById('empDrStatusEmployee');

        var empId   = empIdEl   ? (empIdEl.innerText   || '').trim() : '';
        var empName = empNameEl ? (empNameEl.innerText  || '').trim() : '';
        var empPos  = empPosEl  ? (empPosEl.innerText   || '').trim() : '';
        var empDept = empDeptEl ? (empDeptEl.innerText  || '').trim() : '';
        var empStat = empStatEl ? (empStatEl.innerText  || '').trim() : 'Contract';

        if (!empId) return;

        // Fill modal fields
        var empIdInput = document.getElementById('promoteProbEmpId');
        var nameEl     = document.getElementById('promoteProbEmpName');
        var posEl      = document.getElementById('promoteProbPosition');
        var avatarEl   = document.getElementById('promoteProbAvatar');
        var badgeEl    = document.getElementById('promoteProbBadge');
        var formEl     = document.getElementById('formPromoteProbation');

        if (empIdInput) empIdInput.value = empId;
        if (nameEl)   nameEl.textContent = empName || '-';
        if (posEl)    posEl.textContent  = (empPos || '-') + (empDept ? ' · ' + empDept : '');
        if (avatarEl) avatarEl.textContent = (empName || 'K').substring(0,2).toUpperCase();
        if (badgeEl)  badgeEl.textContent  = empStat;
        if (formEl)   formEl.action = '/hr/employees/' + empId + '/promote-probation';

        // Set default start date
        var startEl = document.getElementById('promoteProbStart');
        if (startEl) startEl.value = new Date().toISOString().split('T')[0];

        // Show modal
        var modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('promoteToProbationModal'));
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
        if (submitBtn) { submitBtn.disabled = true; submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Memproses...'; }

        var formData = new FormData(probForm);
        // probation_end is not in form, compute from start + duration
        var startVal = (document.getElementById('promoteProbStart') || {}).value || '';
        var durVal   = (document.getElementById('promoteProbDuration') || {}).value || '3 Bulan';
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
            'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') || {}).content || ''
          },
          body: formData
        })
        .then(function(r) { return r.json(); })
        .then(function(res) {
          if (submitBtn) { submitBtn.disabled = false; submitBtn.innerHTML = origHtml; }
          var modal = bootstrap.Modal.getInstance(document.getElementById('promoteToProbationModal'));
          if (modal) modal.hide();
          if (res && res.success) {
            if (typeof showToast === 'function') showToast(res.message || 'Berhasil didaftarkan ke Probation.', 'success', 5000);
            else alert(res.message || 'Berhasil.');
            setTimeout(function() { window.location.reload(); }, 800);
          } else {
            if (typeof showToast === 'function') showToast('Gagal: ' + (res ? (res.message || 'Error') : 'Error'), 'error');
            else alert('Gagal: ' + (res ? res.message : 'Error'));
          }
        })
        .catch(function(err) {
          if (submitBtn) { submitBtn.disabled = false; submitBtn.innerHTML = origHtml; }
          if (typeof showToast === 'function') showToast('Error: ' + (err ? err.message : 'Network error'), 'error');
          else alert('Error: ' + (err ? err.message : 'Network error'));
        });
      });
    }
  });
</script>

<!-- 2. IMPORT CSV / EXCEL MODAL — Master Data & Employee (1:1 from GAS Modals.html lines 68-171) -->
<div class="modal fade" id="empImportModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content" style="border-radius:16px">
      <div class="modal-header" style="background:#166534;color:#fff;border-radius:16px 16px 0 0">
        <h6 class="modal-title mb-0 fw-bold"><i class="bi bi-upload me-2"></i>Import Karyawan (CSV / Excel)</h6>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-4">
        <!-- Step 1: File upload -->
        <div id="empImportStep1">
          <p style="font-size:13.5px;color:var(--color-text-soft)">
            Upload file <strong>CSV</strong> atau <strong>Excel (.xlsx/.xls)</strong> dengan format kolom yang sesuai. Kolom <strong>wajib</strong>: <code>fullName</code>. Semua kolom lain opsional.
          </p>
          <div class="border rounded-3 p-4 text-center" id="empImportDropZone" style="cursor:pointer;border-style:dashed!important;transition:background .2s;background:var(--color-bg)" onclick="document.getElementById('empImportFileInput').click()">
            <i class="bi bi-file-earmark-spreadsheet fs-1" style="color:var(--color-primary, #eb1c24)"></i>
            <p class="mb-1 mt-2 fw-semibold" style="font-size:14px">Klik atau seret file CSV / Excel ke sini</p>
            <p class="mb-0 text-muted" style="font-size:12px">Format: .csv &bull; .xlsx &bull; .xls — maks 5MB &bull; maks 500 baris</p>
            <input type="file" id="empImportFileInput" accept=".csv,.xlsx,.xls" class="d-none" onchange="handleEmpImportFileSelect(this)" />
          </div>
          <div class="mt-3" id="empImportFileInfo" style="display:none">
            <div class="d-flex align-items-center gap-2 p-2 rounded" style="background:var(--color-bg)">
              <i class="bi bi-file-earmark-check fs-5 text-success"></i>
              <div>
                <div class="fw-semibold" style="font-size:13px" id="empImportFileName">-</div>
                <div class="text-muted" style="font-size:12px" id="empImportFileSize">-</div>
              </div>
              <button class="btn btn-sm btn-outline-danger ms-auto" type="button" onclick="clearEmpImportFile()"><i class="bi bi-x"></i></button>
            </div>
          </div>
          <!-- Kolom yang dikenali (1:1 from GAS) -->
          <div class="mt-3 p-3 rounded" style="background:var(--color-bg);border:1px solid var(--color-border);font-size:11.5px">
            <div class="fw-semibold mb-1" style="font-size:12px"><i class="bi bi-info-circle me-1"></i>Kolom yang dikenali (sesuai header Employee)</div>
            <div class="mb-1"><span class="badge bg-danger me-1">Wajib</span> <code>fullName</code> (atau: <code>nama</code>, <code>namalengkap</code>)</div>
            <div style="color:var(--color-text-soft)">
              <strong>Identitas:</strong> <code>nik</code>, <code>npwp</code>, <code>birthPlace</code>, <code>birthDate</code>, <code>gender</code>, <code>religion</code>, <code>maritalStatus</code>, <code>bloodType</code><br>
              <strong>Kontak:</strong> <code>citizenIdAddress</code>, <code>residentialAddress</code>, <code>mobilePhone</code>, <code>personalEmail</code>, <code>workingEmail</code><br>
              <strong>Bank &amp; BPJS:</strong> <code>bankName</code>, <code>bankAccount</code>, <code>bankAccountHolder</code>, <code>bpjsKetenagakerjaan</code>, <code>bpjsKesehatan</code><br>
              <strong>Organisasi:</strong> <code>branchName</code>, <code>division</code>, <code>department</code>, <code>positionCurrent</code>, <code>jobLevel</code>, <code>areaKerja</code>, <code>lokasiKerja</code><br>
              <strong>Status &amp; Kontrak:</strong> <code>statusEmployee</code>, <code>joinDate</code>, <code>endDateContract</code>, <code>outsourceVendor</code>
            </div>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <a href="{{ route('hr.export.employees-csv') }}" class="btn btn-outline-secondary btn-sm" id="btnEmpDownloadTemplate">
          <i class="bi bi-download me-1"></i>Download Template CSV
        </a>
        <button class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Tutup</button>
        <button class="btn btn-sm text-white fw-semibold" style="background:#166534" id="btnEmpImportStart" disabled onclick="simulateEmpImport()">
          <i class="bi bi-upload me-1"></i>Mulai Import
        </button>
      </div>
    </div>
  </div>
</div>

<!-- 3. AJUKAN ONBOARDING PROBATION MODAL (1:1 from GAS Modals.html lines 172-238) -->
<div class="modal fade" id="promoteToProbationModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content" style="border-radius:16px">
      <div class="modal-header" style="background:#0B2540;border-radius:16px 16px 0 0">
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
          <div class="p-3 rounded-3 mb-3" style="background:#f0f7ff;border:1px solid #c7dff7">
            <div class="d-flex align-items-center gap-3">
              <div class="avatar-sm" id="promoteProbAvatar"
                style="width:40px;height:40px;font-size:14px;flex-shrink:0;background:#0B2540;color:#fff;border-radius:10px;display:flex;align-items:center;justify-content:center;font-weight:700">?</div>
              <div class="flex-grow-1">
                <div class="fw-bold text-navy" id="promoteProbEmpName" style="font-size:14px">-</div>
                <div class="text-muted" id="promoteProbPosition" style="font-size:12px">-</div>
              </div>
              <div class="text-end">
                <span class="badge bg-secondary" id="promoteProbBadge">Contract</span>
              </div>
            </div>
          </div>

          <p style="font-size:12.5px;color:var(--color-text-soft)">
            Karyawan berkinerja baik ini akan didaftarkan ke <strong>Modul Evaluasi Probation</strong> untuk proses penilaian menuju Karyawan Tetap (PKWTT).
          </p>

          <div class="row g-2 mb-3">
            <div class="col-6">
              <label class="form-label fw-semibold" style="font-size:12.5px">Mulai Probation <span class="text-danger">*</span></label>
              <input type="date" class="form-control form-control-sm" name="probation_start" id="promoteProbStart" value="{{ date('Y-m-d') }}" required />
            </div>
            <div class="col-6">
              <label class="form-label fw-semibold" style="font-size:12.5px">Durasi Probation <span class="text-danger">*</span></label>
              <select class="form-select form-select-sm" name="probation_duration" id="promoteProbDuration" required>
                <option value="1 Bulan">1 Bulan</option>
                <option value="3 Bulan" selected>3 Bulan</option>
                <option value="6 Bulan">6 Bulan</option>
              </select>
            </div>
            <div class="col-12">
              <label class="form-label fw-semibold" style="font-size:12.5px">Catatan Rekomendasi HR / Manager</label>
              <textarea class="form-control form-control-sm" name="notes" id="promoteProbNotes" rows="2"
                placeholder="Contoh: Kinerja melampaui target, direkomendasikan probation tetap..."></textarea>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-sm text-white fw-semibold" style="background:#0B2540">
            <i class="bi bi-person-check-fill me-1"></i>Daftarkan ke Probation
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
  function handleEmpImportFileSelect(input) {
    if (input.files && input.files[0]) {
      const file = input.files[0];
      document.getElementById('empImportFileName').textContent = file.name;
      document.getElementById('empImportFileSize').textContent = (file.size / 1024).toFixed(1) + ' KB';
      document.getElementById('empImportFileInfo').style.display = 'block';
      document.getElementById('btnEmpImportStart').disabled = false;
    }
  }

  function clearEmpImportFile() {
    document.getElementById('empImportFileInput').value = '';
    document.getElementById('empImportFileInfo').style.display = 'none';
    document.getElementById('btnEmpImportStart').disabled = true;
  }

  function simulateEmpImport() {
    const btn = document.getElementById('btnEmpImportStart');
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Mengimpor berkas...';
    btn.disabled = true;
    setTimeout(() => {
      alert('Berkas berhasil diproses dan disinkronkan ke master spreadsheet.');
      location.reload();
    }, 1200);
  }
</script>

<div class="modal fade" id="exportPdfModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content" style="border-radius:16px">
      <div class="modal-header" style="background:#0B2540;border-radius:16px 16px 0 0">
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
            <i class="bi bi-person me-1"></i>Cari Karyawan <span class="text-danger">*</span>
          </label>
          <div class="position-relative">
            <input type="text" class="form-control" id="pdfEmpSearch"
                   placeholder="Ketik nama atau Employee ID..."
                   autocomplete="off" style="font-size:13px;padding-right:36px" oninput="handlePdfEmpSearch(this.value)" />
            <i class="bi bi-x-circle-fill position-absolute" id="pdfEmpSearchClear"
               style="right:10px;top:50%;transform:translateY(-50%);cursor:pointer;color:#aaa;display:none" onclick="clearPdfEmpSearch()"></i>
          </div>
          <div id="pdfEmpDropdown" class="border rounded-3 mt-1 shadow-sm"
               style="display:none;max-height:220px;overflow-y:auto;background:#fff;z-index:9999;position:relative">
          </div>
        </div>

        <!-- STEP 2: Preview + pilih jenis dokumen -->
        <div id="pdfEmpPreview" style="display:none">
          <div class="p-3 rounded-3 mb-4" style="background:#f0f7ff;border:1px solid #b8d4ff">
            <div class="d-flex align-items-center gap-3">
              <div id="pdfEmpAvatar" style="width:44px;height:44px;font-size:16px;flex-shrink:0;background:#0B2540;color:#fff;border-radius:12px;display:flex;align-items:center;justify-content:center;font-weight:800">?</div>
              <div class="flex-grow-1">
                <div class="fw-bold text-navy" id="pdfEmpName" style="font-size:15px">-</div>
                <div class="text-muted" style="font-size:12px">
                  <span id="pdfEmpPosition">-</span> <span class="mx-1">&bull;</span> <span id="pdfEmpDept">-</span>
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
              <label class="form-label fw-semibold" style="font-size:13px">Pilih Jenis Dokumen <span class="text-danger">*</span></label>
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
        <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
        <button class="btn btn-sm text-white fw-semibold" style="background:#0B2540" id="btnExportPdf" disabled onclick="generatePdfFromModal()">
          <i class="bi bi-file-earmark-pdf me-1"></i>Unduh PDF
        </button>
      </div>
    </div>
  </div>
</div>

@php
    // Serialize employees untuk PDF modal search
    $allEmpForPdf = collect($all ?? $employees ?? [])->map(function($e) {
        if (!is_object($e)) return $e;
        return [
            'employeeId'          => $e->employeeId ?? null,
            'fullName'            => $e->fullName ?? null,
            'statusEmployee'      => $e->statusEmployee ?? null,
            'jobPosition'         => $e->jobPosition ?? null,
            'jobPositionLocation' => $e->jobPositionLocation ?? null,
            'department'          => $e->department ?? null,
            'branchName'          => $e->branchName ?? null,
            'joinDate'            => $e->joinDate ?? null,
        ];
    })->values()->all();
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
      dropdown.innerHTML = '<div class="p-3 text-muted text-center" style="font-size:13px">Tidak ada karyawan yang cocok</div>';
      dropdown.style.display = 'block';
      return;
    }

    dropdown.innerHTML = matched.map(function(emp) {
      return '<div class="p-2 border-bottom d-flex align-items-center gap-2 hover-item pdf-search-item" style="cursor:pointer;" data-emp-id="' + (emp.employeeId || '') + '">' +
        '<div class="avatar-sm" style="width:32px;height:32px;background:#0B2540;color:#fff;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700">' +
          (emp.fullName || 'E').substring(0,2).toUpperCase() +
        '</div>' +
        '<div class="flex-grow-1" style="font-size:12.5px;">' +
          '<div class="fw-semibold text-navy">' + (emp.fullName || '-') + '</div>' +
          '<div class="text-muted" style="font-size:11px">' + (emp.employeeId || '') + ' &bull; ' + (emp.jobPosition || '-') + '</div>' +
        '</div></div>';
    }).join('');
    dropdown.querySelectorAll('.pdf-search-item').forEach(function(item) {
      item.addEventListener('click', function() {
        var empId = item.getAttribute('data-emp-id');
        var found = (window.__allEmployeesForPdf || []).find(function(e) { return e.employeeId === empId; });
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
  document.getElementById('exportPdfModal').addEventListener('hidden.bs.modal', function () {
    clearPdfEmpSearch();
  });
</script>
