@extends('layouts.hr')

@section('title', 'Kandidat Hold - MITO HRIS')
@section('page-title', 'Kandidat Hold')
@section('page-subtitle', 'Daftar kandidat yang ditunda atau masuk talent pool')

@section('content')
    <!-- partials/HoldPage.html — Halaman Kandidat Hold (1:1 from GAS) -->
    <section class="page-section active" id="pageHold">

        <!-- Stat card -->
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-3">
                <div class="stat-card">
                    <div class="stat-icon bg-blue"><i class="bi bi-pause-circle-fill"></i></div>
                    <div>
                        <div class="stat-label">Total Hold</div>
                        <div class="stat-value text-navy" id="holdStatTotal">{{ $candidates->count() }}</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Table panel -->
        <div class="panel" id="holdTablePanel">
            <div class="panel-header">
                <div>
                    <h6>Kandidat Hold</h6>
                    <div class="panel-subtitle" id="holdPanelSubtitle">Menampilkan {{ $candidates->count() }} data</div>
                </div>
                <div class="export-btns">
                    <button class="btn-refresh" id="btnHoldRefresh" type="button" title="Muat ulang"
                        onclick="location.reload()">
                        <i class="bi bi-arrow-clockwise"></i>
                    </button>
                </div>
            </div>

            <!-- Filter bar -->
            <form action="{{ route('hr.recruitment.hold') }}" method="GET">
                <div class="filter-bar">
                    <div class="table-search">
                        <i class="bi bi-search"></i>
                        <input type="text" name="search" id="holdSearchInput"
                            placeholder="Cari ID, nama, posisi, kota..." value="{{ request('search') }}" />
                    </div>
                    <select class="filter-select" name="sort" id="holdSortSelect" onchange="this.form.submit()">
                        <option value="newest" {{ request('sort') === 'newest' ? 'selected' : '' }}>Terbaru</option>
                        <option value="oldest" {{ request('sort') === 'oldest' ? 'selected' : '' }}>Terlama</option>
                        <option value="name_asc" {{ request('sort') === 'name_asc' ? 'selected' : '' }}>Nama A-Z</option>
                        <option value="name_desc" {{ request('sort') === 'name_desc' ? 'selected' : '' }}>Nama Z-A</option>
                    </select>
                    <a href="{{ route('hr.recruitment.hold') }}" class="btn-reset-filter text-decoration-none"
                        id="btnHoldReset">
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
                            <th>Recruitment ID</th>
                            <th>Kandidat</th>
                            <th>Usia</th>
                            <th>Posisi</th>
                            <th>Alasan Hold</th>
                            <th>Follow Up</th>
                        </tr>
                    </thead>
                    <tbody id="holdTableBody">
                        @forelse($paginatedCandidates as $c)
                            <tr data-drawer-type="candidate" data-drawer-id="{{ $c->recruitmentId }}"
                                style="cursor:pointer;">
                                <td>
                                    <div class="avatar-sm">
                                        {{ strtoupper(substr($c->fullName ?? 'H', 0, 2)) }}
                                    </div>
                                </td>
                                <td class="id-mono">{{ $c->recruitmentId }}</td>
                                <td>
                                    <div class="cand-name fw-bold text-primary text-decoration-underline">
                                        {{ $c->fullName }}</div>
                                    <div class="cand-sub">{{ $c->email }}</div>
                                </td>
                                <td>{{ $c->age ? "{$c->age} th" : '-' }}</td>
                                <td class="fw-semibold text-navy">{{ $c->positionApplied }}</td>
                                <td>{{ $c->holdReason ?? '-' }}</td>
                                <td class="id-mono text-warning fw-semibold">{{ $c->holdFollowUpDate ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8">
                                    <div class="table-empty">
                                        <i class="bi bi-inbox"></i>
                                        <p>Belum ada kandidat dengan status Hold.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="panel-footer">
                <span id="holdFooterCount">Menampilkan {{ $paginatedCandidates->count() > 0 ? (($currentPage - 1) * $perPage + 1) . '–' . min($currentPage * $perPage, $total) : 0 }} dari {{ $total }} data</span>
                <x-pagination 
                    :currentPage="$currentPage" 
                    :total="$total" 
                    :perPage="$perPage" 
                    :route="'hr.recruitment.hold'"
                    :queryParams="['search' => request('search')]"
                />
            </div>
        </div>
    </section>
@endsection

@section('scripts')
    <script>
        document.querySelectorAll('#holdTableBody tr[data-drawer-type="candidate"]').forEach(function(row) {
            row.addEventListener('click', function(e) {
                e.stopPropagation();
                var id = this.getAttribute('data-drawer-id');
                if (id && typeof openCandidateDrawer === 'function') openCandidateDrawer(id);
            });
        });
    </script>
@endsection
