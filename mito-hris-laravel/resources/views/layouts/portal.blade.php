<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/png" href="{{ asset('assets/logo-favicon.png') }}">
    @include('components.seo', [
        'title' => trim($__env->yieldContent('title')) ?: 'MITO Portal',
        'description' => 'MITO dedicated internal portal.',
        'robots' => 'noindex,nofollow,noarchive',
    ])

    <script nonce="{{ request()->attributes->get('csp_nonce') }}">
        (function() {
            const savedTheme = localStorage.getItem('mito_theme') || 'light';
            document.documentElement.setAttribute('data-theme', savedTheme);
            document.documentElement.setAttribute('data-bs-theme', savedTheme);
        })();
    </script>

    @vite([
        'resources/scss/app.scss',
        'resources/scss/hr.scss',
        'resources/js/app.js',
        'resources/js/page-loader.js',
        'resources/js/csp-hardening.js',
    ])
    @yield('styles')
</head>

<body>
    <div id="mito-page-loader" aria-hidden="true"></div>
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    @include('components.portal-sidebar')

    <div class="main-content">
        @include('components.portal-topbar')
        <div class="content-wrap">
            @include('components.alerts')
            <x-toast />
            @yield('content')
        </div>
    </div>

    @yield('scripts')
</body>

</html>
