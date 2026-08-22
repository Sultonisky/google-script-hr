<!-- partials/Topbar.html — SHARED TOPBAR (1:1 from GAS) -->
<header class="topbar">
  <button class="btn-burger" id="btnBurger" type="button" aria-label="Menu">
    <i class="bi bi-list"></i>
  </button>
  <div class="page-title">
    <h5 id="pageTitleText">@yield('page-title', 'Dashboard')</h5>
    <small id="pageSubtitleText">@yield('page-subtitle', 'Ringkasan rekrutmen & data kandidat')</small>
  </div>
  <div class="topbar-actions">
    <!-- User Menu Dropdown -->
    <div class="topbar-user-menu" id="topbarUserMenu">
      <button class="topbar-user-btn" type="button" data-bs-toggle="dropdown" aria-label="Menu Pengguna">
        <div class="topbar-avatar" id="topbarAvatar">{{ strtoupper(substr(session('hr_user.fullName', session('hr_user.name', 'HR')), 0, 2)) }}</div>
        <div class="topbar-user-meta d-none d-md-flex">
          <span class="topbar-user-name" id="topbarUserName">{{ session('hr_user.fullName', session('hr_user.name', 'HR Team')) }}</span>
          <span class="topbar-user-role" id="topbarUserRole">{{ session('hr_user.role', 'Viewer') }}</span>
        </div>
        <i class="bi bi-chevron-down ms-1"></i>
      </button>
      <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 py-2" style="min-width: 200px;">
        <li><a class="dropdown-item py-2" href="{{ route('hr.users.index') }}"><i class="bi bi-people-fill me-2 text-primary"></i> Manajemen Pengguna</a></li>
        <li><a class="dropdown-item py-2" href="{{ route('hr.settings.index') }}"><i class="bi bi-gear-fill me-2 text-primary"></i> Pengaturan</a></li>
        <li><a class="dropdown-item py-2" href="{{ url('/') }}" target="_blank"><i class="bi bi-globe me-2 text-primary"></i> Portal Karir</a></li>
        <li><hr class="dropdown-divider my-1"></li>
        <li><a class="dropdown-item py-2 text-danger" href="{{ route('logout') }}"><i class="bi bi-box-arrow-right me-2"></i> Keluar</a></li>
      </ul>
    </div>
    <button class="icon-btn" id="btnDarkModeToggle" title="Toggle tema">
      <i class="bi bi-moon-fill"></i>
    </button>
  </div>
</header>
