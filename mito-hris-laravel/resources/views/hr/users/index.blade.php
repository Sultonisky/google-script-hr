@extends('layouts.hr')

@section('title', 'Manajemen Pengguna - MITO HRIS')
@section('page-title', 'Manajemen Pengguna')
@section('page-subtitle', 'Kelola akun tim HR dan hak akses sistem')

@section('content')
<section class="page-section active" id="pageUserManagement">
  <div class="panel">
    <div class="panel-header">
      <div>
        <h6><i class="bi bi-people-fill text-primary me-2"></i>Daftar Pengguna</h6>
        <div class="panel-subtitle">Kelola akun tim HR dan hak akses sistem.</div>
      </div>
      @can('manage_settings')
        <button class="btn btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#userAddModal">
          <i class="bi bi-plus-lg me-1"></i> Tambah Pengguna
        </button>
      @endcan
    </div>

    <div class="filter-bar">
      <div class="table-search">
        <i class="bi bi-search"></i>
        <input type="search" id="userSearch" placeholder="Cari nama atau email..."
          aria-label="Cari pengguna" autocomplete="off" />
      </div>
      <span class="small text-muted align-self-center" id="userResultCount">
        {{ count($users ?? []) }} pengguna
      </span>
    </div>

    <div class="table-responsive">
      <table class="table hr-table table-hover mb-0">
          <thead>
            <tr>
              <th scope="col">No.</th>
              <th>Pengguna</th>
              <th>Role</th>
              <th>Status</th>
              <th>Login Terakhir</th>
            </tr>
          </thead>
          <tbody id="usersTableBody">
            @forelse($users ?? [] as $user)
              @php
                $role = $user['role'] ?? '-';
                $roleClass = match ($role) {
                    'Super Admin' => 'bg-dark',
                    'Admin' => 'bg-primary',
                    'Privileged User' => 'bg-info text-dark',
                    default => 'bg-secondary',
                };
                $status = $user['status'] ?? 'Aktif';
                $statusClass = in_array(strtolower($status), ['aktif', 'active']) ? 'bg-success' : 'bg-secondary';
              @endphp
              <tr>
                <td class="id-mono">{{ $loop->iteration }}</td>
                <td>
                  <div class="cand-name user-name">{{ $user['name'] ?? '-' }}</div>
                  <div class="cand-sub user-email">{{ $user['email'] ?? '-' }}</div>
                </td>
                <td>
                  <span class="badge {{ $roleClass }}">{{ $role }}</span>
                </td>
                <td><span class="badge {{ $statusClass }}">{{ $status }}</span></td>
                <td class="id-mono">{{ $user['last_login'] ?? '-' }}</td>
              </tr>
            @empty
              <tr id="userEmptyRow">
                <td colspan="5">
                  <div class="table-empty">
                    <i class="bi bi-person-x"></i>
                    <p class="mb-0">Belum ada pengguna sistem.</p>
                  </div>
                </td>
              </tr>
            @endforelse
            <tr id="userSearchEmptyRow" class="d-none">
              <td colspan="5">
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
        <span class="small">Data pengguna sistem</span>
      </div>
    </div>
  </div>
</section>

<div class="modal fade" id="userAddModal" tabindex="-1" aria-labelledby="userAddModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="userAddModalLabel"><i class="bi bi-person-plus me-2 text-primary"></i>Tambah Pengguna</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
      </div>
      <form action="{{ route('hr.users.store') }}" method="POST">
        @csrf
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label" for="userName">Nama Lengkap <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="userName" name="name" placeholder="Nama staf HR..." required />
          </div>
          <div class="mb-3">
            <label class="form-label" for="userEmail">Email Login <span class="text-danger">*</span></label>
            <input type="email" class="form-control" id="userEmail" name="email" placeholder="staf@mitocareer.com" required />
          </div>
          <div class="mb-0">
            <label class="form-label" for="userRole">Role Hak Akses <span class="text-danger">*</span></label>
            <select class="form-select" id="userRole" name="role" required>
              @foreach(config('hris.auth.valid_roles_internal', []) as $role)
                <option value="{{ $role }}">{{ $role }}</option>
              @endforeach
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-primary"><i class="bi bi-check2 me-1"></i> Simpan Pengguna</button>
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
    if (!search || !body) return;

    search.addEventListener('input', function() {
      const query = this.value.trim().toLowerCase();
      const rows = Array.from(body.querySelectorAll('tr:not(#userSearchEmptyRow):not(#userEmptyRow)'));
      let visible = 0;
      rows.forEach(function(row) {
        const name = row.querySelector('.user-name')?.textContent.toLowerCase() || '';
        const email = row.querySelector('.user-email')?.textContent.toLowerCase() || '';
        const matches = name.includes(query) || email.includes(query);
        row.classList.toggle('d-none', !matches);
        if (matches) visible++;
      });
      if (emptyRow) emptyRow.classList.toggle('d-none', visible !== 0 || !query);
      if (resultCount) resultCount.textContent = query ? visible + ' pengguna ditemukan' : rows.length + ' pengguna';
    });
  });
</script>
@endsection
