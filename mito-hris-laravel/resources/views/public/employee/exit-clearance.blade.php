@extends('layouts.public')

@section('title', 'Exit Clearance')
@section('description', 'Formulir exit clearance MITO. Cetak PDF untuk keperluan proses keluar Anda.')
@section('robots', 'noindex, nofollow, noarchive')

@section('styles')
<style>
    /* ── Search ───────────────────────────────────── */
    #ecEmpSearch {
        border-color: #eb1c24 !important;
        font-size: 13px;
        padding-left: 36px;
        padding-right: 36px;
    }
    #ecEmpSearch:focus {
        border-color: #eb1c24 !important;
        box-shadow: 0 0 0 0.2rem rgba(235, 28, 36, 0.25);
    }
    /* Dropdown diposisikan via JS (fixed) agar tidak terpotong overflow:hidden pada .form-section */
    #ecEmpDropdown {
        background: var(--color-surface, #fff);
        border: 1px solid #eb1c24;
        color: var(--color-text, #1f2937);
        position: fixed;
        z-index: 1050;
    }
    #ecEmpDropdown .ec-search-item {
        cursor: pointer;
        transition: background 0.1s ease;
    }
    #ecEmpDropdown .ec-search-item:hover {
        background: rgba(235, 28, 36, 0.1);
    }
    #ecEmpDropdown .ec-search-name {
        color: #eb1c24;
        font-size: 12.5px;
        font-weight: 700;
    }
    #ecEmpDropdown .ec-search-meta,
    #ecEmpDropdown .ec-search-empty {
        color: var(--color-text-soft, #6b7280);
        font-size: 11px;
    }
    #ecEmpDropdown .ec-search-empty { font-size: 13px; }

    /* ── Interactive option labels ────────────────── */
    .ec-option-label {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 8px 10px;
        border-radius: 10px;
        border: 1px solid var(--color-border, #e5e7eb);
        font-size: 13px;
        cursor: pointer;
        transition: border-color 0.15s, background 0.15s;
        height: 100%;
    }
    .ec-option-label:hover {
        border-color: rgba(235, 28, 36, 0.4);
        background: rgba(235, 28, 36, 0.04);
    }
    .ec-option-label:has(input:checked) {
        border-color: #eb1c24;
        background: rgba(235, 28, 36, 0.06);
    }
    .ec-option-label input { flex-shrink: 0; margin: 0; }

    /* ── Fieldset disabled fade ───────────────────── */
    #ecFields:disabled .form-section {
        opacity: 0.5;
        pointer-events: none;
    }
</style>
@endsection

