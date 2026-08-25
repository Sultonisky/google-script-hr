<!-- partials/EntityModals.html — OFF CONTRACT MODAL (1:1 from GAS) -->
<div class="modal fade" id="offContractModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content" style="border-radius:16px">
      <div class="modal-header" style="background:#d97706;border-radius:16px 16px 0 0">
        <div class="d-flex align-items-center gap-2">
          <i class="bi bi-calendar-x-fill text-white fs-5"></i>
          <h6 class="modal-title mb-0 text-white fw-bold">Proses Off Contract (Akhir Kontrak PKWT)</h6>
        </div>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>

      <form action="" method="POST" id="formOffContract">
        @csrf
        <div class="modal-body p-4">
          <input type="hidden" id="ocEmployeeId" name="employee_id" />

          <!-- STEP 1: Live Search karyawan kontrak -->
          <div class="mb-3">
            <label class="form-label fw-semibold" style="font-size:13px">
              <i class="bi bi-search me-1"></i>Cari Karyawan PKWT / Kontrak <span class="text-danger">*</span>
            </label>
            <div class="position-relative">
              <input type="text" class="form-control" id="ocEmpSearch"
                     placeholder="Ketik nama atau Employee ID..."
                     autocomplete="off" style="font-size:13px;padding-right:36px" oninput="handleOcEmpSearch(this.value)" />
              <i class="bi bi-x-circle-fill position-absolute" id="ocEmpSearchClear"
                 style="right:10px;top:50%;transform:translateY(-50%);cursor:pointer;color:#aaa;display:none" onclick="clearOcEmpSearch()"></i>
            </div>
            <div id="ocEmpDropdown" class="border rounded-3 mt-1 shadow-sm"
                 style="display:none;max-height:220px;overflow-y:auto;background:#fff;z-index:9999;position:relative">
            </div>
          </div>

          <!-- STEP 2: Preview karyawan terpilih + form -->
          <div id="ocEmpPreview" style="display:none">
            <div class="p-3 rounded-3 mb-4" style="background:#fef3c7;border:1px solid #fcd34d">
              <div class="d-flex align-items-center gap-3">
                <div id="ocEmpAvatar" style="width:44px;height:44px;font-size:16px;flex-shrink:0;background:#d97706;color:#fff;border-radius:12px;display:flex;align-items:center;justify-content:center;font-weight:800">?</div>
                <div class="flex-grow-1">
                  <div class="fw-bold text-navy" id="ocEmpName" style="font-size:15px">-</div>
                  <div class="text-muted" style="font-size:12px">
                    <span id="ocEmpPosition">-</span> <span class="mx-1">&bull;</span> <span id="ocEmpDept">-</span>
                  </div>
                </div>
                <div class="text-end flex-shrink-0" style="font-size:11.5px">
                  <div class="text-muted">Employee ID</div>
                  <div class="fw-semibold text-warning" id="ocEmpIdDisp">-</div>
                </div>
              </div>
            </div>

            <!-- Info kontrak -->
            <div class="p-3 rounded-3 mb-4" style="background:#fffbeb;border:1px solid #fde68a;font-size:12.5px">
              <div class="d-flex align-items-center gap-2 mb-1">
                <i class="bi bi-info-circle-fill text-warning"></i>
                <span class="fw-semibold">Tipe: <span style="color:#d97706">Contract Finished</span></span>
              </div>
              <div>Tanggal berakhir kontrak: <strong id="ocContractEndDisp">-</strong></div>
            </div>

            <!-- Form offcontract -->
            <div id="ocFormSection">
              <p class="fw-bold mb-3" style="font-size:12px;text-transform:uppercase;letter-spacing:.05em;color:#d97706">
                <i class="bi bi-info-circle me-1"></i>Detail Akhir Kontrak
              </p>
              <div class="row g-3 mb-4">
                <div class="col-12">
                  <label class="form-label fw-semibold" style="font-size:13px">Tanggal Berakhir Kontrak <span class="text-danger">*</span></label>
                  <input type="date" class="form-control form-control-sm" name="last_working_date" id="ocLastWorkingDate" required />
                  <div class="form-text" style="font-size:11px">Otomatis dari data kontrak, dapat disesuaikan</div>
                </div>
                <div class="col-12">
                  <label class="form-label fw-semibold" style="font-size:13px">Catatan <span class="text-muted fw-normal">(opsional)</span></label>
                  <textarea class="form-control form-control-sm" name="notes" id="ocNotes" rows="2" placeholder="Misal: Kontrak berakhir dan tidak diperpanjang atas pertimbangan kebutuhan tim..."></textarea>
                </div>
              </div>

              <p class="fw-bold mb-3" style="font-size:12px;text-transform:uppercase;letter-spacing:.05em;color:#d97706">
                <i class="bi bi-file-earmark-check me-1"></i>Administrasi & BPJS
              </p>
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label fw-semibold" style="font-size:13px">No. BPJS Ketenagakerjaan</label>
                  <input type="text" class="form-control form-control-sm" name="bpjs_tk" id="ocBpjsTk" placeholder="Untuk proses klaim JHT" />
                </div>
                <div class="col-md-6">
                  <label class="form-label fw-semibold" style="font-size:13px">No. BPJS Kesehatan</label>
                  <input type="text" class="form-control form-control-sm" name="bpjs_kes" id="ocBpjsKes" placeholder="Untuk proses nonaktivasi" />
                </div>
                <div class="col-12">
                  <label class="form-label fw-semibold" style="font-size:13px">Approved By</label>
                  <input type="text" class="form-control form-control-sm" name="approved_by" id="ocApprovedBy" value="Human Resources Department" />
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-sm text-white fw-semibold" style="background:#d97706" id="btnConfirmOffContract" disabled>
            <i class="bi bi-calendar-check me-1"></i>Proses Off Contract
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

