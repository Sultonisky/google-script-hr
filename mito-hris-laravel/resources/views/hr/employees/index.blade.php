@extends('layouts.hr')

@section('title', 'Master Data Karyawan - MITO HRIS')
@section('page-title', 'Master Data Karyawan')
@section('page-subtitle', 'Daftar master seluruh karyawan aktif dan riwayat kontrak')

@section('content')
    <!-- partials/EmployeeContent.html — EMPLOYEE PAGE CONTENT (1:1 from GAS) -->
    <section class="page-section active" id="pageEmployee">

        <!-- Stat Cards -->
        <div class="row g-3 mb-4" id="employeeStatsPanel">
            <div class="col-6 col-md-2">
                <div class="stat-card">
                    <div class="stat-icon bg-blue"><i class="bi bi-people-fill"></i></div>
                    <div>
                        <div class="stat-label">Total Karyawan</div>
                        <div class="stat-value text-navy" id="empStatTotal">{{ $stats['total'] ?? 0 }}</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-2">
                <div class="stat-card">
                    <div class="stat-icon bg-green"><i class="bi bi-person-check-fill"></i></div>
                    <div>
                        <div class="stat-label">Permanent</div>
                        <div class="stat-value text-navy" id="empStatPermanent">{{ $stats['permanent'] ?? 0 }}</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-2">
                <div class="stat-card">
                    <div class="stat-icon bg-blue"><i class="bi bi-file-earmark-person-fill"></i></div>
                    <div>
                        <div class="stat-label">Contract</div>
                        <div class="stat-value text-navy" id="empStatContract">{{ $stats['contract'] ?? 0 }}</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-2">
                <div class="stat-card">
                    <div class="stat-icon bg-gold"><i class="bi bi-hourglass-split"></i></div>
                    <div>
                        <div class="stat-label">Probation</div>
                        <div class="stat-value text-navy" id="empStatProbation">{{ $stats['probation'] ?? 0 }}</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-2">
                <div class="stat-card">
                    <div class="stat-icon bg-cyan"><i class="bi bi-building-fill"></i></div>
                    <div>
                        <div class="stat-label">Outsource</div>
                        <div class="stat-value text-navy" id="empStatOutsource">{{ $stats['outsource'] ?? 0 }}</div>
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
                @can('manage_employees')
                    <div class="export-btns d-flex flex-wrap gap-2">
                        <button class="btn btn-sm text-white fw-semibold"
                            style="background:#166534;border:none;border-radius:8px;padding:6px 14px;font-size:13px"
                            type="button" data-bs-toggle="modal" data-bs-target="#empImportModal">
                            <i class="bi bi-upload me-1"></i>Import
                        </button>
                        <button class="btn btn-sm fw-semibold text-white"
                            style="background:#0B2540;border:none;border-radius:8px;padding:6px 14px;font-size:13px"
                            type="button" data-bs-toggle="modal" data-bs-target="#rotationModal">
                            <i class="bi bi-arrow-left-right me-1"></i>Rotasi
                        </button>
                        <button class="btn btn-sm fw-semibold text-white"
                            style="background:#d97706;border:none;border-radius:8px;padding:6px 14px;font-size:13px"
                            type="button" data-bs-toggle="modal" data-bs-target="#offContractModal">
                            <i class="bi bi-calendar-x me-1"></i>Off Contract
                        </button>
                        <button class="btn btn-sm fw-semibold text-white"
                            style="background:#eb1c24;border:none;border-radius:8px;padding:6px 14px;font-size:13px"
                            type="button" data-bs-toggle="modal" data-bs-target="#offboardingModal">
                            <i class="bi bi-box-arrow-right me-1"></i>Offboarding
                        </button>
                    </div>
                @endcan
            </div>

            <!-- FILTER BAR -->
            <form action="{{ route('hr.employees.index') }}" method="GET" id="empFilterForm">
                {{-- Reset page to 1 on any filter change --}}
                <input type="hidden" name="page" value="1">
                <div class="filter-bar employee-filter-bar">
                    <div class="table-search">
                        <i class="bi bi-search"></i>
                        <input type="text" name="search" id="empSearchInput"
                            placeholder="Cari nama, NIK, email, posisi, dept..." value="{{ $searchFilter ?? '' }}" />
                    </div>
                    <select class="filter-select" name="department" id="empDeptFilter" data-auto-submit="true">
                        <option value="">Semua Dept</option>
                        @foreach ($departments ?? [] as $dept)
                            <option value="{{ $dept }}" {{ ($departmentFilter ?? '') === $dept ? 'selected' : '' }}>
                                {{ $dept }}</option>
                        @endforeach
                    </select>
                    {{--
                        Status filter values MUST match actual values stored in Employee Sheet.
                        GAS generates: Permanent, Contract, Probation, Outsource (via empStatEmp array).
                        Legacy sheet data may use PKWTT/PKWT — both are included for coverage.
                        Controller does strtolower(trim()) comparison so all values are matched case-insensitively.
                    --}}
                    <select class="filter-select" name="status" id="empStatusFilter" data-auto-submit="true">
                        <option value="">
                            Semua Status ({{ $stats['total'] ?? 0 }})
                        </option>
                        <option value="Permanent" {{ ($statusFilter ?? '') === 'Permanent' ? 'selected' : '' }}>
                            Permanent ({{ $stats['permanent'] ?? 0 }})
                        </option>
                        <option value="Contract" {{ ($statusFilter ?? '') === 'Contract' ? 'selected' : '' }}>
                            Contract ({{ $stats['contract'] ?? 0 }})
                        </option>
                        <option value="Probation" {{ ($statusFilter ?? '') === 'Probation' ? 'selected' : '' }}>
                            Probation ({{ $stats['probation'] ?? 0 }})
                        </option>
                        <option value="Outsource" {{ ($statusFilter ?? '') === 'Outsource' ? 'selected' : '' }}>
                            Outsource ({{ $stats['outsource'] ?? 0 }})
                        </option>
                        <option value="Resigned" {{ ($statusFilter ?? '') === 'Resigned' ? 'selected' : '' }}>Resigned
                        </option>
                        <option value="Terminated" {{ ($statusFilter ?? '') === 'Terminated' ? 'selected' : '' }}>
                            Terminated</option>
                    </select>
                    <select class="filter-select" name="sort" id="empSortSelect" data-auto-submit="true">
                        <option value="name_asc" selected>Nama A-Z</option>
                    </select>
                    <select class="filter-select employee-per-page" name="per_page" id="empPerPage"
                        data-auto-submit="true">
                        <option value="10" {{ ($perPage ?? 10) == 10 ? 'selected' : '' }}>10 / hal</option>
                        <option value="20" {{ ($perPage ?? 10) == 20 ? 'selected' : '' }}>20 / hal</option>
                        <option value="50" {{ ($perPage ?? 10) == 50 ? 'selected' : '' }}>50 / hal</option>
                    </select>
                    <div class="employee-filter-actions">
                        <a href="{{ route('hr.employees.index') }}" class="btn-reset-filter text-decoration-none"
                            title="Reset filter">
                            <i class="bi bi-arrow-counterclockwise"></i> Reset
                        </a>
                        <button class="btn-refresh" type="button" title="Muat ulang data" aria-label="Muat ulang data" data-refresh="page">
                            <i class="bi bi-arrow-clockwise"></i>
                        </button>
                    </div>
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
                <span id="empFooterCount">
                    @if ($total > 0)
                        Menampilkan {{ ($currentPage - 1) * $perPage + 1 }}–{{ min($currentPage * $perPage, $total) }}
                        dari {{ $total }} data
                    @else
                        Tidak ada data
                    @endif
                </span>
                @if ($total > $perPage)
                    <x-pagination :currentPage="$currentPage" :total="$total" :perPage="$perPage" :route="'hr.employees.index'"
                        :queryParams="[
                            'search' => $searchFilter,
                            'status' => $statusFilter,
                            'department' => $departmentFilter,
                            'sort' => $sortFilter,
                            'per_page' => $perPage,
                        ]" />
                @endif
            </div>
        </div>

    </section>

    {{-- Semua modal (Rotasi, Off Contract, Offboarding, Promote Probation) sudah di-include --}}
    {{-- via layouts/hr.blade.php → hr.partials.rotation-modal, off-contract-modal, entity-modals --}}
    {{-- JANGAN include lagi di sini — akan menyebabkan duplikasi modal ID di DOM --}}
@endsection

@section('scripts')
    <script>
        // Drawer click handler
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('#empTableBody tr[data-drawer-type="employee"]').forEach(function(row) {
                row.addEventListener('click', function() {
                    var empId = this.getAttribute('data-drawer-id');
                    if (empId && typeof openEmployeeDrawer === 'function') {
                        openEmployeeDrawer(empId);
                    }
                });
            });
        });

        // Search submit on Enter
        var empSearchInput = document.getElementById('empSearchInput');
        if (empSearchInput) {
            empSearchInput.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    document.getElementById('empFilterForm').submit();
                }
            });
        }
    </script>
@endsection