@section('content')

    {{-- Hero --}}
    <div class="hero-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-8 col-md-7">
                    <div class="hero-org">MITO Group</div>
                    <div class="hero-dept">Human Resources Department</div>
                    <h1 class="hero-title">Exit Clearance</h1>
                    <p class="hero-subtitle">Lengkapi formulir exit clearance Anda dan unduh PDF untuk keperluan proses pengunduran diri.</p>
                </div>
                <div class="col-lg-4 col-md-5 d-none d-md-flex justify-content-end align-items-center">
                    <img src="{{ asset('assets/mito.png') }}" alt="MITO Official" class="hero-avatar-img" loading="lazy">
                </div>
            </div>
        </div>
    </div>

    {{-- Page body --}}
    <div class="container py-4" style="max-width:980px">

        @php
            $pdfAction = route('public.exit-clearance.pdf', ['id' => '__ID__']);
            $ecEmployees = collect($employees)
                ->map(function ($e) {
                    return [
                        'employeeId'  => $e->employeeId ?? null,
                        'fullName'    => $e->fullName ?? null,
                        'jobPosition' => $e->jobPosition ?? null,
                        'division'    => ($e->division ?? '') !== '' ? $e->division : ($e->department ?? null),
                        'branchName'  => $e->branchName ?? null,
                        'company'     => \App\Support\ExitClearanceForm::matchCompany((string) ($e->branchName ?? '')),
                        // nikNpwp, workingEmail, resignDate, directSuperior
                        // sengaja tidak di-expose - diambil server-side saat POST pdf()
                    ];
                })
                ->values();
        @endphp

        <form id="formExitClearance" method="POST" target="_blank" action="#"
            data-action="{{ $pdfAction }}" autocomplete="off">
            @csrf

            {{-- ── Identitas Anda ─────────────────────────── --}}
            <div class="form-section">
                <div class="form-section-header">
                    <i class="bi bi-search"></i>
                    <div>
                        <h5>Identitas Anda</h5>
                        <p>Ketik nama atau Employee ID Anda untuk memulai</p>
                    </div>
                </div>
                <div class="p-3 p-md-4">
                    <label class="form-label fw-semibold mb-1" style="font-size:13px" for="ecEmpSearch">
                        Nama Anda <span class="text-danger">*</span>
                    </label>
                    <div class="position-relative" id="ecEmpSearchWrap">
                        <span class="position-absolute" style="left:11px;top:50%;transform:translateY(-50%);color:#9ca3af;pointer-events:none;font-size:13px">
                            <i class="bi bi-search"></i>
                        </span>
                        <input type="text" class="form-control" id="ecEmpSearch"
                            placeholder="Ketik nama atau Employee ID Anda..." autocomplete="off">
                        <i class="bi bi-x-circle-fill position-absolute" id="ecEmpSearchClear"
                            style="right:10px;top:50%;transform:translateY(-50%);cursor:pointer;color:#eb1c24;display:none;font-size:14px"></i>
                    </div>
                    {{-- Dropdown di luar .form-section agar tidak terpotong overflow:hidden --}}
                    <div id="ecEmpDropdown" class="rounded-3 shadow-sm" style="display:none;max-height:220px;overflow-y:auto"></div>
                </div>

                {{-- Employee preview card --}}
                <div id="ecEmpPreview" class="mx-3 mx-md-4 mb-3 p-3 rounded-3"
                    style="display:none;background:rgba(235,28,36,0.05);border:1px solid rgba(235,28,36,0.25)">
                    <div class="d-flex align-items-center gap-3">
                        <div id="ecEmpAvatar"
                            style="width:46px;height:46px;font-size:16px;flex-shrink:0;background:#eb1c24;color:#fff;border-radius:12px;display:flex;align-items:center;justify-content:center;font-weight:800">?</div>
                        <div class="flex-grow-1 overflow-hidden">
                            <div class="fw-bold text-truncate" id="ecEmpName" style="font-size:14px;color:#eb1c24">-</div>
                            <div class="text-muted text-truncate" style="font-size:12px">
                                <span id="ecEmpPosition">-</span>
                                <span class="mx-1">&bull;</span>
                                <span id="ecEmpDivision">-</span>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <fieldset id="ecFields" disabled>

                {{-- ── Data Anda ──────────────────────────── --}}
                <div class="form-section">
                    <div class="form-section-header">
                        <i class="bi bi-person-vcard-fill"></i>
                        <div>
                            <h5>Data Anda</h5>
                            <p>Perusahaan dan tanggal berakhir bekerja</p>
                        </div>
                    </div>
                    <div class="p-3 p-md-4">
                        <div class="mb-4">
                            <div class="form-label fw-semibold mb-2" style="font-size:13px">
                                Perusahaan <span class="text-danger">*</span>
                            </div>
                            <div class="row g-2">
                                @foreach (\App\Support\ExitClearanceForm::COMPANIES as $code => $name)
                                    <div class="col-md-6">
                                        <label class="ec-option-label" for="ecCompany{{ $code }}">
                                            <input class="form-check-input" type="radio" name="company"
                                                id="ecCompany{{ $code }}" value="{{ $code }}">
                                            {{ $name }}
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        <div>
                            <label class="form-label fw-semibold mb-1" style="font-size:13px" for="ecLastDate">
                                Hari/Tanggal Berakhir Bekerja <span class="text-danger">*</span>
                            </label>
                            <input type="date" class="form-control form-control-sm" id="ecLastDate"
                                name="last_working_date" style="max-width:220px">
                        </div>
                    </div>
                </div>

                {{-- ── Serah Terima Berkas ─────────────────── --}}
                <div class="form-section">
                    <div class="form-section-header">
                        <i class="bi bi-folder-fill"></i>
                        <div>
                            <h5>Serah Terima Berkas / Dokumen</h5>
                            <p>Hardcopy, softcopy, delegasi, dan tanggal penyerahan</p>
                        </div>
                    </div>
                    <div class="p-3 p-md-4">
                        {{-- Baris 1: dua checkbox --}}
                        <div class="row g-3 mb-3">
                            <div class="col-6 col-sm-4 col-md-6">
                                <label class="ec-option-label" for="ecHardcopy">
                                    <input class="form-check-input" type="checkbox" value="1" name="hardcopy" id="ecHardcopy">
                                    Hardcopy
                                </label>
                            </div>
                            <div class="col-6 col-sm-4 col-md-6">
                                <label class="ec-option-label" for="ecSoftcopy">
                                    <input class="form-check-input" type="checkbox" value="1" name="softcopy" id="ecSoftcopy">
                                    Softcopy
                                </label>
                            </div>
                        </div>
                        {{-- Baris 2: semua input teks --}}
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold mb-1" style="font-size:13px" for="ecDelegate">Didelegasi kepada</label>
                                <input type="text" class="form-control form-control-sm" id="ecDelegate"
                                    name="delegated_to" maxlength="160"
                                    inputmode="text" autocomplete="off">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold mb-1" style="font-size:13px" for="ecHandoverDate">Tanggal Penyerahan</label>
                                <input type="date" class="form-control form-control-sm" id="ecHandoverDate"
                                    name="handover_date">
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-semibold mb-1" style="font-size:13px" for="ecHandoverOther">Lainnya</label>
                                <input type="text" class="form-control form-control-sm" id="ecHandoverOther"
                                    name="handover_other" maxlength="180">
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ── Aset HR & GA ────────────────────────── --}}
                <div class="form-section">
                    <div class="form-section-header">
                        <i class="bi bi-shield-fill-check"></i>
                        <div>
                            <h5>Aset HR &amp; GA</h5>
                            <p>Aset yang tidak dicentang akan tercetak sebagai tanda strip di PDF</p>
                        </div>
                    </div>
                    <div class="p-3 p-md-4">
                        <div class="row g-2 mb-3">
                            @foreach (\App\Support\ExitClearanceForm::GA_ASSETS as $key => $label)
                                @if ($key === 'other') @continue @endif
                                <div class="col-6 col-md-4">
                                    <label class="ec-option-label" for="ecGa{{ $key }}">
                                        <input class="form-check-input" type="checkbox" value="1"
                                            name="ga[{{ $key }}]" id="ecGa{{ $key }}">
                                        {{ $label }}
                                    </label>
                                </div>
                            @endforeach
                        </div>
                        <div>
                            <label class="form-label fw-semibold mb-1" style="font-size:13px" for="ecGaOther">Lainnya</label>
                            <input type="text" class="form-control form-control-sm" id="ecGaOther"
                                name="ga[other]" maxlength="180" placeholder="Sebutkan aset lainnya jika ada">
                        </div>
                    </div>
                </div>

                {{-- ── IT ───────────────────────────────────── --}}
                <div class="form-section">
                    <div class="form-section-header">
                        <i class="bi bi-laptop-fill"></i>
                        <div>
                            <h5>IT</h5>
                            <p>Akun, perangkat, dan akses yang dikembalikan ke tim IT</p>
                        </div>
                    </div>
                    <div class="p-3 p-md-4">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold mb-1" style="font-size:13px" for="ecItEmail">Email</label>
                                <input type="email" class="form-control form-control-sm" id="ecItEmail"
                                    name="it[email]" maxlength="160">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold mb-1" style="font-size:13px" for="ecItPassword">Password</label>
                                <input type="text" class="form-control form-control-sm" id="ecItPassword"
                                    name="it[password]" maxlength="120" autocomplete="off">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold mb-1" style="font-size:13px" for="ecItLaptop">Laptop</label>
                                <input type="text" class="form-control form-control-sm" id="ecItLaptop"
                                    name="it[laptop]" maxlength="180">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold mb-1" style="font-size:13px" for="ecItPhone">Handphone</label>
                                <input type="text" class="form-control form-control-sm" id="ecItPhone"
                                    name="it[phone]" maxlength="16" inputmode="numeric" placeholder="08xxxxxxxxxx">
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-semibold mb-1" style="font-size:13px" for="ecItOther">Lainnya</label>
                                <input type="text" class="form-control form-control-sm" id="ecItOther"
                                    name="it[other]" maxlength="180">
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ── Aset Divisi ──────────────────────────── --}}
                <div class="form-section">
                    <div class="form-section-header">
                        <i class="bi bi-boxes"></i>
                        <div>
                            <h5>Aset Divisi</h5>
                            <p>Perangkat dan akun yang dikembalikan ke divisi</p>
                        </div>
                    </div>
                    <div class="p-3 p-md-4">
                        <div class="row g-3">
                            @foreach (\App\Support\ExitClearanceForm::DIVISION_FIELDS as $key => $label)
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold mb-1" style="font-size:13px"
                                        for="ecDivision{{ $key }}">{{ $label }}</label>
                                    <input type="text" class="form-control form-control-sm"
                                        id="ecDivision{{ $key }}" name="division[{{ $key }}]" maxlength="180">
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                {{-- ── Accounting ───────────────────────────── --}}
                <div class="form-section">
                    <div class="form-section-header">
                        <i class="bi bi-cash-stack"></i>
                        <div>
                            <h5>Accounting</h5>
                            <p>Petty cash dan pinjaman yang diselesaikan sebelum keluar</p>
                        </div>
                    </div>
                    <div class="p-3 p-md-4">
                        <div class="row g-3 mb-3">
                            @foreach (['petty' => 'Petty Cash', 'loan' => 'Pinjaman'] as $moneyKey => $moneyLabel)
                                <div class="col-md-6">
                                    <div class="rounded-3 p-3 h-100" style="border:1px solid var(--color-border,#e5e7eb);background:#fafafa">
                                        <div class="fw-semibold mb-2" style="font-size:13px">{{ $moneyLabel }}</div>
                                        <div class="d-flex gap-3 mb-3">
                                            @foreach (\App\Support\ExitClearanceForm::MONEY_STATUSES as $status => $statusLabel)
                                                <label class="d-flex align-items-center gap-2" style="cursor:pointer;font-size:13px"
                                                    for="ec{{ $moneyKey }}{{ $status }}">
                                                    <input class="form-check-input m-0" type="radio"
                                                        name="{{ $moneyKey }}_status"
                                                        id="ec{{ $moneyKey }}{{ $status }}"
                                                        value="{{ $status }}">
                                                    {{ $statusLabel }}
                                                </label>
                                            @endforeach
                                        </div>
                                        <div class="mb-2">
                                            <label class="form-label mb-1" style="font-size:12px"
                                                for="ec{{ $moneyKey }}Amount">Nominal (Rp)</label>
                                            <input type="text" class="form-control form-control-sm"
                                                id="ec{{ $moneyKey }}Amount" name="{{ $moneyKey }}_amount"
                                                inputmode="numeric" maxlength="15" disabled>
                                        </div>
                                        <div>
                                            <label class="form-label mb-1" style="font-size:12px"
                                                for="ec{{ $moneyKey }}To">Telah diserahkan kepada</label>
                                            <input type="text" class="form-control form-control-sm"
                                                id="ec{{ $moneyKey }}To" name="{{ $moneyKey }}_to"
                                                maxlength="160" disabled>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <div>
                            <label class="form-label fw-semibold mb-1" style="font-size:13px" for="ecAccountingOther">Lainnya</label>
                            <input type="text" class="form-control form-control-sm" id="ecAccountingOther"
                                name="accounting_other" maxlength="300">
                        </div>
                    </div>
                </div>

                {{-- ── Approval ─────────────────────────────── --}}
                <div class="form-section">
                    <div class="form-section-header">
                        <i class="bi bi-pen-fill"></i>
                        <div>
                            <h5>Approval</h5>
                            <p>Nama dan tanggal tercetak di PDF - kotak tanda tangan dikosongkan untuk ditandatangani di kertas</p>
                        </div>
                    </div>
                    <div class="p-3 p-md-4">
                        <div class="row g-3">
                            @foreach (\App\Support\ExitClearanceForm::APPROVERS as $key => $approver)
                                <div class="col-md-6">
                                    <div class="rounded-3 p-3 h-100" style="border:1px solid var(--color-border,#e5e7eb)">
                                        <div class="fw-semibold" style="font-size:12.5px;color:var(--color-navy,#0b2540)">{{ $approver['title'] }}</div>
                                        <div class="mb-2" style="font-size:11px;color:var(--color-text-soft,#6b7280)">{{ $approver['hint'] }}</div>
                                        <label class="form-label mb-1" style="font-size:12px" for="ecApprover{{ $key }}Name">Nama</label>
                                        <input type="text" class="form-control form-control-sm mb-2"
                                            id="ecApprover{{ $key }}Name"
                                            name="approvers[{{ $key }}][name]" maxlength="120"
                                            inputmode="text" autocomplete="off">
                                        <label class="form-label mb-1" style="font-size:12px" for="ecApprover{{ $key }}Date">Tanggal</label>
                                        <input type="date" class="form-control form-control-sm"
                                            id="ecApprover{{ $key }}Date"
                                            name="approvers[{{ $key }}][date]">
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

            </fieldset>

            {{-- Footer actions --}}
            <div class="d-flex justify-content-between align-items-center pt-2 pb-4 gap-2">
                <button type="button" class="btn btn-outline-secondary" id="btnExitClearanceReset" style="font-size:13px">
                    <i class="bi bi-arrow-counterclockwise me-1"></i>Reset
                </button>
                <button type="submit" class="btn-submit d-flex align-items-center gap-2"
                    id="btnExitClearancePdf" disabled>
                    <i class="bi bi-file-earmark-pdf"></i>Unduh PDF
                </button>
            </div>

        </form>
    </div>

