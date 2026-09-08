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
                {{-- Export XLSX — server-side dilindungi can:view_employees di route --}}
                @can('view_employees')
                    <div class="export-btns d-flex flex-wrap gap-2">
                        @can('manage_employees')
                            <button class="btn btn-sm text-white fw-semibold"
                                style="background:#005BAC;border:none;border-radius:8px;padding:6px 14px;font-size:13px"
                                type="button" data-bs-toggle="modal" data-bs-target="#addEmployeeModal">
                                <i class="bi bi-person-plus-fill me-1"></i>Tambah Karyawan
                            </button>
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
                        @endcan
                        <a href="{{ route('hr.export.employees-xlsx') }}"
                            class="btn btn-sm fw-semibold text-white"
                            style="background:#005BAC;border:none;border-radius:8px;padding:6px 14px;font-size:13px"
                            id="btnExportEmployeeXlsx"
                            title="Export seluruh data karyawan ke Excel (XLSX)">
                            <i class="bi bi-download me-1"></i>Export
                        </a>
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
                        {{-- The 'Probation' option above is intentionally retained:
                             Employee.Status no longer carries a 'Probation'
                             value, but the dropdown label is kept as a UX cue.
                             The count shown is the canonical active-probation
                             count (see EmployeeController::index stats).
                             Selecting 'Probation' here filters by
                             active-probation state via the index filter. --}}
                        <option value="Outsource" {{ ($statusFilter ?? '') === 'Outsource' ? 'selected' : '' }}>
                            Outsource ({{ $stats['outsource'] ?? 0 }})
                        </option>
                        <option value="Resigned" {{ ($statusFilter ?? '') === 'Resigned' ? 'selected' : '' }}>Resigned
                        </option>
                        <option value="Terminated" {{ ($statusFilter ?? '') === 'Terminated' ? 'selected' : '' }}>
                            Terminated</option>
                    </select>
                    <select class="filter-select" name="sort" id="empSortSelect" data-auto-submit="true">
                        <option value="">Sortir Nama</option>
                        <option value="name_asc" {{ ($sortFilter ?? '') === 'name_asc' ? 'selected' : '' }}>A-Z</option>
                        <option value="name_desc" {{ ($sortFilter ?? '') === 'name_desc' ? 'selected' : '' }}>Z-A</option>
                    </select>
                    <select class="filter-select" name="date_sort" id="empDateSort" data-auto-submit="true">
                        <option value="">Sortir Tanggal</option>
                        <option value="join_date_desc" {{ ($sortFilter ?? '') === 'join_date_desc' ? 'selected' : '' }}>Terbaru</option>
                        <option value="join_date_asc" {{ ($sortFilter ?? 'join_date_asc') === 'join_date_asc' ? 'selected' : '' }}>Terlama</option>
                    </select>
                    <div class="employee-filter-actions">
                        <a href="{{ route('hr.employees.index') }}" class="btn-reset-filter text-decoration-none"
                            title="Reset filter">
                            <i class="bi bi-arrow-counterclockwise"></i> Reset
                        </a>
                        <button class="btn-refresh" type="button" title="Muat ulang data" aria-label="Muat ulang data" data-refresh="page">
                            <i class="bi bi-arrow-repeat"></i>
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
                            <th class="sortable" data-sort="emp_id">
                                Employee ID
                                @if(($sortFilter ?? '') === 'emp_id_asc')
                                    <i class="bi bi-arrow-up"></i>
                                @elseif(($sortFilter ?? '') === 'emp_id_desc')
                                    <i class="bi bi-arrow-down"></i>
                                @else
                                    <i class="bi bi-arrow-down-up text-muted"></i>
                                @endif
                            </th>
                            <th class="sortable" data-sort="name">
                                Karyawan
                                @if(($sortFilter ?? 'name_asc') === 'name_asc')
                                    <i class="bi bi-arrow-up"></i>
                                @elseif(($sortFilter ?? '') === 'name_desc')
                                    <i class="bi bi-arrow-down"></i>
                                @else
                                    <i class="bi bi-arrow-down-up text-muted"></i>
                                @endif
                            </th>
                            <th class="sortable" data-sort="nik">
                                NIK
                                @if(($sortFilter ?? '') === 'nik_asc')
                                    <i class="bi bi-arrow-up"></i>
                                @elseif(($sortFilter ?? '') === 'nik_desc')
                                    <i class="bi bi-arrow-down"></i>
                                @else
                                    <i class="bi bi-arrow-down-up text-muted"></i>
                                @endif
                            </th>
                            <th class="sortable" data-sort="dept">
                                Departemen
                                @if(($sortFilter ?? '') === 'dept_asc')
                                    <i class="bi bi-arrow-up"></i>
                                @elseif(($sortFilter ?? '') === 'dept_desc')
                                    <i class="bi bi-arrow-down"></i>
                                @else
                                    <i class="bi bi-arrow-down-up text-muted"></i>
                                @endif
                            </th>
                            <th class="sortable" data-sort="division">
                                Divisi
                                @if(($sortFilter ?? '') === 'division_asc')
                                    <i class="bi bi-arrow-up"></i>
                                @elseif(($sortFilter ?? '') === 'division_desc')
                                    <i class="bi bi-arrow-down"></i>
                                @else
                                    <i class="bi bi-arrow-down-up text-muted"></i>
                                @endif
                            </th>
                            <th class="sortable" data-sort="position">
                                Posisi
                                @if(($sortFilter ?? '') === 'position_asc')
                                    <i class="bi bi-arrow-up"></i>
                                @elseif(($sortFilter ?? '') === 'position_desc')
                                    <i class="bi bi-arrow-down"></i>
                                @else
                                    <i class="bi bi-arrow-down-up text-muted"></i>
                                @endif
                            </th>
                            <th>Status</th>
                            <th class="sortable" data-sort="join_date">
                                Tanggal Masuk
                                @if(($sortFilter ?? '') === 'join_date_asc')
                                    <i class="bi bi-arrow-up"></i>
                                @elseif(($sortFilter ?? '') === 'join_date_desc')
                                    <i class="bi bi-arrow-down"></i>
                                @else
                                    <i class="bi bi-arrow-down-up text-muted"></i>
                                @endif
                            </th>
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
                                <td>{{ $emp->division ?? '-' }}</td>
                                <td class="fw-semibold text-navy">{{ $emp->jobPosition ?? '-' }}</td>
                                <td>
                                    @php
                                        $employeeStatus = strtolower(trim((string) ($emp->statusEmployee ?? '')));
                                        $employeeBadgeClass = match (true) {
                                            in_array($employeeStatus, ['permanent', 'pkwtt', 'active', 'aktif'], true) => 'accepted',
                                            in_array($employeeStatus, ['contract', 'pkwt', 'probation'], true) => 'hold',
                                            default => null,
                                        };
                                    @endphp
                                    @if ($employeeBadgeClass)
                                        <span class="badge-status {{ $employeeBadgeClass }}">{{ $emp->statusEmployee ?? '-' }}</span>
                                    @else
                                        <x-badge-status :status="$emp->statusEmployee" />
                                    @endif
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
        // Wire add-employee modal store URL (domain-agnostic)
        document.addEventListener('DOMContentLoaded', function () {
            var aeModal = document.getElementById('addEmployeeModal');
            if (aeModal) {
                aeModal.setAttribute('data-store-url', '{{ route("hr.employees.store") }}');
            }
        });

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

        // Auto-submit for all filter selects with data-auto-submit="true"
        document.addEventListener('DOMContentLoaded', function() {
            const sortSelect = document.getElementById('empSortSelect');
            const dateSort = document.getElementById('empDateSort');
            
            // When A-Z / Z-A dropdown changes, clear date sort to empty
            if (sortSelect) {
                sortSelect.addEventListener('change', function() {
                    if (this.value && dateSort) {
                        dateSort.value = ''; // Clear date sort selection
                    }
                    document.getElementById('empFilterForm').submit();
                });
            }
            
            // When Terbaru / Terlama dropdown changes, clear name sort to empty
            if (dateSort) {
                dateSort.addEventListener('change', function() {
                    if (this.value && sortSelect) {
                        sortSelect.value = ''; // Clear name sort
                    }
                    document.getElementById('empFilterForm').submit();
                });
            }
            
            // Other filters (dept, status) auto-submit
            document.querySelectorAll('select[data-auto-submit="true"]').forEach(function(select) {
                if (select.id !== 'empSortSelect' && select.id !== 'empDateSort') {
                    select.addEventListener('change', function() {
                        document.getElementById('empFilterForm').submit();
                    });
                }
            });
        });

        // Table header sort handler
        document.addEventListener('DOMContentLoaded', function() {
            const currentSort = '{{ $sortFilter ?? "join_date_asc" }}';
            
            document.querySelectorAll('.sortable').forEach(function(header) {
                header.style.cursor = 'pointer';
                header.style.userSelect = 'none';
                
                header.addEventListener('click', function(e) {
                    // Prevent click if clicking on row
                    if (e.target.closest('tbody')) return;
                    
                    const sortField = this.getAttribute('data-sort');
                    let newSort = sortField + '_asc';
                    
                    // Toggle direction if clicking same column
                    if (currentSort === sortField + '_asc') {
                        newSort = sortField + '_desc';
                    } else if (currentSort === sortField + '_desc') {
                        newSort = sortField + '_asc';
                    }
                    
                    const form = document.getElementById('empFilterForm');
                    const sortSelect = document.getElementById('empSortSelect');
                    const dateSort = document.getElementById('empDateSort');
                    
                    // If clicking join_date column, update date_sort dropdown
                    if (sortField === 'join_date') {
                        if (dateSort) {
                            dateSort.value = newSort;
                        }
                        if (sortSelect) {
                            sortSelect.value = ''; // Clear name sort
                        }
                    } 
                    // If clicking name column, update sort dropdown
                    else if (sortField === 'name') {
                        if (sortSelect) {
                            sortSelect.value = newSort;
                        }
                        if (dateSort) {
                            dateSort.value = ''; // Clear date sort
                        }
                    }
                    // For other columns, create temporary sort parameter
                    else {
                        // Clear both dropdowns
                        if (sortSelect) sortSelect.value = '';
                        if (dateSort) dateSort.value = '';
                        
                        // Add hidden input for this sort
                        let hiddenSort = form.querySelector('input[name="temp_sort"]');
                        if (!hiddenSort) {
                            hiddenSort = document.createElement('input');
                            hiddenSort.type = 'hidden';
                            hiddenSort.name = 'sort';
                            form.appendChild(hiddenSort);
                        }
                        hiddenSort.value = newSort;
                    }
                    
                    form.submit();
                });
            });
        });
    </script>
@endsection
