@extends('layouts.hr')

@section('title', 'Karyawan Outsource - MITO HRIS')
@section('page-title', 'Karyawan Outsource')
@section('page-subtitle', 'Manajemen tenaga kerja alih daya (Outsource)')

@section('content')
    <!-- partials/OutsourceTable.html — OUTSOURCE DATA PANEL (1:1 from GAS) -->
    <section class="page-section active" id="pageOutsource">

        <!-- Outsource Stat Cards (1:1 from GAS) -->
        <div class="row g-3 mb-3 mt-2" id="outsourceStats">
            <div class="col-12">
                <div
                    style="font-size:13px;font-weight:700;color:var(--color-text-soft);text-transform:uppercase;letter-spacing:.05em;padding:4px 0 8px;">
                    <i class="bi bi-building-fill me-2" style="color:var(--color-primary, #eb1c24)"></i>Data Karyawan
                    Outsource
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card">
                    <div class="stat-icon bg-cyan"><i class="bi bi-people-fill"></i></div>
                    <div>
                        <div class="stat-label">Total OS</div>
                        <div class="stat-value text-navy" id="osStatTotal">{{ $outsources->count() }}</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card">
                    <div class="stat-icon bg-green"><i class="bi bi-check-circle-fill"></i></div>
                    <div>
                        <div class="stat-label">Permanent</div>
                        <div class="stat-value text-navy" id="osStatPermanent">{{ $stats['permanent'] ?? 0 }}</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card">
                    <div class="stat-icon bg-blue"><i class="bi bi-file-earmark-person-fill"></i></div>
                    <div>
                        <div class="stat-label">Contract</div>
                        <div class="stat-value text-navy" id="osStatContract">{{ $stats['contract'] ?? 0 }}</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card">
                    <div class="stat-icon bg-gold"><i class="bi bi-hourglass-split"></i></div>
                    <div>
                        <div class="stat-label">Probation</div>
                        <div class="stat-value text-navy" id="osStatProbation">{{ $stats['probation'] ?? 0 }}</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Outsource Table Panel -->
        <div class="panel mt-2" id="outsourcePanel">
            <div class="panel-header">
                <div>
                    <h6>Data Karyawan Outsource</h6>
                    <div class="panel-subtitle" id="osPanelSubtitle">Menampilkan {{ $outsources->count() }} data</div>
                </div>
            </div>

            <!-- Filter bar -->
            <form action="{{ route('hr.outsource.index') }}" method="GET">
                <div class="filter-bar">
                    <div class="table-search">
                        <i class="bi bi-search"></i>
                        <input type="text" name="search" id="osSearchInput" placeholder="Cari nama, NIK, posisi..."
                            value="{{ request('search') }}" />
                    </div>
                    <select class="filter-select" name="status" id="osStatusFilter" onchange="this.form.submit()">
                        <option value="">Semua Status</option>
                        <option value="Permanent" {{ request('status') === 'Permanent' ? 'selected' : '' }}>Permanent
                        </option>
                        <option value="Contract" {{ request('status') === 'Contract' ? 'selected' : '' }}>Contract</option>
                        <option value="Probation" {{ request('status') === 'Probation' ? 'selected' : '' }}>Probation
                        </option>
                        <option value="Outsource" {{ request('status') === 'Outsource' ? 'selected' : '' }}>Outsource
                        </option>
                    </select>
                    <select class="filter-select" name="sort" id="osSortSelect" onchange="this.form.submit()">
                        <option value="newest" {{ request('sort') === 'newest' ? 'selected' : '' }}>Terbaru</option>
                        <option value="oldest" {{ request('sort') === 'oldest' ? 'selected' : '' }}>Terlama</option>
                        <option value="name_asc" {{ request('sort') === 'name_asc' ? 'selected' : '' }}>Nama A-Z</option>
                        <option value="name_desc" {{ request('sort') === 'name_desc' ? 'selected' : '' }}>Nama Z-A</option>
                    </select>
                    <a href="{{ route('hr.outsource.index') }}" class="btn-reset-filter text-decoration-none"
                        id="osBtnResetFilter">
                        <i class="bi bi-arrow-counterclockwise"></i> Reset
                    </a>
                </div>
            </form>

            <!-- Table (1:1 from GAS: Avatar, Employee ID, Nama, Posisi / Dept, Tanggal Join, Status, Aksi) -->
            <div class="table-responsive">
                <table class="table hr-table">
                    <thead>
                        <tr>
                            <th>Avatar</th>
                            <th>Employee ID</th>
                            <th>Nama</th>
                            <th>Posisi / Dept</th>
                            <th>Tanggal Join</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody id="osTableBody">
                        @forelse($outsources as $os)
                            <tr data-drawer-type="outsource" data-drawer-id="{{ $os->employeeId }}"
                                style="cursor:pointer;">
                                <td>
                                    <div class="avatar-sm">
                                        {{ strtoupper(substr($os->fullName ?? 'O', 0, 2)) }}
                                    </div>
                                </td>
                                <td class="id-mono fw-bold">{{ $os->employeeId }}</td>
                                <td>
                                    <div class="cand-name fw-bold text-primary text-decoration-underline ">
                                        {{ $os->fullName }}</div>
                                    <div class="cand-sub">{{ $os->workingEmail ?? $os->personalEmail }}</div>
                                </td>
                                <td>{{ $os->jobPosition }} <small
                                        class="text-muted d-block">({{ $os->department }})</small></td>
                                <td class="id-mono">{{ $os->joinDate ?? '-' }}</td>
                                <td><x-badge-status :status="$os->statusEmployee ?? 'Outsource'" /></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7">
                                    <div class="table-empty">
                                        <i class="bi bi-inbox"></i>
                                        <p>Belum ada data karyawan outsource.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="panel-footer">
                <span id="osFooterCount">Menampilkan {{ $outsources->count() }} data</span>
            </div>
        </div>

    </section>
@endsection

@section('scripts')
    <script>
        // Drawer click handler for outsource
        document.querySelectorAll('#osTableBody tr[data-drawer-type="outsource"]').forEach(function(row) {
            row.addEventListener('click', function(e) {
                if (e.target.tagName === 'BUTTON' || e.target.closest('button')) return;
                var id = this.getAttribute('data-drawer-id');
                if (id && typeof openOutsourceDrawer === 'function') {
                    openOutsourceDrawer(id);
                }
            });
        });
    </script>
@endsection