@endsection

@section('scripts')
<script nonce="{{ request()->attributes->get('csp_nonce') }}">
(function () {
    var employees = @json($ecEmployees);
    var form      = document.getElementById('formExitClearance');
    var search    = document.getElementById('ecEmpSearch');
    var searchWrap = document.getElementById('ecEmpSearchWrap');
    var dropdown  = document.getElementById('ecEmpDropdown');
    var clearBtn  = document.getElementById('ecEmpSearchClear');
    var preview   = document.getElementById('ecEmpPreview');
    var fields    = document.getElementById('ecFields');
    var submitBtn = document.getElementById('btnExitClearancePdf');
    var selected  = null;

    /* ── Helpers ────────────────────────────────── */
    function positionDropdown() {
        var rect = searchWrap.getBoundingClientRect();
        // position:fixed → koordinat viewport, tidak perlu scrollY/scrollX
        dropdown.style.top   = (rect.bottom + 4) + 'px';
        dropdown.style.left  = rect.left + 'px';
        dropdown.style.width = rect.width + 'px';
    }

    function lockScroll() {
        var scrollbarWidth = window.innerWidth - document.documentElement.clientWidth;
        document.body.style.overflow   = 'hidden';
        document.body.style.paddingRight = scrollbarWidth + 'px';
    }

    function unlockScroll() {
        document.body.style.overflow    = '';
        document.body.style.paddingRight = '';
    }

    function showDropdown() {
        positionDropdown();
        lockScroll();
        dropdown.style.display = 'block';
    }

    function hideDropdown() {
        dropdown.style.display = 'none';
        unlockScroll();
    }

    window.addEventListener('resize', function () {
        if (dropdown.style.display !== 'none') positionDropdown();
    });

    function textOrDash(v) {
        var t = (v || '').toString().trim();
        return t === '' ? '-' : t;
    }

    function toDateInput(v) {
        var t = (v || '').toString().trim();
        var iso = t.match(/^(\d{4}-\d{2}-\d{2})/);
        if (iso) return iso[1];
        var dmy = t.match(/^(\d{2})[\/\-](\d{2})[\/\-](\d{4})/);
        if (dmy) return dmy[3] + '-' + dmy[2] + '-' + dmy[1];
        return '';
    }

    function setText(id, v) {
        var el = document.getElementById(id);
        if (el) el.textContent = textOrDash(v);
    }

    function toast(msg) {
        if (typeof window.showToast === 'function') window.showToast(msg, 'error');
    }

    /* ── Money field toggle ─────────────────────── */
    function syncMoneyFields(key) {
        var checked = form.querySelector('input[name="' + key + '_status"]:checked');
        var on = !!(checked && checked.value === 'ada');
        ['Amount', 'To'].forEach(function (s) {
            var el = document.getElementById('ec' + key + s);
            if (!el) return;
            el.disabled = !on;
            if (!on) { el.value = ''; el.classList.remove('is-invalid'); }
        });
    }

    function formatNominal(v) {
        return (v || '').replace(/\D/g, '').slice(0, 12).replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    }

    ['petty', 'loan'].forEach(function (key) {
        form.querySelectorAll('input[name="' + key + '_status"]').forEach(function (r) {
            r.addEventListener('change', function () { syncMoneyFields(key); });
        });
        var amt = document.getElementById('ec' + key + 'Amount');
        if (amt) amt.addEventListener('input', function () {
            amt.value = formatNominal(amt.value);
            amt.classList.remove('is-invalid');
        });
        var rcv = document.getElementById('ec' + key + 'To');
        if (rcv) rcv.addEventListener('input', function () { rcv.classList.remove('is-invalid'); });
        syncMoneyFields(key);
    });

    /* ── Input cleanup ──────────────────────────── */
    var emailInput = document.getElementById('ecItEmail');
    if (emailInput) emailInput.addEventListener('input', function () {
        emailInput.classList.remove('is-invalid');
    });

    var phoneInput = document.getElementById('ecItPhone');
    if (phoneInput) phoneInput.addEventListener('input', function () {
        var plus = phoneInput.value.charAt(0) === '+';
        phoneInput.value = (plus ? '+' : '') + phoneInput.value.replace(/\D/g, '').slice(0, 15);
        phoneInput.classList.remove('is-invalid');
    });

    /* ── Reset ──────────────────────────────────── */
    function resetForm() {
        selected = null;
        form.reset();
        form.action = '#';
        fields.disabled = true;
        submitBtn.disabled = true;
        preview.style.display = 'none';
        hideDropdown();
        clearBtn.style.display = 'none';
        search.value = '';
        syncMoneyFields('petty');
        syncMoneyFields('loan');
    }

    /* ── Select employee ────────────────────────── */
    function selectEmployee(emp) {
        form.reset();
        search.value = (emp.fullName || '') + (emp.employeeId ? ' (' + emp.employeeId + ')' : '');
        clearBtn.style.display = 'block';
        hideDropdown();
        selected = emp;
        form.action = form.getAttribute('data-action').replace('__ID__', encodeURIComponent(emp.employeeId || ''));
        fields.disabled = false;
        submitBtn.disabled = false;

        setText('ecEmpName', emp.fullName);
        setText('ecEmpPosition', emp.jobPosition);
        setText('ecEmpDivision', emp.division);
        document.getElementById('ecEmpAvatar').textContent = (emp.fullName || 'K').substring(0, 2).toUpperCase();
        preview.style.display = 'block';

        if (emp.company) {
            var radio = form.querySelector('input[name="company"][value="' + emp.company + '"]');
            if (radio) radio.checked = true;
        }
        // resignDate, workingEmail, directSuperior tidak di-expose di client -
        // field-field ini diisi server saat PDF di-generate dari findById()
        syncMoneyFields('petty');
        syncMoneyFields('loan');
    }

    /* ── Render dropdown ────────────────────────── */
    function renderDropdown(list) {
        dropdown.textContent = '';
        if (!list.length) {
            var empty = document.createElement('div');
            empty.className = 'p-3 text-center ec-search-empty';
            empty.textContent = 'Nama tidak ditemukan. Coba kata kunci lain.';
            dropdown.appendChild(empty);
            showDropdown();
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
            item.addEventListener('click', function () { selectEmployee(emp); });
            dropdown.appendChild(item);
        });
        showDropdown();
    }

    /* ── Search events ──────────────────────────── */
    search.addEventListener('keydown', function (e) { if (e.key === 'Enter') e.preventDefault(); });
    search.addEventListener('input', function () {
        var q = search.value.toLowerCase().trim();
        clearBtn.style.display = q ? 'block' : 'none';
        if (!q) { hideDropdown(); return; }
        var matched = (employees || []).filter(function (emp) {
            return (emp.fullName || '').toLowerCase().indexOf(q) !== -1
                || (emp.employeeId || '').toLowerCase().indexOf(q) !== -1
                || (emp.jobPosition || '').toLowerCase().indexOf(q) !== -1;
        }).slice(0, 8);
        renderDropdown(matched);
    });
    clearBtn.addEventListener('click', resetForm);

    // Tutup dropdown + unlock scroll saat klik di luar
    document.addEventListener('click', function (e) {
        if (dropdown.style.display === 'none') return;
        if (!searchWrap.contains(e.target) && !dropdown.contains(e.target)) {
            hideDropdown();
        }
    });

    /* ── Submit validation ──────────────────────── */
    // Regex nama orang — konsisten dengan NAME_REGEX di ExitClearanceForm.php
    // Huruf Unicode, apostrof, titik (gelar); dipisah spasi
    var nameRegex = /^[\p{L}'.]+(?:[ ]+[\p{L}'.]+)*$/u;

    function validateName(el, label) {
        var val = (el.value || '').trim();
        if (val === '') return true; // nullable — kosong diizinkan
        if (val.length < 2) {
            el.classList.add('is-invalid');
            toast(label + ' minimal 2 karakter.'); return false;
        }
        if (!nameRegex.test(val)) {
            el.classList.add('is-invalid');
            toast(label + ' hanya boleh berisi huruf dan spasi.'); return false;
        }
        return true;
    }

    // Strip karakter non-nama secara realtime (angka, simbol, dll)
    // Hanya izinkan huruf Unicode, apostrof, titik, dan spasi
    function enforceNameInput(el) {
        el.addEventListener('input', function () {
            var pos   = el.selectionStart;
            var raw   = el.value;
            // Buang semua karakter selain huruf Unicode, apostrof, titik, spasi
            var clean = raw.replace(/[^\p{L}'. ]/gu, '');
            // Cegah spasi berturutan dan spasi di awal
            clean = clean.replace(/  +/g, ' ').replace(/^ /, '');
            if (clean !== raw) {
                el.value = clean;
                // Pertahankan posisi cursor setelah strip
                var diff = raw.length - clean.length;
                el.setSelectionRange(Math.max(0, pos - diff), Math.max(0, pos - diff));
            }
            el.classList.remove('is-invalid');
        });
    }

    var delegateInput = document.getElementById('ecDelegate');
    if (delegateInput) enforceNameInput(delegateInput);

    @foreach (\App\Support\ExitClearanceForm::APPROVERS as $key => $approver)
    (function () {
        var el = document.getElementById('ecApprover{{ $key }}Name');
        if (el) enforceNameInput(el);
    })();
    @endforeach

    form.addEventListener('submit', function (e) {
        if (!selected || !selected.employeeId) {
            e.preventDefault(); toast('Ketik nama Anda dan pilih dari daftar terlebih dahulu.'); return;
        }
        if (!form.querySelector('input[name="company"]:checked')) {
            e.preventDefault(); toast('Pilih perusahaan.'); return;
        }
        if (!document.getElementById('ecLastDate').value) {
            e.preventDefault(); toast('Tanggal berakhir bekerja wajib diisi.'); return;
        }

        // Validasi nama — delegasi & semua approver
        if (!validateName(delegateInput, 'Nama penerima delegasi')) { e.preventDefault(); return; }
        @foreach (\App\Support\ExitClearanceForm::APPROVERS as $key => $approver)
        if (!validateName(document.getElementById('ecApprover{{ $key }}Name'), "Nama {{ $approver['hint'] }}")) { e.preventDefault(); return; }
        @endforeach

        var email = (emailInput.value || '').trim();
        if (email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
            e.preventDefault();
            emailInput.classList.add('is-invalid');
            toast('Format email tidak valid.'); return;
        }
        var phone = (phoneInput.value || '').trim();
        if (phone && !/^\+?[0-9]{8,15}$/.test(phone)) {
            e.preventDefault();
            phoneInput.classList.add('is-invalid');
            toast('Nomor handphone harus 8–15 digit.'); return;
        }
        var moneyLabels = { petty: 'petty cash', loan: 'pinjaman' };
        var blocked = false;
        Object.keys(moneyLabels).forEach(function (key) {
            if (blocked) return;
            var sel = form.querySelector('input[name="' + key + '_status"]:checked');
            if (!sel || sel.value !== 'ada') return;
            var amt = document.getElementById('ec' + key + 'Amount');
            var rcv = document.getElementById('ec' + key + 'To');
            if (!amt.value.replace(/\D/g, '')) {
                blocked = true; e.preventDefault();
                amt.classList.add('is-invalid');
                toast('Nominal ' + moneyLabels[key] + ' wajib diisi jika status Ada.'); return;
            }
            if (!(rcv.value || '').trim()) {
                blocked = true; e.preventDefault();
                rcv.classList.add('is-invalid');
                toast('Penerima ' + moneyLabels[key] + ' wajib diisi jika status Ada.');
            }
        });
    });

    document.getElementById('btnExitClearanceReset').addEventListener('click', resetForm);
})();
</script>
@endsection
