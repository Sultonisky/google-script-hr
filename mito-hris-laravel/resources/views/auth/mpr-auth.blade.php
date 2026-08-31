<!doctype html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Login MPR - {{ config('app.name', 'MITO HRIS') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
    <link rel="icon" type="image/png" href="{{ asset('assets/logo-favicon.png') }}">

    @vite(['resources/js/app.js'])

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', 'Segoe UI', sans-serif;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            background: #f0f2f5;
        }

        .login-page {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 40px 24px 32px;
            gap: 32px;
        }

        .login-header {
            text-align: center;
        }

        .login-logo {
            width: 64px;
            height: 64px;
            background: #fff;
            border-radius: 16px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            font-weight: 800;
            color: #eb1c24;
            margin-bottom: 14px;
        }

        .login-logo img {
            max-width: 40px;
            max-height: 40px;
            border-radius: 8px;
        }

        .login-header h1 {
            font-size: 22px;
            font-weight: 800;
            color: #0b2540;
            margin-bottom: 4px;
            letter-spacing: -0.3px;
        }

        .login-header .login-greeting {
            font-size: 14px;
            color: #6b7280;
        }

        .login-card {
            width: 100%;
            max-width: 400px;
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 2px 16px rgba(0, 0, 0, 0.08);
            padding: 36px 32px;
        }

        .login-card-title {
            font-size: 16px;
            font-weight: 700;
            color: #1a1a2e;
            margin-bottom: 4px;
        }

        .login-card-subtitle {
            font-size: 13px;
            color: #9ca3af;
            margin-bottom: 24px;
        }

        .login-error {
            background: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: 10px;
            padding: 12px 14px;
            margin-bottom: 16px;
            display: none;
            align-items: flex-start;
            gap: 8px;
        }

        .login-error.show {
            display: flex;
        }

        .login-error i {
            color: #991b1b;
            font-size: 16px;
            margin-top: 1px;
            flex-shrink: 0;
        }

        .login-error .error-text {
            font-size: 13px;
            color: #991b1b;
            line-height: 1.5;
        }

        .login-info {
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            border-radius: 10px;
            padding: 12px 14px;
            margin-bottom: 16px;
            display: none;
            align-items: flex-start;
            gap: 8px;
        }

        .login-info.show {
            display: flex;
        }

        .login-info i {
            color: #1d4ed8;
            font-size: 16px;
            margin-top: 1px;
            flex-shrink: 0;
        }

        .login-info .info-text {
            font-size: 13px;
            color: #1e40af;
            line-height: 1.5;
        }

        .login-form-group {
            margin-bottom: 16px;
        }

        .login-form-group label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #374151;
            margin-bottom: 6px;
        }

        .login-form-group .input-wrapper {
            position: relative;
        }

        .login-form-group .input-wrapper>i {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 16px;
            color: #9ca3af;
            pointer-events: none;
        }

        .login-form-group input {
            width: 100%;
            padding: 11px 14px 11px 38px;
            border: 1.5px solid #e5e7eb;
            border-radius: 10px;
            font-size: 14px;
            font-family: 'Inter', 'Segoe UI', sans-serif;
            color: #1a1a2e;
            background: #fff;
            transition: border-color 0.18s ease, box-shadow 0.18s ease;
            outline: none;
        }

        #password {
            padding-right: 42px;
        }

        .password-toggle {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            color: #9ca3af;
            font-size: 16px;
            padding: 4px 6px;
            line-height: 1;
            z-index: 2;
            transition: color 0.18s ease;
        }

        .password-toggle:hover {
            color: #6b7280;
        }

        .password-toggle i {
            pointer-events: none;
        }

        .login-form-group input::placeholder {
            color: #c0c4cc;
        }

        .login-form-group input:focus {
            border-color: #eb1c24;
            box-shadow: 0 0 0 3px rgba(235, 28, 36, 0.1);
        }

        .btn-login {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            width: 100%;
            padding: 12px 20px;
            border: none;
            border-radius: 10px;
            background: #eb1c24;
            font-size: 14px;
            font-weight: 600;
            color: #fff;
            cursor: pointer;
            transition: all 0.18s ease;
            margin-top: 4px;
        }

        .btn-login:hover {
            opacity: 0.92;
            box-shadow: 0 4px 12px rgba(235, 28, 36, 0.3);
        }

        .btn-login:active {
            transform: scale(0.98);
        }

        .btn-login:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            box-shadow: none;
        }

        .btn-login i {
            font-size: 16px;
        }

        .login-loading {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 24px 0;
        }

        .login-spinner {
            width: 36px;
            height: 36px;
            border: 3px solid #e5e7eb;
            border-top-color: #eb1c24;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        .login-divider {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 20px 0 0;
            color: #d1d5db;
            font-size: 12px;
        }

        .login-divider::before,
        .login-divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: #e5e7eb;
        }

        .login-features {
            width: 100%;
            max-width: 560px;
            display: flex;
            justify-content: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .feature-chip {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 20px;
            font-size: 12px;
            color: #6b7280;
            font-weight: 500;
            white-space: nowrap;
        }

        .feature-chip i {
            font-size: 13px;
            color: #eb1c24;
        }

        .login-footer {
            padding: 16px 24px;
            text-align: center;
            font-size: 11px;
            color: #9ca3af;
        }

        .login-footer a {
            color: #9ca3af;
            text-decoration: none;
        }

        .version-badge {
            display: inline-block;
            background: #e5e7eb;
            color: #9ca3af;
            padding: 1px 8px;
            border-radius: 20px;
            font-size: 10px;
            font-weight: 600;
            margin-left: 6px;
        }

        .back-link {
            color: #6b7280;
            text-decoration: none;
            font-size: 12.5px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: color 0.2s ease;
        }

        .back-link:hover {
            color: #374151;
        }

        .login-page.exiting {
            animation: loginExit 0.45s ease forwards;
        }

        @keyframes loginExit {
            0% {
                opacity: 1;
                transform: translateY(0) scale(1);
            }

            100% {
                opacity: 0;
                transform: translateY(-20px) scale(0.96);
            }
        }

        .login-transition-overlay {
            position: fixed;
            inset: 0;
            z-index: 99990;
            background: rgba(255, 255, 255, 0);
            pointer-events: none;
            opacity: 0;
        }

        .login-transition-overlay.active {
            animation: overlayFadeIn 0.3s ease 0.1s forwards;
            pointer-events: auto;
        }

        @keyframes overlayFadeIn {
            0% {
                opacity: 0;
                background: rgba(255, 255, 255, 0);
            }

            100% {
                opacity: 1;
                background: rgba(255, 255, 255, 1);
            }
        }

        @media (max-width: 480px) {
            .login-page {
                padding: 24px 12px 20px;
                gap: 18px;
            }

            .login-card {
                padding: 24px 16px;
                border-radius: 14px;
            }

            .login-header h1 {
                font-size: 19px;
            }

            .login-features {
                gap: 6px;
            }

            .feature-chip {
                font-size: 11px;
                padding: 5px 10px;
            }
        }
    </style>
</head>

<body>
    <div class="login-page">
        <div class="login-header">
            <div class="login-logo rounded-circle d-inline-flex align-items-center justify-content-center shadow-lg"
                style="width: 130px; height: 130px; margin-bottom: 14px; border: 2px solid #eb1c24;">
                <img src="{{ asset('assets/mito-red.png') }}" alt="MITO Logo" style="max-width: 120%; max-height: 120%;"
                    onerror="this.style.display='none';this.parentElement.innerHTML='<h2 class=\'fw-bold mb-0 text-danger\' style=\'font-size:32px;\'>MITO</h2>'">
            </div>
            <h1 id="loginCompanyName">{{ config('app.name', 'MITO HRIS') }}</h1>
            <p class="login-greeting">Portal MPR - silakan masuk untuk melanjutkan</p>
        </div>

        <div class="login-card">
            <div class="login-card-title">Masuk ke MPR</div>
            <div class="login-card-subtitle">Gunakan email requestor MPR Anda</div>

            @if (session('error'))
                <div class="login-error show" role="alert">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    <div class="error-text">{{ session('error') }}</div>
                </div>
            @endif

            @if ($errors->any())
                <div class="login-error show" role="alert">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    <div class="error-text">{{ $errors->first() }}</div>
                </div>
            @endif

            <div class="login-loading" id="loginLoading" style="display:none;">
                <div class="login-spinner"></div>
            </div>

            <form id="mprLoginForm" method="POST" action="{{ route('mpr.auth.login.post') }}" autocomplete="on">
                @csrf
                <div class="login-form-group">
                    <label for="identifier">Email atau Username</label>
                    <div class="input-wrapper">
                        <i class="bi bi-person-fill"></i>
                        <input type="text" id="identifier" name="identifier" value="{{ old('identifier') }}"
                            placeholder="Email atau Username" required autofocus />
                    </div>
                </div>

                <div class="login-form-group">
                    <label for="password">Password</label>
                    <div class="input-wrapper">
                        <i class="bi bi-lock-fill"></i>
                        <input type="password" id="password" name="password" placeholder="Password" required />
                        <button type="button" class="password-toggle" id="togglePassword" tabindex="-1"
                            aria-label="Tampilkan password">
                            <i class="bi bi-eye-fill"></i>
                        </button>
                    </div>
                </div>

                <button class="btn-login" id="btnMprLogin" type="submit">
                    <i class="bi bi-box-arrow-in-right"></i>
                    Masuk
                </button>
            </form>

            <div class="login-divider">Manpower Request Portal</div>
            <div class="text-center mt-3">
                <a href="{{ route('mpr.auth.portal') }}" class="back-link">
                    <i class="bi bi-arrow-left"></i>
                    Kembali ke Portal MPR
                </a>
            </div>
        </div>

        <div class="login-features">
            <div class="feature-chip"><i class="bi bi-clipboard-check"></i> Request MPR</div>
            <div class="feature-chip"><i class="bi bi-people-fill"></i> Requestor Terdaftar</div>
            <div class="feature-chip"><i class="bi bi-file-earmark-text"></i> Dokumen Mendukung</div>
            <div class="feature-chip"><i class="bi bi-shield-check"></i> Akses Khusus</div>
        </div>
    </div>

    <div class="login-transition-overlay" id="loginTransitionOverlay"></div>

    <div class="login-footer">
        <span>{{ config('app.name', 'MITO HRIS') }}</span> &copy; 2026
        <span class="version-badge">v1.0.0</span>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const togglePassword = document.getElementById('togglePassword');
            const passwordInput = document.getElementById('password');
            const form = document.getElementById('mprLoginForm');
            const loading = document.getElementById('loginLoading');
            const submitButton = document.getElementById('btnMprLogin');
            const page = document.querySelector('.login-page');
            const overlay = document.getElementById('loginTransitionOverlay');

            if (togglePassword && passwordInput) {
                togglePassword.addEventListener('click', function() {
                    const isHidden = passwordInput.type === 'password';
                    passwordInput.type = isHidden ? 'text' : 'password';
                    const icon = togglePassword.querySelector('i');
                    if (icon) {
                        icon.className = isHidden ? 'bi bi-eye-slash-fill' : 'bi bi-eye-fill';
                    }
                    togglePassword.setAttribute('aria-label', isHidden ? 'Sembunyikan password' :
                        'Tampilkan password');
                });
            }

            if (form && loading && submitButton) {
                form.addEventListener('submit', function(event) {
                    event.preventDefault();

                    form.style.display = 'none';
                    loading.style.display = 'flex';
                    submitButton.disabled = true;

                    if (page) {
                        page.classList.add('exiting');
                    }

                    if (overlay) {
                        overlay.classList.add('active');
                    }

                    setTimeout(function() {
                        form.submit();
                    }, 450);
                });
            }
        });
    </script>
</body>

</html>
