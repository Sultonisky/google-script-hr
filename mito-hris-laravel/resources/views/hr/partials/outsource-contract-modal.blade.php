{{-- Modal Proses Kontrak PKWT TAD (Outsource / Damaindo) --}}
<div class="modal fade" id="outsourceContractModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content" style="border-radius:16px">
            <div class="modal-header" style="background:#eb1c24;border-radius:16px 16px 0 0">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-file-earmark-text-fill text-white fs-5"></i>
                    <h6 class="modal-title mb-0 text-white fw-bold">Proses Kontrak PKWT TAD (Outsource)</h6>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <form id="formOutsourceContract" action="{{ route('hr.outsource.kontrak-pkwt-tad') }}" method="POST">
                @csrf
                <div class="modal-body p-4">
                    <input type="hidden" id="oscEmployeeId" name="employee_id" />

                    {{-- STEP 1: Live search outsource only --}}
                    <div class="mb-3">
                        <label class="form-label fw-semibold" style="font-size:13px">
                            <i class="bi bi-search me-1"></i>Cari Karyawan Outsource <span class="text-danger">*</span>
                        </label>
                        <div class="position-relative">
                            <input type="text" class="form-control" id="oscEmpSearch"
                                placeholder="Ketik nama atau Employee ID..." autocomplete="off"
                                style="font-size:13px;padding-right:36px" />
                            <i class="bi bi-x-circle-fill position-absolute" id="oscEmpSearchClear"
                                style="right:10px;top:50%;transform:translateY(-50%);cursor:pointer;color:#aaa;display:none"></i>
                        </div>
                        <div id="oscEmpDropdown" class="border rounded-3 mt-1 shadow-sm"
                            style="display:none;max-height:220px;overflow-y:auto;z-index:9999;position:relative">
                        </div>
                    </div>

                    {{-- STEP 2: Preview + form --}}
                    <div id="oscEmpPreview" style="display:none">
                        <div class="p-3 rounded-3 mb-4" style="border:1px solid #eb1c24">
                            <div class="d-flex align-items-center gap-3">
                                <div id="oscEmpAvatar"
                                    style="width:44px;height:44px;font-size:16px;flex-shrink:0;background:#eb1c24;color:#fff;border-radius:12px;display:flex;align-items:center;justify-content:center;font-weight:800">
                                    ?</div>
                                <div class="flex-grow-1">
                                    <div class="fw-bold text-primary" id="oscEmpName" style="font-size:15px">-</div>
                                    <div class="text-muted" style="font-size:12px">
                                        <span id="oscEmpPosition">-</span> <span class="mx-1">&bull;</span>
                                        <span id="oscEmpVendor">-</span>
                                    </div>
                                </div>
                                <div class="text-end flex-shrink-0" style="font-size:11.5px">
                                    <div class="text-muted">Employee ID</div>
                                    <div class="fw-semibold text-primary" id="oscEmpIdDisp">-</div>
                                </div>
                            </div>
                        </div>

                        <p class="fw-bold mb-3"
                            style="font-size:12px;text-transform:uppercase;letter-spacing:.05em;color:#eb1c24">
                            <i class="bi bi-person-vcard me-1"></i>Data Karyawan (otomatis)
                        </p>
                        <div class="row g-3 mb-4" style="font-size:13px">
                            <div class="col-md-6">
                                <div class="text-muted" style="font-size:11px">Nama</div>
                                <div class="fw-semibold" id="oscDispNama">-</div>
                            </div>
                            <div class="col-md-6">
                                <div class="text-muted" style="font-size:11px">Tempat &amp; Tgl Lahir</div>
                                <div class="fw-semibold" id="oscDispTtl">-</div>
                            </div>
                            <div class="col-md-6">
                                <div class="text-muted" style="font-size:11px">No HP Aktif</div>
                                <div class="fw-semibold" id="oscDispPhone">-</div>
                            </div>
                            <div class="col-md-6">
                                <div class="text-muted" style="font-size:11px">Email</div>
                                <div class="fw-semibold" id="oscDispEmail">-</div>
                            </div>
                        </div>

                        <p class="fw-bold mb-3"
                            style="font-size:12px;text-transform:uppercase;letter-spacing:.05em;color:#eb1c24">
                            <i class="bi bi-pencil-square me-1"></i>Data Kontrak (isi manual)
                        </p>
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label fw-semibold" style="font-size:13px">Perusahaan <span
                                        class="text-danger">*</span></label>
                                <input type="text" class="form-control form-control-sm" name="perusahaan"
                                    id="oscPerusahaan" required placeholder="Contoh: Toko Tiara Palu" />
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-semibold" style="font-size:13px">Beralamat di <span
                                        class="text-danger">*</span></label>
                                <input type="text" class="form-control form-control-sm" name="beralamat_di"
                                    id="oscBeralamatDi" required placeholder="Contoh: Jl. Hasanuddin" />
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold" style="font-size:13px">Mulai tanggal <span
                                        class="text-danger">*</span></label>
                                <input type="date" class="form-control form-control-sm" name="mulai_tanggal"
                                    id="oscMulaiTanggal" required />
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold" style="font-size:13px">Pendidikan <span
                                        class="text-danger">*</span></label>
                                <input type="text" class="form-control form-control-sm" name="pendidikan"
                                    id="oscPendidikan" required placeholder="Contoh: SLTA / S1" />
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary btn-sm"
                        data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-sm text-white fw-semibold" style="background:#eb1c24"
                        id="btnGenerateOscContract" disabled>
                        <i class="bi bi-file-earmark-pdf me-1"></i>Generate PDF Kontrak
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@php
    $__oscList = collect($allOutsourcesForSearch ?? [])
        ->map(function ($e) {
            if (!is_object($e)) {
                return $e;
            }
            $bulanId = [
                'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
                'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember',
            ];
            $ttl = $e->birthPlace ?? '';
            if (!empty($e->birthDate)) {
                try {
                    $d = \Carbon\Carbon::parse($e->birthDate);
                    $ttl = trim(($e->birthPlace ?? '') . ', ' . $d->day . ' ' . $bulanId[$d->month - 1] . ' ' . $d->year);
                } catch (\Throwable) {
                    $ttl = trim(($e->birthPlace ?? '') . ($e->birthDate ? ', ' . $e->birthDate : ''));
                }
            }
            return [
                'employeeId' => $e->employeeId ?? null,
                'fullName' => $e->fullName ?? null,
                'jobPosition' => $e->jobPosition ?? null,
                'department' => $e->department ?? null,
                'outsourceVendor' => $e->outsourceVendor ?? null,
                'birthPlace' => $e->birthPlace ?? null,
                'birthDate' => $e->birthDate ?? null,
                'ttl' => $ttl ?: '-',
                'mobilePhone' => ltrim((string) ($e->mobilePhone ?? ''), "'"),
                'email' => $e->personalEmail ?: ($e->workingEmail ?? ''),
                'joinDate' => $e->joinDate ?? null,
            ];
        })
        ->values()
        ->all();
