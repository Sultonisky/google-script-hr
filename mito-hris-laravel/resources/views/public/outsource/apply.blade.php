@extends('layouts.public')

@section('title', 'Registrasi Karyawan Outsource - MITO Group')
@section('description', 'Formulir registrasi karyawan outsource untuk keperluan administrasi HRIS MITO.')
@section('robots', 'noindex,follow,noarchive')

@section('content')
    <!-- Branded submit loading overlay -->
    <div class="public-submit-loader" id="loadingOverlay" role="status" aria-label="Mengirim data">
        <div class="public-submit-loader-content">
            <img class="public-loader-logo" src="{{ asset('assets/mito-red-load.png') }}" alt="MITO">
            <div class="public-loader-dots" aria-hidden="true"><span></span><span></span><span></span></div>
            <span class="public-submit-loader-label">Mengirim data...</span>
        </div>
    </div>

    <!-- HERO (1:1 from GAS OutsourceForm.html) -->
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

    <!-- PROGRESS (sticky 1:1 from GAS OutsourceForm.html) -->
    <div class="progress-section">
        <div class="container">
            <div class="progress-label">Progress Pengisian</div>
            <div class="progress-bar-wrap">
                <div class="progress-bar-fill" id="progressFill"></div>
            </div>
            <div class="progress-meta">
                <span id="progressCount">0 dari 36 data telah lengkap</span>
                <span class="progress-message" id="progressMessage">Mulai mengisi formulir...</span>
            </div>
        </div>
    </div>

    <!-- FORM BODY (1:1 from GAS partials/OutsourceFormBody.html) -->
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

            <div id="registrationForm">
                <form action="{{ route('public.outsource.store') }}" method="POST" id="formOutsource" novalidate>
                    @csrf
                    <input type="hidden" name="consent_timestamp" id="consentTimestamp">
                    <input type="hidden" name="consent_device" id="consentDevice">
                    <input type="hidden" name="consent_latitude" id="consentLatitude">
                    <input type="hidden" name="consent_longitude" id="consentLongitude">
                    <input type="hidden" name="consent_location" id="consentLocation">
                    {{-- BUG FIX #2: carries resolved city NAME so backend never stores the numeric code --}}
                    <input type="hidden" name="kota_nama" id="kotaNama" value="{{ old('kota_nama') }}">

                    <!-- SECTION 1: PERSONAL INFORMATION -->
                    <div class="form-section" id="sectionPersonal">
                        <div class="form-section-header">
                            <i class="bi bi-person-fill"></i>
                            <div>
                                <h5>Informasi Pribadi</h5>
                                <p>Data identitas dan informasi personal Anda</p>
                            </div>
                            <span class="section-status" id="secBadgePersonal"><i class="bi bi-hourglass-split"></i> Belum
                                lengkap</span>
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
                                            NIK. Pastikan data sudah sesuai sebelum mengirim.</div>
                                    </div>
                                    <div class="mt-1" id="nikFeedback"></div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fw-semibold" for="full_name" style="font-size:13px">Nama
                                        Lengkap <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="full_name" name="nama_lengkap"
                                        value="{{ old('nama_lengkap') }}" required minlength="3" maxlength="255"
                                        pattern="[\p{L}]+( [\p{L}]+)*" autocomplete="name" data-sanitize-name="true">
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
                                    <label class="form-label fw-semibold" for="birth_place" style="font-size:13px">Tempat
                                        Lahir <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="birth_place" name="tempat_lahir"
                                        value="{{ old('tempat_lahir') }}" required placeholder="Contoh: Jakarta">
                                    <div class="invalid-feedback">Tempat lahir wajib diisi.</div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fw-semibold" for="age"
                                        style="font-size:13px">Usia</label>
                                    <input type="number" class="form-control" id="age" name="usia"
                                        value="{{ old('usia') }}" readonly
                                        style="background-color:#f8f9fa;cursor:default;"
                                        placeholder="Otomatis dari Tanggal Lahir">
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
                                    <label class="form-label fw-semibold" for="religion" style="font-size:13px">Agama
                                        <span class="text-danger">*</span></label>
                                    <select class="form-select" id="religion" name="agama" required>
                                        <option value="">-- Pilih Agama --</option>
                                        <option value="Islam">Islam</option>
                                        <option value="Kristen">Kristen</option>
                                        <option value="Katolik">Katolik</option>
                                        <option value="Hindu">Hindu</option>
                                        <option value="Buddha">Buddha</option>
                                        <option value="Khonghucu">Khonghucu</option>
                                    </select>
                                    <div class="invalid-feedback">Agama wajib dipilih.</div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fw-semibold" for="blood_type"
                                        style="font-size:13px">Golongan Darah <span class="text-danger">*</span></label>
                                    <select class="form-select" id="blood_type" name="golongan_darah" required>
                                        <option value="">-- Pilih Golongan Darah --</option>
                                        <option value="A">A</option>
                                        <option value="B">B</option>
                                        <option value="AB">AB</option>
                                        <option value="O">O</option>
                                    </select>
                                    <div class="invalid-feedback">Golongan darah wajib dipilih.</div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fw-semibold" for="marital_status"
                                        style="font-size:13px">Status Pernikahan <span
                                            class="text-danger">*</span></label>
                                    <select class="form-select" id="marital_status" name="status_pernikahan" required>
                                        <option value="">-- Pilih Status Pernikahan --</option>
                                        <option value="Belum Menikah">Belum Menikah</option>
                                        <option value="Menikah">Menikah</option>
                                        <option value="Cerai">Cerai</option>
                                    </select>
                                    <div class="invalid-feedback">Status pernikahan wajib dipilih.</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- SECTION 2: CONTACT & ADDRESS (1:1 from GAS sectionContact) -->
                    <div class="form-section" id="sectionContact">
                        <div class="form-section-header">
                            <i class="bi bi-envelope-fill"></i>
                            <div>
                                <h5>Informasi Kontak</h5>
                                <p>Data komunikasi dan alamat domisili</p>
                            </div>
                            <span class="section-status" id="secBadgeContact"><i
                                    class="bi bi-hourglass-split"></i></span>
                        </div>
                        <div class="form-section-body p-4">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold" for="email" style="font-size:13px">Email
                                        Pribadi <span class="text-danger">*</span></label>
                                    <input type="email" class="form-control" id="email" name="email_pribadi"
                                        value="{{ old('email_pribadi') }}" required autocomplete="email">
                                    <div class="invalid-feedback">Format alamat email tidak valid.</div>
                                    <div id="emailValidation" class="mt-1" style="display:none;font-size:12px;"></div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fw-semibold" for="working_email"
                                        style="font-size:13px">Email Kantor (MITO) <span
                                            class="text-danger">*</span></label>
                                    <input type="email" class="form-control" id="working_email" name="email_kantor"
                                        value="{{ old('email_kantor') }}" required autocomplete="off"
                                        placeholder="nama@mitogroup.co.id">
                                    <div class="invalid-feedback">Email kantor wajib diisi.</div>
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
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fw-semibold" for="province" style="font-size:13px">Provinsi
                                        <span class="text-danger">*</span></label>
                                    <select class="form-select" id="province" name="provinsi" required>
                                        <option value="">-- Pilih Provinsi --</option>
                                    </select>
                                    <div class="invalid-feedback">Provinsi wajib dipilih.</div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fw-semibold" for="city"
                                        style="font-size:13px">Kota/Kabupaten <span class="text-danger">*</span></label>
                                    <select class="form-select" id="city" name="kota" required>
                                        <option value="">-- Pilih Kota/Kabupaten --</option>
                                    </select>
                                    <div class="invalid-feedback">Kota/Kabupaten wajib dipilih.</div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fw-semibold" for="district" style="font-size:13px">Kecamatan
                                        <span class="text-danger">*</span></label>
                                    <select class="form-select" id="district" name="kecamatan" required disabled>
                                        <option value="">-- Pilih Kota dahulu --</option>
                                    </select>
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
                                    <label class="form-label fw-semibold" for="address" style="font-size:13px">Alamat
                                        KTP <span class="text-danger">*</span></label>
                                    <textarea class="form-control" id="address" name="alamat_ktp" rows="2" required
                                        placeholder="Jalan, RT/RW, Kelurahan, Kecamatan, Kota, Provinsi...">{{ old('alamat_ktp') }}</textarea>
                                    <div class="invalid-feedback">Alamat KTP wajib diisi.</div>
                                </div>

                                <div class="col-12">
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" id="same_address">
                                        <label class="form-check-label fw-semibold" for="same_address"
                                            style="font-size:12.5px">Alamat domisili saat ini sama dengan alamat
                                            KTP</label>
                                    </div>
                                    <label class="form-label fw-semibold" for="address_residential"
                                        style="font-size:13px">Alamat Tinggal (Domisili) <span
                                            class="text-danger">*</span></label>
                                    <textarea class="form-control" id="address_residential" name="alamat_domisili" rows="2" required
                                        placeholder="Alamat tempat tinggal saat ini...">{{ old('alamat_domisili') }}</textarea>
                                    <div class="invalid-feedback">Alamat tinggal wajib diisi.</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- SECTION 3: DATA PENEMPATAN (1:1 from GAS sectionEmployment) -->
                    <div class="form-section" id="sectionEmployment">
                        <div class="form-section-header">
                            <i class="bi bi-briefcase-fill"></i>
                            <div>
                                <h5>Data Penempatan</h5>
                                <p>Informasi posisi dan penempatan di MITO Group</p>
                            </div>
                            <span class="section-status" id="secBadgeEmployment"><i
                                    class="bi bi-hourglass-split"></i></span>
                        </div>
                        <div class="form-section-body p-4">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold" for="branch_name"
                                        style="font-size:13px">Entitas Perusahaan (Branch) <span
                                            class="text-danger">*</span></label>
                                    <select class="form-select" id="branch_name" name="cabang_penempatan" required>
                                        <option value="">-- Pilih Entitas --</option>
                                        <option value="PT Mahakarya Sukses Indonesia">PT Mahakarya Sukses Indonesia
                                        </option>
                                        <option value="PT Stein Perkasa Internasional">PT Stein Perkasa Internasional
                                        </option>
                                        <option value="PT Perkasa Injeksi Indonesia">PT Perkasa Injeksi Indonesia</option>
                                        <option value="PT Mitra Elektro Perkasa">PT Mitra Elektro Perkasa</option>
                                    </select>
                                    <div class="invalid-feedback">Entitas perusahaan wajib dipilih.</div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fw-semibold" for="outsource_vendor"
                                        style="font-size:13px">Vendor Outsource <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="outsource_vendor"
                                        name="vendor_outsource" value="{{ old('vendor_outsource') }}" required
                                        minlength="3" maxlength="120" placeholder="Contoh: PT Nama Vendor Outsource">
                                    <div class="invalid-feedback">Vendor outsource wajib diisi (min. 3 karakter).</div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fw-semibold" for="division" style="font-size:13px">Divisi
                                        <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="division" name="divisi"
                                        value="{{ old('divisi') }}" required placeholder="Contoh: Finance, IT, HRD">
                                    <div class="invalid-feedback">Divisi wajib diisi.</div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fw-semibold" for="department"
                                        style="font-size:13px">Departemen <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="department" name="departemen"
                                        value="{{ old('departemen') }}" required
                                        placeholder="Contoh: Human Resources, Finance">
                                    <div class="invalid-feedback">Departemen wajib diisi.</div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fw-semibold" for="area_kerja" style="font-size:13px">Area
                                        Kerja <span class="text-danger">*</span></label>
                                    <select class="form-select" id="area_kerja" name="area_kerja" required>
                                        <option value="">-- Pilih Area Kerja --</option>
                                        <option value="Head Office">Head Office</option>
                                        <option value="Cabang">Cabang</option>
                                        <option value="Pabrik">Pabrik</option>
                                    </select>
                                    <div class="invalid-feedback">Area kerja wajib dipilih.</div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fw-semibold" for="cost_center" style="font-size:13px">Cost
                                        Center <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="cost_center" name="cost_center"
                                        value="{{ old('cost_center') }}" required
                                        placeholder="Contoh: IT, Finance, Manufacture">
                                    <div class="invalid-feedback">Cost center wajib diisi.</div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fw-semibold" for="lokasi_kerja"
                                        style="font-size:13px">Lokasi Kerja <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="lokasi_kerja" name="lokasi_kerja"
                                        value="{{ old('lokasi_kerja') }}" required
                                        placeholder="Contoh: Jakarta, Bandung, Surabaya">
                                    <div class="invalid-feedback">Lokasi kerja wajib diisi.</div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fw-semibold" for="position" style="font-size:13px">Posisi /
                                        Jabatan <span class="text-danger">*</span></label>
                                    <select class="form-select" id="position" name="posisi_jabatan" required>
                                        <option value="">-- Pilih Posisi --</option>
                                        @foreach ($positions ?? [] as $pos)
                                            <option value="{{ $pos }}"
                                                {{ old('posisi_jabatan') === $pos ? 'selected' : '' }}>{{ $pos }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <div class="invalid-feedback">Posisi/jabatan wajib dipilih.</div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fw-semibold" for="job_level" style="font-size:13px">Job
                                        Level <span class="text-danger">*</span></label>
                                    <select class="form-select" id="job_level" name="job_level" required>
                                        <option value="">-- Pilih Job Level --</option>
                                        <option value="Associate">Associate</option>
                                        <option value="Supervisor">Supervisor</option>
                                        <option value="Manager">Manager</option>
                                    </select>
                                    <div class="invalid-feedback">Job level wajib dipilih.</div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fw-semibold" for="employee_status"
                                        style="font-size:13px">Status Karyawan <span class="text-danger">*</span></label>
                                    <select class="form-select" id="employee_status" name="status_karyawan" required>
                                        <option value="">-- Pilih Status --</option>
                                        <option value="Probation">Probation</option>
                                        <option value="Contract">Contract</option>
                                        <option value="Permanent">Permanent</option>
                                        <option value="Outsource">Outsource</option>
                                    </select>
                                    <div class="invalid-feedback">Status karyawan wajib dipilih.</div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fw-semibold" for="join_date" style="font-size:13px">Tanggal
                                        Masuk (Join Date) <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control" id="join_date" name="tanggal_masuk"
                                        value="{{ old('tanggal_masuk', date('Y-m-d')) }}" required>
                                    <div class="invalid-feedback">Tanggal masuk wajib diisi.</div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fw-semibold" for="contract_end_date"
                                        style="font-size:13px">Tanggal Berakhir Kontrak <span
                                            class="text-danger">*</span></label>
                                    <input type="date" class="form-control" id="contract_end_date"
                                        name="tanggal_berakhir_kontrak" value="{{ old('tanggal_berakhir_kontrak') }}"
                                        required>
                                    <div class="invalid-feedback">Tanggal berakhir kontrak wajib diisi.</div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fw-semibold" for="direct_superior"
                                        style="font-size:13px">Atasan Langsung (Direct Superior) <span
                                            class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="direct_superior"
                                        name="atasan_langsung" value="{{ old('atasan_langsung') }}" required
                                        placeholder="Nama atasan langsung">
                                    <div class="invalid-feedback">Atasan langsung wajib diisi.</div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fw-semibold" for="indirect_superior"
                                        style="font-size:13px">Atasan Tidak Langsung (Indirect Superior) <span
                                            class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="indirect_superior"
                                        name="atasan_tidak_langsung" value="{{ old('atasan_tidak_langsung') }}" required
                                        placeholder="Nama atasan tidak langsung">
                                    <div class="invalid-feedback">Atasan tidak langsung wajib diisi.</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- SECTION 4: BANK & PAYROLL (1:1 from GAS sectionBank) -->
                    <div class="form-section" id="sectionBank">
                        <div class="form-section-header">
                            <i class="bi bi-credit-card-fill"></i>
                            <div>
                                <h5>Data Bank &amp; Payroll</h5>
                                <p>Informasi rekening bank, NPWP, dan BPJS</p>
                            </div>
                            <span class="section-status" id="secBadgeBank"><i class="bi bi-hourglass-split"></i> Belum
                                lengkap</span>
                        </div>
                        <div class="form-section-body p-4">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold" for="bank_name" style="font-size:13px">Nama
                                        Bank</label>
                                    <input type="text" class="form-control bg-light" id="bank_name" name="nama_bank"
                                        value="BCA" readonly style="cursor:default;" disabled>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fw-semibold" for="bank_account" style="font-size:13px">Nomor
                                        Rekening <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="bank_account" name="nomor_rekening"
                                        value="{{ old('nomor_rekening') }}" required inputmode="numeric"
                                        placeholder="Contoh: 1234567890">
                                    <div class="invalid-feedback">Nomor rekening wajib diisi.</div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fw-semibold" for="bank_account_holder"
                                        style="font-size:13px">Nama Pemilik Rekening <span
                                            class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="bank_account_holder"
                                        name="nama_pemilik_rekening" value="{{ old('nama_pemilik_rekening') }}" required
                                        placeholder="Nama sesuai di buku rekening">
                                    <div class="invalid-feedback">Nama pemilik rekening wajib diisi.</div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fw-semibold" for="npwp" style="font-size:13px">NPWP
                                        <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="npwp" name="npwp"
                                        value="{{ old('npwp') }}" required maxlength="20"
                                        placeholder="Contoh: 01.123.456.7-123.000">
                                    <div class="invalid-feedback">NPWP wajib diisi.</div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fw-semibold" for="ptkp_status" style="font-size:13px">Status
                                        PTKP <span class="text-danger">*</span></label>
                                    <select class="form-select" id="ptkp_status" name="status_ptkp" required>
                                        <option value="">-- Pilih Status PTKP --</option>
                                        <option value="TK/0">TK/0 - Tidak Kawin Tanpa Tanggungan</option>
                                        <option value="TK/1">TK/1 - Tidak Kawin 1 Tanggungan</option>
                                        <option value="TK/2">TK/2 - Tidak Kawin 2 Tanggungan</option>
                                        <option value="TK/3">TK/3 - Tidak Kawin 3 Tanggungan</option>
                                        <option value="K/0">K/0 - Kawin Tanpa Tanggungan</option>
                                        <option value="K/1">K/1 - Kawin 1 Tanggungan</option>
                                        <option value="K/2">K/2 - Kawin 2 Tanggungan</option>
                                        <option value="K/3">K/3 - Kawin 3 Tanggungan</option>
                                    </select>
                                    <div class="invalid-feedback">Status PTKP wajib dipilih.</div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fw-semibold" for="bpjs_ketenagakerjaan"
                                        style="font-size:13px">BPJS Ketenagakerjaan <span
                                            class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="bpjs_ketenagakerjaan"
                                        name="bpjs_ketenagakerjaan" value="{{ old('bpjs_ketenagakerjaan') }}" required
                                        maxlength="13" inputmode="numeric" placeholder="Contoh: 123456789012">
                                    <div class="invalid-feedback">BPJS Ketenagakerjaan wajib diisi.</div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fw-semibold" for="bpjs_kesehatan"
                                        style="font-size:13px">BPJS Kesehatan <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="bpjs_kesehatan" name="bpjs_kesehatan"
                                        value="{{ old('bpjs_kesehatan') }}" required maxlength="13" inputmode="numeric"
                                        placeholder="Contoh: 1234567890123">
                                    <div class="invalid-feedback">BPJS Kesehatan wajib diisi.</div>
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

            {{-- SUCCESS PAGE (1:1 from GAS successPage div) --}}
            <div id="successPage" style="display:none;text-align:center;padding:80px 20px;">
                <div class="success-icon"
                    style="width:100px;height:100px;background:linear-gradient(135deg,#ecfdf3,#d1fae5);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 24px;color:#166534;font-size:48px;border:4px solid #166534;box-shadow:0 6px 20px -6px rgba(22,101,52,0.2);">
                    <i class="bi bi-check-circle-fill"></i>
                </div>
                <h2 class="success-title" style="font-size:30px;font-weight:800;color:#1f2937;margin-bottom:16px;">
                    Registrasi Berhasil</h2>
                <p class="success-message"
                    style="font-size:15px;color:#6b7280;line-height:1.8;max-width:540px;margin:0 auto 32px;">
                    Terima kasih, data Anda telah berhasil kami terima.<br><br>
                    Data outsource Anda akan diproses oleh tim Human Resources MITO Group untuk keperluan administrasi
                    HRIS.<br><br>
                    Apabila ada pertanyaan, silakan menghubungi tim HR.
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
        // REGIONS DATASET (same as career form, 1:1 from GAS js/regions.html)
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
                "1271": "KOTA MEDAN",
                "1371": "KOTA PADANG",
                "1471": "KOTA PEKANBARU",
                "1671": "KOTA PALEMBANG",
                "1771": "KOTA BENGKULU",
                "1871": "KOTA BANDAR LAMPUNG",
                "1971": "KOTA PANGKAL PINANG",
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
                "6171": "KOTA PONTIANAK",
                "6172": "KOTA SINGKAWANG",
                "6471": "KOTA BALIKPAPAN",
                "6472": "KOTA SAMARINDA",
                "6473": "KOTA BONTANG",
                "7171": "KOTA MANADO",
                "7271": "KOTA PALU",
                "7371": "KOTA MAKASSAR",
                "7372": "KOTA PARE PARE",
                "7373": "KOTA PALOPO",
                "7471": "KOTA KENDARI",
                "7472": "KOTA BAU BAU",
                "7571": "KOTA GORONTALO",
                "8171": "KOTA AMBON",
                "8172": "KOTA TUAL",
                "8271": "KOTA TERNATE",
                "8272": "KOTA TIDORE KEPULAUAN",
                "9171": "KOTA JAYAPURA",
                "9271": "KOTA SORONG"
            },
            "districts": {
                "3171": ["Cempaka Putih", "Gambir", "Johar Baru", "Kemayoran", "Menteng", "Sawah Besar", "Senen",
                    "Tanah Abang"
                ],
                "3172": ["Cilincing", "Kelapa Gading", "Koja", "Pademangan", "Penjaringan", "Tanjung Priok"],
                "3173": ["Cengkareng", "Grogol Petamburan", "Kalideres", "Kebon Jeruk", "Kembangan", "Palmerah",
                    "Taman Sari", "Tambora"
                ],
                "3174": ["Cilandak", "Jagakarsa", "Kebayoran Baru", "Kebayoran Lama", "Mampang Prapatan", "Pancoran",
                    "Pasar Minggu", "Pesanggrahan", "Setiabudi", "Tebet"
                ],
                "3175": ["Cipayung", "Ciracas", "Duren Sawit", "Jatinegara", "Kramat Jati", "Makasar", "Matraman",
                    "Pasar Rebo", "Pulo Gadung"
                ],
                "3273": ["Bandung Kidul", "Bandung Kulon", "Bandung Wetan", "Bojongloa Kaler", "Bojongloa Kidul",
                    "Cibeunying Kaler", "Cibeunying Kidul", "Cidadap", "Cinambo", "Coblong", "Gedebage",
                    "Kiaracondong", "Lengkong", "Mandalajati", "Panyileukan", "Rancasari", "Regol", "Sukajadi",
                    "Sukasari", "Sumur Bandung", "Ujungberung"
                ],
                "3275": ["Bekasi Barat", "Bekasi Selatan", "Bekasi Timur", "Bekasi Utara", "Medan Satria", "Rawalumbu",
                    "Jati Asih", "Jati Sampurna", "Bantar Gebang", "Mustika Jaya"
                ],
                "3276": ["Beji", "Bojongsari", "Cilodong", "Cimanggis", "Cinere", "Cipayung", "Depok", "Limo",
                    "Pancoran Mas", "Sawangan", "Sukmajaya", "Tapos"
                ],
                "3578": ["Bulak", "Kenjeran", "Krembangan", "Pabean Cantian", "Semampir", "Simokerto", "Tambaksari",
                    "Tandes", "Wiyung", "Wonokromo", "Wonocolo", "Genteng", "Jambangan", "Karang Pilang", "Rungkut",
                    "Sawahan", "Tegalsari"
                ],
                "3671": ["Batuceper", "Benda", "Ciledug", "Cipondoh", "Jatiuwung", "Karang Tengah", "Karawaci",
                    "Larangan", "Neglasari", "Periuk", "Pinang", "Tangerang"
                ],
                "3674": ["Ciputat", "Ciputat Timur", "Pamulang", "Pondok Aren", "Serpong", "Serpong Utara", "Setu"],
                "5171": ["Denpasar Barat", "Denpasar Selatan", "Denpasar Timur", "Denpasar Utara"],
                "7371": ["Biringkanaya", "Bontoala", "Makassar", "Mamajang", "Manggala", "Mariso", "Panakkukang",
                    "Rappocini", "Tallo", "Tamalanrea", "Tamalate", "Ujung Pandang", "Ujung Tanah", "Wajo"
                ],
                "3374": ["Banyumanik", "Candisari", "Gajahmungkur", "Gayamsari", "Genuk", "Gunungpati", "Mijen",
                    "Ngaliyan", "Pedurungan", "Semarang Barat", "Semarang Selatan", "Semarang Tengah",
                    "Semarang Timur", "Semarang Utara", "Tembalang", "Tugu"
                ],
                "3573": ["Blimbing", "Kedungkandang", "Klojen", "Lowokwaru", "Sukun"],
                "6471": ["Balikpapan Barat", "Balikpapan Kota", "Balikpapan Selatan", "Balikpapan Tengah",
                    "Balikpapan Timur", "Balikpapan Utara"
                ],
                "6472": ["Loa Janan Ilir", "Samarinda Ilir", "Samarinda Kota", "Samarinda Seberang", "Samarinda Ulu",
                    "Samarinda Utara", "Sambutan", "Sungai Kunjang", "Sungai Pinang", "Palaran"
                ]
            }
        };

        // ============================================================
        // SESSION GUARD (1:1 from GAS js/outsourceApp.html)
        // ============================================================
        var SESSION_KEY = 'msi_outsource_submitted';

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
                    'Data Anda telah terdaftar pada sesi ini.<br><br>Hubungi HR apabila ada pertanyaan.';
            }
        }
        if (isAlreadySubmitted()) {
            document.addEventListener('DOMContentLoaded', showAlreadySubmittedPage);
        }

        // ============================================================
        // FORM SECTIONS CONFIG (1:1 from GAS FORM_SECTIONS outsourceApp)
        // ============================================================
        var FORM_SECTIONS = [{
                id: 'sectionPersonal',
                badgeId: 'secBadgePersonal',
                fields: ['nik', 'full_name', 'birth_date', 'birth_place', 'gender', 'religion', 'blood_type',
                    'marital_status'
                ]
            },
            {
                id: 'sectionContact',
                badgeId: 'secBadgeContact',
                fields: ['email', 'working_email', 'phone', 'province', 'city', 'address', 'address_residential'],
                district: true
            },
            {
                id: 'sectionEmployment',
                badgeId: 'secBadgeEmployment',
                fields: ['branch_name', 'outsource_vendor', 'division', 'department', 'area_kerja', 'cost_center',
                    'lokasi_kerja', 'position', 'job_level', 'employee_status', 'join_date', 'contract_end_date',
                    'direct_superior', 'indirect_superior'
                ]
            },
            {
                id: 'sectionBank',
                badgeId: 'secBadgeBank',
                fields: ['bank_account', 'bank_account_holder', 'npwp', 'ptkp_status', 'bpjs_ketenagakerjaan',
                    'bpjs_kesehatan'
                ]
            },
        ];

        var districtInput, districtLoading, districtManualWrap, districtManualInput;
        var manualBirthDateChanged = false;
        var isSubmitting = false;

        function isDistrictValid() {
            if (districtManualWrap && districtManualWrap.style.display !== 'none') {
                return (districtManualInput.value || '').trim() !== '';
            }
            return districtInput && !districtInput.disabled && (districtInput.value || '').trim() !== '';
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

        var agreementCheckbox, agreementError, submitBtn, submitSpinner, submitText, loadingOverlay;
        var progressFill, progressCount, progressMessage;

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
        // BIRTH DATE — auto-slash DD/MM/YYYY (1:1 from GAS outsourceApp)
        // ============================================================
        function attachBirthDateListeners(birthDateEl, ageEl) {
            birthDateEl.addEventListener('input', function() {
                manualBirthDateChanged = true;
                var val = this.value.replace(/[^0-9]/g, '');
                var f = '';
                if (val.length > 0) {
                    f = val.substring(0, 2);
                    if (val.length > 2) f += '/' + val.substring(2, 4);
                    if (val.length > 4) f += '/' + val.substring(4, 8);
                }
                this.value = f;
                calculateAge(f, ageEl);
                if (f.length === 10) validateBirthDate(birthDateEl);
                else this.classList.remove('is-valid', 'is-invalid');
                updateProgress();
            });
            birthDateEl.addEventListener('blur', function() {
                if (this.value.trim().length > 0) validateBirthDate(birthDateEl);
                updateProgress();
            });
        }

        function validateBirthDate(el) {
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
                setInvalid('Format tidak valid. Gunakan DD/MM/YYYY.');
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
                setInvalid('Tahun lahir tidak valid.');
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
                setInvalid('Usia minimal 17 tahun.');
                return false;
            }
            setValid();
            return true;
        }

        function calculateAge(bdStr, ageEl) {
            if (!bdStr || bdStr.length < 10) {
                ageEl.value = '';
                return;
            }
            var p = bdStr.split('/');
            if (p.length !== 3) return;
            var day = parseInt(p[0], 10),
                month = parseInt(p[1], 10),
                year = parseInt(p[2], 10);
            if (!day || !month || !year) return;
            var bd = new Date(year, month - 1, day),
                today = new Date();
            var age = today.getFullYear() - bd.getFullYear(),
                m = today.getMonth() - bd.getMonth();
            if (m < 0 || (m === 0 && today.getDate() < bd.getDate())) age--;
            ageEl.value = (age >= 0 && age <= 120) ? age : '';
        }

        // ============================================================
        // NIK PARSER + AUTO-FILL (1:1 from GAS outsourceApp)
        // ============================================================
        function parseNIK(nik) {
            if (!nik || nik.length !== 16 || !/^[0-9]{16}$/.test(nik)) return null;
            var day = parseInt(nik.substring(6, 8), 10),
                gender = (day > 40) ? 'Perempuan' : 'Laki-laki';
            if (day > 40) day -= 40;
            var month = parseInt(nik.substring(8, 10), 10),
                year = parseInt(nik.substring(10, 12), 10);
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

        function processNIK(nik, nikFeedback, genderEl, birthDateEl, ageEl, provinceEl, cityEl) {
            var r = parseNIK(nik);
            if (!r) {
                showNIKFeedback(nikFeedback, false);
                return;
            }
            if (!manualBirthDateChanged) {
                birthDateEl.value = r.birthDate.formatted;
                calculateAge(r.birthDate.formatted, ageEl);
                validateBirthDate(birthDateEl);
            }
            genderEl.value = r.gender;
            if (REGIONS.provinces[r.provinceCode]) {
                provinceEl.value = r.provinceCode;
                populateCities(r.provinceCode, provinceEl, cityEl);
                if (REGIONS.cities[r.cityCode]) {
                    cityEl.value = r.cityCode;
                    loadDistricts(r.cityCode);
                    // BUG FIX #2: sync kotaNama from NIK autofill
                    var kotaNamaInput = document.getElementById('kotaNama');
                    if (kotaNamaInput) {
                        kotaNamaInput.value = REGIONS.cities[r.cityCode];
                    }
                }
            }
            showNIKFeedback(nikFeedback, true, r);
            updateProgress();
        }

        function showNIKFeedback(el, success, r) {
            if (success) {
                var det = ['Tanggal Lahir: ' + r.birthDate.formatted, 'Jenis Kelamin: ' + r.gender];
                if (REGIONS.provinces[r.provinceCode]) det.push('Provinsi: ' + REGIONS.provinces[r.provinceCode]);
                if (REGIONS.cities[r.cityCode]) det.push('Kabupaten/Kota: ' + REGIONS.cities[r.cityCode]);
                el.innerHTML =
                    '<div class="alert alert-success p-2 mb-0" style="font-size:12px"><i class="bi bi-check-circle-fill me-1"></i> <strong>Data NIK Terdeteksi</strong><ul class="mb-0 mt-1 ps-3">' +
                    det.map(function(d) {
                        return '<li>' + d + '</li>';
                    }).join('') + '</ul></div>';
            } else {
                el.innerHTML =
                    '<div class="alert alert-warning p-2 mb-0" style="font-size:12px"><i class="bi bi-exclamation-triangle-fill me-1"></i> <strong>NIK tidak valid.</strong></div>';
            }
        }

        // ============================================================
        // REGION DROPDOWNS
        // ============================================================
        function populateProvinces(provinceEl) {
            var html = '<option value="">-- Pilih Provinsi --</option>';
            Object.keys(REGIONS.provinces).sort().forEach(function(code) {
                html += '<option value="' + code + '">' + REGIONS.provinces[code] + '</option>';
            });
            provinceEl.innerHTML = html;
        }

        function populateCities(provinceCode, provinceEl, cityEl) {
            cityEl.innerHTML = '<option value="">-- Pilih Kota/Kabupaten --</option>';
            resetDistrict();
            if (!provinceCode) return;
            Object.keys(REGIONS.cities).filter(function(c) {
                    return c.substring(0, 2) === provinceCode;
                }).sort()
                .forEach(function(code) {
                    cityEl.innerHTML += '<option value="' + code + '">' + REGIONS.cities[code] + '</option>';
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

        // ============================================================
        // COPY KTP ADDRESS
        // ============================================================
        // ============================================================
        // FIELD VALIDATION HELPER
        // ============================================================
        function validateField(el) {
            if (!el) return;
            if (el.id === 'birth_date') {
                validateBirthDate(el);
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
        // INIT
        // ============================================================
        document.addEventListener('DOMContentLoaded', function() {
            agreementCheckbox = document.getElementById('agreement');
            agreementError = document.getElementById('agreementError');
            submitBtn = document.getElementById('submitBtn');
            submitSpinner = document.getElementById('submitSpinner');
            submitText = document.getElementById('submitText');
            loadingOverlay = document.getElementById('loadingOverlay');
            progressFill = document.getElementById('progressFill');
            progressCount = document.getElementById('progressCount');
            progressMessage = document.getElementById('progressMessage');
            districtInput = document.getElementById('district');
            districtLoading = document.getElementById('districtLoading');
            districtManualWrap = document.getElementById('districtManualWrap');
            districtManualInput = document.getElementById('districtManual');

            var nikEl = document.getElementById('nik');
            var nikFeedback = document.getElementById('nikFeedback');
            var birthDateEl = document.getElementById('birth_date');
            var ageEl = document.getElementById('age');
            var genderEl = document.getElementById('gender');
            var provinceEl = document.getElementById('province');
            var cityEl = document.getElementById('city');
            var phoneEl = document.getElementById('phone');
            var emailEl = document.getElementById('email');
            var emailValidation = document.getElementById('emailValidation');
            var form = document.getElementById('formOutsource');

            populateProvinces(provinceEl);
            attachBirthDateListeners(birthDateEl, ageEl);

            // NIK input
            nikEl.addEventListener('input', function() {
                var nik = this.value.replace(/[^0-9]/g, '').substring(0, 16);
                this.value = nik;
                if (nik.length === 16) processNIK(nik, nikFeedback, genderEl, birthDateEl, ageEl,
                    provinceEl, cityEl);
                else nikFeedback.innerHTML = nik.length > 0 ?
                    '<span class="text-muted" style="font-size:12px">Ketik 16 digit NIK... (' + nik.length +
                    '/16)</span>' : '';
                validateField(this);
                updateProgress();
            });

            // Province / city / district
            provinceEl.addEventListener('change', function() {
                populateCities(this.value, provinceEl, cityEl);
                // Clear kotaNama when province changes — city is reset to blank,
                // so any previously stored name would be stale.
                var kotaNamaInput = document.getElementById('kotaNama');
                if (kotaNamaInput) kotaNamaInput.value = '';
                validateField(this);
                updateProgress();
            });
            cityEl.addEventListener('change', function() {
                validateField(this);
                loadDistricts(this.value);
                // Keep kotaNama in sync with the human-readable city name.
                var kotaNamaInput = document.getElementById('kotaNama');
                if (kotaNamaInput) {
                    kotaNamaInput.value = this.value ? (REGIONS.cities[this.value] || '') : '';
                }
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
            });

            // Phone
            phoneEl.addEventListener('input', function() {
                this.value = this.value.replace(/[^0-9]/g, '').substring(0, 13);
                validateField(this);
                updateProgress();
            });

            // Email typo
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
                if (!email) return;
                if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                    emailValidation.style.cssText =
                        'display:flex;font-size:12px;color:#991b1b;align-items:center;gap:4px;';
                    emailValidation.innerHTML =
                        '<i class="bi bi-x-circle-fill"></i> Format email tidak valid';
                    return;
                }
                var domain = email.split('@')[1];
                if (commonTypos[domain]) {
                    emailValidation.style.cssText =
                        'display:flex;font-size:12px;color:#8a6100;align-items:center;gap:4px;';
                    emailValidation.innerHTML =
                        '<i class="bi bi-lightbulb-fill"></i> Apakah yang Anda maksud: <strong>' + email
                        .split('@')[0] + '@' + commonTypos[domain] + '</strong>?';
                } else {
                    emailValidation.style.cssText =
                        'display:flex;font-size:12px;color:#166534;align-items:center;gap:4px;';
                    emailValidation.innerHTML =
                        '<i class="bi bi-check-circle-fill"></i> Format email valid';
                }
            });
            emailEl.addEventListener('input', function() {
                emailValidation.style.display = 'none';
                validateField(this);
                updateProgress();
            });

            // Agreement
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

            // Real-time validation
            var inputs = form.querySelectorAll('input, select, textarea');
            inputs.forEach(function(input) {
                if (input.id === 'birth_date' || input.id === 'agreement') return;
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

            // Form submit
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
                agreementError.style.display = 'none';
                var bdValid = validateBirthDate(birthDateEl);
                if (!form.checkValidity() || !bdValid) {
                    e.preventDefault();
                    form.classList.add('was-validated');
                    if (!bdValid) birthDateEl.scrollIntoView({
                        behavior: 'smooth',
                        block: 'center'
                    });
                    return;
                }
                // Final authoritative sync: ensure kota_nama always reflects the
                // current city dropdown value regardless of prior event timing.
                var kotaNamaInput = document.getElementById('kotaNama');
                if (kotaNamaInput && cityEl) {
                    kotaNamaInput.value = cityEl.value ? (REGIONS.cities[cityEl.value] || '') : '';
                }
                isSubmitting = true;
                submitBtn.disabled = true;
                submitSpinner.classList.remove('d-none');
                submitText.textContent = 'Mengirim...';
                loadingOverlay.classList.add('is-active');
            });

            updateProgress();
        });
    </script>
@endsection
@endsection
