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
        @php
          // ── Identity resolution — strict portal isolation ───────────────────────────
          // DomainMiddleware sets request attribute 'portal' = 'hris' | 'mpr' | ...
          // This is the authoritative, domain-driven signal for which portal is rendering.
          // We NEVER infer the portal from session content — that is exactly how the
          // cross-portal leakage bug occurred: the old code checked mpr_requestor_auth
          // FIRST (before hr_user), so when both sessions coexisted after switching
          // portals, MPR Manpower name/role/logout appeared in the HRIS topbar.
          //
          // MPR portal  (/mpr/*)  → identity = mpr_requestor_auth ONLY
          // HRIS portal (/hr/*)   → identity = hr_user             ONLY
          $currentPortal  = request()->attributes->get('portal', 'hris');

          if ($currentPortal === 'mpr') {
              $mprSessionKey      = config('mpr.session_key', 'mpr_requestor_auth');
              $mprSession         = session($mprSessionKey, []);
              $isDedicatedMprUser = !empty($mprSession)
                                    && ($mprSession['auth_domain'] ?? '') === 'mpr_requestor';
              $topbarUserName     = $mprSession['fullName'] ?? 'Requestor';
              $topbarUserRole     = $mprSession['role']     ?? 'Manpower';
          } else {
              // HRIS portal — read ONLY hr_user. mpr_requestor_auth is never consulted.
              $isDedicatedMprUser = false;
              $topbarUserName     = session('hr_user.fullName', session('hr_user.name', 'HR Team'));
              $topbarUserRole     = session('hr_user.role', 'Viewer');
          }

          $topbarAvatar = strtoupper(substr((string) $topbarUserName, 0, 2));
        @endphp
        <div class="topbar-avatar" id="topbarAvatar">{{ $topbarAvatar ?: 'HR' }}</div>
        <div class="topbar-user-meta d-none d-md-flex">
          <span class="topbar-user-name" id="topbarUserName">{{ $topbarUserName }}</span>
          <span class="topbar-user-role" id="topbarUserRole">{{ $topbarUserRole }}</span>
        </div>
        <i class="bi bi-chevron-down ms-1"></i>
      </button>
      <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 py-2" style="min-width: 200px;">
        @if (!$isDedicatedMprUser)
          {{-- HRIS users: Settings links are gated by Gate permission on hr_user role. --}}
          @can('manage_settings')
            <li><a class="dropdown-item py-2" href="{{ route('hr.users.index') }}"><i class="bi bi-people-fill me-2 text-primary"></i> Manajemen Pengguna</a></li>
            <li><a class="dropdown-item py-2" href="{{ route('hr.settings.index') }}"><i class="bi bi-gear-fill me-2 text-primary"></i> Pengaturan</a></li>
          @endcan
        @endif
        <li><a class="dropdown-item py-2" href="{{ url('/') }}" target="_blank"><i class="bi bi-globe me-2 text-primary"></i> Portal Karir</a></li>
        <li><hr class="dropdown-divider my-1"></li>
        <li>
          @if ($isDedicatedMprUser)
            {{-- MPR Requestor: use MPR-specific logout to clear only the MPR session key. --}}
            <form method="POST" action="{{ route('mpr.auth.logout') }}" class="d-inline w-100">
              @csrf
              <button type="submit" class="dropdown-item py-2 text-danger w-100 text-start border-0 bg-transparent">
                <i class="bi bi-box-arrow-right me-2"></i> Keluar
              </button>
            </form>
          @else
            {{-- HRIS user: use HRIS logout. --}}
            <a class="dropdown-item py-2 text-danger" href="{{ route('logout') }}"><i class="bi bi-box-arrow-right me-2"></i> Keluar</a>
          @endif
        </li>
      </ul>
    </div>
    <button class="icon-btn" id="btnDarkModeToggle" title="Toggle tema">
      <i class="bi bi-moon-fill"></i>
    </button>
  </div>
</header>
