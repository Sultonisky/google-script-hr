@extends('layouts.hr')

@section('title', 'Manajemen Pengguna - MITO HRIS')
@section('page-title', 'Manajemen Pengguna')
@section('page-subtitle', 'Kelola akun tim HR dan hak akses sistem')

@section('content')
    <section class="page-section active" id="pageUserManagement">
        <div class="row g-3 mb-4" id="userStatsPanel">
            <div class="col-6 col-md-3">
                <div class="stat-card">
                    <div class="stat-icon bg-blue"><i class="bi bi-people-fill"></i></div>
                    <div>
                        <div class="stat-label">Total Pengguna</div>
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
                    <div class="stat-icon bg-gold"><i class="bi bi-shield-lock-fill"></i></div>
                    <div>
                        <div class="stat-label">Administrator</div>
                        <div class="stat-value">{{ $stats['administrators'] ?? 0 }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="panel">
            <div class="panel-header">
                <div>
                    <h6><i class="bi bi-people-fill text-primary me-2"></i>Daftar Pengguna</h6>
                    <div class="panel-subtitle">Kelola akun tim HR dan hak akses sistem.</div>
                </div>

                @can('manage_settings')
                    <div class="export-btns ms-auto">
                        <button class="btn btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#userAddModal">
                            <i class="bi bi-plus-lg me-1"></i> Tambah Pengguna
                        </button>
                    </div>
                    <button class="btn-refresh" type="button" title="Muat ulang data pengguna"
                        aria-label="Muat ulang data pengguna" onclick="location.reload()">
                        <i class="bi bi-arrow-clockwise" aria-hidden="true"></i>
                    </button>
                @endcan
            </div>

            <div class="filter-bar">
                <div class="table-search">
                    <i class="bi bi-search"></i>
                    <input type="search" id="userSearch" placeholder="Cari nama, email, atau username..."
                        aria-label="Cari pengguna" autocomplete="off" />
                </div>
                <select class="filter-select" id="userRoleFilter" aria-label="Filter role">
                    <option value="">Semua Role</option>
                    @foreach (config('hris.auth.valid_roles_internal', []) as $roleOption)
                        <option value="{{ strtolower($roleOption) }}">{{ $roleOption }}</option>
                    @endforeach
                </select>
                <select class="filter-select" id="userStatusFilter" aria-label="Filter status">
                    <option value="">Semua Status</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>

            <div class="table-responsive">
                <table class="table hr-table table-hover mb-0">
                    <thead>
                        <tr>
                            <th scope="col">No.</th>
                            <th>Pengguna</th>
                            <th>Username</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Login Terakhir</th>
                            <th>Dibuat</th>
                        </tr>
                    </thead>
                    <tbody id="usersTableBody">
                        @forelse($users ?? [] as $user)
                            @php
                                $role = $user['Role'] ?? '-';
                                $status = $user['Status'] ?? '-';
                                $statusClass = in_array(strtolower($status), ['aktif', 'active'])
                                    ? 'bg-success'
                                    : 'bg-secondary';
                            @endphp
                            <tr>
                                <td class="id-mono">{{ $loop->iteration }}</td>
                                <td>
                                    <div class="cand-name user-name">{{ $user['Full Name'] ?? '-' }}</div>
                                    <div class="cand-sub user-email">{{ $user['Email'] ?? '-' }}</div>
                                </td>
                                <td class="id-mono user-username">{{ $user['Username'] ?? '-' }}</td>
                                <td><span class="fw-semibold text-navy">{{ $role }}</span></td>
                                <td><span class="badge {{ $statusClass }}">{{ $status }}</span></td>
                                <td class="id-mono">{{ $user['Last Login'] ?? '-' }}</td>
                                <td class="id-mono">{{ $user['Created At'] ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr id="userEmptyRow">
                                <td colspan="7">
                                    <div class="table-empty">
                                        <i class="bi bi-person-x"></i>
                                        <p class="mb-0">Belum ada pengguna sistem.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                        <tr id="userSearchEmptyRow" class="d-none">
                            <td colspan="7">
                                <div class="table-empty">
                                    <i class="bi bi-search"></i>
                                    <p class="mb-0">Tidak ada pengguna yang sesuai pencarian.</p>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="panel-footer">
                <span class="small" id="userResultCount">
                    @if (count($users ?? []) > 0)
                        Menampilkan 1–{{ count($users) }} dari {{ count($users) }} pengguna
                    @else
                        Tidak ada pengguna
                    @endif
                </span>

            </div>
        </div>
    </section>

    <div class="modal fade" id="userAddModal" tabindex="-1" aria-labelledby="userAddModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="userAddModalLabel"><i
                            class="bi bi-person-plus me-2 text-primary"></i>Tambah
                        Pengguna</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <form action="{{ route('hr.users.store') }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label" for="userName">Nama Lengkap <span
                                    class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="userName" name="name"
                                placeholder="Nama staf HR..." required />
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="userEmail">Email Login <span
                                    class="text-danger">*</span></label>
                            <input type="email" class="form-control" id="userEmail" name="email"
                                placeholder="staf@mitocareer.com" required />
                        </div>
                        <div class="mb-0">
                            <label class="form-label" for="userRole">Role Hak Akses <span
                                    class="text-danger">*</span></label>
                            <select class="form-select" id="userRole" name="role" required>
                                @foreach (config('hris.auth.valid_roles_internal', []) as $role)
                                    <option value="{{ $role }}">{{ $role }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="row g-3 mt-0">
                            <div class="col-md-6">
                                <label class="form-label" for="userPassword">Password <span
                                        class="text-danger">*</span></label>
                                <input type="password" class="form-control" id="userPassword" name="password"
                                    minlength="8" autocomplete="new-password" required />
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="userPasswordConfirmation">Konfirmasi Password <span
                                        class="text-danger">*</span></label>
                                <input type="password" class="form-control" id="userPasswordConfirmation"
                                    name="password_confirmation" minlength="8" autocomplete="new-password" required />
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary"><i class="bi bi-check2 me-1"></i> Simpan
                            Pengguna</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const search = document.getElementById('userSearch');
            const body = document.getElementById('usersTableBody');
            const emptyRow = document.getElementById('userSearchEmptyRow');
            const resultCount = document.getElementById('userResultCount');
            const roleFilter = document.getElementById('userRoleFilter');
            const statusFilter = document.getElementById('userStatusFilter');
            if (!search || !body) return;

            function applyUserFilters() {
                const query = search.value.trim().toLowerCase();
                const selectedRole = roleFilter?.value || '';
                const selectedStatus = statusFilter?.value || '';
                const rows = Array.from(body.querySelectorAll('tr:not(#userSearchEmptyRow):not(#userEmptyRow)'));
                let visible = 0;
                rows.forEach(function(row) {
                    const name = row.querySelector('.user-name')?.textContent.toLowerCase() || '';
                    const email = row.querySelector('.user-email')?.textContent.toLowerCase() || '';
                    const username = row.querySelector('.user-username')?.textContent.toLowerCase() || '';
                    const role = row.cells[3]?.textContent.toLowerCase().trim() || '';
                    const status = row.cells[4]?.textContent.toLowerCase().trim() || '';
                    const matches = (name.includes(query) || email.includes(query) || username.includes(
                            query)) &&
                        (!selectedRole || role === selectedRole) &&
                        (!selectedStatus || status === selectedStatus);
                    row.classList.toggle('d-none', !matches);
                    if (matches) visible++;
                });
                const hasFilter = query || selectedRole || selectedStatus;
                if (emptyRow) emptyRow.classList.toggle('d-none', visible !== 0 || !hasFilter);
                if (resultCount) {
                    resultCount.textContent = hasFilter ?
                        'Menampilkan ' + visible + ' dari ' + rows.length + ' pengguna' :
                        (rows.length ? 'Menampilkan 1–' + rows.length + ' dari ' + rows.length + ' pengguna' :
                            'Tidak ada pengguna');
                }
            }

            search.addEventListener('input', applyUserFilters);
            roleFilter?.addEventListener('change', applyUserFilters);
            statusFilter?.addEventListener('change', applyUserFilters);
        });
    </script>
@endsection
