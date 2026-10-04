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

                        <div id="asHistory" class="alert alert-warning py-2 px-3 mb-3" style="display:none;font-size:12.5px">
                            <i class="bi bi-clock-history me-1"></i><span id="asHistoryText"></span>
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
                                <div class="form-text">Hari kerja mangkir sejak tanggal mulai sampai dengan tanggal surat, sesuai absensi aktual.</div>
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-semibold" for="asCompanyEntity">Entitas Perusahaan</label>
                                <input type="text" class="form-control form-control-sm bg-light" id="asCompanyEntity" readonly tabindex="-1" />
                                <div class="form-text">Otomatis dari branch karyawan yang dipilih (kop surat &amp; nomor surat).</div>
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-semibold" for="asDocDate">Tanggal Surat <span class="text-danger">*</span></label>
                                <input type="date" lang="id-ID" class="form-control form-control-sm" name="doc_date" id="asDocDate"
                                    value="{{ now()->timezone('Asia/Jakarta')->format('Y-m-d') }}" required />
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold" for="asAbsenceStart"><span id="asAbsenceStartLabel">Mangkir dari tanggal</span> <span class="text-danger">*</span></label>
                                <input type="date" lang="id-ID" class="form-control form-control-sm" name="absence_start_date" id="asAbsenceStart" required />
                                <div class="form-text" id="asAbsenceStartHint" style="display:none">Dihitung sampai dengan tanggal surat.</div>
                            </div>
                            <div class="col-md-6 as-first-only">
                                <label class="form-label fw-semibold" for="asAbsenceEnd">Sampai tanggal <span class="text-danger">*</span></label>
                                <input type="date" lang="id-ID" class="form-control form-control-sm" name="absence_end_date" id="asAbsenceEnd" required />
                            </div>
                            <div class="col-md-6 as-first-only">
                                <label class="form-label fw-semibold" for="asAbsenceSecondStart">Mangkir lagi dari tanggal <span class="text-muted fw-normal">(opsional)</span></label>
                                <input type="date" lang="id-ID" class="form-control form-control-sm" name="absence_second_start_date" id="asAbsenceSecondStart" />
                            </div>
                            <div class="col-md-6 as-first-only">
                                <label class="form-label fw-semibold" for="asAbsenceSecondEnd">Sampai tanggal <span class="text-muted fw-normal">(opsional)</span></label>
                                <input type="date" lang="id-ID" class="form-control form-control-sm" name="absence_second_end_date" id="asAbsenceSecondEnd" />
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-semibold mb-1">File Lampiran <span class="text-muted fw-normal">(opsional, maks. 5 gambar)</span></label>
                                <div id="asAttachmentRows" class="d-flex flex-column gap-2"></div>
                                <button type="button" class="btn btn-outline-secondary btn-sm mt-2" id="btnAddAsAttachment">
                                    <i class="bi bi-paperclip me-1"></i>Tambah Lampiran
                                </button>
                                <div class="form-text">
                                    Foto/scan rekap absensi, resi pengiriman, dll. (JPG, PNG, WebP, maks. 5 MB per file). Gambar ditambahkan
                                    sebagai halaman lampiran di PDF dan jumlah lampiran di surat dihitung otomatis (tanpa file = "-").
                                </div>
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
                            <div class="col-12" id="asMeetingLocationGroup">
                                <label class="form-label fw-semibold" for="asMeetingLocation">Tempat <span class="text-danger">*</span></label>
                                <textarea class="form-control form-control-sm" name="meeting_location" id="asMeetingLocation"
                                    rows="2" maxlength="1000" required></textarea>
                            </div>
                            <div class="col-12 as-first-only">
                                <label class="form-label fw-semibold" for="asMeetingAgenda">Agenda <span class="text-danger">*</span></label>
                                <input type="text" class="form-control form-control-sm" name="meeting_agenda" id="asMeetingAgenda"
                                    maxlength="255" value="Klarifikasi Ketidakhadiran/Mangkir" required />
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-outline-secondary btn-sm fw-semibold" id="btnPreviewAbsenceSummons" disabled
                        title="Lihat draft PDF tanpa menerbitkan nomor surat" style="color:#7c2d12;border-color:#7c2d12">
                        <i class="bi bi-eye me-1"></i>Preview PDF
                    </button>
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
    $__asSkNumbers = app(\App\Services\SkNumberService::class);
    $__asHistory = app(\App\Services\AbsenceSummonsService::class)->historyByEmployee();
    $__asEmployees = collect($all ?? [])
        ->filter(fn ($e) => \App\Services\WarningLetterService::isEligible($e))
        ->map(fn ($e) => [
            'employeeId' => $e->employeeId ?? null,
            'fullName' => $e->fullName ?? null,
            'statusEmployee' => $e->statusEmployee ?? null,
            'jobPosition' => $e->jobPosition ?? null,
            'department' => $e->department ?? null,
            'branchName' => $e->branchName ?? null,
            'companyEntity' => config('hris.mpr.companies.'.$__asSkNumbers->resolveEntityCode((string) ($e->branchName ?? '')).'.name'),
            'summons' => $__asHistory[$e->employeeId ?? ''] ?? null,
        ])
        ->values()
        ->all();
    $__asStoreUrl = route('hr.employees.absence-summons', ['id' => '__ID__']);
    $__asPreviewUrl = route('hr.employees.absence-summons.preview', ['id' => '__ID__']);
    $__asLevelLabels = collect(\App\Enums\AbsenceSummonsLevel::cases())->mapWithKeys(fn ($level) => [$level->value => $level->label()]);
