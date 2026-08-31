@extends('layouts.hr')

@section('title', 'Riwayat Pengajuan MPR - MITO HRIS')
@section('page-title', 'Riwayat Pengajuan MPR')
@section('page-subtitle', 'Lihat seluruh pengajuan manpower yang pernah Anda buat')

@section('content')
    <section class="page-section active" id="pageMprHistory">
        <div class="row g-3 mb-4">
            <div class="col-12 col-md-6 col-xl-3">
                <div class="stat-card">
                    <div class="stat-icon bg-blue"><i class="bi bi-file-earmark-text"></i></div>
                    <div>
                        <div class="stat-label">Total Pengajuan</div>
                        <div class="stat-value">{{ $stats['total'] ?? 0 }}</div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-6 col-xl-3">
                <div class="stat-card">
                    <div class="stat-icon bg-gold"><i class="bi bi-hourglass-split"></i></div>
                    <div>
                        <div class="stat-label">Menunggu Review</div>
                        <div class="stat-value">{{ $stats['submitted'] ?? 0 }}</div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-6 col-xl-3">
                <div class="stat-card">
                    <div class="stat-icon bg-green"><i class="bi bi-check-circle"></i></div>
                    <div>
                        <div class="stat-label">Disetujui</div>
                        <div class="stat-value">{{ $stats['approved'] ?? 0 }}</div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-6 col-xl-3">
                <div class="stat-card">
                    <div class="stat-icon bg-navy"><i class="bi bi-people"></i></div>
                    <div>
                        <div class="stat-label">Total Kebutuhan</div>
                        <div class="stat-value">{{ $stats['total_quantity'] ?? 0 }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="panel mb-4">
            <div class="panel-header justify-content-between align-items-center">
                <div>
                    <h6><i class="bi bi-funnel me-2 text-primary"></i>Filter & Pencarian</h6>
                </div>
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <button type="button" class="btn-refresh" title="Muat ulang data" aria-label="Muat ulang data"
                        data-refresh="page">
                        <i class="bi bi-arrow-clockwise" aria-hidden="true"></i>
                    </button>
                    <a href="{{ request()->routeIs('mpr.auth.*') ? route('mpr.auth.request') : route('hr.mpr.create') }}" class="btn btn-primary fw-semibold">
                        <i class="bi bi-plus-circle-fill me-1"></i> Buat Pengajuan MPR
                    </a>
                </div>
            </div>

            <form action="{{ request()->routeIs('mpr.auth.*') ? route('mpr.auth.request.history') : route('hr.mpr.history') }}" method="GET" class="filter-bar">
                <div class="table-search">
                    <i class="bi bi-search"></i>
                    <input type="text" name="search" placeholder="Cari No MPR, posisi, dept..."
                        value="{{ $search }}">
                </div>

                <select name="department" class="filter-select" data-auto-submit="true">
                    <option value="">Semua Departemen</option>
                    @foreach ($departments as $d)
                        <option value="{{ $d }}" {{ $dept === $d ? 'selected' : '' }}>{{ $d }}
                        </option>
                    @endforeach
                </select>

                <select name="status" class="filter-select" data-auto-submit="true">
                    <option value="">Semua Status</option>
                    <option value="Submitted" {{ $status === 'Submitted' ? 'selected' : '' }}>Submitted</option>
                </select>

                <select name="sort" class="filter-select" data-auto-submit="true">
                    <option value="newest" {{ request('sort', 'newest') === 'newest' ? 'selected' : '' }}>Terbaru</option>
                    <option value="oldest" {{ request('sort') === 'oldest' ? 'selected' : '' }}>Terlama</option>
                </select>

                <a href="{{ request()->routeIs('mpr.auth.*') ? route('mpr.auth.request.history') : route('hr.mpr.history') }}" class="btn-reset-filter text-decoration-none"
                    title="Reset semua filter">
                    <i class="bi bi-arrow-counterclockwise"></i>
                </a>
            </form>
        </div>

        <div class="panel">
            <div class="panel-header">
                <div>
                    <h6><i class="bi bi-table me-2 text-primary"></i>Daftar Pengajuan Saya</h6>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table hr-table">
                    <thead>
                        <tr>
                            <th>MPR Number</th>
                            <th>Posisi</th>
                            <th>Departemen</th>
                            <th>Qty</th>
                            <th>Status</th>
                            <th>Tanggal</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($paginatedMprs as $mpr)
                            <tr>
                                <td><strong class="text-primary">{{ $mpr->mprNumber }}</strong></td>
                                <td>
                                    <div class="fw-semibold text-dark">{{ $mpr->position }}</div>
                                    <small class="text-muted">{{ $mpr->jobLevel ?? '-' }}</small>
                                </td>
                                <td>
                                    <div class="fw-medium text-dark">{{ $mpr->department }}</div>
                                    <small class="text-muted">{{ $mpr->entity ?: $mpr->company }}</small>
                                </td>
                                <td>
                                    <span class="badge bg-primary-subtle text-primary border rounded-pill px-2 py-1">
                                        {{ $mpr->quantity }} Org
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-success-subtle text-success border rounded-pill px-2 py-1">
                                        {{ strtoupper($mpr->status ?? 'SUBMITTED') }}
                                    </span>
                                </td>
                                <td>
                                    <span
                                        class="text-dark">{{ $mpr->requestDate ? date('d M Y', strtotime($mpr->requestDate)) : '-' }}</span>
                                </td>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm">
                                        <button type="button" class="btn btn-outline-primary btn-mpr-detail"
                                            data-id="{{ $mpr->mprNumber }}" title="Lihat Detail">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                        @if (filled($mpr->mprNumber))
                                            <a href="{{ request()->routeIs('mpr.auth.*') ? route('mpr.auth.pdf', ['id' => $mpr->mprNumber]) : route('hr.mpr.pdf', ['id' => $mpr->mprNumber]) }}" target="_blank"
                                                class="btn btn-outline-danger" title="Unduh PDF">
                                                <i class="bi bi-file-earmark-pdf"></i>
                                            </a>
                                        @else
                                            <button type="button" class="btn btn-outline-secondary" disabled
                                                title="Nomor MPR tidak tersedia">
                                                <i class="bi bi-file-earmark-pdf"></i>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7">
                                    <div class="table-empty">
                                        <i class="bi bi-inbox"></i>
                                        <p>Belum ada riwayat pengajuan Manpower Request.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($lastPage > 1)
                <div class="panel-header justify-content-between">
                    <div class="panel-subtitle mb-0">Menampilkan halaman <strong>{{ $currentPage }}</strong> dari
                        <strong>{{ $lastPage }}</strong>
                    </div>
                    <ul class="pagination pagination-sm mb-0">
                        <li class="page-item {{ $currentPage <= 1 ? 'disabled' : '' }}">
                            <a class="page-link"
                                href="{{ request()->fullUrlWithQuery(['page' => $currentPage - 1]) }}">Sebelumnya</a>
                        </li>
                        @for ($p = 1; $p <= $lastPage; $p++)
                            <li class="page-item {{ $p === $currentPage ? 'active' : '' }}">
                                <a class="page-link"
                                    href="{{ request()->fullUrlWithQuery(['page' => $p]) }}">{{ $p }}</a>
                            </li>
                        @endfor
                        <li class="page-item {{ $currentPage >= $lastPage ? 'disabled' : '' }}">
                            <a class="page-link"
                                href="{{ request()->fullUrlWithQuery(['page' => $currentPage + 1]) }}">Berikutnya</a>
                        </li>
                    </ul>
                </div>
            @endif
        </div>
    </section>

    @include('hr.mpr.partials.detail-modal')
@endsection

@section('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const getCsrfToken = () => {
                const meta = document.querySelector('meta[name="csrf-token"]');
                return meta ? meta.getAttribute('content') : '';
            };

            const detailButtons = document.querySelectorAll('.btn-mpr-detail');
            detailButtons.forEach(btn => {
                btn.addEventListener('click', function() {
                    const id = this.getAttribute('data-id');
                    const modalEl = document.getElementById('modalMprDetail');
                    const detailModal = new bootstrap.Modal(modalEl);
                    const loadingEl = document.getElementById('detailLoading');
                    const contentEl = document.getElementById('detailContent');
                    const detailMprNum = document.getElementById('detailMprNumber');
                    const btnPdf = document.getElementById('btnModalDownloadPdf');

                    loadingEl.classList.remove('d-none');
                    contentEl.classList.add('d-none');
                    detailMprNum.innerText = id;
                    detailModal.show();

                    fetch(`/hr/mpr/${encodeURIComponent(id)}/json`, {
                            headers: {
                                'X-CSRF-TOKEN': getCsrfToken(),
                                'Accept': 'application/json',
                            }
                        })
                        .then(async response => {
                            const data = await response.json();
                            if (!response.ok) throw new Error(data.error ||
                                'Gagal memuat detail MPR.');
                            return data;
                        })
                        .then(data => {
                            const m = data.mpr;
                            document.getElementById('detManagerName').innerText = m
                                .requestor_name || m.manager_name || '-';
                            document.getElementById('detManagerEmail').innerText = m
                                .requestor_email || m.manager_email || '-';
                            document.getElementById('detCompany').innerText = m.entity || m
                                .company || '-';
                            const detBranch = document.getElementById('detBranch');
                            if (detBranch) detBranch.innerText = m.branch ?
                                `Branch: ${m.branch}` : '';
                            document.getElementById('detCreatedBy').innerText =
                                `Diajukan: ${m.created_at || m.request_date || '-'}`;
                            document.getElementById('detPosition').innerText = m.position ||
                                '-';
                            document.getElementById('detDeptDiv').innerText =
                                `${m.department || '-'} / ${m.division || '-'}`;
                            document.getElementById('detJobLevel').innerText = m.job_level ||
                                '-';
                            document.getElementById('detEmpType').innerText = m
                                .employment_type || '-';
                            document.getElementById('detLocation').innerText = m
                                .work_location || '-';
                            document.getElementById('detQuantity').innerText = m.quantity ||
                                '1';
                            document.getElementById('detJoinDate').innerText = m
                                .expected_join_date || '-';
                            document.getElementById('detReason').innerText = m.reason || '-';

                            const wrapRepl = document.getElementById('wrapReplacement');
                            if (m.replacement_for) {
                                document.getElementById('detReplacementFor').innerText = m
                                    .replacement_for;
                                wrapRepl.classList.remove('d-none');
                            } else {
                                wrapRepl.classList.add('d-none');
                            }

                            document.getElementById('detRequirements').innerHTML = m
                                .requirements_html ||
                                '<span class="text-muted fst-italic">Tidak ada kualifikasi khusus.</span>';
                            document.getElementById('detJobDesc').innerHTML = m
                                .job_description_html ||
                                '<span class="text-muted fst-italic">Tidak ada uraian pekerjaan khusus.</span>';
                            document.getElementById('detNotes').innerHTML = m.notes_html ||
                                '<span class="text-muted fst-italic">-</span>';

                            if (btnPdf) {
                                btnPdf.href = `/hr/mpr/${encodeURIComponent(m.mpr_number)}/pdf`;
                            }

                            loadingEl.classList.add('d-none');
                            contentEl.classList.remove('d-none');
                        })
                        .catch(error => {
                            loadingEl.innerHTML =
                                `<div class="text-danger py-4"><i class="bi bi-exclamation-triangle-fill fs-2 d-block mb-2"></i>${error.message}</div>`;
                        });
                });
            });
        });
    </script>
@endsection
