<!doctype html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Portal Aset | {{ config('app.name', 'MITO HRIS') }}</title>
    <link rel="icon" type="image/png" href="{{ asset('assets/logo-favicon.png') }}">
    @vite(['resources/scss/app.scss', 'resources/js/app.js'])
    <style>
        body { min-height: 100vh; background: #f4f6f8; }
        .portal-login { min-height: 100vh; display: grid; place-items: center; padding: 1.5rem; }
        .portal-login-card { width: min(100%, 28rem); }
    </style>
</head>
<body>
    <main class="portal-login">
        <section class="portal-login-card card border-0 shadow-sm" aria-labelledby="portal-login-title">
            <div class="card-body p-4 p-md-5">
                <p class="text-uppercase text-danger fw-semibold small mb-2">MITO Internal Portal</p>
                <h1 id="portal-login-title" class="h3 mb-2">Portal Aset</h1>
                <p class="text-secondary mb-4">Masuk untuk mengelola aset perusahaan.</p>

                @if (session('error'))
                    <div class="alert alert-danger" role="alert">{{ session('error') }}</div>
                @endif
                @if (session('info'))
                    <div class="alert alert-info" role="status">{{ session('info') }}</div>
                @endif
                @if ($errors->any())
                    <div class="alert alert-danger" role="alert">Email/username atau password salah.</div>
                @endif

                <form method="POST" action="{{ route('assets.login.post') }}" novalidate>
                    @csrf
                    <div class="mb-3">
                        <label class="form-label" for="asset-login-identifier">Email atau Username</label>
                        <input class="form-control" id="asset-login-identifier" name="identifier" type="text" autocomplete="username" required autofocus>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="asset-login-password">Password</label>
                        <input class="form-control" id="asset-login-password" name="password" type="password" autocomplete="current-password" required>
                    </div>
                    <div class="form-check mb-4">
                        <input class="form-check-input" id="asset-login-remember" name="rememberMe" type="checkbox" value="1">
                        <label class="form-check-label" for="asset-login-remember">Ingat Saya</label>
                    </div>
                    <button class="btn btn-danger w-100" type="submit">Masuk ke Portal Aset</button>
                </form>
            </div>
        </section>
    </main>
</body>
</html>
