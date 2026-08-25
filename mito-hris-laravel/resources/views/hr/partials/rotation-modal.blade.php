<!-- partials/RotationModal.html — MODAL ROTASI KARYAWAN (1:1 from GAS) -->
<div class="modal fade" id="rotationModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content" style="border-radius:16px">
      <div class="modal-header" style="background:#0B2540;border-radius:16px 16px 0 0">
        <div class="d-flex align-items-center gap-2">
          <i class="bi bi-arrow-left-right text-white fs-5"></i>
          <h6 class="modal-title mb-0 text-white fw-bold">Proses Rotasi / Mutasi Karyawan</h6>
        </div>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>

      <form action="" method="POST" id="formRotation">
        @csrf
        <div class="modal-body p-4">
          <input type="hidden" id="rotEmployeeId" name="employee_id" />
          {{-- Snapshot old data — WAJIB dikirim ke controller agar PDF mendapat jabatan semula yg benar --}}
          <input type="hidden" id="rotOldJobPosition" name="old_job_position" />
          <input type="hidden" id="rotOldDepartment"  name="old_department" />
          <input type="hidden" id="rotOldBranch"      name="old_branch_name" />

          <!-- STEP 1: Live Search karyawan -->
          <div class="mb-3">
            <label class="form-label fw-semibold" style="font-size:13px">
              <i class="bi bi-search me-1"></i>Cari Karyawan Aktif <span class="text-danger">*</span>
            </label>
            <div class="position-relative">
              <input type="text" class="form-control" id="rotEmpSearch"
                     placeholder="Ketik nama atau Employee ID..."
                     autocomplete="off" style="font-size:13px;padding-right:36px" oninput="handleRotEmpSearch(this.value)" />
              <i class="bi bi-x-circle-fill position-absolute" id="rotEmpSearchClear"
                 style="right:10px;top:50%;transform:translateY(-50%);cursor:pointer;color:#aaa;display:none" onclick="clearRotEmpSearch()"></i>
            </div>
            <div id="rotEmpDropdown" class="border rounded-3 mt-1 shadow-sm"
                 style="display:none;max-height:220px;overflow-y:auto;background:#fff;z-index:9999;position:relative">
            </div>
          </div>

          <!-- STEP 2: Preview karyawan terpilih + form -->
          <div id="rotEmpPreview" style="display:none">
            <div class="p-3 rounded-3 mb-4" style="background:#f0f7ff;border:1px solid #b8d4ff">
              <div class="d-flex align-items-center gap-3">
                <div id="rotEmpAvatar" style="width:44px;height:44px;font-size:16px;flex-shrink:0;background:#0B2540;color:#fff;border-radius:12px;display:flex;align-items:center;justify-content:center;font-weight:800">?</div>
                <div class="flex-grow-1">
                  <div class="fw-bold text-navy" id="rotEmpName" style="font-size:15px">-</div>
                  <div class="text-muted" style="font-size:12px">
                    <span id="rotEmpPosition">-</span> <span class="mx-1">&bull;</span> <span id="rotEmpDept">-</span>
                  </div>
                </div>
                <div class="text-end flex-shrink-0" style="font-size:11.5px">
                  <div class="text-muted">Employee ID</div>
                  <div class="fw-semibold text-primary" id="rotEmpIdDisp">-</div>
                  <div class="text-muted mt-1">Join Date</div>
                  <div class="fw-semibold" id="rotEmpJoinDate">-</div>
                </div>
              </div>
            </div>

            <!-- Form rotasi -->
            <div id="rotFormSection">
              <p class="fw-bold mb-3" style="font-size:12px;text-transform:uppercase;letter-spacing:0.05em;color:#0B2540">
                <i class="bi bi-pencil-square me-1"></i>Detail Rotasi
              </p>
              <div class="row g-3 mb-3">
                <div class="col-md-6">
                  <label class="form-label fw-semibold" style="font-size:13px">Tipe Rotasi <span class="text-danger">*</span></label>
                  <select class="form-select form-select-sm" name="rotation_type" id="rotType" required>
                    <option value="">— Pilih —</option>
                    <option value="Promosi">Promosi</option>
                    <option value="Demosi">Demosi</option>
                    <option value="Mutasi">Mutasi</option>
                  </select>
                </div>
                <div class="col-md-6">
                  <label class="form-label fw-semibold" style="font-size:13px">Tanggal Efektif <span class="text-danger">*</span></label>
                  <input type="date" class="form-control form-control-sm" name="effective_date" id="rotEffectiveDate" value="{{ date('Y-m-d') }}" required />
                </div>
                <div class="col-md-6">
                  <label class="form-label fw-semibold" style="font-size:13px">Jabatan Baru <span class="text-danger">*</span></label>
                  <input type="text" class="form-control form-control-sm" name="new_job_position" id="rotNewPosition" placeholder="Contoh: HR Manager (Jakarta)" required />
                </div>
                <div class="col-md-6">
                  <label class="form-label fw-semibold" style="font-size:13px">Departemen Baru <span class="text-danger">*</span></label>
                  <input type="text" class="form-control form-control-sm" name="new_department" id="rotNewDepartment" placeholder="Contoh: Operations / Sales" required />
                </div>
                <div class="col-md-6">
                  <label class="form-label fw-semibold" style="font-size:13px">Cabang Baru</label>
                  <select class="form-select form-select-sm" name="new_branch_name" id="rotNewBranch">
                    <option value="">— Pilih Cabang —</option>
                    @php
                      $branchOptions = ($all ?? collect())->pluck('branchName')->filter()->unique()->sort()->values();
                    @endphp
                    @foreach($branchOptions as $bOpt)
                      <option value="{{ $bOpt }}">{{ $bOpt }}</option>
                    @endforeach
                  </select>
                </div>
                <div class="col-md-12">
                  <label class="form-label fw-semibold" style="font-size:13px">Alasan / Catatan Rotasi</label>
                  <textarea class="form-control form-control-sm" name="notes" id="rotReason" rows="2" placeholder="Contoh: Penyesuaian struktur organisasi..."></textarea>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-sm text-white fw-semibold" style="background:#0B2540" id="btnConfirmRotation" disabled>
            <i class="bi bi-check2-circle me-1"></i>Proses Rotasi
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
  @php
    $allEmpForRotation = collect($all ?? $employees ?? [])->map(function($e) {
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
  window.__allEmployeesForRotation = @json($allEmpForRotation);
  // Alias so employee modals that use window.__allEmployees also work from this page
  if (typeof window.__allEmployees === 'undefined' || !Array.isArray(window.__allEmployees) || !window.__allEmployees.length) {
    window.__allEmployees = window.__allEmployeesForRotation;
  }

  // ==========================================================================
  // AUTO-DOWNLOAD SK ROTASI (FALLBACK: dari session flash setelah page reload)
  // ==========================================================================
  @if(session('auto_download_sk_rotation'))
    (function() {
      var empId = @json(session('auto_download_sk_rotation'));
      var query = @json(session('auto_download_sk_rotation_query', ''));
      var url = "{{ route('hr.export.sk-rotation', '') }}" + "/" + empId;
      if (query) url += "?" + query;
      setTimeout(function() { window.location.href = url; }, 400);
    })();
  @endif

  function _showRotToast(msg, type) {
    type = type || 'success';
    var cls = type === 'success' ? 'alert-success' : (type === 'error' ? 'alert-danger' : 'alert-warning');
    var icon = type === 'success' ? 'bi-check-circle-fill' : (type === 'error' ? 'bi-x-circle-fill' : 'bi-exclamation-triangle-fill');
    var toast = document.createElement('div');
    toast.className = 'toast align-items-center text-white border-0 show fade ' + cls;
    toast.style.cssText = 'position:fixed;top:20px;right:20px;z-index:99999;min-width:320px;padding:12px 16px;border-radius:12px;box-shadow:0 10px 25px rgba(0,0,0,0.15);';
    toast.innerHTML = '<div class="d-flex align-items-center gap-2"><i class="bi ' + icon + ' fs-5"></i><div class="fw-medium">' + msg + '</div></div>';
    document.body.appendChild(toast);
    setTimeout(function() {
      toast.style.opacity = '0';
      toast.style.transition = 'opacity 0.4s ease';
      setTimeout(function() { toast.remove(); }, 500);
    }, 4500);
  }

  function handleRotEmpSearch(query) {
    const q = (query || '').toLowerCase().trim();
    const dropdown = document.getElementById('rotEmpDropdown');
    const clearBtn = document.getElementById('rotEmpSearchClear');
    clearBtn.style.display = q ? 'block' : 'none';

    if (!q) {
      dropdown.style.display = 'none';
      return;
    }

    const matched = (window.__allEmployeesForRotation || []).filter(e => {
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
      return '<div class="p-2 border-bottom d-flex align-items-center gap-2 hover-item rot-search-item" style="cursor:pointer;" data-emp-id="' + (emp.employeeId || '') + '">' +
        '<div class="avatar-sm" style="width:32px;height:32px;background:#0B2540;color:#fff;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700">' +
          (emp.fullName || 'E').substring(0,2).toUpperCase() +
        '</div>' +
        '<div class="flex-grow-1" style="font-size:12.5px;">' +
          '<div class="fw-semibold text-navy">' + (emp.fullName || '-') + '</div>' +
          '<div class="text-muted" style="font-size:11px">' + (emp.employeeId || '') + ' &bull; ' + (emp.jobPosition || '-') + '</div>' +
        '</div></div>';
    }).join('');
    // Wire via event listener — avoid Blade parsing () inside inline onclick
    dropdown.querySelectorAll('.rot-search-item').forEach(function(item) {
      item.addEventListener('click', function() {
        var empId = item.getAttribute('data-emp-id');
        var found = (window.__allEmployeesForRotation || []).find(function(e) { return e.employeeId === empId; });
        if (found) selectRotEmployee(found);
      });
    });
    dropdown.style.display = 'block';
  }

  function selectRotEmployee(emp) {
    document.getElementById('rotEmpDropdown').style.display = 'none';
    document.getElementById('rotEmpSearch').value = `${emp.fullName} (${emp.employeeId})`;
    document.getElementById('rotEmployeeId').value = emp.employeeId;

    // ── Snapshot old data SEBELUM rotasi (1:1 GAS: old_job_position = jabatan sebelum update) ──
    document.getElementById('rotOldJobPosition').value = emp.jobPosition || emp.jobPositionLocation || '';
    document.getElementById('rotOldDepartment').value  = emp.department || '';
    document.getElementById('rotOldBranch').value      = emp.branchName || '';

    document.getElementById('formRotation').action = `/hr/employees/${emp.employeeId}/rotate`;
    document.getElementById('rotEmpName').textContent = emp.fullName || '-';
    document.getElementById('rotEmpPosition').textContent = emp.jobPosition || '-';
    document.getElementById('rotEmpDept').textContent = emp.department || '-';
    document.getElementById('rotEmpIdDisp').textContent = emp.employeeId;
    document.getElementById('rotEmpJoinDate').textContent = emp.joinDate || '-';
    document.getElementById('rotEmpAvatar').textContent = (emp.fullName || 'E').substring(0, 2).toUpperCase();

    document.getElementById('rotNewPosition').value = emp.jobPosition || '';
    document.getElementById('rotNewDepartment').value = emp.department || '';
    // Set select branch — cari option yang match, fallback ke value langsung
    var branchSel = document.getElementById('rotNewBranch');
    if (branchSel) {
      var found = false;
      for (var i = 0; i < branchSel.options.length; i++) {
        if (branchSel.options[i].value === (emp.branchName || '')) {
          branchSel.selectedIndex = i;
          found = true;
          break;
        }
      }
      if (!found) branchSel.selectedIndex = 0;
    }

    document.getElementById('rotEmpPreview').style.display = 'block';
    document.getElementById('btnConfirmRotation').disabled = false;
  }

  function clearRotEmpSearch() {
    document.getElementById('rotEmpSearch').value = '';
    document.getElementById('rotEmpDropdown').style.display = 'none';
    document.getElementById('rotEmpSearchClear').style.display = 'none';
    document.getElementById('rotEmpPreview').style.display = 'none';
    document.getElementById('btnConfirmRotation').disabled = true;
    // Clear snapshots
    document.getElementById('rotOldJobPosition').value = '';
    document.getElementById('rotOldDepartment').value  = '';
    document.getElementById('rotOldBranch').value      = '';
  }

  // ==========================================================================
  // AJAX SUBMIT + AUTO GENERATE PDF SK ROTASI PADA SUCCESS
  // ==========================================================================
  document.addEventListener('DOMContentLoaded', function() {
    var form = document.getElementById('formRotation');
    var btn = document.getElementById('btnConfirmRotation');
    if (!form || !btn) return;

    var origBtnHtml = btn.innerHTML;

    form.addEventListener('submit', function(e) {
      e.preventDefault();
      if (!form.action || !form.checkValidity()) return;

      btn.disabled = true;
      btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Memproses...';

      var formData = new FormData(form);
      var payload = {};
      // sk_number tidak boleh dikirim dari frontend — Nomor SK di-generate server-side
      formData.forEach(function(v, k) { if (k !== 'sk_number') { payload[k] = v; } });

      fetch(form.action, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          'Accept': 'application/json',
          'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') || {}).content || (form.querySelector('input[name="_token"]') || {}).value || ''
        },
        body: JSON.stringify(payload)
      })
      .then(function(r) {
        if (!r.ok) return r.text().then(function(t) { try { return JSON.parse(t); } catch(_) { return {success:false, message: t || 'HTTP ' + r.status}; } });
        return r.json();
      })
      .then(function(res) {
        btn.disabled = false;
        btn.innerHTML = origBtnHtml;

        if (!res || !res.success) {
          _showRotToast('Gagal: ' + (res ? (res.message || 'Error tidak diketahui') : 'Tidak ada respon'), 'error');
          return;
        }

        _showRotToast(res.message || 'Rotasi berhasil diproses.', 'success');

        // -- Auto download PDF SK Rotasi --
        var downloadUrl = res.download_url || res.downloadUrl;
        if (downloadUrl) {
          var iframe = document.createElement('iframe');
          iframe.style.display = 'none';
          iframe.style.width = '0';
          iframe.style.height = '0';
          iframe.style.border = '0';
          iframe.src = downloadUrl;
          document.body.appendChild(iframe);
          setTimeout(function() { try { document.body.removeChild(iframe); } catch(_) {} }, 15000);
        }

        // -- Tutup modal & reload data --
        try {
          var modalEl = document.getElementById('rotationModal');
          if (modalEl && bootstrap && bootstrap.Modal) {
            var m = bootstrap.Modal.getInstance(modalEl);
            if (m) m.hide();
          }
        } catch(_) {}

        setTimeout(function() {
          if (typeof clearRotEmpSearch === 'function') clearRotEmpSearch();
          window.location.reload();
        }, 800);
      })
      .catch(function(err) {
        btn.disabled = false;
        btn.innerHTML = origBtnHtml;
        _showRotToast('Error: ' + (err ? (err.message || String(err)) : 'Network error'), 'error');
      });
    });
  });
</script>
