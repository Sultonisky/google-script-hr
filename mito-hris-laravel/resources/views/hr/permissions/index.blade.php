@extends('layouts.hr')

@section('title', 'Permission Management')

@section('content')
<div class="container-fluid py-3" data-permission-management>
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h2 class="mb-1">Permission Management</h2>
            <p class="text-muted mb-0">Kelola permission per pengguna tanpa mengubah role.</p>
        </div>
        <span class="badge text-bg-dark">Super Admin</span>
    </div>

    <div class="row g-3">
        <div class="col-lg-4">
            <div class="card shadow-sm">
                <div class="card-body">
                    <label for="permission-user-search" class="form-label">Search User</label>
                    <input id="permission-user-search" class="form-control mb-3" type="search" placeholder="Nama, email, atau role">
                    <label for="permission-user-select" class="form-label">User</label>
                    <select id="permission-user-select" class="form-select" size="12">
                        <option value="">Pilih pengguna</option>
                        @foreach ($users as $user)
                            <option value="{{ $user['email'] }}">{{ $user['fullName'] }} | {{ $user['email'] }}</option>
                        @endforeach
                    </select>
                    <div id="permission-user-summary" class="small text-muted mt-3">Belum ada pengguna dipilih.</div>
                </div>
            </div>
        </div>
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div id="permission-alert" class="alert d-none" role="alert"></div>
                    <div id="permission-loading" class="text-muted d-none">Memuat permission...</div>
                    <div id="permission-groups" class="row g-3"></div>
                    <div class="d-flex justify-content-end mt-4">
                        <button type="button" class="btn btn-primary" id="permission-save" disabled>
                            <i class="bi bi-save me-1"></i> Simpan Permission
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script nonce="{{ request()->attributes->get('csp_nonce') }}">
document.addEventListener('DOMContentLoaded', function () {
    const root = document.querySelector('[data-permission-management]');
    const select = document.getElementById('permission-user-select');
    const search = document.getElementById('permission-user-search');
    const groups = document.getElementById('permission-groups');
    const save = document.getElementById('permission-save');
    const loading = document.getElementById('permission-loading');
    const alertBox = document.getElementById('permission-alert');
    const summary = document.getElementById('permission-user-summary');
    const csrf = document.querySelector('meta[name="csrf-token"]').content;
    const users = @json($users);
    const catalog = @json($permissionGroups);

    function showAlert(message, type) {
        alertBox.textContent = message;
        alertBox.className = 'alert alert-' + type;
    }

    function renderGroups(values) {
        groups.innerHTML = Object.entries(catalog).map(([group, permissions]) => `
            <div class="col-md-6">
                <fieldset class="border rounded p-3 h-100">
                    <legend class="float-none w-auto px-2 fs-6">${group}</legend>
                    ${permissions.map(permission => {
                        const key = permission.key;
                        const label = permission.name;
                        return `<div class="form-check mb-2">
                            <input class="form-check-input permission-checkbox" type="checkbox" value="${key}" id="permission-${key.replace(/[^a-z0-9]+/gi, '-') }" ${values[key] ? 'checked' : ''}>
                            <label class="form-check-label" for="permission-${key.replace(/[^a-z0-9]+/gi, '-') }">${label}<span class="d-block small text-muted">${key}</span></label>
                        </div>`;
                    }).join('')}
                </fieldset>
            </div>`).join('');
    }

    async function loadUser(email) {
        if (!email) {
            groups.innerHTML = '';
            save.disabled = true;
            summary.textContent = 'Belum ada pengguna dipilih.';
            return;
        }
        loading.classList.remove('d-none');
        save.disabled = true;
        try {
            const response = await fetch(`{{ url('/hr/permissions') }}/${encodeURIComponent(email)}`, { headers: { Accept: 'application/json' } });
            if (!response.ok) throw new Error('Gagal memuat permission.');
            const data = await response.json();
            summary.textContent = `${data.user.fullName} | ${data.user.email} | Role: ${data.user.role} | Status: ${data.user.status}`;
            renderGroups(data.permissions);
            save.disabled = false;
            alertBox.className = 'alert d-none';
        } catch (error) {
            showAlert(error.message, 'danger');
        } finally {
            loading.classList.add('d-none');
        }
    }

    search.addEventListener('input', function () {
        const query = this.value.toLowerCase();
        Array.from(select.options).forEach(option => {
            if (!option.value) return;
            const user = users.find(item => item.email === option.value);
            option.hidden = !`${user.fullName} ${user.email} ${user.role}`.toLowerCase().includes(query);
        });
    });
    select.addEventListener('change', () => loadUser(select.value));
    save.addEventListener('click', async function () {
        if (!select.value) return;
        save.disabled = true;
        save.classList.add('disabled');
        const permissions = Array.from(document.querySelectorAll('.permission-checkbox:checked')).map(input => input.value);
        try {
            const response = await fetch(`{{ url('/hr/permissions') }}/${encodeURIComponent(select.value)}`, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrf },
                body: JSON.stringify({ permissions })
            });
            const data = await response.json();
            if (!response.ok) throw new Error(data.message || 'Permission gagal disimpan.');
            showAlert(data.message, 'success');
        } catch (error) {
            showAlert(error.message, 'danger');
        } finally {
            save.disabled = false;
            save.classList.remove('disabled');
        }
    });
});
</script>
@endsection