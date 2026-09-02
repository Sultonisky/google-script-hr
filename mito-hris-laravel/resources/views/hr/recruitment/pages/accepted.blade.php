@extends('layouts.hr')

@section('title', 'Kandidat Accepted - MITO HRIS')
@section('page-title', 'Kandidat Diterima')
@section('page-subtitle', 'Daftar kandidat yang telah lolos tahap seleksi dan diterima')

@section('content')
    <!-- partials/AcceptedPage.html — Halaman Kandidat Accepted (1:1 from GAS) -->
    <section class="page-section active" id="pageAccepted">

        <!-- Stat card (1:1 from GAS) -->
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-4">
                <div class="stat-card">
                    <div class="stat-icon bg-green"><i class="bi bi-check-circle-fill"></i></div>
                    <div>
                        <div class="stat-label">Total Diterima</div>
                        <div class="stat-value text-navy" id="accStatTotal">{{ $candidates->count() }}</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-4">
                <div class="stat-card">
                    <div class="stat-icon bg-blue"><i class="bi bi-file-earmark-text-fill"></i></div>
                    <div>
                        <div class="stat-label">Total Offering Letter</div>
                        <div class="stat-value text-navy" id="accStatOffering">
                            {{ $stats['offering'] ?? $candidates->count() }}</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-4">
                <div class="stat-card">
                    <div class="stat-icon bg-navy"><i class="bi bi-file-earmark-check-fill"></i></div>
                    <div>
                        <div class="stat-label">Kontrak PKWT Selesai</div>
                        <div class="stat-value text-navy" id="accStatOnboarding">{{ $stats['onboarding'] ?? 0 }}</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Table panel -->
        <div class="panel" id="accTablePanel">
            <div class="panel-header">
                <div>
                    <h6>Kandidat Diterima</h6>
                    <div class="panel-subtitle" id="accPanelSubtitle">Menampilkan {{ $candidates->count() }} kandidat
                        berstatus Accepted</div>
                </div>
                <div class="export-btns accepted-header-actions d-flex flex-wrap gap-2">
                    @can('create_offering')
                        <button class="btn btn-sm text-white fw-semibold accepted-offering-btn"
                            style="background:var(--color-primary, #eb1c24);border:none;border-radius:8px;padding:6px 14px;font-size:13px"
                            type="button" data-bs-toggle="modal" data-bs-target="#offeringModal">
                            <i class="bi bi-file-earmark-text me-1"></i>Buat Offering Letter
                        </button>
                        <div class="accepted-secondary-actions">
                            <button class="btn btn-sm text-white fw-semibold accepted-contract-btn"
                                style="background:#166534;border:none;border-radius:8px;padding:6px 14px;font-size:13px"
                                type="button" data-bs-toggle="modal" data-bs-target="#onboardingModal">
                                <i class="bi bi-file-earmark-check-fill me-1"></i>Proses Kontrak PKWT
                            </button>
                            <button class="btn-refresh accepted-refresh-btn accepted-refresh-header" type="button"
                                title="Muat ulang" aria-label="Muat ulang data" data-refresh="page">
                                <i class="bi bi-arrow-clockwise" aria-hidden="true"></i>
                            </button>
                        </div>
                    @endcan
                </div>
            </div>

            <!-- Filter bar -->
            <form action="{{ route('hr.recruitment.accepted') }}" method="GET">
                <div class="filter-bar accepted-filter-bar">
                    <div class="table-search">
                        <i class="bi bi-search"></i>
                        <input type="text" name="search" id="accSearchInput"
                            placeholder="Cari ID, nama, posisi, Employee ID..." value="{{ request('search') }}" />
                    </div>
                    <select class="filter-select" name="sort" id="accSortSelect" data-auto-submit="true">
                        <option value="newest" {{ $sortFilter === 'newest' ? 'selected' : '' }}>Terbaru</option>
                        <option value="oldest" {{ $sortFilter === 'oldest' ? 'selected' : '' }}>Terlama</option>
                    </select>
                    <select class="filter-select" name="offering" id="accOfferingFilter" data-auto-submit="true">
                        <option value="">Offering Letter</option>
                        <option value="exists" {{ $offeringFilter === 'exists' ? 'selected' : '' }}>Sudah Ada</option>
                        <option value="missing" {{ $offeringFilter === 'missing' ? 'selected' : '' }}>Belum Ada</option>
                    </select>
                    <select class="filter-select" name="response" id="accResponseFilter" data-auto-submit="true">
                        <option value="">Respon Offering</option>
                        <option value="Menunggu" {{ $responseFilter === 'Menunggu' ? 'selected' : '' }}>Menunggu</option>
                        <option value="Diterima" {{ $responseFilter === 'Diterima' ? 'selected' : '' }}>Diterima</option>
                        <option value="Ditolak" {{ $responseFilter === 'Ditolak' ? 'selected' : '' }}>Ditolak</option>
                        <option value="none" {{ $responseFilter === 'none' ? 'selected' : '' }}>Belum Ada Respon</option>
                    </select>
                    <select class="filter-select" name="contract" id="accContractFilter" data-auto-submit="true">
                        <option value="">Proses Kontrak</option>
                        <option value="processed" {{ $contractFilter === 'processed' ? 'selected' : '' }}>Sudah Diproses
                        </option>
                        <option value="pending" {{ $contractFilter === 'pending' ? 'selected' : '' }}>Belum Diproses
                        </option>
                    </select>
                    <div class="accepted-filter-actions">
                        <a href="{{ route('hr.recruitment.accepted') }}" class="btn-reset-filter text-decoration-none"
                            id="btnAccReset">
                            <i class="bi bi-arrow-counterclockwise"></i> Reset
                        </a>
                        <button class="btn-refresh accepted-refresh-btn accepted-refresh-filter" id="btnAccRefresh"
                            type="button" title="Muat ulang" aria-label="Muat ulang data" data-refresh="page">
                            <i class="bi bi-arrow-clockwise" aria-hidden="true"></i>
                        </button>
                    </div>
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
                            <th>Offering Letter</th>
                            <th>Tgl Diterima</th>
                            <th>Diproses Oleh</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="accTableBody">
                        @forelse($paginatedCandidates as $c)
                            <tr data-drawer-type="candidate" data-drawer-id="{{ $c->recruitmentId }}"
                                style="cursor:pointer;">
                                <td>
                                    <div class="avatar-sm">
                                        {{ strtoupper(substr($c->fullName ?? 'A', 0, 2)) }}
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
                                <td>
                                    @if (!empty($c->offeringCreated) && $c->offeringCreated !== '-')
                                        <span class="badge-status accepted"
                                            style="font-size:10px;padding:2px 8px;white-space:nowrap">
                                            <i class="bi bi-check2-circle me-1"></i>{{ $c->offeringCreated }}
                                        </span>
                                    @else
                                        <span style="color:#aaa;font-size:12px">-</span>
                                    @endif
                                </td>
                                <td class="id-mono"><small>{{ $c->processedDate ?? ($c->createdDate ?? '-') }}</small>
                                </td>
                                <td><small>{{ $c->processedBy ?? '-' }}</small></td>
                                <td>
                                    <button class="btn btn-sm btn-outline-secondary btn-status-move"
                                        data-id="{{ $c->recruitmentId }}" data-from="Accepted" title="Ubah Status"
                                        data-action="open-move-status-modal" data-recruitment-id="{{ $c->recruitmentId }}" data-from-status="Accepted">
                                        <i class="bi bi-arrow-repeat"></i>
                                    </button>
                                    @can('create_offering')
                                        @if (!empty($c->offeringCreated) && $c->offeringCreated !== '-')
                                            <button class="btn btn-sm btn-offering-preview" data-id="{{ $c->recruitmentId }}"
                                                title="Dibuat: {{ $c->offeringCreated }} oleh {{ $c->offeringCreatedBy ?? '-' }}"
                                                style="background:#e8f4e8;color:#166534;border:1px solid #bbf7d0;border-radius:6px;padding:4px 8px"
                                                data-action="open-offering-preview-modal" data-recruitment-id="{{ $c->recruitmentId }}">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                        @endif
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10">
                                    <div class="table-empty">
                                        <i class="bi bi-inbox"></i>
                                        <p>Belum ada kandidat dengan status Accepted.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="panel-footer">
                <span id="accFooterCount">Menampilkan
                    {{ $paginatedCandidates->count() > 0 ? ($currentPage - 1) * $perPage + 1 . '–' . min($currentPage * $perPage, $total) : 0 }}
                    dari {{ $total }} data</span>
                <x-pagination :currentPage="$currentPage" :total="$total" :perPage="$perPage" :route="'hr.recruitment.accepted'" :queryParams="[
                    'search' => request('search'),
                    'sort' => $sortFilter,
                    'offering' => $offeringFilter,
                    'response' => $responseFilter,
                    'contract' => $contractFilter,
                ]" />
            </div>
        </div>
    </section>
@endsection

@section('scripts')
    <script>
        document.querySelectorAll('#accTableBody tr[data-drawer-type="candidate"]').forEach(function(row) {
            row.addEventListener('click', function(e) {
                // Guard first: let button clicks (Change Status, Preview Offering, etc.)
                // bubble up to the document-level delegation in csp-hardening.js.
                if (e.target.closest('button')) return;
                // Only stop propagation when opening the drawer (non-button row click).
                e.stopPropagation();
                var id = this.getAttribute('data-drawer-id');
                if (id && typeof openCandidateDrawer === 'function') openCandidateDrawer(id);
            });
        });
    </script>
@endsection
