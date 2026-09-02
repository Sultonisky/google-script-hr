<!-- partials/Sidebar.html — SIDEBAR (1:1 from GAS) -->
{{--
  Sidebar visibility uses @can / @endcan throughout, consistent with route-level
  can: middleware. Gate::userResolver() in AuthServiceProvider wires session('hr_user')
  as the Gate user, so all @can checks work correctly without Laravel's Eloquent auth.
--}}
<aside class="sidebar" id="sidebar">
    <!-- Brand Header -->
    <div class="sidebar-brand">
        <div class="logo-box">
            <img src="{{ asset('assets/mito-white.png') }}" alt="MITO" class="sidebar-logo">
        </div>
        <div class="brand-text">
            <h1>Human Resource Information System</h1>
        </div>
        <button type="button" class="sidebar-close-btn d-lg-none" id="btnSidebarClose" aria-label="Tutup Sidebar">
            <i class="bi bi-x-lg"></i>
        </button>
    </div>

    <!-- Navigation Menu -->
    <nav class="sidebar-nav" id="sidebarNav">
        @php
            // ── Sidebar navigation — strict portal isolation ─────────────────────────────
            // DomainMiddleware sets request attribute 'portal' = 'hris' | 'mpr' | ...
            // This is the authoritative signal for which portal is currently being rendered.
            // We never infer the portal from session content — that is how cross-portal
            // leakage happened before the fix.
            //
            // MPR portal (/mpr/*): read ONLY mpr_requestor_auth (auth_domain='mpr_requestor').
            // HRIS portal (/hr/*): read ONLY hr_user (auth_domain='users').
            // Neither portal ever falls back to the other portal's session key.
            $currentPortal = request()->attributes->get('portal', 'hris');

            if ($currentPortal === 'mpr') {
                $mprSidebarKey    = config('mpr.session_key', 'mpr_requestor_auth');
                $mprSidebarSess   = session($mprSidebarKey, []);
                $isMprRequestorUi = !empty($mprSidebarSess)
                                    && ($mprSidebarSess['auth_domain'] ?? '') === 'mpr_requestor';
            } else {
                // HRIS portal — always show HRIS navigation regardless of any MPR session.
                $isMprRequestorUi = false;
            }

            // HRIS identity variables (only used when $isMprRequestorUi = false).
            $currentAuthDomain = session('hr_user.auth_domain', 'users');
            $currentRole       = session('hr_user.role', 'Viewer');
        @endphp
        @if ($isMprRequestorUi)
            <!-- MPR Requestor Navigation (source: mpr_requestor sheet) -->
            <div class="nav-section-label">Manpower Request</div>
            <a href="{{ route('mpr.auth.request') }}" class="nav-item {{ request()->routeIs('mpr.auth.request') ? 'active' : '' }}">
                <i class="bi bi-file-earmark-plus-fill"></i> Pengajuan MPR
            </a>
            <a href="{{ route('mpr.auth.request.history') }}" class="nav-item {{ request()->routeIs('mpr.auth.request.history') ? 'active' : '' }}">
                <i class="bi bi-clock-history"></i> Riwayat Pengajuan
            </a>
        @else
            <!-- Main Section -->
            <div class="nav-section-label">Main</div>
            <a href="{{ route('hr.dashboard') }}"
                class="nav-item {{ request()->routeIs('hr.dashboard') ? 'active' : '' }}">
                <i class="bi bi-grid-1x2-fill"></i> Dashboard
            </a>
            @can('view_mpr')
                <a href="{{ route('hr.mpr.index') }}" class="nav-item {{ request()->routeIs('hr.mpr.*') ? 'active' : '' }}">
                    <i class="bi bi-file-earmark-text-fill"></i> Manpower Request
                </a>
            @endcan
            @can('view_recruitment')
                <a href="{{ route('hr.recruitment.index') }}"
                    class="nav-item {{ request()->routeIs('hr.recruitment.index') ? 'active' : '' }}">
                    <i class="bi bi-people-fill"></i> Recruitment
                </a>
            @endcan
            {{-- Master Data (Employee) — requires view_employees OR manage_employees --}}
            @canany(['view_employees', 'manage_employees'])
                <a href="{{ route('hr.employees.index') }}"
                    class="nav-item {{ request()->routeIs('hr.employees.*') ? 'active' : '' }}">
                    <i class="bi bi-person-badge-fill"></i> Master Data
                </a>
            @endcanany

            <!-- Candidate Status Section -->
            @can('view_recruitment')
                <div class="nav-section-label">Candidate Status</div>
                <a href="{{ route('hr.recruitment.accepted') }}"
                    class="nav-item {{ request()->routeIs('hr.recruitment.accepted') ? 'active' : '' }}">
                    <i class="bi bi-check-circle-fill"></i> Accepted
                </a>
                <a href="{{ route('hr.recruitment.hold') }}"
                    class="nav-item {{ request()->routeIs('hr.recruitment.hold') ? 'active' : '' }}">
                    <i class="bi bi-pause-circle-fill"></i> Hold
                </a>
                <a href="{{ route('hr.recruitment.blacklist') }}"
                    class="nav-item {{ request()->routeIs('hr.recruitment.blacklist') ? 'active' : '' }}">
                    <i class="bi bi-slash-circle-fill"></i> Blacklist
                </a>
            @endcan

            <!-- Employee Lifecycle Section -->
            @canany(['manage_probation', 'view_employees'])
                <div class="nav-section-label">Employee Lifecycle</div>
                @can('manage_probation')
                    <a href="{{ route('hr.probation.index') }}"
                        class="nav-item {{ request()->routeIs('hr.probation.*') ? 'active' : '' }}">
                        <i class="bi bi-hourglass-split"></i> Probation
                    </a>
                @endcan
                @can('view_employees')
                    <a href="{{ route('hr.outsource.index') }}"
                        class="nav-item {{ request()->routeIs('hr.outsource.*') ? 'active' : '' }}">
                        <i class="bi bi-building"></i> Outsource
                    </a>
                @endcan
            @endcanany

            <!-- System Section -->
            @canany(['view_reports', 'manage_settings'])
                <div class="nav-section-label">System</div>

                @can('manage_settings')
                    <a href="{{ route('hr.users.index') }}"
                        class="nav-item {{ request()->routeIs('hr.users.*') ? 'active' : '' }}">
                        <i class="bi bi-shield-lock"></i> User Management
                    </a>
                    <a href="{{ route('hr.mpr-requestors.index') }}"
                        class="nav-item {{ request()->routeIs('hr.mpr-requestors.*') ? 'active' : '' }}">
                        <i class="bi bi-person-lines-fill"></i> MPR Requestors
                    </a>
                    <a href="{{ route('hr.settings.index') }}"
                        class="nav-item {{ request()->routeIs('hr.settings.*') ? 'active' : '' }}">
                        <i class="bi bi-gear-fill"></i> Settings
                    </a>
                @endcan
                @can('view_reports')
                    <a href="{{ route('hr.audit-logs.index') }}"
                        class="nav-item {{ request()->routeIs('hr.audit-logs.*') ? 'active' : '' }}">
                        <i class="bi bi-clock-history"></i> Audit Log
                    </a>
                @endcan
            @endcanany
        @endif
    </nav>
</aside>
