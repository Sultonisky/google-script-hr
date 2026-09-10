@extends('layouts.hr')

@section('title', 'Permission - MITO HRIS')
@section('page-title', 'Permission Management')
@section('page-subtitle', 'Manage individual user access without changing their role.')

@section('styles')
<style>
    [data-permission-management] .permission-shell {
        max-width: 1500px;
        margin: 0 auto;
    }

    [data-permission-management] .permission-sidebar,
    [data-permission-management] .permission-editor {
        border: 1px solid var(--bs-border-color);
        box-shadow: 0 0.35rem 1.25rem rgba(20, 32, 45, 0.06);
    }

    [data-permission-management] .permission-sidebar > .card-body {
        display: flex;
        min-height: 0;
        flex-direction: column;
    }

    [data-permission-management] .permission-editor {
        height: clamp(520px, calc(100vh - 220px), 900px);
        min-height: 0;
    }

    [data-permission-management] .permission-editor > .card-body {
        display: flex;
        min-height: 0;
        flex-direction: column;
    }

    [data-permission-management] .permission-content-scroll {
        min-height: 0;
        flex: 1 1 auto;
        overflow-x: hidden;
        overflow-y: auto;
        scrollbar-gutter: stable;
        padding-right: 0.35rem;
    }

    [data-permission-management] .permission-user-list {
        max-height: min(560px, 42vh);
        min-height: 8rem;
        display: grid;
        gap: 0.35rem;
        overflow-x: hidden;
        overflow-y: auto;
        scrollbar-gutter: stable;
    }

    [data-permission-management] .permission-user {
        width: 100%;
        border: 1px solid transparent;
        border-radius: 0.55rem;
        background: transparent;
        color: var(--bs-body-color);
        text-align: left;
    }

    [data-permission-management] .permission-user:hover,
    [data-permission-management] .permission-user:focus-visible {
        background: var(--bs-tertiary-bg);
        border-color: var(--bs-border-color);
    }

    [data-permission-management] .permission-user.is-selected {
        background: rgba(var(--bs-primary-rgb), 0.1);
        border-color: var(--bs-primary);
    }

    [data-permission-management] .permission-avatar {
        width: 2.25rem;
        height: 2.25rem;
        flex: 0 0 2.25rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        background: var(--bs-secondary-bg);
        color: var(--bs-emphasis-color);
        font-size: 0.75rem;
        font-weight: 700;
    }

    [data-permission-management] .permission-user-meta {
        min-width: 0;
    }

    [data-permission-management] .permission-user-email {
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    [data-permission-management] .permission-user .badge-status {
        padding: 3px 8px;
        font-size: 0.68rem;
    }

    [data-permission-management] .permission-group {
        border: 1px solid var(--bs-border-color);
        border-radius: 0.65rem;
        background: var(--bs-body-bg);
    }

    [data-permission-management] .permission-group-header {
        border-bottom: 1px solid var(--bs-border-color);
        background: var(--bs-tertiary-bg);
    }

    [data-permission-management] .permission-item {
        border-bottom: 1px solid var(--bs-border-color-translucent);
    }

    [data-permission-management] .permission-item:last-child {
        border-bottom: 0;
    }

    [data-permission-management] .permission-item:hover {
        background: var(--bs-tertiary-bg);
    }

    [data-permission-management] .permission-description {
        max-width: 52rem;
        line-height: 1.4;
    }

    [data-permission-management] .permission-toolbar-search {
        flex: 1 1 18rem;
        max-width: 26rem;
    }

    [data-permission-management] .permission-toolbar-actions {
        margin-left: auto;
    }

    [data-permission-management] .permission-summary-value {
        font-size: 1.15rem;
        font-weight: 600;
    }

    [data-permission-management] .permission-status {
        min-height: 1.5rem;
    }

    [data-permission-management] .permission-save-footer {
        flex: 0 0 auto;
        margin: 1rem -1.5rem -1.5rem;
        padding: 0.9rem 1.5rem;
        border-top: 1px solid var(--bs-border-color);
        background: color-mix(in srgb, var(--bs-body-bg) 94%, transparent);
    }

    [data-permission-management] .permission-super-admin {
        border: 1px solid var(--bs-success-border-subtle);
        background: var(--bs-success-bg-subtle);
    }

    [data-permission-management] .permission-detail-key {
        overflow-wrap: anywhere;
    }

    @media (max-width: 575.98px) {
        [data-permission-management] .permission-editor {
            height: calc(100vh - 190px);
            min-height: 480px;
        }

        [data-permission-management] .permission-save-footer {
            margin-left: -1rem;
            margin-right: -1rem;
            padding-left: 1rem;
            padding-right: 1rem;
        }

        [data-permission-management] .permission-save-footer .btn {
            flex: 1 1 auto;
        }
    }
</style>
@endsection

@section('content')
<div class="container-fluid py-3" data-permission-management>
    <div class="permission-shell">
        <div class="row g-4">
            <div class="col-lg-4">
                <div class="card permission-sidebar">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <h5 class="mb-1">Users</h5>
                                <p class="small text-muted mb-0"><span id="permission-user-count">{{ count($users) }}</span> users available</p>
                            </div>
                            <i class="bi bi-people fs-4 text-primary" aria-hidden="true"></i>
                        </div>
                        <label for="permission-user-search" class="form-label small fw-semibold">Search users</label>
                        <div class="input-group mb-3">
                            <span class="input-group-text"><i class="bi bi-search" aria-hidden="true"></i></span>
                            <input id="permission-user-search" class="form-control" type="search" placeholder="Name, email, or role" autocomplete="off">
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <label class="form-label small fw-semibold mb-0" for="permission-user-list">User list</label>
                            <span class="small text-muted" id="permission-user-result-count" aria-live="polite"></span>
                        </div>
                        <div id="permission-user-list" class="permission-user-list" role="listbox" aria-label="Users" aria-describedby="permission-user-help">
                            @foreach ($users as $user)
                                @php
                                    $userStatus = trim((string) $user['status']);
                                    $userStatusClass = in_array(strtolower($userStatus), ['active', 'aktif'], true)
                                        ? 'accepted'
                                        : 'blacklist';
                                @endphp
                                <button type="button" class="permission-user d-flex align-items-center gap-3 p-2" role="option" aria-selected="false" data-email="{{ $user['email'] }}" data-search="{{ strtolower($user['fullName'] . ' ' . $user['email'] . ' ' . $user['role']) }}">
                                    <span class="permission-avatar" aria-hidden="true">{{ collect(explode(' ', trim($user['fullName'])))->filter()->map(fn ($part) => strtoupper(substr($part, 0, 1)))->take(2)->implode('') }}</span>
                                    <span class="permission-user-meta flex-grow-1">
                                        <span class="d-block fw-semibold text-truncate">{{ $user['fullName'] }}</span>
                                        <span class="permission-user-email d-block small text-muted">{{ $user['email'] }}</span>
                                        <span class="d-flex flex-wrap gap-1 mt-1">
                                            <span class="badge-status hold permission-role-badge">{{ $user['role'] }}</span>
                                            <span class="badge-status {{ $userStatusClass }}">{{ $user['status'] }}</span>
                                        </span>
                                    </span>
                                </button>
                            @endforeach
                        </div>
                        <div id="permission-user-no-results" class="d-none border rounded p-3 text-center text-muted small">No users found</div>
                        <div id="permission-user-help" class="form-text mt-3">Use the search field or keyboard to choose a user.</div>
                        <div id="permission-user-summary" class="border rounded p-3 mt-3 small text-muted">No user selected.</div>
                    </div>
                </div>
            </div>
            <div class="col-lg-8">
                <div class="card permission-editor h-100">
                    <div class="card-body p-4">
                        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
                            <div>
                                <h5 class="mb-1">User permissions</h5>
                                <p class="small text-muted mb-0">Enable only the access this user needs.</p>
                            </div>
                            <div class="d-flex gap-3 text-end" aria-live="polite">
                                <div><div class="permission-summary-value" id="permission-active-count">0</div><div class="small text-muted">Active</div></div>
                                <div><div class="permission-summary-value" id="permission-total-count">0</div><div class="small text-muted">Total</div></div>
                            </div>
                        </div>
                        <div id="permission-status" class="permission-status small text-muted mb-3" aria-live="polite">No unsaved changes</div>
                        <div id="permission-loading" class="text-muted d-none py-4 text-center" aria-live="polite"><span class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>Loading permissions...</div>
                        <div id="permission-empty" class="border rounded p-5 text-center text-muted">
                            <i class="bi bi-person-check fs-1 d-block mb-2"></i>
                            <div class="fw-semibold">No user selected</div>
                            <div class="small">Choose a user on the left to manage their permissions.</div>
                        </div>
                        <div id="permission-super-admin" class="permission-super-admin rounded p-4 d-none" role="status">
                            <div class="d-flex gap-3">
                                <i class="bi bi-shield-check fs-3 text-success" aria-hidden="true"></i>
                                <div>
                                    <h6 class="mb-1">Full System Access</h6>
                                    <p class="mb-0 small">Super Admin has full access through the system authorization wildcard. Individual permission assignments are not required, and the settings below are not used to determine access.</p>
                                </div>
                            </div>
                        </div>
                        <div id="permission-toolbar" class="d-none d-flex flex-wrap align-items-center gap-2 mb-3">
                            <div class="input-group input-group-sm permission-toolbar-search">
                                <span class="input-group-text"><i class="bi bi-search" aria-hidden="true"></i></span>
                                <input id="permission-search" class="form-control" type="search" placeholder="Search permissions" autocomplete="off">
                            </div>
                            <div class="permission-toolbar-actions d-flex gap-2">
                                <button type="button" class="btn btn-sm btn-outline-secondary" id="permission-select-all"><i class="bi bi-check2-square me-1"></i>Select all</button>
                                <button type="button" class="btn btn-sm btn-outline-danger" id="permission-clear-all"><i class="bi bi-x-circle me-1"></i>Clear all</button>
                            </div>
                        </div>
                        <div class="permission-content-scroll">
                            <div id="permission-no-results" class="d-none border rounded p-4 text-center text-muted small">No permissions found</div>
                            <div id="permission-groups" class="row g-3"></div>
                        </div>
                        <div class="permission-save-footer d-flex flex-wrap justify-content-between align-items-center gap-3">
                            <div class="small text-muted"><i class="bi bi-info-circle me-1"></i><span id="permission-save-hint">Changes are saved as user permissions.</span></div>
                            <button type="button" class="btn btn-primary" id="permission-save" disabled>
                                <i class="bi bi-save me-1"></i><span id="permission-save-label">Save permissions</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="permission-detail-modal" tabindex="-1" aria-labelledby="permission-detail-title" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="permission-detail-title">Permission details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p id="permission-detail-description" class="mb-4"></p>
                <dl class="row mb-0 small">
                    <dt class="col-sm-4 text-muted">Permission key</dt>
                    <dd class="col-sm-8"><code id="permission-detail-key" class="permission-detail-key"></code></dd>
                    <dt class="col-sm-4 text-muted">Group</dt>
                    <dd class="col-sm-8" id="permission-detail-group"></dd>
                </dl>
            </div>
        </div>
    </div>
</div>

<script nonce="{{ request()->attributes->get('csp_nonce') }}">
document.addEventListener('DOMContentLoaded', function () {
    const root = document.querySelector('[data-permission-management]');
    const userList = document.getElementById('permission-user-list');
    const userButtons = Array.from(userList.querySelectorAll('[data-email]'));
    const search = document.getElementById('permission-user-search');
    const groups = document.getElementById('permission-groups');
    const save = document.getElementById('permission-save');
    const saveLabel = document.getElementById('permission-save-label');
    const saveHint = document.getElementById('permission-save-hint');
    const loading = document.getElementById('permission-loading');
    const status = document.getElementById('permission-status');
    const summary = document.getElementById('permission-user-summary');
    const emptyState = document.getElementById('permission-empty');
    const superAdminState = document.getElementById('permission-super-admin');
    const toolbar = document.getElementById('permission-toolbar');
    const permissionSearch = document.getElementById('permission-search');
    const permissionNoResults = document.getElementById('permission-no-results');
    const userNoResults = document.getElementById('permission-user-no-results');
    const userResultCount = document.getElementById('permission-user-result-count');
    const activeCount = document.getElementById('permission-active-count');
    const totalCount = document.getElementById('permission-total-count');
    const selectAll = document.getElementById('permission-select-all');
    const clearAll = document.getElementById('permission-clear-all');
    const detailModalElement = document.getElementById('permission-detail-modal');
    const detailModal = window.bootstrap ? new bootstrap.Modal(detailModalElement) : null;
    const detailTitle = document.getElementById('permission-detail-title');
    const detailDescription = document.getElementById('permission-detail-description');
    const detailKey = document.getElementById('permission-detail-key');
    const detailGroup = document.getElementById('permission-detail-group');
    const csrf = document.querySelector('meta[name="csrf-token"]').content;
    const users = @json($users);
    const catalog = @json($permissionGroups);
    const allPermissions = Object.values(catalog).flat();
    let selectedEmail = '';
    let selectedUser = null;
    let originalPermissions = new Set();
    let currentPermissions = new Set();
    let isSuperAdmin = false;
    let isLoading = false;
    let isSaving = false;

    function showAlert(message, type) {
        if (typeof window.showToast === 'function') {
            window.showToast(message, type === 'danger' ? 'error' : type);
        }
    }

    function escapeHtml(value) {
        return String(value ?? '').replace(/[&<>'"]/g, character => ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            "'": '&#39;',
            '"': '&quot;'
        }[character]));
    }

    function getInitials(name) {
        return String(name || '').trim().split(/\s+/).filter(Boolean).slice(0, 2).map(part => part.charAt(0).toUpperCase()).join('') || '--';
    }

    function getStatusBadgeClass(status) {
        return ['active', 'aktif'].includes(String(status || '').trim().toLowerCase()) ? 'accepted' : 'blacklist';
    }

    function sortedPermissionKeys(set) {
        return Array.from(set).sort();
    }

    function hasDirtyChanges() {
        return sortedPermissionKeys(currentPermissions).join('|') !== sortedPermissionKeys(originalPermissions).join('|');
    }

    function updateDirtyState() {
        const dirty = !isSuperAdmin && hasDirtyChanges();
        status.textContent = dirty ? 'Unsaved changes' : 'No unsaved changes';
        status.className = dirty ? 'permission-status small text-warning fw-semibold mb-3' : 'permission-status small text-muted mb-3';
        save.disabled = !selectedUser || isSuperAdmin || !dirty || isSaving || isLoading;
        saveHint.textContent = dirty ? 'Review your changes before saving.' : 'No changes to save.';
    }

    function updateCounts() {
        activeCount.textContent = currentPermissions.size;
        totalCount.textContent = isSuperAdmin ? allPermissions.length : document.querySelectorAll('.permission-checkbox').length;
        document.querySelectorAll('[data-group-checkboxes]').forEach(group => {
            const checkboxes = group.querySelectorAll('.permission-checkbox');
            const checked = group.querySelectorAll('.permission-checkbox:checked');
            const toggle = group.querySelector('[data-group-toggle]');
            const counter = group.querySelector('[data-group-counter]');
            if (toggle) {
                toggle.checked = checkboxes.length > 0 && checked.length === checkboxes.length;
                toggle.indeterminate = checked.length > 0 && checked.length < checkboxes.length;
            }
            if (counter) counter.textContent = `${checked.length} / ${checkboxes.length}`;
        });
        updateDirtyState();
    }

    function permissionMatches(permission, query) {
        return `${permission.name} ${permission.key} ${permission.description}`.toLowerCase().includes(query);
    }

    function renderGroups() {
        groups.innerHTML = Object.entries(catalog).map(([group, permissions]) => `
            <div class="col-xl-6">
                <section class="permission-group h-100" data-group-checkboxes>
                    <div class="permission-group-header d-flex justify-content-between align-items-center gap-2 px-3 py-2">
                        <h6 class="mb-0">${escapeHtml(group)}</h6>
                        <span class="small text-muted" data-group-counter>0 / ${permissions.length}</span>
                        <label class="small text-muted d-flex align-items-center gap-2 mb-0">
                            <input class="form-check-input mt-0" type="checkbox" data-group-toggle aria-label="Select all permissions in ${escapeHtml(group)}">
                            <span class="d-none d-sm-inline">Select all</span>
                        </label>
                    </div>
                    <div class="px-3">
                    ${permissions.map(permission => {
                        const rawKey = permission.key;
                        const key = escapeHtml(rawKey);
                        const label = escapeHtml(permission.name);
                        const description = escapeHtml(permission.description || 'Tidak ada detail tambahan untuk permission ini.');
                        const id = `permission-${rawKey.replace(/[^a-z0-9]+/gi, '-')}`;
                        return `<div class="permission-item py-3 d-flex gap-3">
                            <input class="form-check-input permission-checkbox flex-shrink-0 mt-1" type="checkbox" value="${key}" id="${id}" ${currentPermissions.has(rawKey) ? 'checked' : ''}>
                            <label class="form-check-label flex-grow-1" for="${id}">
                                <span class="fw-semibold d-block">${label}</span>
                                <span class="permission-description d-block small text-muted mt-1">${description}</span>
                                <code class="small text-muted">${key}</code>
                            </label>
                            <button type="button" class="btn btn-sm btn-link text-secondary p-0 align-self-start permission-detail" data-key="${key}" data-group="${escapeHtml(group)}" aria-label="Show details for ${label}" title="Show permission details"><i class="bi bi-info-circle" aria-hidden="true"></i></button>
                        </div>`;
                    }).join('')}
                    </div>
                </section>
            </div>`).join('');
        emptyState.classList.add('d-none');
        applyPermissionFilter();
        updateCounts();
    }

    function renderUserSummary(user) {
        summary.innerHTML = `<div class="fw-semibold text-body mb-1">${escapeHtml(user.fullName)}</div>
            <div>${escapeHtml(user.email)}</div>
            <div class="d-flex flex-wrap gap-1 mt-2">
                <span class="badge-status hold permission-role-badge">${escapeHtml(user.role)}</span>
                <span class="badge-status ${getStatusBadgeClass(user.status)}">${escapeHtml(user.status)}</span>
            </div>`;
    }

    function renderUserSelection(email) {
        userButtons.forEach(button => {
            const selected = button.dataset.email === email;
            button.classList.toggle('is-selected', selected);
            button.setAttribute('aria-selected', selected ? 'true' : 'false');
            if (selected) button.setAttribute('aria-current', 'true');
            else button.removeAttribute('aria-current');
        });
    }

    function resetEditor() {
        groups.innerHTML = '';
        toolbar.classList.add('d-none');
        superAdminState.classList.add('d-none');
        emptyState.classList.remove('d-none');
        permissionNoResults.classList.add('d-none');
        activeCount.textContent = '0';
        totalCount.textContent = '0';
        summary.textContent = 'No user selected.';
        status.textContent = 'No unsaved changes';
        updateDirtyState();
    }

    function updateUserList() {
        const query = search.value.trim().toLowerCase();
        let visible = 0;
        userButtons.forEach(button => {
            const matches = !query || button.dataset.search.includes(query);
            button.classList.toggle('d-none', !matches);
            if (matches) visible += 1;
        });
        userNoResults.classList.toggle('d-none', visible > 0);
        userResultCount.textContent = query ? `${visible} found` : '';
    }

    function applyPermissionFilter() {
        const query = permissionSearch.value.trim().toLowerCase();
        let visible = 0;
        groups.querySelectorAll('[data-group-checkboxes]').forEach(group => {
            let groupVisible = 0;
            group.querySelectorAll('.permission-item').forEach(row => {
                const permission = allPermissions.find(item => item.key === row.querySelector('.permission-checkbox').value);
                const matches = !query || (permission && permissionMatches(permission, query));
                row.classList.toggle('d-none', !matches);
                if (matches) {
                    groupVisible += 1;
                    visible += 1;
                }
            });
            group.closest('.col-xl-6').classList.toggle('d-none', groupVisible === 0);
        });
        permissionNoResults.classList.toggle('d-none', visible > 0 || !query);
    }

    function confirmDiscard() {
        return !hasDirtyChanges() || window.confirm('You have unsaved permission changes. Discard changes and switch user?');
    }

    async function loadUser(email) {
        if (!email || isLoading || !confirmDiscard()) {
            renderUserSelection(selectedEmail);
            return;
        }
        selectedEmail = email;
        selectedUser = users.find(user => user.email === email) || null;
        renderUserSelection(email);
        isLoading = true;
        loading.classList.remove('d-none');
        groups.innerHTML = '';
        toolbar.classList.add('d-none');
        superAdminState.classList.add('d-none');
        emptyState.classList.add('d-none');
        updateDirtyState();
        try {
            const response = await fetch(`{{ url('/hr/permissions') }}/${encodeURIComponent(email)}`, { headers: { Accept: 'application/json' } });
            if (!response.ok) throw new Error(response.status === 403 ? 'You are not authorized to manage permissions.' : response.status === 404 ? 'User not found.' : 'Failed to load permissions.');
            const data = await response.json();
            selectedUser = data.user;
            renderUserSummary(data.user);
            isSuperAdmin = data.user.role.toLowerCase() === 'super admin';
            originalPermissions = new Set(Object.entries(data.permissions).filter(([, enabled]) => enabled).map(([key]) => key));
            currentPermissions = new Set(originalPermissions);
            if (isSuperAdmin) {
                activeCount.textContent = allPermissions.length;
                totalCount.textContent = allPermissions.length;
                superAdminState.classList.remove('d-none');
            } else {
                toolbar.classList.remove('d-none');
                renderGroups();
            }
        } catch (error) {
            showAlert(error.message, 'danger');
            resetEditor();
        } finally {
            isLoading = false;
            loading.classList.add('d-none');
            updateDirtyState();
        }
    }

    search.addEventListener('input', updateUserList);
    permissionSearch.addEventListener('input', applyPermissionFilter);
    userButtons.forEach((button, index) => {
        button.addEventListener('click', () => loadUser(button.dataset.email));
        button.addEventListener('keydown', event => {
            const visibleButtons = userButtons.filter(item => !item.classList.contains('d-none'));
            const currentIndex = visibleButtons.indexOf(button);
            let nextIndex = currentIndex;
            if (event.key === 'ArrowDown') nextIndex = Math.min(currentIndex + 1, visibleButtons.length - 1);
            if (event.key === 'ArrowUp') nextIndex = Math.max(currentIndex - 1, 0);
            if (event.key === 'Home') nextIndex = 0;
            if (event.key === 'End') nextIndex = visibleButtons.length - 1;
            if (nextIndex !== currentIndex) {
                event.preventDefault();
                visibleButtons[nextIndex]?.focus();
            }
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                loadUser(button.dataset.email);
            }
        });
    });

    groups.addEventListener('change', function (event) {
        if (!event.target.matches('.permission-checkbox, [data-group-toggle]')) return;
        if (event.target.matches('[data-group-toggle]')) {
            event.target.closest('[data-group-checkboxes]').querySelectorAll('.permission-checkbox:not(.d-none)').forEach(checkbox => {
                checkbox.checked = event.target.checked;
            });
        }
        currentPermissions = new Set(Array.from(document.querySelectorAll('.permission-checkbox:checked')).map(input => input.value));
        updateCounts();
    });

    groups.addEventListener('click', function (event) {
        const detailButton = event.target.closest('.permission-detail');
        if (!detailButton) return;
        const permission = allPermissions.find(item => item.key === detailButton.dataset.key);
        if (!permission) return;
        detailTitle.textContent = permission.name;
        detailDescription.textContent = permission.description || 'No additional description is available for this permission.';
        detailKey.textContent = permission.key;
        detailGroup.textContent = detailButton.dataset.group;
        if (detailModal) detailModal.show();
    });

    selectAll.addEventListener('click', function () {
        currentPermissions = new Set(allPermissions.map(permission => permission.key));
        groups.querySelectorAll('.permission-checkbox').forEach(checkbox => checkbox.checked = true);
        updateCounts();
    });

    clearAll.addEventListener('click', function () {
        if (isSuperAdmin || !window.confirm('Remove all permissions? This will remove all explicit permissions from this user.')) return;
        currentPermissions = new Set();
        groups.querySelectorAll('.permission-checkbox').forEach(checkbox => checkbox.checked = false);
        updateCounts();
    });

    save.addEventListener('click', async function () {
        if (!selectedEmail || !hasDirtyChanges() || isSaving || isSuperAdmin) return;
        isSaving = true;
        save.disabled = true;
        save.classList.add('disabled');
        saveLabel.textContent = 'Saving...';
        try {
            const response = await fetch(`{{ url('/hr/permissions') }}/${encodeURIComponent(selectedEmail)}`, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrf },
                body: JSON.stringify({ permissions: sortedPermissionKeys(currentPermissions) })
            });
            const data = await response.json().catch(() => ({}));
            if (!response.ok) throw new Error(response.status === 419 ? 'Your session has expired. Refresh the page and try again.' : response.status === 403 ? 'You are not authorized to save permissions.' : data.message || 'Failed to save permissions.');
            originalPermissions = new Set(currentPermissions);
            showAlert(data.message || 'Permission updated successfully.', 'success');
        } catch (error) {
            showAlert(error.message, 'danger');
        } finally {
            isSaving = false;
            saveLabel.textContent = 'Save permissions';
            save.disabled = false;
            save.classList.remove('disabled');
            updateDirtyState();
        }
    });

    updateUserList();
    resetEditor();
});
</script>
@endsection