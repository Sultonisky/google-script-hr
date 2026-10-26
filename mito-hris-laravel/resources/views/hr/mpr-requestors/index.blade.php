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
                    <div class="panel-subtitle">@if (\App\Support\HrisDataDriver::usesSheets())Data akun diambil langsung dari sheet <strong>mpr_requestor</strong>.@else Data akun diambil dari database HRIS.@endif</div>
                </div>
                <div class="export-btns ms-auto">
                    <button class="btn btn-primary" type="button" data-bs-toggle="modal"
                        data-bs-target="#mprRequestorAddModal">
                        <i class="bi bi-plus-lg me-1"></i> Tambah Requestor
                    </button>
                    <button class="btn-refresh" type="button" title="Muat ulang data requestor"
                        aria-label="Muat ulang data requestor" data-refresh="page">
                        <i class="bi bi-arrow-repeat" aria-hidden="true"></i>
                    </button>
                </div>
            </div>

            <form action="{{ route('hr.mpr-requestors.index') }}" method="GET" id="mprRequestorFilterForm">
                <input type="hidden" name="page" value="1">
                <div class="filter-bar">
                    <div class="table-search">
                        <i class="bi bi-search"></i>
                        <input type="search" id="mprRequestorSearch" name="search"
                            placeholder="Cari nama, email, username, atau job position..." aria-label="Cari MPR requestor"
                            value="{{ $searchFilter }}" autocomplete="off" />
                    </div>
                    <select class="filter-select" id="mprRequestorStatusFilter" name="status"
                        aria-label="Filter status requestor" data-auto-submit="true">
                        <option value="">Semua Status</option>
                        <option value="active" {{ $statusFilter === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ $statusFilter === 'inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                    <select class="filter-select" name="per_page" aria-label="Jumlah data per halaman"
                        data-auto-submit="true">
                        @foreach ([10, 20, 50] as $pageSize)
                            <option value="{{ $pageSize }}" {{ $perPage === $pageSize ? 'selected' : '' }}>
                                {{ $pageSize }} / hal
                            </option>
                        @endforeach
                    </select>
                    <a href="{{ route('hr.mpr-requestors.index') }}" class="btn-reset-filter text-decoration-none"
                        title="Reset filter">
                        <i class="bi bi-arrow-counterclockwise"></i> Reset
                    </a>
                </div>
            </form>

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
                                $statusClass = strtolower(trim($status)) === 'active' ? 'accepted' : 'blacklist';
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
                                <td><span class="badge-status {{ $statusClass }} requestor-status">{{ $status }}</span>
                                </td>
                                <td class="id-mono">{{ $requestor['Last Login'] ?? '-' }}</td>
                                <td class="id-mono">{{ $requestor['Created At'] ?? '-' }}</td>
                                <td class="text-nowrap">
                                    @can('manage_settings')
                                        <div class="d-inline-flex flex-nowrap align-items-center gap-1">
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
                                            @if (\App\Support\Rbac::normalizeRole(session('hr_user.role')) === 'Super Admin')
                                                <button type="button" class="btn btn-sm btn-outline-danger"
                                                    title="Force Delete" aria-label="Force delete MPR requestor"
                                                    data-bs-toggle="modal" data-bs-target="#mprRequestorForceDeleteModal"
                                                    data-email="{{ $requestor['Email'] ?? '' }}"
                                                    data-name="{{ $requestor['Full Name'] ?? '' }}">
                                                    <i class="bi bi-trash-fill" aria-hidden="true"></i>
                                                </button>
                                            @endif
                                        </div>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr id="mprRequestorEmptyRow">
                                <td colspan="9">
                                    <div class="table-empty">
                                        <i class="bi {{ ($searchFilter !== '' || $statusFilter !== '') ? 'bi-search' : 'bi-person-x' }}"></i>
                                        <p class="mb-0">
                                            {{ ($searchFilter !== '' || $statusFilter !== '') ? 'Tidak ada requestor yang sesuai filter.' : 'Belum ada MPR requestor.' }}
                                        </p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="panel-footer">
                <span class="small" id="mprRequestorResultCount">
                    @if ($total > 0)
                        Menampilkan {{ ($currentPage - 1) * $perPage + 1 }}–{{ min($currentPage * $perPage, $total) }}
                        dari {{ $total }} requestor
                    @elseif ($searchFilter !== '' || $statusFilter !== '')
                        Tidak ada requestor yang sesuai filter
                    @else
                        Tidak ada requestor
                    @endif
                </span>
                <div class="d-flex align-items-center gap-3">
                    <span class="small text-muted">Sumber: {{ \App\Support\HrisDataDriver::usesSheets() ? 'sheet mpr_requestor' : 'database HRIS' }}</span>
                    @if ($total > $perPage)
                        <x-pagination :currentPage="$currentPage" :total="$total" :perPage="$perPage" :route="'hr.mpr-requestors.index'"
                            :queryParams="array_filter(['search' => $searchFilter, 'status' => $statusFilter])" />
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
                                <input type="text" class="form-control" id="mprRequestorJobPosition"
                                    name="job_position" maxlength="255" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="mprRequestorPassword">Password <span
                                        class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="mprRequestorPassword" name="password"
                                        minlength="8" autocomplete="new-password" required>
                                    <button type="button" class="btn btn-outline-primary"
                                        data-password-toggle="mprRequestorPassword" aria-label="Tampilkan password"
                                        aria-pressed="false">
                                        <i class="bi bi-eye-fill" aria-hidden="true"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="mprRequestorPasswordConfirmation">Konfirmasi Password <span
                                        class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="mprRequestorPasswordConfirmation"
                                        name="password_confirmation" minlength="8" autocomplete="new-password" required>
                                    <button type="button" class="btn btn-outline-primary"
                                        data-password-toggle="mprRequestorPasswordConfirmation"
                                        aria-label="Tampilkan password" aria-pressed="false">
                                        <i class="bi bi-eye-fill" aria-hidden="true"></i>
                                    </button>
                                </div>
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
                                <label class="form-label" for="mprEditEmail">Email <span
                                        class="text-danger">*</span></label>
                                <input type="email" class="form-control" id="mprEditEmail" name="email" required>
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
                                <div class="input-group">
                                    <input type="password" class="form-control" id="mprEditPassword" name="password"
                                        minlength="8" autocomplete="new-password">
                                    <button type="button" class="btn btn-outline-primary"
                                        data-password-toggle="mprEditPassword" aria-label="Tampilkan password"
                                        aria-pressed="false">
                                        <i class="bi bi-eye-fill" aria-hidden="true"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="mprEditPasswordConfirmation">Konfirmasi Password</label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="mprEditPasswordConfirmation"
                                        name="password_confirmation" minlength="8" autocomplete="new-password">
                                    <button type="button" class="btn btn-outline-primary"
                                        data-password-toggle="mprEditPasswordConfirmation" aria-label="Tampilkan password"
                                        aria-pressed="false">
                                        <i class="bi bi-eye-fill" aria-hidden="true"></i>
                                    </button>
                                </div>
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

    @if (\App\Support\Rbac::normalizeRole(session('hr_user.role')) === 'Super Admin')
        <div class="modal fade" id="mprRequestorForceDeleteModal" tabindex="-1"
            aria-labelledby="mprRequestorForceDeleteModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title text-danger" id="mprRequestorForceDeleteModalLabel">
                            <i class="bi bi-trash me-2"></i>Force Delete MPR Requestor
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>
                    <form id="mprRequestorForceDeleteForm" method="POST"
                        data-delete-url="{{ route('hr.mpr-requestors.force-delete', ['email' => '__EMAIL__']) }}">
                        @csrf
                        @method('DELETE')
                        <div class="modal-body">
                            <p class="mb-2">MPR requestor <strong id="mprRequestorForceDeleteName">-</strong> akan dihapus permanen.</p>
                            <p class="mb-0 small text-muted">Tindakan ini tidak bisa dibatalkan. Data MPR yang sudah dibuat tetap tersimpan.</p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-danger">
                                <i class="bi bi-trash me-1"></i>Force Delete
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
@endsection

@section('scripts')
    <script nonce="{{ request()->attributes->get('csp_nonce') }}">
        document.addEventListener('DOMContentLoaded', function() {
            function setPasswordVisible(button, visible) {
                const input = document.getElementById(button.dataset.passwordToggle);
                if (!input) return;
                input.type = visible ? 'text' : 'password';
                button.setAttribute('aria-pressed', visible ? 'true' : 'false');
                button.setAttribute('aria-label', visible ? 'Sembunyikan password' : 'Tampilkan password');
                const icon = button.querySelector('i');
                if (icon) icon.className = visible ? 'bi bi-eye-slash-fill' : 'bi bi-eye-fill';
            }

            document.querySelectorAll('[data-password-toggle]').forEach(function(button) {
                button.addEventListener('click', function() {
                    const input = document.getElementById(button.dataset.passwordToggle);
                    if (input) setPasswordVisible(button, input.type === 'password');
                });
            });

            ['mprRequestorAddModal', 'mprRequestorEditModal'].forEach(function(id) {
                document.getElementById(id)?.addEventListener('hidden.bs.modal', function() {
                    this.querySelectorAll('[data-password-toggle]').forEach(function(button) {
                        setPasswordVisible(button, false);
                    });
                });
            });

            const search = document.getElementById('mprRequestorSearch');
            const filterForm = document.getElementById('mprRequestorFilterForm');
            const editModal = document.getElementById('mprRequestorEditModal');
            const editForm = document.getElementById('mprRequestorEditForm');
            const forceDeleteModal = document.getElementById('mprRequestorForceDeleteModal');
            const forceDeleteForm = document.getElementById('mprRequestorForceDeleteForm');

            editModal?.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                const email = button?.dataset.email || '';
                editForm.action = editForm.dataset.updateUrl.replace('__EMAIL__', encodeURIComponent(
                    email));
                document.getElementById('mprEditEmail').value = email;
                document.getElementById('mprEditName').value = button?.dataset.name || '';
                document.getElementById('mprEditUsername').value = button?.dataset.username || '';
                document.getElementById('mprEditJobPosition').value = button?.dataset.jobPosition || button
                    ?.dataset.job_position || '';
                document.getElementById('mprEditRole').value = button?.dataset.role || 'Manpower';
                document.getElementById('mprEditStatus').value = button?.dataset.status || 'Active';
                document.getElementById('mprEditPassword').value = '';
                document.getElementById('mprEditPasswordConfirmation').value = '';
            });

            forceDeleteModal?.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                const email = button?.dataset.email || '';
                const name = button?.dataset.name || email;
                if (forceDeleteForm) {
                    forceDeleteForm.action = forceDeleteForm.dataset.deleteUrl.replace('__EMAIL__', encodeURIComponent(email));
                }
                const nameEl = document.getElementById('mprRequestorForceDeleteName');
                if (nameEl) nameEl.textContent = name + (email ? ' (' + email + ')' : '');
            });

            if (search && filterForm) {
                if (search.value) {
                    search.focus();
                    const end = search.value.length;
                    if (typeof search.setSelectionRange === 'function') {
                        search.setSelectionRange(end, end);
                    }
                }

                let searchTimer = null;
                search.addEventListener('input', function() {
                    clearTimeout(searchTimer);
                    searchTimer = setTimeout(function() {
                        filterForm.submit();
                    }, 400);
                });
                search.addEventListener('search', function() {
                    clearTimeout(searchTimer);
                    filterForm.submit();
                });
            }
        });
    </script>
@endsection