@endphp
<script>
    (function() {
        var employees = @json($__asEmployees);
        var levelLabels = @json($__asLevelLabels);
        var FOLLOW_UP_DAYS = 30;
        var MONTHS_ID = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
        var currentEmployee = null;
        var storeUrlTemplate = @json($__asStoreUrl);
        var previewUrlTemplate = @json($__asPreviewUrl);
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
            document.querySelectorAll('#absenceSummonsModal .as-first-only').forEach(function(group) {
                group.style.display = isSecondSummons ? 'none' : '';
                group.querySelectorAll('input, textarea').forEach(function(input) { input.disabled = isSecondSummons; });
            });
            el('asAbsenceStartLabel').textContent = isSecondSummons ? 'Mangkir sejak tanggal' : 'Mangkir dari tanggal';
            el('asAbsenceStartHint').style.display = isSecondSummons ? 'block' : 'none';
        }
        var MAX_ATTACHMENTS = 5;
        var MAX_ATTACHMENT_BYTES = 5 * 1024 * 1024;
        var ATTACHMENT_TYPES = ['image/jpeg', 'image/png', 'image/webp'];
        function attachmentRows() {
            return Array.prototype.slice.call(el('asAttachmentRows').querySelectorAll('.as-attachment-row'));
        }
        function updateAttachmentButton() {
            el('btnAddAsAttachment').disabled = attachmentRows().length >= MAX_ATTACHMENTS;
        }
        function addAttachmentRow() {
            if (attachmentRows().length >= MAX_ATTACHMENTS) return;
            var row = document.createElement('div');
            row.className = 'input-group input-group-sm as-attachment-row';
            var file = document.createElement('input');
            file.type = 'file';
            file.name = 'attachments[]';
            file.accept = ATTACHMENT_TYPES.join(',');
            file.className = 'form-control';
            var label = document.createElement('input');
            label.type = 'text';
            label.name = 'attachment_labels[]';
            label.maxLength = 100;
            label.className = 'form-control';
            label.placeholder = 'Keterangan, mis. Rekap Absensi';
            var remove = document.createElement('button');
            remove.type = 'button';
            remove.className = 'btn btn-outline-danger';
            remove.title = 'Hapus lampiran';
            remove.innerHTML = '<i class="bi bi-trash"></i>';
            remove.addEventListener('click', function() { row.remove(); updateAttachmentButton(); });
            row.appendChild(file);
            row.appendChild(label);
            row.appendChild(remove);
            el('asAttachmentRows').appendChild(row);
            updateAttachmentButton();
        }
        function attachmentErrors() {
            var errors = [];
            attachmentRows().forEach(function(row, index) {
                var input = row.querySelector('input[type="file"]');
                var position = index + 1;
                if (!input.files || !input.files.length) {
                    errors.push('Pilih file untuk lampiran ' + position + ' atau hapus barisnya.');
                    return;
                }
                var file = input.files[0];
                if (ATTACHMENT_TYPES.indexOf(file.type) === -1) errors.push('File lampiran ' + position + ' harus berupa gambar JPG, PNG, atau WebP.');
                else if (file.size > MAX_ATTACHMENT_BYTES) errors.push('File lampiran ' + position + ' maksimal 5 MB.');
            });
            return errors;
        }
        function parseYmd(value) {
            var m = /^(\d{4})-(\d{2})-(\d{2})/.exec(String(value || ''));
            return m ? new Date(Number(m[1]), Number(m[2]) - 1, Number(m[3])) : null;
        }
        function fmtId(date) {
            return date ? date.getDate() + ' ' + MONTHS_ID[date.getMonth()] + ' ' + date.getFullYear() : '-';
        }
        function prefillFirstSummonsStart() {
            var first = currentEmployee && currentEmployee.summons && currentEmployee.summons.first;
            if (el('asLevel').value === 'SPM2' && first && first.absence_start_date) {
                el('asAbsenceStart').value = first.absence_start_date;
            }
        }
        function applySummonsHistory(emp) {
            var history = emp.summons;
            var box = el('asHistory');
            var level = 'SPM1';
            box.className = 'alert alert-warning py-2 px-3 mb-3';
            if (!history || !history.latest) {
                box.style.display = 'none';
            } else {
                var latest = history.latest;
                var issued = parseYmd(latest.issued_at);
                var text = 'Panggilan terakhir: ' + (levelLabels[latest.level] || latest.level) +
                    (latest.nomor ? ' — ' + latest.nomor : '') + ' (' + fmtId(issued) + ').';
                var daysSince = issued ? Math.floor((Date.now() - issued.getTime()) / 86400000) : null;
                if (latest.level === 'SPM1' && daysSince !== null && daysSince <= FOLLOW_UP_DAYS) {
                    level = 'SPM2';
                    text += ' Panggilan Kerja II dipilih otomatis dan tanggal mulai mangkir diisi dari Panggilan Kerja I, sesuaikan bila perlu.';
                } else if (latest.level === 'SPM2') {
                    box.className = 'alert alert-danger py-2 px-3 mb-3';
                    text += ' Panggilan Kerja II (Terakhir) sudah diterbitkan. Pastikan ini kasus mangkir baru sebelum membuat panggilan lagi.';
                } else {
                    text += ' Sudah lebih dari ' + FOLLOW_UP_DAYS + ' hari, dianggap kasus mangkir baru (Panggilan Kerja I).';
                }
                el('asHistoryText').textContent = text;
                box.style.display = 'block';
            }
            el('asLevel').value = level;
            updateWorkingDaysField();
            prefillFirstSummonsStart();
        }
        function selectEmployee(emp) {
            currentEmployee = emp;
            el('asEmpDropdown').style.display = 'none';
            el('asEmpSearch').value = emp.fullName + ' (' + emp.employeeId + ')';
            el('asEmployeeId').value = emp.employeeId;
            el('asEmpName').textContent = emp.fullName || '-';
            el('asEmpPosition').textContent = emp.jobPosition || '-';
            el('asEmpDept').textContent = emp.department || '-';
            el('asEmpStatus').textContent = emp.statusEmployee || '-';
            el('asEmpBranch').textContent = emp.branchName || '-';
            el('asEmpIdDisp').textContent = emp.employeeId || '-';
            el('asCompanyEntity').value = emp.companyEntity || '-';
            el('asEmpPreview').style.display = 'block';
            el('asErrors').style.display = 'none';
            applySummonsHistory(emp);
            el('btnGenerateAbsenceSummons').disabled = false;
            el('btnPreviewAbsenceSummons').disabled = false;
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
                        escapeHtml(e.jobPosition || '-') + ' &bull; ' + escapeHtml(e.statusEmployee || '-') +
                        (e.summons && e.summons.latest ? ' &bull; <span class="text-danger">' +
                            escapeHtml(levelLabels[e.summons.latest.level] || e.summons.latest.level) + '</span>' : '') +
                        '</small></button>';
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
            el('asAttachmentRows').innerHTML = '';
            updateAttachmentButton();
            el('asHistory').style.display = 'none';
            currentEmployee = null;
            updateWorkingDaysField();
            el('btnGenerateAbsenceSummons').disabled = true;
            el('btnPreviewAbsenceSummons').disabled = true;
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
            el('asLevel').addEventListener('change', function() {
                updateWorkingDaysField();
                if (!el('asAbsenceStart').value) prefillFirstSummonsStart();
            });
            updateWorkingDaysField();
            el('asEmpSearchClear').addEventListener('click', resetModal);
            el('absenceSummonsModal').addEventListener('hidden.bs.modal', resetModal);
            el('btnAddAsAttachment').addEventListener('click', addAttachmentRow);

            function requestHeaders() {
                return {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') || {}).content || ''
                };
            }
            function responseErrors(data, fallback) {
                return data && data.errors ? Object.values(data.errors).flat() : [(data && data.message) || fallback];
            }
            function prepareSubmission() {
                var missing = [];
                var isSecondSummons = el('asLevel').value === 'SPM2';
                var secondStart = el('asAbsenceSecondStart').value;
                var secondEnd = el('asAbsenceSecondEnd').value;
                if (!el('asEmployeeId').value) missing.push('Pilih karyawan terlebih dahulu.');
                if (!el('asLevel').value) missing.push('Pilih jenis panggilan.');
                if (isSecondSummons && !el('asWorkingDays').value) missing.push('Isi jumlah hari kerja mangkir aktual.');
                if (!el('asDocDate').value) missing.push('Tanggal surat wajib diisi.');
                if (isSecondSummons) {
                    if (!el('asAbsenceStart').value) missing.push('Tanggal mulai mangkir wajib diisi.');
                    else if (el('asDocDate').value && el('asAbsenceStart').value > el('asDocDate').value) missing.push('Tanggal mulai mangkir tidak boleh setelah tanggal surat.');
                } else {
                    if (!el('asAbsenceStart').value || !el('asAbsenceEnd').value) missing.push('Periode mangkir wajib diisi.');
                    else if (el('asAbsenceEnd').value < el('asAbsenceStart').value) missing.push('Tanggal akhir mangkir tidak boleh sebelum tanggal awal mangkir.');
                    if (!!secondStart !== !!secondEnd) missing.push('Periode mangkir kedua harus diisi lengkap (dari dan sampai tanggal).');
                    else if (secondStart && secondEnd < secondStart) missing.push('Tanggal akhir periode mangkir kedua tidak boleh sebelum tanggal awal.');
                }
                if (!el('asMeetingDate').value) missing.push('Tanggal panggilan wajib diisi.');
                else if (el('asDocDate').value && el('asMeetingDate').value < el('asDocDate').value) missing.push('Tanggal panggilan tidak boleh sebelum tanggal surat.');
                if (!el('asMeetingTime').value) missing.push('Waktu panggilan wajib diisi.');
                if (!el('asMeetingLocation').value.trim()) missing.push('Tempat panggilan wajib diisi.');
                if (!isSecondSummons && !el('asMeetingAgenda').value.trim()) missing.push('Agenda panggilan wajib diisi.');
                missing = missing.concat(attachmentErrors());
                if (missing.length) { showErrors(missing); return false; }
                return true;
            }

            el('btnPreviewAbsenceSummons').addEventListener('click', function() {
                if (submitting || !prepareSubmission()) return;
                var previewBtn = el('btnPreviewAbsenceSummons');
                // Jendela dibuka saat klik agar tidak diblokir popup blocker; URL diisi setelah draft siap.
                var previewWin = window.open('', '_blank');
                var origHtml = previewBtn.innerHTML;
                submitting = true;
                previewBtn.disabled = true;
                previewBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Menyiapkan...';
                fetch(previewUrlTemplate.replace('__ID__', encodeURIComponent(el('asEmployeeId').value)), {
                    method: 'POST',
                    headers: requestHeaders(),
                    body: new FormData(form)
                })
                    .then(function(r) { return r.json().then(function(data) { return { ok: r.ok, data: data }; }); })
                    .then(function(res) {
                        if (res.ok && res.data.success && res.data.preview_url) {
                            el('asErrors').style.display = 'none';
                            if (previewWin) previewWin.location.href = res.data.preview_url;
                            else window.location.assign(res.data.preview_url);
                            return;
                        }
                        if (previewWin) previewWin.close();
                        showErrors(responseErrors(res.data, 'Gagal membuat pratinjau Surat Penggilan Mangkir.'));
                    })
                    .catch(function(err) {
                        if (previewWin) previewWin.close();
                        showErrors(['Error: ' + (err && err.message ? err.message : 'Network error')]);
                    })
                    .then(function() {
                        submitting = false;
                        previewBtn.disabled = false;
                        previewBtn.innerHTML = origHtml;
                    });
            });

            form.addEventListener('submit', function(e) {
                e.preventDefault();
                if (submitting || !prepareSubmission()) return;
                var empId = el('asEmployeeId').value;
                submitting = true;
                var origHtml = btn.innerHTML;
                btn.disabled = true;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Memproses...';
                fetch(storeUrlTemplate.replace('__ID__', encodeURIComponent(empId)), {
                    method: 'POST',
                    headers: requestHeaders(),
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
                        showErrors(responseErrors(res.data, 'Gagal menerbitkan Surat Penggilan Mangkir.'));
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
