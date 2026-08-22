<!-- partials/Sidebar.html — SIDEBAR (1:1 from GAS) -->
<aside class="sidebar" id="sidebar">
    <!-- Brand Header -->
    <div class="sidebar-brand">
        <div class="logo-box">MITO</div>
        <div class="brand-text">
            <strong>MITO HRIS</strong>
            <small>Applicant Tracking System</small>
        </div>
    </div>

    <!-- Navigation Menu -->
    <nav class="sidebar-nav" id="sidebarNav">
        <!-- Main Section -->
        <div class="nav-section-label">Main</div>
        <a href="{{ route('hr.dashboard') }}" class="nav-item {{ request()->routeIs('hr.dashboard') ? 'active' : '' }}">
            <i class="bi bi-grid-1x2-fill"></i> Dashboard
        </a>
        <a href="{{ route('hr.recruitment.index') }}" class="nav-item {{ request()->routeIs('hr.recruitment.index') ? 'active' : '' }}">
            <i class="bi bi-people-fill"></i> Recruitment
        </a>
        <a href="{{ route('hr.employees.index') }}" class="nav-item {{ request()->routeIs('hr.employees.*') ? 'active' : '' }}">
            <i class="bi bi-person-badge-fill"></i> Master Data
        </a>

        <!-- Candidate Status Section -->
        <div class="nav-section-label">Candidate Status</div>
        <a href="{{ route('hr.recruitment.accepted') }}" class="nav-item {{ request()->routeIs('hr.recruitment.accepted') ? 'active' : '' }}">
            <i class="bi bi-check-circle-fill"></i> Accepted
        </a>
        <a href="{{ route('hr.recruitment.hold') }}" class="nav-item {{ request()->routeIs('hr.recruitment.hold') ? 'active' : '' }}">
            <i class="bi bi-pause-circle-fill"></i> Hold
        </a>
        <a href="{{ route('hr.recruitment.blacklist') }}" class="nav-item {{ request()->routeIs('hr.recruitment.blacklist') ? 'active' : '' }}">
            <i class="bi bi-slash-circle-fill"></i> Blacklist
        </a>

        <!-- Employee Lifecycle Section -->
        <div class="nav-section-label">Employee Lifecycle</div>
        <a href="{{ route('hr.probation.index') }}" class="nav-item {{ request()->routeIs('hr.probation.*') ? 'active' : '' }}">
            <i class="bi bi-hourglass-split"></i> Probation
        </a>
        <a href="{{ route('hr.outsource.index') }}" class="nav-item {{ request()->routeIs('hr.outsource.*') ? 'active' : '' }}">
            <i class="bi bi-building"></i> Outsource
        </a>

        <!-- System Section -->
        <div class="nav-section-label">System</div>
        <a href="{{ route('hr.audit-logs.index') }}" class="nav-item {{ request()->routeIs('hr.audit-logs.*') ? 'active' : '' }}">
            <i class="bi bi-clock-history"></i> Audit Log
        </a>
        <a href="{{ route('hr.settings.index') }}" class="nav-item {{ request()->routeIs('hr.settings.*') ? 'active' : '' }}">
            <i class="bi bi-gear-fill"></i> Settings
        </a>
        <a href="{{ route('hr.users.index') }}" class="nav-item {{ request()->routeIs('hr.users.*') ? 'active' : '' }}">
            <i class="bi bi-shield-lock"></i> User Management
        </a>
    </nav>
</aside>
