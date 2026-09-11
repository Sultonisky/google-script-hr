<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @include('components.seo', [
        'title' => 'Portal Manpower Request (MPR) - MITO Group HRIS',
        'description' =>
            'Portal Manpower Request untuk pengajuan kebutuhan tenaga kerja internal, riwayat pengajuan, dan akses requestor MITO.',
        'robots' => 'index,follow',
        'canonical' => route('mpr.auth.domain.root'),
    ])
    <link rel="icon" type="image/png" href="{{ asset('assets/logo-favicon.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

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

        .hero-avatar-img {
            width: 150px;
            height: 150px;
            object-fit: cover;
            border: 4px solid var(--mito-border);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.12);
            background: #fff;
        }

        .session-card {
            background-color: var(--mito-bg);
            border: 1px solid var(--mito-border);
            border-radius: 8px;
            padding: 16px 20px;
            margin-bottom: 20px;
            max-width: 580px;
        }

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
    <div class="portal-loader" id="portalLoader">
        <div class="portal-loader-content">
            <img class="portal-loader-logo" src="{{ asset('assets/mito-red-load.png') }}" alt="MITO Logo"
                onerror="this.style.display='none';this.parentElement.innerHTML='<span class=\'fw-bold text-danger fs-4\'>MITO</span>'">
            <div class="portal-loader-dots" aria-hidden="true"><span></span><span></span><span></span></div>
            <div class="loader-text" id="loaderText">Memuat Portal MPR...</div>
        </div>
    </div>

    <section class="portal-hero">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-8 col-md-7">
                    <h1 class="hero-title">Portal Manpower Request (MPR)</h1>
                    <p class="hero-desc">
                        Sistem pengajuan kebutuhan tenaga kerja internal untuk unit kerja MITO. Silakan masuk dengan
                        akun requestor MPR yang telah terdaftar untuk mengelola formulir dan riwayat pengajuan.
                    </p>

                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <a href="{{ route('mpr.auth.login') }}" class="btn-mito-primary" id="btnPortalLogin"
                            data-portal-redirect data-portal-msg="Membuka Halaman Login MPR...">
                            <i class="bi bi-box-arrow-in-right"></i>
                            Get Started
                        </a>
                    </div>
                </div>
                <div class="col-lg-4 col-md-5 d-none d-md-flex justify-content-end align-items-center">
                    <img src="{{ asset('assets/mito.png') }}" alt="Logo MITO untuk Portal MPR"
                        class="hero-avatar-img" width="150" height="150" fetchpriority="high">
                </div>
            </div>
        </div>
    </section>

    <main class="portal-content">
        <div class="container">
            <h2 class="section-title">Fitur Utama MPR</h2>
            <p class="section-desc">Alur pengajuan kebutuhan tenaga kerja yang terstruktur untuk kebutuhan operasional
                unit kerja.</p>

            <div class="row g-3">
                <div class="col-md-6 col-lg-4">
                    <div class="module-card">
                        <div class="module-icon"><i class="bi bi-file-earmark-plus-fill"></i></div>
                        <h3 class="module-title">Pengajuan Baru</h3>
                        <p class="module-desc">Membuat formulir kebutuhan tenaga kerja baru sesuai kebutuhan unit kerja.
                        </p>
                    </div>
                </div>

                <div class="col-md-6 col-lg-4">
                    <div class="module-card">
                        <div class="module-icon"><i class="bi bi-clock-history"></i></div>
                        <h3 class="module-title">Riwayat Pengajuan</h3>
                        <p class="module-desc">Melihat status dan histori pengajuan yang sudah dibuat sebelumnya.</p>
                    </div>
                </div>

                <div class="col-md-6 col-lg-4">
                    <div class="module-card">
                        <div class="module-icon"><i class="bi bi-shield-check"></i></div>
                        <h3 class="module-title">Akses Terbatas</h3>
                        <p class="module-desc">Portal khusus untuk requestor MPR yang telah terdaftar dan aktif.</p>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <footer class="portal-footer">
        <div class="container text-center">
            <span>{{ config('app.name', 'MITO HRIS') }}</span> &copy; {{ date('Y') }}
            <span class="portal-footer-badge">v1.0.0</span>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        (function() {
            'use strict';

            var loader = document.getElementById('portalLoader');
            var loaderText = document.getElementById('loaderText');

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
                        var msg = this.getAttribute('data-portal-msg') || 'Membuka halaman...';
                        triggerLoaderRedirect(targetHref, msg);
                    }
                });
            });
        })();
    </script>
</body>

</html>
