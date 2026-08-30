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
        .mpr-card-header {
            background: var(--color-primary);
            color: #ffffff;
            border-radius: 8px 8px 0 0;
            padding: 16px 20px;
        }

        .stat-card-mpr {
            /* legacy class retained only for compatibility; layout now follows global stat-card */
        }

        .stat-icon-mpr {
            /* legacy class retained only for compatibility; layout now follows global stat-icon */
        }

        .bg-blue-light,
        .bg-green-light,
        .bg-purple-light,
        .bg-amber-light {
            /* legacy custom colors intentionally unused; use shared stat-icon palette */
        }

        /* Shared Bootstrap/HR table and badge styles are used instead of duplicate MPR-specific overrides. */

        #modalMprDetail .modal-content {
            background: var(--color-surface);
            border-radius: 16px;
            overflow: hidden;
        }

        #modalMprDetail .modal-header {
            padding: 18px 24px;
        }

        #modalMprDetail .modal-body {
            background: var(--color-bg);
            padding: 24px !important;
        }

        #modalMprDetail .modal-footer {
            background: var(--color-surface) !important;
            border-color: var(--color-border) !important;
            padding: 14px 24px;
        }

        #modalMprDetail #detailContent>.card,
        #modalMprDetail #detailContent>.bg-light {
            background: var(--color-surface) !important;
            border: 1px solid var(--color-border) !important;
            border-radius: 12px !important;
            padding: 16px !important;
        }

        #modalMprDetail #detailContent>.row {
            row-gap: 12px;
        }

        #modalMprDetail #detailContent>.row>[class*="col-"] {
            padding: 14px;
            background: var(--color-surface);
            border: 1px solid var(--color-border);
            border-radius: 12px;
        }

        #modalMprDetail .text-dark {
            color: var(--color-text) !important;
        }

        #modalMprDetail .mpr-markdown-content {
            background: var(--color-surface) !important;
            border-color: var(--color-border) !important;
            border-radius: 10px !important;
            color: var(--color-text);
            line-height: 1.6;
            padding: 12px !important;
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

        .mpr-internal-dashboard .stat-card-mpr,
        .mpr-internal-dashboard .mpr-list-panel {
            background: var(--color-surface) !important;
            border-color: var(--color-border) !important;
            color: var(--color-text);
        }

        .mpr-internal-dashboard .stat-card-mpr {
            border-radius: 16px;
        }

        .mpr-internal-dashboard .mpr-list-panel {
            border-radius: 16px !important;
            overflow: visible;
        }

        .mpr-internal-dashboard .mpr-table-wrap {
            border-radius: 0 0 16px 16px;
            overflow-x: auto;
            overflow-y: hidden;
            -webkit-overflow-scrolling: touch;
        }

        .mpr-internal-dashboard .mpr-list-header {
            padding: 20px 22px !important;
        }

        .mpr-internal-dashboard .mpr-filter-bar {
            padding: 18px 22px !important;
        }

        .mpr-internal-dashboard .mpr-list-footer {
            padding: 16px 22px !important;
        }

        .mpr-internal-dashboard .text-dark {
            color: var(--color-text) !important;
        }

        .mpr-internal-dashboard .text-muted {
            color: var(--color-text-soft) !important;
        }

        .mpr-internal-dashboard .mpr-list-header,
        .mpr-internal-dashboard .mpr-filter-bar,
        .mpr-internal-dashboard .mpr-list-footer {
            background: var(--color-surface) !important;
            border-color: var(--color-border) !important;
        }

        .mpr-internal-dashboard .mpr-filter-bar {
            background: var(--color-bg) !important;
        }

        .mpr-internal-dashboard .mpr-filter-bar form {
            --bs-gutter-x: 10px;
            --bs-gutter-y: 10px;
        }

        .mpr-internal-dashboard .mpr-filter-bar .input-group,
        .mpr-internal-dashboard .mpr-filter-bar .input-group-text,
        .mpr-internal-dashboard .mpr-filter-bar .form-control,
        .mpr-internal-dashboard .mpr-filter-bar .form-select {
            min-height: 40px;
        }

        .mpr-internal-dashboard .mpr-filter-bar .input-group-text,
        .mpr-internal-dashboard .mpr-filter-bar .form-control,
        .mpr-internal-dashboard .mpr-filter-bar .form-select {
            border-radius: 10px;
            border-width: 1.5px;
        }

        .mpr-internal-dashboard .mpr-filter-bar .input-group-text {
            border-right: 0;
            border-radius: 10px 0 0 10px;
        }

        .mpr-internal-dashboard .mpr-filter-bar .input-group .form-control {
            border-left: 0;
            border-radius: 0 10px 10px 0;
        }

        .mpr-internal-dashboard .mpr-filter-bar .btn-dark,
        .mpr-internal-dashboard .mpr-filter-bar .btn-outline-secondary {
            min-height: 40px;
            border-radius: 10px;
        }

        .mpr-internal-dashboard .mpr-filter-actions {
            display: flex;
            gap: 10px;
        }

        .mpr-internal-dashboard .mpr-filter-submit {
            flex: 1 1 auto;
            min-width: 0;
            padding: 0 18px;
            font-weight: 600;
        }

        .mpr-internal-dashboard .mpr-filter-reset {
            flex: 0 0 auto;
            min-width: 82px;
            padding: 0 14px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            text-decoration: none;
        }

        .mpr-internal-dashboard .mpr-header-actions {
            display: flex;
            align-items: center;
            gap: 10px;
            flex: 0 0 auto;
        }

        .mpr-internal-dashboard .mpr-create-btn,
        .mpr-internal-dashboard .mpr-refresh-btn {
            height: 40px;
            white-space: nowrap;
        }

        .mpr-internal-dashboard .mpr-create-btn {
            min-width: 136px;
            padding: 0 14px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .mpr-internal-dashboard .mpr-refresh-btn {
            width: 40px;
            min-width: 40px;
            padding: 0;
            flex: 0 0 40px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .mpr-internal-dashboard .input-group-text,
        .mpr-internal-dashboard .form-control,
        .mpr-internal-dashboard .form-select {
            background-color: var(--color-surface) !important;
            border-color: var(--color-border) !important;
            color: var(--color-text) !important;
        }

        /* MPR uses the shared hr-table styling instead of custom table classes. */

        .mpr-internal-dashboard .badge.bg-light {
            background: var(--color-bg) !important;
            color: var(--color-text-soft) !important;
            border-color: var(--color-border) !important;
        }

        .mpr-internal-dashboard .stat-card-mpr {
            min-height: 110px;
        }

        .mpr-internal-dashboard .stat-card-mpr .text-muted {
            font-size: 11.5px;
            font-weight: 600;
            letter-spacing: 0.03em;
            text-transform: uppercase;
            color: var(--color-text-soft) !important;
        }

        .mpr-internal-dashboard .stat-card-mpr .fs-4 {
            line-height: 1.1;
            font-size: 22px !important;
            font-weight: 800 !important;
            color: var(--color-text) !important;
        }

        .mpr-internal-dashboard .mpr-list-panel .table-responsive,
        .mpr-internal-dashboard .mpr-table-wrap {
            display: block;
            width: 100%;
            max-width: 100%;
            overflow-x: auto;
            overflow-y: hidden;
            -webkit-overflow-scrolling: touch;
        }

        .mpr-internal-dashboard .mpr-list-panel table {
            min-width: 980px;
            width: 100%;
            margin-bottom: 0;
            table-layout: auto;
        }

        .mpr-internal-dashboard .mpr-list-panel .btn-group {
            white-space: nowrap;
        }

        @media (max-width: 767.98px) {
            .mpr-card-header {
                padding: 14px 16px;
            }

            .mpr-internal-dashboard .stat-card-mpr {
                padding: 14px;
                gap: 12px;
                min-height: 92px;
            }

            .mpr-internal-dashboard .stat-icon-mpr {
                width: 42px;
                height: 42px;
                font-size: 18px;
                flex-shrink: 0;
            }

            .mpr-internal-dashboard .mpr-list-header,
            .mpr-internal-dashboard .mpr-filter-bar,
            .mpr-internal-dashboard .mpr-list-footer {
                padding-left: 14px !important;
                padding-right: 14px !important;
            }

            .mpr-internal-dashboard .mpr-list-header {
                align-items: flex-start !important;
            }

            .mpr-internal-dashboard .mpr-header-actions {
                width: 100%;
                justify-content: flex-end;
            }

            .mpr-internal-dashboard .mpr-header-actions .btn {
                width: auto;
            }

            .mpr-internal-dashboard .mpr-create-btn {
                min-width: 0;
                flex: 1 1 auto;
            }

            .mpr-internal-dashboard .mpr-refresh-btn {
                width: 40px;
                min-width: 40px;
                flex: 0 0 40px;
            }

            .mpr-internal-dashboard .mpr-filter-actions {
                flex-direction: column;
                width: 100%;
            }

            .mpr-internal-dashboard .mpr-filter-submit,
            .mpr-internal-dashboard .mpr-filter-reset {
                width: 100%;
                flex: 1 1 auto;
            }

            .mpr-internal-dashboard .mpr-list-footer {
                flex-direction: column;
                align-items: flex-start !important;
                gap: 12px;
            }

            .mpr-internal-dashboard .mpr-list-footer .pagination {
                width: 100%;
                justify-content: flex-start;
                flex-wrap: wrap;
            }
        }

        @media (max-width: 575.98px) {
            .mpr-internal-dashboard .mpr-filter-bar form>[class*='col-'] {
                width: 100%;
                flex: 0 0 100%;
                max-width: 100%;
            }

            .mpr-internal-dashboard .mpr-filter-bar form {
                --bs-gutter-x: 8px;
                --bs-gutter-y: 8px;
            }

            .mpr-internal-dashboard .stat-card-mpr .text-muted {
                font-size: 11px;
            }

            .mpr-internal-dashboard .stat-card-mpr .fw-bold {
                font-size: 1.15rem !important;
            }
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

        @media (min-width: 1200px) {
            .mpr-form-card {
                height: calc(100vh - 132px);
            }

            .mpr-form-card .mpr-form-body {
                min-height: 0;
                overflow-y: auto;
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
                            <span class="badge bg-white text-primary fw-bold px-3 py-2">Role: Manpower</span>
                        </div>
                        <div class="card-body p-4 mpr-form-body">

                            <form id="formManagerMpr" action="{{ route('hr.mpr.store') }}" method="POST">
                                @csrf

                                <!-- SECTION: IDENTITAS PEMOHON (READONLY & DISABLED - ANTI FRAUD) -->
                                <div class="mpr-applicant-identity p-3 rounded-3 mb-4 border">
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <span class="text-primary fw-bold small"><i class="bi bi-shield-lock-fill me-1"></i>
                                            Identitas Pemohon (Terverifikasi Sistem)</span>
                                        <span class="badge bg-secondary-subtle text-secondary border"><i
                                                class="bi bi-lock-fill me-1"></i> Terkunci Permanen</span>
                                    </div>
                                    <div class="row g-3">
                                        <div class="col-md-4">
                                            <label class="form-label text-muted small fw-semibold mb-1">Nama Pemohon
                                                (Manager)</label>
                                            <div class="input-group input-group-sm">
                                                <span class="input-group-text bg-white border-end-0 text-muted"><i
                                                        class="bi bi-person-fill"></i></span>
                                                <input type="text"
                                                    class="form-control bg-white border-start-0 text-dark fw-semibold"
                                                    value="{{ $user['fullName'] ?? ($user['name'] ?? 'Manager') }}" readonly
                                                    disabled style="cursor: not-allowed;">
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label text-muted small fw-semibold mb-1">Email
                                                Pemohon</label>
                                            <div class="input-group input-group-sm">
                                                <span class="input-group-text bg-white border-end-0 text-muted"><i
                                                        class="bi bi-envelope-fill"></i></span>
                                                <input type="text"
                                                    class="form-control bg-white border-start-0 text-dark fw-semibold"
                                                    value="{{ $user['email'] ?? '-' }}" readonly disabled
                                                    style="cursor: not-allowed;">
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label text-muted small fw-semibold mb-1">Branch / Lokasi
                                                Pemohon</label>
                                            <div class="input-group input-group-sm">
                                                <span class="input-group-text bg-white border-end-0 text-muted"><i
                                                        class="bi bi-geo-alt-fill"></i></span>
                                                <input type="text"
                                                    class="form-control bg-white border-start-0 text-dark fw-semibold"
                                                    value="{{ !empty($user['branch']) ? $user['branch'] : '-' }}" readonly
                                                    disabled style="cursor: not-allowed;"
                                                    title="Branch diambil otomatis dari profil akun Anda">
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Entity Selector: hanya entity yang menjadi assignment Manager ini --}}
                                    <div class="row g-3 mt-1">
                                        <div class="col-md-6">
                                            <label class="form-label text-muted small fw-semibold mb-1">
                                                Entitas yang Dituju <span class="text-danger">*</span>
                                                <span class="ms-1 text-muted fw-normal" style="font-size:10px;">(pilih dari
                                                    daftar entitas Anda)</span>
                                            </label>
                                            @if (count($allowedEntities) === 0)
                                                <div class="alert alert-warning py-2 px-3 mb-0 small">
                                                    <i class="bi bi-exclamation-triangle-fill me-1"></i>
                                                    Akun Anda belum memiliki entitas yang di-assign. Hubungi administrator
                                                    untuk menambahkan entitas.
                                                </div>
                                                {{-- Hidden fallback agar form tidak kosong --}}
                                                <input type="hidden" name="entity" value="">
                                            @elseif(count($allowedEntities) === 1)
                                                {{-- Jika hanya satu entity, langsung readonly (auto-select) --}}
                                                @php
                                                    $singleEntityCode = array_key_first($allowedEntities);
                                                    $singleEntityLabel = $allowedEntities[$singleEntityCode];
                                                @endphp
                                                <div class="input-group input-group-sm">
                                                    <span class="input-group-text bg-white border-end-0 text-muted"><i
                                                            class="bi bi-building-fill"></i></span>
                                                    <input type="text"
                                                        class="form-control bg-white border-start-0 text-dark fw-semibold"
                                                        value="{{ $singleEntityCode }} — {{ $singleEntityLabel }}" readonly
                                                        disabled style="cursor: not-allowed;">
                                                </div>
                                                <input type="hidden" name="entity" value="{{ $singleEntityCode }}">
                                            @else
                                                <select name="entity" class="form-select form-select-sm" required>
                                                    <option value="">-- Pilih Entitas --</option>
                                                    @foreach ($allowedEntities as $code => $label)
                                                        <option value="{{ $code }}">{{ $code }} —
                                                            {{ $label }}</option>
                                                    @endforeach
                                                </select>
                                            @endif
                                        </div>
                                        <div class="col-md-6 d-flex align-items-end">
                                            <div class="text-muted bg-white border rounded px-3 py-2 w-100 small">
                                                <i class="bi bi-info-circle-fill text-primary me-1"></i>
                                                <strong>Entity</strong> = perusahaan yang menjadi tanggung jawab Anda.<br>
                                                <strong>Branch</strong> = lokasi Anda bertugas, otomatis dari profil akun.
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mt-2 text-muted" style="font-size: 11px;">
                                        <i class="bi bi-shield-check-fill text-success me-1"></i>
                                        Identitas, branch, dan daftar entitas diambil dari sesi akun login Anda. Pilih
                                        entitas yang sesuai untuk pengajuan ini.
                                    </div>
                                </div>

                                <!-- SECTION: DETAIL POSISI -->
                                <h6 class="fw-bold text-primary mb-3"><i class="bi bi-briefcase-fill me-2"></i>Detail
                                    Posisi yang Dibutuhkan</h6>
                                <div class="row g-3 mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold small">Posisi / Jabatan yang Diminta <span
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

                                <!-- SECTION: ALASAN PERMINTAAN -->
                                <h6 class="fw-bold text-primary mb-3 mt-4"><i
                                        class="bi bi-question-circle-fill me-2"></i>Alasan & Kualifikasi</h6>
                                <div class="row g-3 mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold small">Alasan Permintaan <span
                                                class="text-danger">*</span></label>
                                        <select name="reason" class="form-select" id="mprReasonSelect" required>
                                            <option value="">-- Pilih Alasan Permintaan --</option>
                                            @foreach ($reasons as $r)
                                                <option value="{{ $r }}">{{ $r }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold small">Nama Karyawan yang Digantikan
                                            (Opsional)</label>
                                        <input type="text" name="replacement_for" class="form-control"
                                            placeholder="Diisi jika alasan adalah penggantian">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label fw-semibold small">Kualifikasi & Persyaratan Khusus
                                            Kandidat</label>
                                        <textarea name="requirements" class="form-control" rows="3"
                                            placeholder="Contoh: Pendidikan min. S1 Informatika, Pengalaman min. 2 tahun di Laravel, memiliki komunikasi baik..."></textarea>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label fw-semibold small">Uraian Tugas & Tanggung Jawab
                                            Utama</label>
                                        <textarea name="job_description" class="form-control" rows="3"
                                            placeholder="Contoh: Mengembangkan fitur web HRIS, melakukan code review, memastikan performa database..."></textarea>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label fw-semibold small">Catatan Tambahan (Opsional)</label>
                                        <textarea name="notes" class="form-control" rows="2"
                                            placeholder="Catatan atau instruksi tambahan untuk tim rekrutmen..."></textarea>
                                    </div>
                                </div>

                                <hr class="my-4">

                                <div class="d-flex justify-content-end gap-2">
                                    <button type="reset" class="btn btn-light px-3">Reset Form</button>
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
                    <div class="card border-0 shadow-sm rounded-3 h-100">
                        <div class="card-header mpr-history-header py-3 border-bottom">
                            <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-clock-history me-2 text-primary"></i>
                                Riwayat Pengajuan MPR Saya</h6>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table hr-table">
                                    <thead>
                                        <tr>
                                            <th>No. MPR</th>
                                            <th>Posisi & Dept</th>
                                            <th>Qty</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody id="managerMprTableBody">
                                        @forelse($paginatedMprs as $mpr)
                                            <tr>
                                                <td>
                                                    <strong class="text-primary">{{ $mpr->mprNumber }}</strong><br>
                                                    <small
                                                        class="text-muted">{{ $mpr->requestDate ? date('d/m/Y', strtotime($mpr->requestDate)) : '-' }}</small>
                                                </td>
                                                <td>
                                                    <span class="fw-semibold text-dark">{{ $mpr->position }}</span><br>
                                                    <small class="text-muted">{{ $mpr->department }}</small><br>
                                                    <small class="badge bg-light text-secondary border"
                                                        style="font-size:10px;">{{ $mpr->entity ?: $mpr->company }}</small>
                                                </td>
                                                <td>
                                                    <span class="badge bg-light text-dark border">{{ $mpr->quantity }}
                                                        Org</span>
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
            <div class="row g-3 mb-4 mpr-internal-dashboard">
                <div class="col-12 col-sm-6 col-xl-3">
                    <div class="stat-card">
                        <div class="stat-icon bg-blue"><i class="bi bi-file-earmark-text-fill"></i></div>
                        <div class="flex-grow-1">
                            <div class="stat-label">Total Pengajuan MPR</div>
                            <div class="stat-value">{{ $stats['total'] ?? 0 }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-xl-3">
                    <div class="stat-card">
                        <div class="stat-icon bg-green"><i class="bi bi-calendar-check-fill"></i></div>
                        <div class="flex-grow-1">
                            <div class="stat-label">Pengajuan Bulan Ini</div>
                            <div class="stat-value">{{ $stats['this_month'] ?? 0 }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-xl-3">
                    <div class="stat-card">
                        <div class="stat-icon bg-purple"><i class="bi bi-people-fill"></i></div>
                        <div class="flex-grow-1">
                            <div class="stat-label">Total Kebutuhan Manpower</div>
                            <div class="stat-value">{{ $stats['total_quantity'] ?? 0 }} <span
                                    class="fs-6 fw-normal text-muted">Orang</span></div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-xl-3">
                    <div class="stat-card">
                        <div class="stat-icon bg-gold"><i class="bi bi-send-check-fill"></i></div>
                        <div class="flex-grow-1">
                            <div class="stat-label">Status Submitted</div>
                            <div class="stat-value">{{ $stats['submitted'] ?? 0 }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- MAIN TABLE PANEL -->
            <div class="panel mb-4 mpr-internal-dashboard mpr-list-panel">
                <div
                    class="card-header bg-white mpr-list-header py-3 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 border-bottom">
                    <div>
                        <h5 class="mb-0 fw-bold text-dark"><i class="bi bi-list-task me-2 text-primary"></i>Daftar
                            Manpower Request (MPR)</h5>
                        <small class="text-muted">Menampilkan {{ $total }} pengajuan kebutuhan tenaga kerja</small>
                    </div>
                    <div class="mpr-header-actions">
                        @can('create_mpr')
                            <button type="button" class="btn btn-primary btn-sm fw-semibold shadow-sm mpr-create-btn"
                                data-bs-toggle="modal" data-bs-target="#modalCreateMpr"><i
                                    class="bi bi-plus-circle-fill me-1"></i>Buat MPR Baru</button>
                        @endcan
                        <button type="button" class="btn btn-outline-secondary mpr-refresh-btn" title="Muat ulang data"
                            aria-label="Muat ulang data" data-refresh="page">
                            <i class="bi bi-arrow-clockwise" aria-hidden="true"></i>
                        </button>
                    </div>
                </div>

                <!-- FILTER BAR -->
                <div class="card-body bg-light mpr-filter-bar border-bottom py-3">
                    <form action="{{ route('hr.mpr.index') }}" method="GET" class="row g-2 align-items-center">
                        <div class="col-12 col-md-3">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-white border-end-0"><i
                                        class="bi bi-search text-muted"></i></span>
                                <input type="text" name="search" class="form-control border-start-0"
                                    placeholder="Cari No MPR, pemohon, posisi, divisi..." value="{{ $search }}">
                            </div>
                        </div>
                        <div class="col-12 col-sm-6 col-md-3">
                            <select name="department" class="form-select form-select-sm" onchange="this.form.submit()">
                                <option value="">Semua Departemen</option>
                                @foreach ($departments as $d)
                                    <option value="{{ $d }}" {{ $dept === $d ? 'selected' : '' }}>
                                        {{ $d }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12 col-sm-6 col-md-3">
                            <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                                <option value="">Semua Status</option>
                                <option value="Submitted" {{ $status === 'Submitted' ? 'selected' : '' }}>Submitted
                                </option>
                            </select>
                        </div>
                        <div class="col-12 col-sm-6 col-md-3">
                            <select name="submit_by" class="form-select form-select-sm" onchange="this.form.submit()">
                                <option value="">Submit By</option>
                                @foreach ($submitByOptions as $requestor)
                                    <option value="{{ $requestor }}" {{ $submitBy === $requestor ? 'selected' : '' }}>
                                        {{ $requestor }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </form>
                </div>

                <!-- TABLE -->
                <div class="card-body p-0">
                    <div class="table-responsive mpr-table-wrap">
                        <table class="table hr-table mb-0">
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
                                            <small class="text-muted"><i
                                                    class="bi bi-calendar3 me-1"></i>{{ $mpr->requestDate ? date('d M Y', strtotime($mpr->requestDate)) : '-' }}</small>
                                        </td>
                                        <td>
                                            <div class="fw-semibold text-dark">{{ $mpr->requestorName }}</div>
                                            <small class="text-muted">{{ $mpr->requestorEmail }}</small>
                                        </td>
                                        <td>
                                            <div class="fw-medium text-dark">{{ $mpr->department }}</div>
                                            <small class="text-muted">{{ $mpr->entity ?: $mpr->company }}</small>
                                            @if (!empty($mpr->branch))
                                                <div><small class="text-muted" style="font-size:10px;"><i
                                                            class="bi bi-geo-alt me-1"></i>{{ $mpr->branch }}</small>
                                                </div>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="fw-semibold text-dark">{{ $mpr->position }}</div>
                                            <small
                                                class="badge bg-light text-secondary border">{{ $mpr->jobLevel ?? '-' }}</small>
                                            <small
                                                class="badge bg-light text-secondary border">{{ $mpr->employmentType ?? '-' }}</small>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-primary px-2 py-1 fs-6">{{ $mpr->quantity }}</span>
                                            <div class="small text-muted" style="font-size:10px;">Orang</div>
                                        </td>
                                        <td>
                                            <span
                                                class="text-dark">{{ $mpr->expectedJoinDate ? date('d M Y', strtotime($mpr->expectedJoinDate)) : '-' }}</span>
                                        </td>
                                        <td>
                                            <span
                                                class="badge bg-success-subtle text-success border border-success-subtle">{{ strtoupper($mpr->status ?? 'SUBMITTED') }}</span>
                                        </td>
                                        <td class="text-center">
                                            <div class="btn-group btn-group-sm">
                                                <button type="button" class="btn btn-outline-primary btn-mpr-detail"
                                                    data-id="{{ $mpr->mprNumber }}" title="Lihat Detail">
                                                    <i class="bi bi-eye-fill"></i>
                                                </button>
                                                @can('export_mpr')
                                                    @if (filled($mpr->mprNumber))
                                                        <a href="{{ route('hr.mpr.pdf', ['id' => $mpr->mprNumber]) }}"
                                                            target="_blank" class="btn btn-outline-danger"
                                                            title="Unduh PDF Resmi">
                                                            <i class="bi bi-file-earmark-pdf-fill"></i>
                                                        </a>
                                                    @else
                                                        <button type="button" class="btn btn-outline-secondary" disabled
                                                            title="Nomor MPR tidak tersedia">
                                                            <i class="bi bi-file-earmark-pdf-fill"></i>
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

                <!-- PAGINATION -->
                @if ($lastPage > 1)
                    <div
                        class="card-footer bg-white mpr-list-footer py-3 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 border-top">
                        <div class="small text-muted">
                            Menampilkan halaman <strong>{{ $currentPage }}</strong> dari
                            <strong>{{ $lastPage }}</strong>
                        </div>
                        <ul class="pagination pagination-sm mb-0">
                            <li class="page-item {{ $currentPage <= 1 ? 'disabled' : '' }}">
                                <a class="page-link"
                                    href="{{ request()->fullUrlWithQuery(['page' => $currentPage - 1]) }}">Sebelumnya</a>
                            </li>
                            @for ($p = 1; $p <= $lastPage; $p++)
                                <li class="page-item {{ $p === $currentPage ? 'active' : '' }}">
                                    <a class="page-link"
                                        href="{{ request()->fullUrlWithQuery(['page' => $p]) }}">{{ $p }}</a>
                                </li>
                            @endfor
                            <li class="page-item {{ $currentPage >= $lastPage ? 'disabled' : '' }}">
                                <a class="page-link"
                                    href="{{ request()->fullUrlWithQuery(['page' => $currentPage + 1]) }}">Berikutnya</a>
                            </li>
                        </ul>
                    </div>
                @endif

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

                                    <!-- INFORMASI PEMOHON -->
                                    <h6 class="fw-bold text-primary mb-4">1. Informasi Pemohon / Requester</h6>
                                    <div class="row g-3 mb-4 p-3 rounded-3 border">
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold small">Nama Manager Pemohon <span
                                                    class="text-danger">*</span></label>
                                            <input type="text" name="manager_name" class="form-control form-control-sm"
                                                value="{{ $user['fullName'] ?? 'HR Manager' }}" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold small">Email Manager Pemohon <span
                                                    class="text-danger">*</span></label>
                                            <input type="email" name="manager_email" class="form-control form-control-sm"
                                                value="{{ $user['email'] ?? '' }}" required>
                                        </div>
                                        <div class="col-md-12">
                                            <label class="form-label fw-semibold small">Entitas / Perusahaan <span
                                                    class="text-danger">*</span></label>
                                            <select name="entity" class="form-select form-select-sm" required>
                                                <option value="">-- Pilih Entitas --</option>
                                                @foreach ($entityOptions as $code => $label)
                                                    <option value="{{ $code }}">{{ $code }} —
                                                        {{ $label }}</option>
                                                @endforeach
                                            </select>
                                            <div class="form-text text-muted" style="font-size:11px;">Pilih entitas (company)
                                                yang akan tercantum di dokumen MPR ini.</div>
                                        </div>
                                    </div>

                                    <!-- DETAIL POSISI -->
                                    <h6 class="fw-bold text-primary mb-3">2. Detail Posisi & Organisasi</h6>
                                    <div class="row g-3 mb-4">
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold small">Posisi / Jabatan yang Diminta <span
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
                                                class="form-control form-control-sm" required>
                                        </div>
                                    </div>

                                    <!-- ALASAN & DESKRIPSI -->
                                    <h6 class="fw-bold text-primary mb-3">3. Alasan & Kualifikasi</h6>
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
                                                (Opsional)</label>
                                            <input type="text" name="replacement_for" class="form-control form-control-sm"
                                                placeholder="Diisi jika penggantian">
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
                                            <label class="form-label fw-semibold small">Catatan Tambahan (Opsional)</label>
                                            <textarea name="notes" class="form-control form-control-sm" rows="2" placeholder="Catatan lain..."></textarea>
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
        <div class="modal fade" id="modalMprDetail" tabindex="-1" aria-labelledby="modalMprDetailLabel"
            aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content border-0 shadow">
                    <div class="modal-header bg-primary text-white">
                        <div>
                            <h5 class="modal-title fw-bold" id="modalMprDetailLabel"><i
                                    class="bi bi-file-earmark-text me-2"></i> Detail Manpower Request</h5>
                            <small class="text-white" id="detailMprNumber">-</small>
                        </div>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                            aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4" id="modalMprDetailBody">
                        <div class="text-center py-5" id="detailLoading">
                            <div class="spinner-border text-primary" role="status"></div>
                            <div class="mt-2 text-muted small">Memuat data MPR...</div>
                        </div>
                        <div id="detailContent" class="d-none">
                            <!-- SECTION 1 -->
                            <div class="card bg-light border-0 mb-3 p-3 rounded-3">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <div class="small text-uppercase text-muted fw-semibold mb-2">Pemohon (Manager)
                                        </div>
                                        <div class="fw-semibold text-dark" id="detManagerName">-</div>
                                        <div class="small text-muted" id="detManagerEmail">-</div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="small text-uppercase text-muted fw-semibold mb-2">Entitas / Perusahaan
                                        </div>
                                        <div class="fw-semibold text-dark" id="detCompany">-</div>
                                        <div class="small text-muted" id="detBranch">-</div>
                                        <div class="small text-muted" id="detCreatedBy">Diajukan: -</div>
                                    </div>
                                </div>
                            </div>

                            <!-- SECTION 2 -->
                            <h6 class="fw-bold text-primary mb-2">Detail Posisi & Kebutuhan</h6>
                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <div class="small text-uppercase text-muted fw-semibold mb-2">Posisi / Jabatan</div>
                                    <div class="text-primary fw-bold" id="detPosition">-</div>
                                </div>
                                <div class="col-md-6">
                                    <div class="small text-uppercase text-muted fw-semibold mb-2">Departemen / Divisi</div>
                                    <div class="fw-semibold text-dark" id="detDeptDiv">-</div>
                                </div>
                                <div class="col-md-4">
                                    <div class="small text-uppercase text-muted fw-semibold mb-2">Level Jabatan</div>
                                    <div class="fw-semibold text-dark" id="detJobLevel">-</div>
                                </div>
                                <div class="col-md-4">
                                    <div class="small text-uppercase text-muted fw-semibold mb-2">Status Kepegawaian</div>
                                    <div class="fw-semibold text-dark" id="detEmpType">-</div>
                                </div>
                                <div class="col-md-4">
                                    <div class="small text-uppercase text-muted fw-semibold mb-2">Lokasi Penempatan</div>
                                    <div class="fw-semibold text-dark" id="detLocation">-</div>
                                </div>
                                <div class="col-md-6">
                                    <div class="small text-uppercase text-muted fw-semibold mb-2">Jumlah Kebutuhan</div>
                                    <div class="fw-semibold text-dark"><span class="badge bg-primary fs-6"
                                            id="detQuantity">-</span>
                                        Orang</div>
                                </div>
                                <div class="col-md-6">
                                    <div class="small text-uppercase text-muted fw-semibold mb-2">Target Tanggal Masuk
                                        (Join Date)</div>
                                    <div class="fw-bold text-dark" id="detJoinDate">-</div>
                                </div>
                            </div>

                            <!-- SECTION 3 -->
                            <h6 class="fw-bold text-primary mb-2">Alasan Permintaan</h6>
                            <div class="bg-light p-3 rounded-3 mb-3 border">
                                <div class="small text-uppercase text-muted fw-semibold mb-2">Alasan</div>
                                <div class="mb-2 fw-semibold text-dark" id="detReason">-</div>
                                <div id="wrapReplacement" class="d-none">
                                    <div class="small text-uppercase text-muted fw-semibold mb-2">Menggantikan Karyawan
                                    </div>
                                    <div class="fw-semibold text-dark" id="detReplacementFor">-</div>
                                </div>
                            </div>

                            <!-- SECTION 4 -->
                            <h6 class="fw-bold text-primary mb-2">Kualifikasi & Deskripsi</h6>
                            <div class="row g-3 mb-3">
                                <div class="col-12">
                                    <div class="small text-uppercase text-muted fw-semibold mb-2">Kualifikasi Kandidat
                                    </div>
                                    <div class="p-2 border rounded bg-white small mpr-markdown-content"
                                        id="detRequirements" style="min-height:60px;">-</div>
                                </div>
                                <div class="col-12">
                                    <div class="small text-uppercase text-muted fw-semibold mb-2">Uraian Tugas & Tanggung
                                        Jawab</div>
                                    <div class="p-2 border rounded bg-white small mpr-markdown-content" id="detJobDesc"
                                        style="min-height:60px;">-</div>
                                </div>
                                <div class="col-12" id="wrapNotes">
                                    <div class="small text-uppercase text-muted fw-semibold mb-2">Catatan Tambahan</div>
                                    <div class="p-2 border rounded bg-white small mpr-markdown-content" id="detNotes">-
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer bg-light border-top">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Tutup</button>
                        @can('update_mpr')
                            <button type="button" id="btnEditMpr" class="btn btn-primary btn-sm fw-semibold">
                                <i class="bi bi-pencil-square me-1"></i> Edit MPR
                            </button>
                        @endcan
                        <a id="btnModalDownloadPdf" href="#" target="_blank"
                            class="btn btn-danger btn-sm fw-semibold">
                            <i class="bi bi-file-earmark-pdf-fill me-1"></i> Unduh PDF Resmi
                        </a>
                    </div>
                </div>
            </div>
        </div>

        @can('update_mpr')
            <div class="modal fade" id="modalEditMpr" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-scrollable">
                    <div class="modal-content border-0 shadow">
                        <div class="modal-header bg-primary text-white">
                            <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square me-2"></i>Edit MPR <small
                                    id="editMprNumber"></small></h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                        <form id="formEditMpr">
                            <div class="modal-body p-4">
                                <div id="editMprErrors" class="alert alert-danger d-none"></div>
                                <div class="row g-3">
                                    <div class="col-md-6"><label class="form-label small fw-semibold">Posisi *</label><input
                                            name="position" class="form-control" required></div>
                                    <div class="col-md-6"><label class="form-label small fw-semibold">Departemen
                                            *</label><select name="department" class="form-select" required>
                                            <option value="">-- Pilih --</option>
                                            @foreach ($departments as $d)
                                                <option value="{{ $d }}">{{ $d }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-6"><label class="form-label small fw-semibold">Divisi *</label><select
                                            name="division" class="form-select"
                                            data-selected-division="{{ old('division') }}" required disabled>
                                            <option value="">-- Pilih Departemen terlebih dahulu --</option>
                                        </select></div>
                                    <div class="col-md-6"><label class="form-label small fw-semibold">Level Jabatan
                                            *</label><select name="job_level" class="form-select" required>
                                            <option value="">-- Pilih --</option>
                                            @foreach ($jobLevels as $d)
                                                <option value="{{ $d }}">{{ $d }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-6"><label class="form-label small fw-semibold">Lokasi Penempatan
                                            *</label><select name="work_location" class="form-select" required>
                                            <option value="">-- Pilih --</option>
                                            @foreach ($workLocations as $d)
                                                <option value="{{ $d }}">{{ $d }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-6"><label class="form-label small fw-semibold">Status Kepegawaian
                                            *</label><select name="employment_type" class="form-select" required>
                                            <option value="">-- Pilih --</option>
                                            @foreach ($employmentTypes as $d)
                                                <option value="{{ $d }}">{{ $d }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-6"><label class="form-label small fw-semibold">Jumlah Kebutuhan
                                            *</label><input name="quantity" type="number" min="1" max="100"
                                            class="form-control" required></div>
                                    <div class="col-md-6"><label class="form-label small fw-semibold">Target Bergabung
                                            *</label><input name="expected_join_date" type="date" class="form-control"
                                            required></div>
                                    <div class="col-md-6"><label class="form-label small fw-semibold">Alasan Permintaan
                                            *</label><select name="reason" class="form-select" required>
                                            <option value="">-- Pilih --</option>
                                            @foreach ($reasons as $d)
                                                <option value="{{ $d }}">{{ $d }}</option>
                                            @endforeach
                                        </select></div>
                                    <div class="col-md-6"><label class="form-label small fw-semibold">Karyawan yang
                                            Digantikan</label><input name="replacement_for" class="form-control"></div>
                                    <div class="col-12"><label class="form-label small fw-semibold">Kualifikasi &
                                            Persyaratan</label>
                                        <textarea name="requirements" rows="4" class="form-control"></textarea>
                                    </div>
                                    <div class="col-12"><label class="form-label small fw-semibold">Uraian Tugas</label>
                                        <textarea name="job_description" rows="4" class="form-control"></textarea>
                                    </div>
                                    <div class="col-12"><label class="form-label small fw-semibold">Catatan</label>
                                        <textarea name="notes" rows="3" class="form-control"></textarea>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-outline-secondary btn-sm"
                                    data-bs-dismiss="modal">Batal</button>
                                <button type="button" id="btnPreviewMpr" class="btn btn-outline-primary btn-sm"><i
                                        class="bi bi-eye me-1"></i> Preview</button>
                                <button type="submit" id="btnSaveMpr" class="btn btn-primary btn-sm"><i
                                        class="bi bi-check-circle me-1"></i> Save Changes</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="modal fade" id="modalPreviewMpr" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-scrollable">
                    <div class="modal-content border-0 shadow">
                        <div class="modal-header bg-primary text-white">
                            <h5 class="modal-title">Preview Perubahan MPR</h5><button type="button"
                                class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body" id="mprPreviewContent"></div>
                        <div class="modal-footer"><button type="button" class="btn btn-secondary btn-sm"
                                data-bs-dismiss="modal">Kembali ke Edit</button><button type="button"
                                class="btn btn-primary btn-sm" id="btnPreviewSaveMpr">Save Changes</button></div>
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
                                .requestor_name || m.manager_name || '-';
                            document.getElementById('detManagerEmail').innerText = m
                                .requestor_email || m.manager_email || '-';
                            document.getElementById('detCompany').innerText = m.entity || m
                                .company || '-';
                            const detBranch = document.getElementById('detBranch');
                            if (detBranch) detBranch.innerText = m.branch ?
                                `Branch: ${m.branch}` : '';
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
                            document.getElementById('detNotes').innerHTML = m.notes_html ||
                                '<span class="text-muted fst-italic">-</span>';

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
                                    Object.keys(m).forEach(key => {
                                        const field = editForm.elements[key];
                                        if (field && !key.endsWith('_html') &&
                                            key !== 'department' && key !==
                                            'division') field.value = m[key] ?? '';
                                    });
                                    editForm.elements.department.value = m.department || '';
                                    setupDepartmentDivision(editForm, m.division || '');
                                    document.getElementById('editMprErrors').classList.add(
                                        'd-none');
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

            function escapeHtml(value) {
                return String(value || '').replace(/[&<>'"]/g, char => ({
                    '&': '&amp;',
                    '<': '&lt;',
                    '>': '&gt;',
                    "'": '&#039;',
                    '"': '&quot;'
                } [char]));
            }

            function renderMprMarkdown(value) {
                let html = escapeHtml(value);
                html = html.replace(/^### (.+)$/gm, '<h5>$1</h5>')
                    .replace(/^## (.+)$/gm, '<h4>$1</h4>')
                    .replace(/^# (.+)$/gm, '<h3>$1</h3>')
                    .replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
                    .replace(/\*(.+?)\*/g, '<em>$1</em>')
                    .replace(/^(?:- )(.+)$/gm, '<li>$1</li>')
                    .replace(/(?:<li>.*<\/li>\n?)+/g, match => `<ul>${match}</ul>`)
                    .replace(/\n/g, '<br>');
                return html || '<span class="text-muted">-</span>';
            }

            function showMprPreview() {
                const form = document.getElementById('formEditMpr');
                const value = name => form.elements[name]?.value || '-';
                const section = (label, content, markdown = false) =>
                    `<div class="mb-3"><div class="small text-uppercase text-muted fw-semibold mb-2">${label}</div><div class="p-2 border rounded ${markdown ? 'mpr-markdown-content' : ''}">${markdown ? renderMprMarkdown(content) : escapeHtml(content)}</div></div>`;
                document.getElementById('mprPreviewContent').innerHTML =
                    `<h6 class="text-primary fw-bold mb-3">MPR ${escapeHtml(currentMprId)}</h6>` +
                    section('Posisi', value('position')) + section('Departemen / Divisi',
                        `${value('department')} / ${value('division')}`) +
                    section('Level / Status / Lokasi',
                        `${value('job_level')} / ${value('employment_type')} / ${value('work_location')}`) +
                    section('Jumlah / Target Bergabung',
                        `${value('quantity')} Orang / ${value('expected_join_date')}`) + section('Alasan', value(
                        'reason')) +
                    section('Kualifikasi & Persyaratan', value('requirements'), true) + section('Uraian Tugas',
                        value('job_description'), true) + section('Catatan', value('notes'), true);
                bootstrap.Modal.getOrCreateInstance(document.getElementById('modalPreviewMpr')).show();
            }

            const previewButton = document.getElementById('btnPreviewMpr');
            if (previewButton) previewButton.addEventListener('click', showMprPreview);
            const previewSaveButton = document.getElementById('btnPreviewSaveMpr');
            if (previewSaveButton) previewSaveButton.addEventListener('click', () => {
                bootstrap.Modal.getOrCreateInstance(document.getElementById('modalPreviewMpr')).hide();
                document.getElementById('formEditMpr').requestSubmit();
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
    </script>
@endsection
