<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @include('components.seo', [
        'title' => trim($__env->yieldContent('title')) ?: 'MITO Career Portal | Job Opportunities',
        'description' =>
            trim($__env->yieldContent('description')) ?:
            'Temukan informasi rekrutmen dan peluang karir yang tersedia melalui MITO HRIS Career Portal.',
        'robots' => trim($__env->yieldContent('robots')) ?: 'index,follow',
        'structuredData' => $__env->yieldContent('structuredData')
            ? json_decode($__env->yieldContent('structuredData'), true)
            : null,
    ])

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="icon" type="image/png" href="{{ asset('assets/logo-favicon.png') }}">
    <link rel="preload" as="image" href="{{ asset('assets/mito-red.png') }}" fetchpriority="high">

    <style>
        .public-loader {
            position: fixed;
            inset: 0;
            z-index: 2000;
            display: grid;
            place-items: center;
            background: #fff;
            opacity: 1;
            visibility: visible;
            transition: opacity 0.42s cubic-bezier(0.4, 0, 0.2, 1), visibility 0s linear 0.42s;
        }

        .public-loader.is-hidden {
            opacity: 0;
            visibility: hidden;
            pointer-events: none;
        }

        .public-loader-content {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 1.35rem;
            transform: translateY(0);
            animation: public-loader-enter 0.65s cubic-bezier(0.22, 1, 0.36, 1) both;
        }

        .public-loader-logo {
            display: block;
            width: min(240px, 65vw);
            height: auto;
        }

        .public-loader-dots {
            display: flex;
            align-items: center;
            gap: 0.55rem;
        }

        .public-loader-dots span {
            display: block;
            width: 0.62rem;
            height: 0.62rem;
            border-radius: 50%;
            background: #eb1c24;
            animation: public-loader-dot 1.15s cubic-bezier(0.4, 0, 0.2, 1) infinite;
            will-change: transform, opacity;
        }

        .public-loader-dots span:nth-child(2) {
            animation-delay: 0.14s;
        }

        .public-loader-dots span:nth-child(3) {
            animation-delay: 0.28s;
        }

        @keyframes public-loader-enter {
            from {
                opacity: 0;
                transform: translateY(0.5rem);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes public-loader-dot {

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

        @media (prefers-reduced-motion: reduce) {

            .public-loader,
            .public-loader-content,
            .public-loader-dots span {
                animation: none;
                transition: none;
            }
        }
    </style>

    @vite(['resources/scss/app.scss', 'resources/scss/public.scss', 'resources/js/app.js', 'resources/js/csp-hardening.js'])
    @yield('styles')
    <style>
        :root {
            --color-primary: #eb1c24;
            --color-primary-dark: #c41118;
            --color-navy: #0b2540;
            --color-accent: #fdb913;
            --color-bg: #f5f7fa;
            --color-surface: #ffffff;
            --color-text: #1f2937;
            --color-text-soft: #6b7280;
            --color-border: #e5e7eb;
        }

        body {
            background: var(--color-bg);
            font-family: "Inter", sans-serif;
            color: var(--color-text);
            margin: 0;
            padding: 0;
        }

        .hero-section {
            background: linear-gradient(135deg, var(--color-primary) 0%, var(--color-primary-dark) 100%);
            color: #fff;
            padding: 48px 0 40px;
            border-bottom: 3px solid #eb1c24;
        }

        .hero-org {
            font-size: 13px;
            font-weight: 600;
            color: rgba(255, 255, 255, 0.85);
            text-transform: uppercase;
            letter-spacing: 0.06em;
            margin-bottom: 4px;
        }

        .hero-dept {
            font-size: 12px;
            font-weight: 500;
            color: #ffcccc;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            margin-bottom: 12px;
        }

        .hero-title {
            font-size: clamp(22px, 3.5vw, 32px);
            font-weight: 800;
            line-height: 1.25;
            margin-bottom: 12px;
            letter-spacing: -0.5px;
        }

        .hero-subtitle {
            font-size: 14px;
            color: rgba(255, 255, 255, 0.9);
            max-width: 600px;
            line-height: 1.6;
        }

        .hero-avatar-img {
            width: 150px;
            height: 150px;
            object-fit: cover;
            border-radius: 50%;
            border: 4px solid rgba(255, 255, 255, 0.4);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.25);
            background: #fff;
        }

        .progress-section {
            background: rgba(255, 255, 255, 0.97);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid var(--color-border);
            padding: 14px 0;
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
        }

        .progress-label {
            font-size: 13px;
            font-weight: 600;
            color: var(--color-text);
            margin-bottom: 6px;
        }

        .progress-bar-wrap {
            height: 8px;
            background: #e5e7eb;
            border-radius: 100px;
            overflow: hidden;
            margin-bottom: 6px;
        }

        .progress-bar-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--color-primary) 0%, #ff4d4f 100%);
            border-radius: 100px;
            width: 0%;
            transition: width 0.35s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .progress-meta {
            font-size: 12px;
            color: var(--color-text-soft);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .progress-message {
            font-weight: 600;
            color: var(--color-primary);
        }

        .form-section {
            background: var(--color-surface);
            border-radius: 16px;
            border: 1px solid var(--color-border);
            margin-bottom: 24px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }

        .form-section-header {
            padding: 18px 22px;
            display: flex;
            align-items: center;
            gap: 14px;
            border-bottom: 1px solid var(--color-border);
            background: #fafafa;
        }

        .form-section-header>i {
            width: 38px;
            height: 38px;
            border-radius: 10px;
            background: rgba(235, 28, 36, 0.1);
            color: var(--color-primary);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            flex-shrink: 0;
        }

        .form-section-header h5 {
            font-size: 15px;
            font-weight: 700;
            margin: 0;
            color: var(--color-navy);
        }

        .form-section-header p {
            font-size: 12px;
            color: var(--color-text-soft);
            margin: 0;
        }

        .section-status {
            margin-left: auto;
            flex-shrink: 0;
            width: 32px;
            height: 32px;
            font-size: 16px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            transition: all 0.15s ease;
            white-space: nowrap;
        }

        .section-status.done {
            background: #dcfce7;
            color: #15803d;
        }

        .section-status:not(.done) {
            background: #fff7ed;
            color: #c2410c;
        }

        .btn-submit {
            background: var(--color-primary);
            color: #fff;
            font-weight: 700;
            font-size: 14px;
            padding: 12px 28px;
            border-radius: 12px;
            border: none;
            box-shadow: 0 6px 18px -3px rgba(235, 28, 36, 0.4);
            transition: all 0.2s ease;
        }

        .btn-submit:hover:not(:disabled) {
            background: var(--color-primary-dark);
            color: #fff;
            transform: translateY(-1px);
        }

        .btn-submit:disabled {
            opacity: 0.55;
            cursor: not-allowed;
        }

        .footer {
            background: var(--color-primary);
            color: #fff;
            padding: 36px 0;
            text-align: center;
            margin-top: 40px;
        }

        .footer-brand-name {
            font-size: 16px;
            font-weight: 800;
            letter-spacing: 0.05em;
            margin-bottom: 4px;
            color: #fff;
        }

        .footer-tagline {
            font-size: 12.5px;
            color: #fff;
            margin-bottom: 10px;
        }

        .footer-help {
            font-size: 11.5px;
            color: #fff;
        }
    </style>
</head>

<body>

    <div class="public-loader" id="publicLoader" role="status" aria-label="Memuat halaman">
        <div class="public-loader-content">
            <img class="public-loader-logo" src="{{ asset('assets/mito-red-load.png') }}" alt="MITO">
            <div class="public-loader-dots" aria-hidden="true">
                <span></span>
                <span></span>
                <span></span>
            </div>
        </div>
    </div>

    <x-toast />

    {{-- Main Content without Topbar (1:1 from GAS) --}}
    <main>
        @yield('content')
    </main>

    {{-- Footer 1:1 from GAS --}}
    <footer class="footer">
        <div class="container">
            <div class="footer-brand-name">MITO HRIS</div>
            <div class="footer-tagline">&copy; {{ date('Y') }} &mdash; Crafted for Modern Human Resources</div>
            <div class="footer-help">Apabila mengalami kendala saat mengisi formulir, silakan menghubungi Human
                Resources MITO Group.</div>
        </div>
    </footer>

    @yield('scripts')

    <script>
        (function() {
            const loader = document.getElementById('publicLoader');
            if (!loader) return;

            let holdVisible = false;

            const hideLoader = () => {
                if (holdVisible || loader.classList.contains('is-hidden')) return;

                window.requestAnimationFrame(() => {
                    if (holdVisible) return;
                    loader.classList.add('is-hidden');
                });
            };

            window.showPublicLoader = function(label) {
                holdVisible = true;
                loader.setAttribute('aria-label', label || 'Memuat halaman');
                loader.classList.remove('is-hidden');
                const content = loader.querySelector('.public-loader-content');
                if (content) {
                    content.style.animation = 'none';
                    void content.offsetWidth;
                    content.style.animation = '';
                }
            };

            if (document.readyState === 'complete') {
                hideLoader();
            } else {
                document.addEventListener('DOMContentLoaded', hideLoader, {
                    once: true
                });
                window.addEventListener('load', hideLoader, {
                    once: true
                });
                window.setTimeout(hideLoader, 2500);
            }
        })();
    </script>
</body>

</html>
