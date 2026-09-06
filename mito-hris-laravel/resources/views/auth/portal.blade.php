<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @include('components.seo', [
        'title' => 'Portal HRIS - MITO Group HRIS',
        'description' =>
            'Portal layanan HRIS untuk rekrutmen, data karyawan, evaluasi masa percobaan, dan kebutuhan tenaga kerja MITO.',
        'robots' => 'index,follow',
        'canonical' => route('hris.domain.root'),
    ])
    <link rel="icon" type="image/png" href="{{ asset('assets/logo-favicon.png') }}">

    <!-- Bootstrap 5, Bootstrap Icons, Inter Font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <script nonce="{{ request()->attributes->get('csp_nonce') }}">
        (function() {
            const savedTheme = localStorage.getItem('mito_theme') || 'light';
            document.documentElement.setAttribute('data-theme', savedTheme);
            document.documentElement.setAttribute('data-bs-theme', savedTheme);
        })();
    </script>

    <style>
        :root {
            --mito-red: #eb1c24;
            --mito-red-hover: #d3151c;
            --mito-bg: #f8fafc;
            --mito-surface: #ffffff;
            --mito-border: #e2e8f0;
            --mito-text: #334155;
            --mito-heading: #0f172a;
            --mito-muted: #64748b;
        }

        :root[data-theme="dark"] {
            --mito-bg: #0f172a;
            --mito-surface: #1e293b;
            --mito-border: #334155;
            --mito-text: #cbd5e1;
            --mito-heading: #f8fafc;
            --mito-muted: #94a3b8;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', 'Segoe UI', -apple-system, sans-serif;
            background-color: var(--mito-bg);
            color: var(--mito-text);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            line-height: 1.5;
        }

        /* MITO Loading Overlay */
        .portal-loader {
            position: fixed;
            inset: 0;
            z-index: 99999;
            display: grid;
            place-items: center;
            background-color: var(--mito-surface);
            opacity: 1;
            visibility: visible;
            transition: opacity 0.42s cubic-bezier(0.4, 0, 0.2, 1), visibility 0s linear 0.42s;
        }

        .portal-loader.fade-out {
            opacity: 0;
            visibility: hidden;
            pointer-events: none;
        }

        .portal-loader-content {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 0.7rem;
            text-align: center;
            animation: portalLoaderEnter 0.65s cubic-bezier(0.22, 1, 0.36, 1) both;
        }

        .portal-loader-logo {
            display: block;
            width: min(180px, 52vw);
            height: auto;
        }

        .portal-loader-dots {
            display: flex;
            align-items: center;
            gap: 0.45rem;
        }

        .portal-loader-dots span {
            display: block;
            width: 0.56rem;
            height: 0.56rem;
            border-radius: 50%;
            background: var(--mito-red);
            animation: portalLoaderDot 1.15s cubic-bezier(0.4, 0, 0.2, 1) infinite;
            will-change: transform, opacity;
        }

        .portal-loader-dots span:nth-child(2) {
            animation-delay: 0.14s;
        }

        .portal-loader-dots span:nth-child(3) {
            animation-delay: 0.28s;
        }

        @keyframes portalLoaderEnter {
            from {
                opacity: 0;
                transform: translateY(0.5rem);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes portalLoaderDot {

            0%,
            70%,
            100% {
                opacity: 0.3;
                transform: translate3d(0, 0, 0) scale(0.82);
            }

            35% {
                opacity: 1;
                transform: translate3d(0, -0.38rem, 0) scale(1);
            }
        }

        .loader-text {
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--mito-muted);
            letter-spacing: 0.1px;
            margin-top: 0.1rem;
        }

        /* Solid Color Buttons */
        .btn-mito-primary {
            background-color: var(--mito-red);
            color: #ffffff;
            font-weight: 600;
            font-size: 0.9rem;
            padding: 10px 20px;
            border-radius: 8px;
            border: 1px solid var(--mito-red);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: background-color 0.15s ease;
        }

        .btn-mito-primary:hover,
        .btn-mito-primary:focus {
            background-color: var(--mito-red-hover);
            border-color: var(--mito-red-hover);
            color: #ffffff;
        }

        /* Hero Section */
        .portal-hero {
            background-color: var(--mito-surface);
            border-bottom: 1px solid var(--mito-border);
            padding: 56px 0 48px;
        }

        .hero-title {
            font-size: 1.85rem;
            font-weight: 700;
            color: var(--mito-heading);
            margin-bottom: 12px;
            line-height: 1.25;
            letter-spacing: -0.02em;
        }

        .hero-desc {
            font-size: 0.95rem;
            color: var(--mito-muted);
            max-width: 640px;
            margin-bottom: 24px;
            line-height: 1.6;
        }

        /* User Active Session Box */
        .session-card {
            background-color: var(--mito-bg);
            border: 1px solid var(--mito-border);
            border-radius: 8px;
            padding: 16px 20px;
            margin-bottom: 20px;
            max-width: 580px;
        }

        /* Main Content Section */
        .portal-content {
            padding: 44px 0 52px;
        }

        .section-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--mito-heading);
            margin-bottom: 6px;
        }

        .section-desc {
            font-size: 0.88rem;
            color: var(--mito-muted);
            margin-bottom: 24px;
        }

        /* Functional Module Cards */
        .module-card {
            background-color: var(--mito-surface);
            border: 1px solid var(--mito-border);
            border-radius: 10px;
            padding: 22px 20px;
            height: 100%;
            display: flex;
            flex-direction: column;
            transition: border-color 0.15s ease;
        }

        .module-card:hover {
            border-color: #cbd5e1;
        }

        :root[data-theme="dark"] .module-card:hover {
            border-color: #475569;
        }

        .module-icon {
            width: 42px;
            height: 42px;
            border-radius: 8px;
            background-color: #fef2f2;
            border: 1px solid #fee2e2;
            color: var(--mito-red);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            margin-bottom: 16px;
        }

        :root[data-theme="dark"] .module-icon {
            background-color: rgba(235, 28, 36, 0.12);
            border-color: rgba(235, 28, 36, 0.25);
            color: #fca5a5;
        }

        .module-title {
            font-size: 1rem;
            font-weight: 700;
            color: var(--mito-heading);
            margin-bottom: 8px;
        }

        .module-desc {
            font-size: 0.85rem;
            color: var(--mito-muted);
            line-height: 1.5;
            margin-bottom: 0;
        }

        /* Footer */
        .portal-footer {
            margin-top: auto;
            padding: 16px 24px;
            text-align: center;
            font-size: 11px;
            color: #9ca3af;
            background: transparent;
        }

        .portal-footer-badge {
            display: inline-block;
            background: #e5e7eb;
            color: #9ca3af;
            padding: 1px 8px;
            border-radius: 20px;
            font-size: 10px;
            font-weight: 600;
            margin-left: 6px;
        }

        @media (max-width: 767.98px) {
            .portal-hero {
                padding: 36px 0 30px;
            }

            .hero-title {
                font-size: 1.6rem;
            }
        }
    </style>
