<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @include('components.seo', [
        'title' => '404 - Halaman Tidak Ditemukan | MITO Career Portal',
        'description' => 'Maaf, halaman yang Anda cari tidak dapat ditemukan di Portal Karir MITO HRIS.',
        'robots' => 'noindex,nofollow,noarchive',
    ])
    <link rel="icon" type="image/png" href="{{ asset('assets/logo-favicon.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    @vite(['resources/scss/app.scss', 'resources/scss/public.scss', 'resources/js/app.js'])

    <style>
        :root {
            --mito-primary: #eb1c24;
            --mito-primary-dark: #c41118;
            --mito-primary-subtle: #fef2f2;
            --mito-primary-border: #fee2e2;
            --mito-navy: #0b2540;
            --mito-slate: #64748b;
            --mito-bg: #f8fafc;
            --mito-card-bg: #ffffff;
            --mito-border: #e2e8f0;
        }

        body {
            font-family: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background-color: var(--mito-bg);
            color: var(--mito-navy);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            margin: 0;
            padding: 0;
            position: relative;
            overflow-x: hidden;
        }

        /* Subtle background aesthetic */
        .bg-ambient-pattern {
            position: fixed;
            inset: 0;
            pointer-events: none;
            background: 
                radial-gradient(circle at 50% 15%, rgba(235, 28, 36, 0.04) 0%, transparent 60%),
                radial-gradient(circle at 90% 90%, rgba(11, 37, 64, 0.03) 0%, transparent 50%);
            z-index: 0;
        }

        /* Main Error Content Area */
        .error-main-area {
            position: relative;
            z-index: 1;
            flex: 1 0 auto;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 3rem 1.25rem;
            width: 100%;
        }

        .error-container {
            width: 100%;
            max-width: 580px;
            margin: 0 auto;
        }

        .error-card {
            background: var(--mito-card-bg);
            border: 1px solid var(--mito-border);
            border-radius: 1.25rem;
            box-shadow: 0 10px 30px -5px rgba(11, 37, 64, 0.06), 0 4px 6px -2px rgba(11, 37, 64, 0.02);
            padding: 2.75rem 2.25rem 2.25rem;
            text-align: center;
            position: relative;
        }

        .brand-logo-link {
            display: inline-flex;
            align-items: center;
            text-decoration: none;
            transition: transform 0.2s ease, opacity 0.2s ease;
            margin-bottom: 0.5rem;
        }

        .brand-logo-link:hover {
            transform: translateY(-1px);
            opacity: 0.92;
        }

        .brand-logo {
            height: 40px;
            width: auto;
            object-fit: contain;
        }

        /* 404 Display Typography */
        .error-code {
            font-size: clamp(4.5rem, 12vw, 6rem);
            font-weight: 800;
            line-height: 1;
            letter-spacing: -0.04em;
            color: var(--mito-primary);
            margin: 0.5rem 0 0.75rem;
            user-select: none;
            display: inline-block;
            background: linear-gradient(135deg, var(--mito-primary) 0%, #b91c1c 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .error-title {
            font-size: clamp(1.25rem, 3vw, 1.5rem);
            font-weight: 700;
            color: var(--mito-navy);
            margin-bottom: 0.75rem;
            letter-spacing: -0.02em;
        }

        .error-desc {
            font-size: 0.9375rem;
            color: var(--mito-slate);
            line-height: 1.6;
            margin-bottom: 2rem;
            max-width: 460px;
            margin-left: auto;
            margin-right: auto;
        }

        /* CTA Button */
        .btn-actions {
            display: flex;
            justify-content: center;
            gap: 0.75rem;
        }

        .btn-mito-primary {
            background-color: var(--mito-primary);
            border: 1px solid var(--mito-primary);
            color: #ffffff !important;
            font-weight: 600;
            font-size: 0.925rem;
            padding: 0.7rem 1.6rem;
            border-radius: 0.625rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
            box-shadow: 0 4px 12px rgba(235, 28, 36, 0.2);
            text-decoration: none;
        }

        .btn-mito-primary:hover {
            background-color: var(--mito-primary-dark);
            border-color: var(--mito-primary-dark);
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(235, 28, 36, 0.3);
        }

        /* Full-Width Footer (1:1 with MITO Public Theme) */
        /* .footer {
            background: #fff;
            color: var(--mito-navy);
            padding: 36px 1rem;
            text-align: center;
            margin-top: auto;
            width: 100%;
            position: relative;
            z-index: 1;
        }

        .footer-brand-name {
            font-size: 16px;
            font-weight: 800;
            letter-spacing: 0.05em;
            margin-bottom: 4px;
            color: var(--mito-navy);
        }

        .footer-tagline {
            font-size: 12.5px;
            color: var(--mito-navy);
            margin-bottom: 8px;
        }

        .footer-help {
            font-size: 11.5px;
            color: rgba(0, 0, 0, 0.85);
            max-width: 600px;
            margin: 0 auto;
        } */

        @media (max-width: 576px) {
            .error-card {
                padding: 2rem 1.25rem 1.75rem;
                border-radius: 1rem;
            }

            .btn-actions {
                width: 100%;
            }

            .btn-mito-primary {
                width: 100%;
            }

            .footer {
                padding: 28px 1rem;
            }
        }
    </style>
</head>

<body>
    <div class="bg-ambient-pattern" aria-hidden="true"></div>

    <!-- Main Content Area -->
    <main class="error-main-area">
        <div class="error-container">
            <section class="error-card">
                <!-- MITO Logo -->
               

                <div class="error-code">404</div>

                <h1 class="error-title">Halaman Tidak Ditemukan</h1>

                <p class="error-desc">
                    Maaf, halaman atau posisi karir yang Anda tuju tidak tersedia, telah dipindahkan, atau lowongan rekrutmen sudah ditutup.
                </p>

                <div class="btn-actions">
                    <a href="{{ route('public.career.index') }}" class="btn-mito-primary">
                        <i class="bi bi-arrow-left me-2" aria-hidden="true"></i>
                        Kembali ke Portal Karir
                    </a>
                </div>
            </section>
        </div>
    </main>

    <!-- Full-Width Footer -->
    <!-- <footer class="footer">
        <div class="footer-brand-name">MITO HRIS</div>
        <div class="footer-tagline">&copy; {{ date('Y') }} &mdash; Crafted for Modern Human Resources</div>
        <div class="footer-help">
            Apabila mengalami kendala saat mengakses formulir atau portal, silakan menghubungi Human Resources MITO Group.
        </div>
    </footer> -->
</body>

</html>
