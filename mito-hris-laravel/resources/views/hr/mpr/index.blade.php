@extends('layouts.hr')

@section('title', 'Daftar Manpower Request (MPR) - MITO HRIS')
@section('page-title', 'Daftar Manpower Request (MPR)')
@section('page-subtitle',
    $isManager
    ? 'Pengajuan dan monitoring kebutuhan tenaga kerja'
    : 'Manajemen permintaan
    kebutuhan manpower dari departemen')

@section('styles')
    <style>
        /* MPR-specific styles only for Manager form section */
        .mpr-card-header {
            background: var(--color-primary);
            color: var(--color-surface);
            border-radius: 8px 8px 0 0;
            padding: 16px 20px;
        }

        .mpr-form-card {
            background: var(--color-surface);
            border-color: var(--color-border);
        }

        .mpr-form-card .card-body {
            background: var(--color-surface);
        }

        /* Manager form section */
        .mpr-form-section-title {
            color: var(--color-primary);
        }

        .mpr-form-card .form-label {
            color: var(--color-text);
        }

        .mpr-form-card .form-control,
        .mpr-form-card .form-select {
            background: var(--color-surface);
            border-color: var(--color-border);
            color: var(--color-text);
        }

        .mpr-form-card .form-control:focus,
        .mpr-form-card .form-select:focus {
            background: var(--color-surface);
            border-color: var(--color-primary);
            color: var(--color-text);
        }

        .mpr-form-card .form-control:disabled,
        .mpr-form-card .form-select:disabled {
            background: var(--color-bg);
            color: var(--color-text-soft);
        }

        .mpr-form-card .border {
            border-color: var(--color-border) !important;
        }

        .mpr-form-card .rounded-3 {
            background: var(--color-bg);
        }

        .mpr-form-card .badge.bg-white {
            background: var(--color-surface) !important;
            border: 1px solid var(--color-border);
        }

        /* Manager history card */
        .mpr-history-card {
            background: var(--color-surface);
            border-color: var(--color-border);
        }

        /* Modal Create MPR (HR) styles */
        #modalCreateMpr .modal-content {
            background: var(--color-surface);
        }

        #modalCreateMpr .modal-header {
            background: var(--color-primary) !important;
        }

        #modalCreateMpr .modal-body {
            background: var(--color-surface);
        }

        #modalCreateMpr .form-label {
            color: var(--color-text);
        }

        #modalCreateMpr .form-control,
        #modalCreateMpr .form-select {
            background: var(--color-surface);
            border-color: var(--color-border);
            color: var(--color-text);
        }

        #modalCreateMpr .form-control:focus,
        #modalCreateMpr .form-select:focus {
            background: var(--color-surface);
            border-color: var(--color-primary);
            color: var(--color-text);
        }

        #modalCreateMpr .border {
            border-color: var(--color-border) !important;
        }

        #modalCreateMpr .form-check-input {
            background-color: var(--color-surface);
            border-color: var(--color-border);
        }

        #modalCreateMpr .form-check-input:checked {
            background-color: var(--color-primary);
            border-color: var(--color-primary);
        }

        #modalCreateMpr .form-check-label {
            color: var(--color-text);
        }

        #modalCreateMpr .form-text {
            color: var(--color-text-soft);
        }

        /* Modal Edit MPR styles */
        #modalEditMpr .modal-content {
            background: var(--color-surface);
        }

        #modalEditMpr .modal-header {
            background: var(--color-primary) !important;
        }

        #modalEditMpr .modal-body {
            background: var(--color-surface);
        }

        #modalEditMpr .form-label {
            color: var(--color-text);
        }

        #modalEditMpr .form-control,
        #modalEditMpr .form-select {
            background: var(--color-surface);
            border-color: var(--color-border);
            color: var(--color-text);
        }

        #modalEditMpr .form-control:focus,
        #modalEditMpr .form-select:focus {
            background: var(--color-surface);
            border-color: var(--color-primary);
            color: var(--color-text);
        }

        #modalEditMpr .border {
            border-color: var(--color-border) !important;
        }

        #modalEditMpr .form-check-input {
            background-color: var(--color-surface);
            border-color: var(--color-border);
        }

        #modalEditMpr .form-check-input:checked {
            background-color: var(--color-primary);
            border-color: var(--color-primary);
        }

        #modalEditMpr .form-check-label {
            color: var(--color-text);
        }

        /* Alert styles */
        .alert {
            background: var(--color-surface);
            border-color: var(--color-border);
            color: var(--color-text);
        }

        .alert-success {
            background: rgba(22, 101, 52, 0.1);
            border-color: rgba(22, 101, 52, 0.2);
            color: var(--color-text);
        }

        .alert-success .text-success {
            color: #166534 !important;
        }

        :root[data-theme="dark"] .alert-success {
            background: rgba(22, 101, 52, 0.15);
            border-color: rgba(22, 101, 52, 0.25);
        }

        :root[data-theme="dark"] .alert-success .text-success {
            color: #4ade80 !important;
        }

        /* Table empty state */
        .table-empty {
            color: var(--color-text-soft);
        }

        .mpr-applicant-identity {
            background: var(--color-bg) !important;
            border-color: var(--color-border) !important;
        }

        .mpr-history-header {
            background: var(--color-surface) !important;
            border-color: var(--color-border) !important;
        }

        .mpr-history-header h6 {
            color: var(--color-text) !important;
        }

        .mpr-applicant-identity .input-group-text,
        .mpr-applicant-identity .form-control {
            background: var(--color-surface) !important;
            border-color: var(--color-border) !important;
            color: var(--color-text) !important;
        }

        .mpr-applicant-identity .text-muted {
            color: var(--color-text-soft) !important;
        }

        /* Modal MPR Detail styles */
        #modalMprDetail .modal-content {
            background: var(--color-surface);
            border-radius: 12px;
            overflow: hidden;
        }

        #modalMprDetail .modal-header {
            padding: 20px 24px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
        }

        #modalMprDetail .modal-body {
            background: var(--color-bg);
            max-height: calc(100vh - 200px);
        }

        #modalMprDetail .modal-body .border-bottom {
            border-color: var(--color-border) !important;
        }

        #modalMprDetail .modal-footer {
            background: var(--color-surface);
            border-top: 1px solid var(--color-border);
            padding: 14px 24px;
        }

        #modalMprDetail label {
            color: var(--color-text-soft);
            font-size: 11px;
            letter-spacing: 0.5px;
            margin-bottom: 6px;
        }

        #modalMprDetail .fw-semibold,
        #modalMprDetail .fw-bold {
            color: var(--color-text);
        }

        #modalMprDetail .text-primary {
            color: var(--color-primary) !important;
        }

        #modalMprDetail h6.text-primary {
            font-size: 15px;
            letter-spacing: 0.3px;
        }

        #modalMprDetail .mpr-markdown-content {
            background: var(--color-surface);
            border: 1px solid var(--color-border);
            color: var(--color-text);
        }

        #modalMprDetail .badge {
            font-weight: 600;
        }

        #modalMprDetail .border-top.border-dark {
            border-color: var(--color-text) !important;
        }

        /* Markdown-rendered content in MPR detail modal */
        .mpr-markdown-content p {
            margin: 0 0 8px;
        }

        .mpr-markdown-content p:last-child {
            margin-bottom: 0;
        }

        .mpr-markdown-content h1,
        .mpr-markdown-content h2,
        .mpr-markdown-content h3,
        .mpr-markdown-content h4,
        .mpr-markdown-content h5,
        .mpr-markdown-content h6 {
            margin-top: 8px;
            margin-bottom: 6px;
            font-weight: 600;
        }

        .mpr-markdown-content ul,
        .mpr-markdown-content ol {
            margin-top: 4px;
            margin-bottom: 8px;
            padding-left: 22px;
        }

        .mpr-markdown-content li {
            margin-bottom: 3px;
        }

        .mpr-markdown-content strong {
            font-weight: 700;
        }

        .mpr-markdown-content em {
            font-style: italic;
        }

        /* Manager form card responsive height */
        @media (min-width: 1200px) {
            .mpr-form-card {
                height: calc(100vh - 132px);
            }

            .mpr-form-card .mpr-form-body {
                min-height: 0;
                overflow-y: auto;
            }
        }

        @media (max-width: 767.98px) {
            .mpr-card-header {
                padding: 14px 16px;
            }
        }
    </style>
@endsection