@php
    // Serialize contract employees untuk modal search
    // Pakai $all dari view (semua karyawan), filter contract di JS
    $contractEmpSerialized = collect($all ?? $employees ?? [])->map(function($e) {
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
            'endDateContract'     => $e->endDateContract ?? null,
            'bpjsKetenagakerjaan' => $e->bpjsKetenagakerjaan ?? null,
            'bpjsKesehatan'       => $e->bpjsKesehatan ?? null,
        ];
    })->values()->all();
@endphp
<script>
  window.__contractEmployeesForOff = @json($contractEmpSerialized);

  function handleOcEmpSearch(query) {
    const q = (query || '').toLowerCase().trim();
    const dropdown = document.getElementById('ocEmpDropdown');
    const clearBtn = document.getElementById('ocEmpSearchClear');
    clearBtn.style.display = q ? 'block' : 'none';

    if (!q) {
      dropdown.style.display = 'none';
      return;
    }

    const matched = (window.__contractEmployeesForOff || []).filter(e => {
      const name = (e.fullName || '').toLowerCase();
      const id = (e.employeeId || '').toLowerCase();
      const pos = (e.jobPosition || '').toLowerCase();
      return name.includes(q) || id.includes(q) || pos.includes(q);
    }).slice(0, 8);

    if (matched.length === 0) {
      dropdown.innerHTML = '<div class="p-3 text-muted text-center" style="font-size:13px">Tidak ada karyawan kontrak yang cocok</div>';
      dropdown.style.display = 'block';
      return;
    }

    dropdown.innerHTML = matched.map(function(emp) {
      return '<div class="p-2 border-bottom d-flex align-items-center gap-2 hover-item oc-search-item" style="cursor:pointer;" data-emp-id="' + (emp.employeeId || '') + '">' +
        '<div class="avatar-sm" style="width:32px;height:32px;background:#d97706;color:#fff;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700">' +
          (emp.fullName || 'E').substring(0,2).toUpperCase() +
        '</div>' +
        '<div class="flex-grow-1" style="font-size:12.5px;">' +
          '<div class="fw-semibold text-navy">' + (emp.fullName || '-') + '</div>' +
          '<div class="text-muted" style="font-size:11px">' + (emp.employeeId || '') + ' &bull; ' + (emp.jobPosition || '-') + ' (End: ' + (emp.endDateContract || '-') + ')</div>' +
        '</div></div>';
    }).join('');
    // Wire via event listener — avoid Blade parsing () inside inline onclick
    dropdown.querySelectorAll('.oc-search-item').forEach(function(item) {
      item.addEventListener('click', function() {
        var empId = item.getAttribute('data-emp-id');
        var found = (window.__contractEmployeesForOff || []).find(function(e) { return e.employeeId === empId; });
        if (found) selectOcEmployee(found);
      });
    });
    dropdown.style.display = 'block';
  }

  function selectOcEmployee(emp) {
    document.getElementById('ocEmpDropdown').style.display = 'none';
    document.getElementById('ocEmpSearch').value = `${emp.fullName} (${emp.employeeId})`;
    document.getElementById('ocEmployeeId').value = emp.employeeId;

    document.getElementById('formOffContract').action = `/hr/employees/${emp.employeeId}/off-contract`;
    document.getElementById('ocEmpName').textContent = emp.fullName || '-';
    document.getElementById('ocEmpPosition').textContent = emp.jobPosition || '-';
    document.getElementById('ocEmpDept').textContent = emp.department || '-';
    document.getElementById('ocEmpIdDisp').textContent = emp.employeeId;
    document.getElementById('ocContractEndDisp').textContent = emp.endDateContract || '-';
    document.getElementById('ocLastWorkingDate').value = emp.endDateContract || '{{ date("Y-m-d") }}';
    document.getElementById('ocBpjsTk').value = emp.bpjsKetenagakerjaan || '';
    document.getElementById('ocBpjsKes').value = emp.bpjsKesehatan || '';
    document.getElementById('ocEmpAvatar').textContent = (emp.fullName || 'E').substring(0, 2).toUpperCase();

    document.getElementById('ocEmpPreview').style.display = 'block';
    document.getElementById('btnConfirmOffContract').disabled = false;
  }

  function clearOcEmpSearch() {
    document.getElementById('ocEmpSearch').value = '';
    document.getElementById('ocEmpDropdown').style.display = 'none';
    document.getElementById('ocEmpSearchClear').style.display = 'none';
    document.getElementById('ocEmpPreview').style.display = 'none';
    document.getElementById('btnConfirmOffContract').disabled = true;
  }

  // ── AJAX Submit + Auto PDF Download ──────────────────────────────────
  document.addEventListener('DOMContentLoaded', function() {
    var ocForm = document.getElementById('formOffContract');
    var ocBtn  = document.getElementById('btnConfirmOffContract');
    if (!ocForm || !ocBtn) return;

    ocForm.addEventListener('submit', function(e) {
      e.preventDefault();

      var empId = (document.getElementById('ocEmployeeId') || {}).value || '';
      var lwd   = ((document.getElementById('ocLastWorkingDate') || {}).value || '').trim();
      if (!empId || !lwd) return;

      var origHtml = ocBtn.innerHTML;
      ocBtn.disabled = true;
      ocBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Memproses...';

      var formData = new FormData(ocForm);

      fetch('/hr/employees/' + empId + '/off-contract', {
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
        ocBtn.disabled = false;
        ocBtn.innerHTML = origHtml;

        if (res && res.success) {
          if (typeof showToast === 'function') {
            showToast('Off Contract berhasil diproses.', 'success', 4000);
          } else {
            alert('Off Contract berhasil: ' + (res.message || ''));
          }

          // Auto-download PDFs via hidden iframe (non-blocking)
          var pdfUrls = res.pdf_urls || {};
          var delay = 300;
          Object.values(pdfUrls).forEach(function(url) {
            if (!url) return;
            setTimeout(function() {
              var iframe = document.createElement('iframe');
              iframe.style.cssText = 'display:none;width:0;height:0;border:0';
              iframe.src = url;
              document.body.appendChild(iframe);
              setTimeout(function() { try { document.body.removeChild(iframe); } catch(_) {} }, 15000);
            }, delay);
            delay += 1200; // stagger multiple PDFs
          });

          // Close modal + reload
          var modal = bootstrap.Modal.getInstance(document.getElementById('offContractModal'));
          if (modal) modal.hide();
          setTimeout(function() { window.location.reload(); }, 800);
        } else {
          if (typeof showToast === 'function') {
            showToast('Gagal: ' + (res ? (res.message || 'Error') : 'Tidak ada respon'), 'error');
          } else {
            alert('Gagal: ' + (res ? res.message : 'Error'));
          }
        }
      })
      .catch(function(err) {
        ocBtn.disabled = false;
        ocBtn.innerHTML = origHtml;
        if (typeof showToast === 'function') {
          showToast('Error: ' + (err ? err.message : 'Network error'), 'error');
        } else {
          alert('Error: ' + (err ? err.message : 'Network error'));
        }
      });
    });

    // Reset on modal close
    var ocModal = document.getElementById('offContractModal');
    if (ocModal) {
      ocModal.addEventListener('hidden.bs.modal', function() {
        clearOcEmpSearch();
        if (ocForm) { ocForm.reset(); ocForm.action = ''; }
      });
    }
  });
</script>
