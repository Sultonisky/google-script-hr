{{-- Exit Clearance: cetak saja, tidak mengubah data master karyawan. --}}
<div class="modal fade" id="exitClearanceModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content" style="border-radius:16px">
            <div class="modal-header" style="background:#eb1c24;border-radius:16px 16px 0 0">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-clipboard-check text-white fs-5"></i>
                    <h6 class="modal-title mb-0 text-white fw-bold">Exit Clearance</h6>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <form id="formExitClearance" class="d-flex flex-column" method="POST" target="_blank" action="#"
                data-action="{{ route('hr.export.exit-clearance', ['id' => '__ID__']) }}"
                style="min-height:0;flex:1 1 auto;overflow:hidden" autocomplete="off">
                @csrf
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold" style="font-size:13px" for="ecEmpSearch">
                            <i class="bi bi-search me-1"></i>Cari Karyawan <span class="text-danger">*</span>
                        </label>
                        <div class="position-relative">
                            <input type="text" class="form-control" id="ecEmpSearch"
                                placeholder="Ketik nama, NIK, atau Employee ID..." autocomplete="off"
                                style="font-size:13px;padding-right:36px" />
                            <i class="bi bi-x-circle-fill position-absolute" id="ecEmpSearchClear"
                                style="right:10px;top:50%;transform:translateY(-50%);cursor:pointer;color:#eb1c24;display:none"></i>
                        </div>
                        <div id="ecEmpDropdown" class="rounded-3 mt-1 shadow-sm"
                            style="display:none;max-height:220px;overflow-y:auto;position:relative"></div>
                    </div>

                    <div id="ecEmpPreview" class="p-3 rounded-3 mb-3" style="display:none;border:1px solid #eb1c24">
                        <div class="d-flex align-items-center gap-3">
                            <div id="ecEmpAvatar"
                                style="width:44px;height:44px;font-size:16px;flex-shrink:0;background:#eb1c24;color:#fff;border-radius:12px;display:flex;align-items:center;justify-content:center;font-weight:800">?</div>
                            <div class="flex-grow-1">
                                <div class="fw-bold" id="ecEmpName" style="font-size:15px;color:#eb1c24">-</div>
                                <div class="text-muted" style="font-size:12px">
                                    <span id="ecEmpPosition">-</span>
                                    <span class="mx-1">&bull;</span>
                                    <span id="ecEmpDivision">-</span>
                                </div>
                            </div>
                            <div class="text-end flex-shrink-0" style="font-size:11.5px">
                                <div class="text-muted">NIK</div>
                                <div class="fw-semibold" id="ecEmpNik" style="color:#eb1c24">-</div>
                            </div>
                        </div>
                    </div>

                    <p class="text-muted mb-3" style="font-size:12px">Form ini hanya mencetak PDF. Data master karyawan tidak diubah. Aset HR &amp; GA yang tidak dicentang tercetak sebagai tanda strip.</p>

                    <fieldset id="ecFields" disabled>
                        <p class="fw-bold mb-2" style="font-size:12px;text-transform:uppercase;letter-spacing:.04em;color:#eb1c24">Data karyawan</p>
                        <div class="mb-3">
                            <div class="form-label fw-semibold" style="font-size:13px">Perusahaan <span class="text-danger">*</span></div>
                            @foreach (\App\Support\ExitClearanceForm::COMPANIES as $code => $name)
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="company" id="ecCompany{{ $code }}" value="{{ $code }}">
                                    <label class="form-check-label" style="font-size:13px" for="ecCompany{{ $code }}">{{ $name }}</label>
                                </div>
                            @endforeach
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold" style="font-size:13px" for="ecLastDate">Hari/Tanggal Berakhir Bekerja <span class="text-danger">*</span></label>
                            <input type="date" class="form-control form-control-sm" id="ecLastDate" name="last_working_date">
                        </div>

                        <p class="fw-bold mb-2" style="font-size:12px;text-transform:uppercase;letter-spacing:.04em;color:#eb1c24">Serah terima berkas/dokumen</p>
                        <div class="row g-2 mb-3">
                            <div class="col-md-6">
                                <div class="form-check mt-4">
                                    <input class="form-check-input" type="checkbox" value="1" name="hardcopy" id="ecHardcopy">
                                    <label class="form-check-label" style="font-size:13px" for="ecHardcopy">Hardcopy</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check mt-4">
                                    <input class="form-check-input" type="checkbox" value="1" name="softcopy" id="ecSoftcopy">
                                    <label class="form-check-label" style="font-size:13px" for="ecSoftcopy">Softcopy</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold" style="font-size:13px" for="ecDelegate">Didelegasi kepada</label>
                                <input type="text" class="form-control form-control-sm" id="ecDelegate" name="delegated_to" maxlength="160">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold" style="font-size:13px" for="ecHandoverDate">Tanggal Penyerahan</label>
                                <input type="date" class="form-control form-control-sm" id="ecHandoverDate" name="handover_date">
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-semibold" style="font-size:13px" for="ecHandoverOther">Lainnya</label>
                                <input type="text" class="form-control form-control-sm" id="ecHandoverOther" name="handover_other" maxlength="180">
                            </div>
                        </div>

                        <p class="fw-bold mb-2" style="font-size:12px;text-transform:uppercase;letter-spacing:.04em;color:#eb1c24">Aset HR &amp; GA</p>
                        <div class="row g-1 mb-3">
                            @foreach (\App\Support\ExitClearanceForm::GA_ASSETS as $key => $label)
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" value="1" name="ga[{{ $key }}]" id="ecGa{{ $key }}">
                                        <label class="form-check-label" style="font-size:13px" for="ecGa{{ $key }}">{{ $label }}</label>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <p class="fw-bold mb-2" style="font-size:12px;text-transform:uppercase;letter-spacing:.04em;color:#eb1c24">IT</p>
                        <div class="row g-2 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold" style="font-size:13px" for="ecItEmail">Email</label>
                                <input type="email" class="form-control form-control-sm" id="ecItEmail" name="it[email]" maxlength="160">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold" style="font-size:13px" for="ecItPassword">Password</label>
                                <input type="text" class="form-control form-control-sm" id="ecItPassword" name="it[password]" maxlength="120" autocomplete="off">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold" style="font-size:13px" for="ecItLaptop">Laptop</label>
                                <input type="text" class="form-control form-control-sm" id="ecItLaptop" name="it[laptop]" maxlength="180">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold" style="font-size:13px" for="ecItPhone">Handphone</label>
                                <input type="text" class="form-control form-control-sm" id="ecItPhone" name="it[phone]" maxlength="16" inputmode="numeric" placeholder="08xxxxxxxxxx">
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-semibold" style="font-size:13px" for="ecItOther">Lainnya</label>
                                <input type="text" class="form-control form-control-sm" id="ecItOther" name="it[other]" maxlength="180">
                            </div>
                        </div>

                        <p class="fw-bold mb-2" style="font-size:12px;text-transform:uppercase;letter-spacing:.04em;color:#eb1c24">Aset Divisi</p>
                        <div class="row g-2 mb-3">
                            @foreach (\App\Support\ExitClearanceForm::DIVISION_FIELDS as $key => $label)
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold" style="font-size:13px" for="ecDivision{{ $key }}">{{ $label }}</label>
                                    <input type="text" class="form-control form-control-sm" id="ecDivision{{ $key }}" name="division[{{ $key }}]" maxlength="180">
                                </div>
                            @endforeach
                        </div>

                        <p class="fw-bold mb-2" style="font-size:12px;text-transform:uppercase;letter-spacing:.04em;color:#eb1c24">Accounting</p>
                        @foreach (['petty' => 'Petty Cash', 'loan' => 'Pinjaman'] as $moneyKey => $moneyLabel)
                            <div class="border rounded-3 p-3 mb-2">
                                <div class="fw-semibold mb-2" style="font-size:13px">{{ $moneyLabel }}</div>
                                <div class="d-flex gap-3 mb-2">
                                    @foreach (\App\Support\ExitClearanceForm::MONEY_STATUSES as $status => $statusLabel)
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="{{ $moneyKey }}_status" id="ec{{ $moneyKey }}{{ $status }}" value="{{ $status }}">
                                            <label class="form-check-label" style="font-size:13px" for="ec{{ $moneyKey }}{{ $status }}">{{ $statusLabel }}</label>
                                        </div>
                                    @endforeach
                                </div>
                                <div class="row g-2">
                                    <div class="col-md-4">
                                        <label class="form-label" style="font-size:12px" for="ec{{ $moneyKey }}Amount">Nominal (Rp)</label>
                                        <input type="text" class="form-control form-control-sm" id="ec{{ $moneyKey }}Amount" name="{{ $moneyKey }}_amount" inputmode="numeric" maxlength="15" disabled>
                                    </div>
                                    <div class="col-md-8">
                                        <label class="form-label" style="font-size:12px" for="ec{{ $moneyKey }}To">Telah diserahkan kepada</label>
                                        <input type="text" class="form-control form-control-sm" id="ec{{ $moneyKey }}To" name="{{ $moneyKey }}_to" maxlength="160" disabled>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                        <div class="mb-3 mt-2">
                            <label class="form-label fw-semibold" style="font-size:13px" for="ecAccountingOther">Lainnya</label>
                            <input type="text" class="form-control form-control-sm" id="ecAccountingOther" name="accounting_other" maxlength="300">
                        </div>

                        <p class="fw-bold mb-2" style="font-size:12px;text-transform:uppercase;letter-spacing:.04em;color:#eb1c24">Approval</p>
                        <p class="text-muted" style="font-size:12px">Nama dan tanggal tercetak di PDF. Kotak tanda tangan dikosongkan untuk ditandatangani di kertas.</p>
                        <div class="row g-2">
                            @foreach (\App\Support\ExitClearanceForm::APPROVERS as $key => $approver)
                                <div class="col-md-6">
                                    <div class="border rounded-3 p-2 h-100">
                                        <div class="fw-semibold" style="font-size:12.5px">{{ $approver['title'] }}</div>
                                        <div class="text-muted mb-2" style="font-size:11px">{{ $approver['hint'] }}</div>
                                        <label class="form-label" style="font-size:12px" for="ecApprover{{ $key }}Name">Nama</label>
                                        <input type="text" class="form-control form-control-sm mb-2" id="ecApprover{{ $key }}Name" name="approvers[{{ $key }}][name]" maxlength="120">
                                        <label class="form-label" style="font-size:12px" for="ecApprover{{ $key }}Date">Tanggal</label>
                                        <input type="date" class="form-control form-control-sm" id="ecApprover{{ $key }}Date" name="approvers[{{ $key }}][date]">
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </fieldset>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-sm text-white fw-semibold" style="background:#eb1c24" id="btnExitClearancePdf" disabled>
                        <i class="bi bi-file-earmark-pdf me-1"></i>Unduh PDF
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@php
    $exitClearanceEmployees = collect($all ?? ($employees ?? []))
        ->map(function ($e) {
            if (!is_object($e)) {
                return $e;
            }
            return [
                'employeeId' => $e->employeeId ?? null,
                'fullName' => $e->fullName ?? null,
                'jobPosition' => $e->jobPosition ?? null,
                'division' => ($e->division ?? '') !== '' ? $e->division : ($e->department ?? null),
                'branchName' => $e->branchName ?? null,
                'company' => \App\Support\ExitClearanceForm::matchCompany((string) ($e->branchName ?? '')),
                'nikNpwp' => $e->nikNpwp ?? null,
                'resignDate' => $e->resignDate ?? null,
                'workingEmail' => $e->workingEmail ?? null,
                'directSuperior' => $e->directSuperior ?? null,
            ];
        })
        ->values();
