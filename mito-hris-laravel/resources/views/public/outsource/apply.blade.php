@extends('layouts.public')

@section('title', 'Registrasi Karyawan Outsource - MITO Group')
@section('description', 'Formulir registrasi karyawan outsource untuk keperluan administrasi HRIS MITO.')
@section('robots', 'noindex,follow,noarchive')

@php
    $osConfig = config('hris.outsource', []);
    $today = now()->timezone('Asia/Jakarta')->toDateString();
@endphp

@section('content')
    <style>
        #formOutsource.contact-duplicate-locked .form-section:not(#sectionContact),
        #formOutsource.contact-duplicate-locked .card {
            opacity: 0.55;
            pointer-events: none;
            filter: grayscale(0.12);
        }

        #whatsapp_number.contact-duplicate,
        #email.contact-duplicate {
            border-color: #dc2626;
            background: #fff5f5;
        }

        #contactLockBanner {
            border-radius: 14px;
        }
    </style>
    <!-- HERO -->
    <div class="hero-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-7 col-md-8">
                    <div class="hero-org">MITO Group</div>
                    <div class="hero-dept">Human Resources Department</div>
                    <h1 class="hero-title">Registrasi Karyawan Outsource</h1>
                    <p class="hero-subtitle">Formulir pendataan karyawan outsource ke dalam sistem HRIS MITO Group. Lengkapi
                        semua data dengan benar untuk keperluan administrasi.</p>
                </div>
                <div class="col-lg-5 col-md-4 d-none d-md-flex justify-content-end align-items-center">
                    <img id="heroAvatarImg" src="{{ asset('assets/mito.png') }}" alt="MITO Official" class="hero-avatar-img"
                        loading="lazy">
                </div>
            </div>
        </div>
    </div>

    <!-- PROGRESS -->
    <div class="progress-section">
        <div class="container">
            <div class="progress-label">Progress Pengisian</div>
            <div class="progress-bar-wrap">
                <div class="progress-bar-fill" id="progressFill"></div>
            </div>
            <div class="progress-meta">
                <span id="progressCount">0 dari 20 data telah lengkap</span>
                <span class="progress-message" id="progressMessage">Mulai mengisi formulir...</span>
            </div>
        </div>
    </div>

    <div class="content-wrap py-4">
        <div class="container" style="max-width:960px;">

            @if (session('error'))
                <div class="alert alert-danger alert-dismissible fade show p-3 mb-4 rounded-3 d-flex align-items-center gap-2"
                    role="alert">
                    <i class="bi bi-exclamation-triangle-fill fs-5 flex-shrink-0"></i>
                    <div>{{ session('error') }}</div>
                    <button type="button" class="btn-close btn-sm p-3" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger p-3 mb-4 rounded-3" role="alert">
                    <div class="d-flex align-items-start gap-2">
                        <i class="bi bi-exclamation-triangle-fill fs-5 flex-shrink-0 mt-1"></i>
                        <div>
                            <strong>Form belum bisa dikirim.</strong>
                            <div class="mt-1" style="font-size:13px;">Periksa isian yang ditandai, lalu kirim ulang.</div>
                            <ul class="mb-0 mt-2 ps-3" style="font-size:13px;">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            @endif

            <div id="registrationForm">
                <form action="{{ route('public.outsource.store') }}" method="POST" id="formOutsource" novalidate>
                    @csrf
                    <input type="hidden" name="consent_timestamp" id="consentTimestamp">
                    <input type="hidden" name="consent_device" id="consentDevice">
                    <input type="hidden" name="consent_latitude" id="consentLatitude">
                    <input type="hidden" name="consent_longitude" id="consentLongitude">
                    <input type="hidden" name="consent_location" id="consentLocation">

                    <!-- SECTION 1: PERSONAL INFORMATION -->
                    <div class="form-section" id="sectionPersonal">
                        <div class="form-section-header">
                            <i class="bi bi-person-fill"></i>
                            <div>
                                <h5>Informasi Pribadi</h5>
                                <p>Data identitas sesuai KTP</p>
                            </div>
                            <span class="section-status" id="secBadgePersonal"><i class="bi bi-hourglass-split"></i></span>
                        </div>
                        <div class="form-section-body p-4">
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label fw-semibold" for="full_name" style="font-size:13px">Nama
                                        (sesuai KTP) <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="full_name" name="full_name"
                                        value="{{ old('full_name') }}" required minlength="3" maxlength="255"
                                        pattern="[\p{L}'.]+( [\p{L}'.]+)*" autocomplete="name">
                                    <div class="invalid-feedback">Nama hanya boleh berisi huruf, spasi, titik, dan apostrof.</div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fw-semibold" for="birth_place" style="font-size:13px">Kota
                                        Kelahiran <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="birth_place" name="birth_place"
                                        value="{{ old('birth_place') }}" required minlength="3" maxlength="120"
                                        pattern="[\p{L}'.]+( [\p{L}'.]+)*" placeholder="Contoh: Jakarta">
                                    <div class="invalid-feedback">Kota kelahiran hanya boleh berisi huruf dan spasi.</div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fw-semibold" for="birth_date" style="font-size:13px">Tanggal
                                        Lahir <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control" id="birth_date" name="birth_date"
                                        value="{{ old('birth_date') }}" required min="1900-01-01" max="{{ $today }}"
                                        autocomplete="bday">
                                    <div class="invalid-feedback">Tanggal lahir wajib diisi dan usia minimal 17 tahun.</div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fw-semibold" for="age" style="font-size:13px">Usia</label>
                                    <input type="number" class="form-control" id="age" readonly
                                        style="background-color:#f8f9fa;cursor:default;"
                                        placeholder="Otomatis dari Tanggal Lahir">
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fw-semibold" for="last_education"
                                        style="font-size:13px">Pendidikan Terakhir <span class="text-danger">*</span></label>
                                    <select class="form-select" id="last_education" name="last_education" required>
                                        <option value="">-- Pilih Pendidikan --</option>
                                        @foreach ($osConfig['education_levels'] ?? [] as $level)
                                            <option value="{{ $level }}" @selected(old('last_education') === $level)>{{ $level }}</option>
                                        @endforeach
                                    </select>
                                    <div class="invalid-feedback">Pendidikan terakhir wajib dipilih.</div>
                                </div>

                                <div class="col-12">
                                    <label class="form-label fw-semibold" for="citizen_id_address"
                                        style="font-size:13px">Alamat sesuai KTP <span class="text-danger">*</span></label>
                                    <textarea class="form-control" id="citizen_id_address" name="citizen_id_address" rows="3" required
                                        minlength="5" maxlength="500" placeholder="Jalan, RT/RW, kelurahan, kecamatan, kota">{{ old('citizen_id_address') }}</textarea>
                                    <div class="invalid-feedback">Alamat sesuai KTP wajib diisi (minimal 5 karakter).</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- SECTION 2: CONTACT -->
                    <div class="form-section" id="sectionContact">
                        <div class="form-section-header">
                            <i class="bi bi-whatsapp"></i>
                            <div>
                                <h5>Informasi Kontak</h5>
                                <p>Nomor WhatsApp dan email aktif</p>
                            </div>
                            <span class="section-status" id="secBadgeContact"><i class="bi bi-hourglass-split"></i></span>
                        </div>
                        <div class="form-section-body p-4">
                            <div id="contactLockBanner" class="alert alert-danger d-none p-3 mb-3 d-flex align-items-start gap-2"
                                role="alert">
                                <i class="bi bi-exclamation-octagon-fill fs-5 flex-shrink-0 mt-1"></i>
                                <div>
                                    <strong>Kontak sudah terdaftar.</strong>
                                    <div id="contactLockBannerMessage" class="mt-1" style="font-size:13px;"></div>
                                    <div class="mt-1" style="font-size:12px;">Ubah nomor WhatsApp atau email untuk membuka
                                        kembali formulir.</div>
                                </div>
                            </div>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold" for="whatsapp_number" style="font-size:13px">No
                                        WhatsApp <span class="text-danger">*</span></label>
                                    <div class="input-group-prefix d-flex">
                                        <span
                                            class="input-prefix d-flex align-items-center px-3 border border-end-0 rounded-start bg-light text-muted"
                                            style="font-size:13px;">+62</span>
                                        <input type="tel" class="form-control rounded-start-0" id="whatsapp_number"
                                            name="whatsapp_number" value="{{ old('whatsapp_number') }}" required
                                            maxlength="13" pattern="8[0-9]{7,12}" autocomplete="tel" inputmode="numeric"
                                            placeholder="81234567890">
                                    </div>
                                    <div class="invalid-feedback">Nomor WhatsApp tidak valid. Gunakan format 8xxxxxxxxxx.</div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fw-semibold" for="email" style="font-size:13px">Email
                                        <span class="text-danger">*</span></label>
                                    <input type="email" class="form-control" id="email" name="email"
                                        value="{{ old('email') }}" required maxlength="255" autocomplete="email"
                                        placeholder="nama@email.com">
                                    <div class="invalid-feedback">Format alamat email tidak valid.</div>
                                    <div id="emailValidation" class="mt-1" style="display:none;font-size:12px;"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- SECTION 3: PLACEMENT & CONTRACT -->
                    <div class="form-section" id="sectionEmployment">
                        <div class="form-section-header">
                            <i class="bi bi-briefcase-fill"></i>
                            <div>
                                <h5>Penempatan &amp; Kontrak</h5>
                                <p>Vendor, jabatan, lokasi kerja, dan periode kontrak</p>
                            </div>
                            <span class="section-status" id="secBadgeEmployment"><i class="bi bi-hourglass-split"></i></span>
                        </div>
                        <div class="form-section-body p-4">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold" for="vendor" style="font-size:13px">Vendor
                                        <span class="text-danger">*</span></label>
                                    <select class="form-select" id="vendor" name="vendor" required>
                                        <option value="">-- Pilih Vendor --</option>
                                        @foreach ($osConfig['vendors'] ?? [] as $vendorName)
                                            <option value="{{ $vendorName }}" @selected(old('vendor') === $vendorName)>{{ $vendorName }}</option>
                                        @endforeach
                                    </select>
                                    <div class="invalid-feedback">Vendor wajib dipilih.</div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fw-semibold" for="entity" style="font-size:13px">Entity
                                        <span class="text-danger">*</span></label>
                                    <select class="form-select" id="entity" name="entity" required>
                                        <option value="">-- Pilih Entity --</option>
                                        @foreach ($osConfig['entities'] ?? [] as $entityName)
                                            <option value="{{ $entityName }}" @selected(old('entity') === $entityName)>{{ $entityName }}</option>
                                        @endforeach
                                    </select>
                                    <div class="invalid-feedback">Entity wajib dipilih.</div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fw-semibold" for="job_title" style="font-size:13px">Nama
                                        Jabatan <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="job_title" name="job_title"
                                        value="{{ old('job_title') }}" required minlength="2" maxlength="120"
                                        list="osJobTitleList" placeholder="Contoh: SPB/SPG Toko">
                                    <datalist id="osJobTitleList">
                                        @foreach ($osConfig['job_titles'] ?? [] as $title)
                                            <option value="{{ $title }}"></option>
                                        @endforeach
                                    </datalist>
                                    <div class="invalid-feedback">Nama jabatan wajib diisi.</div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fw-semibold" for="cost_center" style="font-size:13px">Cabang
                                        (Cost Center) <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="cost_center" name="cost_center"
                                        value="{{ old('cost_center') }}" required minlength="2" maxlength="120"
                                        placeholder="Contoh: Jakarta">
                                    <div class="invalid-feedback">Cabang (cost center) wajib diisi.</div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fw-semibold" for="work_location" style="font-size:13px">Lokasi
                                        Kerja <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="work_location" name="work_location"
                                        value="{{ old('work_location') }}" required minlength="2" maxlength="255"
                                        placeholder="Nama toko / outlet">
                                    <div class="invalid-feedback">Lokasi kerja wajib diisi.</div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fw-semibold" for="work_city" style="font-size:13px">Kota
                                        Lokasi Kerja <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="work_city" name="work_city"
                                        value="{{ old('work_city') }}" required minlength="2" maxlength="120"
                                        placeholder="Contoh: Bekasi">
                                    <div class="invalid-feedback">Kota lokasi kerja wajib diisi.</div>
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label fw-semibold" for="mito_join_date" style="font-size:13px">Tgl
                                        Join di Mito <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control" id="mito_join_date" name="mito_join_date"
                                        value="{{ old('mito_join_date') }}" required min="1900-01-01">
                                    <div class="invalid-feedback">Tanggal join di Mito wajib diisi.</div>
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label fw-semibold" for="contract_start_date"
                                        style="font-size:13px">Tgl Awal Kontrak (Damarindo) <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control" id="contract_start_date"
                                        name="contract_start_date" value="{{ old('contract_start_date') }}" required
                                        min="1900-01-01">
                                    <div class="invalid-feedback">Tanggal awal kontrak wajib diisi.</div>
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label fw-semibold" for="contract_end_date"
                                        style="font-size:13px">Tgl Akhir Kontrak (StaffInc) <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control" id="contract_end_date"
                                        name="contract_end_date" value="{{ old('contract_end_date') }}" required
                                        min="1900-01-02">
                                    <div class="invalid-feedback">Tanggal akhir kontrak harus setelah tanggal awal kontrak.</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- SECTION 4: PAYROLL -->
                    <div class="form-section" id="sectionBank">
                        <div class="form-section-header">
                            <i class="bi bi-credit-card-fill"></i>
                            <div>
                                <h5>Data Payroll</h5>
                                <p>Rekening BCA, skema penggajian, dan nominal UMK</p>
                            </div>
                            <span class="section-status" id="secBadgeBank"><i class="bi bi-hourglass-split"></i></span>
                        </div>
                        <div class="form-section-body p-4">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold" for="bank_account" style="font-size:13px">No
                                        Rekening BCA <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="bank_account" name="bank_account"
                                        value="{{ old('bank_account') }}" required inputmode="numeric" maxlength="20"
                                        pattern="[0-9]{8,20}" placeholder="Contoh: 1234567890">
                                    <div class="invalid-feedback">Nomor rekening hanya boleh berisi 8–20 digit angka.</div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fw-semibold" for="payroll_scheme"
                                        style="font-size:13px">Skema Penggajian <span class="text-danger">*</span></label>
                                    <select class="form-select" id="payroll_scheme" name="payroll_scheme" required>
                                        <option value="">-- Pilih Skema --</option>
                                        @foreach ($osConfig['payroll_schemes'] ?? [] as $scheme)
                                            <option value="{{ $scheme }}" @selected(old('payroll_scheme') === $scheme)>{{ $scheme }}</option>
                                        @endforeach
                                    </select>
                                    <div class="invalid-feedback">Skema penggajian wajib dipilih.</div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fw-semibold" for="umk_amount" style="font-size:13px">Nominal
                                        UMK <span class="text-danger">*</span></label>
                                    <div class="input-group-prefix d-flex">
                                        <span
                                            class="input-prefix d-flex align-items-center px-3 border border-end-0 rounded-start bg-light text-muted"
                                            style="font-size:13px;">Rp</span>
                                        <input type="text" class="form-control rounded-start-0" id="umk_amount"
                                            name="umk_amount" value="{{ old('umk_amount') }}" required
                                            inputmode="numeric" maxlength="15" pattern="[0-9]{1,3}(\.[0-9]{3})+"
                                            autocomplete="off" data-amount placeholder="Contoh: 5.396.761">
                                    </div>
                                    <div class="form-text amount-hint" style="font-size:12px;"></div>
                                    <div class="invalid-feedback">Nominal UMK wajib diisi angka, minimal Rp 1.000.</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- DECLARATION -->
                    <div class="card border-0 shadow-sm rounded-4 mb-4"
                        style="background:#fff5f5;border:1px solid #fed7d7!important;">
                        <div class="card-body p-4">
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" id="agreement" name="agreement"
                                    value="1" required data-unlocked="0"
                                    style="width:20px;height:20px;cursor:not-allowed;opacity:0.5;">
                                <label class="form-check-label ms-2 fw-semibold" for="agreement"
                                    style="font-size:13px;cursor:pointer;color:#0B2540;">
                                    Saya menyatakan bahwa seluruh data yang saya isi adalah benar dan dapat
                                    dipertanggungjawabkan. Saya menyetujui pendataan diri saya ke dalam sistem HRIS MITO
                                    Group.
                                </label>
                            </div>
                            <div class="invalid-feedback d-block mb-2" id="agreementError"
                                style="display:none!important;font-size:13px;">Anda harus menyetujui pernyataan di atas
                                untuk melanjutkan.</div>
                            <div id="agreementHint" class="form-text text-danger mb-3" style="font-size:12px;">
                                <i class="bi bi-info-circle-fill me-1"></i> Lengkapi seluruh data pada semua bagian
                                terlebih dahulu untuk mengaktifkan persetujuan.
                            </div>
                            <div class="d-flex justify-content-end">
                                <button type="submit" class="btn btn-submit" id="submitBtn" disabled>
                                    <span class="spinner-border spinner-border-sm d-none me-1" id="submitSpinner"></span>
                                    <span id="submitText">Kirim Data</span>
                                </button>
                            </div>
                        </div>
                    </div>

                </form>
            </div>

        </div>
    </div>