@endphp
<script>
    window.__outsourceEmployeesForContract = @json($__oscList);

    function handleOscEmpSearch(query) {
        var q = (query || '').toLowerCase().trim();
        var dropdown = document.getElementById('oscEmpDropdown');
        var clearBtn = document.getElementById('oscEmpSearchClear');
        if (clearBtn) clearBtn.style.display = q ? 'block' : 'none';

        if (!q) {
            if (dropdown) dropdown.style.display = 'none';
            return;
        }

        var matched = (window.__outsourceEmployeesForContract || []).filter(function(e) {
            var name = (e.fullName || '').toLowerCase();
            var id = (e.employeeId || '').toLowerCase();
            var pos = (e.jobPosition || '').toLowerCase();
            var vendor = (e.outsourceVendor || '').toLowerCase();
            return name.indexOf(q) !== -1 || id.indexOf(q) !== -1 || pos.indexOf(q) !== -1 || vendor.indexOf(q) !== -1;
        }).slice(0, 8);

        if (!matched.length) {
            dropdown.innerHTML =
                '<div class="p-3 text-muted text-center" style="font-size:13px">Tidak ada karyawan outsource yang cocok</div>';
            dropdown.style.display = 'block';
            return;
        }

        dropdown.innerHTML = matched.map(function(emp) {
            return '<div class="p-2 border-bottom d-flex align-items-center gap-2 osc-search-item" style="cursor:pointer;" data-emp-id="' +
                (emp.employeeId || '') + '">' +
                '<div class="avatar-sm" style="width:32px;height:32px;background:#eb1c24;color:#fff;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700">' +
                (emp.fullName || 'O').substring(0, 2).toUpperCase() +
                '</div>' +
                '<div class="flex-grow-1" style="font-size:12.5px;">' +
                '<div class="fw-semibold text-primary">' + (emp.fullName || '-') + '</div>' +
                '<div class="text-muted" style="font-size:11px">' + (emp.employeeId || '') + ' &bull; ' +
                (emp.jobPosition || '-') + (emp.outsourceVendor ? ' &bull; ' + emp.outsourceVendor : '') +
                '</div></div></div>';
        }).join('');

        dropdown.querySelectorAll('.osc-search-item').forEach(function(item) {
            item.addEventListener('click', function() {
                var empId = item.getAttribute('data-emp-id');
                var found = (window.__outsourceEmployeesForContract || []).find(function(e) {
                    return e.employeeId === empId;
                });
                if (found) selectOscEmployee(found);
            });
        });
        dropdown.style.display = 'block';
    }

    function selectOscEmployee(emp) {
        document.getElementById('oscEmpDropdown').style.display = 'none';
        document.getElementById('oscEmpSearch').value = (emp.fullName || '') + ' (' + (emp.employeeId || '') + ')';
        document.getElementById('oscEmployeeId').value = emp.employeeId || '';

        document.getElementById('oscEmpName').textContent = emp.fullName || '-';
        document.getElementById('oscEmpPosition').textContent = emp.jobPosition || '-';
        document.getElementById('oscEmpVendor').textContent = emp.outsourceVendor || '-';
        document.getElementById('oscEmpIdDisp').textContent = emp.employeeId || '-';
        document.getElementById('oscEmpAvatar').textContent = (emp.fullName || 'O').substring(0, 2).toUpperCase();

        document.getElementById('oscDispNama').textContent = emp.fullName || '-';
        document.getElementById('oscDispTtl').textContent = emp.ttl || '-';
        document.getElementById('oscDispPhone').textContent = emp.mobilePhone || '-';
        document.getElementById('oscDispEmail').textContent = emp.email || '-';

        if (emp.joinDate) {
            try {
                var d = new Date(emp.joinDate);
                if (!isNaN(d.getTime())) {
                    document.getElementById('oscMulaiTanggal').value = d.toISOString().slice(0, 10);
                }
            } catch (e) {}
        }

        document.getElementById('oscEmpPreview').style.display = 'block';
        document.getElementById('btnGenerateOscContract').disabled = false;
    }

    function clearOscEmpSearch() {
        document.getElementById('oscEmpSearch').value = '';
        document.getElementById('oscEmpDropdown').style.display = 'none';
        document.getElementById('oscEmpSearchClear').style.display = 'none';
        document.getElementById('oscEmpPreview').style.display = 'none';
        document.getElementById('oscEmployeeId').value = '';
        document.getElementById('btnGenerateOscContract').disabled = true;
    }

    document.addEventListener('DOMContentLoaded', function() {
        var searchInput = document.getElementById('oscEmpSearch');
        var clearBtn = document.getElementById('oscEmpSearchClear');
        var form = document.getElementById('formOutsourceContract');
        var btn = document.getElementById('btnGenerateOscContract');
        var modalEl = document.getElementById('outsourceContractModal');

        if (searchInput) {
            searchInput.addEventListener('input', function() {
                handleOscEmpSearch(this.value);
            });
        }
        if (clearBtn) {
            clearBtn.addEventListener('click', clearOscEmpSearch);
        }

        if (form && btn) {
            var submitting = false;
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                if (submitting) return;

                var empId = (document.getElementById('oscEmployeeId') || {}).value || '';
                if (!empId) {
                    if (typeof showToast === 'function') showToast('Pilih karyawan outsource terlebih dahulu.', 'error');
                    return;
                }

                submitting = true;
                var origHtml = btn.innerHTML;
                btn.disabled = true;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Generate PDF...';

                var formData = new FormData(form);

                fetch(form.action, {
                        method: 'POST',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') || {}).content || ''
                        },
                        body: formData
                    })
                    .then(function(r) {
                        return r.json().then(function(data) {
                            if (!r.ok || !data || !data.success) {
                                throw new Error((data && data.message) ? data.message : ('HTTP ' + r.status));
                            }
                            return data;
                        });
                    })
                    .then(function(res) {
                        var pdfUrls = res.pdf_urls || {};
                        var delay = 200;
                        Object.keys(pdfUrls).forEach(function(key) {
                            var url = pdfUrls[key];
                            if (!url) return;
                            setTimeout(function() {
                                var iframe = document.createElement('iframe');
                                iframe.style.cssText = 'display:none;width:0;height:0;border:0';
                                iframe.src = url;
                                document.body.appendChild(iframe);
                                setTimeout(function() {
                                    try { document.body.removeChild(iframe); } catch (_) {}
                                }, 20000);
                            }, delay);
                            delay += 900;
                        });

                        if (typeof showToast === 'function') {
                            showToast('2 PDF berhasil digenerate: Kontrak + Surat Pernyataan (' + (res.contract_number || '') + ').', 'success', 5000);
                        }

                        if (modalEl) {
                            var modal = bootstrap.Modal.getInstance(modalEl);
                            if (modal) modal.hide();
                        }
                    })
                    .catch(function(err) {
                        if (typeof showToast === 'function') {
                            showToast('Gagal generate kontrak: ' + (err && err.message ? err.message.substring(0, 160) : 'Error'), 'error');
                        }
                    })
                    .finally(function() {
                        submitting = false;
                        btn.disabled = false;
                        btn.innerHTML = origHtml;
                    });
            });
        }

        if (modalEl) {
            modalEl.addEventListener('hidden.bs.modal', function() {
                clearOscEmpSearch();
                if (form) form.reset();
            });
        }
    });
</script>
