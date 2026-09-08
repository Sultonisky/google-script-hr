@php
    $portal = request()->attributes->get('portal');
    $sessionKey = $portal === 'certificates' ? 'certificate_auth' : 'asset_auth';
    $portalUser = session($sessionKey, []);
    $portalName = $portal === 'certificates' ? 'Portal Sertifikasi' : 'Portal Aset';
    $logoutRoute = $portal === 'certificates' ? 'certificates.logout' : 'assets.logout';
@endphp

<header class="topbar">
    <button class="btn-burger" id="btnBurger" type="button" aria-label="Menu">
        <i class="bi bi-list"></i>
    </button>
    <div class="page-title">
        <h5 id="pageTitleText">@yield('page-title', $portalName)</h5>
        <small id="pageSubtitleText">@yield('page-subtitle', 'Kelola data portal')</small>
    </div>
    <div class="topbar-actions">
        <div class="topbar-user-menu" id="topbarUserMenu">
            <button class="topbar-user-btn" type="button" data-bs-toggle="dropdown" aria-label="Menu Pengguna">
                <div class="topbar-avatar" id="topbarAvatar">{{ strtoupper(substr((string) ($portalUser['fullName'] ?? $portalUser['name'] ?? 'U'), 0, 2)) }}</div>
                <div class="topbar-user-meta d-none d-md-flex">
                    <span class="topbar-user-name" id="topbarUserName">{{ $portalUser['fullName'] ?? $portalUser['name'] ?? 'Pengguna' }}</span>
                    <span class="topbar-user-role" id="topbarUserRole">{{ $portalUser['role'] ?? $portalName }}</span>
                </div>
                <i class="bi bi-chevron-down ms-1"></i>
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 py-2">
                <li>
                    <form method="POST" action="{{ route($logoutRoute) }}">
                        @csrf
                        <button type="submit" class="dropdown-item py-2 text-danger">
                            <i class="bi bi-box-arrow-right me-2"></i> Keluar
                        </button>
                    </form>
                </li>
            </ul>
        </div>
        <button class="icon-btn" id="btnDarkModeToggle" title="Toggle tema">
            <i class="bi bi-moon-fill"></i>
        </button>
    </div>
</header>
