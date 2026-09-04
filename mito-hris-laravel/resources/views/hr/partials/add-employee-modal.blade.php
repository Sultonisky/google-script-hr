{{--
  partials/add-employee-modal.blade.php
  Modal Tambah Karyawan Baru (manual entry — bukan dari rekrutmen)
  Form input 1:1 dengan kolom sheet Employee (config/hris.php schemas.Employee).
  Diinclude dari layouts/hr.blade.php — satu kali, tidak per-page.

  Field groups (sama dengan employee-form-modal.blade.php / GAS):
    Seksi 1 : Identitas & Data Pribadi
    Seksi 2 : Bank & BPJS
    Seksi 3 : Struktur Organisasi & Pekerjaan  ← termasuk Status Employee (required)
    Seksi 4 : Kontrak
    Seksi 5 : Catatan HR
--}}
<div class="modal fade" id="addEmployeeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content" style="border-radius:16px">

            {{-- HEADER --}}
            <div class="modal-header" style="background:#005BAC;border-radius:16px 16px 0 0">
                <div class="d-flex align-items-center gap-2 text-white">
                    <i class="bi bi-person-plus-fill fs-5"></i>
                    <h6 class="modal-title mb-0 fw-bold">Tambah Karyawan Baru</h6>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                    aria-label="Tutup"></button>
            </div>

            {{-- BODY --}}
            <div class="modal-body p-4">

                {{-- ============================================================
                     SEKSI 1: IDENTITAS & DATA PRIBADI
                     ============================================================ --}}
                <p class="fw-bold mb-3"
                    style="font-size:12px;text-transform:uppercase;letter-spacing:.05em;color:#005BAC">
                    <i class="bi bi-person-badge-fill me-1"></i>Identitas &amp; Data Pribadi
                </p>
                <div class="row g-2 mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold" style="font-size:12.5px">
                            Nama Lengkap <span class="text-danger">*</span>
                        </label>
                        <input type="text" class="form-control form-control-sm" id="aeFullName"
                            placeholder="Nama lengkap sesuai KTP" required autocomplete="off" />
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold" style="font-size:12.5px">NIK (16 Digit)</label>
                        <input type="text" class="form-control form-control-sm" id="aeNik"
                            maxlength="16" placeholder="16 digit NIK" inputmode="numeric" />
                        <div class="d-flex align-items-center gap-1 mt-1"
                            style="font-size:11px;color:#005BAC;background:#f0f7ff;border:1px solid #c7dff7;border-radius:6px;padding:4px 8px">
                            <i class="bi bi-magic flex-shrink-0"></i>
                            <span>NIK akan otomatis mengisi Tanggal Lahir &amp; Jenis Kelamin</span>
                        </div>
                        <div id="aeNikFeedback" class="mt-1"></div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold" style="font-size:12.5px">NPWP (16 Digit)</label>
                        <input type="text" class="form-control form-control-sm" id="aeNpwp"
                            maxlength="16" placeholder="16 digit NPWP" inputmode="numeric" />
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold" style="font-size:12.5px">Tempat Lahir</label>
                        <input type="text" class="form-control form-control-sm" id="aeBirthPlace"
                            placeholder="Kota tempat lahir" />
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold" style="font-size:12.5px">Tanggal Lahir</label>
                        <input type="date" class="form-control form-control-sm" id="aeBirthDate" />
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold" style="font-size:12.5px">Golongan Darah</label>
                        <select class="form-select form-select-sm" id="aeBloodType">
                            <option value="">— Pilih —</option>
                            <option value="A">A</option>
                            <option value="B">B</option>
                            <option value="AB">AB</option>
                            <option value="O">O</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold" style="font-size:12.5px">Jenis Kelamin</label>
                        <select class="form-select form-select-sm" id="aeGender">
                            <option value="">— Pilih —</option>
                            <option value="Laki-laki">Laki-laki</option>
                            <option value="Perempuan">Perempuan</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold" style="font-size:12.5px">Agama</label>
                        <select class="form-select form-select-sm" id="aeReligion">
                            <option value="">— Pilih —</option>
                            <option value="Islam">Islam</option>
                            <option value="Kristen">Kristen</option>
                            <option value="Katholik">Katholik</option>
                            <option value="Hindu">Hindu</option>
                            <option value="Buddha">Buddha</option>
                            <option value="Konghucu">Konghucu</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold" style="font-size:12.5px">Status Pernikahan</label>
                        <select class="form-select form-select-sm" id="aeMarital">
                            <option value="">— Pilih —</option>
                            <option value="Belum Menikah">Belum Menikah</option>
                            <option value="Menikah">Menikah</option>
                            <option value="Cerai">Cerai</option>
                            <option value="Duda/Janda">Duda/Janda</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold" style="font-size:12.5px">Status PTKP</label>
                        <select class="form-select form-select-sm" id="aePtkp">
                            <option value="">— Pilih —</option>
                            <option value="TK/0">TK/0</option>
                            <option value="TK/1">TK/1</option>
                            <option value="TK/2">TK/2</option>
                            <option value="TK/3">TK/3</option>
                            <option value="K/0">K/0</option>
                            <option value="K/1">K/1</option>
                            <option value="K/2">K/2</option>
                            <option value="K/3">K/3</option>
                            <option value="K/I/0">K/I/0</option>
                            <option value="K/I/1">K/I/1</option>
                            <option value="K/I/2">K/I/2</option>
                            <option value="K/I/3">K/I/3</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold" style="font-size:12.5px">No. HP</label>
                        <input type="text" class="form-control form-control-sm" id="aeMobilePhone"
                            placeholder="628xxxxxxxxxx" inputmode="numeric" />
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold" style="font-size:12.5px">Email Pribadi</label>
                        <input type="email" class="form-control form-control-sm" id="aePersonalEmail"
                            placeholder="email@domain.com" />
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold" style="font-size:12.5px">Alamat KTP</label>
                        <textarea class="form-control form-control-sm" id="aeCitizenAddress" rows="2"
                            placeholder="Alamat lengkap sesuai KTP"></textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold" style="font-size:12.5px">Alamat Domisili</label>
                        <textarea class="form-control form-control-sm" id="aeResidentialAddress" rows="2"
                            placeholder="Alamat domisili (kosongkan jika sama dengan KTP)"></textarea>
                    </div>
                </div>

                <hr class="my-3" />

                {{-- ============================================================
                     SEKSI 2: BANK & BPJS
                     ============================================================ --}}
                <p class="fw-bold mb-3"
                    style="font-size:12px;text-transform:uppercase;letter-spacing:.05em;color:#005BAC">
                    <i class="bi bi-wallet2 me-1"></i>Bank &amp; BPJS
                </p>
                <div class="row g-2 mb-3">
                    <div class="col-md-4">
                        <label class="form-label fw-semibold" style="font-size:12.5px">Email Kantor</label>
                        <input type="email" class="form-control form-control-sm" id="aeWorkingEmail"
                            placeholder="email@perusahaan.com" />
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold" style="font-size:12.5px">Nama Bank</label>
                        <div class="input-group input-group-sm">
                            <input type="text" class="form-control form-control-sm" id="aeBankName"
                                value="BCA" readonly tabindex="-1"
                                style="background:#f0f4f8;color:#374151;font-weight:600;cursor:not-allowed;border-color:#d1d5db" />
                            <span class="input-group-text" style="background:#e9ecef;border-color:#d1d5db;font-size:11px;color:#6b7280">
                                <i class="bi bi-lock-fill me-1"></i>Fixed
                            </span>
                        </div>
                        <div class="form-text" style="font-size:11px;color:#6b7280">
                            <i class="bi bi-info-circle me-1"></i>Bank default perusahaan, tidak dapat diubah.
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold" style="font-size:12.5px">Nomor Rekening</label>
                        <input type="text" class="form-control form-control-sm" id="aeBankAccount"
                            placeholder="Nomor rekening bank" inputmode="numeric" />
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold" style="font-size:12.5px">Atas Nama Rekening</label>
                        <input type="text" class="form-control form-control-sm" id="aeBankHolder"
                            placeholder="Nama pemilik rekening" />
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold" style="font-size:12.5px">BPJS Ketenagakerjaan</label>
                        <input type="text" class="form-control form-control-sm" id="aeBpjsTk"
                            placeholder="Nomor BPJS Ketenagakerjaan" inputmode="numeric" />
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold" style="font-size:12.5px">BPJS Kesehatan</label>
                        <input type="text" class="form-control form-control-sm" id="aeBpjsKes"
                            placeholder="Nomor BPJS Kesehatan" inputmode="numeric" />
                    </div>
                </div>

                <hr class="my-3" />

                {{-- ============================================================
                     SEKSI 3: STRUKTUR ORGANISASI & PEKERJAAN
                     ============================================================ --}}
                <p class="fw-bold mb-3"
                    style="font-size:12px;text-transform:uppercase;letter-spacing:.05em;color:#005BAC">
                    <i class="bi bi-briefcase-fill me-1"></i>Struktur Organisasi &amp; Pekerjaan
                </p>
                <div class="row g-2 mb-3">
                    <div class="col-md-4">
                        <label class="form-label fw-semibold" style="font-size:12.5px">
                            Status Karyawan <span class="text-danger">*</span>
                        </label>
                        <select class="form-select form-select-sm" id="aeStatusEmployee" required>
                            <option value="">— Pilih —</option>
                            <option value="Contract" selected>Contract / PKWT</option>
                            <option value="Permanent">Permanent / PKWTT</option>
                            <option value="Outsource">Outsource</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold" style="font-size:12.5px">Tanggal Masuk</label>
                        <input type="date" class="form-control form-control-sm" id="aeJoinDate" />
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold" style="font-size:12.5px">
                            Employee ID <span class="text-muted fw-normal" style="font-size:11px">(auto-generate)</span>
                        </label>
                        <div class="input-group input-group-sm">
                            <input type="text" class="form-control form-control-sm font-monospace" id="aeEmployeeIdPreview"
                                readonly tabindex="-1"
                                placeholder="Pilih tanggal masuk..."
                                style="background:#f0f4f8;color:#374151;font-weight:600;cursor:default;border-color:#d1d5db;letter-spacing:0.04em" />
                            <span class="input-group-text" style="background:#e9ecef;border-color:#d1d5db;font-size:11px;color:#6b7280">
                                <i class="bi bi-hash me-1"></i>Preview
                            </span>
                        </div>
                        <div class="form-text" style="font-size:11px;color:#6b7280">
                            <i class="bi bi-info-circle me-1"></i>Format: <code>YYYYMMDD</code> + urutan harian. ID final di-generate server saat simpan.
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold" style="font-size:12.5px">Entitas / Branch</label>
                        <select class="form-select form-select-sm" id="aeBranchName">
                            <option value="">— Pilih Entitas —</option>
                            @foreach(config('hris.mpr.companies', []) as $code => $company)
                                <option value="{{ $company['name'] }}">{{ $code }} — {{ $company['name'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold" style="font-size:12.5px">Departemen</label>
                        <input type="text" class="form-control form-control-sm" id="aeDepartment"
                            placeholder="Nama departemen" list="aeDeptList" />
                        <datalist id="aeDeptList">
                            @foreach(array_keys(config('hris.mpr_department_divisions', [])) as $dept)
                                <option value="{{ $dept }}">
                            @endforeach
                        </datalist>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold" style="font-size:12.5px">Divisi</label>
                        <input type="text" class="form-control form-control-sm" id="aeDivision"
                            placeholder="Nama divisi" />
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold" style="font-size:12.5px">Jabatan (dengan Lokasi)</label>
                        <input type="text" class="form-control form-control-sm" id="aeJobPositionLocation"
                            placeholder="Contoh: HR Staff - Jakarta" />
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold" style="font-size:12.5px">Jabatan (tanpa Lokasi)</label>
                        <input type="text" class="form-control form-control-sm" id="aeJobPosition"
                            placeholder="Contoh: HR Staff" />
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold" style="font-size:12.5px">Job Level</label>
                        <select class="form-select form-select-sm" id="aeJobLevel">
                            <option value="">— Pilih —</option>
                            @foreach(config('hris.mpr_form_options.job_levels', []) as $level)
                                <option value="{{ $level }}">{{ $level }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold" style="font-size:12.5px">Grade</label>
                        <input type="text" class="form-control form-control-sm" id="aeGrade"
                            placeholder="Contoh: Grade 3" />
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold" style="font-size:12.5px">Area Kerja</label>
                        <input type="text" class="form-control form-control-sm" id="aeAreaKerja"
                            placeholder="Area Kerja" />
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold" style="font-size:12.5px">Lokasi Kerja</label>
                        <select class="form-select form-select-sm" id="aeLokasiKerja">
                            <option value="">— Pilih —</option>
                            @foreach(config('hris.mpr_form_options.work_locations', []) as $loc)
                                <option value="{{ $loc }}">{{ $loc }}</option>
                            @endforeach
                            <option value="Lainnya">Lainnya</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold" style="font-size:12.5px">Cost Center</label>
                        <input type="text" class="form-control form-control-sm" id="aeCostCenter"
                            placeholder="Kode cost center" />
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold" style="font-size:12.5px">Atasan Langsung</label>
                        <input type="text" class="form-control form-control-sm" id="aeDirectSuperior"
                            placeholder="Nama atasan langsung" />
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold" style="font-size:12.5px">Atasan Tidak Langsung</label>
                        <input type="text" class="form-control form-control-sm" id="aeIndirectSuperior"
                            placeholder="Nama atasan tidak langsung" />
                    </div>
                </div>

                {{-- Outsource vendor — hanya tampil jika Status = Outsource --}}
                <div id="aeOutsourceVendorWrap" style="display:none">
                    <div class="alert d-flex align-items-center gap-2 py-2 px-3 mb-3"
                        style="font-size:12px;border-radius:8px;background:#fff7ed;border:1px solid #fed7aa;color:#92400e">
                        <i class="bi bi-building-fill-gear"></i>
                        <span>Status <strong>Outsource</strong> dipilih — wajib mengisi nama vendor penyedia tenaga kerja.</span>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="font-size:12.5px">
                                Vendor Outsource <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control form-control-sm" id="aeOutsourceVendor"
                                placeholder="Nama perusahaan vendor outsource" />
                            <div class="form-text" style="font-size:11px">Wajib diisi untuk status Outsource</div>
                        </div>
                    </div>
                </div>

                {{-- ============================================================
                     SEKSI 4: KONTRAK — hanya tampil jika Status = Contract
                     ============================================================ --}}
                <div id="aeContractSection" style="display:none">
                    <hr class="my-3" />
                    <p class="fw-bold mb-3"
                        style="font-size:12px;text-transform:uppercase;letter-spacing:.05em;color:#005BAC">
                        <i class="bi bi-file-earmark-text-fill me-1"></i>Kontrak
                    </p>
                    <div class="row g-2 mb-3">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold" style="font-size:12.5px">
                                Tanggal Akhir Kontrak <span class="text-danger">*</span>
                            </label>
                            <input type="date" class="form-control form-control-sm" id="aeEndDateContract" />
                            <div class="form-text" style="font-size:11px">Wajib diisi untuk status Contract / PKWT</div>
                        </div>
                    </div>
                </div>

                <hr class="my-3" />

                {{-- ============================================================
                     SEKSI 5: CATATAN HR
                     ============================================================ --}}
                <p class="fw-bold mb-3"
                    style="font-size:12px;text-transform:uppercase;letter-spacing:.05em;color:#005BAC">
                    <i class="bi bi-chat-square-text-fill me-1"></i>Catatan HR
                </p>
                <div class="row g-2">
                    <div class="col-12">
                        <textarea class="form-control form-control-sm" id="aeHrNotes" rows="3"
                            placeholder="Tulis catatan HR (opsional)..."></textarea>
                    </div>
                </div>

            </div>{{-- /modal-body --}}

            {{-- FOOTER --}}
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary btn-sm"
                    data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-sm text-white fw-semibold" id="btnAddEmployeeSave"
                    style="background:#005BAC;border:none" disabled>
                    <span id="aeSpinner" class="spinner-border spinner-border-sm me-1 d-none"
                        role="status" aria-hidden="true"></span>
                    <i class="bi bi-person-plus-fill me-1" id="aeIcon"></i>Simpan Karyawan
                </button>
            </div>

        </div>
    </div>
</div>

<script>
(function () {
    'use strict';

    // ── Helpers ──────────────────────────────────────────────────────────────

    /**
     * Tampilkan toast — reuse fungsi global showToast jika tersedia,
     * fallback ke alert sederhana.
     */
    function toast(msg, type) {
        if (typeof showToast === 'function') {
            showToast(msg, type || 'success');
        } else {
            alert(msg);
        }
    }

    function val(id) {
        var el = document.getElementById(id);
        return el ? el.value.trim() : '';
    }

    // ── State ─────────────────────────────────────────────────────────────────

    var modalEl   = document.getElementById('addEmployeeModal');
    var saveBtn   = document.getElementById('btnAddEmployeeSave');
    var spinner   = document.getElementById('aeSpinner');
    var icon      = document.getElementById('aeIcon');
    var fullNameEl = document.getElementById('aeFullName');
    var statusEl   = document.getElementById('aeStatusEmployee');
    var outsourceWrap = document.getElementById('aeOutsourceVendorWrap');

    if (!modalEl || !saveBtn) return;

    // ── Enable/Disable simpan button ─────────────────────────────────────────

    function checkForm() {
        var ok = val('aeFullName') !== '' && val('aeStatusEmployee') !== '';
        saveBtn.disabled = !ok;
    }

    ['aeFullName', 'aeStatusEmployee'].forEach(function (id) {
        var el = document.getElementById(id);
        if (el) el.addEventListener('input', checkForm);
    });

    // ── Employee ID preview (format: YYYYMMDDxx — 1:1 dengan EmployeeIdGenerator.php) ──

    var joinDateEl     = document.getElementById('aeJoinDate');
    var idPreviewEl    = document.getElementById('aeEmployeeIdPreview');

    function updateIdPreview() {
        if (!idPreviewEl) return;
        var dateVal = joinDateEl ? joinDateEl.value : '';
        if (!dateVal) {
            idPreviewEl.value = '';
            idPreviewEl.placeholder = 'Pilih tanggal masuk...';
            return;
        }
        // Format: YYYYMMDD (hapus tanda hubung dari value "YYYY-MM-DD")
        var datePart = dateVal.replace(/-/g, '');
        // Sequence preview selalu 01 — server yang tentukan urutan final
        idPreviewEl.value = datePart + '01';
        idPreviewEl.placeholder = '';
    }

    if (joinDateEl) {
        joinDateEl.addEventListener('change', updateIdPreview);
        joinDateEl.addEventListener('input', updateIdPreview);
    }

    // ── NIK Parser + Autofill (1:1 dari GAS / public career apply.blade.php) ──────
    //
    // Format NIK 16 digit:
    //   [0-1]  Kode Provinsi
    //   [2-3]  Kode Kabupaten/Kota
    //   [4-5]  Kode Kecamatan
    //   [6-7]  Tanggal lahir (perempuan +40)
    //   [8-9]  Bulan lahir
    //   [10-11] Tahun lahir (2 digit)
    //   [12-15] Nomor urut
    //
    // Autofill target: #aeBirthDate (type="date" → YYYY-MM-DD), #aeGender (select)

    var nikEl       = document.getElementById('aeNik');
    var nikFeedback = document.getElementById('aeNikFeedback');

    function parseNIK_ae(nik) {
        if (!nik || nik.length !== 16 || !/^[0-9]{16}$/.test(nik)) return null;
        var day    = parseInt(nik.substring(6, 8), 10);
        var gender = (day > 40) ? 'Perempuan' : 'Laki-laki';
        if (day > 40) day -= 40;
        var month   = parseInt(nik.substring(8, 10), 10);
        var year    = parseInt(nik.substring(10, 12), 10);
        var fullYear = (year <= 24) ? 2000 + year : 1900 + year;
        if (month < 1 || month > 12 || day < 1 || day > 31) return null;
        return {
            // YYYY-MM-DD untuk <input type="date">
            birthDateIso: fullYear + '-' +
                ('0' + month).slice(-2) + '-' +
                ('0' + day).slice(-2),
            // DD/MM/YYYY untuk display di feedback
            birthDateFormatted: ('0' + day).slice(-2) + '/' +
                ('0' + month).slice(-2) + '/' + fullYear,
            gender: gender,
        };
    }

    function processNIK_ae(nik) {
        if (!nikFeedback) return;
        var r = parseNIK_ae(nik);

        if (!r) {
            nikFeedback.innerHTML = nik.length > 0
                ? '<div class="alert alert-warning p-2 mb-0" style="font-size:11.5px"><i class="bi bi-exclamation-triangle-fill me-1"></i><strong>NIK tidak valid.</strong> Pastikan 16 digit angka yang benar.</div>'
                : '';
            return;
        }

        // Autofill Tanggal Lahir (hanya jika belum diisi manual)
        var bdEl = document.getElementById('aeBirthDate');
        if (bdEl && !bdEl.dataset.manualChanged) {
            bdEl.value = r.birthDateIso;
        }

        // Autofill Jenis Kelamin
        var genEl = document.getElementById('aeGender');
        if (genEl) genEl.value = r.gender;

        // Feedback sukses
        nikFeedback.innerHTML =
            '<div class="alert alert-success p-2 mb-0" style="font-size:11.5px">' +
            '<i class="bi bi-check-circle-fill me-1"></i><strong>Data dikenali</strong>' +
            '<ul class="mb-0 mt-1 ps-3">' +
            '<li>Tanggal Lahir: ' + r.birthDateFormatted + '</li>' +
            '<li>Jenis Kelamin: ' + r.gender + '</li>' +
            '</ul></div>';
    }

    // Tandai jika user mengisi birth date manual — supaya NIK tidak menimpa
    var birthDateEl = document.getElementById('aeBirthDate');
    if (birthDateEl) {
        birthDateEl.addEventListener('change', function () {
            this.dataset.manualChanged = '1';
        });
    }

    if (nikEl) {
        nikEl.addEventListener('input', function () {
            // Sanitize: hanya angka, max 16
            var nik = this.value.replace(/[^0-9]/g, '').substring(0, 16);
            this.value = nik;

            if (nik.length === 16) {
                processNIK_ae(nik);
            } else if (nikFeedback) {
                nikFeedback.innerHTML = nik.length > 0
                    ? '<span class="text-muted" style="font-size:11.5px"><i class="bi bi-keyboard me-1"></i>Ketik 16 digit NIK... (' + nik.length + '/16)</span>'
                    : '';
            }
        });
    }

    // Clear NIK feedback dan reset manual birth date flag saat modal ditutup
    // (disisipkan ke handler hidden.bs.modal yang sudah ada di bawah)

    var contractSection = document.getElementById('aeContractSection');

    // ── Show/hide contract section (only for Contract / PKWT) ────────────────
    // ── Show/hide outsource vendor field ─────────────────────────────────────

    function syncStatusDependentFields() {
        var status = statusEl ? statusEl.value : '';

        // Contract section: visible only when status === 'Contract'
        if (contractSection) {
            var isContract = (status === 'Contract');
            contractSection.style.display = isContract ? '' : 'none';
            // Clear the date when hiding so no stale value gets submitted
            if (!isContract) {
                var endDateEl = document.getElementById('aeEndDateContract');
                if (endDateEl) endDateEl.value = '';
            }
        }

        // Outsource vendor field
        if (outsourceWrap) {
            outsourceWrap.style.display = (status === 'Outsource') ? '' : 'none';
            if (status !== 'Outsource') {
                var vendorEl = document.getElementById('aeOutsourceVendor');
                if (vendorEl) vendorEl.value = '';
            }
        }
    }

    if (statusEl) {
        statusEl.addEventListener('change', syncStatusDependentFields);
        // Run once on load to match the pre-selected default ('Contract')
        syncStatusDependentFields();
    }

    // ── Reset form on modal close ─────────────────────────────────────────────

    modalEl.addEventListener('hidden.bs.modal', function () {
        // Clear all inputs / selects / textareas inside modal-body
        modalEl.querySelectorAll('.modal-body input, .modal-body select, .modal-body textarea')
            .forEach(function (el) {
                if (el.tagName === 'SELECT') {
                    // Restore default: Contract for status, empty for others
                    if (el.id === 'aeStatusEmployee') {
                        el.value = 'Contract';
                    } else {
                        el.value = '';
                    }
                } else if (el.id === 'aeBankName') {
                    el.value = 'BCA';
                } else {
                    el.value = '';
                }
            });
        if (outsourceWrap) outsourceWrap.style.display = 'none';
        // Re-sync dependent fields after reset (Contract default → show contract section)
        syncStatusDependentFields();
        // Clear Employee ID preview
        updateIdPreview();
        // Clear NIK feedback dan reset manual birth date flag
        if (nikFeedback) nikFeedback.innerHTML = '';
        var bdReset = document.getElementById('aeBirthDate');
        if (bdReset) delete bdReset.dataset.manualChanged;
        saveBtn.disabled = true;
    });

    // ── Submit via Fetch ──────────────────────────────────────────────────────

    saveBtn.addEventListener('click', function () {
        var fullName = val('aeFullName');
        var status   = val('aeStatusEmployee');

        if (!fullName || !status) {
            toast('Nama lengkap dan Status Karyawan wajib diisi.', 'danger');
            return;
        }

        // Build payload — semua field Employee sheet (camelCase sesuai EmployeeService::createEmployee)
        var payload = {
            fullName:             fullName,
            statusEmployee:       status,
            joinDate:             val('aeJoinDate'),
            branchName:           val('aeBranchName'),
            division:             val('aeDivision'),
            department:           val('aeDepartment'),
            jobPositionLocation:  val('aeJobPositionLocation'),
            jobPosition:          val('aeJobPosition'),
            jobLevel:             val('aeJobLevel'),
            grade:                val('aeGrade'),
            areaKerja:            val('aeAreaKerja'),
            lokasiKerja:          val('aeLokasiKerja'),
            costCenter:           val('aeCostCenter'),
            directSuperior:       val('aeDirectSuperior'),
            indirectSuperior:     val('aeIndirectSuperior'),
            outsourceVendor:      val('aeOutsourceVendor'),
            endDateContract:      val('aeEndDateContract'),
            // Identitas pribadi
            nikNpwp:              val('aeNik'),
            npwp:                 val('aeNpwp'),
            birthPlace:           val('aeBirthPlace'),
            birthDate:            val('aeBirthDate'),
            bloodType:            val('aeBloodType'),
            gender:               val('aeGender'),
            religion:             val('aeReligion'),
            maritalStatus:        val('aeMarital'),
            ptkpStatus:           val('aePtkp'),
            mobilePhone:          val('aeMobilePhone'),
            personalEmail:        val('aePersonalEmail'),
            workingEmail:         val('aeWorkingEmail'),
            citizenIdAddress:     val('aeCitizenAddress'),
            residentialAddress:   val('aeResidentialAddress'),
            // Bank & BPJS
            bankName:             val('aeBankName'),
            bankAccount:          val('aeBankAccount'),
            bankAccountHolder:    val('aeBankHolder'),
            bpjsKetenagakerjaan:  val('aeBpjsTk'),
            bpjsKesehatan:        val('aeBpjsKes'),
            // Catatan
            hrNotes:              val('aeHrNotes'),
        };

        // Spinner on, button disabled
        saveBtn.disabled = true;
        if (spinner) spinner.classList.remove('d-none');
        if (icon) icon.classList.add('d-none');

        // Resolve store URL — pakai data attribute di modal agar domain-agnostic
        var storeUrl = modalEl.getAttribute('data-store-url') || '/hr/employees';

        var csrfToken = document.querySelector('meta[name="csrf-token"]');

        fetch(storeUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken ? csrfToken.getAttribute('content') : '',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify(payload),
        })
        .then(function (res) { return res.json().then(function (data) { return { ok: res.ok, data: data }; }); })
        .then(function (result) {
            if (result.ok && result.data.success) {
                toast('Karyawan berhasil ditambahkan. Employee ID: ' + (result.data.employeeId || '-'), 'success');
                bootstrap.Modal.getInstance(modalEl).hide();
                // Reload halaman agar tabel dan stats ter-refresh
                setTimeout(function () { window.location.reload(); }, 900);
            } else {
                var msg = result.data.message || 'Gagal menyimpan data karyawan.';
                // Jika Laravel mengembalikan validation errors (errors object)
                if (result.data.errors) {
                    var errs = Object.values(result.data.errors).flat();
                    msg = errs.join(' ');
                }
                toast(msg, 'danger');
                saveBtn.disabled = false;
            }
        })
        .catch(function (err) {
            console.error('addEmployee fetch error:', err);
            toast('Terjadi kesalahan jaringan. Silakan coba kembali.', 'danger');
            saveBtn.disabled = false;
        })
        .finally(function () {
            if (spinner) spinner.classList.add('d-none');
            if (icon) icon.classList.remove('d-none');
        });
    });

})();
</script>