@endphp
<script nonce="{{ request()->attributes->get('csp_nonce') }}">
    (function () {
        var employees = @json($exitClearanceEmployees);
        var form = document.getElementById('formExitClearance');
        var modal = document.getElementById('exitClearanceModal');
        var search = document.getElementById('ecEmpSearch');
        var dropdown = document.getElementById('ecEmpDropdown');
        var clearBtn = document.getElementById('ecEmpSearchClear');
        var preview = document.getElementById('ecEmpPreview');
        var fields = document.getElementById('ecFields');
        var submitBtn = document.getElementById('btnExitClearancePdf');
        var selected = null;

        function textOrDash(value) {
            var text = (value || '').toString().trim();
            return text === '' ? '-' : text;
        }

        function toDateInput(value) {
            var text = (value || '').toString().trim();
            var iso = text.match(/^(\d{4}-\d{2}-\d{2})/);
            if (iso) return iso[1];
            var dmy = text.match(/^(\d{2})[\/\-](\d{2})[\/\-](\d{4})/);
            if (dmy) return dmy[3] + '-' + dmy[2] + '-' + dmy[1];
            return '';
        }

        function setText(id, value) {
            var el = document.getElementById(id);
            if (el) el.textContent = textOrDash(value);
        }

        function syncMoneyFields(key) {
            var selected = form.querySelector('input[name="' + key + '_status"]:checked');
            var enabled = !!(selected && selected.value === 'ada');
            ['Amount', 'To'].forEach(function (suffix) {
                var el = document.getElementById('ec' + key + suffix);
                if (!el) return;
                el.disabled = !enabled;
                if (!enabled) {
                    el.value = '';
                    el.classList.remove('is-invalid');
                }
            });
        }

        function formatNominal(value) {
            var digits = (value || '').replace(/\D/g, '').slice(0, 12);
            return digits.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        }

        ['petty', 'loan'].forEach(function (key) {
            form.querySelectorAll('input[name="' + key + '_status"]').forEach(function (radio) {
                radio.addEventListener('change', function () {
                    syncMoneyFields(key);
                });
            });
            var amount = document.getElementById('ec' + key + 'Amount');
            if (amount) {
                amount.addEventListener('input', function () {
                    amount.value = formatNominal(amount.value);
                    amount.classList.remove('is-invalid');
                });
            }
            var receiver = document.getElementById('ec' + key + 'To');
            if (receiver) {
                receiver.addEventListener('input', function () {
                    receiver.classList.remove('is-invalid');
                });
            }
            syncMoneyFields(key);
        });

        var emailInput = document.getElementById('ecItEmail');
        if (emailInput) {
            emailInput.addEventListener('input', function () {
                emailInput.classList.remove('is-invalid');
            });
        }
        var phoneInput = document.getElementById('ecItPhone');
        if (phoneInput) {
            phoneInput.addEventListener('input', function () {
                var plus = phoneInput.value.charAt(0) === '+';
                var digits = phoneInput.value.replace(/\D/g, '').slice(0, 15);
                phoneInput.value = (plus ? '+' : '') + digits;
                phoneInput.classList.remove('is-invalid');
            });
        }

        function resetForm() {
            selected = null;
            form.reset();
            form.action = '#';
            fields.disabled = true;
            submitBtn.disabled = true;
            preview.style.display = 'none';
            dropdown.style.display = 'none';
            clearBtn.style.display = 'none';
            search.value = '';
            syncMoneyFields('petty');
            syncMoneyFields('loan');
        }

        function selectEmployee(emp) {
            var savedSearch = (emp.fullName || '') + (emp.employeeId ? ' (' + emp.employeeId + ')' : '');
            form.reset();
            search.value = savedSearch;
            clearBtn.style.display = 'block';
            dropdown.style.display = 'none';
            selected = emp;
            form.action = form.getAttribute('data-action').replace('__ID__', encodeURIComponent(emp.employeeId || ''));
            fields.disabled = false;
            submitBtn.disabled = false;
            setText('ecEmpName', emp.fullName);
            setText('ecEmpPosition', emp.jobPosition);
            setText('ecEmpDivision', emp.division);
            setText('ecEmpNik', emp.nikNpwp);
            document.getElementById('ecEmpAvatar').textContent = (emp.fullName || 'K').substring(0, 2).toUpperCase();
            preview.style.display = 'block';
            if (emp.company) {
                var company = form.querySelector('input[name="company"][value="' + emp.company + '"]');
                if (company) company.checked = true;
            }
            document.getElementById('ecLastDate').value = toDateInput(emp.resignDate);
            document.getElementById('ecItEmail').value = emp.workingEmail || '';
            document.getElementById('ecApproveremployeeName').value = emp.fullName || '';
            document.getElementById('ecApproversupervisorName').value = emp.directSuperior || '';
            syncMoneyFields('petty');
            syncMoneyFields('loan');
        }

        function renderDropdown(list) {
            dropdown.textContent = '';
            if (!list.length) {
                var empty = document.createElement('div');
                empty.className = 'p-3 text-center ec-search-empty';
                empty.textContent = 'Tidak ada karyawan yang cocok';
                dropdown.appendChild(empty);
                dropdown.style.display = 'block';
                return;
            }
            list.forEach(function (emp) {
                var item = document.createElement('div');
                item.className = 'p-2 border-bottom ec-search-item';
                var name = document.createElement('div');
                name.className = 'ec-search-name';
                name.textContent = emp.fullName || '-';
                var meta = document.createElement('div');
                meta.className = 'ec-search-meta';
                meta.textContent = (emp.employeeId || '') + ' · ' + (emp.jobPosition || '-');
                item.appendChild(name);
                item.appendChild(meta);
                item.addEventListener('click', function () {
                    selectEmployee(emp);
                });
                dropdown.appendChild(item);
            });
            dropdown.style.display = 'block';
        }

        search.addEventListener('keydown', function (event) {
            if (event.key === 'Enter') event.preventDefault();
        });
        search.addEventListener('input', function () {
            var query = search.value.toLowerCase().trim();
            clearBtn.style.display = query ? 'block' : 'none';
            if (!query) {
                dropdown.style.display = 'none';
                return;
            }
            var matched = (employees || []).filter(function (emp) {
                return (emp.fullName || '').toLowerCase().indexOf(query) !== -1
                    || (emp.employeeId || '').toLowerCase().indexOf(query) !== -1
                    || (emp.nikNpwp || '').toLowerCase().indexOf(query) !== -1
                    || (emp.jobPosition || '').toLowerCase().indexOf(query) !== -1;
            }).slice(0, 8);
            renderDropdown(matched);
        });
        clearBtn.addEventListener('click', resetForm);
        form.addEventListener('submit', function (event) {
            if (!selected || !selected.employeeId) {
                event.preventDefault();
                if (typeof window.showToast === 'function') window.showToast('Pilih karyawan terlebih dahulu.', 'error');
                return;
            }
            if (!form.querySelector('input[name="company"]:checked')) {
                event.preventDefault();
                if (typeof window.showToast === 'function') window.showToast('Pilih perusahaan.', 'error');
                return;
            }
            if (!document.getElementById('ecLastDate').value) {
                event.preventDefault();
                if (typeof window.showToast === 'function') window.showToast('Tanggal berakhir bekerja wajib diisi.', 'error');
                return;
            }
            var email = (emailInput.value || '').trim();
            if (email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                event.preventDefault();
                emailInput.classList.add('is-invalid');
                if (typeof window.showToast === 'function') window.showToast('Format email tidak valid.', 'error');
                return;
            }
            var phone = (phoneInput.value || '').trim();
            if (phone && !/^\+?[0-9]{8,15}$/.test(phone)) {
                event.preventDefault();
                phoneInput.classList.add('is-invalid');
                if (typeof window.showToast === 'function') window.showToast('Nomor handphone harus 8–15 digit.', 'error');
                return;
            }
            var moneyLabels = { petty: 'petty cash', loan: 'pinjaman' };
            var blocked = false;
            Object.keys(moneyLabels).forEach(function (key) {
                if (blocked) return;
                var selectedMoney = form.querySelector('input[name="' + key + '_status"]:checked');
                if (!selectedMoney || selectedMoney.value !== 'ada') return;
                var amount = document.getElementById('ec' + key + 'Amount');
                var receiver = document.getElementById('ec' + key + 'To');
                if (!amount.value.replace(/\D/g, '')) {
                    blocked = true;
                    event.preventDefault();
                    amount.classList.add('is-invalid');
                    if (typeof window.showToast === 'function') window.showToast('Nominal ' + moneyLabels[key] + ' wajib diisi jika status Ada.', 'error');
                    return;
                }
                if (!(receiver.value || '').trim()) {
                    blocked = true;
                    event.preventDefault();
                    receiver.classList.add('is-invalid');
                    if (typeof window.showToast === 'function') window.showToast('Penerima ' + moneyLabels[key] + ' wajib diisi jika status Ada.', 'error');
                }
            });
        });
        modal.addEventListener('hidden.bs.modal', resetForm);
    })();
