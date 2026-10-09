@foreach ($assets as $asset)
    <div class="modal fade" id="asset-history-{{ $asset->id }}" tabindex="-1" aria-labelledby="asset-history-title-{{ $asset->id }}" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title" id="asset-history-title-{{ $asset->id }}">Riwayat Penugasan</h5>
                        <div class="small text-muted">{{ $asset->asset_code ?: '—' }} · {{ $asset->name }}</div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <div class="table-responsive">
                        <table class="table hr-table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Karyawan</th>
                                    <th>ID Karyawan</th>
                                    <th>Tanggal Penugasan</th>
                                    <th>Tanggal Pengembalian</th>
                                    <th>Status</th>
                                    <th>Catatan Penugasan</th>
                                    <th>Catatan Pengembalian</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($asset->assignments as $assignment)
                                    <tr>
                                        <td>{{ $assignment->employee_name ?: '—' }}</td>
                                        <td>{{ $assignment->employee_id }}</td>
                                        <td>{{ $assignment->assigned_date?->format('d/m/Y') ?: '—' }}</td>
                                        <td>{{ $assignment->return_date?->format('d/m/Y') ?: '—' }}</td>
                                        <td>
                                            <span class="badge-status {{ $assignment->status === 'active' ? 'hold' : 'accepted' }}">
                                                {{ $assignment->status === 'active' ? 'Aktif' : 'Dikembalikan' }}
                                            </span>
                                        </td>
                                        <td>{{ $assignment->assignment_notes ?: '—' }}</td>
                                        <td>{{ $assignment->return_notes ?: '—' }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="7" class="text-center text-muted py-4">Belum ada riwayat penugasan.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @if ($asset->activeAssignment)
                    @can($permissionPrefix . '.return')
                        <div class="modal-footer justify-content-between">
                            <div class="small text-muted">
                                Saat ini ditugaskan kepada
                                <strong>{{ $asset->activeAssignment->employee_name ?: $asset->activeAssignment->employee_id }}</strong>
                            </div>
                            <form method="POST" action="{{ route($routeName . '.return', $asset->id) }}" class="d-flex flex-wrap gap-2 align-items-end">
                                @csrf
                                <div>
                                    <label class="form-label small mb-1" for="return-date-{{ $asset->id }}">Tanggal Kembali</label>
                                    <input id="return-date-{{ $asset->id }}" type="date" name="return_date" class="form-control form-control-sm" value="{{ now()->toDateString() }}" required>
                                </div>
                                <div>
                                    <label class="form-label small mb-1" for="return-notes-{{ $asset->id }}">Catatan</label>
                                    <input id="return-notes-{{ $asset->id }}" type="text" name="return_notes" class="form-control form-control-sm" maxlength="1000">
                                </div>
                                <button class="btn btn-warning btn-sm" type="submit">
                                    <i class="bi bi-arrow-return-left me-1"></i>Catat Pengembalian
                                </button>
                            </form>
                        </div>
                    @endcan
                @endif
            </div>
        </div>
    </div>

    @can($permissionPrefix . '.assign')
        @if (($asset->status === \App\Enums\AssetStatus::AVAILABLE && !$asset->activeAssignment) || (int) session('open_asset_assign_modal') === (int) $asset->id)
            <div class="modal fade" id="asset-assign-{{ $asset->id }}" tabindex="-1" aria-labelledby="asset-assign-title-{{ $asset->id }}" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <form method="POST" action="{{ route($routeName . '.assign', $asset->id) }}" class="modal-content">
                        @csrf
                        <div class="modal-header">
                            <div>
                                <h5 class="modal-title" id="asset-assign-title-{{ $asset->id }}">Tugaskan Aset</h5>
                                <div class="small text-muted">{{ $asset->asset_code ?: '—' }} · {{ $asset->name }}</div>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                        </div>
                        <div class="modal-body">
                            @if ((int) session('open_asset_assign_modal') === (int) $asset->id && $errors->any())
                                <div class="alert alert-danger py-2">
                                    @foreach ($errors->all() as $error)
                                        <div>{{ $error }}</div>
                                    @endforeach
                                </div>
                            @endif
                            <div class="mb-3">
                                <label for="employee-search-{{ $asset->id }}" class="form-label">Cari Karyawan <span class="text-danger">*</span></label>
                                <input
                                    id="employee-search-{{ $asset->id }}"
                                    type="search"
                                    class="form-control"
                                    value="{{ (int) session('open_asset_assign_modal') === (int) $asset->id ? old('employee_id') : '' }}"
                                    placeholder="Ketik nama atau ID karyawan..."
                                    autocomplete="off"
                                    role="combobox"
                                    aria-autocomplete="list"
                                    aria-expanded="false"
                                    aria-controls="employee-results-{{ $asset->id }}"
                                    data-employee-search
                                    data-initial-employee-id="{{ (int) session('open_asset_assign_modal') === (int) $asset->id ? old('employee_id') : '' }}">
                                <input
                                    type="hidden"
                                    name="employee_id"
                                    value="{{ (int) session('open_asset_assign_modal') === (int) $asset->id ? old('employee_id') : '' }}"
                                    data-employee-id>
                                <div class="list-group shadow-sm mt-1 d-none" id="employee-results-{{ $asset->id }}" role="listbox" data-employee-results></div>
                                <div class="border rounded-3 p-3 mt-2 d-none" aria-live="polite" data-employee-selected></div>
                                <div class="form-text" aria-live="polite" data-employee-result>Mulai ketik nama atau ID karyawan untuk mencari.</div>
                            </div>
                            <div class="mb-3">
                                <label for="assigned-date-{{ $asset->id }}" class="form-label">Tanggal Penugasan</label>
                                <input id="assigned-date-{{ $asset->id }}" type="date" name="assigned_date" class="form-control" value="{{ (int) session('open_asset_assign_modal') === (int) $asset->id ? old('assigned_date', now()->toDateString()) : now()->toDateString() }}" required>
                            </div>
                            <div>
                                <label for="assignment-notes-{{ $asset->id }}" class="form-label">Catatan</label>
                                <textarea id="assignment-notes-{{ $asset->id }}" name="assignment_notes" class="form-control" rows="3">{{ (int) session('open_asset_assign_modal') === (int) $asset->id ? old('assignment_notes') : '' }}</textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-primary"><i class="bi bi-person-check me-1"></i>Simpan Penugasan</button>
                        </div>
                    </form>
                </div>
            </div>
        @endif
    @endcan
@endforeach

@can($permissionPrefix . '.delete')
    <div class="modal fade" id="asset-disposal-confirm-modal" tabindex="-1" aria-labelledby="asset-disposal-confirm-title" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form method="POST" id="asset-disposal-confirm-form" class="modal-content">
                @csrf
                @method('DELETE')
                <div class="modal-header">
                    <h5 class="modal-title text-danger" id="asset-disposal-confirm-title">
                        <i class="bi bi-trash me-1"></i>Konfirmasi Disposisi Aset
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-3">Aset ini akan ditandai sebagai <strong>Disposisi</strong> dan tetap tersimpan dalam riwayat. Lanjutkan?</p>
                    <div class="border rounded p-3 bg-light">
                        <div class="row g-2">
                            <div class="col-4 text-muted small">Kode</div>
                            <div class="col-8 fw-semibold id-mono" id="asset-disposal-code">—</div>
                            <div class="col-4 text-muted small">Nama</div>
                            <div class="col-8 fw-semibold" id="asset-disposal-name">—</div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-trash me-1"></i>Disposisi Aset
                    </button>
                </div>
            </form>
        </div>
    </div>
@endcan

<script nonce="{{ request()->attributes->get('csp_nonce') }}">
    (() => {
        const lookupUrl = @json($employeeLookupUrl);

        document.querySelectorAll('[data-employee-search]').forEach((input) => {
            const form = input.closest('form');
            const employeeIdInput = form.querySelector('[data-employee-id]');
            const results = form.querySelector('[data-employee-results]');
            const selected = form.querySelector('[data-employee-selected]');
            const result = form.querySelector('[data-employee-result]');
            const modal = input.closest('.modal');
            let timer;
            let searchSequence = 0;
            let activeRequest;

            const hideResults = () => {
                results.classList.add('d-none');
                input.setAttribute('aria-expanded', 'false');
                input.removeAttribute('aria-activedescendant');
            };

            const showMessage = (message, className = 'text-muted') => {
                results.replaceChildren();
                const item = document.createElement('div');
                item.className = 'list-group-item ' + className;
                item.textContent = message;
                results.append(item);
                results.classList.remove('d-none');
                input.setAttribute('aria-expanded', 'true');
            };

            const clearSelection = () => {
                employeeIdInput.value = '';
                selected.replaceChildren();
                selected.classList.add('d-none');
            };

            const chooseEmployee = (employee) => {
                employeeIdInput.value = employee.employeeId || '';
                selected.replaceChildren();
                selected.classList.remove('d-none');

                const name = document.createElement('div');
                name.className = 'fw-semibold';
                name.textContent = employee.fullName || employee.employeeId || 'Karyawan';
                selected.append(name);

                const details = [
                    employee.employeeId,
                    employee.jobPosition,
                    [employee.division, employee.department].filter(Boolean).join(' / '),
                ].filter(Boolean);
                if (details.length) {
                    const meta = document.createElement('div');
                    meta.className = 'small text-muted mt-1';
                    meta.textContent = details.join(' · ');
                    selected.append(meta);
                }

                input.value = '';
                result.textContent = 'Karyawan dipilih. Ketik kembali untuk mengganti pilihan.';
                hideResults();
            };

            const searchEmployees = async (query, exactEmployeeId = false, sequence = ++searchSequence) => {
                activeRequest?.abort();
                activeRequest = new AbortController();
                const response = await fetch(lookupUrl + '?q=' + encodeURIComponent(query) + '&limit=8&scope=identity', {
                    headers: { Accept: 'application/json' },
                    signal: activeRequest.signal,
                });
                if (sequence !== searchSequence) return;
                if (!response.ok) {
                    throw new Error('Pencarian karyawan gagal (' + response.status + ').');
                }
                const payload = await response.json();
                if (sequence !== searchSequence) return;
                const employees = Array.isArray(payload.data) ? payload.data : [];

                if (exactEmployeeId) {
                    const employee = employees.find((item) => String(item.employeeId) === query);
                    if (employee) {
                        chooseEmployee(employee);
                    } else {
                        clearSelection();
                        input.value = query;
                        result.textContent = 'Karyawan sebelumnya tidak ditemukan. Cari dan pilih karyawan yang valid.';
                    }
                    hideResults();
                    return;
                }

                if (employees.length === 0) {
                    showMessage('Tidak ada karyawan yang cocok dengan "' + query + '".');
                    return;
                }

                results.replaceChildren();
                employees.forEach((employee, index) => {
                    const option = document.createElement('button');
                    option.type = 'button';
                    option.className = 'list-group-item list-group-item-action';
                    option.id = input.id + '-option-' + index;
                    option.setAttribute('role', 'option');
                    option.setAttribute('aria-selected', 'false');
                    option.dataset.employeeIndex = String(index);
                    option.textContent = employee.fullName || employee.employeeId || 'Karyawan';

                    const details = [
                        employee.employeeId,
                        employee.jobPosition,
                        [employee.division, employee.department].filter(Boolean).join(' / '),
                    ].filter(Boolean);
                    if (details.length) {
                        const meta = document.createElement('small');
                        meta.className = 'd-block text-muted';
                        meta.textContent = details.join(' · ');
                        option.append(meta);
                    }
                    option.addEventListener('click', () => chooseEmployee(employee));
                    results.append(option);
                });
                results.classList.remove('d-none');
                input.setAttribute('aria-expanded', 'true');
                result.textContent = employees.length + ' karyawan ditemukan. Pilih salah satu.';
            };

            input.addEventListener('input', () => {
                window.clearTimeout(timer);
                searchSequence++;
                activeRequest?.abort();
                clearSelection();
                hideResults();
                const query = input.value.trim();
                if (query.length < 2) {
                    result.textContent = 'Masukkan minimal 2 karakter untuk mencari karyawan.';
                    return;
                }
                result.textContent = 'Mencari karyawan...';
                showMessage('Mencari karyawan...', 'text-muted');
                const sequence = searchSequence;
                timer = window.setTimeout(() => {
                    searchEmployees(query, false, sequence).catch((error) => {
                        if (error.name === 'AbortError') return;
                        showMessage(error.message || 'Tidak dapat mencari karyawan.', 'text-danger');
                        result.textContent = 'Pencarian karyawan gagal.';
                    });
                }, 250);
            });

            input.addEventListener('keydown', (event) => {
                const options = Array.from(results.querySelectorAll('[role="option"]'));
                if (event.key === 'Escape') {
                    hideResults();
                } else if (event.key === 'ArrowDown' && options.length) {
                    event.preventDefault();
                    options[0].focus();
                } else if (event.key === 'Enter' && options.length) {
                    event.preventDefault();
                    options[0].click();
                }
            });

            results.addEventListener('keydown', (event) => {
                const option = event.target.closest('[role="option"]');
                if (!option) return;
                if (event.key === 'ArrowDown') {
                    event.preventDefault();
                    (option.nextElementSibling || results.querySelector('[role="option"]'))?.focus();
                } else if (event.key === 'ArrowUp') {
                    event.preventDefault();
                    const previous = option.previousElementSibling;
                    if (previous) previous.focus();
                    else input.focus();
                } else if (event.key === 'Escape') {
                    hideResults();
                    input.focus();
                }
            });

            form.addEventListener('submit', (event) => {
                if (employeeIdInput.value.trim()) return;
                event.preventDefault();
                result.textContent = 'Pilih karyawan dari hasil pencarian sebelum menyimpan penugasan.';
                input.focus();
            });

            modal?.addEventListener('shown.bs.modal', () => input.focus());

            const initialEmployeeId = input.dataset.initialEmployeeId;
            if (initialEmployeeId) {
                const sequence = ++searchSequence;
                employeeIdInput.value = '';
                result.textContent = 'Memulihkan pilihan karyawan...';
                searchEmployees(initialEmployeeId, true, sequence).catch((error) => {
                    if (error.name === 'AbortError') return;
                    clearSelection();
                    result.textContent = error.message || 'Pilihan karyawan sebelumnya tidak dapat dimuat.';
                });
            }
        });

        const modalId = @json(session('open_asset_assign_modal'));
        if (modalId) {
            document.addEventListener('DOMContentLoaded', () => {
                if (!window.bootstrap?.Modal) return;

                const modal = document.getElementById('asset-assign-' + modalId);
                if (modal) {
                    window.bootstrap.Modal.getOrCreateInstance(modal).show();
                }
            }, { once: true });
        }

        const disposalModal = document.getElementById('asset-disposal-confirm-modal');
        if (disposalModal) {
            disposalModal.addEventListener('show.bs.modal', (event) => {
                const trigger = event.relatedTarget;
                if (!trigger) return;

                document.getElementById('asset-disposal-confirm-form').action = trigger.dataset.disposalUrl;
                document.getElementById('asset-disposal-code').textContent = trigger.dataset.assetCode || '—';
                document.getElementById('asset-disposal-name').textContent = trigger.dataset.assetName || '—';
            });
        }
    })();
</script>
