<!doctype html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <title>Login — Portal Aset | {{ config('app.name', 'MITO HRIS') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
    <link rel="icon" type="image/png" href="{{ asset('assets/logo-favicon.png') }}">
    @vite(['resources/scss/app.scss', 'resources/js/app.js'])
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

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

        .login-header { text-align: center; }

        .login-logo {
            width: 130px;
            height: 130px;
            background: #fff;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 14px;
            border: 2px solid #eb1c24;
            box-shadow: 0 4px 20px rgba(0,0,0,.1);
        }

        .login-logo img { max-width: 120%; max-height: 120%; }

        .login-header h1 {
            font-size: 22px;
            font-weight: 800;
            color: #0b2540;
            margin-bottom: 4px;
            letter-spacing: -.3px;
        }

        .login-header .login-greeting { font-size: 14px; color: #6b7280; }

        .login-card {
            width: 100%;
            max-width: 400px;
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 2px 16px rgba(0,0,0,.08);
            padding: 36px 32px;
        }

        .login-card-title  { font-size: 16px; font-weight: 700; color: #1a1a2e; margin-bottom: 4px; }
        .login-card-subtitle { font-size: 13px; color: #9ca3af; margin-bottom: 24px; }

        /* ── alerts ── */
        .login-error, .login-info {
            border-radius: 10px;
            padding: 12px 14px;
            margin-bottom: 16px;
            display: none;
            align-items: flex-start;
            gap: 8px;
        }
        .login-error { background: #fef2f2; border: 1px solid #fecaca; }
        .login-error.show { display: flex; }
        .login-error i  { color: #991b1b; font-size: 16px; margin-top: 1px; flex-shrink: 0; }
        .login-error .error-text { font-size: 13px; color: #991b1b; line-height: 1.5; }

        .login-info { background: #eff6ff; border: 1px solid #bfdbfe; }
        .login-info.show { display: flex; }
        .login-info i  { color: #1d4ed8; font-size: 16px; margin-top: 1px; flex-shrink: 0; }
        .login-info .info-text { font-size: 13px; color: #1e40af; line-height: 1.5; }

        /* ── loading ── */
        .login-loading {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 24px 0;
        }
        .login-spinner {
            width: 36px; height: 36px;
            border: 3px solid #e5e7eb;
            border-top-color: #eb1c24;
            border-radius: 50%;
            animation: spin .8s linear infinite;
        }
        @keyframes spin { to { transform: rotate(360deg); } }

        /* ── form ── */
        .login-form-group { margin-bottom: 16px; }
        .login-form-group label { display: block; font-size: 13px; font-weight: 600; color: #374151; margin-bottom: 6px; }
        .login-form-group .input-wrapper { position: relative; }
        .login-form-group .input-wrapper > i {
            position: absolute; left: 12px; top: 50%; transform: translateY(-50%);
            font-size: 16px; color: #9ca3af; pointer-events: none;
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
            transition: border-color .18s ease, box-shadow .18s ease;
            outline: none;
        }
        #assetLoginPassword { padding-right: 42px; }
        .login-form-group input::placeholder { color: #c0c4cc; }
        .login-form-group input:focus { border-color: #eb1c24; box-shadow: 0 0 0 3px rgba(235,28,36,.1); }
        .login-form-group input.is-invalid { border-color: #dc2626; box-shadow: 0 0 0 3px rgba(220,38,38,.12); }

        .password-toggle {
            position: absolute; right: 10px; top: 50%; transform: translateY(-50%);
            background: none; border: none; cursor: pointer; color: #9ca3af;
            font-size: 16px; padding: 4px 6px; line-height: 1; z-index: 2;
            transition: color .18s ease;
        }
        .password-toggle:hover { color: #6b7280; }
        .password-toggle i { pointer-events: none; }

        .btn-login {
            display: flex; align-items: center; justify-content: center; gap: 8px;
            width: 100%; padding: 12px 20px; border: none; border-radius: 10px;
            background: #eb1c24; font-size: 14px; font-weight: 600; color: #fff;
            cursor: pointer; transition: all .18s ease; margin-top: 4px;
        }
        .btn-login:hover { opacity: .92; box-shadow: 0 4px 12px rgba(235,28,36,.3); }
        .btn-login:active { transform: scale(.98); }
        .btn-login:disabled { opacity: .6; cursor: not-allowed; }
        .btn-login i { font-size: 16px; }

        .login-divider {
            display: flex; align-items: center; gap: 10px;
            margin: 20px 0 0; color: #d1d5db; font-size: 12px;
        }
        .login-divider::before, .login-divider::after { content: ''; flex: 1; height: 1px; background: #e5e7eb; }

        /* ── feature chips ── */
        .login-features { width: 100%; max-width: 560px; display: flex; justify-content: center; gap: 8px; flex-wrap: wrap; }
        .feature-chip {
            display: inline-flex; align-items: center; gap: 6px; padding: 6px 12px;
            background: #fff; border: 1px solid #e5e7eb; border-radius: 20px;
            font-size: 12px; color: #6b7280; font-weight: 500; white-space: nowrap;
        }
        .feature-chip i { font-size: 13px; color: #eb1c24; }

        /* ── footer ── */
        .login-footer { padding: 16px 24px; text-align: center; font-size: 11px; color: #9ca3af; }
        .login-footer a { color: #9ca3af; text-decoration: none; }
        .version-badge {
            display: inline-block; background: #e5e7eb; color: #9ca3af;
            padding: 1px 8px; border-radius: 20px; font-size: 10px; font-weight: 600; margin-left: 6px;
        }

        /* ── success state ── */
        .login-success-state { display: none; flex-direction: column; align-items: center; gap: 12px; padding: 24px 0 8px; }
        .login-success-state.show { display: flex; }
        .login-success-check {
            width: 56px; height: 56px; border-radius: 50%;
            background: #ECFDF5; border: 2.5px solid #10B981;
            display: flex; align-items: center; justify-content: center;
            animation: checkBounceIn .5s cubic-bezier(.34,1.56,.64,1) forwards;
        }
        .login-success-check i { font-size: 28px; color: #10B981; opacity: 0; animation: checkIconFadeIn .3s ease .25s forwards; }
        @keyframes checkBounceIn {
            0%   { transform: scale(0); opacity: 0; }
            60%  { transform: scale(1.1); }
            100% { transform: scale(1);  opacity: 1; }
        }
        @keyframes checkIconFadeIn {
            0%   { opacity: 0; transform: scale(.5); }
            100% { opacity: 1; transform: scale(1); }
        }
        .login-success-text    { font-size: 16px; font-weight: 700; color: #065F46; animation: fadeSlideUp .4s ease .4s forwards; opacity: 0; }
        .login-success-welcome { font-size: 13px; color: #6b7280; animation: fadeSlideUp .4s ease .55s forwards; opacity: 0; }
        @keyframes fadeSlideUp {
            0%   { opacity: 0; transform: translateY(6px); }
            100% { opacity: 1; transform: translateY(0); }
        }

        /* ── exit animation ── */
        .login-page.exiting { animation: loginExit .45s ease forwards; }
        @keyframes loginExit {
            0%   { opacity: 1; transform: translateY(0) scale(1); }
            100% { opacity: 0; transform: translateY(-20px) scale(.96); }
        }
        .login-transition-overlay {
            position: fixed; inset: 0; z-index: 99990;
            background: rgba(255,255,255,0); pointer-events: none; opacity: 0;
        }
        .login-transition-overlay.active { animation: overlayFadeIn .3s ease .1s forwards; pointer-events: auto; }
        @keyframes overlayFadeIn {
            0%   { opacity: 0; background: rgba(255,255,255,0); }
            100% { opacity: 1; background: rgba(255,255,255,1); }
        }

        @media (max-width: 480px) {
            .login-page  { padding: 24px 12px 20px; gap: 18px; }
            .login-card  { padding: 24px 16px; border-radius: 14px; }
            .login-header h1 { font-size: 19px; }
            .login-features { gap: 6px; }
            .feature-chip { font-size: 11px; padding: 5px 10px; }
        }
    </style>
</head>

<body>

<div class="login-page">

    <div class="login-header">
        <div class="login-logo">
            <img src="{{ asset('assets/mito-red.png') }}" alt="MITO Logo"
                 onerror="this.style.display='none';this.parentElement.innerHTML='<h2 class=\'fw-bold mb-0 text-danger\' style=\'font-size:32px;\'>MITO</h2>'">
        </div>
        <h1>{{ config('app.name', 'MITO HRIS') }}</h1>
        <p class="login-greeting">Selamat datang - silakan masuk untuk melanjutkan</p>
    </div>

    <div class="login-card">

        <div class="login-card-title">Portal Aset</div>
        <div class="login-card-subtitle">Masuk untuk mengelola aset perusahaan</div>

        <div class="login-error" id="loginError">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <div class="error-text" id="loginErrorText"></div>
        </div>
        <div class="login-info" id="loginInfo">
            <i class="bi bi-info-circle-fill"></i>
            <div class="info-text" id="loginInfoText"></div>
        </div>

        <div class="login-loading" id="loginLoading" style="display:none;">
            <div class="login-spinner"></div>
        </div>

        <div class="login-success-state" id="loginSuccessState">
            <div class="login-success-check"><i class="bi bi-check-lg"></i></div>
            <div class="login-success-text">Login Berhasil</div>
            <div class="login-success-welcome" id="loginSuccessWelcome"></div>
        </div>

        @if (session('error'))
            <script>
                document.addEventListener('DOMContentLoaded', function () {
                    var el = document.getElementById('loginError');
                    var tx = document.getElementById('loginErrorText');
                    if (el && tx) { tx.textContent = @json(session('error')); el.classList.add('show'); }
                });
            </script>
        @endif
        @if (session('info'))
            <script>
                document.addEventListener('DOMContentLoaded', function () {
                    var el = document.getElementById('loginInfo');
                    var tx = document.getElementById('loginInfoText');
                    if (el && tx) { tx.textContent = @json(session('info')); el.classList.add('show'); }
                });
            </script>
        @endif
        @if ($errors->any())
            <script>
                document.addEventListener('DOMContentLoaded', function () {
                    var el = document.getElementById('loginError');
                    var tx = document.getElementById('loginErrorText');
                    if (el && tx) { tx.textContent = 'Email atau password salah.'; el.classList.add('show'); }
                });
            </script>
        @endif

        <form id="loginForm" method="POST" action="{{ route('assets.login.post') }}" autocomplete="on" novalidate>
            @csrf
            <div class="login-form-group">
                <label for="assetLoginIdentifier">Email</label>
                <div class="input-wrapper">
                    <i class="bi bi-envelope-fill"></i>
                    <input type="email" id="assetLoginIdentifier" name="identifier"
                           placeholder="nama@perusahaan.com" autocomplete="email" inputmode="email"
                           maxlength="255" required autofocus />
                </div>
            </div>
            <div class="login-form-group">
                <label for="assetLoginPassword">Password</label>
                <div class="input-wrapper">
                    <i class="bi bi-lock-fill"></i>
                    <input type="password" id="assetLoginPassword" name="password"
                           placeholder="Password" autocomplete="current-password" maxlength="255" required />
                    <button type="button" class="password-toggle" id="togglePassword" tabindex="-1"
                            aria-label="Tampilkan password">
                        <i class="bi bi-eye-fill"></i>
                    </button>
                </div>
            </div>
            <div class="login-form-group" style="margin-bottom:8px;">
                <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-weight:500;margin-bottom:0;">
                    <input type="checkbox" id="loginRememberMe" name="rememberMe"
                           style="width:16px;height:16px;accent-color:#eb1c24;cursor:pointer;" />
                    <span style="font-size:13px;color:#374151;">Ingat Saya</span>
                </label>
            </div>
            <button class="btn-login" id="btnLogin" type="submit">
                <i class="bi bi-box-arrow-in-right"></i>
                Masuk ke Portal Aset
            </button>
        </form>

        <div class="login-divider">Sistem Internal — Hanya Pengguna Terdaftar</div>
    </div>

    <div class="login-features">
        <div class="feature-chip"><i class="bi bi-box-seam-fill"></i> Manajemen Aset</div>
        <div class="feature-chip"><i class="bi bi-person-badge-fill"></i> Akses Berbasis Role</div>
        <div class="feature-chip"><i class="bi bi-arrow-left-right"></i> Penugasan & Pengembalian</div>
        <div class="feature-chip"><i class="bi bi-qr-code"></i> Kode Aset Otomatis</div>
        <div class="feature-chip"><i class="bi bi-clock-history"></i> Audit Log</div>
    </div>

</div>

<div class="login-transition-overlay" id="loginTransitionOverlay"></div>

<div class="login-footer">
    <span>{{ config('app.name', 'MITO HRIS') }}</span> &copy; 2026
    <span class="version-badge">Portal Aset</span>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
(function () {
    'use strict';

    var loginError   = document.getElementById('loginError');
    var loginErrorTx = document.getElementById('loginErrorText');
    var loginInfo    = document.getElementById('loginInfo');
    var loginInfoTx  = document.getElementById('loginInfoText');
    var loginLoading = document.getElementById('loginLoading');
    var btnLogin     = document.getElementById('btnLogin');
    var togglePw     = document.getElementById('togglePassword');

    function showError(msg) {
        loginError.classList.add('show');
        loginErrorTx.textContent = msg;
        if (loginLoading) loginLoading.style.display = 'none';
        var form = document.getElementById('loginForm');
        if (form) form.style.display = '';
        btnLogin.disabled = false;
    }

    function showInfo(msg) {
        loginInfo.classList.add('show');
        loginInfoTx.textContent = msg;
    }

    function showLoading() {
        if (loginLoading) loginLoading.style.display = 'flex';
        var form = document.getElementById('loginForm');
        if (form) form.style.display = 'none';
        loginError.classList.remove('show');
        loginInfo.classList.remove('show');
    }

    function getCsrf() {
        var m = document.querySelector('meta[name="csrf-token"]');
        if (m) return m.getAttribute('content');
        var i = document.querySelector('input[name="_token"]');
        return i ? i.value : '';
    }

    function redirectTo(url) {
        var target = url || '{{ route('assets.portal.index') }}';
        try { window.top.location.replace(target); } catch (e) { window.location.replace(target); }
    }

    function handleResult(result) {
        if (result && result.success) {
            var form = document.getElementById('loginForm');
            if (form) form.style.display = 'none';
            loginError.classList.remove('show');
            loginInfo.classList.remove('show');
            if (loginLoading) loginLoading.style.display = 'none';

            var successState = document.getElementById('loginSuccessState');
            var welcomeEl    = document.getElementById('loginSuccessWelcome');
            if (welcomeEl && result.message) {
                welcomeEl.textContent = result.message;
            }
            if (successState) successState.classList.add('show');

            var targetUrl = result.redirect || '{{ route('assets.portal.index') }}';
            setTimeout(function () {
                var overlay   = document.getElementById('loginTransitionOverlay');
                var loginPage = document.querySelector('.login-page');
                if (overlay)   overlay.classList.add('active');
                if (loginPage) loginPage.classList.add('exiting');
                setTimeout(function () { redirectTo(targetUrl); }, 500);
            }, 1100);
            return;
        }

        showError(result && result.error ? result.error : 'Login gagal. Silakan coba lagi.');
    }

    // password toggle
    if (togglePw) {
        togglePw.addEventListener('click', function (e) {
            e.stopPropagation();
            var pwInput = document.getElementById('assetLoginPassword');
            var icon    = togglePw.querySelector('i');
            if (!pwInput) return;
            var hidden = pwInput.type === 'password';
            pwInput.type = hidden ? 'text' : 'password';
            if (icon) icon.className = hidden ? 'bi bi-eye-slash-fill' : 'bi bi-eye-fill';
            togglePw.setAttribute('aria-label', hidden ? 'Sembunyikan password' : 'Tampilkan password');
        });
    }

    var EMAIL_PATTERN = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

    function setInvalid(el, invalid) {
        if (el) el.classList.toggle('is-invalid', !!invalid);
    }

    // submit
    document.getElementById('loginForm').addEventListener('submit', function (e) {
        e.preventDefault();

        var emailInput    = document.getElementById('assetLoginIdentifier');
        var passwordInput = document.getElementById('assetLoginPassword');
        var identifier    = String(emailInput.value || '').trim().toLowerCase();
        var password      = String(passwordInput.value || '');

        setInvalid(emailInput, false);
        setInvalid(passwordInput, false);

        if (!identifier) { setInvalid(emailInput, true); emailInput.focus(); showError('Email wajib diisi.'); return; }
        if (!EMAIL_PATTERN.test(identifier)) { setInvalid(emailInput, true); emailInput.focus(); showError('Format email tidak valid.'); return; }
        if (!password)   { setInvalid(passwordInput, true); passwordInput.focus(); showError('Password wajib diisi.'); return; }

        emailInput.value = identifier;
        btnLogin.disabled = true;
        showLoading();

        var remember = document.getElementById('loginRememberMe') ? document.getElementById('loginRememberMe').checked : false;

        fetch('{{ route('assets.login.post') }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': getCsrf(), 'Accept': 'application/json' },
            body: JSON.stringify({ identifier: identifier, password: password, rememberMe: remember }),
            credentials: 'same-origin',
        })
        .then(function (res) {
            return res.json().then(function (data) { return { status: res.status, data: data }; });
        })
        .then(function (result) {
            if (result.status >= 200 && result.status < 300 && result.data && result.data.success) {
                handleResult(result.data);
            } else {
                var err = (result.data && result.data.error) ? result.data.error : 'Login gagal. Silakan coba lagi.';
                if (result.data && result.data.errors) {
                    var k = Object.keys(result.data.errors)[0];
                    if (k && result.data.errors[k]) err = result.data.errors[k][0];
                }
                handleResult({ success: false, error: err });
            }
        })
        .catch(function (err) {
            showError('Gagal terhubung ke server: ' + (err && err.message ? err.message : String(err)));
        });
    });

})();
</script>
</body>
</html>
