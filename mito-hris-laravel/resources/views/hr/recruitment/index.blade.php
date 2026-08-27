@extends('layouts.hr')

@section('title', 'Recruitment - MITO HRIS')
@section('page-title', 'Recruitment')
@section('page-subtitle', 'Manajemen pelamar dan pipeline seleksi')

@section('content')
    <!-- partials/RecruitmentPage.html — RECRUITMENT IN-PAGE SECTION (1:1 from GAS) -->
    <section class="page-section active" id="pageRecruitment">

        <!-- Stat card: kandidat baru (pending) -->
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-3">
                <div class="stat-card">
                    <div class="stat-icon bg-gold"><i class="bi bi-hourglass-split"></i></div>
                    <div>
                        <div class="stat-label">Kandidat Baru (Pending)</div>
                        <div class="stat-value" id="recStatPending">{{ $counts['new'] ?? 0 }}</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Table panel -->
        <div class="panel" id="tablePanel">
            <div class="panel-header">
                <div>
                    <h6>Data Kandidat</h6>
                    <div class="panel-subtitle" id="panelSubtitle">Menampilkan {{ $candidates->count() }} data kandidat
                    </div>
                </div>
                @can('manage_recruitment')
                <div class="export-btns">
                    <a href="{{ route('hr.export.candidates-csv') }}" class="btn btn-sm btn-outline-success">
                        <i class="bi bi-file-earmark-spreadsheet me-1"></i>Export CSV
                    </a>
                </div>
                @endcan
            </div>

            <!-- FILTER BAR — basic -->
            <form action="{{ route('hr.recruitment.index') }}" method="GET" id="filterForm">
                <div class="filter-bar">
                    <div class="table-search">
                        <i class="bi bi-search"></i>
                        <input type="text" name="search" id="searchInput"
                            placeholder="Cari ID, nama, HP, email, posisi, kota..." value="{{ request('search') }}" />
                    </div>
                    <select class="filter-select" name="status" id="statusFilter" onchange="this.form.submit()">
                        <option value="">Semua Status</option>
                        <option value="New" {{ request('status') === 'New' ? 'selected' : '' }}>New (Pending)</option>
                        <option value="Screening" {{ request('status') === 'Screening' ? 'selected' : '' }}>Screening
                        </option>
                        <option value="Interview HR" {{ request('status') === 'Interview HR' ? 'selected' : '' }}>Interview
                            HR</option>
                        <option value="Interview User" {{ request('status') === 'Interview User' ? 'selected' : '' }}>
                            Interview User</option>
                        <option value="Offering" {{ request('status') === 'Offering' ? 'selected' : '' }}>Offering</option>
                        <option value="Accepted" {{ request('status') === 'Accepted' ? 'selected' : '' }}>Accepted</option>
                        <option value="Hold" {{ request('status') === 'Hold' ? 'selected' : '' }}>Hold</option>
                        <option value="Blacklist" {{ request('status') === 'Blacklist' ? 'selected' : '' }}>Blacklist
                        </option>
                        <option value="Rejected" {{ request('status') === 'Rejected' ? 'selected' : '' }}>Rejected</option>
                    </select>
                    <select class="filter-select" name="position" id="positionFilter" onchange="this.form.submit()">
                        <option value="">Semua Posisi</option>
                        @foreach ($positions ?? [] as $pos)
                            <option value="{{ $pos }}" {{ request('position') === $pos ? 'selected' : '' }}>
                                {{ $pos }}</option>
                        @endforeach
                    </select>
                    <select class="filter-select" name="sort" id="sortSelect" onchange="this.form.submit()">
                        <option value="newest" {{ request('sort') === 'newest' ? 'selected' : '' }}>Terbaru</option>
                        <option value="oldest" {{ request('sort') === 'oldest' ? 'selected' : '' }}>Terlama</option>
                        <option value="name_asc" {{ request('sort') === 'name_asc' ? 'selected' : '' }}>Nama A-Z</option>
                        <option value="name_desc" {{ request('sort') === 'name_desc' ? 'selected' : '' }}>Nama Z-A</option>
                    </select>
                    <button class="btn-toggle-more" id="btnToggleMore" type="button">
                        <i class="bi bi-sliders"></i> Filter Lanjutan
                    </button>
                    <a href="{{ route('hr.recruitment.index') }}" class="btn-reset-filter text-decoration-none"
                        id="btnResetFilter">
                        <i class="bi bi-arrow-counterclockwise"></i> Reset
                    </a>
                    <button class="btn-refresh" id="btnRefresh" type="button" title="Muat ulang data"
                        onclick="location.reload()">
                        <i class="bi bi-arrow-clockwise"></i>
                    </button>
                </div>

                <!-- FILTER BAR — advanced (collapsible) -->
                <div class="filter-bar filter-bar-collapse" id="filterBarMore">
                    <select class="filter-select" name="gender" id="genderFilter">
                        <option value="">Semua Gender</option>
                        <option value="Laki-laki" {{ request('gender') === 'Laki-laki' ? 'selected' : '' }}>Laki-laki
                        </option>
                        <option value="Perempuan" {{ request('gender') === 'Perempuan' ? 'selected' : '' }}>Perempuan
                        </option>
                    </select>
                    <select class="filter-select" name="education" id="educationFilter">
                        <option value="">Semua Pendidikan</option>
                        <option value="SMA/SMK" {{ request('education') === 'SMA/SMK' ? 'selected' : '' }}>SMA/SMK</option>
                        <option value="D3" {{ request('education') === 'D3' ? 'selected' : '' }}>D3</option>
                        <option value="S1" {{ request('education') === 'S1' ? 'selected' : '' }}>S1</option>
                        <option value="S2" {{ request('education') === 'S2' ? 'selected' : '' }}>S2</option>
                    </select>
                    <input type="text" class="filter-select" name="city" id="cityFilter" placeholder="Kota KTP..."
                        value="{{ request('city') }}">
                    <button class="btn-reset-filter" type="submit" id="btnApplyMoreFilter">
                        <i class="bi bi-check2"></i> Terapkan
                    </button>
                </div>
            </form>

            <!-- TABLE -->
            <div class="table-responsive">
                <table class="table hr-table">
                    <thead>
                        <tr>
                            <th class="col-check">
                                <input type="checkbox" class="form-check-input" id="selectAll" title="Pilih semua" />
                            </th>
                            <th>Avatar</th>
                            <th>Recruitment ID</th>
                            <th>Kandidat</th>
                            <th>Usia</th>
                            <th>Gender</th>
                            <th>Posisi</th>
                            <th>Status</th>
                            <th>Tanggal Daftar</th>
                        </tr>
                    </thead>
                    <tbody id="tableBody">
                        @forelse($paginatedCandidates as $candidate)
                            <tr data-drawer-type="candidate" data-drawer-id="{{ $candidate->recruitmentId }}"
                                style="cursor:pointer;">
                                <td class="col-check" onclick="event.stopPropagation();">
                                    <input type="checkbox" class="form-check-input row-check"
                                        value="{{ $candidate->recruitmentId }}" />
                                </td>
                                <td>
                                    <div class="avatar-sm">
                                        {{ strtoupper(substr($candidate->fullName ?? 'U', 0, 2)) }}
                                    </div>
                                </td>
                                <td class="id-mono">
                                    {{ $candidate->recruitmentId }}
                                </td>
                                <td>
                                    <div class="cand-name  fw-bold text-primary text-decoration-underline">
                                        {{ $candidate->fullName }}</div>
                                    <div class="cand-sub font-monospace">{{ $candidate->email }}</div>
                                </td>
                                <td>{{ $candidate->age ? "{$candidate->age} th" : '-' }}</td>
                                <td>{{ $candidate->gender ?? '-' }}</td>
                                <td class="fw-semibold text-navy">{{ $candidate->positionApplied }}</td>
                                <td>
                                    <x-badge-status :status="$candidate->status" />
                                </td>
                                <td class="id-mono">{{ $candidate->createdDate ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9">
                                    <div class="table-empty">
                                        <i class="bi bi-inbox"></i>
                                        <p>Belum ada data kandidat.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="panel-footer">
                <span id="footerCount">Menampilkan {{ $paginatedCandidates->count() > 0 ? (($currentPage - 1) * $perPage + 1) . '–' . min($currentPage * $perPage, $total) : 0 }} dari {{ $total }} data</span>
                <x-pagination 
                    :currentPage="$currentPage" 
                    :total="$total" 
                    :perPage="$perPage" 
                    :route="'hr.recruitment.index'"
                    :queryParams="['status' => $statusFilter, 'search' => $searchFilter, 'city' => $cityFilter]"
                />
            </div>
        </div>

    </section>
@endsection

@section('scripts')
    <script>
        // Drawer click handler for candidates
        document.querySelectorAll('#tableBody tr[data-drawer-type="candidate"]').forEach(function(row) {
            row.addEventListener('click', function(e) {
                e.stopPropagation();
                if (e.target.type === 'checkbox') return;
                var id = this.getAttribute('data-drawer-id');
                if (id && typeof openCandidateDrawer === 'function') {
                    openCandidateDrawer(id);
                }
            });
        });

        document.addEventListener('DOMContentLoaded', () => {
            const btnToggleMore = document.getElementById('btnToggleMore');
            const filterBarMore = document.getElementById('filterBarMore');
            if (btnToggleMore && filterBarMore) {
                btnToggleMore.addEventListener('click', () => {
                    filterBarMore.classList.toggle('show');
                });
            }

            const selectAll = document.getElementById('selectAll');
            const checkboxes = document.querySelectorAll('.row-check');
            if (selectAll) {
                selectAll.addEventListener('change', () => {
                    checkboxes.forEach(cb => cb.checked = selectAll.checked);
                });
            }
        });
    </script>
@endsection
