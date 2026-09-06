(function () {
    'use strict';

    const CSRF = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const HEADERS = { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json', 'Content-Type': 'application/json' };

    // Domain-aware base path: MITO_ASSET_BASE on HRIS, '/assets' on the asset portal.
    // The view injects window.MITO_ASSET_BASE per active domain; default keeps HRIS behaviour.
    const MITO_ASSET_BASE = window.MITO_ASSET_BASE || '/hr/assets';


    const CATEGORY_META = {
        Building:   { label: 'Building',   badge: 'bg-secondary text-white', prefix: 'BLD', icon: 'bi-building' },
        Vehicle:    { label: 'Vehicle',    badge: 'bg-primary text-white',   prefix: 'VHL', icon: 'bi-truck' },
        Office:     { label: 'Office',     badge: 'bg-info text-white',      prefix: 'OFC', icon: 'bi-printer' },
        Elektronik: { label: 'Elektronik', badge: 'bg-success text-white',   prefix: 'ELK', icon: 'bi-cpu' },
    };
    const STATUS_META = {
        Available: 'bg-success text-white', Assigned: 'bg-primary text-white',
        Maintenance: 'bg-warning text-dark', Damaged: 'bg-danger text-white',
        Lost: 'bg-dark text-white', Disposed: 'bg-secondary text-white',
    };
    const COND_META = {
        Good: 'bg-success text-white', Fair: 'bg-info text-white',
        Poor: 'bg-warning text-dark', Damaged: 'bg-danger text-white',
    };

    function showToast(msg, type) { if (window.showToast) showToast(msg, type); }

    function setLoading(btn, loading) {
        btn.disabled = loading;
        if (loading) btn.dataset.origText = btn.innerHTML;
        btn.innerHTML = loading ? '<i class="bi bi-arrow-repeat fa-spin"></i>' : (btn.dataset.origText || btn.innerHTML);
    }

    function todayInputValue() {
        const d = new Date();
        return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
    }

    // Index page filter selects that auto-submit
    document.querySelectorAll('[data-auto-submit="true"]').forEach(function (sel) {
        sel.addEventListener('change', function () { sel.closest('form')?.submit(); });
    });

    // ═══ Category-aware form panels ════════════════════════════════

    function currentCategory() {
        return document.getElementById('assetFormCategory')?.value || '';
    }

    /**
     * Show only the panel for the selected category. Fields inside hidden
     * panels are disabled so they are never submitted; values stay in the
     * DOM and are preserved when the user switches back to the category.
     */
    function syncCategoryPanels() {
        const cat = currentCategory();
        const hint = document.getElementById('assetCatHint');
        if (hint) hint.style.display = cat ? 'none' : 'block';
        document.querySelectorAll('#assetForm .category-fields').forEach(function (panel) {
            const active = panel.dataset.category === cat;
            panel.style.display = active ? 'block' : 'none';
            panel.querySelectorAll('input, select, textarea').forEach(function (el) {
                el.disabled = !active;
            });
        });
    }

    document.getElementById('assetFormCategory')?.addEventListener('change', function () {
        clearAssetFormErrors();
        syncCategoryPanels();
    });

    // ═══ Asset form helpers ════════════════════════════════════════

    function clearAssetFormErrors() {
        document.querySelectorAll('#assetForm .is-invalid').forEach(function (el) {
            el.classList.remove('is-invalid');
        });
    }

    function clearAssetForm() {
        document.querySelectorAll('#assetForm input, #assetForm select, #assetForm textarea').forEach(function (el) {
            if (el.id === 'assetFormMethod' || el.id === 'assetFormId') return;
            if (el.type === 'checkbox' || el.type === 'radio') { el.checked = false; return; }
            if (el.tagName === 'SELECT') { el.selectedIndex = -1; return; }
            el.value = '';
        });
        document.getElementById('assetFormCode')?.removeAttribute('readonly');
        clearAssetFormErrors();
    }

    /** Defaults applied when creating a brand-new asset. */
    function applyAssetFormDefaults() {
        const cond = document.getElementById('assetFormCondition');
        const status = document.getElementById('assetFormStatus');
        if (cond) cond.value = 'Good';
        if (status) status.value = 'Available';
    }

    function setFormField(name, value) {
        if (value === null || value === undefined || value === '') return;
        document.querySelectorAll('#assetForm [name="' + name + '"]').forEach(function (el) {
            if (el.tagName === 'SELECT') {
                const ok = Array.from(el.options).some(function (o) { return o.value === String(value); });
                if (ok) el.value = String(value);
            } else if (el.type === 'date' || el.type === 'number') {
                el.value = String(value).slice(0, 10);
            } else {
                el.value = String(value);
            }
        });
    }

    /** Populate the whole asset form from an asset record. */
    function fillAssetForm(a) {
        clearAssetForm();
        setFormField('asset_code', a.asset_code);
        setFormField('category', a.category?.value || a.category);
        setFormField('name', a.name);
        setFormField('description', a.description);
        setFormField('brand', a.brand);
        setFormField('model', a.model);
        setFormField('serial_number', a.serial_number);
        setFormField('purchase_date', a.purchase_date);
        setFormField('purchase_price', a.purchase_price);
        setFormField('condition_status', a.condition_status?.value || a.condition_status || 'Good');
        setFormField('status', a.status?.value || a.status || 'Available');
        setFormField('location', a.location);
        setFormField('notes', a.notes);
        Object.keys(a).forEach(function (key) {
            if (['id', 'asset_code', 'category', 'name', 'description', 'brand', 'model',
                 'serial_number', 'purchase_date', 'purchase_price', 'condition_status',
                 'status', 'location', 'notes', 'created_by', 'updated_at', 'created_at',
                 'activeAssignment', 'active_assignment', 'assignments'].indexOf(key) !== -1) return;
            setFormField(key, a[key]);
        });
        syncCategoryPanels();
    }

    // ═══ Add modal open (reset only when creating) ═════════════════

    document.getElementById('addAssetModal')?.addEventListener('show.bs.modal', function () {
        const form = this.querySelector('#assetForm');
        const isNew = !form.querySelector('#assetFormId').value;
        if (isNew) {
            clearAssetForm();
            applyAssetFormDefaults();
            form.querySelector('#assetFormMethod').value = 'POST';
        }
        document.getElementById('addAssetModalLabel').textContent = isNew ? 'Tambah Aset Baru' : 'Edit Aset';
        syncCategoryPanels();
    });

    // ═══ Code preview (Add/Edit form) ══════════════════════════════

    document.getElementById('btnFormGenerateCode')?.addEventListener('click', async function () {
        const catKey = currentCategory();
        const meta = CATEGORY_META[catKey];
        if (!meta) return showToast('Pilih kategori aset terlebih dahulu.', 'error');
        setLoading(this, true);
        try {
            const res = await fetch(MITO_ASSET_BASE + '/preview-next-code/' + meta.prefix, { headers: HEADERS });
            const data = await res.json();
            if (res.ok && data.success) {
                document.getElementById('assetFormCode').value = data.code;
                showToast('Kode aset siap: ' + data.code, 'success');
            } else {
                showToast(data.message || 'Gagal generate kode.', 'error');
            }
        } catch (_) {
            showToast('Kesalahan jaringan.', 'error');
        } finally {
            setLoading(this, false);
        }
    });

    // ═══ Asset form submit (Add / Edit) ═════════════════════════════

    document.getElementById('assetForm')?.addEventListener('submit', async function (e) {
        e.preventDefault();
        const form = this;
        const id = form.querySelector('#assetFormId').value;
        const isEdit = !!id;
        const btn = form.querySelector('#btnAssetFormSubmit');
        const body = {};
        clearAssetFormErrors();
        new FormData(form).forEach(function (v, k) {
            if (k !== '_method' && k !== 'asset_id') body[k] = v;
        });
        if (!body.asset_code) delete body.asset_code;
        const url = isEdit ? MITO_ASSET_BASE + '/' + id : MITO_ASSET_BASE;
        const httpMethod = isEdit ? 'PUT' : 'POST';
        setLoading(btn, true);
        try {
            const res = await fetch(url, { method: httpMethod, headers: HEADERS, body: JSON.stringify(body) });
            const data = await res.json();
            if (res.ok && data.success) {
                showToast(data.message, 'success');
                bootstrap.Modal.getInstance(form.closest('.modal'))?.hide();
                setTimeout(function () { location.reload(); }, 600);
            } else {
                showToast(data.message || 'Gagal menyimpan.', 'error');
                if (data.errors) {
                    Object.keys(data.errors).forEach(function (name) {
                        form.querySelectorAll('[name="' + name + '"]').forEach(function (el) {
                            el.classList.add('is-invalid');
                        });
                    });
                }
            }
        } catch (_) {
            showToast('Kesalahan jaringan.', 'error');
        } finally {
            setLoading(btn, false);
        }
    });

    // ═══ Edit button ═══════════════════════════════════════════════

    document.querySelectorAll('.asset-edit-btn').forEach(function (btn) {
        btn.addEventListener('click', async function () {
            const id = this.dataset.assetId;
            try {
                const res = await fetch(MITO_ASSET_BASE + '/' + id + '/json', { headers: HEADERS });
                const data = await res.json();
                if (!data.success) return showToast('Gagal memuat data.', 'error');
                const form = document.getElementById('assetForm');
                form.querySelector('#assetFormId').value = data.asset.id;
                form.querySelector('#assetFormMethod').value = 'PUT';
                fillAssetForm(data.asset);
                document.getElementById('addAssetModalLabel').textContent = 'Edit Aset';
                new bootstrap.Modal(document.getElementById('addAssetModal')).show();
            } catch (_) {
                showToast('Gagal memuat data.', 'error');
            }
        });
    });

    // ═══ View button ═══════════════════════════════════════════════

    document.querySelectorAll('.asset-view-btn').forEach(function (btn) {
        btn.addEventListener('click', async function () {
            const id = this.dataset.assetId;
            const body = document.getElementById('viewAssetBody');
            body.innerHTML = '<div class="text-center py-4"><i class="bi bi-arrow-repeat"></i> Memuat...</div>';
            new bootstrap.Modal(document.getElementById('viewAssetModal')).show();
            try {
                const res = await fetch(MITO_ASSET_BASE + '/' + id + '/json', { headers: HEADERS });
                const data = await res.json();
                if (!data.success) {
                    body.innerHTML = '<div class="alert alert-danger mb-0">Gagal memuat data.</div>';
                    return;
                }
                body.innerHTML = buildDetailHtml(data.asset);
            } catch (_) {
                body.innerHTML = '<div class="alert alert-danger mb-0">Kesalahan jaringan.</div>';
            }
        });
    });


    // ═══ Detail modal HTML builder ═════════════════════════════════

    const CATEGORY_DETAIL_ROWS = {
        Building: {
            title: 'Detail Bangunan / Properti',
            rows: [
                ['property_type', 'Tipe Properti'], ['ownership_status', 'Status Kepemilikan'],
                ['address', 'Alamat'], ['floors', 'Jumlah Lantai'],
                ['area_sqm', 'Luas Bangunan (m²)'], ['land_area_sqm', 'Luas Tanah (m²)'],
                ['certificate_number', 'No. Sertifikat'], ['maintenance_schedule', 'Jadwal Pemeliharaan'],
            ],
        },
        Vehicle: {
            title: 'Detail Kendaraan',
            rows: [
                ['brand', 'Brand / Merek'], ['model', 'Model'],
                ['vehicle_type', 'Jenis Kendaraan'], ['license_plate', 'Plat Nomor'],
                ['year', 'Tahun'], ['vin', 'VIN / No. Rangka'], ['engine_number', 'No. Mesin'],
                ['color', 'Warna'], ['fuel_type', 'Bahan Bakar'], ['transmission', 'Transmisi'],
                ['stnk_number', 'No. STNK'], ['stnk_expiry', 'Masa Berlaku STNK'],
                ['bpkb_number', 'No. BPKB'], ['mileage', 'Kilometer (km)'],
                ['last_service_date', 'Service Terakhir'], ['next_service_date', 'Service Berikutnya'],
            ],
        },
        Office: {
            title: 'Detail Peralatan Kantor',
            rows: [
                ['brand', 'Brand / Merek'], ['model', 'Model'], ['serial_number', 'Serial Number'],
                ['equipment_type', 'Jenis Peralatan'], ['supplier', 'Supplier'],
                ['purchase_warranty', 'Garansi Pembelian'], ['warranty_start', 'Garansi Mulai'],
                ['warranty_expiry', 'Garansi Berakhir'], ['maintenance_schedule', 'Jadwal Pemeliharaan'],
            ],
        },
        Elektronik: {
            title: 'Detail Elektronik / IT',
            rows: [
                ['brand', 'Brand / Merek'], ['model', 'Model'], ['serial_number', 'Serial Number'],
                ['device_type', 'Jenis Perangkat'], ['processor', 'Prosesor'], ['ram', 'RAM'],
                ['storage', 'Penyimpanan'], ['storage_type', 'Tipe Penyimpanan'],
                ['operating_system', 'Sistem Operasi'], ['mac_address', 'MAC Address'],
                ['ip_address', 'IP Address'], ['hostname', 'Hostname'],
                ['warranty_start', 'Garansi Mulai'], ['warranty_expiry', 'Garansi Berakhir'],
            ],
        },
    };

    function escHtml(v) {
        return String(v === null || v === undefined ? '' : v).replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }

    function detailRowsHtml(def, a) {
        const filled = (def.rows || []).filter(function (r) {
            return a[r[0]] !== null && a[r[0]] !== undefined && a[r[0]] !== '';
        });
        if (!filled.length) return '';
        return '<div class="asset-detail-section-title">' + def.title + '</div>'
            + filled.map(function (r) {
                return '<div class="asset-detail-item"><div class="asset-detail-label">' + r[1]
                    + '</div><div class="asset-detail-value">' + escHtml(a[r[0]]) + '</div></div>';
            }).join('');
    }

    function detailItem(label, value) {
        return '<div class="asset-detail-item"><div class="asset-detail-label">' + label
            + '</div><div class="asset-detail-value">' + (value || '<em class="text-muted">—</em>') + '</div></div>';
    }


    function buildDetailHtml(a) {
        const catKey = a.category?.value || a.category || '';
        const catMeta = CATEGORY_META[catKey] || { label: catKey || '-', badge: '', icon: 'bi-box' };
        const statusVal = a.status?.value || a.status || '-';
        const condVal = a.condition_status?.value || a.condition_status || '-';
        const active = a.activeAssignment || a.active_assignment;
        const assignments = a.assignments || [];

        let assignBlock = '';
        if (active) {
            assignBlock = '<div class="asset-detail-section-title">Penugasan Aktif</div>'
                + '<div class="asset-detail-item"><div class="asset-detail-label">Ditugaskan Kepada</div>'
                + '<div class="asset-detail-value">' + escHtml(active.employee_name || active.employee_id) + '</div></div>'
                + '<div class="asset-detail-item"><div class="asset-detail-label">Sejak</div>'
                + '<div class="asset-detail-value">' + escHtml(String(active.assigned_date || '').slice(0, 10)) + '</div></div>';
        }

        const historyRows = assignments.length
            ? assignments.map(function (h) {
                return '<tr><td>' + escHtml(h.employee_id) + '</td><td>' + escHtml(h.employee_name || '-') + '</td>'
                    + '<td>' + escHtml(String(h.assigned_date || '-').slice(0, 10)) + '</td>'
                    + '<td>' + (h.return_date ? escHtml(String(h.return_date).slice(0, 10)) : '-') + '</td>'
                    + '<td><span class="badge-status ' + (h.status === 'active' ? 'bg-success text-white' : 'bg-secondary text-white') + '">' + escHtml(h.status) + '</span></td></tr>';
            }).join('')
            : '<tr><td colspan="5" class="text-center text-muted">Belum ada riwayat.</td></tr>';

        return '<div class="asset-detail-grid">'
            + '<div class="asset-detail-section-title">Informasi Umum</div>'
            + detailItem('Kode Aset', '<span class="fw-bold id-mono">' + escHtml(a.asset_code || '—') + '</span>')
            + '<div class="asset-detail-item"><div class="asset-detail-label">Kategori</div><div class="asset-detail-value"><span class="badge-status ' + catMeta.badge + '"><i class="bi ' + catMeta.icon + ' me-1"></i>' + catMeta.label + '</span></div></div>'
            + detailItem('Nama', escHtml(a.name))
            + detailItem('Deskripsi', escHtml(a.description))
            + detailItem('Lokasi', escHtml(a.location))
            + '<div class="asset-detail-section-title">Status & Kondisi</div>'
            + '<div class="asset-detail-item"><div class="asset-detail-label">Status</div><div class="asset-detail-value"><span class="badge-status ' + (STATUS_META[statusVal] || '') + '">' + statusVal + '</span></div></div>'
            + '<div class="asset-detail-item"><div class="asset-detail-label">Kondisi</div><div class="asset-detail-value"><span class="badge-status ' + (COND_META[condVal] || '') + '">' + condVal + '</span></div></div>'
            + '<div class="asset-detail-item"><div class="asset-detail-label">Tanggal Pembelian</div><div class="asset-detail-value">' + escHtml(String(a.purchase_date || '').slice(0, 10)) + '</div></div>'
            + '<div class="asset-detail-item"><div class="asset-detail-label">Harga Pembelian</div><div class="asset-detail-value">' + (a.purchase_price ? 'Rp ' + escHtml(Number(a.purchase_price).toLocaleString('id-ID')) : '-') + '</div></div>'
            + detailItem('Catatan', escHtml(a.notes))
            + detailRowsHtml(CATEGORY_DETAIL_ROWS[catKey] || { title: '', rows: [] }, a)
            + assignBlock
            + '<div class="asset-detail-section-title">Riwayat Penugasan</div>'
            + '<div style="grid-column:1/-1;"><table class="table table-sm asset-history-table">'
            + '<thead><tr><th>Employee ID</th><th>Nama</th><th>Tgl Assign</th><th>Tgl Return</th><th>Status</th></tr></thead>'
            + '<tbody>' + historyRows + '</tbody></table></div>'
            + '</div>';
    }


    // ═══ Assign ════════════════════════════════════════════════════

    document.querySelectorAll('.asset-assign-btn').forEach(function (btn) {
        btn.addEventListener('click', async function () {
            const id = this.dataset.assetId;
            try {
                const res = await fetch(MITO_ASSET_BASE + '/' + id + '/json', { headers: HEADERS });
                const data = await res.json();
                if (!data.success) return showToast('Gagal memuat data.', 'error');
                const a = data.asset;
                document.getElementById('assignAssetId').value = a.id;
                document.getElementById('assignAssetDisplay').value = (a.asset_code || '—') + ' — ' + a.name;
                resetAssignPicker();
                document.getElementById('assignNotes').value = '';
                const assignDate = document.getElementById('assignDate');
                if (assignDate) assignDate.value = todayInputValue();
                new bootstrap.Modal(document.getElementById('assignAssetModal')).show();
            } catch (_) {
                showToast('Kesalahan jaringan.', 'error');
            }
        });
    });

    // ═══ Assign employee picker (shared UX with Certification) ═════
    const aEmpSearch = document.getElementById('assignEmployeeSearch');
    const aEmpResults = document.getElementById('assignEmployeeResults');
    const aEmpError = document.getElementById('assignEmployeeError');
    const aSelectedBox = document.getElementById('assignEmployeeSelected');
    const aEmpId = document.getElementById('assignEmpId');
    const aEmpName = document.getElementById('assignEmpName');
    let aEmpTimer = null;
    let aActiveOption = -1;

    function resetAssignPicker() {
        if (aEmpSearch) aEmpSearch.value = '';
        if (aEmpId) aEmpId.value = '';
        if (aEmpName) aEmpName.value = '';
        if (aEmpError) { aEmpError.textContent = ''; aEmpError.classList.add('d-none'); }
        if (aSelectedBox) { aSelectedBox.innerHTML = ''; aSelectedBox.classList.add('d-none'); }
        if (aEmpResults) aEmpResults.classList.add('d-none');
        aActiveOption = -1;
    }

    function showAssignEmpError(msg) {
        if (!aEmpError) return;
        aEmpError.textContent = msg || '';
        aEmpError.classList.toggle('d-none', !msg);
    }

    function renderAssignSelected(name, id) {
        if (!aSelectedBox) return;
        aSelectedBox.innerHTML = '';
        const note = document.createElement('div');
        note.className = 'text-muted small mb-1';
        note.textContent = 'Karyawan terpilih:';
        const chip = document.createElement('button');
        chip.type = 'button';
        chip.className = 'btn btn-sm btn-outline-success';
        chip.innerHTML = '<i class="bi bi-person-check me-1"></i>' + escHtml(name) + ' (' + escHtml(id) + ') <i class="bi bi-x-lg ms-2"></i>';
        chip.addEventListener('click', function () {
            if (aEmpSearch) aEmpSearch.value = '';
            if (aEmpId) aEmpId.value = '';
            if (aEmpName) aEmpName.value = '';
            showAssignEmpError('');
            if (aEmpSearch) aEmpSearch.focus();
        });
        aSelectedBox.appendChild(note);
        aSelectedBox.appendChild(chip);
        aSelectedBox.classList.remove('d-none');
    }

    function selectAssignEmployee(opt) {
        if (aEmpId) aEmpId.value = opt.dataset.empId || '';
        if (aEmpName) aEmpName.value = opt.dataset.empName || '';
        if (aEmpSearch) { aEmpSearch.value = opt.dataset.empName || ''; aEmpSearch.blur(); }
        showAssignEmpError('');
        if (aEmpResults) aEmpResults.classList.add('d-none');
        renderAssignSelected(opt.dataset.empName || '', opt.dataset.empId || '');
    }

    aEmpSearch?.addEventListener('input', function () {
        clearTimeout(aEmpTimer);
        const q = this.value.trim();
        if (aEmpId) aEmpId.value = '';
        if (aEmpName) aEmpName.value = '';
        showAssignEmpError('');
        if (aEmpResults) { aEmpResults.innerHTML = ''; aEmpResults.classList.add('d-none'); }
        aActiveOption = -1;
        if (q.length < 2) return;
        if (aEmpResults) {
            aEmpResults.innerHTML = '<div class="list-group-item"><span class="spinner-border spinner-border-sm me-2"></span>Mencari karyawan...</div>';
            aEmpResults.classList.remove('d-none');
        }
        aEmpTimer = setTimeout(async function () {
            try {
                const res = await fetch('/hr/employees/lookup?q=' + encodeURIComponent(q) + '&limit=8', { headers: HEADERS });
                if (res.status === 403) {
                    if (aEmpResults) aEmpResults.classList.add('d-none');
                    showAssignEmpError('Anda tidak memiliki izin untuk mencari karyawan.');
                    return;
                }
                const data = await res.json();
                if (!aEmpResults) return;
                if (!data.data || data.data.length === 0) {
                    aEmpResults.innerHTML = '<div class="list-group-item text-muted">Tidak ada karyawan yang cocok dengan "' + escHtml(q) + '".</div>';
                    aEmpResults.classList.remove('d-none');
                    return;
                }
                aEmpResults.innerHTML = data.data.map(function (e, i) {
                    return '<button type="button" class="list-group-item list-group-item-action assign-option" data-index="' + i + '"'
                        + ' data-emp-id="' + escHtml(e.employeeId) + '"'
                        + ' data-emp-name="' + escHtml(e.fullName || '') + '">'
                        + '<div class="fw-semibold">' + escHtml(e.fullName || '') + '</div>'
                        + '<small class="text-muted">' + escHtml(e.employeeId || '') + (e.department ? ' - ' + escHtml(e.department) : '') + '</small>'
                        + '</button>';
                }).join('');
                aEmpResults.classList.remove('d-none');
            } catch (_) {
                if (aEmpResults) aEmpResults.classList.add('d-none');
                showAssignEmpError('Kesalahan jaringan saat mencari karyawan.');
            }
        }, 300);
    });

    aEmpResults?.addEventListener('click', function (e) {
        const opt = e.target.closest('.assign-option');
        if (opt) selectAssignEmployee(opt);
    });

    aEmpResults?.addEventListener('mousemove', function (e) {
        const opt = e.target.closest('.assign-option');
        if (!opt) return;
        Array.prototype.forEach.call(aEmpResults.querySelectorAll('.assign-option'), function (el) {
            const isActive = el === opt;
            el.classList.toggle('active', isActive);
            if (isActive) aActiveOption = Number(el.dataset.index);
        });
    });

    aEmpSearch?.addEventListener('keydown', function (e) {
        if (!aEmpResults) return;
        const opts = Array.prototype.slice.call(aEmpResults.querySelectorAll('.assign-option'));
        if (!opts.length) return;
        if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
            e.preventDefault();
            const dir = e.key === 'ArrowDown' ? 1 : -1;
            aActiveOption = (aActiveOption + dir + opts.length) % opts.length;
            opts.forEach(function (el, i) { el.classList.toggle('active', i === aActiveOption); });
            if (opts[aActiveOption]) opts[aActiveOption].scrollIntoView({ block: 'nearest' });
        } else if (e.key === 'Enter') {
            const current = opts[aActiveOption] || opts[0];
            if (current) { e.preventDefault(); selectAssignEmployee(current); }
        } else if (e.key === 'Escape') {
            if (aEmpResults) aEmpResults.classList.add('d-none');
        }
    });

    document.addEventListener('click', function (e) {
        if (aEmpResults && !aEmpResults.contains(e.target) && e.target.id !== 'assignEmployeeSearch') {
            aEmpResults.classList.add('d-none');
        }
    });

    document.getElementById('assignAssetModal')?.addEventListener('hidden.bs.modal', function () {
        resetAssignPicker();
    });

    document.getElementById('assignAssetForm')?.addEventListener('submit', async function (e) {
        e.preventDefault();
        const form = this;
        const assetId = form.querySelector('#assignAssetId').value;
        const btn = form.querySelector('button[type="submit"]');
        const body = {};
        new FormData(form).forEach(function (v, k) {
            if (k !== 'asset_id') body[k] = v;
        });
        setLoading(btn, true);
        try {
            const res = await fetch(MITO_ASSET_BASE + '/' + assetId + '/assign', { method: 'POST', headers: HEADERS, body: JSON.stringify(body) });
            const data = await res.json();
            if (res.ok && data.success) {
                showToast(data.message, 'success');
                bootstrap.Modal.getInstance(form.closest('.modal'))?.hide();
                setTimeout(function () { location.reload(); }, 600);
            } else {
                showToast(data.message || 'Gagal menugaskan.', 'error');
                if (data.errors && data.errors.employee_id) {
                    if (aEmpSearch) aEmpSearch.classList.add('is-invalid');
                    showAssignEmpError(Array.isArray(data.errors.employee_id) ? data.errors.employee_id[0] : data.errors.employee_id);
                } else {
                    if (aEmpSearch) aEmpSearch.classList.remove('is-invalid');
                }
            }
        } catch (_) {
            showToast('Kesalahan jaringan.', 'error');
        } finally {
            setLoading(btn, false);
        }
    });

    // ═══ Return ════════════════════════════════════════════════════

    document.querySelectorAll('.asset-return-btn').forEach(function (btn) {
        btn.addEventListener('click', async function () {
            const id = this.dataset.assetId;
            try {
                const res = await fetch(MITO_ASSET_BASE + '/' + id + '/json', { headers: HEADERS });
                const data = await res.json();
                if (!data.success) return showToast('Gagal memuat data.', 'error');
                const a = data.asset;
                document.getElementById('returnAssetId').value = a.id;
                document.getElementById('returnAssetDisplay').value = (a.asset_code || '—') + ' — ' + a.name;
                const active = a.activeAssignment || a.active_assignment;
                document.getElementById('returnAssignedTo').value = active ? (active.employee_name || active.employee_id) : '—';
                document.getElementById('returnNotes').value = '';
                document.getElementById('returnDate').value = todayInputValue();
                new bootstrap.Modal(document.getElementById('returnAssetModal')).show();
            } catch (_) {
                showToast('Kesalahan jaringan.', 'error');
            }
        });
    });

    document.getElementById('returnAssetForm')?.addEventListener('submit', async function (e) {
        e.preventDefault();
        const form = this;
        const assetId = form.querySelector('#returnAssetId').value;
        const btn = form.querySelector('button[type="submit"]');
        const body = {};
        new FormData(form).forEach(function (v, k) {
            if (k !== 'asset_id') body[k] = v;
        });
        setLoading(btn, true);
        try {
            const res = await fetch(MITO_ASSET_BASE + '/' + assetId + '/return', { method: 'POST', headers: HEADERS, body: JSON.stringify(body) });
            const data = await res.json();
            if (res.ok && data.success) {
                showToast(data.message, 'success');
                bootstrap.Modal.getInstance(form.closest('.modal'))?.hide();
                setTimeout(function () { location.reload(); }, 600);
            } else {
                showToast(data.message || 'Gagal mengembalikan.', 'error');
            }
        } catch (_) {
            showToast('Kesalahan jaringan.', 'error');
        } finally {
            setLoading(btn, false);
        }
    });

    // ═══ Dispose ══════════════════════════════════════════════════

    document.querySelectorAll('.asset-dispose-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const id = btn.dataset.assetId;
            const code = btn.dataset.assetCode || '—';
            const name = btn.dataset.name || '—';
            const cat = btn.dataset.category || '—';
            document.getElementById('disposeAssetId').value = id;
            document.getElementById('disposeAssetCode').textContent = code;
            document.getElementById('disposeAssetName').textContent = name;
            document.getElementById('disposeAssetCategory').textContent = cat;
            const err = document.getElementById('disposeAssetError');
            err.style.display = 'none';
            err.textContent = '';
            new bootstrap.Modal(document.getElementById('disposeAssetModal')).show();
        });
    });

    document.getElementById('btnDisposeConfirm')?.addEventListener('click', async function () {
        const btn = this;
        const id = document.getElementById('disposeAssetId').value;
        const err = document.getElementById('disposeAssetError');
        err.style.display = 'none';
        setLoading(btn, true);
        try {
            const res = await fetch(MITO_ASSET_BASE + '/' + id, { method: 'DELETE', headers: HEADERS });
            const data = await res.json().catch(function () { return { success: false, message: 'Respons tidak valid.' }; });
            if (res.ok && data.success) {
                showToast(data.message || 'Aset berhasil didisposisi.', 'success');
                bootstrap.Modal.getInstance(document.getElementById('disposeAssetModal'))?.hide();
                setTimeout(function () { location.reload(); }, 600);
            } else {
                err.textContent = data.message || ('Gagal (HTTP ' + res.status + ').');
                err.style.display = 'block';
                showToast(err.textContent, 'error');
            }
        } catch (_) {
            err.textContent = 'Kesalahan jaringan.';
            err.style.display = 'block';
            showToast(err.textContent, 'error');
        } finally {
            setLoading(btn, false);
        }
    });

    document.getElementById('addAssetModal')?.addEventListener('hidden.bs.modal', function () {
        const form = this.querySelector('#assetForm');
        if (form.querySelector('#assetFormId').value) {
            form.querySelector('#assetFormId').value = '';
            form.querySelector('#assetFormMethod').value = 'POST';
            clearAssetForm();
            applyAssetFormDefaults();
            syncCategoryPanels();
        }
    });

    // ═══ Generate code button (table row) ══════════════════════════

    document.querySelectorAll('.asset-gencode-btn').forEach(function (btn) {
        btn.addEventListener('click', async function () {
            const id = this.dataset.assetId;
            this.disabled = true;
            try {
                const res = await fetch(MITO_ASSET_BASE + '/' + id + '/generate-code', { method: 'POST', headers: HEADERS });
                const data = await res.json();
                if (res.ok && data.success) {
                    showToast(data.message, 'success');
                    setTimeout(function () { location.reload(); }, 600);
                } else {
                    showToast(data.message || 'Gagal generate kode.', 'error');
                }
            } catch (_) {
                showToast('Kesalahan jaringan.', 'error');
            } finally {
                this.disabled = false;
            }
        });
    });

    // ═══ Bulk generate codes ═══════════════════════════════════════

    document.getElementById('bulkGenerateModal')?.addEventListener('show.bs.modal', async function () {
        const summary = document.getElementById('bulkGenSummary');
        const result = document.getElementById('bulkGenResult');
        summary.style.display = 'none';
        result.style.display = 'none';
        try {
            const res = await fetch(MITO_ASSET_BASE + '/missing-code-summary', { headers: HEADERS });
            const data = await res.json();
            if (data.success) {
                const entries = Object.entries(data.summary || {});
                if (entries.length === 0) {
                    summary.innerHTML = '<div class="alert alert-success mb-0">Semua aset sudah memiliki kode.</div>';
                } else {
                    let total = 0, cats = '';
                    entries.forEach(function (entry) {
                        total += entry[1];
                        cats += '<li>' + entry[0] + ': ' + entry[1] + '</li>';
                    });
                    document.getElementById('bulkGenTotal').textContent = total;
                    document.getElementById('bulkGenCategories').innerHTML = cats;
                }
                summary.style.display = 'block';
            }
        } catch (_) { /* keep modal empty on failure */ }
    });

    document.getElementById('btnBulkGenExecute')?.addEventListener('click', async function () {
        const btn = this;
        const result = document.getElementById('bulkGenResult');
        setLoading(btn, true);
        try {
            const res = await fetch(MITO_ASSET_BASE + '/generate-bulk-codes', { method: 'POST', headers: HEADERS });
            const data = await res.json();
            if (res.ok && data.success) {
                let html = '<div class="alert alert-success">' + (data.message || 'Selesai.') + '</div>';
                if (data.summary && data.summary.categories) {
                    html += '<ul class="mb-0">';
                    Object.entries(data.summary.categories).forEach(function (entry) {
                        if (entry[1] > 0) html += '<li>' + entry[0] + ': ' + entry[1] + '</li>';
                    });
                    html += '</ul>';
                }
                result.innerHTML = html;
                showToast(data.message, 'success');
            } else {
                result.innerHTML = '<div class="alert alert-danger">' + (data.message || 'Gagal.') + '</div>';
                showToast(data.message || 'Gagal generate kode.', 'error');
            }
        } catch (_) {
            result.innerHTML = '<div class="alert alert-danger">Kesalahan jaringan.</div>';
        } finally {
            setLoading(btn, false);
            result.style.display = 'block';
            setTimeout(function () { location.reload(); }, 2000);
        }
    });

})();

