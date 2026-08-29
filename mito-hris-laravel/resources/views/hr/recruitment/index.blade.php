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
                <div class="export-btns">
                    @can('manage_recruitment')
                        <a href="{{ route('hr.export.candidates-csv') }}" class="btn btn-outline-success btn-export-csv">
                            <i class="bi bi-file-earmark-spreadsheet" aria-hidden="true"></i>Export CSV
                        </a>
                    @endcan
                    <button class="btn-refresh" id="btnRefresh" type="button" title="Muat ulang data"
                        aria-label="Muat ulang data" onclick="location.reload()">
                        <i class="bi bi-arrow-clockwise" aria-hidden="true"></i>
                    </button>
                </div>
            </div>

            <!-- FILTER BAR — basic -->
            <form action="{{ route('hr.recruitment.index') }}" method="GET" id="filterForm">
                <div class="filter-bar recruitment-filter-bar">
                    <div class="table-search">
                        <i class="bi bi-search"></i>
                        <input type="text" name="search" id="searchInput"
                            placeholder="Cari ID, nama, HP, email, posisi, kota..." value="{{ request('search') }}" />
                    </div>
                    <select class="filter-select" name="position" id="positionFilter" onchange="this.form.submit()">
                        <option value="">Semua Posisi</option>
                        @foreach ($positions ?? [] as $pos)
                            <option value="{{ $pos }}" {{ request('position') === $pos ? 'selected' : '' }}>
                                {{ $pos }}</option>
                        @endforeach
                    </select>
                    <select class="filter-select" name="sort" id="sortSelect" onchange="this.form.submit()">
                        <option value="newest" {{ request('sort', 'newest') === 'newest' ? 'selected' : '' }}>Terbaru
                        </option>
                        <option value="oldest" {{ request('sort') === 'oldest' ? 'selected' : '' }}>Terlama</option>
                    </select>
                    <select class="filter-select" name="gender" id="genderFilter" onchange="this.form.submit()">
                        <option value="">Semua Gender</option>
                        <option value="Laki-laki" {{ request('gender') === 'Laki-laki' ? 'selected' : '' }}>Laki-laki
                        </option>
                        <option value="Perempuan" {{ request('gender') === 'Perempuan' ? 'selected' : '' }}>Perempuan
                        </option>
                    </select>
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
                <span id="footerCount">Menampilkan
                    {{ $paginatedCandidates->count() > 0 ? ($currentPage - 1) * $perPage + 1 . '–' . min($currentPage * $perPage, $total) : 0 }}
                    dari {{ $total }} data</span>
                <x-pagination :currentPage="$currentPage" :total="$total" :perPage="$perPage" :route="'hr.recruitment.index'" :queryParams="[
                    'search' => $searchFilter,
                    'position' => $positionFilter,
                    'sort' => $sortFilter,
                    'gender' => $genderFilter,
                ]" />
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
