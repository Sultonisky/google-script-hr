@extends('layouts.hr')

@section('title', 'Kandidat Blacklist - MITO HRIS')
@section('page-title', 'Kandidat Blacklist')
@section('page-subtitle', 'Daftar hitam pelamar yang diblokir dari sistem')

@section('content')
    <!-- partials/BlacklistPage.html — Halaman Kandidat Blacklist (1:1 from GAS) -->
    <section class="page-section active" id="pageBlacklist">

        <!-- Stat card -->
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-3">
                <div class="stat-card">
                    <div class="stat-icon bg-red"><i class="bi bi-slash-circle-fill"></i></div>
                    <div>
                        <div class="stat-label">Total Blacklist</div>
                        <div class="stat-value text-navy" id="blStatTotal">{{ $candidates->count() }}</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Table panel -->
        <div class="panel" id="blTablePanel">
            <div class="panel-header">
                <div>
                    <h6>Kandidat Blacklist</h6>
                    <div class="panel-subtitle" id="blPanelSubtitle">Menampilkan {{ $candidates->count() }} data</div>
                </div>
                <div class="export-btns">
                    <button class="btn-refresh" id="btnBlRefresh" type="button" title="Muat ulang"
                        onclick="location.reload()">
                        <i class="bi bi-arrow-clockwise"></i>
                    </button>
                </div>
            </div>

            <!-- Filter bar -->
            <form action="{{ route('hr.recruitment.blacklist') }}" method="GET">
                <div class="filter-bar">
                    <div class="table-search">
                        <i class="bi bi-search"></i>
                        <input type="text" name="search" id="blSearchInput"
                            placeholder="Cari ID, nama, posisi, alasan..." value="{{ request('search') }}" />
                    </div>
                    <select class="filter-select" name="sort" id="blSortSelect" onchange="this.form.submit()">
                        <option value="newest" {{ request('sort') === 'newest' ? 'selected' : '' }}>Terbaru</option>
                        <option value="oldest" {{ request('sort') === 'oldest' ? 'selected' : '' }}>Terlama</option>
                        <option value="name_asc" {{ request('sort') === 'name_asc' ? 'selected' : '' }}>Nama A-Z</option>
                        <option value="name_desc" {{ request('sort') === 'name_desc' ? 'selected' : '' }}>Nama Z-A</option>
                    </select>
                    <a href="{{ route('hr.recruitment.blacklist') }}" class="btn-reset-filter text-decoration-none"
                        id="btnBlReset">
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
                            <th>Alasan Blacklist</th>
                            <th>Tgl Blacklist</th>
                            <th>Diproses Oleh</th>
                        </tr>
                    </thead>
                    <tbody id="blTableBody">
                        @forelse($paginatedCandidates as $c)
                            <tr data-drawer-type="candidate" data-drawer-id="{{ $c->recruitmentId }}"
                                style="cursor:pointer;">
                                <td>
                                    <div class="avatar-sm" style="background:rgba(220,38,38,0.1);color:#dc3545;">
                                        {{ strtoupper(substr($c->fullName ?? 'B', 0, 2)) }}
                                    </div>
                                </td>
                                <td class="id-mono">{{ $c->recruitmentId }}</td>
                                <td>
                                    <div class="cand-name fw-bold text-danger text-decoration-underline">
                                        {{ $c->fullName }}
                                    </div>
                                    <div class="cand-sub">{{ $c->email }}</div>
                                </td>
                                <td>{{ $c->age ? "{$c->age} th" : '-' }}</td>
                                <td class="fw-semibold text-navy">{{ $c->positionApplied }}</td>
                                <td class="text-danger fw-semibold">{{ $c->blacklistReason ?? '-' }}</td>
                                <td class="id-mono">{{ $c->blacklistDate ?? '-' }}</td>
                                <td>{{ $c->blacklistUpdatedBy ?? 'HR Team' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9">
                                    <div class="table-empty">
                                        <i class="bi bi-inbox"></i>
                                        <p>Belum ada kandidat dengan status Blacklist.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="panel-footer">
                <span id="blFooterCount">Menampilkan {{ $paginatedCandidates->count() > 0 ? (($currentPage - 1) * $perPage + 1) . '–' . min($currentPage * $perPage, $total) : 0 }} dari {{ $total }} data</span>
                <x-pagination 
                    :currentPage="$currentPage" 
                    :total="$total" 
                    :perPage="$perPage" 
                    :route="'hr.recruitment.blacklist'"
                    :queryParams="['search' => request('search')]"
                />
            </div>
        </div>
    </section>
@endsection

@section('scripts')
    <script>
  document.querySelectorAll('#blTableBody tr[data-drawer-type="candidate"]').forEach(function(row) {
    row.addEventListener('click', function(e) {
      e.stopPropagation();
      var id = this.getAttribute('data-drawer-id');
      if (id && typeof openCandidateDrawer === 'function') openCandidateDrawer(id);
    });
  });
    </script>
@endsection
