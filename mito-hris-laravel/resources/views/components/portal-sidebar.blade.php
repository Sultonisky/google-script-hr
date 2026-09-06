@php
    $portal = request()->attributes->get('portal');
@endphp

<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <div class="logo-box">
            <img src="{{ asset('assets/mito-white.png') }}" alt="MITO" class="sidebar-logo">
        </div>
        <div class="brand-text">
            <h1>{{ $portal === 'certificates' ? 'Certification Portal' : 'Asset Portal' }}</h1>
        </div>
        <button type="button" class="sidebar-close-btn d-lg-none" id="btnSidebarClose" aria-label="Tutup Sidebar">
            <i class="bi bi-x-lg"></i>
        </button>
    </div>

    <nav class="sidebar-nav" id="sidebarNav">
        @if ($portal === 'certificates')
            <div class="nav-section-label">Certification</div>
            <a href="{{ route('certificates.portal.index') }}"
                class="nav-item {{ request()->routeIs('certificates.portal.*') ? 'active' : '' }}">
                <i class="bi bi-award-fill"></i> Certification Management
            </a>
        @else
            <div class="nav-section-label">Asset</div>
            <a href="{{ route('assets.portal.index') }}"
                class="nav-item {{ request()->routeIs('assets.portal.*') ? 'active' : '' }}">
                <i class="bi bi-box-seam-fill"></i> Asset Management
            </a>
        @endif
    </nav>
</aside>
