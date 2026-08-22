@extends('layouts.hr')

@section('title', 'Manajemen Pengguna - MITO HRIS')
@section('page-title', 'Manajemen Pengguna')
@section('page-subtitle', 'Kelola akun tim HR dan hak akses sistem')

@section('content')
<!-- partials/UserManagementContent.html — USER MANAGEMENT CONTENT (1:1 from GAS) -->
<section class="page-section active" id="pageUserManagement">
  <div class="um-container">
    <!-- Header -->
    <div class="um-header">
      <div>
        <h4><i class="bi bi-people-fill me-2"></i>Manajemen Pengguna</h4>
        <small class="text-muted">Kelola akun tim HR dan hak akses sistem</small>
      </div>
      <div class="d-flex align-items-center gap-2">
        <button class="btn btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#userAddModal">
          <i class="bi bi-plus-lg"></i> Tambah Pengguna
        </button>
      </div>
    </div>

    <!-- Table Card -->
    <div class="um-card">
      <div class="um-toolbar">
        <div class="input-group" style="max-width: 320px">
          <span class="input-group-text border-end-0"><i class="bi bi-search"></i></span>
          <input type="text" class="form-control border-start-0" id="userSearch" placeholder="Cari nama atau email..." onkeyup="filterUserTable()" />
        </div>
      </div>
      <div class="table-responsive">
        <table class="table um-table mb-0">
          <thead>
            <tr>
              <th>Pengguna</th>
              <th>Role</th>
              <th>Status</th>
              <th>Login Terakhir</th>
            </tr>
          </thead>
          <tbody id="usersTableBody">
            @forelse($users ?? [] as $user)
              <tr>
                <td>
                  <div class="fw-semibold text-navy user-name">{{ $user['name'] }}</div>
                  <small class="text-muted user-email">{{ $user['email'] }}</small>
                </td>
                <td>
                  <span class="badge {{ $user['role'] === 'Super Admin' ? 'bg-danger' : 'bg-primary' }}">
                    {{ $user['role'] }}
                  </span>
                </td>
                <td><span class="badge bg-success">{{ $user['status'] ?? 'Aktif' }}</span></td>
                <td class="id-mono">{{ $user['last_login'] ?? '-' }}</td>
              </tr>
            @empty
              <tr>
                <td colspan="4" class="text-center py-4 text-muted">Belum ada pengguna sistem.</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>
</section>

<!-- Modal Add User -->
<div class="modal fade" id="userAddModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content" style="border-radius:16px">
      <div class="modal-header" style="background:#0B2540;color:#fff;border-radius:16px 16px 0 0">
        <h6 class="modal-title fw-bold">Tambah Pengguna Baru</h6>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form action="{{ route('hr.users.store') }}" method="POST">
        @csrf
        <div class="modal-body p-4">
          <div class="mb-3">
            <label class="form-label fw-semibold">Nama Lengkap <span class="text-danger">*</span></label>
            <input type="text" class="form-control" name="name" placeholder="Nama staf HR..." required />
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold">Email Login <span class="text-danger">*</span></label>
            <input type="email" class="form-control" name="email" placeholder="staf@mitocareer.com" required />
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold">Role Hak Akses <span class="text-danger">*</span></label>
            <select class="form-select" name="role" required>
              <option value="HR Recruiter">HR Recruiter (Akses Recruitment & Calon Kandidat)</option>
              <option value="HR Admin">HR Admin (Akses Seluruh Modul HR)</option>
              <option value="Super Admin">Super Admin (Akses Penuh & Manajemen Pengguna)</option>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-sm text-white" style="background:var(--color-primary)">
            <i class="bi bi-check2 me-1"></i> Tambah Pengguna
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection

@section('scripts')
<script>
  function filterUserTable() {
    const q = document.getElementById('userSearch').value.toLowerCase();
    const rows = document.querySelectorAll('#usersTableBody tr');
    rows.forEach(r => {
      const name = r.querySelector('.user-name')?.textContent.toLowerCase() || '';
      const email = r.querySelector('.user-email')?.textContent.toLowerCase() || '';
      r.style.display = (name.includes(q) || email.includes(q)) ? '' : 'none';
    });
  }
</script>
@endsection
