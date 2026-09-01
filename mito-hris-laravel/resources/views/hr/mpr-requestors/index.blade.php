@extends('layouts.hr')

@section('title', 'MPR Requestors - MITO HRIS')
@section('page-title', 'MPR Requestors')
@section('page-subtitle', 'Daftar akun requestor untuk Manpower Request')

@section('content')
    <section class="page-section active" id="pageMprRequestors">
        <div class="row g-3 mb-4" id="mprRequestorStatsPanel">
            <div class="col-6 col-md-3">
                <div class="stat-card">
                    <div class="stat-icon bg-blue"><i class="bi bi-person-lines-fill"></i></div>
                    <div>
                        <div class="stat-label">Total Requestor</div>
                        <div class="stat-value">{{ $stats['total'] ?? 0 }}</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card">
                    <div class="stat-icon bg-green"><i class="bi bi-person-check-fill"></i></div>
                    <div>
                        <div class="stat-label">Aktif</div>
                        <div class="stat-value">{{ $stats['active'] ?? 0 }}</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card">
                    <div class="stat-icon bg-navy"><i class="bi bi-person-x-fill"></i></div>
                    <div>
                        <div class="stat-label">Tidak Aktif</div>
                        <div class="stat-value">{{ $stats['inactive'] ?? 0 }}</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card">
                    <div class="stat-icon bg-gold"><i class="bi bi-briefcase-fill"></i></div>
                    <div>
                        <div class="stat-label">Dengan Job Position</div>
                        <div class="stat-value">{{ $stats['job_positions'] ?? 0 }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="panel">
            <div class="panel-header">
                <div>
                    <h6><i class="bi bi-person-lines-fill text-primary me-2"></i>Daftar MPR Requestors</h6>
                    <div class="panel-subtitle">Data akun diambil langsung dari sheet <strong>mpr_requestor</strong>.</div>
                </div>
                <div class="export-btns ms-auto">
                    <button class="btn btn-primary" type="button" data-bs-toggle="modal"
                        data-bs-target="#mprRequestorAddModal">
                        <i class="bi bi-plus-lg me-1"></i> Tambah Requestor
                    </button>
                    <button class="btn-refresh" type="button" title="Muat ulang data requestor"
                        aria-label="Muat ulang data requestor" data-refresh="page">
                        <i class="bi bi-arrow-clockwise" aria-hidden="true"></i>
                    </button>
                </div>
            </div>

            <div class="filter-bar">
                <div class="table-search">
                    <i class="bi bi-search"></i>
                    <input type="search" id="mprRequestorSearch" placeholder="Cari nama, email, username, atau job position..."
                        aria-label="Cari MPR requestor" autocomplete="off" />
                </div>
                <select class="filter-select" id="mprRequestorStatusFilter" aria-label="Filter status requestor">
                    <option value="">Semua Status</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
                <select class="filter-select" aria-label="Jumlah data per halaman"
                    data-per-page-url="{{ route('hr.mpr-requestors.index') }}">
                    @foreach ([10, 20, 50] as $pageSize)
                        <option value="{{ $pageSize }}" {{ $perPage === $pageSize ? 'selected' : '' }}>
                            {{ $pageSize }} / hal
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="table-responsive">
                <table class="table hr-table table-hover mb-0">
                    <thead>
                        <tr>
                            <th scope="col">No.</th>
                            <th>Requestor</th>
                            <th>Username</th>
                            <th>Job Position</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Login Terakhir</th>
                            <th>Dibuat</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="mprRequestorsTableBody">
                        @forelse($requestors ?? [] as $requestor)
                            @php
                                $status = $requestor['Status'] ?? '-';
                                $statusClass = strtolower(trim($status)) === 'active' ? 'bg-success' : 'bg-secondary';
                            @endphp
                            <tr data-requestor-email="{{ $requestor['Email'] ?? '' }}">
                                <td class="id-mono">{{ $loop->iteration }}</td>
                                <td>
                                    <div class="cand-name requestor-name">{{ $requestor['Full Name'] ?? '-' }}</div>
                                    <div class="cand-sub requestor-email">{{ $requestor['Email'] ?? '-' }}</div>
                                </td>
                                <td class="id-mono requestor-username">{{ $requestor['Username'] ?? '-' }}</td>
                                <td class="requestor-job-position">{{ $requestor['Job Position'] ?? '-' }}</td>
                                <td><span
                                        class="fw-semibold text-navy requestor-role">{{ $requestor['Role'] ?? '-' }}</span>
                                </td>
                                <td><span class="badge {{ $statusClass }} requestor-status">{{ $status }}</span>
                                </td>
                                <td class="id-mono">{{ $requestor['Last Login'] ?? '-' }}</td>
                                <td class="id-mono">{{ $requestor['Created At'] ?? '-' }}</td>
                                <td>
                                    @can('manage_settings')
                                        <button type="button" class="btn btn-sm btn-primary" title="Edit MPR Requestor"
                                            aria-label="Edit MPR requestor" data-bs-toggle="modal"
                                            data-bs-target="#mprRequestorEditModal"
                                            data-email="{{ $requestor['Email'] ?? '' }}"
                                            data-name="{{ $requestor['Full Name'] ?? '' }}"
                                            data-username="{{ $requestor['Username'] ?? '' }}"
                                            data-job-position="{{ $requestor['Job Position'] ?? '' }}"
                                            data-role="{{ $requestor['Role'] ?? '' }}" data-status="{{ $status }}">
                                            <i class="bi bi-pencil-fill" aria-hidden="true"></i>
                                        </button>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr id="mprRequestorEmptyRow">
                                <td colspan="10">
                                    <div class="table-empty">
                                        <i class="bi bi-person-x"></i>
                                        <p class="mb-0">Belum ada MPR requestor.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                        <tr id="mprRequestorSearchEmptyRow" class="d-none">
                            <td colspan="10">
                                <div class="table-empty">
                                    <i class="bi bi-search"></i>
                                    <p class="mb-0">Tidak ada requestor yang sesuai filter.</p>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="panel-footer">
                <span class="small" id="mprRequestorResultCount">
                    @if ($total > 0)
                        Menampilkan {{ ($currentPage - 1) * $perPage + 1 }}–{{ min($currentPage * $perPage, $total) }}
                        dari {{ $total }} requestor
                    @else
                        Tidak ada requestor
                    @endif
                </span>
                <div class="d-flex align-items-center gap-3">
                    <span class="small text-muted">Sumber: sheet mpr_requestor</span>
                    @if ($total > $perPage)
                        <x-pagination :currentPage="$currentPage" :total="$total" :perPage="$perPage" :route="'hr.mpr-requestors.index'" />
                    @endif
                </div>
            </div>
        </div>
    </section>

    <div class="modal fade" id="mprRequestorAddModal" tabindex="-1" aria-labelledby="mprRequestorAddModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="mprRequestorAddModalLabel">
                        <i class="bi bi-person-plus me-2 text-primary"></i>Tambah MPR Requestor
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <form action="{{ route('hr.mpr-requestors.store') }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="mprRequestorName">Nama Lengkap <span
                                        class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="mprRequestorName" name="name"
                                    required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="mprRequestorEmail">Email <span
                                        class="text-danger">*</span></label>
                                <input type="email" class="form-control" id="mprRequestorEmail" name="email"
                                    required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="mprRequestorUsername">Username <span
                                        class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="mprRequestorUsername" name="username"
                                    required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="mprRequestorRole">Role <span
                                        class="text-danger">*</span></label>
                                <select class="form-select" id="mprRequestorRole" name="role" required>
                                    @foreach (config('hris.auth.valid_roles_requestor', []) as $role)
                                        <option value="{{ $role }}">{{ $role }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="mprRequestorJobPosition">Job Position <span
                                        class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="mprRequestorJobPosition" name="job_position"
                                    maxlength="255" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="mprRequestorPassword">Password <span
                                        class="text-danger">*</span></label>
                                <input type="password" class="form-control" id="mprRequestorPassword" name="password"
                                    minlength="8" autocomplete="new-password" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="mprRequestorPasswordConfirmation">Konfirmasi Password <span
                                        class="text-danger">*</span></label>
                                <input type="password" class="form-control" id="mprRequestorPasswordConfirmation"
                                    name="password_confirmation" minlength="8" autocomplete="new-password" required>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary"><i class="bi bi-check2 me-1"></i> Simpan
                            Requestor</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="mprRequestorEditModal" tabindex="-1" aria-labelledby="mprRequestorEditModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="mprRequestorEditModalLabel"><i
                            class="bi bi-person-gear me-2 text-primary"></i>Edit MPR Requestor</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <form id="mprRequestorEditForm" method="POST"
                    data-update-url="{{ route('hr.mpr-requestors.update', ['email' => '__EMAIL__']) }}">
                    @csrf
                    @method('PUT')
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="mprEditEmail">Email</label>
                                <input type="email" class="form-control" id="mprEditEmail" readonly />
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="mprEditName">Nama Lengkap <span
                                        class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="mprEditName" name="name"
                                    minlength="3" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="mprEditUsername">Username <span
                                        class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="mprEditUsername" name="username"
                                    minlength="3" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="mprEditRole">Role <span
                                        class="text-danger">*</span></label>
                                <select class="form-select" id="mprEditRole" name="role" required>
                                    @foreach (config('hris.auth.valid_roles_requestor', []) as $role)
                                        <option value="{{ $role }}">{{ $role }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="mprEditJobPosition">Job Position <span
                                        class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="mprEditJobPosition" name="job_position"
                                    maxlength="255" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="mprEditStatus">Status <span
                                        class="text-danger">*</span></label>
                                <select class="form-select" id="mprEditStatus" name="status" required>
                                    <option value="Active">Active</option>
                                    <option value="Inactive">Inactive</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="mprEditPassword">Password Baru</label>
                                <input type="password" class="form-control" id="mprEditPassword" name="password"
                                    minlength="8" autocomplete="new-password">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="mprEditPasswordConfirmation">Konfirmasi Password</label>
                                <input type="password" class="form-control" id="mprEditPasswordConfirmation"
                                    name="password_confirmation" minlength="8" autocomplete="new-password">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary"><i class="bi bi-check2 me-1"></i>Simpan
                            Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const search = document.getElementById('mprRequestorSearch');
            const statusFilter = document.getElementById('mprRequestorStatusFilter');
            const body = document.getElementById('mprRequestorsTableBody');
            const emptyRow = document.getElementById('mprRequestorSearchEmptyRow');
            const resultCount = document.getElementById('mprRequestorResultCount');
            const editModal = document.getElementById('mprRequestorEditModal');
            const editForm = document.getElementById('mprRequestorEditForm');
            if (!search || !body) return;

            editModal?.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                const email = button?.dataset.email || '';
                editForm.action = editForm.dataset.updateUrl.replace('__EMAIL__', encodeURIComponent(
                email));
                document.getElementById('mprEditEmail').value = email;
                document.getElementById('mprEditName').value = button?.dataset.name || '';
                document.getElementById('mprEditUsername').value = button?.dataset.username || '';
                document.getElementById('mprEditJobPosition').value = button?.dataset.jobPosition || button?.dataset.job_position || '';
                document.getElementById('mprEditRole').value = button?.dataset.role || 'Manpower';
                document.getElementById('mprEditStatus').value = button?.dataset.status || 'Active';
                document.getElementById('mprEditPassword').value = '';
                document.getElementById('mprEditPasswordConfirmation').value = '';
            });

            function applyFilters() {
                const query = search.value.trim().toLowerCase();
                const selectedStatus = statusFilter?.value || '';
                const rows = Array.from(body.querySelectorAll(
                    'tr:not(#mprRequestorSearchEmptyRow):not(#mprRequestorEmptyRow)'));
                let visible = 0;

                rows.forEach(function(row) {
                    const searchable = row.textContent.toLowerCase();
                    const status = row.querySelector('.requestor-status')?.textContent.toLowerCase()
                    .trim() || '';
                    const matches = searchable.includes(query) && (!selectedStatus || status ===
                        selectedStatus);
                    row.classList.toggle('d-none', !matches);
                    if (matches) visible++;
                });

                const hasFilter = query || selectedStatus;
                if (emptyRow) emptyRow.classList.toggle('d-none', visible !== 0 || !hasFilter);
                if (resultCount) {
                    resultCount.textContent = hasFilter ?
                        'Menampilkan ' + visible + ' dari ' + rows.length + ' requestor' :
                        (rows.length ? 'Menampilkan 1–' + rows.length + ' dari ' + rows.length + ' requestor' :
                            'Tidak ada requestor');
                }
            }

            search.addEventListener('input', applyFilters);
            statusFilter?.addEventListener('change', applyFilters);
        });
    </script>
@endsection