</head>

<body>

    <!-- MITO Loading Overlay (Initial Load & Navigation) -->
    <div class="portal-loader" id="portalLoader">
        <div class="portal-loader-content">
            <img class="portal-loader-logo" src="{{ asset('assets/mito-red-load.png') }}" alt="MITO Logo"
                onerror="this.style.display='none';this.parentElement.innerHTML='<span class=\'fw-bold text-danger fs-4\'>MITO</span>'">
            <div class="portal-loader-dots" aria-hidden="true"><span></span><span></span><span></span></div>
            <div class="loader-text" id="loaderText">Memuat Portal HRIS...</div>
        </div>
    </div>

    <!-- Hero Section -->
    <section class="portal-hero">
        <div class="container">
            <div class="row">
                <div class="col-lg-9 col-xl-8">

                    <h1 class="hero-title">
                        Portal Layanan Internal HRIS
                    </h1>

                    <p class="hero-desc">
                        Sistem informasi SDM terpadu untuk staf HR dan pimpinan unit kerja dalam mengelola proses
                        rekrutmen, administrasi data karyawan, pengajuan tenaga kerja (MPR), dan evaluasi masa
                        percobaan.
                    </p>

                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <a href="{{ route('login') }}" class="btn-mito-primary" id="btnPortalLogin" data-portal-redirect
                            data-portal-msg="Membuka Halaman Login...">
                            <i class="bi bi-box-arrow-in-right"></i>
                            Get Started
                        </a>
                        <a href="{{ request()->getScheme() . '://' . config('hris.domains.assets') }}"
                            class="btn btn-outline-danger" data-portal-redirect data-portal-msg="Membuka Portal Aset...">
                            <i class="bi bi-box-seam"></i>
                            Portal Aset
                        </a>
                        <a href="{{ request()->getScheme() . '://' . config('hris.domains.certificates') }}"
                            class="btn btn-outline-danger" data-portal-redirect data-portal-msg="Membuka Portal Sertifikasi...">
                            <i class="bi bi-award"></i>
                            Portal Sertifikasi
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Modules Section -->
    <main class="portal-content">
        <div class="container">
            <h2 class="section-title">Modul Operasional HRIS</h2>
            <p class="section-desc">Fungsi utama sistem informasi SDM yang dapat diakses sesuai dengan wewenang akun
                Anda.</p>

            <div class="row g-3">
                <!-- Module 1: ATS Recruitment -->
                <div class="col-md-6 col-lg-4">
                    <div class="module-card">
                        <div class="module-icon">
                            <i class="bi bi-person-lines-fill"></i>
                        </div>
                        <h3 class="module-title">Rekrutmen & Seleksi</h3>
                        <p class="module-desc">
                            Pemrosesan berkas pelamar kerja, penjadwalan interview, surat penawaran, hingga onboarding
                            kandidat diterima.
                        </p>
                    </div>
                </div>

                <!-- Module 2: Employee Master Data -->
                <div class="col-md-6 col-lg-4">
                    <div class="module-card">
                        <div class="module-icon">
                            <i class="bi bi-people-fill"></i>
                        </div>
                        <h3 class="module-title">Data Karyawan & Outsource</h3>
                        <p class="module-desc">
                            Administrasi data kepegawaian, status kerja, mutasi, dan pencatatan riwayat penempatan
                            cabang kerja.
                        </p>
                    </div>
                </div>

                <!-- Module 3: Probation Evaluation -->
                <div class="col-md-6 col-lg-4">
                    <div class="module-card">
                        <div class="module-icon">
                            <i class="bi bi-clipboard-check-fill"></i>
                        </div>
                        <h3 class="module-title">Evaluasi Masa Percobaan</h3>
                        <p class="module-desc">
                            Penilaian berkala performa karyawan masa percobaan dan rekomendasi pengangkatan atau
                            kelulusan kerja.
                        </p>
                    </div>
                </div>

                <!-- Module 4: Manpower Request (MPR) -->
                <div class="col-md-6 col-lg-4">
                    <div class="module-card">
                        <div class="module-icon">
                            <i class="bi bi-file-earmark-plus-fill"></i>
                        </div>
                        <h3 class="module-title">Manpower Request (MPR)</h3>
                        <p class="module-desc">
                            Pengajuan kebutuhan penambahan tenaga kerja baru dari unit kerja terkait dengan alur
                            verifikasi resmi.
                        </p>
                    </div>
                </div>

                <!-- Module 5: Audit Trail & Security -->
                <div class="col-md-6 col-lg-4">
                    <div class="module-card">
                        <div class="module-icon">
                            <i class="bi bi-shield-check"></i>
                        </div>
                        <h3 class="module-title">Audit Log & Keamanan Akses</h3>
                        <p class="module-desc">
                            Pencatatan riwayat aktivitas operasional untuk menjaga integritas data sistem dan otorisasi
                            pengguna.
                        </p>
                    </div>
                </div>

                <!-- Module 6: Export & Reporting -->
                <div class="col-md-6 col-lg-4">
                    <div class="module-card">
                        <div class="module-icon">
                            <i class="bi bi-file-earmark-text-fill"></i>
                        </div>
                        <h3 class="module-title">Laporan & Rekapitulasi Data</h3>
                        <p class="module-desc">
                            Pengelolaan rekapan data SDM terstruktur untuk kebutuhan dokumentasi dan pelaporan manajemen
                            internal.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Access Information Notice -->

        </div>

        <!-- SECTION: ASSET MANAGEMENT -->
        <div class="container mt-4">
            <h2 class="section-title">Asset Management</h2>
            <p class="section-desc">Pengelolaan aset perusahaan untuk mendukung operasional dan inventarisasi aset tetap.</p>

            <div class="row g-3">
                <!-- Asset 1: Building -->
                <div class="col-md-6 col-lg-3">
                    <div class="module-card">
                        <div class="module-icon">
                            <i class="bi bi-building"></i>
                        </div>
                        <h3 class="module-title">Building & Property</h3>
                        <p class="module-desc">
                            Pencatatan gedung, properti, dan fasilitas kantor beserta status kepemilikan dan lokasi penempatan.
                        </p>
                    </div>
                </div>

                <!-- Asset 2: Vehicle -->
                <div class="col-md-6 col-lg-3">
                    <div class="module-card">
                        <div class="module-icon">
                            <i class="bi bi-truck"></i>
                        </div>
                        <h3 class="module-title">Vehicle & Transport</h3>
                        <p class="module-desc">
                            Administrasi kendaraan operasional, jadwal maintenance, dan dokumentasi STNK/BPKB armada perusahaan.
                        </p>
                    </div>
                </div>

                <!-- Asset 3: Office Equipment -->
                <div class="col-md-6 col-lg-3">
                    <div class="module-card">
                        <div class="module-icon">
                            <i class="bi bi-briefcase-fill"></i>
                        </div>
                        <h3 class="module-title">Office Equipment</h3>
                        <p class="module-desc">
                            Inventarisasi peralatan kantor seperti meja, kursi, filing cabinet, dan perlengkapan kerja lainnya.
                        </p>
                    </div>
                </div>

                <!-- Asset 4: Electronics -->
                <div class="col-md-6 col-lg-3">
                    <div class="module-card">
                        <div class="module-icon">
                            <i class="bi bi-laptop"></i>
                        </div>
                        <h3 class="module-title">Electronics & IT</h3>
                        <p class="module-desc">
                            Manajemen aset elektronik seperti komputer, printer, server, dan perangkat IT operasional lainnya.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- SECTION: SERTIFIKASI & COMPLIANCE -->
        <div class="container mt-4">
            <h2 class="section-title">Sertifikasi & Compliance</h2>
            <p class="section-desc">Dokumentasi standar mutu, sertifikasi operasional, dan kepatuhan regulasi perusahaan.</p>

            <div class="row g-3">
                <!-- Cert 1: SNI -->
                <div class="col-md-6 col-lg-4">
                    <div class="module-card">
                        <div class="module-icon">
                            <i class="bi bi-patch-check-fill"></i>
                        </div>
                        <h3 class="module-title">SNI (Standar Nasional Indonesia)</h3>
                        <p class="module-desc">
                            Dokumentasi sertifikasi SNI untuk produk dan layanan yang memenuhi standar mutu nasional Indonesia.
                        </p>
                    </div>
                </div>

                <!-- Cert 2: ISO -->
                <div class="col-md-6 col-lg-4">
                    <div class="module-card">
                        <div class="module-icon">
                            <i class="bi bi-award-fill"></i>
                        </div>
                        <h3 class="module-title">ISO Certification</h3>
                        <p class="module-desc">
                            Sertifikasi ISO 9001 (Quality), ISO 14001 (Environmental), dan standar internasional lainnya.
                        </p>
                    </div>
                </div>

                <!-- Cert 3: K3 -->
                <div class="col-md-6 col-lg-4">
                    <div class="module-card">
                        <div class="module-icon">
                            <i class="bi bi-shield-fill-check"></i>
                        </div>
                        <h3 class="module-title">K3 (Kesehatan & Keselamatan Kerja)</h3>
                        <p class="module-desc">
                            Dokumentasi program K3, audit keselamatan kerja, dan sertifikasi SMK3 untuk kepatuhan regulasi.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="portal-footer">
        <div class="container text-center">
            <span>{{ config('app.name', 'MITO HRIS') }}</span> &copy; {{ date('Y') }}
            <span class="portal-footer-badge">v1.0.0</span>
        </div>
    </footer>

    <!-- Bootstrap Bundle & Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        (function() {
            'use strict';

            var loader = document.getElementById('portalLoader');
            var loaderText = document.getElementById('loaderText');

            // 1. Initial Page Load: fade out loader once page is ready
            function hideInitialLoader() {
                if (loader) {
                    setTimeout(function() {
                        loader.classList.add('fade-out');
                    }, 280);
                }
            }

            if (document.readyState === 'complete' || document.readyState === 'interactive') {
                hideInitialLoader();
            } else {
                window.addEventListener('DOMContentLoaded', hideInitialLoader);
            }

            // 2. Navigation Click: show MITO spinner before moving to login/workspace
            function triggerLoaderRedirect(targetUrl, message) {
                if (loader) {
                    if (loaderText && message) {
                        loaderText.textContent = message;
                    }
                    loader.classList.remove('fade-out');
                    setTimeout(function() {
                        window.location.href = targetUrl;
                    }, 250);
                } else {
                    window.location.href = targetUrl;
                }
            }

            var redirectLinks = document.querySelectorAll('[data-portal-redirect]');
            redirectLinks.forEach(function(link) {
                link.addEventListener('click', function(e) {
                    var targetHref = this.getAttribute('href');
                    if (targetHref && targetHref !== '#') {
                        e.preventDefault();
                        var msg = this.getAttribute('data-portal-msg') || 'Membuka halaman login...';
                        triggerLoaderRedirect(targetHref, msg);
                    }
                });
            });

        })();
    </script>
</body>

</html>
