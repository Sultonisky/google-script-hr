{{--
  Modal Tambah / Edit Karyawan Outsource (store Outsource_Employees).
  Field = kolom A–V master outsource + Vendor. Outsource ID dibuat otomatis.
--}}
@php
    $osOpts = config('hris.outsource');
@endphp
<div class="modal fade" id="outsourceFormModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content" style="border-radius:16px">
            <div class="modal-header" style="background:#eb1c24;border-radius:16px 16px 0 0">
                <div class="d-flex align-items-center gap-2 text-white">
                    <i class="bi bi-building-fill-gear fs-5"></i>
                    <h6 class="modal-title mb-0 fw-bold" id="osfTitle">Tambah Karyawan Outsource</h6>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <form id="outsourceForm" novalidate>
                <div class="modal-body p-4">
                    <input type="hidden" id="osfId" />

                    <div class="alert alert-light border d-flex align-items-center gap-2 py-2 mb-3" id="osfIdInfo"
                        style="font-size:12.5px">
                        <i class="bi bi-info-circle-fill text-primary"></i>
                        <span>Outsource ID dibuat otomatis dengan format <strong>{{ $osOpts['id_prefix'] ?? 'DM' }}{{ now()->timezone('Asia/Jakarta')->format('Y') }}0001</strong>.</span>
                    </div>

                    <p class="fw-bold mb-3" style="font-size:12px;text-transform:uppercase;letter-spacing:.05em;color:#eb1c24">
                        <i class="bi bi-person-badge-fill me-1"></i>Identitas &amp; Kontak
                    </p>
                    <div class="row g-2 mb-4">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="osfFullName" style="font-size:12.5px">Nama Karyawan (sesuai KTP) <span class="text-danger">*</span></label>
                            <input type="text" class="form-control form-control-sm" id="osfFullName" data-field="fullName" required maxlength="255" />
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold" for="osfBirthPlace" style="font-size:12.5px">Kota Kelahiran</label>
                            <input type="text" class="form-control form-control-sm" id="osfBirthPlace" data-field="birthPlace" maxlength="120" />
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold" for="osfBirthDate" style="font-size:12.5px">Tanggal Lahir</label>
                            <input type="date" class="form-control form-control-sm" id="osfBirthDate" data-field="birthDate" />
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold" for="osfCitizenIdAddress" style="font-size:12.5px">Alamat sesuai KTP</label>
                            <textarea class="form-control form-control-sm" id="osfCitizenIdAddress" data-field="citizenIdAddress" rows="2" maxlength="500"></textarea>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold" for="osfLastEducation" style="font-size:12.5px">Pendidikan Terakhir</label>
                            <select class="form-select form-select-sm" id="osfLastEducation" data-field="lastEducation">
                                <option value="">— Pilih —</option>
                                @foreach ($osOpts['education_levels'] ?? [] as $edu)
                                    <option value="{{ $edu }}">{{ $edu }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold" for="osfWhatsapp" style="font-size:12.5px">No WA (Aktif)</label>
                            <input type="tel" class="form-control form-control-sm" id="osfWhatsapp" data-field="whatsappNumber" maxlength="16" inputmode="numeric" placeholder="08xxxxxxxxxx" />
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold" for="osfEmail" style="font-size:12.5px">Alamat Email (Aktif)</label>
                            <input type="email" class="form-control form-control-sm" id="osfEmail" data-field="email" maxlength="255" />
                        </div>
                    </div>

                    <p class="fw-bold mb-3" style="font-size:12px;text-transform:uppercase;letter-spacing:.05em;color:#eb1c24">
                        <i class="bi bi-briefcase-fill me-1"></i>Penempatan &amp; Kontrak
                    </p>
                    <div class="row g-2 mb-4">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold" for="osfVendor" style="font-size:12.5px">Vendor <span class="text-danger">*</span></label>
                            <select class="form-select form-select-sm" id="osfVendor" data-field="vendor" required>
                                <option value="">— Pilih —</option>
                                @foreach ($osOpts['vendors'] ?? [] as $vendor)
                                    <option value="{{ $vendor }}">{{ $vendor }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold" for="osfJobTitle" style="font-size:12.5px">Nama Jabatan</label>
                            <input type="text" class="form-control form-control-sm" id="osfJobTitle" data-field="jobTitle" maxlength="255" list="osfJobTitleList" />
                            <datalist id="osfJobTitleList">
                                @foreach ($osOpts['job_titles'] ?? [] as $title)
                                    <option value="{{ $title }}"></option>
                                @endforeach
                            </datalist>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold" for="osfEntity" style="font-size:12.5px">Entity</label>
                            <select class="form-select form-select-sm" id="osfEntity" data-field="entity">
                                <option value="">— Pilih —</option>
                                @foreach ($osOpts['entities'] ?? [] as $entity)
                                    <option value="{{ $entity }}">{{ $entity }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold" for="osfWorkLocation" style="font-size:12.5px">Lokasi Kerja / Toko Penempatan</label>
                            <input type="text" class="form-control form-control-sm" id="osfWorkLocation" data-field="workLocation" maxlength="255" />
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold" for="osfWorkCity" style="font-size:12.5px">Kota/Kabupaten Lokasi Kerja</label>
                            <input type="text" class="form-control form-control-sm" id="osfWorkCity" data-field="workCity" maxlength="120" />
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold" for="osfCostCenter" style="font-size:12.5px">Nama Cabang (Cost Center)</label>
                            <input type="text" class="form-control form-control-sm" id="osfCostCenter" data-field="costCenter" maxlength="120" />
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold" for="osfMitoJoinDate" style="font-size:12.5px">Tgl Join di Mito</label>
                            <input type="date" class="form-control form-control-sm" id="osfMitoJoinDate" data-field="mitoJoinDate" />
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold" for="osfContractStart" style="font-size:12.5px">Tgl Awal Kontrak (Damarindo)</label>
                            <input type="date" class="form-control form-control-sm" id="osfContractStart" data-field="contractStartDate" />
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold" for="osfContractEnd" style="font-size:12.5px">Tgl Akhir Kontrak (StaffInc)</label>
                            <input type="date" class="form-control form-control-sm" id="osfContractEnd" data-field="contractEndDate" />
                        </div>
                    </div>

                    <p class="fw-bold mb-3" style="font-size:12px;text-transform:uppercase;letter-spacing:.05em;color:#eb1c24">
                        <i class="bi bi-wallet2 me-1"></i>Payroll
                    </p>
                    <div class="row g-2 mb-4">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold" for="osfBankAccount" style="font-size:12.5px">No Rekening BCA (Aktif)</label>
                            <input type="text" class="form-control form-control-sm" id="osfBankAccount" data-field="bankAccount" maxlength="20" inputmode="numeric" />
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold" for="osfPayrollScheme" style="font-size:12.5px">Skema Penggajian</label>
                            <select class="form-select form-select-sm" id="osfPayrollScheme" data-field="payrollScheme">
                                <option value="">— Pilih —</option>
                                @foreach ($osOpts['payroll_schemes'] ?? [] as $scheme)
                                    <option value="{{ $scheme }}">{{ $scheme }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold" for="osfUmk" style="font-size:12.5px">Nominal UMK yang Dipakai</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text">Rp</span>
                                <input type="text" class="form-control" id="osfUmk" data-field="umkAmount" data-amount inputmode="numeric" maxlength="15" placeholder="3.984.000" autocomplete="off" />
                            </div>
                            <div class="form-text amount-hint" style="font-size:11.5px"></div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold" for="osfBasicSalary" style="font-size:12.5px">Amount Gaji Pokok</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text">Rp</span>
                                <input type="text" class="form-control" id="osfBasicSalary" data-field="basicSalary" data-amount inputmode="numeric" maxlength="15" placeholder="2.788.800" autocomplete="off" />
                            </div>
                            <div class="form-text amount-hint" style="font-size:11.5px"></div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold" for="osfIncentive" style="font-size:12.5px">Amount Insentif 30%</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text">Rp</span>
                                <input type="text" class="form-control" id="osfIncentive" data-field="incentiveAmount" data-amount inputmode="numeric" maxlength="15" placeholder="1.195.200" autocomplete="off" />
                            </div>
                            <div class="form-text amount-hint" style="font-size:11.5px"></div>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold" for="osfRemarks" style="font-size:12.5px">Remarks</label>
                            <textarea class="form-control form-control-sm" id="osfRemarks" data-field="remarks" rows="2" maxlength="1000" placeholder="Contoh: Resign per 20/9"></textarea>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-sm text-white fw-semibold" style="background:#eb1c24" id="btnOsfSave">
                        <i class="bi bi-check2-circle me-1"></i><span id="osfSaveLabel">Simpan Outsource</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script nonce="{{ request()->attributes->get('csp_nonce') }}">
    (function() {
        var modalEl = document.getElementById('outsourceFormModal');
        var form = document.getElementById('outsourceForm');
        if (!modalEl || !form) return;

        var storeUrl = @json(route('hr.outsource.store'));
        var fields = form.querySelectorAll('[data-field]');

        function toDateInput(val) {
            if (!val) return '';
            var s = String(val).trim();
            if (/^\d{4}-\d{2}-\d{2}/.test(s)) return s.substring(0, 10);
            var m = s.match(/^(\d{1,2})\/(\d{1,2})\/(\d{4})/);
            if (m) return m[3] + '-' + m[2].padStart(2, '0') + '-' + m[1].padStart(2, '0');
            return '';
        }

        function toAmountInput(val) {
            if (val === null || val === undefined || val === '') return '';
            var n = Number(val);
            return isNaN(n) ? String(val).replace(/\D/g, '') : String(Math.round(n));
        }

        function amountHint(digits) {
            var n = Number(digits);
            if (!digits || !n) return '';
            var units = [[1e12, 'triliun'], [1e9, 'miliar'], [1e6, 'juta'], [1e3, 'ribu']];
            for (var i = 0; i < units.length; i++) {
                if (n >= units[i][0]) {
                    return '≈ ' + (n / units[i][0]).toLocaleString('id-ID', { maximumFractionDigits: 2 }) + ' ' + units[i][1];
                }
            }
            return '';
        }

        function formatAmount(el) {
            var caret = el.selectionStart || 0;
            var digitsBeforeCaret = el.value.slice(0, caret).replace(/\D/g, '').length;
            var digits = el.value.replace(/\D/g, '').replace(/^0+(?=\d)/, '').slice(0, 12);
            var formatted = digits.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
            el.value = formatted;
            if (document.activeElement === el) {
                var pos = 0, seen = 0;
                while (pos < formatted.length && seen < digitsBeforeCaret) {
                    if (formatted.charAt(pos) !== '.') seen++;
                    pos++;
                }
                el.setSelectionRange(pos, pos);
            }
            var hint = el.closest('[class*="col-"]').querySelector('.amount-hint');
            if (hint) hint.textContent = amountHint(digits);
        }

        var amountFields = form.querySelectorAll('[data-amount]');
        amountFields.forEach(function(el) {
            el.addEventListener('input', function() { formatAmount(el); });
        });

        function setSelectValue(el, value) {
            if (value && !Array.from(el.options).some(function(o) { return o.value === value; })) {
                var opt = document.createElement('option');
                opt.value = value;
                opt.textContent = value;
                el.appendChild(opt);
            }
            el.value = value || '';
        }

        window.openOutsourceForm = function(data) {
            form.reset();
            form.classList.remove('was-validated');
            var isEdit = !!(data && data.outsourceId);
            document.getElementById('osfId').value = isEdit ? data.outsourceId : '';
            document.getElementById('osfTitle').textContent = isEdit ? 'Edit Karyawan Outsource · ' + data.outsourceId : 'Tambah Karyawan Outsource';
            document.getElementById('osfSaveLabel').textContent = isEdit ? 'Simpan Perubahan' : 'Simpan Outsource';
            document.getElementById('osfIdInfo').style.display = isEdit ? 'none' : '';

            fields.forEach(function(el) {
                var key = el.getAttribute('data-field');
                var value = data ? data[key] : null;
                if (el.type === 'date') {
                    el.value = toDateInput(value);
                } else if (el.hasAttribute('data-amount')) {
                    el.value = toAmountInput(value);
                    formatAmount(el);
                } else if (el.tagName === 'SELECT') {
                    setSelectValue(el, value === null || value === undefined ? '' : String(value));
                } else {
                    el.value = value === null || value === undefined ? '' : String(value).replace(/^'/, '');
                }
            });

            bootstrap.Modal.getOrCreateInstance(modalEl).show();
        };

        form.addEventListener('submit', function(e) {
            e.preventDefault();
            var id = document.getElementById('osfId').value;
            var btn = document.getElementById('btnOsfSave');
            var payload = {};
            fields.forEach(function(el) {
                var value = (el.value || '').trim();
                payload[el.getAttribute('data-field')] = el.hasAttribute('data-amount') ? value.replace(/\D/g, '') : value;
            });

            if (!payload.fullName || !payload.vendor) {
                showToast('Nama karyawan dan vendor wajib diisi.', 'warning');
                return;
            }

            var origHtml = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Menyimpan...';

            fetch(id ? '/hr/outsource/' + encodeURIComponent(id) : storeUrl, {
                    method: id ? 'PUT' : 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') || {}).content || ''
                    },
                    body: JSON.stringify(payload)
                })
                .then(function(res) {
                    return res.json().then(function(data) {
                        if (!res.ok || !data.success) throw data;
                        return data;
                    });
                })
                .then(function(result) {
                    bootstrap.Modal.getInstance(modalEl)?.hide();
                    showToast(result.message || 'Data outsource tersimpan.', 'success');
                    setTimeout(function() { window.location.reload(); }, 700);
                })
                .catch(function(err) {
                    var msg = (err && err.message) ? err.message : 'Terjadi kesalahan. Coba lagi.';
                    if (err && err.errors) msg = Object.values(err.errors).flat().join(' ');
                    showToast(msg, 'error');
                })
                .finally(function() {
                    btn.disabled = false;
                    btn.innerHTML = origHtml;
                });
        });
    })();
</script>
