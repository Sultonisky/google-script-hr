@extends('layouts.hr')

@section('title', 'Karyawan Outsource - MITO HRIS')
@section('page-title', 'Karyawan Outsource')
@section('page-subtitle', 'Manajemen tenaga kerja alih daya (Outsource)')

@section('content')
    <section class="page-section active" id="pageOutsource">

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
                {{-- Export XLSX — server-side dilindungi can:view_outsource di route --}}
                @can('view_outsource')
                    <div class="export-btns d-flex flex-wrap gap-2">
                        @can('manage_outsource')
                            <button class="btn btn-sm fw-semibold text-white"
                                style="background:#0d6efd;border:none;border-radius:8px;padding:6px 14px;font-size:13px"
                                type="button"
                                data-bs-toggle="modal"
                                data-bs-target="#outsourceContractModal"
                                id="btnProsesKontrakOutsource">
                                <i class="bi bi-file-earmark-text me-1"></i>Proses Kontrak
                            </button>
                            <button class="btn btn-sm fw-semibold text-white"
                                style="background:#198754;border:none;border-radius:8px;padding:6px 14px;font-size:13px"
                                type="button"
                                data-bs-toggle="modal"
                                data-bs-target="#outsourceImportModal"
                                id="btnImportOutsource">
                                <i class="bi bi-file-earmark-spreadsheet me-1"></i>Import Excel
                            </button>
                            <button class="btn btn-sm fw-semibold text-white"
                                style="background:#eb1c24;border:none;border-radius:8px;padding:6px 14px;font-size:13px"
                                type="button"
                                id="btnAddOutsource">
                                <i class="bi bi-building-fill-gear me-1"></i>Tambah Outsource
                            </button>
                        @endcan
                        <a href="{{ route('hr.export.outsource-xlsx') }}"
                            class="btn btn-sm fw-semibold text-white"
                            style="background:#005BAC;border:none;border-radius:8px;padding:6px 14px;font-size:13px"
                            id="btnExportOutsourceXlsx"
                            title="Export seluruh data karyawan outsource ke Excel (XLSX)">
                            <i class="bi bi-download me-1"></i>Export
                        </a>
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
                            placeholder="Cari nama, ID, jabatan, lokasi..." value="{{ $searchFilter ?? '' }}" />
                    </div>
                    <select class="filter-select" name="vendor" id="osVendorSelect" data-auto-submit="true">
                        <option value="">Semua Vendor</option>
                        @foreach (config('hris.outsource.vendors', []) as $vendorName)
                            <option value="{{ $vendorName }}" {{ ($vendorFilter ?? '') === $vendorName ? 'selected' : '' }}>
                                {{ $vendorName }}</option>
                        @endforeach
                    </select>
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

            <div class="table-responsive">
                <table class="table hr-table">
                    <thead>
                        <tr>
                            <th>Avatar</th>
                            <th>Outsource ID</th>
                            <th>Nama</th>
                            <th>Jabatan / Lokasi</th>
                            <th>Vendor</th>
                            <th>Tgl Join Mito</th>
                            <th>Tgl Akhir Kontrak (StaffInc)</th>
                        </tr>
                    </thead>
                    <tbody id="osTableBody">
                        @forelse($outsources as $os)
                            <tr data-drawer-type="outsource" data-drawer-id="{{ $os->outsourceId }}"
                                style="cursor:pointer;">
                                <td>
                                    <div class="avatar-sm">
                                        {{ strtoupper(substr($os->fullName ?? 'O', 0, 2)) }}
                                    </div>
                                </td>
                                <td class="id-mono fw-bold">{{ $os->outsourceId }}</td>
                                <td>
                                    <div class="cand-name fw-bold text-primary text-decoration-underline ">
                                        {{ $os->fullName }}</div>
                                    <div class="cand-sub">{{ $os->email }}</div>
                                </td>
                                <td>{{ $os->jobTitle ?? '-' }} <small
                                        class="text-muted d-block">{{ collect([$os->workLocation, $os->workCity])->filter()->implode(' · ') ?: '-' }}</small></td>
                                <td>{{ $os->vendor ?? '-' }}</td>
                                <td class="id-mono">{{ $os->mitoJoinDate ?? '-' }}</td>
                                <td class="id-mono">{{ $os->contractEndDate ?? '-' }}</td>
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
                        :queryParams="['search' => $searchFilter, 'vendor' => $vendorFilter, 'sort' => $sortFilter, 'per_page' => $perPage]" />
                @endif
            </div>
        </div>

    </section>

    @can('manage_outsource')
        @include('hr.partials.outsource-contract-modal')
        @include('hr.partials.outsource-form-modal')
        @include('hr.partials.outsource-import-modal')
    @endcan
@endsection

@section('scripts')
    <script>
        var btnAddOutsource = document.getElementById('btnAddOutsource');
        if (btnAddOutsource) {
            btnAddOutsource.addEventListener('click', function() {
                if (typeof window.openOutsourceForm === 'function') window.openOutsourceForm(null);
            });
        }

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
