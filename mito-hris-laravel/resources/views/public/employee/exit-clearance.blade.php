@extends('layouts.public')

@section('title', 'Exit Clearance')
@section('description', 'Formulir exit clearance MITO. Cetak PDF untuk keperluan proses keluar Anda.')
@section('robots', 'noindex, nofollow, noarchive')

@section('styles')
<style>

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

        <form id="formExitClearance" method="POST" target="_blank"
            action="{{ route('public.exit-clearance.pdf') }}" autocomplete="off">
            @csrf

            {{-- ── Banner hint ─────────────────────────────── --}}
            <div class="d-flex align-items-start gap-2 rounded-3 p-3 mb-3"
                style="background:#f0f9ff;border:1px solid #bae6fd;font-size:13px;color:#0369a1">
                <i class="bi bi-info-circle-fill flex-shrink-0" style="color:#38bdf8;margin-top:1px"></i>
                <span>
                    Field bertanda <strong class="text-danger">*</strong> wajib diisi.
                    Kolom lainnya <strong>opsional</strong> - kosongkan saja jika tidak relevan,
                    dan akan tercetak sebagai tanda strip (<strong>–</strong>) di PDF.
                </span>
            </div>

            {{-- ── Identitas Anda ─────────────────────────── --}}
            <div class="form-section">
                <div class="form-section-header">
                    <i class="bi bi-person-vcard-fill"></i>
                    <div>
                        <h5>Identitas Anda</h5>
                        <p>Isi data diri Anda sesuai dokumen kepegawaian</p>
                    </div>
                </div>
                <div class="p-3 p-md-4">
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold mb-1" style="font-size:13px" for="ecFullName">
                                Nama Lengkap <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control form-control-sm" id="ecFullName"
                                name="full_name" maxlength="160" inputmode="text" autocomplete="name"
                                placeholder="Sesuai KTP / dokumen kepegawaian" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold mb-1" style="font-size:13px" for="ecNik">
                                NIK <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control form-control-sm" id="ecNik"
                                name="nik" maxlength="20" inputmode="numeric" autocomplete="off"
                                placeholder="Nomor Induk Kependudukan" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold mb-1" style="font-size:13px" for="ecEmployeeId">
                                Employee ID <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control form-control-sm" id="ecEmployeeId"
                                name="employee_id" maxlength="30" autocomplete="off"
                                placeholder="Contoh: 20210301" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold mb-1" style="font-size:13px" for="ecPosition">
                                Posisi / Jabatan <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control form-control-sm" id="ecPosition"
                                name="position" maxlength="160" autocomplete="off" required
                                placeholder="Contoh: Staff Finance">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold mb-1" style="font-size:13px" for="ecDivision">
                                Divisi / Departemen <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control form-control-sm" id="ecDivision"
                                name="division" maxlength="160" autocomplete="off" required
                                placeholder="Contoh: Finance & Accounting">
                        </div>
                    </div>

                    {{-- Perusahaan --}}
                    <div class="mb-4">
                        <div class="form-label fw-semibold mb-2" style="font-size:13px">
                            Perusahaan <span class="text-danger">*</span>
                        </div>
                        <div class="row g-2">
                            @foreach (\App\Support\ExitClearanceForm::COMPANIES as $code => $name)
                                <div class="col-md-6">
                                    <label class="ec-option-label" for="ecCompany{{ $code }}">
                                        <input class="form-check-input" type="radio" name="company"
                                            id="ecCompany{{ $code }}" value="{{ $code }}" required>
                                        {{ $name }}
                                    </label>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- Tanggal --}}
                    <div>
                        <label class="form-label fw-semibold mb-1" style="font-size:13px" for="ecLastDate">
                            Hari/Tanggal Berakhir Bekerja <span class="text-danger">*</span>
                        </label>
                        <input type="date" class="form-control form-control-sm" id="ecLastDate"
                            name="last_working_date" style="max-width:220px" required>
                    </div>
                </div>
            </div>

            <fieldset id="ecFields">

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
                                        id="ecDivision{{ $key }}" name="division_assets[{{ $key }}]" maxlength="180">
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
                    id="btnExitClearancePdf">
                    <i class="bi bi-file-earmark-pdf"></i>Unduh PDF
                </button>
            </div>

        </form>
    </div>