</script>
<style>
    #exitClearanceModal #ecEmpSearch {
        border-color: #eb1c24 !important;
        color: var(--color-text, #1f2937);
        background: var(--color-surface, #fff);
    }
    #exitClearanceModal #ecEmpSearch:focus {
        border-color: #eb1c24 !important;
        box-shadow: 0 0 0 0.2rem rgba(235, 28, 36, 0.28);
    }
    #exitClearanceModal #ecEmpDropdown {
        background: var(--color-surface, #fff);
        border: 1px solid #eb1c24 !important;
        color: var(--color-text, #1f2937);
    }
    #exitClearanceModal #ecEmpDropdown .ec-search-item {
        cursor: pointer;
    }
    #exitClearanceModal #ecEmpDropdown .ec-search-item:hover {
        background: rgba(235, 28, 36, 0.14);
    }
    #exitClearanceModal #ecEmpDropdown .ec-search-name {
        color: #eb1c24 !important;
        font-size: 12.5px;
        font-weight: 700;
    }
    #exitClearanceModal #ecEmpDropdown .ec-search-meta,
    #exitClearanceModal #ecEmpDropdown .ec-search-empty {
        color: var(--color-text, #1f2937) !important;
        font-size: 11px;
    }
    #exitClearanceModal #ecEmpDropdown .ec-search-empty {
        font-size: 13px;
    }
</style>