@endsection

@section('scripts')
    <script nonce="{{ request()->attributes->get('csp_nonce') }}">
        var FORM_SECTIONS = [{
                id: 'sectionPersonal',
                badgeId: 'secBadgePersonal',
                fields: ['full_name', 'birth_place', 'birth_date', 'last_education', 'citizen_id_address']
            },
            {
                id: 'sectionContact',
                badgeId: 'secBadgeContact',
                fields: ['whatsapp_number', 'email']
            },
            {
                id: 'sectionEmployment',
                badgeId: 'secBadgeEmployment',
                fields: ['vendor', 'entity', 'job_title', 'cost_center', 'work_location', 'work_city',
                    'mito_join_date', 'contract_start_date', 'contract_end_date'
                ]
            },
            {
                id: 'sectionBank',
                badgeId: 'secBadgeBank',
                fields: ['bank_account', 'payroll_scheme', 'umk_amount']
            },
        ];

        var CONTACT_CHECK_URL = @json(route('public.outsource.contact-check'));
        var SUBMISSION_FLAG = 'mito_outsource_submitted';
        var isSubmitting = false;
        var contactIsBlocked = false;
        var contactCheckPending = false;
        var contactCheckSeq = 0;
        var contactCheckTimer = null;
        var form, agreementCheckbox, agreementError, submitBtn, submitSpinner, submitText;
        var progressFill, progressCount, progressMessage;
        var birthDateEl, ageEl, phoneEl, emailEl, contractStartEl, contractEndEl, umkEl;

        function isAlreadySubmitted() {
            try {
                return window.sessionStorage.getItem(SUBMISSION_FLAG) === '1';
            } catch (err) {
                return false;
            }
        }

        function markSubmitted() {
            try {
                window.sessionStorage.setItem(SUBMISSION_FLAG, '1');
            } catch (err) {}
        }

        function clearClientSubmitFlag() {
            try {
                window.sessionStorage.removeItem(SUBMISSION_FLAG);
            } catch (err) {}
        }

        function getCsrfToken() {
            var meta = document.querySelector('meta[name="csrf-token"]');
            if (meta) return meta.getAttribute('content');
            var input = form ? form.querySelector('input[name="_token"]') : null;
            return input ? input.value : '';
        }

        // ============================================================
        // FIELD / SECTION STATE
        // ============================================================
        function isFieldValid(el) {
            if (!el) return false;
            var v = (el.value || '').trim();
            if (v === '') return false;
            if (el.id === 'birth_date') return validateBirthDate(el, true);
            if (el.id === 'contract_end_date') return validateContractDates(true);
            return el.checkValidity ? el.checkValidity() : true;
        }

        function countSection(sec) {
            var valid = 0;
            sec.fields.forEach(function(id) {
                if (isFieldValid(document.getElementById(id))) valid++;
            });
            return {
                total: sec.fields.length,
                valid: valid
            };
        }

        function setSectionBadge(sec, complete) {
            var b = document.getElementById(sec.badgeId);
            if (!b) return;
            b.className = 'section-status' + (complete ? ' done' : '');
            b.innerHTML = complete ? '<i class="bi bi-check-circle-fill"></i>' : '<i class="bi bi-hourglass-split"></i>';
        }

        function lockAgreement() {
            agreementCheckbox.setAttribute('data-unlocked', '0');
            agreementCheckbox.checked = false;
            agreementCheckbox.style.cursor = 'not-allowed';
            agreementCheckbox.style.opacity = '0.5';
        }

        function updateFormState() {
            var allComplete = true;
            FORM_SECTIONS.forEach(function(sec) {
                var c = countSection(sec);
                var done = c.valid === c.total;
                if (!done) allComplete = false;
                setSectionBadge(sec, done);
            });

            var hint = document.getElementById('agreementHint');
            if (contactIsBlocked) {
                lockAgreement();
                if (hint) {
                    hint.style.display = 'block';
                    hint.innerHTML =
                        '<i class="bi bi-exclamation-octagon-fill me-1"></i> Nomor WhatsApp atau email sudah terdaftar. Ubah kontak agar formulir dapat dikirim.';
                }
                submitBtn.disabled = true;
                return;
            }

            if (allComplete) {
                agreementCheckbox.setAttribute('data-unlocked', '1');
                agreementCheckbox.style.cursor = 'pointer';
                agreementCheckbox.style.opacity = '1';
                if (hint) hint.style.display = 'none';
            } else {
                lockAgreement();
                if (hint) {
                    hint.style.display = 'block';
                    hint.innerHTML =
                        '<i class="bi bi-info-circle-fill me-1"></i> Lengkapi seluruh data pada semua bagian terlebih dahulu untuk mengaktifkan persetujuan.';
                }
            }
            submitBtn.disabled = contactCheckPending || !(allComplete && agreementCheckbox.checked);
        }

        var progressMessages = [{
                min: 0,
                text: 'Mulai mengisi formulir...'
            },
            {
                min: 25,
                text: 'Awal yang bagus!'
            },
            {
                min: 50,
                text: 'Form sudah setengah selesai.'
            },
            {
                min: 75,
                text: 'Hampir selesai. Centang persetujuan di langkah terakhir.'
            },
            {
                min: 100,
                text: 'Semua data telah lengkap. Silakan kirim.'
            }
        ];

        function updateProgress() {
            var total = 1,
                valid = agreementCheckbox.checked ? 1 : 0;
            FORM_SECTIONS.forEach(function(sec) {
                var c = countSection(sec);
                total += c.total;
                valid += c.valid;
            });
            var percent = Math.min(100, Math.round((valid / total) * 100));
            progressFill.style.width = percent + '%';
            progressCount.textContent = valid + ' dari ' + total + ' data telah lengkap';
            var msg = progressMessages[0].text;
            for (var i = progressMessages.length - 1; i >= 0; i--) {
                if (percent >= progressMessages[i].min) {
                    msg = progressMessages[i].text;
                    break;
                }
            }
            progressMessage.textContent = msg;
            updateFormState();
        }

        // ============================================================
        // AMOUNT FORMATTER — 5396761 → 5.396.761 (+ "≈ 5,4 juta" hint)
        // ============================================================
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

        function setFieldState(el, valid, message) {
            var container = el.closest('[class*="col-"]');
            var feedback = container ? container.querySelector('.invalid-feedback') : null;
            if (feedback && message) feedback.textContent = message;
            if (valid === null) {
                el.classList.remove('is-valid', 'is-invalid');
                if (feedback) feedback.style.display = 'none';
                return;
            }
            el.classList.toggle('is-valid', valid);
            el.classList.toggle('is-invalid', !valid);
            if (feedback) feedback.style.display = valid ? 'none' : 'flex';
        }

        function validateField(el) {
            if (!el || el.type === 'checkbox' || el.type === 'hidden') return;
            if (el.id === 'birth_date') {
                validateBirthDate(el, false);
                return;
            }
            if (el.id === 'contract_end_date' || el.id === 'contract_start_date') {
                validateContractDates(false);
                if (el.id === 'contract_end_date') return;
            }
            if ((el.id === 'whatsapp_number' || el.id === 'email') && contactIsBlocked) {
                el.classList.add('is-invalid', 'contact-duplicate');
                el.classList.remove('is-valid');
                return;
            }
            if (!el.value) {
                setFieldState(el, null);
                return;
            }
            setFieldState(el, el.checkValidity());
        }

        // ============================================================
        // DATES
        // ============================================================
        function calculateAge(value) {
            if (!value) {
                ageEl.value = '';
                return null;
            }
            var p = value.split('-');
            var birth = new Date(parseInt(p[0], 10), parseInt(p[1], 10) - 1, parseInt(p[2], 10));
            if (isNaN(birth.getTime())) {
                ageEl.value = '';
                return null;
            }
            var now = new Date();
            var age = now.getFullYear() - birth.getFullYear();
            var m = now.getMonth() - birth.getMonth();
            if (m < 0 || (m === 0 && now.getDate() < birth.getDate())) age--;
            ageEl.value = age >= 0 ? age : '';
            return age;
        }

        function validateBirthDate(el, silent) {
            var val = (el.value || '').trim();
            var msg = null;
            if (!/^\d{4}-\d{2}-\d{2}$/.test(val)) {
                msg = 'Tanggal lahir wajib diisi dengan format YYYY-MM-DD.';
            } else {
                var age = calculateAge(val);
                var year = parseInt(val.substring(0, 4), 10);
                if (year < 1900 || age === null || age < 0) msg = 'Tanggal lahir tidak valid.';
                else if (age < 17) msg = 'Usia minimal untuk mendaftar adalah 17 tahun.';
            }
            if (!silent) {
                if (!val) setFieldState(el, null);
                else setFieldState(el, msg === null, msg);
            }
            return msg === null;
        }

        function syncContractEndMin() {
            if (!contractStartEl.value) {
                contractEndEl.min = '1900-01-02';
                return;
            }
            var d = new Date(contractStartEl.value + 'T00:00:00');
            d.setDate(d.getDate() + 1);
            var mm = String(d.getMonth() + 1).padStart(2, '0');
            var dd = String(d.getDate()).padStart(2, '0');
            contractEndEl.min = d.getFullYear() + '-' + mm + '-' + dd;
        }

        function validateContractDates(silent) {
            var start = contractStartEl.value;
            var end = contractEndEl.value;
            var ok = !!end && (!start || end > start);
            if (!silent) {
                if (!end) setFieldState(contractEndEl, null);
                else setFieldState(contractEndEl, ok, ok ? null :
                    'Tanggal akhir kontrak harus setelah tanggal awal kontrak.');
            }
            return ok;
        }

        // ============================================================
        // CONTACT DUPLICATE CHECK
        // ============================================================
        function setContactBlocked(blocked, message) {
            contactIsBlocked = !!blocked;
            var banner = document.getElementById('contactLockBanner');
            var bannerMsg = document.getElementById('contactLockBannerMessage');
            form.classList.toggle('contact-duplicate-locked', contactIsBlocked);
            if (banner) banner.classList.toggle('d-none', !contactIsBlocked);
            if (bannerMsg) bannerMsg.textContent = contactIsBlocked ? (message || '') : '';
            [phoneEl, emailEl].forEach(function(el) {
                el.classList.toggle('contact-duplicate', contactIsBlocked);
                if (!contactIsBlocked) validateField(el);
            });
            updateProgress();
        }

        function scheduleContactCheck() {
            if (contactCheckTimer) clearTimeout(contactCheckTimer);
            var phone = phoneEl.checkValidity() ? phoneEl.value : '';
            var email = emailEl.checkValidity() ? emailEl.value.trim() : '';
            if (!phone && !email) {
                contactCheckPending = false;
                if (contactIsBlocked) setContactBlocked(false);
                return;
            }
            contactCheckPending = true;
            updateFormState();
            contactCheckTimer = setTimeout(function() {
                checkContact(phone, email);
            }, 400);
        }

        function checkContact(phone, email) {
            var seq = ++contactCheckSeq;
            fetch(CONTACT_CHECK_URL, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken(),
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin',
                body: JSON.stringify({
                    whatsapp_number: phone,
                    email: email
                })
            }).then(function(res) {
                return res.json();
            }).then(function(data) {
                if (seq !== contactCheckSeq) return;
                contactCheckPending = false;
                setContactBlocked(data && data.available === false, data ? data.message : null);
            }).catch(function() {
                if (seq !== contactCheckSeq) return;
                contactCheckPending = false;
                setContactBlocked(false);
            });
        }

        // ============================================================
        // INIT
        // ============================================================
        document.addEventListener('DOMContentLoaded', function() {
            form = document.getElementById('formOutsource');
            agreementCheckbox = document.getElementById('agreement');
            agreementError = document.getElementById('agreementError');
            submitBtn = document.getElementById('submitBtn');
            submitSpinner = document.getElementById('submitSpinner');
            submitText = document.getElementById('submitText');
            progressFill = document.getElementById('progressFill');
            progressCount = document.getElementById('progressCount');
            progressMessage = document.getElementById('progressMessage');
            birthDateEl = document.getElementById('birth_date');
            ageEl = document.getElementById('age');
            phoneEl = document.getElementById('whatsapp_number');
            emailEl = document.getElementById('email');
            contractStartEl = document.getElementById('contract_start_date');
            contractEndEl = document.getElementById('contract_end_date');
            var emailValidation = document.getElementById('emailValidation');
            clearClientSubmitFlag();
            syncContractEndMin();
            if (birthDateEl.value) calculateAge(birthDateEl.value);

            umkEl = document.getElementById('umk_amount');
            umkEl.addEventListener('input', function() {
                formatAmount(umkEl);
            });
            if (umkEl.value) formatAmount(umkEl);

            ['whatsapp_number', 'bank_account'].forEach(function(id) {
                var el = document.getElementById(id);
                el.addEventListener('input', function() {
                    var digits = this.value.replace(/[^0-9]/g, '');
                    if (id === 'whatsapp_number') {
                        if (digits.indexOf('62') === 0) digits = digits.substring(2);
                        else if (digits.indexOf('0') === 0) digits = digits.substring(1);
                    }
                    this.value = digits.substring(0, parseInt(this.getAttribute('maxlength'), 10) || 20);
                });
            });

            contractStartEl.addEventListener('change', syncContractEndMin);

            phoneEl.addEventListener('change', scheduleContactCheck);
            emailEl.addEventListener('change', scheduleContactCheck);

            var commonTypos = {
                'gmial.com': 'gmail.com',
                'gmai.com': 'gmail.com',
                'gmail.con': 'gmail.com',
                'hotnail.com': 'hotmail.com',
                'outlok.com': 'outlook.com'
            };
            emailEl.addEventListener('blur', function() {
                var email = this.value.trim();
                emailValidation.style.display = 'none';
                if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) return;
                var domain = email.split('@')[1];
                if (commonTypos[domain]) {
                    emailValidation.style.cssText =
                        'display:flex;font-size:12px;color:#8a6100;align-items:center;gap:4px;';
                    emailValidation.textContent = 'Apakah yang Anda maksud: ' + email.split('@')[0] + '@' +
                        commonTypos[domain] + '?';
                }
            });

            agreementCheckbox.addEventListener('click', function(e) {
                if (this.getAttribute('data-unlocked') !== '1') e.preventDefault();
            });
            agreementCheckbox.addEventListener('change', function() {
                if (this.checked) {
                    agreementError.style.setProperty('display', 'none', 'important');
                    this.classList.remove('is-invalid');
                }
                updateProgress();
            });

            form.querySelectorAll('input, select, textarea').forEach(function(input) {
                if (input.id === 'agreement' || input.type === 'hidden') return;
                ['input', 'change', 'blur'].forEach(function(evt) {
                    input.addEventListener(evt, function() {
                        validateField(this);
                        updateProgress();
                    });
                });
            });

            form.addEventListener('submit', function(e) {
                if (isSubmitting) {
                    e.preventDefault();
                    return;
                }
                if (isAlreadySubmitted()) {
                    e.preventDefault();
                    window.location.replace(@json(route('public.outsource.success')));
                    return;
                }
                if (contactIsBlocked || contactCheckPending) {
                    e.preventDefault();
                    phoneEl.focus();
                    return;
                }
                if (!agreementCheckbox.checked) {
                    e.preventDefault();
                    agreementError.style.setProperty('display', 'block', 'important');
                    agreementCheckbox.classList.add('is-invalid');
                    return;
                }
                var bdValid = validateBirthDate(birthDateEl, false);
                var contractValid = validateContractDates(false);
                if (!form.checkValidity() || !bdValid || !contractValid) {
                    e.preventDefault();
                    form.classList.add('was-validated');
                    var target = !bdValid ? birthDateEl : (!contractValid ? contractEndEl : form.querySelector(':invalid'));
                    if (target) target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'center'
                    });
                    return;
                }
                document.getElementById('consentTimestamp').value = new Date().toISOString();
                document.getElementById('consentDevice').value = (navigator.platform || '').substring(0, 120);
                umkEl.value = umkEl.value.replace(/\D/g, '');
                isSubmitting = true;
                markSubmitted();
                submitBtn.disabled = true;
                submitSpinner.classList.remove('d-none');
                submitText.textContent = 'Mengirim...';
                if (typeof window.showPublicLoader === 'function') {
                    window.showPublicLoader('Mengirim data');
                }
            });

            var serverError = @json(session('error'));
            if (serverError && /sudah terdaftar/i.test(String(serverError))) {
                setContactBlocked(true, serverError);
            } else if (phoneEl.value || emailEl.value) {
                scheduleContactCheck();
            }

            updateProgress();
        });

        window.addEventListener('pageshow', function(event) {
            if (event.persisted) {
                window.location.reload();
            }
        });
    </script>
@endsection
