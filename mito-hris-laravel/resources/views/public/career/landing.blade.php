@extends('layouts.public')

@section('title', 'Karir dan Rekrutmen - MITO Career Portal')
@section('description',
    'Temukan informasi proses rekrutmen dan peluang karir melalui MITO HRIS Career Portal.
    Pendaftaran dilakukan secara online tanpa biaya.')
@section('structuredData',
    json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'Organization',
    'name' => 'MITO Group',
    'url' => config('seo.canonical_base_url'),
    'logo' => asset('assets/mito.png'),
    ]))

@section('content')
    <div class="career-landing-page">

        <!-- HERO (Red MITO Theme 1:1 GAS) -->
        <header class="hero-section">
            <div class="container">
                <div class="row align-items-center">
                    <div class="col-lg-8 col-md-7">
                        <div class="hero-org">MITO Group</div>
                        <div class="hero-dept">Human Resources Department</div>
                        <h1 class="hero-title">Recruitment &amp; Career Portal</h1>
                        <p class="hero-subtitle">
                            Wujudkan potensi dan kembangkan karir profesional Anda bersama perusahaan teknologi elektronik
                            terkemuka di Indonesia.
                        </p>
                    </div>
                    <div class="col-lg-4 col-md-5 d-none d-md-flex justify-content-end align-items-center">
                        <img id="heroAvatarImg" src="{{ asset('assets/mito.png') }}" alt="Logo MITO untuk portal karir"
                            class="hero-avatar-img" width="150" height="150" fetchpriority="high">
                    </div>
                </div>
            </div>
        </header>

        <div class="container py-5" style="max-width:960px;">

            <!-- Alert Message -->
            @if (session('error'))
                <div class="alert alert-danger alert-dismissible fade show p-3 mb-4 rounded-3 d-flex align-items-center gap-2"
                    role="alert">
                    <i class="bi bi-exclamation-triangle-fill fs-5 flex-shrink-0"></i>
                    <div>{{ session('error') }}</div>
                    <button type="button" class="btn-close btn-sm p-3" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <!-- 4 Langkah Rekrutmen -->
            <section class="mb-5" aria-labelledby="recruitment-process-title">
                <div class="text-center mb-4">
                    <h2 id="recruitment-process-title" class="h5 fw-bold text-navy mb-1"><i
                            class="bi bi-diagram-3 me-2 text-danger"></i>Tahapan Proses
                        Rekrutmen</h2>
                    <small class="text-muted">Proses seleksi transparan, terstruktur, dan tanpa dipungut biaya
                        apapun</small>
                </div>
                <div class="row g-3">
                    <div class="col-6 col-md-3">
                        <div class="p-3 bg-white rounded-4 border text-center h-100 shadow-sm">
                            <div class="mx-auto mb-2 d-flex align-items-center justify-content-center rounded-circle"
                                style="width:44px;height:44px;background:rgba(235,28,36,0.1);color:#eb1c24;font-weight:800;">
                                1</div>
                            <div class="fw-bold text-navy" style="font-size:14px">Pendaftaran Online</div>
                            <small class="text-muted d-block" style="font-size:11.5px">Pengisian data diri &amp;
                                kualifikasi</small>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="p-3 bg-white rounded-4 border text-center h-100 shadow-sm">
                            <div class="mx-auto mb-2 d-flex align-items-center justify-content-center rounded-circle"
                                style="width:44px;height:44px;background:rgba(235,28,36,0.1);color:#eb1c24;font-weight:800;">
                                2</div>
                            <div class="fw-bold text-navy" style="font-size:14px">Screening &amp; Review</div>
                            <small class="text-muted d-block" style="font-size:11.5px">Verifikasi berkas oleh tim HR</small>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="p-3 bg-white rounded-4 border text-center h-100 shadow-sm">
                            <div class="mx-auto mb-2 d-flex align-items-center justify-content-center rounded-circle"
                                style="width:44px;height:44px;background:rgba(235,28,36,0.1);color:#eb1c24;font-weight:800;">
                                3</div>
                            <div class="fw-bold text-navy" style="font-size:14px">Wawancara &amp; Tes</div>
                            <small class="text-muted d-block" style="font-size:11.5px">Interview HR dan User Dept</small>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="p-3 bg-white rounded-4 border text-center h-100 shadow-sm">
                            <div class="mx-auto mb-2 d-flex align-items-center justify-content-center rounded-circle"
                                style="width:44px;height:44px;background:rgba(235,28,36,0.1);color:#eb1c24;font-weight:800;">
                                4</div>
                            <div class="fw-bold text-navy" style="font-size:14px">Offering &amp; PKWT</div>
                            <small class="text-muted d-block" style="font-size:11.5px">Penerimaan &amp; Onboarding</small>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Syarat & Ketentuan Pendaftaran (Wajib Checklist) -->
            <section class="card border-0 shadow-sm rounded-4 mb-4" id="syaratKetentuan"
                style="border:1px solid #e2e8f0!important;">
                <div class="card-header bg-white p-4 pb-3 border-0">
                    <div class="d-flex align-items-center gap-2">
                        <div class="py-2 px-3 rounded-3 text-white" style="background:#eb1c24"><i
                                class="bi bi-shield-check fs-5"></i></div>
                        <div>
                            <h5 class="fw-bold mb-0 text-navy">Syarat &amp; Ketentuan Pendaftaran</h5>
                            <small class="text-muted">Harap membaca dan menyetujui seluruh ketentuan sebelum mengisi
                                formulir</small>
                        </div>
                    </div>
                </div>
                <div class="card-body p-4 pt-0 mt-4">
                    <div class="p-3 rounded-3 mb-4"
                        style="background:#f8fafc;border:1px solid #e2e8f0;font-size:13px;line-height:1.7;color:#334155;">
                        <ol class="mb-0 ps-3">
                            <li class="mb-2"><strong>Kebenaran Data:</strong> Seluruh data identitas (NIK, Nama Lengkap,
                                Tanggal Lahir, Riwayat Pendidikan, Pengalaman Kerja) yang diisikan harus sesuai dengan
                                dokumen kependudukan yang sah.</li>
                            <li class="mb-2"><strong>Kerahasiaan &amp; Privasi:</strong> Data yang Anda berikan hanya akan
                                digunakan secara internal oleh Tim Human Resources MITO Group untuk keperluan seleksi dan
                                tidak akan disebarluaskan kepada pihak ketiga.</li>
                            <li class="mb-2"><strong>Proses Seleksi Tanpa Biaya:</strong> Proses rekrutmen di MITO Group
                                <strong>TIDAK MEMUNGUT BIAYA APAPUN</strong>. Waspadai segala bentuk penipuan yang
                                mengatasnamakan manajemen MITO Group.
                            </li>
                            <li><strong>Hak Verifikasi Perusahaan:</strong> Perusahaan berhak mendiskualifikasi pelamar atau
                                membatalkan perjanjian kerja di kemudian hari apabila ditemukan data atau keterangan palsu.
                            </li>
                        </ol>
                    </div>

                    <!-- FORM CONSENT CHECKLIST -->
                    <form action="{{ route('public.career.consent') }}" method="POST" id="formConsent">
                        @csrf
                        <input type="hidden" name="consent_timestamp" id="consentTimestamp">
                        <input type="hidden" name="consent_device" id="consentDevice">
                        <input type="hidden" name="consent_latitude" id="consentLatitude">
                        <input type="hidden" name="consent_longitude" id="consentLongitude">
                        <input type="hidden" name="consent_location" id="consentLocation" value="pending">
                        <div class="p-3 rounded-3 mb-4" style="background:#fff5f5;border:1px solid #fed7d7;">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="consentCheckbox" name="consent"
                                    value="1" style="cursor:pointer;width:20px;height:20px;margin-top:2px;">
                                <label class="form-check-label ms-2 fw-bold text-navy" for="consentCheckbox"
                                    style="cursor:pointer;font-size:13.5px;">
                                    Saya telah membaca, memahami, dan menyetujui seluruh syarat &amp; ketentuan pendaftaran
                                    rekrutmen di atas. <span class="text-danger">*</span>
                                </label>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end align-items-center flex-wrap gap-3">

                            <button type="submit" class="btn btn-submit" id="btnProceedApply" disabled>
                                <i class="bi bi-arrow-right-circle me-1"></i> Lanjutkan ke Formulir Pendaftaran
                            </button>
                        </div>
                    </form>
                </div>
            </section>

        </div>
    </div>

    <script nonce="{{ request()->attributes->get('csp_nonce') }}">
        (function() {
            var form = document.getElementById('formConsent');
            var checkbox = document.getElementById('consentCheckbox');
            var button = document.getElementById('btnProceedApply');
            var timestamp = document.getElementById('consentTimestamp');
            var device = document.getElementById('consentDevice');
            var latitude = document.getElementById('consentLatitude');
            var longitude = document.getElementById('consentLongitude');
            var locationField = document.getElementById('consentLocation');
            var geoRequested = false;

            function setDevice() {
                if (!device) return;
                try {
                    device.value = (navigator.userAgentData && navigator.userAgentData.platform)
                        ? navigator.userAgentData.platform
                        : (navigator.platform || 'Unknown');
                } catch (e) {
                    device.value = navigator.platform || 'Unknown';
                }
            }

            function syncProceedButton() {
                if (!checkbox || !button) return;
                button.disabled = !checkbox.checked;
            }

            function markLocationUnavailable() {
                if (locationField && locationField.value !== 'granted') {
                    locationField.value = 'unavailable';
                }
            }

            function requestLocationOptional() {
                if (geoRequested) return;
                geoRequested = true;

                if (!navigator.geolocation || typeof navigator.geolocation.getCurrentPosition !== 'function') {
                    markLocationUnavailable();
                    return;
                }

                navigator.geolocation.getCurrentPosition(function(position) {
                    if (latitude) latitude.value = position.coords.latitude;
                    if (longitude) longitude.value = position.coords.longitude;
                    if (locationField) locationField.value = 'granted';
                }, function() {
                    markLocationUnavailable();
                }, {
                    enableHighAccuracy: false,
                    timeout: 4000,
                    maximumAge: 300000
                });
            }

            if (timestamp) timestamp.value = new Date().toISOString();
            setDevice();
            syncProceedButton();

            if (checkbox) {
                ['change', 'input', 'click'].forEach(function(eventName) {
                    checkbox.addEventListener(eventName, function() {
                        syncProceedButton();
                        if (checkbox.checked) {
                            requestLocationOptional();
                        }
                    });
                });
            }

            if (form) {
                form.addEventListener('submit', function(e) {
                    if (!checkbox || !checkbox.checked) {
                        e.preventDefault();
                        syncProceedButton();
                        return;
                    }
                    if (timestamp) timestamp.value = new Date().toISOString();
                    setDevice();
                    if (!geoRequested) {
                        markLocationUnavailable();
                    }
                    if (button) button.disabled = true;
                    if (typeof window.showPublicLoader === 'function') {
                        window.showPublicLoader('Memuat formulir');
                    }
                });
            }
        }());
    </script>
@endsection