@section('content')
    <div class="container-fluid px-0">

        <!-- ALERT SUCCESS WITH DIRECT PDF DOWNLOAD (Auto PDF Notification) -->
        <div id="mprSuccessAlert" class="alert alert-success alert-dismissible fade d-none shadow-sm" role="alert">
            <div class="d-flex align-items-center">
                <i class="bi bi-check-circle-fill fs-4 me-3 text-success"></i>
                <div>
                    <h6 class="alert-heading mb-1 fw-bold" id="mprSuccessTitle">Pengajuan MPR Berhasil Dibuat!</h6>
                    <p class="mb-0" id="mprSuccessBody">Dokumen PDF resmi telah digenerate secara otomatis.</p>
                </div>
                <div class="ms-auto d-flex gap-2">
                    <a id="btnAlertOpenPdf" href="#" target="_blank" class="btn btn-sm btn-success fw-semibold">
                        <i class="bi bi-file-earmark-pdf-fill me-1"></i> Buka PDF MPR
                    </a>
                </div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>

        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
                <div class="d-flex align-items-center">
                    <i class="bi bi-check-circle-fill fs-4 me-3 text-success"></i>
                    <div>
                        <h6 class="alert-heading mb-1 fw-bold">Berhasil</h6>
                        <p class="mb-0">{{ session('success') }}</p>
                    </div>
                    @if (session('mpr_pdf_url'))
                        <div class="ms-auto">
                            <a href="{{ session('mpr_pdf_url') }}" target="_blank"
                                class="btn btn-sm btn-success fw-semibold">
                                <i class="bi bi-file-earmark-pdf-fill me-1"></i> Unduh PDF
                            </a>
                        </div>
                    @endif
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if ($isManager)
            {{-- ========================================================================= --}}
            {{-- MANAGER EXPERIENCE: SUBMISSION FORM + MY REQUESTS TABLE                   --}}
            {{-- ========================================================================= --}}
            <div class="row g-4 mb-4">

                <!-- FORM CARD: Pengajuan MPR Baru -->
                <div class="col-12 col-xl-7">
                    <div class="card border-0 shadow-sm rounded-3 mpr-form-card">
                        <div class="mpr-card-header d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="mb-1 fw-bold text-white"><i class="bi bi-file-earmark-plus-fill me-2"></i>
                                    Formulir Pengajuan Manpower Request</h5>
                                <small class="text-white">Isi data kebutuhan tenaga kerja untuk diproses oleh Tim
                                    HR</small>
                            </div>
                            <span class="badge text-primary fw-bold px-3 py-2" style="background: var(--color-surface); border: 1px solid var(--color-border);">Role: Manpower</span>
                        </div>
                        <div class="card-body p-4 mpr-form-body">

                            <form id="formManagerMpr" action="{{ route('hr.mpr.store') }}" method="POST">
                                @csrf
                                @php $mprOptions = config('hris.mpr_form_options', []); @endphp

                                <!-- SECTION: IDENTITAS PEMOHON (READONLY & DISABLED - ANTI FRAUD) -->
                                <div class="mpr-applicant-identity p-3 rounded-3 mb-4 border">
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <span class="text-primary fw-bold small"><i class="bi bi-shield-lock-fill me-1"></i>
                                            Identitas Pemohon (Terverifikasi Sistem)</span>
                                        <span class="badge bg-secondary-subtle text-secondary border"><i
                                                class="bi bi-lock-fill me-1"></i> Terkunci Permanen</span>
                                    </div>
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label text-muted small fw-semibold mb-1">Nama Pemohon</label>
                                            <div class="input-group input-group-sm">
                                                <span class="input-group-text border-end-0"><i class="bi bi-person-fill"></i></span>
                                                <input type="text" class="form-control border-start-0 fw-semibold"
                                                    value="{{ $user['fullName'] ?? ($user['name'] ?? 'Manager') }}" readonly
                                                    disabled style="cursor: not-allowed;">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label text-muted small fw-semibold mb-1">Jabatan Pemohon</label>
                                            <div class="input-group input-group-sm">
                                                <span class="input-group-text border-end-0"><i class="bi bi-briefcase-fill"></i></span>
                                                <input type="text" class="form-control border-start-0 fw-semibold"
                                                    value="{{ $user['jobPosition'] ?? '-' }}" readonly
                                                    disabled style="cursor: not-allowed;">
                                            </div>
                                            <input type="hidden" name="requestor_position" value="{{ $user['jobPosition'] ?? '' }}">
                                        </div>
                                    </div>
                                    <div class="mt-2 text-muted" style="font-size: 11px;">
                                        <i class="bi bi-shield-check-fill text-success me-1"></i>
                                        Identitas dan jabatan pemohon diambil otomatis dari sesi akun login Anda.
                                    </div>
                                </div>

                                <!-- SECTION: DETAIL POSISI -->
                                <h6 class="fw-bold text-primary mb-3"><i class="bi bi-briefcase-fill me-2"></i>Detail
                                    Posisi yang Dibutuhkan</h6>
                                <div class="row g-3 mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold small">Entitas / Perusahaan yang Dituju <span
                                                class="text-danger">*</span></label>
                                        <select name="entity" class="form-select" required>
                                            <option value="">-- Pilih Entitas --</option>
                                            @foreach ($entityOptions as $code => $label)
                                                <option value="{{ $code }}">{{ $code }} - {{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold small">Posisi / Nama Jabatan yang Diminta <span
                                                class="text-danger">*</span></label>
                                        <input type="text" name="position" class="form-control"
                                            placeholder="Contoh: Frontend Developer, Sales Executive" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold small">Departemen <span
                                                class="text-danger">*</span></label>
                                        <select name="department" class="form-select" required>
                                            <option value="">-- Pilih Departemen --</option>
                                            @foreach ($departments as $d)
                                                <option value="{{ $d }}"
                                                    {{ old('department') === $d ? 'selected' : '' }}>{{ $d }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold small">Divisi <span
                                                class="text-danger">*</span></label>
                                        <select name="division" class="form-select"
                                            data-selected-division="{{ old('division') }}" required>
                                            <option value="">-- Pilih Departemen terlebih dahulu --</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold small">Disetujui oleh (Divisi) <span
                                                class="text-danger">*</span></label>
                                        <select name="approval_division" class="form-select" required>
                                            <option value="">-- Pilih Divisi --</option>
                                            @foreach (config('hris.mpr.approval_divisions', []) as $division)
                                                <option value="{{ $division }}">{{ $division }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold small">Level Jabatan <span
                                                class="text-danger">*</span></label>
                                        <select name="job_level" class="form-select" required>
                                            <option value="">-- Pilih Level Jabatan --</option>
                                            @foreach ($jobLevels as $jl)
                                                <option value="{{ $jl }}">{{ $jl }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold small">Lokasi Kerja Penempatan <span
                                                class="text-danger">*</span></label>
                                        <select name="work_location" class="form-select" required>
                                            <option value="">-- Pilih Lokasi Kerja --</option>
                                            @foreach ($workLocations as $wl)
                                                <option value="{{ $wl }}">{{ $wl }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold small">Status Kepegawaian <span
                                                class="text-danger">*</span></label>
                                        <select name="employment_type" class="form-select" required>
                                            <option value="">-- Pilih Status Kepegawaian --</option>
                                            @foreach ($employmentTypes as $et)
                                                <option value="{{ $et }}">{{ $et }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold small">Jumlah Kebutuhan (Orang) <span
                                                class="text-danger">*</span></label>
                                        <input type="number" name="quantity" class="form-control" value="1"
                                            min="1" max="100" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold small">Target Tanggal Bergabung (Join Date)
                                            <span class="text-danger">*</span></label>
                                        <input type="date" name="expected_join_date" class="form-control"
                                            min="{{ date('Y-m-d') }}" required>
                                    </div>
                                </div>

                                <!-- SECTION: JADWAL KERJA & FASILITAS -->
                                <h6 class="fw-bold text-primary mb-3 mt-4"><i class="bi bi-clock-fill me-2"></i>Jadwal Kerja &amp; Fasilitas</h6>
                                <div class="row g-3 mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold small">Hari Kerja <span class="text-danger">*</span></label>
                                        <div class="border rounded p-2 d-flex flex-column gap-1">
                                            @foreach ($mprOptions['working_days'] ?? [] as $dayKey => $dayLabel)
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="working_days[]"
                                                        value="{{ $dayKey }}" id="mgwd_{{ $dayKey }}">
                                                    <label class="form-check-label small" for="mgwd_{{ $dayKey }}">{{ $dayLabel }}</label>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold small">Jam Kerja <span class="text-danger">*</span></label>
                                        <div class="border rounded p-2 d-flex flex-column gap-1">
                                            @foreach ($mprOptions['working_hours'] ?? [] as $hourKey => $hourLabel)
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="working_hours[]"
                                                        value="{{ $hourKey }}" id="mgwh_{{ $hourKey }}">
                                                    <label class="form-check-label small" for="mgwh_{{ $hourKey }}">{{ $hourLabel }}</label>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label fw-semibold small">
                                            Detail Shift
                                            <span class="text-danger d-none" id="mgShiftDetailRequiredMark">*</span>
                                            <span class="text-muted fw-normal small" id="mgShiftDetailHint">(aktif &amp; wajib jika "Shifting" dipilih)</span>
                                        </label>
                                        <textarea name="shift_detail" class="form-control" rows="2"
                                            id="mgShiftDetailField"
                                            placeholder="Contoh: Shift pagi 07:00-15:00, shift siang 15:00-23:00, rotasi mingguan..."
                                            disabled></textarea>
                                        <div class="form-text text-muted small mt-1" id="mgShiftDetailNote">
                                            <i class="bi bi-info-circle me-1"></i>Centang "Shifting" pada Hari Kerja untuk mengisi detail shift.
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label fw-semibold small">Benefits / Tunjangan <span class="text-danger">*</span></label>
                                        <div class="border rounded p-2 row g-1">
                                            @foreach ($mprOptions['benefits'] ?? [] as $benefitKey => $benefitLabel)
                                                <div class="col-md-4 col-6">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" name="benefits[]"
                                                            value="{{ $benefitKey }}" id="mgbn_{{ $benefitKey }}">
                                                        <label class="form-check-label small" for="mgbn_{{ $benefitKey }}">{{ $benefitLabel }}</label>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>

                                <!-- SECTION: ALASAN & KUALIFIKASI -->
                                <h6 class="fw-bold text-primary mb-3 mt-4"><i
                                        class="bi bi-question-circle-fill me-2"></i>Alasan &amp; Kualifikasi</h6>
                                <div class="row g-3 mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold small">Alasan Permintaan <span
                                                class="text-danger">*</span></label>
                                        <select name="reason" class="form-select" required>
                                            <option value="">-- Pilih Alasan Permintaan --</option>
                                            @foreach ($reasons as $r)
                                                <option value="{{ $r }}">{{ $r }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold small">
                                            Nama Karyawan yang Digantikan
                                            <span class="text-danger d-none" id="mgReplacementForRequiredMark">*</span>
                                            <span class="text-muted fw-normal small" id="mgReplacementForHint">(aktif jika alasan "Penggantian Karyawan")</span>
                                        </label>
                                        <input type="text" name="replacement_for" class="form-control"
                                            id="mgReplacementForField"
                                            placeholder="Nama karyawan yang akan digantikan"
                                            disabled>
                                        <div class="form-text text-muted small mt-1" id="mgReplacementForNote">
                                            <i class="bi bi-info-circle me-1"></i>Pilih alasan "Penggantian Karyawan Resign / Mutasi / Demosi" untuk mengisi field ini.
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold small">Latar Belakang Pendidikan <span class="text-danger">*</span></label>
                                        <select name="education_background" class="form-select" required>
                                            <option value="">-- Pilih Pendidikan --</option>
                                            @foreach ($mprOptions['education_background'] ?? [] as $eduKey => $eduLabel)
                                                <option value="{{ $eduKey }}">{{ $eduLabel }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold small">Pengalaman Kerja <span class="text-danger">*</span></label>
                                        <select name="work_experience" class="form-select" required>
                                            <option value="">-- Pilih Pengalaman --</option>
                                            @foreach ($mprOptions['work_experience'] ?? [] as $expKey => $expLabel)
                                                <option value="{{ $expKey }}">{{ $expLabel }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold small">Skills &amp; Kompetensi</label>
                                        <textarea name="skills_competencies" class="form-control" rows="2"
                                            placeholder="Contoh: Laravel, Excel lanjutan, leadership..."></textarea>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold small">Bahasa yang Dikuasai</label>
                                        <textarea name="languages" class="form-control" rows="2"
                                            placeholder="Contoh: Bahasa Indonesia (aktif), Bahasa Inggris (pasif)..."></textarea>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label fw-semibold small">Kualifikasi &amp; Persyaratan Khusus Kandidat</label>
                                        <textarea name="requirements" class="form-control" rows="3"
                                            placeholder="Contoh: Pendidikan min. S1 Informatika, pengalaman min. 2 tahun di Laravel, komunikasi baik..."></textarea>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold small">Uraian Tugas &amp; Tanggung Jawab Utama</label>
                                        <textarea name="job_description" class="form-control" rows="4"
                                            placeholder="Contoh: Mengembangkan fitur web HRIS, melakukan code review..."></textarea>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold small">Key Results / Target Posisi Ini</label>
                                        <textarea name="key_results_targets" class="form-control" rows="4"
                                            placeholder="Contoh: Mencapai target penjualan 100 unit/bulan..."></textarea>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label fw-semibold small">Referensi Industri Sejenis</label>
                                        <textarea name="industry_reference" class="form-control" rows="2"
                                            placeholder="Contoh: Pengalaman dari industri mining, logistik, atau FMCG..."></textarea>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label fw-semibold small">Catatan Khusus MPR <span class="text-muted fw-normal">(Opsional)</span></label>
                                        <textarea name="special_notes" class="form-control" rows="2"
                                            placeholder="Catatan khusus terkait kebutuhan ini..."></textarea>
                                    </div>
                                </div>

                                <hr class="my-4">

                                <div class="d-flex justify-content-end gap-2">
                                    <button type="reset" class="btn btn-outline-secondary px-3">Reset Form</button>
                                    <button type="submit" id="btnSubmitMprManager"
                                        class="btn btn-primary px-4 fw-semibold shadow-sm">
                                        <span class="spinner-border spinner-border-sm me-1 d-none"
                                            id="spinnerSubmitManager"></span>
                                        <i class="bi bi-send-fill me-1" id="iconSubmitManager"></i> Kirim Pengajuan &
                                        Generate PDF
                                    </button>
                                </div>

                            </form>
                        </div>
                    </div>
                </div>

                <!-- SIDE SUMMARY: Ringkasan Pengajuan Saya -->
                <div class="col-12 col-xl-5">
                    <div class="card border-0 shadow-sm rounded-3 h-100 mpr-history-card">
                        <div class="card-header mpr-history-header py-3 border-bottom">
                            <h6 class="mb-0 fw-bold"><i class="bi bi-clock-history me-2 text-primary"></i>
                                Riwayat Pengajuan MPR Saya</h6>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table hr-table">
                                    <thead>
                                        <tr>
                                            <th>No. MPR</th>
                                            <th>Posisi & Dept</th>
                                            <th>Kebutuhan</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody id="managerMprTableBody">
                                        @forelse($paginatedMprs as $mpr)
                                            <tr>
                                                <td>
                                                    <strong class="text-primary">{{ $mpr->mprNumber }}</strong><br>
                                                    <small class="text-muted">{{ $mpr->requestDate ? \Carbon\Carbon::parse($mpr->requestDate)->format('d M Y') : '-' }}</small>
                                                </td>
                                                <td>
                                                    <span class="fw-semibold">{{ $mpr->position }}</span><br>
                                                    <small class="text-muted">{{ $mpr->department }}</small><br>
                                                    <small class="badge bg-light text-secondary border"
                                                        style="font-size:10px;">{{ $mpr->entity ?: '-' }}</small>
                                                </td>
                                                <td>
                                                    <span class="fw-bold text-navy">{{ $mpr->quantity }}</span>
                                                </td>
                                                <td>
                                                    <div class="btn-group btn-group-sm">
                                                        <button type="button"
                                                            class="btn btn-outline-primary btn-mpr-detail"
                                                            data-id="{{ $mpr->mprNumber }}" title="Lihat Detail">
                                                            <i class="bi bi-eye"></i>
                                                        </button>
                                                        @if (filled($mpr->mprNumber))
                                                            <a href="{{ route('hr.mpr.pdf', ['id' => $mpr->mprNumber]) }}"
                                                                target="_blank" class="btn btn-outline-danger"
                                                                title="Unduh PDF">
                                                                <i class="bi bi-file-earmark-pdf"></i>
                                                            </a>
                                                        @else
                                                            <button type="button" class="btn btn-outline-secondary"
                                                                disabled title="Nomor MPR tidak tersedia">
                                                                <i class="bi bi-file-earmark-pdf"></i>
                                                            </button>
                                                        @endif
                                                    </div>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="4" class="text-center py-5 text-muted">
                                                    <i class="bi bi-inbox fs-2 d-block mb-2 text-muted"></i>
                                                    Belum ada riwayat pengajuan Manpower Request.
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        @else
            {{-- ========================================================================= --}}
            {{-- HR / HR MANAGER / SUPER ADMIN EXPERIENCE: DASHBOARD + TABLE + MODAL       --}}
            {{-- ========================================================================= --}}

            <!-- METRICS CARDS -->
            <div class="row g-3 mb-4">
                <div class="col-6 col-md-3">
                    <div class="stat-card">
                        <div class="stat-icon bg-blue"><i class="bi bi-file-earmark-text-fill"></i></div>
                        <div>
                            <div class="stat-label">Total Pengajuan MPR</div>
                            <div class="stat-value text-navy">{{ $stats['total'] ?? 0 }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="stat-card">
                        <div class="stat-icon bg-green"><i class="bi bi-calendar-check-fill"></i></div>
                        <div>
                            <div class="stat-label">Pengajuan Bulan Ini</div>
                            <div class="stat-value text-navy">{{ $stats['this_month'] ?? 0 }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="stat-card">
                        <div class="stat-icon bg-purple"><i class="bi bi-people-fill"></i></div>
                        <div>
                            <div class="stat-label">Total Kebutuhan Manpower</div>
                            <div class="stat-value text-navy">{{ $stats['total_quantity'] ?? 0 }} <span
                                    class="fs-6 fw-normal text-muted">Org</span></div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="stat-card">
                        <div class="stat-icon bg-gold"><i class="bi bi-send-check-fill"></i></div>
                        <div>
                            <div class="stat-label">Status Submitted</div>
                            <div class="stat-value text-navy">{{ $stats['submitted'] ?? 0 }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- MAIN TABLE PANEL -->
            <div class="panel mb-4">
                <div class="panel-header">
                    <div>
                        <h6>Daftar Manpower Request (MPR)</h6>
                        <div class="panel-subtitle">Menampilkan {{ $total }} pengajuan kebutuhan tenaga kerja</div>
                    </div>
                    <div class="d-flex gap-2 ms-auto">
                        @can('create_mpr')
                            <button type="button" class="btn btn-sm text-white fw-semibold"
                                style="background:#eb1c24;border:none;border-radius:8px;padding:6px 14px;font-size:13px"
                                data-bs-toggle="modal" data-bs-target="#modalCreateMpr">
                                <i class="bi bi-plus-circle-fill me-1"></i>Buat MPR Baru
                            </button>
                        @endcan
                        <button class="btn-refresh" type="button" title="Muat ulang data" aria-label="Muat ulang data" data-refresh="page">
                            <i class="bi bi-arrow-repeat"></i>
                        </button>
                    </div>
                </div>

                <!-- FILTER BAR -->
                <form action="{{ route('hr.mpr.index') }}" method="GET" id="mprFilterForm">
                    <input type="hidden" name="page" value="1">
                    <div class="filter-bar">
                        <div class="table-search">
                            <i class="bi bi-search"></i>
                            <input type="text" name="search" id="mprSearchInput"
                                placeholder="Cari No MPR, pemohon, posisi, divisi..." value="{{ $search }}" />
                        </div>
                        <select class="filter-select" name="department" id="mprDeptFilter" data-auto-submit="true">
                            <option value="">Semua Departemen</option>
                            @foreach ($departments as $d)
                                <option value="{{ $d }}" {{ $dept === $d ? 'selected' : '' }}>
                                    {{ $d }}</option>
                            @endforeach
                        </select>
                        <select class="filter-select" name="status" id="mprStatusFilter" data-auto-submit="true">
                            <option value="">Semua Status</option>
                            <option value="Submitted" {{ $status === 'Submitted' ? 'selected' : '' }}>Submitted
                            </option>
                        </select>
                        <select class="filter-select" name="submit_by" id="mprSubmitByFilter" data-auto-submit="true">
                            <option value="">Submit By</option>
                            @foreach ($submitByOptions as $requestor)
                                <option value="{{ $requestor }}" {{ $submitBy === $requestor ? 'selected' : '' }}>
                                    {{ $requestor }}
                                </option>
                            @endforeach
                        </select>
                        <div class="d-flex gap-2">
                            <a href="{{ route('hr.mpr.index') }}" class="btn-reset-filter text-decoration-none"
                                title="Reset filter">
                                <i class="bi bi-arrow-counterclockwise"></i> Reset
                            </a>
                        </div>
                    </div>
                </form>

                <!-- TABLE -->
                <div class="table-responsive">
                    <table class="table hr-table">
                            <thead>
                                <tr>
                                    <th>No. MPR & Tanggal</th>
                                    <th>Pemohon (Manager)</th>
                                    <th>Perusahaan & Dept</th>
                                    <th>Posisi & Level</th>
                                    <th class="text-center">Kebutuhan</th>
                                    <th>Target Join</th>
                                    <th>Status</th>
                                    <th class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($paginatedMprs as $mpr)
                                    <tr>
                                        <td>
                                            <strong class="text-primary font-monospace">{{ $mpr->mprNumber }}</strong><br>
                                            <small class="text-muted">{{ $mpr->requestDate ? \Carbon\Carbon::parse($mpr->requestDate)->format('d M Y') : '-' }}</small>
                                        </td>
                                        <td>
                                            <div class="fw-semibold">{{ $mpr->requestorName }}</div>
                                            <small class="text-muted">{{ $mpr->requestorEmail }}</small>
                                        </td>
                                        <td>
                                            <div class="fw-medium">{{ $mpr->department }}</div>
                                            <small class="text-muted">{{ $mpr->entity ?: '-' }}</small>
                                        </td>
                                        <td>
                                            <div class="fw-semibold">{{ $mpr->position }}</div>
                                            <small
                                                class="badge bg-light text-secondary border">{{ $mpr->jobLevel ?? '-' }}</small>
                                            <small
                                                class="badge bg-light text-secondary border">{{ $mpr->employmentType ?? '-' }}</small>
                                        </td>
                                        <td class="text-center">
                                            <span class="fw-bold text-navy">{{ $mpr->quantity }} Orang</span>
                                        </td>
                                        <td>
                                            <span>{{ $mpr->expectedJoinDate ? \Carbon\Carbon::parse($mpr->expectedJoinDate)->format('d M Y') : '-' }}</span>
                                        </td>
                                        <td>
                                            <span class="badge-status accepted">SUBMITTED</span>
                                        </td>
                                        <td class="text-center">
                                            <div class="btn-group btn-group-sm">
                                                <button type="button" class="btn btn-sm btn-outline-primary btn-mpr-detail"
                                                    data-id="{{ $mpr->mprNumber }}" title="Lihat Detail">
                                                    <i class="bi bi-eye-fill"></i>
                                                </button>
                                                @can('export_mpr')
                                                    @if (filled($mpr->mprNumber))
                                                        <a href="{{ route('hr.mpr.pdf', ['id' => $mpr->mprNumber]) }}"
                                                            target="_blank" class="btn btn-sm btn-outline-danger"
                                                            title="Unduh PDF Resmi">
                                                            <i class="bi bi-download"></i>
                                                        </a>
                                                    @else
                                                        <button type="button" class="btn btn-sm btn-outline-secondary" disabled
                                                            title="Nomor MPR tidak tersedia">
                                                            <i class="bi bi-download"></i>
                                                        </button>
                                                    @endif
                                                @endcan
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center py-5 text-muted">
                                            <i class="bi bi-folder-x fs-1 d-block mb-2 text-muted"></i>
                                            Tidak ada data Manpower Request yang cocok dengan filter.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="panel-footer">
                    <span>
                        @if ($total > 0)
                            Menampilkan {{ ($currentPage - 1) * 10 + 1 }}–{{ min($currentPage * 10, $total) }} dari {{ $total }} data
                        @else
                            Tidak ada data
                        @endif
                    </span>
                    @if ($lastPage > 1)
                        <x-pagination :currentPage="$currentPage" :total="$total" :perPage="10" :route="'hr.mpr.index'"
                            :queryParams="[
                                'search' => $search,
                                'department' => $dept,
                                'status' => $status,
                                'submit_by' => $submitBy,
                            ]" />
                    @endif
                </div>

            </div>

            <!-- MODAL CREATE MPR (FOR HR MANAGER / SUPER ADMIN) -->
            @can('create_mpr')
                <div class="modal fade" id="modalCreateMpr" tabindex="-1" aria-labelledby="modalCreateMprLabel"
                    aria-hidden="true">
                    <div class="modal-dialog modal-lg modal-dialog-scrollable">
                        <div class="modal-content border-0 shadow">
                            <div class="modal-header bg-primary text-white">
                                <h5 class="modal-title fw-bold" id="modalCreateMprLabel"><i
                                        class="bi bi-plus-circle me-2"></i> Buat Pengajuan Manpower Request (MPR)</h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                                    aria-label="Close"></button>
                            </div>
                            <div class="modal-body p-4">
                                <form id="formHrCreateMpr" action="{{ route('hr.mpr.store') }}" method="POST">
                                    @csrf
                                    @php
                                        $mprOptions = config('hris.mpr_form_options', []);
                                    @endphp

                                    <!-- 1. INFORMASI PEMOHON -->
                                    <h6 class="fw-bold text-primary mb-3">1. Informasi Pemohon / Requester</h6>
                                    <div class="row g-3 mb-4 p-3 rounded-3 border" style="background: var(--color-bg);">
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold small">Nama Pemohon <span
                                                    class="text-danger">*</span></label>
                                            <input type="text" name="manager_name" class="form-control form-control-sm"
                                                value="{{ $user['fullName'] ?? 'HR Manager' }}" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold small">Jabatan Pemohon <span
                                                    class="text-danger">*</span></label>
                                            <input type="text" name="requestor_position"
                                                class="form-control form-control-sm"
                                                placeholder="Contoh: Area Manager, Branch Manager" required>
                                        </div>
                                        <input type="hidden" name="manager_email" value="{{ $user['email'] ?? '' }}">
                                        <div class="col-12">
                                            <div class="form-text text-muted" style="font-size:11px;">
                                                <i class="bi bi-shield-check-fill text-success me-1"></i>
                                                Nama pemohon diisi otomatis dari akun login. Jabatan dapat disesuaikan jika mengajukan atas nama orang lain.
                                            </div>
                                        </div>
                                    </div>

                                    <!-- 2. DETAIL POSISI & ORGANISASI -->
                                    <h6 class="fw-bold text-primary mb-3">2. Detail Posisi & Organisasi</h6>
                                    <div class="row g-3 mb-4">
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold small">Entitas / Perusahaan yang Dituju <span
                                                    class="text-danger">*</span></label>
                                            <select name="entity" class="form-select form-select-sm" required>
                                                <option value="">-- Pilih Entitas --</option>
                                                @foreach ($entityOptions as $code => $label)
                                                    <option value="{{ $code }}">{{ $code }} — {{ $label }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold small">Posisi / Nama Jabatan yang Diminta <span
                                                    class="text-danger">*</span></label>
                                            <input type="text" name="position" class="form-control form-control-sm"
                                                placeholder="Contoh: Digital Marketing Lead" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold small">Departemen <span
                                                    class="text-danger">*</span></label>
                                            <select name="department" class="form-select form-select-sm" required>
                                                <option value="">-- Pilih Departemen --</option>
                                                @foreach ($departments as $d)
                                                    <option value="{{ $d }}"
                                                        {{ old('department') === $d ? 'selected' : '' }}>{{ $d }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold small">Divisi <span
                                                    class="text-danger">*</span></label>
                                            <select name="division" class="form-select form-select-sm"
                                                data-selected-division="{{ old('division') }}" required>
                                                <option value="">-- Pilih Departemen terlebih dahulu --</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold small">Disetujui oleh (Divisi) <span
                                                    class="text-danger">*</span></label>
                                            <select name="approval_division" class="form-select form-select-sm" required>
                                                <option value="">-- Pilih Divisi --</option>
                                                @foreach (config('hris.mpr.approval_divisions', []) as $division)
                                                    <option value="{{ $division }}">{{ $division }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold small">Level Jabatan <span
                                                    class="text-danger">*</span></label>
                                            <select name="job_level" class="form-select form-select-sm" required>
                                                <option value="">-- Pilih Level Jabatan --</option>
                                                @foreach ($jobLevels as $jl)
                                                    <option value="{{ $jl }}">{{ $jl }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold small">Lokasi Penempatan <span
                                                    class="text-danger">*</span></label>
                                            <select name="work_location" class="form-select form-select-sm" required>
                                                <option value="">-- Pilih Lokasi Kerja --</option>
                                                @foreach ($workLocations as $wl)
                                                    <option value="{{ $wl }}">{{ $wl }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold small">Status Kepegawaian <span
                                                    class="text-danger">*</span></label>
                                            <select name="employment_type" class="form-select form-select-sm" required>
                                                <option value="">-- Pilih Status Kepegawaian --</option>
                                                @foreach ($employmentTypes as $et)
                                                    <option value="{{ $et }}">{{ $et }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold small">Jumlah Kebutuhan (Orang) <span
                                                    class="text-danger">*</span></label>
                                            <input type="number" name="quantity" class="form-control form-control-sm"
                                                value="1" min="1" max="100" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold small">Target Tanggal Bergabung <span
                                                    class="text-danger">*</span></label>
                                            <input type="date" name="expected_join_date"
                                                class="form-control form-control-sm" min="{{ date('Y-m-d') }}" required>
                                        </div>
                                    </div>

                                    <!-- WAKTU KERJA & BENEFITS -->
                                    <h6 class="fw-bold text-primary mb-3">3. Waktu Kerja & Benefits</h6>
                                    <div class="row g-3 mb-4">
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold small">Hari Kerja <span
                                                    class="text-danger">*</span></label>
                                            <div class="border rounded p-2 d-flex flex-column gap-1">
                                                @foreach ($mprOptions['working_days'] ?? [] as $dayKey => $dayLabel)
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox"
                                                            name="working_days[]" value="{{ $dayKey }}"
                                                            id="hrwd_{{ $dayKey }}">
                                                        <label class="form-check-label small"
                                                            for="hrwd_{{ $dayKey }}">{{ $dayLabel }}</label>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold small">Jam Kerja <span
                                                    class="text-danger">*</span></label>
                                            <div class="border rounded p-2 d-flex flex-column gap-1">
                                                @foreach ($mprOptions['working_hours'] ?? [] as $hourKey => $hourLabel)
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox"
                                                            name="working_hours[]" value="{{ $hourKey }}"
                                                            id="hrwh_{{ $hourKey }}">
                                                        <label class="form-check-label small"
                                                            for="hrwh_{{ $hourKey }}">{{ $hourLabel }}</label>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label fw-semibold small">
                                                Detail Shift
                                                <span class="text-danger d-none" id="hrShiftDetailRequiredMark">*</span>
                                                <span class="text-muted fw-normal" id="hrShiftDetailHint">(aktif &amp; wajib jika "Shifting" dipilih)</span>
                                            </label>
                                            <textarea name="shift_detail" class="form-control form-control-sm" rows="2"
                                                id="hrShiftDetailField"
                                                placeholder="Contoh: Shift pagi 07:00-15:00, shift siang 15:00-23:00, rotasi mingguan..."
                                                disabled></textarea>
                                            <div class="form-text text-muted small mt-1" id="hrShiftDetailNote">
                                                <i class="bi bi-info-circle me-1"></i>Centang "Shifting" pada Hari Kerja untuk mengisi detail shift.
                                            </div>
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label fw-semibold small">Benefits / Tunjangan <span
                                                    class="text-danger">*</span></label>
                                            <div class="border rounded p-2 row g-1">
                                                @foreach ($mprOptions['benefits'] ?? [] as $benefitKey => $benefitLabel)
                                                    <div class="col-md-4 col-6">
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="checkbox"
                                                                name="benefits[]" value="{{ $benefitKey }}"
                                                                id="hrbn_{{ $benefitKey }}">
                                                            <label class="form-check-label small"
                                                                for="hrbn_{{ $benefitKey }}">{{ $benefitLabel }}</label>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>

                                    <!-- ALASAN & DESKRIPSI -->
                                    <h6 class="fw-bold text-primary mb-3">4. Alasan & Kualifikasi</h6>
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold small">Alasan Permintaan <span
                                                    class="text-danger">*</span></label>
                                            <select name="reason" class="form-select form-select-sm" required>
                                                <option value="">-- Pilih Alasan Permintaan --</option>
                                                @foreach ($reasons as $r)
                                                    <option value="{{ $r }}">{{ $r }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold small">Nama Karyawan yang Digantikan
                                                <span class="text-danger d-none" id="hrReplacementForRequiredMark">*</span>
                                                <span class="text-muted fw-normal" id="hrReplacementForHint">(aktif jika alasan "Penggantian Karyawan")</span>
                                            </label>
                                            <input type="text" name="replacement_for" class="form-control form-control-sm"
                                                id="hrReplacementForField"
                                                placeholder="Nama karyawan yang akan digantikan"
                                                disabled>
                                            <div class="form-text text-muted small mt-1" id="hrReplacementForNote">
                                                <i class="bi bi-info-circle me-1"></i>Pilih alasan "Penggantian Karyawan Resign / Mutasi / Demosi" untuk mengisi field ini.
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold small">Latar Belakang Pendidikan <span
                                                    class="text-danger">*</span></label>
                                            <select name="education_background" class="form-select form-select-sm" required>
                                                <option value="">-- Pilih Pendidikan --</option>
                                                @foreach ($mprOptions['education_background'] ?? [] as $eduKey => $eduLabel)
                                                    <option value="{{ $eduKey }}">{{ $eduLabel }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold small">Pengalaman Kerja <span
                                                    class="text-danger">*</span></label>
                                            <select name="work_experience" class="form-select form-select-sm" required>
                                                <option value="">-- Pilih Pengalaman --</option>
                                                @foreach ($mprOptions['work_experience'] ?? [] as $expKey => $expLabel)
                                                    <option value="{{ $expKey }}">{{ $expLabel }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold small">Skills &amp; Kompetensi</label>
                                            <textarea name="skills_competencies" class="form-control form-control-sm"
                                                rows="2" placeholder="Contoh: Laravel, Excel lanjutan, leadership..."></textarea>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold small">Bahasa yang Dikuasai</label>
                                            <textarea name="languages" class="form-control form-control-sm" rows="2"
                                                placeholder="Contoh: Bahasa Indonesia (aktif), Bahasa Inggris (pasif)..."></textarea>
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label fw-semibold small">Referensi Industri Sejenis</label>
                                            <textarea name="industry_reference" class="form-control form-control-sm"
                                                rows="2" placeholder="Contoh: Pengalaman dari industri mining, logistik, atau FMCG..."></textarea>
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label fw-semibold small">Kualifikasi & Persyaratan
                                                Khusus</label>
                                            <textarea name="requirements" class="form-control form-control-sm" rows="3"
                                                placeholder="Pendidikan, pengalaman kerja, keahlian teknis..."></textarea>
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label fw-semibold small">Uraian Tugas Utama</label>
                                            <textarea name="job_description" class="form-control form-control-sm" rows="3"
                                                placeholder="Ringkasan tanggung jawab posisi..."></textarea>
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label fw-semibold small">Key Results / Target Posisi</label>
                                            <textarea name="key_results_targets" class="form-control form-control-sm"
                                                rows="2" placeholder="Contoh: Mencapai target penjualan 100 unit/bulan..."></textarea>
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label fw-semibold small">Catatan Khusus MPR</label>
                                            <textarea name="special_notes" class="form-control form-control-sm" rows="2"
                                                placeholder="Catatan khusus terkait kebutuhan ini..."></textarea>
                                        </div>
                                    </div>

                                    <div class="modal-footer px-0 pb-0 mt-4 border-top">
                                        <button type="button" class="btn btn-secondary btn-sm"
                                            data-bs-dismiss="modal">Batal</button>
                                        <button type="submit" id="btnSubmitHrCreateMpr"
                                            class="btn btn-primary btn-sm fw-semibold">
                                            <span class="spinner-border spinner-border-sm me-1 d-none"
                                                id="spinnerSubmitHr"></span>
                                            <i class="bi bi-check-circle-fill me-1" id="iconSubmitHr"></i> Simpan MPR &
                                            Generate PDF
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            @endcan

        @endif

        <!-- MODAL DETAIL MPR (SHARED) -->
        @include('hr.mpr.partials.detail-modal')

        @can('update_mpr')
            <div class="modal fade" id="modalEditMpr" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-scrollable">
                    <div class="modal-content border-0 shadow">
                        <div class="modal-header bg-primary text-white">
                            <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square me-2"></i>Edit MPR <small
                                    id="editMprNumber"></small></h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body p-4">
                            <form id="formEditMpr">
                                @php
                                    $mprOptions = config('hris.mpr_form_options', []);
                                @endphp
                                <div id="editMprErrors" class="alert alert-danger d-none"></div>

                                <!-- 1. Detail Posisi & Organisasi -->
                                <h6 class="fw-bold text-primary mb-3">1. Detail Posisi &amp; Organisasi</h6>
                                <div class="row g-3 mb-4">
                                    <div class="col-md-6">
                                        <label class="form-label small fw-semibold">Posisi / Nama Jabatan <span class="text-danger">*</span></label>
                                        <input name="position" class="form-control form-control-sm" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-semibold">Entitas / Perusahaan <span class="text-danger">*</span></label>
                                        <select name="entity" class="form-select form-select-sm" required>
                                            <option value="">-- Pilih Entitas --</option>
                                            @foreach ($entityOptions as $code => $label)
                                                <option value="{{ $code }}">{{ $code }} — {{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-semibold">Departemen <span class="text-danger">*</span></label>
                                        <select name="department" class="form-select form-select-sm" required>
                                            <option value="">-- Pilih --</option>
                                            @foreach ($departments as $d)
                                                <option value="{{ $d }}">{{ $d }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-semibold">Divisi <span class="text-danger">*</span></label>
                                        <select name="division" class="form-select form-select-sm"
                                            data-selected-division="{{ old('division') }}" required disabled>
                                            <option value="">-- Pilih Departemen terlebih dahulu --</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-semibold">Disetujui oleh (Divisi) <span class="text-danger">*</span></label>
                                        <select name="approval_division" class="form-select form-select-sm" required>
                                            <option value="">-- Pilih Divisi --</option>
                                            @foreach (config('hris.mpr.approval_divisions', []) as $division)
                                                <option value="{{ $division }}">{{ $division }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-semibold">Level Jabatan <span class="text-danger">*</span></label>
                                        <select name="job_level" class="form-select form-select-sm" required>
                                            <option value="">-- Pilih --</option>
                                            @foreach ($jobLevels as $d)
                                                <option value="{{ $d }}">{{ $d }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-semibold">Lokasi Penempatan <span class="text-danger">*</span></label>
                                        <select name="work_location" class="form-select form-select-sm" required>
                                            <option value="">-- Pilih --</option>
                                            @foreach ($workLocations as $d)
                                                <option value="{{ $d }}">{{ $d }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-semibold">Status Kepegawaian <span class="text-danger">*</span></label>
                                        <select name="employment_type" class="form-select form-select-sm" required>
                                            <option value="">-- Pilih --</option>
                                            @foreach ($employmentTypes as $d)
                                                <option value="{{ $d }}">{{ $d }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-semibold">Jumlah Kebutuhan <span class="text-danger">*</span></label>
                                        <input name="quantity" type="number" min="1" max="100" class="form-control form-control-sm" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-semibold">Target Bergabung <span class="text-danger">*</span></label>
                                        <input name="expected_join_date" type="date" class="form-control form-control-sm" required>
                                    </div>
                                </div>

                                <!-- 2. Waktu Kerja & Benefits -->
                                <h6 class="fw-bold text-primary mb-3">2. Waktu Kerja &amp; Benefits</h6>
                                <div class="row g-3 mb-4">
                                    <div class="col-md-6">
                                        <label class="form-label small fw-semibold">Hari Kerja <span class="text-danger">*</span></label>
                                        <div class="border rounded p-2 d-flex flex-column gap-1">
                                            @foreach ($mprOptions['working_days'] ?? [] as $dayKey => $dayLabel)
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="working_days[]"
                                                        value="{{ $dayKey }}" id="editwd_{{ $dayKey }}">
                                                    <label class="form-check-label small" for="editwd_{{ $dayKey }}">{{ $dayLabel }}</label>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-semibold">Jam Kerja <span class="text-danger">*</span></label>
                                        <div class="border rounded p-2 d-flex flex-column gap-1">
                                            @foreach ($mprOptions['working_hours'] ?? [] as $hourKey => $hourLabel)
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="working_hours[]"
                                                        value="{{ $hourKey }}" id="editwh_{{ $hourKey }}">
                                                    <label class="form-check-label small" for="editwh_{{ $hourKey }}">{{ $hourLabel }}</label>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label small fw-semibold">
                                            Detail Shift
                                            <span class="text-danger d-none" id="editShiftDetailRequiredMark">*</span>
                                            <span class="text-muted fw-normal small" id="editShiftDetailHint">(aktif &amp; wajib jika "Shifting" dipilih)</span>
                                        </label>
                                        <textarea name="shift_detail" class="form-control form-control-sm" rows="2"
                                            id="editShiftDetailField"
                                            placeholder="Contoh: Shift pagi 07:00-15:00, shift siang 15:00-23:00, rotasi mingguan..."
                                            disabled></textarea>
                                        <div class="form-text text-muted small mt-1" id="editShiftDetailNote">
                                            <i class="bi bi-info-circle me-1"></i>Centang "Shifting" pada Hari Kerja untuk mengisi detail shift.
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label small fw-semibold">Benefits / Tunjangan <span class="text-danger">*</span></label>
                                        <div class="border rounded p-2 row g-1">
                                            @foreach ($mprOptions['benefits'] ?? [] as $benefitKey => $benefitLabel)
                                                <div class="col-md-4 col-6">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" name="benefits[]"
                                                            value="{{ $benefitKey }}" id="editbn_{{ $benefitKey }}">
                                                        <label class="form-check-label small" for="editbn_{{ $benefitKey }}">{{ $benefitLabel }}</label>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>

                                <!-- 3. Alasan & Kualifikasi -->
                                <h6 class="fw-bold text-primary mb-3">3. Alasan &amp; Kualifikasi</h6>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label small fw-semibold">Alasan Permintaan <span class="text-danger">*</span></label>
                                        <select name="reason" class="form-select form-select-sm" id="editReasonSelect" required>
                                            <option value="">-- Pilih --</option>
                                            @foreach ($reasons as $d)
                                                <option value="{{ $d }}">{{ $d }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-semibold">
                                            Karyawan yang Digantikan
                                            <span class="text-danger d-none" id="editReplacementForRequiredMark">*</span>
                                            <span class="text-muted fw-normal small" id="editReplacementForHint">(aktif jika alasan "Penggantian Karyawan")</span>
                                        </label>
                                        <input name="replacement_for" class="form-control form-control-sm"
                                            id="editReplacementForField"
                                            placeholder="Nama karyawan yang akan digantikan"
                                            disabled>
                                        <div class="form-text text-muted small mt-1" id="editReplacementForNote">
                                            <i class="bi bi-info-circle me-1"></i>Pilih alasan "Penggantian Karyawan" untuk mengisi field ini.
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-semibold">Latar Belakang Pendidikan <span class="text-danger">*</span></label>
                                        <select name="education_background" class="form-select form-select-sm" required>
                                            <option value="">-- Pilih Pendidikan --</option>
                                            @foreach ($mprOptions['education_background'] ?? [] as $eduKey => $eduLabel)
                                                <option value="{{ $eduKey }}">{{ $eduLabel }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-semibold">Pengalaman Kerja <span class="text-danger">*</span></label>
                                        <select name="work_experience" class="form-select form-select-sm" required>
                                            <option value="">-- Pilih Pengalaman --</option>
                                            @foreach ($mprOptions['work_experience'] ?? [] as $expKey => $expLabel)
                                                <option value="{{ $expKey }}">{{ $expLabel }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-semibold">Skills &amp; Kompetensi</label>
                                        <textarea name="skills_competencies" class="form-control form-control-sm" rows="2"
                                            placeholder="Contoh: Laravel, Excel lanjutan, leadership..."></textarea>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-semibold">Bahasa yang Dikuasai</label>
                                        <textarea name="languages" class="form-control form-control-sm" rows="2"
                                            placeholder="Contoh: Bahasa Indonesia (aktif), Bahasa Inggris (pasif)..."></textarea>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label small fw-semibold">Referensi Industri Sejenis</label>
                                        <textarea name="industry_reference" class="form-control form-control-sm" rows="2"
                                            placeholder="Contoh: Pengalaman dari industri mining, logistik, atau FMCG..."></textarea>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label small fw-semibold">Kualifikasi &amp; Persyaratan Khusus</label>
                                        <textarea name="requirements" rows="3" class="form-control form-control-sm"
                                            placeholder="Pendidikan, pengalaman, keahlian teknis..."></textarea>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label small fw-semibold">Uraian Tugas &amp; Tanggung Jawab Utama</label>
                                        <textarea name="job_description" rows="4" class="form-control form-control-sm"
                                            placeholder="Ringkasan tanggung jawab posisi..."></textarea>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label small fw-semibold">Key Results / Target Posisi</label>
                                        <textarea name="key_results_targets" class="form-control form-control-sm" rows="2"
                                            placeholder="Contoh: Mencapai target penjualan 100 unit/bulan..."></textarea>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label small fw-semibold">Catatan Khusus MPR</label>
                                        <textarea name="special_notes" class="form-control form-control-sm" rows="2"
                                            placeholder="Catatan khusus terkait kebutuhan ini..."></textarea>
                                    </div>
                                </div>
                            </form>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary btn-sm"
                                data-bs-dismiss="modal">Batal</button>
                            <button type="submit" form="formEditMpr" id="btnSaveMpr" class="btn btn-primary btn-sm"><i
                                    class="bi bi-check-circle me-1"></i> Save Changes</button>
                        </div>
                    </div>
                </div>
            </div>
        @endcan

    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            let currentMprId = null;
            const departmentDivisionMap = @json($departmentDivisionMap);

            function setupDepartmentDivision(form, selectedDivision = '') {
                const department = form.elements.department;
                const division = form.elements.division;
                if (!department || !division) return;

                const initialDivision = selectedDivision || division.dataset.selectedDivision || '';
                const updateDivisions = (preserveSelection = false) => {
                    const divisions = departmentDivisionMap[department.value] || [];
                    const currentDivision = preserveSelection ? initialDivision || division.value : '';
                    division.innerHTML = '';
                    division.append(new Option(
                        divisions.length ? '-- Pilih Divisi --' :
                        '-- Pilih Departemen terlebih dahulu --',
                        ''
                    ));
                    divisions.forEach(value => division.append(new Option(value, value)));
                    division.disabled = divisions.length === 0;
                    division.value = divisions.includes(currentDivision) ? currentDivision : '';
                };

                department.addEventListener('change', () => updateDivisions(false));
                updateDivisions(true);
            }

            document.querySelectorAll('form').forEach(form => setupDepartmentDivision(form));

            /**
             * Logika Shifting (shared helper — digunakan di semua form MPR):
             * - Shifting diceklis → hari kerja lain + jam kerja di-disable & uncheck,
             *   textarea detail shift: enabled, required, border highlight
             * - Shifting di-unceklis → semua checkbox kembali enabled,
             *   textarea detail shift: disabled, not required, value dikosongkan
             */
            function applyShiftingState(form) {
                if (!form) return;

                const shiftingCb   = form.querySelector('input[name="working_days[]"][value="shifting"]');
                const otherDayCbs  = Array.from(form.querySelectorAll('input[name="working_days[]"]'))
                                         .filter(cb => cb.value !== 'shifting');
                const hourCbs      = Array.from(form.querySelectorAll('input[name="working_hours[]"]'));
                const shiftField   = form.querySelector('[id$="ShiftDetailField"]');
                const requiredMark = form.querySelector('[id$="ShiftDetailRequiredMark"]');
                const hintNote     = form.querySelector('[id$="ShiftDetailNote"]');

                if (!shiftingCb) return;
                const isShifting = shiftingCb.checked;

                // Hari Kerja lain
                otherDayCbs.forEach(cb => {
                    if (isShifting) {
                        cb.checked  = false;
                        cb.disabled = true;
                        cb.closest('.form-check')?.classList.add('opacity-50');
                    } else {
                        cb.disabled = false;
                        cb.closest('.form-check')?.classList.remove('opacity-50');
                    }
                });

                // Jam Kerja
                hourCbs.forEach(cb => {
                    if (isShifting) {
                        cb.checked  = false;
                        cb.disabled = true;
                        cb.closest('.form-check')?.classList.add('opacity-50');
                    } else {
                        cb.disabled = false;
                        cb.closest('.form-check')?.classList.remove('opacity-50');
                    }
                });

                // Detail Shift textarea
                if (shiftField) {
                    shiftField.disabled = !isShifting;
                    shiftField.required = isShifting;
                    if (!isShifting) {
                        shiftField.value = '';
                        shiftField.classList.remove('border-primary');
                    } else {
                        shiftField.classList.add('border-primary');
                        shiftField.focus();
                    }
                }
                if (requiredMark) requiredMark.classList.toggle('d-none', !isShifting);
                if (hintNote)     hintNote.classList.toggle('d-none', isShifting);
            }

            /**
             * Logika Replacement:
             * - Jika alasan = "Penggantian Karyawan Resign / Mutasi / Demosi":
             *   → Input nama karyawan: enabled, required, border highlight
             * - Selain itu: disabled, not required, value dikosongkan
             */
            const REPLACEMENT_REASON_VALUE = 'Penggantian Karyawan Resign / Mutasi / Demosi';

            function applyReplacementState(form) {
                if (!form) return;
                const reasonSelect     = form.querySelector('[name="reason"]');
                const replacementField = form.querySelector('[id$="ReplacementForField"]');
                const requiredMark     = form.querySelector('[id$="ReplacementForRequiredMark"]');
                const hintText         = form.querySelector('[id$="ReplacementForHint"]');
                const noteText         = form.querySelector('[id$="ReplacementForNote"]');
                if (!reasonSelect || !replacementField) return;

                const isReplacement = reasonSelect.value === REPLACEMENT_REASON_VALUE;

                replacementField.disabled = !isReplacement;
                replacementField.required = isReplacement;
                if (!isReplacement) {
                    replacementField.value = '';
                    replacementField.classList.remove('border-primary');
                } else {
                    replacementField.classList.add('border-primary');
                }
                if (requiredMark) requiredMark.classList.toggle('d-none', !isReplacement);
                if (hintText)     hintText.classList.toggle('d-none', isReplacement);
                if (noteText)     noteText.classList.toggle('d-none', isReplacement);
            }

            // Pasang listener & inisialisasi untuk form HR Create MPR (modal)
            const hrCreateForm = document.getElementById('formHrCreateMpr');

            // Pasang listener & inisialisasi untuk Manager Experience form (inline)
            const mgForm = document.getElementById('formManagerMpr');
            if (mgForm) {
                const mgShiftingCb = mgForm.querySelector('input[name="working_days[]"][value="shifting"]');
                if (mgShiftingCb) {
                    mgShiftingCb.addEventListener('change', () => applyShiftingState(mgForm));
                }
                applyShiftingState(mgForm);

                const mgReasonSelect = mgForm.querySelector('[name="reason"]');
                if (mgReasonSelect) {
                    mgReasonSelect.addEventListener('change', () => applyReplacementState(mgForm));
                }
                applyReplacementState(mgForm);
            }

            if (hrCreateForm) {
                const shiftingCb = hrCreateForm.querySelector('input[name="working_days[]"][value="shifting"]');
                if (shiftingCb) {
                    shiftingCb.addEventListener('change', () => applyShiftingState(hrCreateForm));
                }
                applyShiftingState(hrCreateForm);

                // Re-apply saat modal dibuka kembali (setelah reset)
                const modalCreateEl = document.getElementById('modalCreateMpr');
                if (modalCreateEl) {
                    modalCreateEl.addEventListener('shown.bs.modal', () => {
                        applyShiftingState(hrCreateForm);
                        applyReplacementState(hrCreateForm);
                    });
                    modalCreateEl.addEventListener('hidden.bs.modal', () => {
                        // Reset shifting state saat modal ditutup
                        hrCreateForm.reset();
                        applyShiftingState(hrCreateForm);
                        applyReplacementState(hrCreateForm);
                    });
                }

                // Listener alasan → replacement
                const hrReasonSelect = hrCreateForm.querySelector('[name="reason"]');
                if (hrReasonSelect) {
                    hrReasonSelect.addEventListener('change', () => applyReplacementState(hrCreateForm));
                }
                applyReplacementState(hrCreateForm);
            }

            function getCsrfToken() {
                const meta = document.querySelector('meta[name="csrf-token"]');
                return meta ? meta.getAttribute('content') : '';
            }

            // Handle Form Submission (Fetch API + Anti-Double-Submit + Auto PDF Popup)
            function setupMprForm(formId, submitBtnId, spinnerId, iconId, modalIdToClose) {
                const form = document.getElementById(formId);
                if (!form) return;

                form.addEventListener('submit', function(e) {
                    e.preventDefault();

                    const btn = document.getElementById(submitBtnId);
                    const spinner = document.getElementById(spinnerId);
                    const icon = document.getElementById(iconId);

                    if (btn) btn.disabled = true;
                    if (spinner) spinner.classList.remove('d-none');
                    if (icon) icon.classList.add('d-none');

                    const formData = new FormData(form);

                    fetch(form.action, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': getCsrfToken(),
                                'Accept': 'application/json',
                            },
                            body: formData,
                        })
                        .then(async response => {
                            const data = await response.json();
                            if (!response.ok) {
                                throw new Error(data.message || (data.errors ? Object.values(data
                                        .errors).flat().join('\n') :
                                    'Terjadi kesalahan saat menyimpan MPR.'));
                            }
                            return data;
                        })
                        .then(data => {
                            // Reset form
                            form.reset();

                            // Close modal if open
                            if (modalIdToClose) {
                                const modalEl = document.getElementById(modalIdToClose);
                                if (modalEl) {
                                    const modalInstance = bootstrap.Modal.getInstance(modalEl);
                                    if (modalInstance) modalInstance.hide();
                                }
                            }

                            // Show success alert banner with direct PDF link
                            const alertEl = document.getElementById('mprSuccessAlert');
                            const alertTitle = document.getElementById('mprSuccessTitle');
                            const alertBody = document.getElementById('mprSuccessBody');
                            const btnPdf = document.getElementById('btnAlertOpenPdf');

                            if (alertEl) {
                                if (alertTitle) alertTitle.innerText =
                                    `Pengajuan MPR Berhasil! (${data.mpr_number})`;
                                if (alertBody) alertBody.innerText =
                                    `Dokumen Manpower Request nomor ${data.mpr_number} telah berhasil disimpan dan PDF otomatis digenerate.`;
                                if (btnPdf) btnPdf.href = data.pdf_url;
                                alertEl.classList.remove('d-none');
                                alertEl.classList.add('show');
                                window.scrollTo({
                                    top: 0,
                                    behavior: 'smooth'
                                });
                            }

                            // Auto open PDF in new tab/window
                            if (data.pdf_url) {
                                window.open(data.pdf_url, '_blank');
                            }

                            // Reload page after brief delay to show new record in table
                            setTimeout(() => {
                                window.location.reload();
                            }, 1500);
                        })
                        .catch(error => {
                            showToast('Gagal: ' + error.message, 'error');
                        })
                        .finally(() => {
                            if (btn) btn.disabled = false;
                            if (spinner) spinner.classList.add('d-none');
                            if (icon) icon.classList.remove('d-none');
                        });
                });
            }

            setupMprForm('formManagerMpr', 'btnSubmitMprManager', 'spinnerSubmitManager', 'iconSubmitManager',
                null);
            setupMprForm('formHrCreateMpr', 'btnSubmitHrCreateMpr', 'spinnerSubmitHr', 'iconSubmitHr',
                'modalCreateMpr');

            // Handle Detail Modal (Fetch API)
            const detailButtons = document.querySelectorAll('.btn-mpr-detail');
            detailButtons.forEach(btn => {
                btn.addEventListener('click', function() {
                    const id = this.getAttribute('data-id');
                    const modalEl = document.getElementById('modalMprDetail');
                    const detailModal = new bootstrap.Modal(modalEl);

                    const loadingEl = document.getElementById('detailLoading');
                    const contentEl = document.getElementById('detailContent');
                    const detailMprNum = document.getElementById('detailMprNumber');
                    const btnPdf = document.getElementById('btnModalDownloadPdf');

                    loadingEl.classList.remove('d-none');
                    contentEl.classList.add('d-none');
                    detailMprNum.innerText = id;
                    detailModal.show();

                    fetch(`/hr/mpr/${encodeURIComponent(id)}/json`, {
                            headers: {
                                'X-CSRF-TOKEN': getCsrfToken(),
                                'Accept': 'application/json'
                            }
                        })
                        .then(async response => {
                            const data = await response.json();
                            if (!response.ok) throw new Error(data.error ||
                                'Gagal memuat detail MPR.');
                            return data;
                        })
                        .then(data => {
                            const m = data.mpr;
                            currentMprId = m.mpr_number || id;
                            document.getElementById('detManagerName').innerText = m
                                .requestor_name || '-';
                            document.getElementById('detManagerEmail').innerText = m
                                .requestor_email || '-';
                            document.getElementById('detEntity').innerText = m.entity || '-';
                            document.getElementById('detCreatedBy').innerText =
                                `Diajukan: ${m.created_at || m.request_date || '-'}`;
                            document.getElementById('detPosition').innerText = m.position ||
                                '-';
                            document.getElementById('detDeptDiv').innerText =
                                `${m.department || '-'} / ${m.division || '-'}`;
                            document.getElementById('detJobLevel').innerText = m.job_level ||
                                '-';
                            document.getElementById('detEmpType').innerText = m
                                .employment_type || '-';
                            document.getElementById('detLocation').innerText = m
                                .work_location || '-';
                            document.getElementById('detQuantity').innerText = m.quantity ||
                                '1';
                            document.getElementById('detJoinDate').innerText = m
                                .expected_join_date || '-';
                            document.getElementById('detReason').innerText = m.reason || '-';

                            // --- Field baru (Refactor Create MPR) — backward compatible ---
                            const setDetText = (elId, val) => {
                                const el = document.getElementById(elId);
                                if (el) el.innerText = (val === null || val === undefined || val === '') ? '-' : val;
                            };
                            const toggleWrap = (wrapId, val) => {
                                const el = document.getElementById(wrapId);
                                if (el) el.classList.toggle('d-none', !(val && String(val).trim() !== ''));
                            };
                            const reqPos = document.getElementById('detRequestorPosition');
                            if (reqPos) reqPos.innerText = m.requestor_position ?
                                `Jabatan: ${m.requestor_position}` : '';
                            setDetText('detWorkingDays', m.working_days);
                            setDetText('detWorkingHours', m.working_hours);
                            setDetText('detShiftDetail', m.shift_detail);
                            toggleWrap('wrapShiftDetail', m.shift_detail);
                            setDetText('detBenefits', m.benefits);
                            setDetText('detEducation', m.education_background);
                            setDetText('detExperience', m.work_experience);
                            setDetText('detSkills', m.skills_competencies);
                            toggleWrap('wrapSkills', m.skills_competencies);
                            setDetText('detLanguages', m.languages);
                            toggleWrap('wrapLanguages', m.languages);
                            setDetText('detIndustryRef', m.industry_reference);
                            toggleWrap('wrapIndustryRef', m.industry_reference);
                            setDetText('detKeyResults', m.key_results_targets);
                            toggleWrap('wrapKeyResults', m.key_results_targets);
                            setDetText('detSpecialNotes', m.special_notes);
                            toggleWrap('wrapSpecialNotes', m.special_notes);

                            const wrapRepl = document.getElementById('wrapReplacement');
                            if (m.replacement_for) {
                                document.getElementById('detReplacementFor').innerText = m
                                    .replacement_for;
                                wrapRepl.classList.remove('d-none');
                            } else {
                                wrapRepl.classList.add('d-none');
                            }

                            document.getElementById('detRequirements').innerHTML = m
                                .requirements_html ||
                                '<span class="text-muted fst-italic">Tidak ada kualifikasi khusus.</span>';
                            document.getElementById('detJobDesc').innerHTML = m
                                .job_description_html ||
                                '<span class="text-muted fst-italic">Tidak ada uraian pekerjaan khusus.</span>';

                            // Tanda tangan (match PDF: 3 + 2 centered)
                            const signName = document.getElementById('detSignRequestorName');
                            if (signName) signName.innerText = m.requestor_name || '-';
                            const signPos = document.getElementById('detSignRequestorPosition');
                            if (signPos) signPos.innerText = m.requestor_position || 'Manager / User Dept';
                            const approvalDivision = document.getElementById('detApprovalDivision');
                            if (approvalDivision) approvalDivision.innerText = m.approval_division || '( ........................................ )';

                            if (btnPdf) {
                                btnPdf.href = `/hr/mpr/${encodeURIComponent(m.mpr_number)}/pdf`;
                            }

                            const btnEdit = document.getElementById('btnEditMpr');
                            if (btnEdit) {
                                btnEdit.onclick = function() {
                                    const editForm = document.getElementById('formEditMpr');
                                    document.getElementById('editMprNumber').innerText = m
                                        .mpr_number || id;
                                    editForm.reset();
                                    
                                    // Debug: Log API data structure
                                    console.log('MPR Data from API:', m);
                                    
                                    // Populate text inputs and selects
                                    Object.keys(m).forEach(key => {
                                        const field = editForm.elements[key];
                                        if (field && !key.endsWith('_html') &&
                                            key !== 'department' && key !== 'division' &&
                                            key !== 'working_days' && key !== 'working_hours' && key !== 'benefits' &&
                                            key !== 'education_background' && key !== 'work_experience') {
                                            field.value = m[key] ?? '';
                                        }
                                    });
                                    
                                    editForm.elements.department.value = m.department || '';
                                    const approvalSelect = editForm.elements.approval_division;
                                    if (approvalSelect) {
                                        approvalSelect.value = m.approval_division || '';
                                    }
                                    setupDepartmentDivision(editForm, m.division || '');
                                    
                                    // Populate education_background select
                                    const eduBgField = editForm.elements.education_background;
                                    if (eduBgField && m.education_background) {
                                        // Try direct key match first
                                        eduBgField.value = m.education_background;
                                        
                                        // If not set, try to find by label match
                                        if (!eduBgField.value || eduBgField.value === '') {
                                            const options = Array.from(eduBgField.options);
                                            const matchedOption = options.find(opt => 
                                                opt.text.toLowerCase() === m.education_background.toLowerCase()
                                            );
                                            if (matchedOption) {
                                                eduBgField.value = matchedOption.value;
                                            }
                                        }
                                        console.log('Set education_background:', m.education_background, '→', eduBgField.value);
                                    }
                                    
                                    // Populate work_experience select
                                    const workExpField = editForm.elements.work_experience;
                                    if (workExpField && m.work_experience) {
                                        // Try direct key match first
                                        workExpField.value = m.work_experience;
                                        
                                        // If not set, try to find by label match
                                        if (!workExpField.value || workExpField.value === '') {
                                            const options = Array.from(workExpField.options);
                                            const matchedOption = options.find(opt => 
                                                opt.text.toLowerCase() === m.work_experience.toLowerCase()
                                            );
                                            if (matchedOption) {
                                                workExpField.value = matchedOption.value;
                                            }
                                        }
                                        console.log('Set work_experience:', m.work_experience, '→', workExpField.value);
                                    }
                                    
                                    // Helper function: Build label-to-key mapping from checkboxes
                                    function buildLabelToKeyMap(checkboxes) {
                                        const map = {};
                                        checkboxes.forEach(cb => {
                                            const label = cb.nextElementSibling?.textContent?.trim();
                                            if (label) {
                                                map[label.toLowerCase()] = cb.value;
                                            }
                                        });
                                        return map;
                                    }
                                    
                                    // Populate checkboxes for working_days
                                    const workingDaysCbs = Array.from(editForm.querySelectorAll('input[name="working_days[]"]'));
                                    const workingDaysMap = buildLabelToKeyMap(workingDaysCbs);
                                    const workingDaysLabels = (m.working_days || '').split(',').map(s => s.trim()).filter(Boolean);
                                    
                                    console.log('Working Days Labels from API:', workingDaysLabels);
                                    console.log('Working Days Label→Key Map:', workingDaysMap);
                                    
                                    workingDaysCbs.forEach(cb => {
                                        // Try direct key match first
                                        let shouldCheck = workingDaysLabels.includes(cb.value);
                                        
                                        // If no match, try label match
                                        if (!shouldCheck) {
                                            shouldCheck = workingDaysLabels.some(label => 
                                                workingDaysMap[label.toLowerCase()] === cb.value
                                            );
                                        }
                                        
                                        cb.checked = shouldCheck;
                                        if (shouldCheck) console.log('✓ Checked working_day:', cb.value);
                                    });
                                    
                                    // Populate checkboxes for working_hours
                                    const workingHoursCbs = Array.from(editForm.querySelectorAll('input[name="working_hours[]"]'));
                                    const workingHoursMap = buildLabelToKeyMap(workingHoursCbs);
                                    const workingHoursLabels = (m.working_hours || '').split(',').map(s => s.trim()).filter(Boolean);
                                    
                                    console.log('Working Hours Labels from API:', workingHoursLabels);
                                    console.log('Working Hours Label→Key Map:', workingHoursMap);
                                    
                                    workingHoursCbs.forEach(cb => {
                                        // Try direct key match first
                                        let shouldCheck = workingHoursLabels.includes(cb.value);
                                        
                                        // If no match, try label match
                                        if (!shouldCheck) {
                                            shouldCheck = workingHoursLabels.some(label => 
                                                workingHoursMap[label.toLowerCase()] === cb.value
                                            );
                                        }
                                        
                                        cb.checked = shouldCheck;
                                        if (shouldCheck) console.log('✓ Checked working_hour:', cb.value);
                                    });
                                    
                                    // Populate checkboxes for benefits
                                    const benefitsCbs = Array.from(editForm.querySelectorAll('input[name="benefits[]"]'));
                                    const benefitsMap = buildLabelToKeyMap(benefitsCbs);
                                    const benefitsLabels = (m.benefits || '').split(',').map(s => s.trim()).filter(Boolean);
                                    
                                    console.log('Benefits Labels from API:', benefitsLabels);
                                    console.log('Benefits Label→Key Map:', benefitsMap);
                                    
                                    benefitsCbs.forEach(cb => {
                                        // Try direct key match first
                                        let shouldCheck = benefitsLabels.includes(cb.value);
                                        
                                        // If no match, try label match
                                        if (!shouldCheck) {
                                            shouldCheck = benefitsLabels.some(label => 
                                                benefitsMap[label.toLowerCase()] === cb.value
                                            );
                                        }
                                        
                                        cb.checked = shouldCheck;
                                        if (shouldCheck) console.log('✓ Checked benefit:', cb.value);
                                    });
                                    
                                    document.getElementById('editMprErrors').classList.add('d-none');
                                    
                                    // Setup Shifting logic
                                    const editShiftingCb = editForm.querySelector('input[name="working_days[]"][value="shifting"]');
                                    if (editShiftingCb) {
                                        editShiftingCb.addEventListener('change', () => applyShiftingState(editForm));
                                        applyShiftingState(editForm);
                                    }
                                    
                                    // Setup Replacement logic
                                    const editReasonSelect = editForm.querySelector('[name="reason"]');
                                    if (editReasonSelect) {
                                        editReasonSelect.addEventListener('change', () => applyReplacementState(editForm));
                                    }
                                    applyReplacementState(editForm);
                                    
                                    bootstrap.Modal.getOrCreateInstance(document
                                        .getElementById('modalMprDetail')).hide();
                                    bootstrap.Modal.getOrCreateInstance(document
                                        .getElementById('modalEditMpr')).show();
                                };
                            }

                            loadingEl.classList.add('d-none');
                            contentEl.classList.remove('d-none');
                        })
                        .catch(error => {
                            loadingEl.innerHTML =
                                `<div class="text-danger py-4"><i class="bi bi-exclamation-triangle-fill fs-2 d-block mb-2"></i>${error.message}</div>`;
                        });
                });
            });

            const editForm = document.getElementById('formEditMpr');
            if (editForm) editForm.addEventListener('submit', async function(event) {
                event.preventDefault();
                const button = document.getElementById('btnSaveMpr');
                const errorBox = document.getElementById('editMprErrors');
                button.disabled = true;
                button.innerHTML =
                    '<span class="spinner-border spinner-border-sm me-1"></span>Saving...';
                errorBox.classList.add('d-none');
                try {
                    const response = await fetch(`/hr/mpr/${encodeURIComponent(currentMprId)}`, {
                        method: 'PUT',
                        headers: {
                            'X-CSRF-TOKEN': getCsrfToken(),
                            'Accept': 'application/json',
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify(Object.fromEntries(new FormData(editForm)))
                    });
                    const data = await response.json();
                    if (!response.ok) {
                        const errors = data.errors ? Object.values(data.errors).flat().join('<br>') : (
                            data.message || 'Gagal memperbarui MPR.');
                        errorBox.innerHTML = errors;
                        errorBox.classList.remove('d-none');
                        return;
                    }
                    bootstrap.Modal.getOrCreateInstance(document.getElementById('modalEditMpr')).hide();
                    if (typeof showToast === 'function') showToast(data.message ||
                        'MPR berhasil diperbarui.', 'success');
                    setTimeout(() => window.location.reload(), 700);
                } catch (error) {
                    errorBox.textContent = 'Terjadi kesalahan saat memperbarui MPR.';
                    errorBox.classList.remove('d-none');
                } finally {
                    button.disabled = false;
                    button.innerHTML = '<i class="bi bi-check-circle me-1"></i> Save Changes';
                }
            });

        });

        // Auto-submit for all filter selects with data-auto-submit="true"
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('select[data-auto-submit="true"]').forEach(function(select) {
                select.addEventListener('change', function() {
                    document.getElementById('mprFilterForm').submit();
                });
            });
        });

        // Search submit on Enter
        const mprSearchInput = document.getElementById('mprSearchInput');
        if (mprSearchInput) {
            mprSearchInput.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    document.getElementById('mprFilterForm').submit();
                }
            });
        }
    </script>
@endsection
