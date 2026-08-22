@extends('layouts.hr')

@section('title', 'Employee Probation - MITO HRIS')
@section('page-title', 'Employee Probation')
@section('page-subtitle', 'Monitoring evaluasi masa percobaan karyawan')

@section('content')
    <!-- partials/ProbationPage.html — Halaman Employee Probation (1:1 from GAS) -->
    <section class="page-section active" id="pageProbation">

        <!-- Stat cards -->
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-3">
                <div class="stat-card">
                    <div class="stat-icon bg-purple"><i class="bi bi-person-fill-check"></i></div>
                    <div>
                        <div class="stat-label">Onboarding Selesai</div>
                        <div class="stat-value" id="probStatWorkflow">{{ $stats['onboarding'] ?? 0 }}</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card">
                    <div class="stat-icon bg-navy"><i class="bi bi-hourglass-split"></i></div>
                    <div>
                        <div class="stat-label">Total Probation</div>
                        <div class="stat-value" id="probStatTotal">{{ $probations->count() }}</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card">
                    <div class="stat-icon bg-gold"><i class="bi bi-clipboard-check"></i></div>
                    <div>
                        <div class="stat-label">Sudah Dievaluasi</div>
                        <div class="stat-value" id="probStatEvaluated">{{ $stats['evaluated'] ?? 0 }}</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card">
                    <div class="stat-icon bg-green"><i class="bi bi-person-check-fill"></i></div>
                    <div>
                        <div class="stat-label">Lulus Tetap</div>
                        <div class="stat-value" id="probStatPassed">{{ $stats['passed'] ?? 0 }}</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card">
                    <div class="stat-icon bg-red"><i class="bi bi-arrow-repeat"></i></div>
                    <div>
                        <div class="stat-label">Diperpanjang</div>
                        <div class="stat-value" id="probStatExtended">{{ $stats['extended'] ?? 0 }}</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Table panel -->
        <div class="panel" id="probTablePanel">
            <div class="panel-header">
                <div>
                    <h6>Karyawan Probation</h6>
                    <div class="panel-subtitle" id="probPanelSubtitle">Menampilkan {{ $probations->count() }} data</div>
                </div>
                <div class="export-btns">
                    <button class="btn-refresh" id="btnProbRefresh" type="button" title="Muat ulang"
                        onclick="location.reload()">
                        <i class="bi bi-arrow-clockwise"></i>
                    </button>
                    <button class="btn btn-sm text-white ms-2 fw-semibold"
                        style="background:#7c3aed;border:none;border-radius:8px;padding:6px 14px;font-size:13px"
                        id="btnOpenEvalModal" type="button" data-bs-toggle="modal" data-bs-target="#probationEvalModal">
                        <i class="bi bi-clipboard-check me-1"></i>Evaluasi Karyawan
                    </button>
                </div>
            </div>

            <!-- Filter bar -->
            <form action="{{ route('hr.probation.index') }}" method="GET">
                <div class="filter-bar">
                    <div class="table-search">
                        <i class="bi bi-search"></i>
                        <input type="text" name="search" id="probSearchInput"
                            placeholder="Cari nama, ID, posisi, departemen..." value="{{ request('search') }}" />
                    </div>
                    <select class="filter-select" name="department" id="probDeptFilter" onchange="this.form.submit()">
                        <option value="">Semua Dept</option>
                        @foreach ($departments ?? [] as $dept)
                            <option value="{{ $dept }}" {{ request('department') === $dept ? 'selected' : '' }}>
                                {{ $dept }}</option>
                        @endforeach
                    </select>
                    <select class="filter-select" name="sort" id="probSortSelect" onchange="this.form.submit()">
                        <option value="newest" {{ request('sort') === 'newest' ? 'selected' : '' }}>Terbaru</option>
                        <option value="oldest" {{ request('sort') === 'oldest' ? 'selected' : '' }}>Terlama</option>
                        <option value="name_asc" {{ request('sort') === 'name_asc' ? 'selected' : '' }}>Nama A-Z</option>
                        <option value="name_desc" {{ request('sort') === 'name_desc' ? 'selected' : '' }}>Nama Z-A</option>
                    </select>
                    <a href="{{ route('hr.probation.index') }}" class="btn-reset-filter text-decoration-none"
                        id="btnProbReset">
                        <i class="bi bi-arrow-counterclockwise"></i> Reset
                    </a>
                </div>
            </form>

            <!-- Table -->
            <div class="table-responsive">
                <table class="table hr-table">
                    <thead>
                        <tr>
                            <th>Avatar</th>
                            <th>Employee ID</th>
                            <th>Employee</th>
                            <th>Position / Dept</th>
                            <th>Probation Start</th>
                            <th>Probation End</th>
                            <th>Last Score</th>
                            <th>Evaluation Status</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="probTableBody">
                        @forelse($probations as $prob)
                            <tr>
                                <td>
                                    <div class="avatar-sm">
                                        {{ strtoupper(substr($prob->fullName ?? 'P', 0, 2)) }}
                                    </div>
                                </td>
                                <td class="id-mono">{{ $prob->employeeId }}</td>
                                <td>
                                    <div class="cand-name fw-bold text-primary text-decoration-underline">
                                        {{ $prob->fullName }}</div>
                                    <div class="cand-sub">{{ $prob->personalEmail }}</div>
                                </td>
                                <td>{{ $prob->jobPosition }} <small
                                        class="text-muted d-block">({{ $prob->department }})</small></td>
                                <td class="id-mono">{{ $prob->joinDate ?? '-' }}</td>
                                <td class="id-mono">{{ $prob->endDateContract ?? '-' }}</td>
                                <td><span class="badge bg-light text-dark border">{{ $prob->lastScore ?? '-' }}</span></td>
                                <td><x-badge-status :status="$prob->statusEmployee ?? 'Probation'" /></td>
                                <td class="text-end">
                                    <button class="btn btn-sm btn-outline-primary" type="button" data-bs-toggle="modal"
                                        data-bs-target="#probationEvalModal"
                                        onclick="document.getElementById('probSelectEmployee').value='{{ $prob->employeeId }}'; document.getElementById('probSelectEmployee').dispatchEvent(new Event('change'));">
                                        <i class="bi bi-clipboard-check me-1"></i> Evaluasi
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8">
                                    <div class="table-empty">
                                        <i class="bi bi-hourglass"></i>
                                        <p>Belum ada karyawan dengan status Probation.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="panel-footer">
                <span id="probFooterCount">Menampilkan {{ $probations->count() }} data</span>
            </div>
        </div>

    </section>
@endsection
