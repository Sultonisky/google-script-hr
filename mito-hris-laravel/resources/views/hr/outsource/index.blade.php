@extends('layouts.hr')

@section('title', 'Karyawan Outsource - MITO HRIS')
@section('page-title', 'Karyawan Outsource')
@section('page-subtitle', 'Manajemen tenaga kerja alih daya (Outsource)')

@section('content')
    <!-- partials/OutsourceTable.html — OUTSOURCE DATA PANEL (1:1 from GAS) -->
    <section class="page-section active" id="pageOutsource">

        <!-- Outsource Stat Cards (1:1 from GAS) -->
        <div class="row g-3 mb-3 mt-2" id="outsourceStats">
            <div class="col-6 col-md-3">
                <div class="stat-card">
                    <div class="stat-icon bg-cyan"><i class="bi bi-people-fill"></i></div>
                    <div>
                        <div class="stat-label">Total Outsource</div>
                        <div class="stat-value text-navy" id="osStatTotal">{{ $stats['total'] ?? 0 }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="panel mt-2" id="outsourcePanel">
            <div class="panel-header">
                <div>
                    <h6>Data Karyawan Outsource</h6>
                    <div class="panel-subtitle" id="osPanelSubtitle">
                        @if ($total > 0)
                            Menampilkan {{ ($currentPage - 1) * $perPage + 1 }}–{{ min($currentPage * $perPage, $total) }}
                            dari {{ $total }} data
                        @else
                            Tidak ada data karyawan outsource
                        @endif
                    </div>
                </div>
                @can('manage_employees')
                    <div class="export-btns d-flex flex-wrap gap-2">
                        <button class="btn btn-sm fw-semibold text-white"
                            style="background:#0d6efd;border:none;border-radius:8px;padding:6px 14px;font-size:13px"
                            type="button"
                            data-bs-toggle="modal"
                            data-bs-target="#outsourceContractModal"
                            id="btnProsesKontrakOutsource">
                            <i class="bi bi-file-earmark-text me-1"></i>Proses Kontrak
                        </button>
                        <button class="btn btn-sm fw-semibold text-white"
                            style="background:#eb1c24;border:none;border-radius:8px;padding:6px 14px;font-size:13px"
                            type="button"
                            data-bs-toggle="modal"
                            data-bs-target="#addEmployeeModal"
                            id="btnAddOutsource">
                            <i class="bi bi-building-fill-gear me-1"></i>Tambah Outsource
                        </button>
                    </div>
                @endcan
            </div>

            <!-- Filter bar -->
            <form action="{{ route('hr.outsource.index') }}" method="GET" id="osFilterForm">
                {{-- Reset page to 1 on any filter change --}}
                <input type="hidden" name="page" value="1">
                <div class="filter-bar">
                    <div class="table-search">
                        <i class="bi bi-search"></i>
                        <input type="text" name="search" id="osSearchInput"
                            placeholder="Cari nama, ID, vendor, posisi..." value="{{ $searchFilter ?? '' }}" />
                    </div>
                    <select class="filter-select" name="sort" id="osSortSelect" data-auto-submit="true">
                        <option value="name_asc" {{ ($sortFilter ?? 'name_asc') === 'name_asc' ? 'selected' : '' }}>Nama A-Z
                        </option>
                        <option value="name_desc" {{ ($sortFilter ?? 'name_asc') === 'name_desc' ? 'selected' : '' }}>Nama
                            Z-A</option>
                    </select>
                    <select class="filter-select" name="order" id="osOrderSelect" data-auto-submit="true">
                        <option value="" {{ empty($orderFilter) ? 'selected' : '' }}>Urutan</option>
                        <option value="newest" {{ ($orderFilter ?? '') === 'newest' ? 'selected' : '' }}>Terbaru</option>
                        <option value="oldest" {{ ($orderFilter ?? '') === 'oldest' ? 'selected' : '' }}>Terlama</option>
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
                <span id="osFooterCount">
                    @if ($total > 0)
                        Menampilkan {{ ($currentPage - 1) * $perPage + 1 }}–{{ min($currentPage * $perPage, $total) }}
                        dari {{ $total }} data
                    @else
                        Tidak ada data
                    @endif
                </span>
                @if ($total > $perPage)
                    <x-pagination :currentPage="$currentPage" :total="$total" :perPage="$perPage" :route="'hr.outsource.index'"
                        :queryParams="['search' => $searchFilter, 'sort' => $sortFilter, 'per_page' => $perPage]" />
                @endif
            </div>
        </div>

    </section>

    @can('manage_employees')
        @include('hr.partials.outsource-contract-modal')
    @endcan
@endsection

@section('scripts')
    <script>
        // Wire add-employee modal untuk mode outsource
        document.addEventListener('DOMContentLoaded', function () {
            var aeModal = document.getElementById('addEmployeeModal');
            if (aeModal) {
                aeModal.setAttribute('data-mode', 'outsource');
                aeModal.setAttribute('data-store-url', '{{ route("hr.outsource.store") }}');
            }
        });

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

        // Search submit on Enter
        var osSearchInput = document.getElementById('osSearchInput');
        if (osSearchInput) {
            osSearchInput.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    document.getElementById('osFilterForm').submit();
                }
            });
        }
    </script>
@endsection
