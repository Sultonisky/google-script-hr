@extends('layouts.hr')

@section('title', 'Master Data Karyawan - MITO HRIS')
@section('page-title', 'Master Data Karyawan')
@section('page-subtitle', 'Daftar master seluruh karyawan aktif dan riwayat kontrak')

@section('content')
    <!-- partials/EmployeeContent.html — EMPLOYEE PAGE CONTENT (1:1 from GAS) -->
    <section class="page-section active" id="pageEmployee">

        <!-- Stat Cards -->
        <div class="row g-3 mb-4" id="employeeStatsPanel">
            <div class="col-6 col-md-3">
                <div class="stat-card">
                    <div class="stat-icon bg-blue"><i class="bi bi-people-fill"></i></div>
                    <div>
                        <div class="stat-label">Total Karyawan</div>
                        <div class="stat-value text-navy" id="empStatTotal">{{ $stats['total'] ?? $employees->count() }}
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card">
                    <div class="stat-icon bg-green"><i class="bi bi-person-check-fill"></i></div>
                    <div>
                        <div class="stat-label">Permanent (PKWTT)</div>
                        <div class="stat-value text-navy" id="empStatPermanent">{{ $stats['pkwtt'] ?? 0 }}</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card">
                    <div class="stat-icon bg-blue"><i class="bi bi-file-earmark-person-fill"></i></div>
                    <div>
                        <div class="stat-label">Contract (PKWT)</div>
                        <div class="stat-value text-navy" id="empStatContract">{{ $stats['pkwt'] ?? 0 }}</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card">
                    <div class="stat-icon bg-gold"><i class="bi bi-hourglass-split"></i></div>
                    <div>
                        <div class="stat-label">Probation</div>
                        <div class="stat-value text-navy" id="empStatProbation">{{ $stats['probation'] ?? 0 }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="panel" id="employeePanel">
            <div class="panel-header">
                <div>
                    <h6>Daftar Karyawan</h6>
                    <div class="panel-subtitle">Klik baris atau nama untuk melihat detail lengkap dari spreadsheet.</div>
                </div>
                <div class="export-btns">
                    <button class="btn btn-sm text-white fw-semibold"
                        style="background:#166534;border:none;border-radius:8px;padding:6px 14px;font-size:13px"
                        type="button" data-bs-toggle="modal" data-bs-target="#importModal">
                        <i class="bi bi-upload me-1"></i>Import
                    </button>
                    <button class="btn btn-sm ms-2 fw-semibold text-white"
                        style="background:#0B2540;border:none;border-radius:8px;padding:6px 14px;font-size:13px"
                        type="button" data-bs-toggle="modal" data-bs-target="#rotationModal">
                        <i class="bi bi-arrow-left-right me-1"></i>Rotasi
                    </button>
                    <button class="btn btn-sm ms-2 fw-semibold text-white"
                        style="background:#d97706;border:none;border-radius:8px;padding:6px 14px;font-size:13px"
                        type="button" data-bs-toggle="modal" data-bs-target="#offContractModal">
                        <i class="bi bi-calendar-x me-1"></i>Off Contract
                    </button>
                    <button class="btn btn-sm ms-2 fw-semibold text-white"
                        style="background:#991b1b;border:none;border-radius:8px;padding:6px 14px;font-size:13px"
                        type="button" data-bs-toggle="modal" data-bs-target="#offboardingModal">
                        <i class="bi bi-box-arrow-right me-1"></i>Offboarding
                    </button>
                    </button>
                </div>
            </div>

            <!-- FILTER BAR -->
            <form action="{{ route('hr.employees.index') }}" method="GET">
                <div class="filter-bar">
                    <div class="table-search">
                        <i class="bi bi-search"></i>
                        <input type="text" name="search" id="empSearchInput"
                            placeholder="Cari nama, NIK, email, posisi, dept..." value="{{ request('search') }}" />
                    </div>
                    <select class="filter-select" name="department" id="empDeptFilter" onchange="this.form.submit()">
                        <option value="">Semua Dept</option>
                        @foreach ($departments ?? [] as $dept)
                            <option value="{{ $dept }}" {{ request('department') === $dept ? 'selected' : '' }}>
                                {{ $dept }}</option>
                        @endforeach
                    </select>
                    <select class="filter-select" name="status" id="empStatusFilter" onchange="this.form.submit()">
                        <option value="">Semua Status</option>
                        <option value="PKWTT" {{ request('status') === 'PKWTT' ? 'selected' : '' }}>PKWTT (Permanent)
                        </option>
                        <option value="PKWT" {{ request('status') === 'PKWT' ? 'selected' : '' }}>PKWT (Contract)
                        </option>
                        <option value="Probation" {{ request('status') === 'Probation' ? 'selected' : '' }}>Probation
                        </option>
                        <option value="Outsource" {{ request('status') === 'Outsource' ? 'selected' : '' }}>Outsource
                        </option>
                        <option value="Resigned" {{ request('status') === 'Resigned' ? 'selected' : '' }}>Resigned</option>
                        <option value="Terminated" {{ request('status') === 'Terminated' ? 'selected' : '' }}>Terminated
                        </option>
                    </select>
                    <select class="filter-select" name="sort" id="empSortSelect" onchange="this.form.submit()">
                        <option value="newest" {{ request('sort') === 'newest' ? 'selected' : '' }}>Terbaru</option>
                        <option value="oldest" {{ request('sort') === 'oldest' ? 'selected' : '' }}>Terlama</option>
                        <option value="name_asc" {{ request('sort') === 'name_asc' ? 'selected' : '' }}>Nama A-Z</option>
                        <option value="name_desc" {{ request('sort') === 'name_desc' ? 'selected' : '' }}>Nama Z-A</option>
                    </select>
                    <button class="btn-refresh" type="button" title="Muat ulang data" onclick="location.reload()">
                        <i class="bi bi-arrow-clockwise"></i>
                    </button>
                </div>
            </form>

            <!-- TABLE -->
            <div class="table-responsive">
                <table class="table hr-table">
                    <thead>
                        <tr>
                            <th>Avatar</th>
                            <th>Employee ID</th>
                            <th>Karyawan</th>
                            <th>NIK</th>
                            <th>Departemen</th>
                            <th>Posisi</th>
                            <th>Status</th>
                            <th>Tanggal Masuk</th>
                        </tr>
                    </thead>
                    <tbody id="empTableBody">
                        @forelse($employees as $emp)
                            <tr data-drawer-type="employee" data-drawer-id="{{ $emp->employeeId }}"
                                style="cursor:pointer;">
                                <td>
                                    <div class="avatar-sm">
                                        {{ strtoupper(substr($emp->fullName ?? 'E', 0, 2)) }}
                                    </div>
                                </td>
                                <td class="id-mono">{{ $emp->employeeId }}</td>
                                <td>
                                    <div class="cand-name fw-bold text-primary text-decoration-underline">
                                        {{ $emp->fullName }}</div>
                                    <div class="cand-sub">{{ $emp->personalEmail ?? $emp->workingEmail }}</div>
                                </td>
                                <td class="id-mono">{{ $emp->nikNpwp ?? '-' }}</td>
                                <td>{{ $emp->department ?? '-' }}</td>
                                <td class="fw-semibold text-navy">{{ $emp->jobPosition ?? '-' }}</td>
                                <td>
                                    <x-badge-status :status="$emp->statusEmployee" />
                                </td>
                                <td class="id-mono">{{ $emp->joinDate ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9">
                                    <div class="table-empty">
                                        <i class="bi bi-inbox"></i>
                                        <p>Belum ada data karyawan.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="panel-footer">
                <span id="empFooterCount">Menampilkan {{ $employees->count() }} data</span>
            </div>
        </div>

    </section>
@endsection

@section('scripts')
    <script>
        function openPromoteModal(emp) {
            if (!emp) return;
            document.getElementById('promoteProbEmpId').value = emp.employeeId;
            document.getElementById('promoteProbEmpName').textContent = emp.fullName || '-';
            document.getElementById('promoteProbPosition').textContent =
                `${emp.jobPosition || '-'} • ${emp.department || '-'}`;
            document.getElementById('promoteProbAvatar').textContent = (emp.fullName || 'E').substring(0, 2).toUpperCase();
            document.getElementById('promoteProbBadge').textContent = emp.statusEmployee || 'Contract';
            document.getElementById('formPromoteProbation').action = `/hr/probation`;
        }

        // Drawer click handler
        document.querySelectorAll('#empTableBody tr[data-drawer-type="employee"]').forEach(function(row) {
            row.addEventListener('click', function() {
                var empId = this.getAttribute('data-drawer-id');
                if (empId && typeof openEmployeeDrawer === 'function') {
                    openEmployeeDrawer(empId);
                }
            });
        });
    </script>
@endsection
