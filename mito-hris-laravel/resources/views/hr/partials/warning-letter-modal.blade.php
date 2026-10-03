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
                                    <div class="text-muted" style="font-size:11.5px">Lokasi kerja: <span id="wlEmpLocation">-</span></div>
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
                                <label class="form-label fw-semibold" style="font-size:13px" for="wlValidity">Masa Berlaku</label>
                                <input type="text" class="form-control form-control-sm bg-light" id="wlValidity" value="6 bulan" readonly tabindex="-1" />
                                <div class="form-text" style="font-size:11px">Berlaku s.d. <strong id="wlValidUntil">-</strong></div>
                            </div>
                        </div>

                        <p class="fw-bold mb-3" style="font-size:12px;text-transform:uppercase;letter-spacing:.05em;color:#eb1c24">
                            <i class="bi bi-exclamation-octagon me-1"></i>Pelanggaran
                        </p>
                        <div class="row g-3 mb-4">
                            <div class="col-md-7" id="wlCategoryField">
                                <label class="form-label fw-semibold" style="font-size:13px" for="wlCategory">Kategori Pelanggaran <span class="text-danger">*</span></label>
                                <select class="form-select form-select-sm" name="violation_category" id="wlCategory" required>
                                    <option value="">— Pilih kategori —</option>
                                    @foreach (\App\Services\WarningLetterService::VIOLATION_CATEGORIES as $wlCategory)
                                        <option value="{{ $wlCategory }}">{{ $wlCategory }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-5" id="wlIncidentDateField">
                                <label class="form-label fw-semibold" style="font-size:13px" for="wlIncidentDate">Tanggal Kejadian <span class="text-muted fw-normal">(opsional)</span></label>
                                <input type="date" class="form-control form-control-sm" name="incident_date" id="wlIncidentDate" />
                            </div>
                            <div class="col-12" id="wlDescriptionField">
                                <label class="form-label fw-semibold" style="font-size:13px" for="wlDescription">Uraian Pelanggaran <span class="text-danger">*</span></label>
                                <textarea class="form-control form-control-sm" name="violation_description" id="wlDescription" rows="4"
                                    maxlength="2000" required
                                    placeholder="Jelaskan kronologi pelanggaran secara faktual: apa yang terjadi, kapan, di mana, dan dampaknya."></textarea>
                                <div class="form-text text-end" style="font-size:11px"><span id="wlDescCount">0</span> / 2000</div>
                            </div>
                            <div class="col-12" id="wlRegulationField">
                                <div id="wlStructuredRegulation">
                                    <label class="form-label fw-semibold" style="font-size:13px">
                                        Jenis Peraturan
                                        <span class="text-danger" id="wlRegulationRequired" style="display:none">*</span>
                                        <span class="text-muted fw-normal" id="wlRegulationOptional">(opsional)</span>
                                    </label>
                                    <div id="wlRegulationRows">
                                        <div class="border rounded-3 p-3 mb-2 wl-regulation-row">
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <span class="small fw-semibold wl-regulation-row-title">Dasar ketentuan 1</span>
                                                <button type="button" class="btn btn-sm btn-outline-danger wl-remove-regulation" aria-label="Hapus dasar ketentuan" style="display:none">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </div>
                                            <div class="row g-2">
                                                <div class="col-12">
                                                    <label class="form-label mb-1" style="font-size:12px">Jenis peraturan</label>
                                                    <select class="form-select form-select-sm" data-regulation-field="regulation_type" name="regulation_references[0][regulation_type]">
                                                        <option value="">— Pilih jenis peraturan —</option>
                                                        @foreach (\App\Services\WarningLetterService::REGULATION_TYPES as $regulationType)
                                                            <option value="{{ $regulationType }}">{{ $regulationType }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div class="col-sm-4">
                                                    <label class="form-label mb-1" style="font-size:12px">Pasal</label>
                                                    <input type="number" class="form-control form-control-sm" data-regulation-field="article_number"
                                                        name="regulation_references[0][article_number]" min="1" max="9999" step="1"
                                                        inputmode="numeric" placeholder="Contoh: 46" />
                                                </div>
                                                <div class="col-sm-4">
                                                    <label class="form-label mb-1" style="font-size:12px">Ayat <span class="text-muted fw-normal">(opsional)</span></label>
                                                    <input type="number" class="form-control form-control-sm" data-regulation-field="paragraph_number"
                                                        name="regulation_references[0][paragraph_number]" min="1" max="999" step="1"
                                                        inputmode="numeric" placeholder="Contoh: 1" />
                                                </div>
                                                <div class="col-sm-4">
                                                    <label class="form-label mb-1" style="font-size:12px">Huruf <span class="text-muted fw-normal">(opsional)</span></label>
                                                    <input type="text" class="form-control form-control-sm text-lowercase" data-regulation-field="article_letter"
                                                        name="regulation_references[0][article_letter]" maxlength="1" pattern="[A-Za-z]" placeholder="Contoh: e" />
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <template id="wlRegulationRowTemplate">
                                        <div class="border rounded-3 p-3 mb-2 wl-regulation-row">
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <span class="small fw-semibold wl-regulation-row-title"></span>
                                                <button type="button" class="btn btn-sm btn-outline-danger wl-remove-regulation" aria-label="Hapus dasar ketentuan">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </div>
                                            <div class="row g-2">
                                                <div class="col-12">
                                                    <label class="form-label mb-1" style="font-size:12px">Jenis peraturan</label>
                                                    <select class="form-select form-select-sm" data-regulation-field="regulation_type">
                                                        <option value="">— Pilih jenis peraturan —</option>
                                                        @foreach (\App\Services\WarningLetterService::REGULATION_TYPES as $regulationType)
                                                            <option value="{{ $regulationType }}">{{ $regulationType }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div class="col-sm-4">
                                                    <label class="form-label mb-1" style="font-size:12px">Pasal</label>
                                                    <input type="number" class="form-control form-control-sm" data-regulation-field="article_number"
                                                        min="1" max="9999" step="1" inputmode="numeric" placeholder="Contoh: 46" />
                                                </div>
                                                <div class="col-sm-4">
                                                    <label class="form-label mb-1" style="font-size:12px">Ayat <span class="text-muted fw-normal">(opsional)</span></label>
                                                    <input type="number" class="form-control form-control-sm" data-regulation-field="paragraph_number"
                                                        min="1" max="999" step="1" inputmode="numeric" placeholder="Contoh: 1" />
                                                </div>
                                                <div class="col-sm-4">
                                                    <label class="form-label mb-1" style="font-size:12px">Huruf <span class="text-muted fw-normal">(opsional)</span></label>
                                                    <input type="text" class="form-control form-control-sm text-lowercase" data-regulation-field="article_letter"
                                                        maxlength="1" pattern="[A-Za-z]" placeholder="Contoh: e" />
                                                </div>
                                            </div>
                                        </div>
                                    </template>
                                    <div class="d-flex justify-content-between align-items-center gap-2">
                                        <div class="form-text" id="wlRegulationHelp" style="font-size:11px">SP-2/SP-3: dasar ketentuan opsional. Jika diisi, jenis peraturan dan nomor pasal wajib dilengkapi.</div>
                                        <button type="button" class="btn btn-sm btn-outline-primary text-nowrap" id="wlAddRegulation">
                                            <i class="bi bi-plus-lg me-1"></i>Tambah pasal
                                        </button>
                                    </div>
                                    <ol class="small mt-2 mb-0 ps-3 text-muted" id="wlRegulationPreview" aria-live="polite"></ol>
                                </div>
                            </div>
                            <div class="col-md-6" id="wlSuperiorPositionField">
                                <label class="form-label fw-semibold" style="font-size:13px" for="wlSuperiorPosition">Jabatan Atasan <span class="text-danger">*</span></label>
                                <input type="text" class="form-control form-control-sm" name="superior_position" id="wlSuperiorPosition"
                                    maxlength="150" value="Atasan Langsung"
                                    placeholder="Misal: Branch Manager Lampung" />
                                <div class="form-text" style="font-size:11px">Nama atasan diambil dari data karyawan.</div>
                            </div>
                        </div>

                        <div id="wlCorrectiveSection">
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
            'jobPositionLocation' => $e->jobPositionLocation ?? null,
            'lokasiKerja' => $e->lokasiKerja ?? null,
            'areaKerja' => $e->areaKerja ?? null,
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
    $__wlLevelMeta = collect(\App\Enums\WarningLetterLevel::cases())
        ->mapWithKeys(fn ($l) => [$l->value => [
            'months' => $l->validityMonths(),
            'next' => ($l->next() ?? $l)->value,
            'first' => $l->usesFirstLetterTemplate(),
        ]])
        ->all();
@endphp
<script>
    (function() {
        var employees = @json($__wlEmployees);
        var storeUrlTemplate = @json($__wlStoreUrl);
        var levelLabels = @json($__wlLevelLabels);
        var levelMeta = @json($__wlLevelMeta);
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

        function levelMonths(level) {
            return (levelMeta[level] && levelMeta[level].months) || 12;
        }

        function usesFirstTemplate(level) {
            return !!(levelMeta[level] && levelMeta[level].first);
        }

        function refreshValidUntil() {
            var d = parseYmd(el('wlDocDate').value);
            var months = levelMonths(el('wlLevel').value);
            el('wlValidity').value = months % 12 === 0 ? (months / 12) + ' tahun' : months + ' bulan';
            if (!d) { el('wlValidUntil').textContent = '-'; return; }
            var until = addMonthsNoOverflow(d, months);
            until.setDate(until.getDate() - 1);
            el('wlValidUntil').textContent = fmtId(until);
        }

        function updateLevelFields() {
            var isSp1 = usesFirstTemplate(el('wlLevel').value);
            el('wlCorrectiveSection').style.display = isSp1 ? 'none' : '';
            el('wlCategoryField').style.display = isSp1 ? 'none' : '';
            el('wlIncidentDateField').style.display = isSp1 ? 'none' : '';
            el('wlCategory').required = !isSp1;
            el('wlStructuredRegulation').style.display = '';
            regulationRows().forEach(function(row) {
                row.querySelector('[data-regulation-field="regulation_type"]').required = isSp1;
                row.querySelector('[data-regulation-field="article_number"]').required = isSp1;
            });
            el('wlRegulationRequired').style.display = isSp1 ? '' : 'none';
            el('wlRegulationOptional').style.display = isSp1 ? 'none' : '';
            el('wlRegulationHelp').textContent = isSp1
                ? 'Tambahkan satu atau lebih dasar ketentuan. Jenis peraturan dan nomor pasal wajib diisi pada setiap baris.'
                : 'SP-2/SP-3: dasar ketentuan opsional. Jika diisi, jenis peraturan dan nomor pasal wajib dilengkapi.';
            refreshRegulationPreview();
            el('wlSuperiorPositionField').style.display = isSp1 ? '' : 'none';
            el('wlSuperiorPosition').required = isSp1;
        }

        function refreshRegulationPreview() {
            var preview = el('wlRegulationPreview');
            preview.innerHTML = '';
            regulationRows().forEach(function(row) {
                var article = row.querySelector('[data-regulation-field="article_number"]').value.trim();
                var paragraph = row.querySelector('[data-regulation-field="paragraph_number"]').value.trim();
                var letter = row.querySelector('[data-regulation-field="article_letter"]').value.trim().toLowerCase();
                var type = row.querySelector('[data-regulation-field="regulation_type"]').value;
                var parts = [];
                if (article) parts.push('Pasal ' + article);
                if (paragraph) parts.push('ayat (' + paragraph + ')');
                if (letter) parts.push('huruf ' + letter);
                if (type) parts.push(type);
                if (!parts.length) return;
                var item = document.createElement('li');
                item.textContent = parts.join(' ');
                preview.appendChild(item);
            });
        }

        function regulationRows() {
            return Array.prototype.slice.call(el('wlRegulationRows').querySelectorAll('.wl-regulation-row'));
        }

        function updateRegulationRows() {
            var rows = regulationRows();
            rows.forEach(function(row, index) {
                row.querySelector('.wl-regulation-row-title').textContent = 'Dasar ketentuan ' + (index + 1);
                var remove = row.querySelector('.wl-remove-regulation');
                remove.style.display = rows.length > 1 ? '' : 'none';
                row.querySelectorAll('[data-regulation-field]').forEach(function(input) {
                    input.name = 'regulation_references[' + index + '][' + input.getAttribute('data-regulation-field') + ']';
                });
            });
            el('wlAddRegulation').disabled = rows.length >= 10;
            refreshRegulationPreview();
        }

        function resetRegulationRows() {
            var rows = regulationRows();
            rows.slice(1).forEach(function(row) { row.remove(); });
            regulationRows()[0].querySelectorAll('[data-regulation-field]').forEach(function(input) {
                input.value = '';
            });
            updateRegulationRows();
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
            el('wlEmpLocation').textContent = emp.lokasiKerja || emp.areaKerja || '-';
            el('wlEmpIdDisp').textContent = emp.employeeId;
            el('wlEmpAvatar').textContent = (emp.fullName || 'E').substring(0, 2).toUpperCase();

            var lastSp = emp.lastSp;
            var level = 'SP1';
            if (lastSp && lastSp.level) {
                var issued = parseYmd(lastSp.issued_at);
                var stillActive = issued && addMonthsNoOverflow(issued, levelMonths(lastSp.level)) > new Date();
                el('wlLastSpText').textContent = 'SP terakhir: ' + (levelLabels[lastSp.level] || lastSp.level) +
                    (lastSp.nomor ? ' — ' + lastSp.nomor : '') + ' (' + fmtId(issued) + ')' +
                    (stillActive ? '. Tingkat berikutnya dipilih otomatis, sesuaikan bila perlu.' : '.');
                el('wlLastSp').style.display = 'block';
                if (stillActive && levelMeta[lastSp.level]) level = levelMeta[lastSp.level].next;
            } else {
                el('wlLastSp').style.display = 'none';
            }
            el('wlLevel').value = level;

            refreshValidUntil();
            updateLevelFields();
            el('wlErrors').style.display = 'none';
            el('wlEmpPreview').style.display = 'block';
            el('btnGenerateWarningLetter').disabled = false;
        }

        function resetModal() {
            var form = el('formWarningLetter');
            if (form) form.reset();
            resetRegulationRows();
            el('wlEmployeeId').value = '';
            el('wlEmpSearch').value = '';
            el('wlEmpDropdown').style.display = 'none';
            el('wlEmpSearchClear').style.display = 'none';
            el('wlEmpPreview').style.display = 'none';
            el('wlErrors').style.display = 'none';
            el('wlDescCount').textContent = '0';
            updateLevelFields();
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
            el('wlLevel').addEventListener('change', function() { updateLevelFields(); refreshValidUntil(); });
            el('wlDescription').addEventListener('input', function(e) { el('wlDescCount').textContent = e.target.value.length; });
            el('wlRegulationRows').addEventListener('input', refreshRegulationPreview);
            el('wlRegulationRows').addEventListener('change', refreshRegulationPreview);
            el('wlRegulationRows').addEventListener('click', function(e) {
                var remove = e.target.closest('.wl-remove-regulation');
                if (!remove || regulationRows().length < 2) return;
                remove.closest('.wl-regulation-row').remove();
                updateRegulationRows();
            });
            el('wlAddRegulation').addEventListener('click', function() {
                var rows = regulationRows();
                if (rows.length >= 10) return;
                el('wlRegulationRows').appendChild(el('wlRegulationRowTemplate').content.firstElementChild.cloneNode(true));
                updateRegulationRows();
            });
            el('warningLetterModal').addEventListener('hidden.bs.modal', resetModal);
            updateLevelFields();
            updateRegulationRows();

            form.addEventListener('submit', function(e) {
                e.preventDefault();
                if (submitting) return;

                var empId = el('wlEmployeeId').value;
                var missing = [];
                if (!empId) missing.push('Pilih karyawan terlebih dahulu.');
                var isFirstTemplate = usesFirstTemplate(el('wlLevel').value);
                if (!isFirstTemplate && !el('wlCategory').value) missing.push('Kategori pelanggaran wajib dipilih.');
                if (el('wlDescription').value.trim().length < 10) missing.push('Uraian pelanggaran minimal 10 karakter.');
                if (!el('wlDocDate').value) missing.push('Tanggal surat wajib diisi.');
                var hasRegulationError = false;
                regulationRows().forEach(function(row, index) {
                    var type = row.querySelector('[data-regulation-field="regulation_type"]').value;
                    var article = row.querySelector('[data-regulation-field="article_number"]').value;
                    var paragraph = row.querySelector('[data-regulation-field="paragraph_number"]').value;
                    var letter = row.querySelector('[data-regulation-field="article_letter"]').value.trim();
                    var hasPart = type || article || paragraph || letter;
                    if (isFirstTemplate && !hasPart) {
                        missing.push('Lengkapi dasar ketentuan ' + (index + 1) + ' atau hapus baris tersebut.');
                        hasRegulationError = true;
                    } else if (hasPart && !type) {
                        missing.push('Pilih jenis peraturan pada dasar ketentuan ' + (index + 1) + '.');
                        hasRegulationError = true;
                    } else if (hasPart && !article) {
                        missing.push('Isi nomor pasal pada dasar ketentuan ' + (index + 1) + '.');
                        hasRegulationError = true;
                    }
                });
                if (hasRegulationError) return;
                if (isFirstTemplate && !el('wlSuperiorPosition').value.trim()) missing.push('Jabatan atasan wajib diisi.');
                if (missing.length) { showErrors(missing); return; }

                if (!isFirstTemplate) {
                    regulationRows().filter(function(row) {
                        return !Array.prototype.some.call(row.querySelectorAll('[data-regulation-field]'), function(input) {
                            return input.value.trim() !== '';
                        });
                    }).forEach(function(row) {
                        if (regulationRows().length > 1) row.remove();
                    });
                    updateRegulationRows();
                }

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
