<!-- SURAT PERINGATAN MODAL — search karyawan Contract/PKWT & Permanent/PKWTT, lalu isi detail SP -->
<div class="modal fade" id="warningLetterModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content" style="border-radius:16px">
            <div class="modal-header" style="background:#eb1c24;border-radius:16px 16px 0 0">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-exclamation-triangle-fill text-white fs-5"></i>
                    <h6 class="modal-title mb-0 text-white fw-bold">Generate Surat Peringatan</h6>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            {{-- Form membungkus body + footer: harus flex column agar modal-dialog-scrollable tetap menggulir body --}}
            <form id="formWarningLetter" class="d-flex flex-column" style="min-height:0;flex:1 1 auto;overflow:hidden" novalidate>
                <div class="modal-body p-4">
                    <input type="hidden" id="wlEmployeeId" />

                    <!-- STEP 1: Search karyawan -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold" style="font-size:13px" for="wlEmpSearch">
                            <i class="bi bi-search me-1"></i>Cari Karyawan Contract / Permanent <span class="text-danger">*</span>
                        </label>
                        <div class="position-relative">
                            <input type="text" class="form-control" id="wlEmpSearch"
                                placeholder="Ketik nama, Employee ID, atau jabatan..." autocomplete="off"
                                style="font-size:13px;padding-right:36px" />
                            <i class="bi bi-x-circle-fill position-absolute" id="wlEmpSearchClear"
                                style="right:10px;top:50%;transform:translateY(-50%);cursor:pointer;color:#aaa;display:none"></i>
                        </div>
                        <div id="wlEmpDropdown" class="border rounded-3 mt-1 shadow-sm"
                            style="display:none;max-height:220px;overflow-y:auto;z-index:9999;position:relative"></div>
                        <div class="form-text" style="font-size:11px">Hanya karyawan berstatus Contract/PKWT dan Permanent/PKWTT.</div>
                    </div>

                    <!-- STEP 2: Preview + form -->
                    <div id="wlEmpPreview" style="display:none">
                        <div class="p-3 rounded-3 mb-3" style="border:1px solid #eb1c24">
                            <div class="d-flex align-items-center gap-3">
                                <div id="wlEmpAvatar"
                                        style="width:44px;height:44px;font-size:16px;flex-shrink:0;background:#eb1c24;color:#fff;border-radius:12px;display:flex;align-items:center;justify-content:center;font-weight:800">
                                    ?</div>
                                <div class="flex-grow-1">
                                    <div class="fw-bold text-primary" id="wlEmpName" style="font-size:15px">-</div>
                                    <div class="text-muted" style="font-size:12px">
                                        <span id="wlEmpPosition">-</span> <span class="mx-1">&bull;</span>
                                        <span id="wlEmpDept">-</span> <span class="mx-1">&bull;</span>
                                        <span id="wlEmpStatus">-</span>
                                    </div>
                                    <div class="text-muted" style="font-size:11.5px" id="wlEmpBranch">-</div>
                                </div>
                                <div class="text-end flex-shrink-0" style="font-size:11.5px">
                                    <div class="text-muted">Employee ID</div>
                                    <div class="fw-semibold text-primary" id="wlEmpIdDisp">-</div>
                                </div>
                            </div>
                        </div>

                        <div id="wlLastSp" class="alert alert-warning py-2 px-3 mb-3" style="display:none;font-size:12.5px">
                            <i class="bi bi-clock-history me-1"></i><span id="wlLastSpText"></span>
                        </div>

                        <div id="wlErrors" class="alert alert-danger py-2 px-3 mb-3" style="display:none;font-size:12.5px"></div>

                        <p class="fw-bold mb-3" style="font-size:12px;text-transform:uppercase;letter-spacing:.05em;color:#eb1c24">
                            <i class="bi bi-file-earmark-text me-1"></i>Detail Surat
                        </p>
                        <div class="row g-3 mb-4">
                            <div class="col-md-4">
                                <label class="form-label fw-semibold" style="font-size:13px" for="wlLevel">Tingkat SP <span class="text-danger">*</span></label>
                                <select class="form-select form-select-sm" name="level" id="wlLevel" required>
                                    @foreach (\App\Enums\WarningLetterLevel::cases() as $wlLevel)
                                        <option value="{{ $wlLevel->value }}">{{ $wlLevel->label() }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold" style="font-size:13px" for="wlDocDate">Tanggal Surat <span class="text-danger">*</span></label>
                                <input type="date" class="form-control form-control-sm" name="doc_date" id="wlDocDate"
                                    value="{{ now()->timezone('Asia/Jakarta')->format('Y-m-d') }}" required />
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold" style="font-size:13px" for="wlValidity">Masa Berlaku <span class="text-danger">*</span></label>
                                <select class="form-select form-select-sm" name="validity_months" id="wlValidity" required>
                                    @for ($m = \App\Services\WarningLetterService::MAX_VALIDITY_MONTHS; $m >= 1; $m--)
                                        <option value="{{ $m }}">{{ $m }} bulan</option>
                                    @endfor
                                </select>
                                <div class="form-text" style="font-size:11px">Berlaku s.d. <strong id="wlValidUntil">-</strong></div>
                            </div>
                        </div>

                        <p class="fw-bold mb-3" style="font-size:12px;text-transform:uppercase;letter-spacing:.05em;color:#eb1c24">
                            <i class="bi bi-exclamation-octagon me-1"></i>Pelanggaran
                        </p>
                        <div class="row g-3 mb-4">
                            <div class="col-md-7">
                                <label class="form-label fw-semibold" style="font-size:13px" for="wlCategory">Kategori Pelanggaran <span class="text-danger">*</span></label>
                                <select class="form-select form-select-sm" name="violation_category" id="wlCategory" required>
                                    <option value="">— Pilih kategori —</option>
                                    @foreach (\App\Services\WarningLetterService::VIOLATION_CATEGORIES as $wlCategory)
                                        <option value="{{ $wlCategory }}">{{ $wlCategory }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-5">
                                <label class="form-label fw-semibold" style="font-size:13px" for="wlIncidentDate">Tanggal Kejadian <span class="text-muted fw-normal">(opsional)</span></label>
                                <input type="date" class="form-control form-control-sm" name="incident_date" id="wlIncidentDate" />
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-semibold" style="font-size:13px" for="wlDescription">Uraian Pelanggaran <span class="text-danger">*</span></label>
                                <textarea class="form-control form-control-sm" name="violation_description" id="wlDescription" rows="4"
                                    maxlength="2000" required
                                    placeholder="Jelaskan kronologi pelanggaran secara faktual: apa yang terjadi, kapan, di mana, dan dampaknya."></textarea>
                                <div class="form-text text-end" style="font-size:11px"><span id="wlDescCount">0</span> / 2000</div>
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-semibold" style="font-size:13px" for="wlRegulation">Dasar Ketentuan <span class="text-muted fw-normal">(opsional)</span></label>
                                <input type="text" class="form-control form-control-sm" name="regulation_reference" id="wlRegulation" maxlength="255"
                                    placeholder="Misal: Peraturan Perusahaan Bab V Pasal 12 tentang Kehadiran" />
                                <div class="form-text" style="font-size:11px">Kosongkan untuk memakai "Peraturan Perusahaan serta tata tertib dan prosedur kerja yang berlaku".</div>
                            </div>
                        </div>

                        <p class="fw-bold mb-3" style="font-size:12px;text-transform:uppercase;letter-spacing:.05em;color:#eb1c24">
                            <i class="bi bi-tools me-1"></i>Tindakan Perbaikan
                        </p>
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label fw-semibold" style="font-size:13px" for="wlCorrective">Tindakan perbaikan yang diharapkan <span class="text-muted fw-normal">(opsional, satu per baris)</span></label>
                                <textarea class="form-control form-control-sm" name="corrective_actions" id="wlCorrective" rows="3" maxlength="1500"
                                    placeholder="Misal:&#10;Hadir tepat waktu sesuai jadwal kerja&#10;Melapor ke atasan langsung apabila berhalangan hadir"></textarea>
                                <div class="form-text" style="font-size:11px">Poin standar (mematuhi peraturan, tidak mengulangi pelanggaran, perbaikan kinerja) selalu dicantumkan.</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-sm text-white fw-semibold" style="background:#eb1c24"
                        id="btnGenerateWarningLetter" disabled>
                        <i class="bi bi-file-earmark-pdf me-1"></i>Buat Surat Peringatan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@php
    $__wlLatest = app(\App\Services\WarningLetterService::class)->latestByEmployee();
    $__wlEmployees = collect($all ?? [])
        ->filter(fn ($e) => \App\Services\WarningLetterService::isEligible($e))
        ->map(fn ($e) => [
            'employeeId' => $e->employeeId ?? null,
            'fullName' => $e->fullName ?? null,
            'statusEmployee' => $e->statusEmployee ?? null,
            'jobPosition' => $e->jobPosition ?? null,
            'department' => $e->department ?? null,
            'branchName' => $e->branchName ?? null,
            'lastSp' => $__wlLatest[$e->employeeId ?? ''] ?? null,
        ])
        ->values()
        ->all();
    $__wlStoreUrl = route('hr.employees.warning-letter', ['id' => '__ID__']);
    $__wlLevelLabels = collect(\App\Enums\WarningLetterLevel::cases())
        ->mapWithKeys(fn ($l) => [$l->value => $l->shortLabel()])
        ->all();
@endphp
<script>
    (function() {
        var employees = @json($__wlEmployees);
        var storeUrlTemplate = @json($__wlStoreUrl);
        var levelLabels = @json($__wlLevelLabels);
        var nextLevel = { SP1: 'SP2', SP2: 'SP3', SP3: 'SP3' };
        var bulanId = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

        function el(id) { return document.getElementById(id); }

        function escapeHtml(value) {
            return String(value == null ? '' : value).replace(/[&<>"']/g, function(c) {
                return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
            });
        }

        function parseYmd(value) {
            var p = String(value || '').slice(0, 10).split('-');
            if (p.length !== 3) return null;
            var d = new Date(Number(p[0]), Number(p[1]) - 1, Number(p[2]));
            return isNaN(d.getTime()) ? null : d;
        }

        function fmtId(d) {
            return d ? d.getDate() + ' ' + bulanId[d.getMonth()] + ' ' + d.getFullYear() : '-';
        }

        function addMonthsNoOverflow(d, months) {
            var target = new Date(d.getFullYear(), d.getMonth() + months, 1);
            var lastDay = new Date(target.getFullYear(), target.getMonth() + 1, 0).getDate();
            target.setDate(Math.min(d.getDate(), lastDay));
            return target;
        }

        function refreshValidUntil() {
            var d = parseYmd(el('wlDocDate').value);
            var months = parseInt(el('wlValidity').value, 10) || 6;
            if (!d) { el('wlValidUntil').textContent = '-'; return; }
            var until = addMonthsNoOverflow(d, months);
            until.setDate(until.getDate() - 1);
            el('wlValidUntil').textContent = fmtId(until);
        }

        function renderDropdown(query) {
            var q = (query || '').toLowerCase().trim();
            var dropdown = el('wlEmpDropdown');
            el('wlEmpSearchClear').style.display = q ? 'block' : 'none';
            if (!q) { dropdown.style.display = 'none'; return; }

            var matched = employees.filter(function(e) {
                return (e.fullName || '').toLowerCase().includes(q)
                    || (e.employeeId || '').toLowerCase().includes(q)
                    || (e.jobPosition || '').toLowerCase().includes(q);
            }).slice(0, 8);

            if (matched.length === 0) {
                dropdown.innerHTML = '<div class="p-3 text-muted text-center" style="font-size:13px">Tidak ada karyawan Contract/Permanent yang cocok</div>';
                dropdown.style.display = 'block';
                return;
            }

            dropdown.innerHTML = matched.map(function(e) {
                return '<div class="p-2 border-bottom d-flex align-items-center gap-2 hover-item wl-search-item" style="cursor:pointer" data-emp-id="' + escapeHtml(e.employeeId) + '">' +
                    '<div style="width:32px;height:32px;background:#7c2d12;color:#fff;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700">' +
                    escapeHtml((e.fullName || 'E').substring(0, 2).toUpperCase()) + '</div>' +
                    '<div class="flex-grow-1" style="font-size:12.5px">' +
                    '<div class="fw-semibold text-primary">' + escapeHtml(e.fullName || '-') + '</div>' +
                    '<div class="text-muted" style="font-size:11px">' + escapeHtml(e.employeeId || '') + ' &bull; ' +
                    escapeHtml(e.jobPosition || '-') + ' &bull; ' + escapeHtml(e.statusEmployee || '-') +
                    (e.lastSp ? ' &bull; <span class="text-danger">' + escapeHtml(levelLabels[e.lastSp.level] || e.lastSp.level) + '</span>' : '') +
                    '</div></div></div>';
            }).join('');

            dropdown.querySelectorAll('.wl-search-item').forEach(function(item) {
                item.addEventListener('click', function() {
                    var empId = item.getAttribute('data-emp-id');
                    var found = employees.find(function(e) { return e.employeeId === empId; });
                    if (found) selectEmployee(found);
                });
            });
            dropdown.style.display = 'block';
        }

        function selectEmployee(emp) {
            el('wlEmpDropdown').style.display = 'none';
            el('wlEmpSearch').value = emp.fullName + ' (' + emp.employeeId + ')';
            el('wlEmployeeId').value = emp.employeeId;
            el('wlEmpName').textContent = emp.fullName || '-';
            el('wlEmpPosition').textContent = emp.jobPosition || '-';
            el('wlEmpDept').textContent = emp.department || '-';
            el('wlEmpStatus').textContent = emp.statusEmployee || '-';
            el('wlEmpBranch').textContent = emp.branchName || '-';
            el('wlEmpIdDisp').textContent = emp.employeeId;
            el('wlEmpAvatar').textContent = (emp.fullName || 'E').substring(0, 2).toUpperCase();

            var lastSp = emp.lastSp;
            var level = 'SP1';
            if (lastSp && lastSp.level) {
                var issued = parseYmd(lastSp.issued_at);
                var stillActive = issued && addMonthsNoOverflow(issued, {{ \App\Services\WarningLetterService::MAX_VALIDITY_MONTHS }}) > new Date();
                el('wlLastSpText').textContent = 'SP terakhir: ' + (levelLabels[lastSp.level] || lastSp.level) +
                    (lastSp.nomor ? ' — ' + lastSp.nomor : '') + ' (' + fmtId(issued) + ')' +
                    (stillActive ? '. Tingkat berikutnya dipilih otomatis, sesuaikan bila perlu.' : '.');
                el('wlLastSp').style.display = 'block';
                if (stillActive && nextLevel[lastSp.level]) level = nextLevel[lastSp.level];
            } else {
                el('wlLastSp').style.display = 'none';
            }
            el('wlLevel').value = level;

            refreshValidUntil();
            el('wlErrors').style.display = 'none';
            el('wlEmpPreview').style.display = 'block';
            el('btnGenerateWarningLetter').disabled = false;
        }

        function resetModal() {
            var form = el('formWarningLetter');
            if (form) form.reset();
            el('wlEmployeeId').value = '';
            el('wlEmpSearch').value = '';
            el('wlEmpDropdown').style.display = 'none';
            el('wlEmpSearchClear').style.display = 'none';
            el('wlEmpPreview').style.display = 'none';
            el('wlErrors').style.display = 'none';
            el('wlDescCount').textContent = '0';
            el('btnGenerateWarningLetter').disabled = true;
        }

        function showErrors(messages) {
            var box = el('wlErrors');
            box.innerHTML = messages.map(function(m) { return '<div>' + escapeHtml(m) + '</div>'; }).join('');
            box.style.display = 'block';
            box.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }

        function downloadPdf(url) {
            var iframe = document.createElement('iframe');
            iframe.style.cssText = 'display:none;width:0;height:0;border:0';
            iframe.src = url;
            document.body.appendChild(iframe);
            setTimeout(function() { try { document.body.removeChild(iframe); } catch (_) {} }, 15000);
        }

        document.addEventListener('DOMContentLoaded', function() {
            var form = el('formWarningLetter');
            var btn = el('btnGenerateWarningLetter');
            if (!form || !btn) return;
            var submitting = false;

            el('wlEmpSearch').addEventListener('input', function(e) { renderDropdown(e.target.value); });
            el('wlEmpSearchClear').addEventListener('click', resetModal);
            el('wlDocDate').addEventListener('change', refreshValidUntil);
            el('wlValidity').addEventListener('change', refreshValidUntil);
            el('wlDescription').addEventListener('input', function(e) { el('wlDescCount').textContent = e.target.value.length; });
            el('warningLetterModal').addEventListener('hidden.bs.modal', resetModal);

            form.addEventListener('submit', function(e) {
                e.preventDefault();
                if (submitting) return;

                var empId = el('wlEmployeeId').value;
                var missing = [];
                if (!empId) missing.push('Pilih karyawan terlebih dahulu.');
                if (!el('wlCategory').value) missing.push('Kategori pelanggaran wajib dipilih.');
                if (el('wlDescription').value.trim().length < 10) missing.push('Uraian pelanggaran minimal 10 karakter.');
                if (!el('wlDocDate').value) missing.push('Tanggal surat wajib diisi.');
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
                            showToast(res.data.message || 'Surat Peringatan berhasil diterbitkan.', 'success', 5000);
                            var modal = bootstrap.Modal.getInstance(el('warningLetterModal'));
                            if (modal) modal.hide();
                            return;
                        }

                        var errors = res.data && res.data.errors
                            ? Object.values(res.data.errors).flat()
                            : [(res.data && res.data.message) || 'Gagal menerbitkan Surat Peringatan.'];
                        showErrors(errors);
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
