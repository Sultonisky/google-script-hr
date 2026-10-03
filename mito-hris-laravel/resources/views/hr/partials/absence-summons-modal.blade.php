<div class="modal fade" id="absenceSummonsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content" style="border-radius:16px">
            <div class="modal-header" style="background:#7c2d12;border-radius:16px 16px 0 0">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-person-exclamation text-white fs-5"></i>
                    <h6 class="modal-title mb-0 text-white fw-bold">Generate Surat Penggilan Mangkir</h6>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <form id="formAbsenceSummons" class="d-flex flex-column" style="min-height:0;flex:1 1 auto;overflow:hidden" novalidate>
                <div class="modal-body p-4">
                    <input type="hidden" id="asEmployeeId" />
                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="asEmpSearch">Cari Karyawan Contract / Permanent <span class="text-danger">*</span></label>
                        <div class="position-relative">
                            <input type="text" class="form-control" id="asEmpSearch"
                                placeholder="Ketik nama, Employee ID, atau jabatan..." autocomplete="off" />
                            <i class="bi bi-x-circle-fill position-absolute" id="asEmpSearchClear"
                                style="right:10px;top:50%;transform:translateY(-50%);cursor:pointer;color:#aaa;display:none"></i>
                        </div>
                        <div id="asEmpDropdown" class="border rounded-3 mt-1 shadow-sm"
                            style="display:none;max-height:220px;overflow-y:auto;z-index:9999;position:relative"></div>
                        <div class="form-text">Hanya karyawan berstatus Contract/PKWT dan Permanent/PKWTT.</div>
                    </div>

                    <div id="asEmpPreview" style="display:none">
                        <div class="p-3 rounded-3 mb-3" style="border:1px solid #7c2d12">
                            <div class="fw-bold text-primary" id="asEmpName">-</div>
                            <div class="text-muted" style="font-size:12px">
                                <span id="asEmpPosition">-</span> &bull; <span id="asEmpDept">-</span> &bull;
                                <span id="asEmpStatus">-</span>
                            </div>
                            <div class="text-muted" style="font-size:12px"><span id="asEmpBranch">-</span> &bull; Employee ID: <span id="asEmpIdDisp">-</span></div>
                        </div>

                        <div id="asErrors" class="alert alert-danger py-2 px-3 mb-3" style="display:none;font-size:12.5px"></div>
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label fw-semibold" for="asLevel">Jenis Panggilan <span class="text-danger">*</span></label>
                                <select class="form-select form-select-sm" name="level" id="asLevel" required>
                                    @foreach (\App\Enums\AbsenceSummonsLevel::cases() as $summonsLevel)
                                        <option value="{{ $summonsLevel->value }}">{{ $summonsLevel->label() }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12" id="asWorkingDaysGroup" style="display:none">
                                <label class="form-label fw-semibold" for="asWorkingDays">Jumlah hari kerja mangkir <span class="text-danger">*</span></label>
                                <input type="number" class="form-control form-control-sm" name="working_days" id="asWorkingDays"
                                    min="1" max="365" step="1" />
                                <div class="form-text">Isi sesuai jumlah hari kerja aktual berdasarkan absensi.</div>
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-semibold" for="asCompanyEntity">Entitas Perusahaan <span class="text-danger">*</span></label>
                                <select class="form-select form-select-sm" name="company_entity" id="asCompanyEntity" required>
                                    <option value="">Pilih entitas perusahaan</option>
                                    @foreach (config('hris.mpr.companies', []) as $entityCode => $entity)
                                        <option value="{{ $entityCode }}">{{ $entity['name'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold" for="asDocDate">Tanggal Surat <span class="text-danger">*</span></label>
                                <input type="date" lang="id-ID" class="form-control form-control-sm" name="doc_date" id="asDocDate"
                                    value="{{ now()->timezone('Asia/Jakarta')->format('Y-m-d') }}" required />
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold" for="asAbsenceStart">Mangkir dari tanggal <span class="text-danger">*</span></label>
                                <input type="date" lang="id-ID" class="form-control form-control-sm" name="absence_start_date" id="asAbsenceStart" required />
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold" for="asAbsenceEnd">Sampai tanggal <span class="text-danger">*</span></label>
                                <input type="date" lang="id-ID" class="form-control form-control-sm" name="absence_end_date" id="asAbsenceEnd" required />
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold" for="asAbsenceSecondStart">Mangkir lagi dari tanggal <span class="text-muted fw-normal">(opsional)</span></label>
                                <input type="date" lang="id-ID" class="form-control form-control-sm" name="absence_second_start_date" id="asAbsenceSecondStart" />
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold" for="asAbsenceSecondEnd">Sampai tanggal <span class="text-muted fw-normal">(opsional)</span></label>
                                <input type="date" lang="id-ID" class="form-control form-control-sm" name="absence_second_end_date" id="asAbsenceSecondEnd" />
                            </div>
                            <div class="col-12">
                                <hr class="my-1">
                                <p class="fw-bold mb-0" style="font-size:12px;text-transform:uppercase;letter-spacing:.05em;color:#7c2d12">
                                    Jadwal Panggilan Kerja
                                </p>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold" for="asMeetingDate">Hari/Tanggal <span class="text-danger">*</span></label>
                                <input type="date" lang="id-ID" class="form-control form-control-sm" name="meeting_date" id="asMeetingDate" required />
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold" for="asMeetingTime">Waktu <span class="text-danger">*</span></label>
                                <input type="time" class="form-control form-control-sm" name="meeting_time" id="asMeetingTime" required />
                            </div>
                            <div class="col-md-7">
                                <label class="form-label fw-semibold" for="asMeetingLocation">Tempat <span class="text-danger">*</span></label>
                                <textarea class="form-control form-control-sm" name="meeting_location" id="asMeetingLocation"
                                    rows="2" maxlength="1000" required></textarea>
                            </div>
                            <div class="col-md-5">
                                <label class="form-label fw-semibold" for="asMeetingAgenda">Agenda <span class="text-danger">*</span></label>
                                <input type="text" class="form-control form-control-sm" name="meeting_agenda" id="asMeetingAgenda"
                                    maxlength="255" required />
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-sm text-white fw-semibold" style="background:#7c2d12"
                        id="btnGenerateAbsenceSummons" disabled>
                        <i class="bi bi-file-earmark-pdf me-1"></i>Buat Surat Penggilan Mangkir
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@php
    $__asEmployees = collect($all ?? [])
        ->filter(fn ($e) => \App\Services\WarningLetterService::isEligible($e))
        ->map(fn ($e) => [
            'employeeId' => $e->employeeId ?? null,
            'fullName' => $e->fullName ?? null,
            'statusEmployee' => $e->statusEmployee ?? null,
            'jobPosition' => $e->jobPosition ?? null,
            'department' => $e->department ?? null,
            'branchName' => $e->branchName ?? null,
        ])
        ->values()
        ->all();
    $__asStoreUrl = route('hr.employees.absence-summons', ['id' => '__ID__']);
@endphp
<script>
    (function() {
        var employees = @json($__asEmployees);
        var storeUrlTemplate = @json($__asStoreUrl);
        function el(id) { return document.getElementById(id); }
        function escapeHtml(value) {
            return String(value == null ? '' : value).replace(/[&<>"']/g, function(c) {
                return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
            });
        }
        function showErrors(messages) {
            var box = el('asErrors');
            box.innerHTML = messages.map(function(m) { return '<div>' + escapeHtml(m) + '</div>'; }).join('');
            box.style.display = 'block';
        }
        function updateWorkingDaysField() {
            var isSecondSummons = el('asLevel').value === 'SPM2';
            el('asWorkingDaysGroup').style.display = isSecondSummons ? 'block' : 'none';
            el('asWorkingDays').required = isSecondSummons;
            if (!isSecondSummons) el('asWorkingDays').value = '';
        }
        function selectEmployee(emp) {
            el('asEmpDropdown').style.display = 'none';
            el('asEmpSearch').value = emp.fullName + ' (' + emp.employeeId + ')';
            el('asEmployeeId').value = emp.employeeId;
            el('asEmpName').textContent = emp.fullName || '-';
            el('asEmpPosition').textContent = emp.jobPosition || '-';
            el('asEmpDept').textContent = emp.department || '-';
            el('asEmpStatus').textContent = emp.statusEmployee || '-';
            el('asEmpBranch').textContent = emp.branchName || '-';
            el('asEmpIdDisp').textContent = emp.employeeId || '-';
            el('asEmpPreview').style.display = 'block';
            el('asErrors').style.display = 'none';
            updateWorkingDaysField();
            el('btnGenerateAbsenceSummons').disabled = false;
        }
        function renderDropdown(query) {
            var q = (query || '').toLowerCase().trim();
            var dropdown = el('asEmpDropdown');
            el('asEmpSearchClear').style.display = q ? 'block' : 'none';
            if (!q) { dropdown.style.display = 'none'; return; }
            var matched = employees.filter(function(e) {
                return (e.fullName || '').toLowerCase().includes(q)
                    || (e.employeeId || '').toLowerCase().includes(q)
                    || (e.jobPosition || '').toLowerCase().includes(q);
            }).slice(0, 8);
            if (!matched.length) {
                dropdown.innerHTML = '<div class="p-3 text-muted text-center">Tidak ada karyawan Contract/Permanent yang cocok</div>';
            } else {
                dropdown.innerHTML = matched.map(function(e) {
                    return '<button type="button" class="w-100 text-start p-2 border-0 border-bottom bg-white as-search-item" data-emp-id="' +
                        escapeHtml(e.employeeId) + '"><span class="fw-semibold text-primary">' + escapeHtml(e.fullName || '-') +
                        '</span><br><small class="text-muted">' + escapeHtml(e.employeeId || '') + ' &bull; ' +
                        escapeHtml(e.jobPosition || '-') + ' &bull; ' + escapeHtml(e.statusEmployee || '-') + '</small></button>';
                }).join('');
                dropdown.querySelectorAll('.as-search-item').forEach(function(item) {
                    item.addEventListener('click', function() {
                        var emp = employees.find(function(e) { return e.employeeId === item.getAttribute('data-emp-id'); });
                        if (emp) selectEmployee(emp);
                    });
                });
            }
            dropdown.style.display = 'block';
        }
        function resetModal() {
            el('formAbsenceSummons').reset();
            el('asEmployeeId').value = '';
            el('asEmpDropdown').style.display = 'none';
            el('asEmpSearchClear').style.display = 'none';
            el('asEmpPreview').style.display = 'none';
            el('asErrors').style.display = 'none';
            updateWorkingDaysField();
            el('btnGenerateAbsenceSummons').disabled = true;
        }
        function downloadPdf(url) {
            var iframe = document.createElement('iframe');
            iframe.style.cssText = 'display:none;width:0;height:0;border:0';
            iframe.src = url;
            document.body.appendChild(iframe);
            setTimeout(function() { iframe.remove(); }, 15000);
        }
        document.addEventListener('DOMContentLoaded', function() {
            var form = el('formAbsenceSummons');
            var btn = el('btnGenerateAbsenceSummons');
            if (!form || !btn) return;
            var submitting = false;
            el('asEmpSearch').addEventListener('input', function(e) { renderDropdown(e.target.value); });
            el('asLevel').addEventListener('change', updateWorkingDaysField);
            updateWorkingDaysField();
            el('asEmpSearchClear').addEventListener('click', resetModal);
            el('absenceSummonsModal').addEventListener('hidden.bs.modal', resetModal);
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                if (submitting) return;
                var empId = el('asEmployeeId').value;
                var missing = [];
                if (!empId) missing.push('Pilih karyawan terlebih dahulu.');
                if (!el('asLevel').value) missing.push('Pilih jenis panggilan.');
                if (el('asLevel').value === 'SPM2' && !el('asWorkingDays').value) missing.push('Isi jumlah hari kerja mangkir aktual.');
                if (!el('asCompanyEntity').value) missing.push('Pilih entitas perusahaan.');
                if (!el('asDocDate').value) missing.push('Tanggal surat wajib diisi.');
                if (!el('asAbsenceStart').value || !el('asAbsenceEnd').value) missing.push('Periode mangkir wajib diisi.');
                if (missing.length) { showErrors(missing); return; }
                submitting = true;
                var origHtml = btn.innerHTML;
                btn.disabled = true;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Memproses...';
                fetch(storeUrlTemplate.replace('__ID__', encodeURIComponent(empId)), {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') || {}).content || ''
                    },
                    body: new FormData(form)
                })
                    .then(function(r) { return r.json().then(function(data) { return { ok: r.ok, data: data }; }); })
                    .then(function(res) {
                        submitting = false;
                        btn.disabled = false;
                        btn.innerHTML = origHtml;
                        if (res.ok && res.data.success) {
                            if (res.data.pdf_url) downloadPdf(res.data.pdf_url);
                            showToast(res.data.message || 'Surat Penggilan Mangkir berhasil diterbitkan.', 'success', 5000);
                            var modal = bootstrap.Modal.getInstance(el('absenceSummonsModal'));
                            if (modal) modal.hide();
                            return;
                        }
                        showErrors(res.data && res.data.errors ? Object.values(res.data.errors).flat() :
                            [(res.data && res.data.message) || 'Gagal menerbitkan Surat Penggilan Mangkir.']);
                    })
                    .catch(function(err) {
                        submitting = false;
                        btn.disabled = false;
                        btn.innerHTML = origHtml;
                        showErrors(['Error: ' + (err && err.message ? err.message : 'Network error')]);
                    });
            });
        });
    })();
</script>
