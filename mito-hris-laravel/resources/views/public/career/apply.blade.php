@extends('layouts.public')

@section('title', 'Form Pendaftaran - MITO Group')
@section('description',
    'Lengkapi formulir pendaftaran karir MITO dengan data yang valid untuk mengikuti proses
    rekrutmen.')
@section('robots', 'noindex,follow,noarchive')

@section('content')
    <!-- Branded submit loading overlay -->
    <div class="public-submit-loader" id="loadingOverlay" role="status" aria-label="Mengirim data">
        <div class="public-submit-loader-content">
            <img class="public-loader-logo" src="{{ asset('assets/mito-red.png') }}" alt="MITO">
            <div class="public-loader-dots" aria-hidden="true"><span></span><span></span><span></span></div>
            <span class="public-submit-loader-label">Mengirim data...</span>
        </div>
    </div>

    <!-- HERO (1:1 from GAS FormPendaftaran.html) -->
    <div class="hero-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-7 col-md-8">
                    <div class="hero-org">MITO Group</div>
                    <div class="hero-dept">Human Resources Department</div>
                    <h1 class="hero-title">Recruitment Portal</h1>
                    <p class="hero-subtitle">Silakan lengkapi formulir pendaftaran berikut dengan data yang benar dan
                        lengkap. Seluruh data akan digunakan hanya untuk kebutuhan proses rekrutmen.</p>
                </div>
                <div class="col-lg-5 col-md-4 d-none d-md-flex justify-content-end align-items-center">
                    <img id="heroAvatarImg" src="{{ asset('assets/mito.png') }}" alt="MITO Official" class="hero-avatar-img"
                        loading="lazy">
                </div>
            </div>
        </div>
    </div>

    <!-- PROGRESS (sticky 1:1 from GAS FormPendaftaran.html) -->
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

    <!-- FORM BODY (1:1 from GAS FormBody.html) -->
    <div class="content-wrap py-4">
        <div class="container" style="max-width:960px;">

            {{-- Server-side error alert --}}
            @if (session('error'))
                <div class="alert alert-danger alert-dismissible fade show p-3 mb-4 rounded-3 d-flex align-items-center gap-2"
                    role="alert">
                    <i class="bi bi-exclamation-triangle-fill fs-5 flex-shrink-0"></i>
                    <div>{{ session('error') }}</div>
                    <button type="button" class="btn-close btn-sm p-3" data-bs-dismiss="alert"></button>
                </div>
            @endif

            {{-- Registration form wrapper (hidden on success via JS session guard) --}}
            <div id="registrationForm">
                <form action="{{ route('public.career.store') }}" method="POST" id="formPendaftaran" novalidate>
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
                                <p>Data identitas dan informasi personal Anda</p>
                            </div>
                            <span class="section-status" id="secBadgePersonal"><i class="bi bi-hourglass-split"></i></span>
                        </div>
                        <div class="form-section-body p-4">
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label fw-semibold" for="nik" style="font-size:13px">NIK (Nomor
                                        Induk Kependudukan) <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="nik" name="nik"
                                        value="{{ old('nik') }}" required maxlength="16" pattern="[0-9]{16}"
                                        autocomplete="off" inputmode="numeric">
                                    <div class="invalid-feedback">NIK harus tepat 16 digit angka.</div>
                                    <div class="p-2 rounded mt-2 d-flex align-items-center gap-2"
                                        style="background:#f0f7ff;border:1px solid #c7dff7;font-size:12px;color:#005BAC">
                                        <i class="bi bi-info-circle-fill fs-6 flex-shrink-0"></i>
                                        <div>Sistem akan mengisi beberapa informasi secara otomatis berdasarkan struktur
                                            NIK. Pastikan seluruh data yang ditampilkan sudah sesuai sebelum mengirim
                                            lamaran. Sistem membaca pola NIK (kode provinsi/kabupaten, tanggal lahir, dan
                                            jenis kelamin) untuk membantu pengisian form. Data Anda aman dan hanya digunakan
                                            untuk proses rekrutmen.</div>
                                    </div>
                                    <div class="nik-feedback mt-2" id="nikFeedback"></div>
                                </div>

                                <div class="col-md-12">
                                    <label class="form-label fw-semibold" for="full_name" style="font-size:13px">Nama
                                        Lengkap <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="full_name" name="nama_lengkap"
                                        value="{{ old('nama_lengkap') }}" required minlength="3" maxlength="255"
                                        pattern="[\p{L}]+( [\p{L}]+)*" autocomplete="name">
                                    <div class="invalid-feedback">Nama lengkap hanya boleh berisi huruf dan spasi.</div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fw-semibold" for="birth_date" style="font-size:13px">Tanggal
                                        Lahir <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="birth_date" name="birth_date"
                                        value="{{ old('birth_date') }}" required placeholder="DD/MM/YYYY" maxlength="10"
                                        autocomplete="off" inputmode="numeric">
                                    <div class="invalid-feedback">Tanggal lahir wajib diisi dengan format DD/MM/YYYY.</div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fw-semibold" for="age" style="font-size:13px">Usia
                                        <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control" id="age" name="usia"
                                        value="{{ old('usia') }}" required min="17" max="100" readonly
                                        style="background-color:#f8f9fa;cursor:default;"
                                        placeholder="Otomatis dari Tanggal Lahir">
                                    <div class="invalid-feedback">Usia minimal 17 tahun untuk mendaftar.</div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fw-semibold" for="gender" style="font-size:13px">Jenis
                                        Kelamin <span class="text-danger">*</span></label>
                                    <select class="form-select" id="gender" name="jenis_kelamin" required>
                                        <option value="">-- Pilih Jenis Kelamin --</option>
                                        <option value="Laki-laki"
                                            {{ old('jenis_kelamin') === 'Laki-laki' ? 'selected' : '' }}>Laki-laki</option>
                                        <option value="Perempuan"
                                            {{ old('jenis_kelamin') === 'Perempuan' ? 'selected' : '' }}>Perempuan</option>
                                    </select>
                                    <div class="invalid-feedback">Jenis kelamin wajib dipilih.</div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fw-semibold" for="marital_status"
                                        style="font-size:13px">Status Pernikahan <span
                                            class="text-danger">*</span></label>
                                    <select class="form-select" id="marital_status" name="marital_status" required>
                                        <option value="">-- Pilih Status Pernikahan --</option>
                                        <option value="Belum Menikah"
                                            {{ old('marital_status') === 'Belum Menikah' ? 'selected' : '' }}>Belum Menikah
                                        </option>
                                        <option value="Menikah"
                                            {{ old('marital_status') === 'Menikah' ? 'selected' : '' }}>Menikah</option>
                                        <option value="Cerai" {{ old('marital_status') === 'Cerai' ? 'selected' : '' }}>
                                            Cerai</option>
                                    </select>
                                    <div class="invalid-feedback">Status pernikahan wajib dipilih.</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- SECTION 2: CONTACT INFORMATION -->
                    <div class="form-section" id="sectionContact">
                        <div class="form-section-header">
                            <i class="bi bi-envelope-fill"></i>
                            <div>
                                <h5>Informasi Kontak</h5>
                                <p>Data komunikasi dan alamat Anda</p>
                            </div>
                            <span class="section-status" id="secBadgeContact"><i
                                    class="bi bi-hourglass-split"></i></span>
                        </div>
                        <div class="form-section-body p-4">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold" for="email" style="font-size:13px">Alamat
                                        Email <span class="text-danger">*</span></label>
                                    <input type="email" class="form-control @error('email') is-invalid @enderror"
                                        id="email" name="email" value="{{ old('email') }}" required
                                        autocomplete="email" aria-describedby="emailFeedback">
                                    <div class="invalid-feedback" id="emailFeedback">
                                        {{ $errors->first('email') ?: 'Silakan masukkan alamat email yang valid.' }}</div>
                                    <div id="emailValidation" class="mt-1" style="display:none;font-size:12px;"></div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fw-semibold" for="phone" style="font-size:13px">Nomor HP
                                        <span class="text-danger">*</span></label>
                                    <div class="input-group-prefix d-flex">
                                        <span
                                            class="input-prefix d-flex align-items-center px-3 border border-end-0 rounded-start bg-light text-muted"
                                            style="font-size:13px;">+62</span>
                                        <input type="tel" class="form-control rounded-start-0" id="phone"
                                            name="nomor_telepon" value="{{ old('nomor_telepon') }}" required
                                            maxlength="13" pattern="8[0-9]{6,12}" autocomplete="tel" inputmode="numeric"
                                            placeholder="81234567890">
                                    </div>
                                    <div class="invalid-feedback">Nomor HP tidak valid. Gunakan format 8xxxxxxxxxx.</div>
                                    <div class="form-text">Contoh: 81234567890</div>
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label fw-semibold" for="province" style="font-size:13px">Provinsi
                                        <span class="text-danger">*</span></label>
                                    <select class="form-select" id="province" name="provinsi" required>
                                        <option value="">-- Pilih Provinsi --</option>
                                    </select>
                                    <div class="invalid-feedback">Provinsi wajib dipilih.</div>
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label fw-semibold" for="city"
                                        style="font-size:13px">Kota/Kabupaten <span class="text-danger">*</span></label>
                                    <select class="form-select" id="city" name="kota" required>
                                        <option value="">-- Pilih Kota/Kabupaten --</option>
                                    </select>
                                    <div class="invalid-feedback">Kota/Kabupaten wajib dipilih.</div>
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label fw-semibold" for="district" style="font-size:13px">Kecamatan
                                        <span class="text-danger">*</span></label>
                                    <div id="districtWrap">
                                        <select class="form-select" id="district" name="kecamatan" required disabled>
                                            <option value="">-- Pilih Kota dahulu --</option>
                                        </select>
                                    </div>
                                    <div class="invalid-feedback">Kecamatan wajib dipilih.</div>
                                    <div id="districtLoading"
                                        style="display:none;font-size:12px;color:#6b7280;margin-top:5px;">
                                        <span class="spinner-border spinner-border-sm me-1" role="status"></span> Memuat
                                        daftar kecamatan...
                                    </div>
                                    <div id="districtManualWrap" style="display:none;margin-top:6px;">
                                        <input type="text" class="form-control" id="districtManual"
                                            name="kecamatan_manual" value="{{ old('kecamatan_manual') }}"
                                            placeholder="Ketik nama kecamatan manual">
                                        <div class="form-text">Data kecamatan tidak tersedia, isi manual.</div>
                                    </div>
                                </div>

                                <div class="col-12">
                                    <label class="form-label fw-semibold" for="address" style="font-size:13px">Detail
                                        Alamat <span class="text-danger">*</span></label>
                                    <textarea class="form-control" id="address" name="alamat_domisili" rows="3" required
                                        placeholder="Nama jalan, nomor rumah, RT/RW, dll.">{{ old('alamat_domisili') }}</textarea>
                                    <div class="invalid-feedback">Detail alamat wajib diisi.</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- SECTION 3: EMPLOYMENT INFORMATION -->
                    <div class="form-section" id="sectionEmployment">
                        <div class="form-section-header">
                            <i class="bi bi-briefcase-fill"></i>
                            <div>
                                <h5>Informasi Pekerjaan</h5>
                                <p>Pengalaman dan ekspektasi karir Anda</p>
                            </div>
                            <span class="section-status" id="secBadgeEmployment"><i
                                    class="bi bi-hourglass-split"></i></span>
                        </div>
                        <div class="form-section-body p-4">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold" for="position_applied"
                                        style="font-size:13px">Posisi yang Dilamar <span
                                            class="text-danger">*</span></label>
                                    <select class="form-select" id="position_applied" name="posisi_dilamar" required>
                                        <option value="">-- Pilih Posisi --</option>
                                        @foreach ($positions ?? [] as $pos)
                                            <option value="{{ $pos }}"
                                                {{ old('posisi_dilamar') === $pos ? 'selected' : '' }}>{{ $pos }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <div class="invalid-feedback">Posisi yang dilamar wajib dipilih.</div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fw-semibold" for="education"
                                        style="font-size:13px">Pendidikan Terakhir <span
                                            class="text-danger">*</span></label>
                                    <select class="form-select" id="education" name="pendidikan_terakhir" required>
                                        <option value="">-- Pilih Pendidikan --</option>
                                        <option value="SD">SD</option>
                                        <option value="SMP">SMP</option>
                                        <option value="SMA/SMK">SMA/SMK</option>
                                        <option value="D3">D3</option>
                                        <option value="S1">S1</option>
                                        <option value="S2">S2</option>
                                        <option value="S3">S3</option>
                                    </select>
                                    <div class="invalid-feedback">Pendidikan terakhir wajib dipilih.</div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fw-semibold" for="work_experience"
                                        style="font-size:13px">Pengalaman Kerja <span class="text-danger">*</span></label>
                                    <select class="form-select" id="work_experience" name="pengalaman_kerja" required>
                                        <option value="">-- Pilih Pengalaman Kerja --</option>
                                        <option value="Fresh Graduate">Fresh Graduate</option>
                                        <option value="1-2 Tahun">1-2 Tahun</option>
                                        <option value="3-5 Tahun">3-5 Tahun</option>
                                        <option value="5-10 Tahun">5-10 Tahun</option>
                                        <option value="Lebih dari 10 Tahun">Lebih dari 10 Tahun</option>
                                    </select>
                                    <div class="invalid-feedback">Pengalaman kerja wajib dipilih.</div>
                                </div>

                                <div class="col-md-6" id="lastCompanyGroup" style="display:none;">
                                    <label class="form-label fw-semibold" for="last_company"
                                        style="font-size:13px">Perusahaan Terakhir</label>
                                    <input type="text" class="form-control" id="last_company"
                                        name="perusahaan_terakhir" value="{{ old('perusahaan_terakhir') }}"
                                        autocomplete="organization" disabled>
                                    <div class="invalid-feedback">Nama perusahaan wajib diisi.</div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fw-semibold" for="current_employment_status"
                                        style="font-size:13px">Status Bekerja Saat Ini <span
                                            class="text-danger">*</span></label>
                                    <select class="form-select" id="current_employment_status" name="status_bekerja"
                                        required>
                                        <option value="">-- Pilih Status Bekerja --</option>
                                        <option value="Employed Full Time">Employed Full Time</option>
                                        <option value="Employed Contract">Employed Contract</option>
                                        <option value="Part Time">Part Time</option>
                                        <option value="Freelance">Freelance</option>
                                        <option value="Unemployed">Unemployed</option>
                                        <option value="Resigned">Resigned</option>
                                        <option value="Fresh Graduate">Fresh Graduate</option>
                                    </select>
                                    <div class="invalid-feedback">Status bekerja saat ini wajib dipilih.</div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fw-semibold" for="available_to_join"
                                        style="font-size:13px">Kesediaan Bergabung <span
                                            class="text-danger">*</span></label>
                                    <select class="form-select" id="available_to_join" name="kesediaan_bergabung"
                                        required>
                                        <option value="">-- Pilih Kesediaan Bergabung --</option>
                                        <option value="Segera">Segera</option>
                                        <option value="1 Minggu">1 Minggu</option>
                                        <option value="2 Minggu">2 Minggu</option>
                                        <option value="1 Bulan">1 Bulan</option>
                                        <option value="2 Bulan">2 Bulan</option>
                                        <option value="3 Bulan">3 Bulan</option>
                                        <option value="Negosiasi">Negosiasi</option>
                                    </select>
                                    <div class="invalid-feedback">Kesediaan bergabung wajib dipilih.</div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fw-semibold" for="expected_salary"
                                        style="font-size:13px">Ekspektasi Gaji <span class="text-danger">*</span></label>
                                    <div class="input-group-prefix d-flex">
                                        <span
                                            class="input-prefix d-flex align-items-center px-3 border border-end-0 rounded-start bg-light text-muted"
                                            style="font-size:13px;">Rp</span>
                                        <input type="text" class="form-control rounded-start-0" id="expected_salary"
                                            name="ekspektasi_gaji" value="{{ old('ekspektasi_gaji') }}" required
                                            inputmode="numeric" placeholder="10.000.000">
                                    </div>
                                    <div class="invalid-feedback">Ekspektasi gaji wajib diisi dengan angka.</div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fw-semibold" for="recruitment_source"
                                        style="font-size:13px">Sumber Informasi Lowongan <span
                                            class="text-danger">*</span></label>
                                    <select class="form-select" id="recruitment_source" name="sumber_informasi" required>
                                        <option value="">-- Pilih Sumber --</option>
                                        <option value="LinkedIn">LinkedIn</option>
                                        <option value="JobStreet">JobStreet</option>
                                        <option value="Instagram">Instagram</option>
                                        <option value="Website Resmi">Website Resmi</option>
                                        <option value="Rekomendasi Karyawan">Rekomendasi Karyawan</option>
                                        <option value="Lainnya">Lainnya</option>
                                    </select>
                                    <div class="invalid-feedback">Sumber informasi lowongan wajib dipilih.</div>
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
                                    value="1" required style="width:20px;height:20px;cursor:not-allowed;opacity:0.5;"
                                    onclick="return false;" onfocus="this.blur();">
                                <label class="form-check-label ms-2 fw-semibold" for="agreement"
                                    style="font-size:13px;cursor:pointer;color:#0B2540;">
                                    Saya menyatakan bahwa seluruh data yang saya isi adalah benar dan dapat
                                    dipertanggungjawabkan. Saya bersedia mengikuti seluruh proses rekrutmen MITO Group
                                    apabila memenuhi kualifikasi.
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
                                    <span id="submitText">Kirim Pendaftaran</span>
                                </button>
                            </div>
                        </div>
                    </div>

                </form>
            </div>

            {{-- SUCCESS PAGE (shown via JS after submit, 1:1 from GAS successPage div) --}}
            <div id="successPage" style="display:none;text-align:center;padding:80px 20px;">
                <div class="success-icon"
                    style="width:100px;height:100px;background:linear-gradient(135deg,#ecfdf3,#d1fae5);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 24px;color:#166534;font-size:48px;border:4px solid #166534;box-shadow:0 6px 20px -6px rgba(22,101,52,0.2);">
                    <i class="bi bi-check-circle-fill"></i>
                </div>
                <h2 class="success-title" style="font-size:30px;font-weight:800;color:#1f2937;margin-bottom:16px;">
                    Pendaftaran Berhasil</h2>
                <p class="success-message"
                    style="font-size:15px;color:#6b7280;line-height:1.8;max-width:540px;margin:0 auto 32px;">
                    Terima kasih telah mengirimkan lamaran Anda.<br><br>
                    Data Anda telah berhasil kami terima dan akan diproses oleh tim Human Resources MITO Group.<br><br>
                    Apabila profil Anda sesuai dengan kebutuhan perusahaan, kami akan menghubungi Anda melalui email atau
                    nomor telepon yang telah didaftarkan.
                </p>
                <p id="successRecruitId" style="font-size:14px;color:#eb1c24;font-weight:700;margin-bottom:32px;"></p>
                <div class="success-actions" style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap;">
                    <a href="{{ route('public.career.index') }}"
                        style="background:#fff;color:#1f2937;border:1.5px solid #e5e7eb;padding:12px 28px;font-size:14px;font-weight:600;border-radius:10px;text-decoration:none;display:inline-flex;align-items:center;gap:8px;">
                        <i class="bi bi-arrow-left"></i> Kembali ke Halaman Info
                    </a>
                </div>
            </div>

        </div>
    </div>

@section('scripts')
    <script>
        // ============================================================
        // REGIONS DATASET (1:1 from GAS js/regions.html)
        // ============================================================
        var REGIONS = {
            "provinces": {
                "11": "ACEH",
                "12": "SUMATERA UTARA",
                "13": "SUMATERA BARAT",
                "14": "RIAU",
                "15": "JAMBI",
                "16": "SUMATERA SELATAN",
                "17": "BENGKULU",
                "18": "LAMPUNG",
                "19": "KEPULAUAN BANGKA BELITUNG",
                "21": "KEPULAUAN RIAU",
                "31": "DKI JAKARTA",
                "32": "JAWA BARAT",
                "33": "JAWA TENGAH",
                "34": "DI YOGYAKARTA",
                "35": "JAWA TIMUR",
                "36": "BANTEN",
                "51": "BALI",
                "52": "NUSA TENGGARA BARAT",
                "53": "NUSA TENGGARA TIMUR",
                "61": "KALIMANTAN BARAT",
                "62": "KALIMANTAN TENGAH",
                "63": "KALIMANTAN SELATAN",
                "64": "KALIMANTAN TIMUR",
                "65": "KALIMANTAN UTARA",
                "71": "SULAWESI UTARA",
                "72": "SULAWESI TENGAH",
                "73": "SULAWESI SELATAN",
                "74": "SULAWESI TENGGARA",
                "75": "GORONTALO",
                "76": "SULAWESI BARAT",
                "81": "MALUKU",
                "82": "MALUKU UTARA",
                "91": "PAPUA",
                "92": "PAPUA BARAT",
                "93": "PAPUA SELATAN",
                "94": "PAPUA TENGAH",
                "95": "PAPUA PEGUNUNGAN",
                "96": "PAPUA BARAT DAYA"
            },
            "cities": {
                "1101": "KAB. ACEH SELATAN",
                "1102": "KAB. ACEH TENGGARA",
                "1103": "KAB. ACEH TIMUR",
                "1104": "KAB. ACEH TENGAH",
                "1105": "KAB. ACEH BARAT",
                "1106": "KAB. ACEH BESAR",
                "1107": "KAB. PIDIE",
                "1108": "KAB. ACEH UTARA",
                "1109": "KAB. SIMEULUE",
                "1110": "KAB. ACEH SINGKIL",
                "1111": "KAB. BIREUEN",
                "1112": "KAB. ACEH BARAT DAYA",
                "1113": "KAB. GAYO LUES",
                "1114": "KAB. ACEH JAYA",
                "1115": "KAB. NAGAN RAYA",
                "1116": "KAB. ACEH TAMIANG",
                "1117": "KAB. BENER MERIAH",
                "1118": "KAB. PIDIE JAYA",
                "1171": "KOTA BANDA ACEH",
                "1172": "KOTA SABANG",
                "1173": "KOTA LHOKSEUMAWE",
                "1174": "KOTA LANGSA",
                "1175": "KOTA SUBULUSSALAM",
                "1201": "KAB. TAPANULI TENGAH",
                "1202": "KAB. TAPANULI UTARA",
                "1203": "KAB. TAPANULI SELATAN",
                "1204": "KAB. NIAS",
                "1205": "KAB. LANGKAT",
                "1206": "KAB. KARO",
                "1207": "KAB. DELI SERDANG",
                "1208": "KAB. SIMALUNGUN",
                "1209": "KAB. ASAHAN",
                "1210": "KAB. LABUHANBATU",
                "1211": "KAB. DAIRI",
                "1212": "KAB. TOBA",
                "1213": "KAB. MANDAILING NATAL",
                "1214": "KAB. NIAS SELATAN",
                "1215": "KAB. PAKPAK BHARAT",
                "1216": "KAB. HUMBANG HASUNDUTAN",
                "1217": "KAB. SAMOSIR",
                "1218": "KAB. SERDANG BEDAGAI",
                "1219": "KAB. BATU BARA",
                "1220": "KAB. PADANG LAWAS UTARA",
                "1221": "KAB. PADANG LAWAS",
                "1222": "KAB. LABUHANBATU SELATAN",
                "1223": "KAB. LABUHANBATU UTARA",
                "1224": "KAB. NIAS UTARA",
                "1225": "KAB. NIAS BARAT",
                "1271": "KOTA MEDAN",
                "1272": "KOTA PEMATANG SIANTAR",
                "1273": "KOTA SIBOLGA",
                "1274": "KOTA TANJUNG BALAI",
                "1275": "KOTA BINJAI",
                "1276": "KOTA TEBING TINGGI",
                "1277": "KOTA PADANG SIDEMPUAN",
                "1278": "KOTA GUNUNGSITOLI",
                "1301": "KAB. PESISIR SELATAN",
                "1302": "KAB. SOLOK",
                "1303": "KAB. SIJUNJUNG",
                "1304": "KAB. TANAH DATAR",
                "1305": "KAB. PADANG PARIAMAN",
                "1306": "KAB. AGAM",
                "1307": "KAB. LIMA PULUH KOTA",
                "1308": "KAB. PASAMAN",
                "1309": "KAB. KEP. MENTAWAI",
                "1310": "KAB. DHARMASRAYA",
                "1311": "KAB. SOLOK SELATAN",
                "1312": "KAB. PASAMAN BARAT",
                "1371": "KOTA PADANG",
                "1372": "KOTA SOLOK",
                "1373": "KOTA SAWAHLUNTO",
                "1374": "KOTA PADANG PANJANG",
                "1375": "KOTA BUKITTINGGI",
                "1376": "KOTA PAYAKUMBUH",
                "1377": "KOTA PARIAMAN",
                "1401": "KAB. KAMPAR",
                "1402": "KAB. INDRAGIRI HULU",
                "1403": "KAB. BENGKALIS",
                "1404": "KAB. INDRAGIRI HILIR",
                "1405": "KAB. PELALAWAN",
                "1406": "KAB. ROKAN HULU",
                "1407": "KAB. ROKAN HILIR",
                "1408": "KAB. SIAK",
                "1409": "KAB. KUANTAN SINGINGI",
                "1410": "KAB. KEP. MERANTI",
                "1471": "KOTA PEKANBARU",
                "1472": "KOTA DUMAI",
                "1501": "KAB. KERINCI",
                "1502": "KAB. MERANGIN",
                "1503": "KAB. SAROLANGUN",
                "1504": "KAB. BATANGHARI",
                "1505": "KAB. MUARO JAMBI",
                "1506": "KAB. TANJUNG JABUNG BARAT",
                "1507": "KAB. TANJUNG JABUNG TIMUR",
                "1508": "KAB. BUNGO",
                "1509": "KAB. TEBO",
                "1571": "KOTA JAMBI",
                "1572": "KOTA SUNGAI PENUH",
                "1601": "KAB. OGAN KOMERING ULU",
                "1602": "KAB. OGAN KOMERING ILIR",
                "1603": "KAB. MUARA ENIM",
                "1604": "KAB. LAHAT",
                "1605": "KAB. MUSI RAWAS",
                "1606": "KAB. MUSI BANYUASIN",
                "1607": "KAB. BANYUASIN",
                "1608": "KAB. OGAN KOMERING ULU TIMUR",
                "1609": "KAB. OGAN KOMERING ULU SELATAN",
                "1610": "KAB. OGAN ILIR",
                "1611": "KAB. EMPAT LAWANG",
                "1612": "KAB. PENUKAL ABAB LEMATANG ILIR",
                "1613": "KAB. MUSI RAWAS UTARA",
                "1671": "KOTA PALEMBANG",
                "1672": "KOTA PAGAR ALAM",
                "1673": "KOTA LUBUKLINGGAU",
                "1674": "KOTA PRABUMULIH",
                "1701": "KAB. BENGKULU SELATAN",
                "1702": "KAB. REJANG LEBONG",
                "1703": "KAB. BENGKULU UTARA",
                "1704": "KAB. KAUR",
                "1705": "KAB. SELUMA",
                "1706": "KAB. MUKOMUKO",
                "1707": "KAB. LEBONG",
                "1708": "KAB. KEPAHIANG",
                "1709": "KAB. BENGKULU TENGAH",
                "1771": "KOTA BENGKULU",
                "1801": "KAB. LAMPUNG SELATAN",
                "1802": "KAB. LAMPUNG TENGAH",
                "1803": "KAB. LAMPUNG UTARA",
                "1804": "KAB. LAMPUNG BARAT",
                "1805": "KAB. TULANG BAWANG",
                "1806": "KAB. TANGGAMUS",
                "1807": "KAB. LAMPUNG TIMUR",
                "1808": "KAB. WAY KANAN",
                "1809": "KAB. PESAWARAN",
                "1810": "KAB. PRINGSEWU",
                "1811": "KAB. MESUJI",
                "1812": "KAB. TULANG BAWANG BARAT",
                "1813": "KAB. PESISIR BARAT",
                "1871": "KOTA BANDAR LAMPUNG",
                "1872": "KOTA METRO",
                "1901": "KAB. BANGKA",
                "1902": "KAB. BELITUNG",
                "1903": "KAB. BANGKA SELATAN",
                "1904": "KAB. BANGKA TENGAH",
                "1905": "KAB. BANGKA BARAT",
                "1906": "KAB. BELITUNG TIMUR",
                "1971": "KOTA PANGKAL PINANG",
                "2101": "KAB. BINTAN",
                "2102": "KAB. KARIMUN",
                "2103": "KAB. NATUNA",
                "2104": "KAB. LINGGA",
                "2105": "KAB. KEP. ANAMBAS",
                "2171": "KOTA BATAM",
                "2172": "KOTA TANJUNG PINANG",
                "3101": "KAB. KEP. SERIBU",
                "3171": "KOTA JAKARTA PUSAT",
                "3172": "KOTA JAKARTA UTARA",
                "3173": "KOTA JAKARTA BARAT",
                "3174": "KOTA JAKARTA SELATAN",
                "3175": "KOTA JAKARTA TIMUR",
                "3201": "KAB. BOGOR",
                "3202": "KAB. SUKABUMI",
                "3203": "KAB. CIANJUR",
                "3204": "KAB. BANDUNG",
                "3205": "KAB. GARUT",
                "3206": "KAB. TASIKMALAYA",
                "3207": "KAB. CIAMIS",
                "3208": "KAB. KUNINGAN",
                "3209": "KAB. CIREBON",
                "3210": "KAB. MAJALENGKA",
                "3211": "KAB. SUMEDANG",
                "3212": "KAB. INDRAMAYU",
                "3213": "KAB. SUBANG",
                "3214": "KAB. PURWAKARTA",
                "3215": "KAB. KARAWANG",
                "3216": "KAB. BEKASI",
                "3217": "KAB. BANDUNG BARAT",
                "3218": "KAB. PANGANDARAN",
                "3271": "KOTA BOGOR",
                "3272": "KOTA SUKABUMI",
                "3273": "KOTA BANDUNG",
                "3274": "KOTA CIREBON",
                "3275": "KOTA BEKASI",
                "3276": "KOTA DEPOK",
                "3277": "KOTA CIMAHI",
                "3278": "KOTA TASIKMALAYA",
                "3279": "KOTA BANJAR",
                "3301": "KAB. CILACAP",
                "3302": "KAB. BANYUMAS",
                "3303": "KAB. PURBALINGGA",
                "3304": "KAB. BANJARNEGARA",
                "3305": "KAB. KEBUMEN",
                "3306": "KAB. PURWOREJO",
                "3307": "KAB. WONOSOBO",
                "3308": "KAB. MAGELANG",
                "3309": "KAB. BOYOLALI",
                "3310": "KAB. KLATEN",
                "3311": "KAB. SUKOHARJO",
                "3312": "KAB. WONOGIRI",
                "3313": "KAB. KARANGANYAR",
                "3314": "KAB. SRAGEN",
                "3315": "KAB. GROBOGAN",
                "3316": "KAB. BLORA",
                "3317": "KAB. REMBANG",
                "3318": "KAB. PATI",
                "3319": "KAB. KUDUS",
                "3320": "KAB. JEPARA",
                "3321": "KAB. DEMAK",
                "3322": "KAB. SEMARANG",
                "3323": "KAB. TEMANGGUNG",
                "3324": "KAB. KENDAL",
                "3325": "KAB. BATANG",
                "3326": "KAB. PEKALONGAN",
                "3327": "KAB. PEMALANG",
                "3328": "KAB. TEGAL",
                "3329": "KAB. BREBES",
                "3371": "KOTA MAGELANG",
                "3372": "KOTA SURAKARTA",
                "3373": "KOTA SALATIGA",
                "3374": "KOTA SEMARANG",
                "3375": "KOTA PEKALONGAN",
                "3376": "KOTA TEGAL",
                "3401": "KAB. KULON PROGO",
                "3402": "KAB. BANTUL",
                "3403": "KAB. GUNUNG KIDUL",
                "3404": "KAB. SLEMAN",
                "3471": "KOTA YOGYAKARTA",
                "3501": "KAB. PACITAN",
                "3502": "KAB. PONOROGO",
                "3503": "KAB. TRENGGALEK",
                "3504": "KAB. TULUNGAGUNG",
                "3505": "KAB. BLITAR",
                "3506": "KAB. KEDIRI",
                "3507": "KAB. MALANG",
                "3508": "KAB. LUMAJANG",
                "3509": "KAB. JEMBER",
                "3510": "KAB. BANYUWANGI",
                "3511": "KAB. BONDOWOSO",
                "3512": "KAB. SITUBONDO",
                "3513": "KAB. PROBOLINGGO",
                "3514": "KAB. PASURUAN",
                "3515": "KAB. SIDOARJO",
                "3516": "KAB. MOJOKERTO",
                "3517": "KAB. JOMBANG",
                "3518": "KAB. NGANJUK",
                "3519": "KAB. MADIUN",
                "3520": "KAB. MAGETAN",
                "3521": "KAB. NGAWI",
                "3522": "KAB. BOJONEGORO",
                "3523": "KAB. TUBAN",
                "3524": "KAB. LAMONGAN",
                "3525": "KAB. GRESIK",
                "3526": "KAB. BANGKALAN",
                "3527": "KAB. SAMPANG",
                "3528": "KAB. PAMEKASAN",
                "3529": "KAB. SUMENEP",
                "3571": "KOTA KEDIRI",
                "3572": "KOTA BLITAR",
                "3573": "KOTA MALANG",
                "3574": "KOTA PROBOLINGGO",
                "3575": "KOTA PASURUAN",
                "3576": "KOTA MOJOKERTO",
                "3577": "KOTA MADIUN",
                "3578": "KOTA SURABAYA",
                "3579": "KOTA BATU",
                "3601": "KAB. PANDEGLANG",
                "3602": "KAB. LEBAK",
                "3603": "KAB. TANGERANG",
                "3604": "KAB. SERANG",
                "3671": "KOTA TANGERANG",
                "3672": "KOTA CILEGON",
                "3673": "KOTA SERANG",
                "3674": "KOTA TANGERANG SELATAN",
                "5101": "KAB. JEMBRANA",
                "5102": "KAB. TABANAN",
                "5103": "KAB. BADUNG",
                "5104": "KAB. GIANYAR",
                "5105": "KAB. KLUNGKUNG",
                "5106": "KAB. BANGLI",
                "5107": "KAB. KARANGASEM",
                "5108": "KAB. BULELENG",
                "5171": "KOTA DENPASAR",
                "5201": "KAB. LOMBOK BARAT",
                "5202": "KAB. LOMBOK TENGAH",
                "5203": "KAB. LOMBOK TIMUR",
                "5204": "KAB. SUMBAWA",
                "5205": "KAB. DOMPU",
                "5206": "KAB. BIMA",
                "5207": "KAB. SUMBAWA BARAT",
                "5208": "KAB. LOMBOK UTARA",
                "5271": "KOTA MATARAM",
                "5272": "KOTA BIMA",
                "6101": "KAB. SAMBAS",
                "6102": "KAB. MEMPAWAH",
                "6103": "KAB. SANGGAU",
                "6104": "KAB. KETAPANG",
                "6105": "KAB. SINTANG",
                "6106": "KAB. KAPUAS HULU",
                "6107": "KAB. BENGKAYANG",
                "6108": "KAB. LANDAK",
                "6109": "KAB. SEKADAU",
                "6110": "KAB. MELAWI",
                "6111": "KAB. KAYONG UTARA",
                "6112": "KAB. KUBU RAYA",
                "6171": "KOTA PONTIANAK",
                "6172": "KOTA SINGKAWANG",
                "6401": "KAB. PASER",
                "6402": "KAB. KUTAI KARTANEGARA",
                "6403": "KAB. BERAU",
                "6404": "KAB. KUTAI BARAT",
                "6405": "KAB. KUTAI TIMUR",
                "6406": "KAB. PENAJAM PASER UTARA",
                "6407": "KAB. MAHAKAM ULU",
                "6471": "KOTA BALIKPAPAN",
                "6472": "KOTA SAMARINDA",
                "6473": "KOTA BONTANG",
                "7101": "KAB. BOLAANG MONGONDOW",
                "7102": "KAB. MINAHASA",
                "7103": "KAB. KEP. SANGIHE",
                "7104": "KAB. KEP. TALAUD",
                "7105": "KAB. MINAHASA SELATAN",
                "7106": "KAB. MINAHASA UTARA",
                "7107": "KAB. MINAHASA TENGGARA",
                "7108": "KAB. BOLAANG MONGONDOW UTARA",
                "7171": "KOTA MANADO",
                "7172": "KOTA BITUNG",
                "7173": "KOTA TOMOHON",
                "7174": "KOTA KOTAMOBAGU",
                "7201": "KAB. BANGGAI",
                "7202": "KAB. POSO",
                "7203": "KAB. DONGGALA",
                "7204": "KAB. TOLI TOLI",
                "7205": "KAB. BUOL",
                "7206": "KAB. MOROWALI",
                "7207": "KAB. BANGGAI KEPULAUAN",
                "7208": "KAB. PARIGI MOUTONG",
                "7209": "KAB. TOJO UNA UNA",
                "7210": "KAB. SIGI",
                "7271": "KOTA PALU",
                "7301": "KAB. KEP. SELAYAR",
                "7302": "KAB. BANTAENG",
                "7303": "KAB. JENEPONTO",
                "7304": "KAB. TAKALAR",
                "7305": "KAB. GOWA",
                "7306": "KAB. SINJAI",
                "7307": "KAB. BONE",
                "7308": "KAB. MAROS",
                "7309": "KAB. PANGKAJENE KEP.",
                "7310": "KAB. BARRU",
                "7311": "KAB. SOPPENG",
                "7312": "KAB. WAJO",
                "7313": "KAB. SIDENRENG RAPPANG",
                "7314": "KAB. PINRANG",
                "7315": "KAB. ENREKANG",
                "7316": "KAB. LUWU",
                "7317": "KAB. TANA TORAJA",
                "7318": "KAB. LUWU UTARA",
                "7319": "KAB. LUWU TIMUR",
                "7320": "KAB. TORAJA UTARA",
                "7371": "KOTA MAKASSAR",
                "7372": "KOTA PARE PARE",
                "7373": "KOTA PALOPO",
                "7401": "KAB. KOLAKA",
                "7402": "KAB. KONAWE",
                "7403": "KAB. MUNA",
                "7404": "KAB. BUTON",
                "7405": "KAB. KONAWE SELATAN",
                "7406": "KAB. BOMBANA",
                "7407": "KAB. WAKATOBI",
                "7408": "KAB. KOLAKA UTARA",
                "7409": "KAB. KONAWE UTARA",
                "7471": "KOTA KENDARI",
                "7472": "KOTA BAU BAU",
                "7501": "KAB. GORONTALO",
                "7502": "KAB. BOALEMO",
                "7503": "KAB. BONE BOLANGO",
                "7504": "KAB. PAHUWATO",
                "7505": "KAB. GORONTALO UTARA",
                "7571": "KOTA GORONTALO",
                "7601": "KAB. MAJENE",
                "7602": "KAB. POLEWALI MANDAR",
                "7603": "KAB. MAMASA",
                "7604": "KAB. MAMUJU",
                "7605": "KAB. MAMUJU TENGAH",
                "7606": "KAB. MAMUJU UTARA",
                "8101": "KAB. MALUKU TENGGARA BARAT",
                "8102": "KAB. MALUKU TENGGARA",
                "8103": "KAB. MALUKU TENGAH",
                "8104": "KAB. BURU",
                "8105": "KAB. KEP. ARU",
                "8106": "KAB. SERAM BAGIAN BARAT",
                "8107": "KAB. SERAM BAGIAN TIMUR",
                "8108": "KAB. MALUKU BARAT DAYA",
                "8109": "KAB. BURU SELATAN",
                "8171": "KOTA AMBON",
                "8172": "KOTA TUAL",
                "8201": "KAB. HALMAHERA BARAT",
                "8202": "KAB. HALMAHERA TENGAH",
                "8203": "KAB. HALMAHERA UTARA",
                "8204": "KAB. HALMAHERA SELATAN",
                "8205": "KAB. KEP. SULA",
                "8206": "KAB. HALMAHERA TIMUR",
                "8207": "KAB. PULAU MOROTAI",
                "8271": "KOTA TERNATE",
                "8272": "KOTA TIDORE KEPULAUAN",
                "9101": "KAB. MERAUKE",
                "9102": "KAB. JAYAWIJAYA",
                "9103": "KAB. JAYAPURA",
                "9104": "KAB. NABIRE",
                "9105": "KAB. KEP. YAPEN",
                "9106": "KAB. BIAK NUMFOR",
                "9107": "KAB. PUNCAK JAYA",
                "9108": "KAB. PANIAI",
                "9109": "KAB. MIMIKA",
                "9110": "KAB. SARMI",
                "9111": "KAB. KEEROM",
                "9112": "KAB. PEGUNUNGAN BINTANG",
                "9113": "KAB. YAHUKIMO",
                "9114": "KAB. TOLIKARA",
                "9115": "KAB. WAROPEN",
                "9116": "KAB. BOVEN DIGOEL",
                "9117": "KAB. MAPPI",
                "9118": "KAB. ASMAT",
                "9119": "KAB. SUPIORI",
                "9120": "KAB. MAMBERAMO RAYA",
                "9171": "KOTA JAYAPURA",
                "9201": "KAB. SORONG",
                "9202": "KAB. MANOKWARI",
                "9203": "KAB. FAK FAK",
                "9204": "KAB. SORONG SELATAN",
                "9205": "KAB. RAJA AMPAT",
                "9206": "KAB. TELUK BINTUNI",
                "9207": "KAB. TELUK WONDAMA",
                "9208": "KAB. KAIMANA",
                "9209": "KAB. TAMBRAUW",
                "9210": "KAB. MAYBRAT",
                "9211": "KAB. MANOKWARI SELATAN",
                "9212": "KAB. PEGUNUNGAN ARFAK",
                "9271": "KOTA SORONG"
            },
            "districts": {}
        };

        // ============================================================
        // SESSION GUARD (1:1 from GAS js/formApp.html)
        // ============================================================
        var SESSION_KEY = 'msi_form_submitted';

        function markSubmitted(nik) {
            try {
                sessionStorage.setItem(SESSION_KEY, nik || '1');
            } catch (e) {}
        }

        function isAlreadySubmitted() {
            try {
                return !!sessionStorage.getItem(SESSION_KEY);
            } catch (e) {
                return false;
            }
        }

        function showAlreadySubmittedPage() {
            var rf = document.getElementById('registrationForm');
            var sp = document.getElementById('successPage');
            if (rf) rf.style.display = 'none';
            if (sp) {
                sp.style.display = 'block';
                var msg = sp.querySelector('.success-message');
                if (msg) msg.innerHTML =
                    'Anda telah mengirimkan lamaran pada sesi ini.<br><br>Apabila Anda merasa ini adalah kesalahan, silakan hubungi tim Human Resources MITO Group.';
            }
        }
        if (isAlreadySubmitted()) {
            document.addEventListener('DOMContentLoaded', showAlreadySubmittedPage);
        }

        // ============================================================
        // ELEMENT REFERENCES
        // ============================================================
        var form = null;
        var agreementCheckbox = null;
        var agreementError = null;
        var submitBtn = null;
        var submitSpinner = null;
        var submitText = null;
        var emailInput = null;
        var emailValidation = null;
        var workExpSelect = null;
        var lastCompanyGroup = null;
        var loadingOverlay = null;
        var nikInput = null;
        var birthDateInput = null;
        var ageInput = null;
        var genderSelect = null;
        var provinceSelect = null;
        var citySelect = null;
        var districtInput = null;
        var districtLoading = null;
        var districtManualWrap = null;
        var districtManualInput = null;
        var nikFeedback = null;
        var progressFill = null;
        var progressCount = null;
        var progressMessage = null;
        var phoneInput = null;
        var salaryInput = null;
        var nikDuplicateWarning = null;
        var manualBirthDateChanged = false;
        var isSubmitting = false;

        // ============================================================
        // FORM SECTIONS CONFIG (1:1 from GAS FORM_SECTIONS)
        // ============================================================
        var FORM_SECTIONS = [{
                id: 'sectionPersonal',
                badgeId: 'secBadgePersonal',
                fields: ['nik', 'full_name', 'birth_date', 'age', 'gender', 'marital_status']
            },
            {
                id: 'sectionContact',
                badgeId: 'secBadgeContact',
                fields: ['email', 'phone', 'province', 'city', 'address'],
                district: true
            },
            {
                id: 'sectionEmployment',
                badgeId: 'secBadgeEmployment',
                fields: ['position_applied', 'education', 'work_experience', 'current_employment_status',
                    'available_to_join', 'expected_salary', 'recruitment_source'
                ],
                lastCompany: true
            },
        ];

        function isLastCompanyRequired() {
            var we = workExpSelect ? workExpSelect.value : '';
            return we !== '' && we !== 'Fresh Graduate';
        }

        function isDistrictValid() {
            if (districtManualWrap && districtManualWrap.style.display !== 'none') {
                return (districtManualInput.value || '').trim() !== '';
            }
            return !districtInput.disabled && (districtInput.value || '').trim() !== '';
        }

        function isFieldValid(el) {
            if (!el) return false;
            if (el.type === 'checkbox') return !!el.checked;
            var v = (el.value || '').trim();
            if (v === '') return false;
            if (el.checkValidity) return el.checkValidity();
            return true;
        }

        function getSectionFieldIds(sec) {
            var ids = sec.fields.slice();
            if (sec.district) ids.push('__district__');
            if (sec.lastCompany && isLastCompanyRequired()) ids.push('last_company');
            return ids;
        }

        function isSectionComplete(sec) {
            var ids = getSectionFieldIds(sec);
            for (var i = 0; i < ids.length; i++) {
                var id = ids[i];
                if (id === '__district__') {
                    if (!isDistrictValid()) return false;
                    continue;
                }
                if (!isFieldValid(document.getElementById(id))) return false;
            }
            return true;
        }

        function countSection(sec) {
            var ids = getSectionFieldIds(sec);
            var valid = 0;
            for (var i = 0; i < ids.length; i++) {
                var id = ids[i];
                if (id === '__district__') {
                    if (isDistrictValid()) valid++;
                    continue;
                }
                var el = document.getElementById(id);
                if (el && isFieldValid(el)) valid++;
            }
            return {
                total: ids.length,
                valid: valid
            };
        }

        function setSectionBadge(sec, complete) {
            var b = document.getElementById(sec.badgeId);
            if (!b) return;
            b.className = 'section-status' + (complete ? ' done' : '');
            b.innerHTML = complete ?
                '<i class="bi bi-check-circle-fill"></i>' :
                '<i class="bi bi-hourglass-split"></i>';
        }

        function updateFormState() {
            var allComplete = true;
            FORM_SECTIONS.forEach(function(sec) {
                var done = isSectionComplete(sec);
                if (!done) allComplete = false;
                setSectionBadge(sec, done);
            });
            if (allComplete) {
                agreementCheckbox.setAttribute('data-unlocked', '1');
                agreementCheckbox.style.cursor = 'pointer';
                agreementCheckbox.style.opacity = '1';
                agreementCheckbox.onclick = null;
                agreementCheckbox.onfocus = null;
            } else {
                agreementCheckbox.setAttribute('data-unlocked', '0');
                agreementCheckbox.checked = false;
                agreementCheckbox.style.cursor = 'not-allowed';
                agreementCheckbox.style.opacity = '0.5';
                agreementCheckbox.onclick = function() {
                    return false;
                };
                agreementCheckbox.onfocus = function() {
                    this.blur();
                };
                submitBtn.disabled = true;
            }
            var hint = document.getElementById('agreementHint');
            if (hint) hint.style.display = allComplete ? 'none' : 'block';
            submitBtn.disabled = !(allComplete && agreementCheckbox.checked);
        }

        var progressMessages = [{
                min: 0,
                text: 'Mulai mengisi formulir...'
            },
            {
                min: 25,
                text: 'Awal yang bagus'
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
                text: 'Semua data telah lengkap. Silakan kirim pendaftaran.'
            }
        ];

        function updateProgress() {
            var total = 0,
                valid = 0;
            FORM_SECTIONS.forEach(function(sec) {
                var c = countSection(sec);
                total += c.total;
                valid += c.valid;
            });
            total += 1;
            if (agreementCheckbox.checked) valid += 1;
            var percent = total === 0 ? 0 : Math.min(100, Math.round((valid / total) * 100));
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
        // BIRTH DATE — auto-slash DD/MM/YYYY + strict validation (1:1 GAS)
        // ============================================================
        function attachBirthDateListeners() {
            birthDateInput.addEventListener('input', function() {
                manualBirthDateChanged = true;
                var val = this.value.replace(/[^0-9]/g, '');
                var f = '';
                if (val.length > 0) {
                    f = val.substring(0, 2);
                    if (val.length > 2) f += '/' + val.substring(2, 4);
                    if (val.length > 4) f += '/' + val.substring(4, 8);
                }
                this.value = f;
                calculateAge();
                if (f.length === 10) validateBirthDate();
                else {
                    this.classList.remove('is-valid', 'is-invalid');
                }
                updateProgress();
            });
            birthDateInput.addEventListener('blur', function() {
                if (this.value.trim().length > 0) validateBirthDate();
                updateProgress();
            });
        }

        function validateBirthDate() {
            var el = birthDateInput;
            var container = el.closest('.col-md-6');
            var feedback = container ? container.querySelector('.invalid-feedback') : null;
            var val = el.value.trim();

            function setInvalid(msg) {
                el.classList.add('is-invalid');
                el.classList.remove('is-valid');
                if (feedback) {
                    feedback.textContent = msg;
                    feedback.style.display = 'flex';
                }
            }

            function setValid() {
                el.classList.remove('is-invalid');
                el.classList.add('is-valid');
                if (feedback) feedback.style.display = 'none';
            }
            if (!val || val.length < 10) {
                setInvalid('Tanggal lahir wajib diisi dengan format DD/MM/YYYY.');
                return false;
            }
            var p = val.split('/');
            if (p.length !== 3 || p[0].length !== 2 || p[1].length !== 2 || p[2].length !== 4) {
                setInvalid('Format tidak valid. Gunakan DD/MM/YYYY (contoh: 15/08/1990).');
                return false;
            }
            var day = parseInt(p[0], 10),
                month = parseInt(p[1], 10),
                year = parseInt(p[2], 10),
                curYear = new Date().getFullYear();
            if (isNaN(day) || isNaN(month) || isNaN(year)) {
                setInvalid('Tanggal lahir mengandung karakter tidak valid.');
                return false;
            }
            if (month < 1 || month > 12) {
                setInvalid('Bulan tidak valid (01–12).');
                return false;
            }
            if (day < 1 || day > 31) {
                setInvalid('Tanggal tidak valid (01–31).');
                return false;
            }
            if (year < 1900 || year > curYear) {
                setInvalid('Tahun lahir tidak valid (1900–' + curYear + ').');
                return false;
            }
            var d = new Date(year, month - 1, day);
            if (d.getFullYear() !== year || d.getMonth() !== month - 1 || d.getDate() !== day) {
                setInvalid('Tanggal tidak ada dalam kalender.');
                return false;
            }
            var today = new Date();
            today.setHours(0, 0, 0, 0);
            if (d > today) {
                setInvalid('Tanggal lahir tidak boleh tanggal yang akan datang.');
                return false;
            }
            var age = today.getFullYear() - year,
                mDiff = today.getMonth() - (month - 1);
            if (mDiff < 0 || (mDiff === 0 && today.getDate() < day)) age--;
            if (age < 17) {
                setInvalid('Usia minimal untuk mendaftar adalah 17 tahun.');
                return false;
            }
            setValid();
            return true;
        }

        function calculateAge() {
            var val = birthDateInput.value.trim();
            if (!val || val.length < 10) {
                ageInput.value = '';
                return;
            }
            var p = val.split('/');
            if (p.length !== 3) return;
            var day = parseInt(p[0], 10),
                month = parseInt(p[1], 10),
                year = parseInt(p[2], 10);
            if (!day || !month || !year || month < 1 || month > 12 || day < 1 || day > 31) return;
            var bd = new Date(year, month - 1, day),
                today = new Date();
            var age = today.getFullYear() - bd.getFullYear(),
                m = today.getMonth() - bd.getMonth();
            if (m < 0 || (m === 0 && today.getDate() < bd.getDate())) age--;
            ageInput.value = (age >= 0 && age <= 120) ? age : '';
        }

        // ============================================================
        // NIK PARSER + AUTO-FILL (1:1 from GAS)
        // ============================================================
        function parseNIK(nik) {
            if (!nik || nik.length !== 16 || !/^[0-9]{16}$/.test(nik)) return null;
            var rawDay = nik.substring(6, 8),
                rawMonth = nik.substring(8, 10),
                rawYear = nik.substring(10, 12);
            var day = parseInt(rawDay, 10),
                gender = (day > 40) ? 'Perempuan' : 'Laki-laki';
            if (day > 40) day -= 40;
            var month = parseInt(rawMonth, 10),
                year = parseInt(rawYear, 10);
            var fullYear = (year <= 24) ? 2000 + year : 1900 + year;
            if (month < 1 || month > 12 || day < 1 || day > 31) return null;
            return {
                birthDate: {
                    day: day,
                    month: month,
                    year: fullYear,
                    formatted: ('0' + day).slice(-2) + '/' + ('0' + month).slice(-2) + '/' + fullYear
                },
                gender: gender,
                provinceCode: nik.substring(0, 2),
                cityCode: nik.substring(0, 4)
            };
        }

        function processNIK(nik) {
            var r = parseNIK(nik);
            if (!r) {
                showNIKFeedback(false);
                return;
            }
            if (!manualBirthDateChanged) {
                birthDateInput.value = r.birthDate.formatted;
                calculateAge();
                validateBirthDate();
                validateField(ageInput);
            }
            genderSelect.value = r.gender;
            validateField(genderSelect);
            var provinceName = REGIONS.provinces[r.provinceCode];
            var cityName = REGIONS.cities[r.cityCode];
            if (provinceName) {
                provinceSelect.value = r.provinceCode;
                validateField(provinceSelect);
                populateCities(r.provinceCode);
                if (cityName) {
                    citySelect.value = r.cityCode;
                    validateField(citySelect);
                    loadDistricts(r.cityCode);
                }
            }
            showNIKFeedback(true, r);
            updateProgress();
        }

        function showNIKFeedback(success, r) {
            nikFeedback.classList.add('show');
            if (success) {
                var det = ['Tanggal Lahir: ' + r.birthDate.formatted, 'Jenis Kelamin: ' + r.gender];
                if (REGIONS.provinces[r.provinceCode]) det.push('Provinsi: ' + REGIONS.provinces[r.provinceCode]);
                if (REGIONS.cities[r.cityCode]) det.push('Kabupaten/Kota: ' + REGIONS.cities[r.cityCode]);
                nikFeedback.innerHTML =
                    '<div class="alert alert-success p-2 mb-0" style="font-size:12px"><i class="bi bi-check-circle-fill me-1"></i> <strong>Data berhasil dikenali</strong><ul class="mb-0 mt-1 ps-3">' +
                    det.map(function(d) {
                        return '<li>' + d + '</li>';
                    }).join('') + '</ul></div>';
            } else {
                nikFeedback.innerHTML =
                    '<div class="alert alert-warning p-2 mb-0" style="font-size:12px"><i class="bi bi-exclamation-triangle-fill me-1"></i> <strong>NIK tidak valid.</strong> Pastikan nomor KTP terdiri dari 16 digit yang benar.</div>';
            }
        }

        // ============================================================
        // FIELD VALIDATION HELPER
        // ============================================================
        function validateField(el) {
            if (!el) return;
            if (el.id === 'birth_date') {
                validateBirthDate();
                return;
            }
            var container = el.closest('.col-md-6, .col-md-4, .col-12');
            var feedback = container ? container.querySelector('.invalid-feedback') : null;
            if (el.value && el.checkValidity()) {
                el.classList.remove('is-invalid');
                el.classList.add('is-valid');
                if (feedback) feedback.style.display = 'none';
            } else if (el.value && !el.checkValidity()) {
                el.classList.add('is-invalid');
                el.classList.remove('is-valid');
                if (feedback) feedback.style.display = 'flex';
            } else {
                el.classList.remove('is-valid', 'is-invalid');
                if (feedback) feedback.style.display = 'none';
            }
        }

        // ============================================================
        // REGION DROPDOWNS (1:1 from GAS)
        // ============================================================
        function populateProvinces() {
            var html = '<option value="">-- Pilih Provinsi --</option>';
            Object.keys(REGIONS.provinces).sort().forEach(function(code) {
                html += '<option value="' + code + '">' + REGIONS.provinces[code] + '</option>';
            });
            provinceSelect.innerHTML = html;
        }

        function populateCities(provinceCode) {
            citySelect.innerHTML = '<option value="">-- Pilih Kota/Kabupaten --</option>';
            resetDistrict();
            if (!provinceCode) return;
            Object.keys(REGIONS.cities).filter(function(c) {
                    return c.substring(0, 2) === provinceCode;
                }).sort()
                .forEach(function(code) {
                    citySelect.innerHTML += '<option value="' + code + '">' + REGIONS.cities[code] + '</option>';
                });
        }

        function resetDistrict() {
            districtInput.innerHTML = '<option value="">-- Pilih Kota dahulu --</option>';
            districtInput.disabled = true;
            districtInput.value = '';
            districtInput.classList.remove('is-valid', 'is-invalid');
            districtLoading.style.display = 'none';
            districtManualWrap.style.display = 'none';
            districtManualInput.value = '';
            districtManualInput.required = false;
            districtInput.required = true;
        }

        function loadDistricts(cityCode) {
            if (!cityCode) {
                resetDistrict();
                return;
            }
            // Load full kecamatan data from JSON if not already loaded
            if (!window._kecamatanData) {
                districtInput.innerHTML = '<option value="">Memuat data kecamatan...</option>';
                districtInput.disabled = true;
                districtLoading.style.display = 'block';
                fetch('/data/kecamatan_all.json')
                    .then(res => {
                        if (!res.ok) throw new Error('Gagal memuat data kecamatan');
                        return res.json();
                    })
                    .then(data => {
                        window._kecamatanData = data;
                        districtLoading.style.display = 'none';
                        populateDistrictsForCity(cityCode);
                    })
                    .catch(() => {
                        districtLoading.style.display = 'none';
                        showDistrictManualFallback();
                    });
                return;
            }
            populateDistrictsForCity(cityCode);
        }

        function populateDistrictsForCity(cityCode) {
            var data = window._kecamatanData;
            if (!data) {
                resetDistrict();
                return;
            }
            var districts = data[cityCode];
            if (districts && districts.length) {
                var html = '<option value="">-- Pilih Kecamatan --</option>';
                districts.forEach(function(n) {
                    html += '<option value="' + n + '">' + n + '</option>';
                });
                districtInput.innerHTML = html;
                districtInput.disabled = false;
                districtInput.required = true;
                districtManualWrap.style.display = 'none';
                districtManualInput.required = false;
                districtInput.classList.remove('is-invalid');
                districtInput.classList.add('is-valid');
            } else {
                // Fallback to manual input if no districts for this city
                showDistrictManualFallback();
            }
            districtLoading.style.display = 'none';
        }

        function showDistrictManualFallback() {
            districtInput.innerHTML = '<option value="">-</option>';
            districtInput.disabled = true;
            districtInput.required = false;
            districtManualWrap.style.display = 'block';
            districtManualInput.required = true;
            districtManualInput.focus();
            districtInput.classList.remove('is-valid');
        }

        function populateDistrictOptions(list) {
            var html = '<option value="">-- Pilih Kecamatan --</option>';
            list.forEach(function(item) {
                html += '<option value="' + item.nama + '">' + item.nama + '</option>';
            });
            districtInput.innerHTML = html;
            districtInput.disabled = false;
            districtInput.required = true;
            districtManualWrap.style.display = 'none';
            districtManualInput.required = false;
        }

        function showDistrictManual() {
            districtInput.innerHTML = '<option value="">-</option>';
            districtInput.disabled = true;
            districtInput.required = false;
            districtManualWrap.style.display = 'block';
            districtManualInput.required = true;
            districtManualInput.focus();
        }

        // ============================================================
        // EMAIL TYPO DETECTION (1:1 from GAS)
        // ============================================================
        var commonTypos = {
            'gmial.com': 'gmail.com',
            'gmai.com': 'gmail.com',
            'gmail.con': 'gmail.com',
            'hotnail.com': 'hotmail.com',
            'hotmai.com': 'hotmail.com',
            'hotmail.con': 'hotmail.com',
            'yahooo.com': 'yahoo.com',
            'yaho.com': 'yahoo.com',
            'outlok.com': 'outlook.com',
            'outlook.con': 'outlook.com'
        };

        function validateEmailField(showToastOnError) {
            if (!emailInput) return true;
            var email = emailInput.value.trim();
            var feedback = document.getElementById('emailFeedback');
            var message = '';

            if (!email) {
                message = 'Email wajib diisi.';
            } else if (!emailInput.checkValidity() || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                message = 'Silakan masukkan alamat email yang valid.';
            }

            if (message) {
                emailInput.classList.add('is-invalid');
                emailInput.classList.remove('is-valid');
                if (feedback) {
                    feedback.textContent = message;
                    feedback.style.display = 'flex';
                }
                if (showToastOnError && typeof window.showToast === 'function') {
                    window.showToast({
                        type: 'error',
                        title: 'Email tidak valid',
                        message: message
                    });
                }
                return false;
            }

            emailInput.value = email;
            emailInput.classList.remove('is-invalid');
            emailInput.classList.add('is-valid');
            if (feedback) feedback.style.display = 'none';
            return true;
        }

        // ============================================================
        // FORM SUBMISSION (Laravel POST — no google.script.run)
        // ============================================================
        function initForm() {
            form = document.getElementById('formPendaftaran');
            agreementCheckbox = document.getElementById('agreement');
            agreementError = document.getElementById('agreementError');
            submitBtn = document.getElementById('submitBtn');
            submitSpinner = document.getElementById('submitSpinner');
            submitText = document.getElementById('submitText');
            emailInput = document.getElementById('email');
            emailValidation = document.getElementById('emailValidation');
            workExpSelect = document.getElementById('work_experience');
            lastCompanyGroup = document.getElementById('lastCompanyGroup');
            loadingOverlay = document.getElementById('loadingOverlay');
            nikInput = document.getElementById('nik');
            birthDateInput = document.getElementById('birth_date');
            ageInput = document.getElementById('age');
            genderSelect = document.getElementById('gender');
            provinceSelect = document.getElementById('province');
            citySelect = document.getElementById('city');
            districtInput = document.getElementById('district');
            districtLoading = document.getElementById('districtLoading');
            districtManualWrap = document.getElementById('districtManualWrap');
            districtManualInput = document.getElementById('districtManual');
            nikFeedback = document.getElementById('nikFeedback');
            progressFill = document.getElementById('progressFill');
            progressCount = document.getElementById('progressCount');
            progressMessage = document.getElementById('progressMessage');
            phoneInput = document.getElementById('phone');
            salaryInput = document.getElementById('expected_salary');

            // Inject duplicate NIK warning element after nikFeedback
            nikDuplicateWarning = document.createElement('div');
            nikDuplicateWarning.className = 'mt-1';
            nikDuplicateWarning.id = 'nikDuplicateWarning';
            nikFeedback.parentNode.insertBefore(nikDuplicateWarning, nikFeedback.nextSibling);

            populateProvinces();
            attachBirthDateListeners();

            // NIK input
            nikInput.addEventListener('input', function() {
                var nik = this.value.replace(/[^0-9]/g, '').substring(0, 16);
                this.value = nik;
                if (nik.length === 16) processNIK(nik);
                else nikFeedback.innerHTML = nik.length > 0 ?
                    '<span class="text-muted" style="font-size:12px">Ketik 16 digit NIK... (' + nik.length +
                    '/16)</span>' : '';
                validateField(this);
                updateProgress();
            });

            // Province / City / District
            provinceSelect.addEventListener('change', function() {
                populateCities(this.value);
                validateField(this);
                updateProgress();
            });
            citySelect.addEventListener('change', function() {
                validateField(this);
                loadDistricts(this.value);
                updateProgress();
            });
            districtInput.addEventListener('change', function() {
                validateField(this);
                updateProgress();
            });
            districtManualInput.addEventListener('input', function() {
                this.classList.toggle('is-valid', this.value.trim() !== '');
                if (!this.value.trim()) this.classList.remove('is-valid', 'is-invalid');
                updateProgress();
            });
            districtManualInput.addEventListener('blur', function() {
                if (this.value.trim() !== '') this.classList.add('is-valid');
                else if (this.required) this.classList.add('is-invalid');
            });

            // Phone formatting
            phoneInput.addEventListener('input', function() {
                this.value = this.value.replace(/[^0-9]/g, '').substring(0, 13);
                validateField(this);
                updateProgress();
            });

            // Salary formatting (Rp)
            salaryInput.addEventListener('input', function() {
                var raw = this.value.replace(/[^0-9]/g, '');
                this.value = raw.length > 0 ? parseInt(raw, 10).toLocaleString('id-ID') : '';
                validateField(this);
                updateProgress();
            });

            // Email typo detection
            emailInput.addEventListener('blur', function() {
                var email = this.value.trim();
                emailValidation.style.display = 'none';
                if (!validateEmailField(false)) return;
                var valid = this.checkValidity();
                if (!valid) {
                    emailValidation.style.cssText =
                        'display:flex;font-size:12px;color:#991b1b;align-items:center;gap:4px;';
                    emailValidation.innerHTML = '<i class="bi bi-x-circle-fill"></i> Format email tidak valid';
                    return;
                }
                var domain = email.split('@')[1];
                if (commonTypos[domain]) {
                    emailValidation.style.cssText =
                        'display:flex;font-size:12px;color:#8a6100;align-items:center;gap:4px;';
                    emailValidation.innerHTML =
                        '<i class="bi bi-lightbulb-fill"></i> Apakah yang Anda maksud: <strong>' + email.split('@')[
                            0] + '@' + commonTypos[domain] + '</strong>?';
                } else {
                    emailValidation.style.cssText =
                        'display:flex;font-size:12px;color:#166534;align-items:center;gap:4px;';
                    emailValidation.innerHTML = '<i class="bi bi-check-circle-fill"></i> Format email valid';
                }
            });
            emailInput.addEventListener('input', function() {
                emailValidation.style.display = 'none';
                validateField(this);
                if (this.value.trim()) validateEmailField(false);
                updateProgress();
            });

            // Work experience → show/hide last company
            workExpSelect.addEventListener('change', function() {
                var lc = document.getElementById('last_company');
                if (this.value === '' || this.value === 'Fresh Graduate') {
                    lastCompanyGroup.style.display = 'none';
                    lc.value = '';
                    lc.disabled = true;
                    lc.required = false;
                    lc.classList.remove('is-valid', 'is-invalid');
                } else {
                    lastCompanyGroup.style.display = 'block';
                    lc.disabled = false;
                    lc.required = true;
                }
                validateField(this);
                updateProgress();
            });

            // Agreement checkbox
            agreementCheckbox.addEventListener('change', function() {
                if (this.getAttribute('data-unlocked') !== '1') {
                    this.checked = false;
                    return;
                }
                if (this.checked) {
                    agreementError.style.display = 'none';
                    this.classList.add('is-valid');
                    this.classList.remove('is-invalid');
                } else {
                    this.classList.remove('is-valid');
                }
                updateProgress();
            });

            // Real-time validation on all fields
            var inputs = form.querySelectorAll('input[required],select[required],textarea[required]');
            inputs.forEach(function(input) {
                if (input.id === 'birth_date') return;
                input.addEventListener('blur', function() {
                    validateField(this);
                    updateProgress();
                });
                input.addEventListener('input', function() {
                    validateField(this);
                    updateProgress();
                });
                input.addEventListener('change', function() {
                    validateField(this);
                    updateProgress();
                });
            });
            var lc = document.getElementById('last_company');
            ['input', 'blur'].forEach(function(ev) {
                lc.addEventListener(ev, function() {
                    this.classList.toggle('is-valid', this.value.trim() !== '');
                    if (!this.value.trim()) this.classList.remove('is-valid', 'is-invalid');
                });
            });

            // Form submit — Laravel native POST (no google.script.run)
            form.addEventListener('submit', function(e) {
                if (isSubmitting) {
                    e.preventDefault();
                    return;
                }
                if (isAlreadySubmitted()) {
                    e.preventDefault();
                    showAlreadySubmittedPage();
                    return;
                }
                if (!agreementCheckbox.checked) {
                    e.preventDefault();
                    agreementError.style.display = 'block';
                    agreementCheckbox.classList.add('is-invalid');
                    return;
                }
                if (!validateEmailField(true)) {
                    e.preventDefault();
                    emailInput.focus();
                    return;
                }
                agreementError.style.display = 'none';
                if (!form.checkValidity() || !validateBirthDate()) {
                    e.preventDefault();
                    form.classList.add('was-validated');
                    if (!validateBirthDate()) birthDateInput.scrollIntoView({
                        behavior: 'smooth',
                        block: 'center'
                    });
                    return;
                }
                // Valid — show loading overlay, let form submit normally
                isSubmitting = true;
                submitBtn.disabled = true;
                submitSpinner.classList.remove('d-none');
                submitText.textContent = 'Mengirim...';
                loadingOverlay.classList.add('is-active');
            });

            updateProgress();
        }

        document.addEventListener('DOMContentLoaded', initForm);
    </script>
@endsection
@endsection
