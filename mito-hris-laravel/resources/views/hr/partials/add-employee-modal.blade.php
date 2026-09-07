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
            <div class="modal-header" style="background:#eb1c24;border-radius:16px 16px 0 0">
                <div class="d-flex align-items-center gap-2 text-white">
                    <i class="bi bi-person-plus-fill fs-5" id="aeModalIcon"></i>
                    <h6 class="modal-title mb-0 fw-bold" id="aeModalTitle">Tambah Karyawan Baru</h6>
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
                    style="font-size:12px;text-transform:uppercase;letter-spacing:.05em;color:#eb1c24">
                    <i class="bi bi-person-badge-fill me-1"></i>Identitas &amp; Data Pribadi
                </p>
                <div class="row g-2 mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold" style="font-size:12.5px">
                            Nama Lengkap <span class="text-danger">*</span>
                        </label>
                        <input type="text" class="form-control form-control-sm" id="aeFullName"
                            placeholder="Nama lengkap sesuai KTP" required autocomplete="off"
                            maxlength="255" />
                        <div class="invalid-feedback" id="aeFullNameFeedback" style="font-size:11.5px">
                            Nama hanya boleh berisi huruf dan spasi.
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold" style="font-size:12.5px">NIK (16 Digit)</label>
                        <input type="text" class="form-control form-control-sm" id="aeNik"
                            maxlength="16" placeholder="16 digit NIK" inputmode="numeric"
                            autocomplete="off" />
                        <div class="invalid-feedback" id="aeNikFeedback" style="font-size:11.5px">NIK harus tepat 16 digit angka.</div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold" style="font-size:12.5px">NPWP (16 Digit)</label>
                        <input type="text" class="form-control form-control-sm" id="aeNpwp"
                            maxlength="16" placeholder="16 digit NPWP" inputmode="numeric"
                            autocomplete="off" />
                        <div class="invalid-feedback" id="aeNpwpFeedback" style="font-size:11.5px">NPWP harus tepat 16 digit angka.</div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold" style="font-size:12.5px">Tempat Lahir</label>
                        <input type="text" class="form-control form-control-sm" id="aeBirthPlace"
                            placeholder="Kota tempat lahir" autocomplete="off" />
                        <div class="invalid-feedback" id="aeBirthPlaceFeedback" style="font-size:11.5px">Tempat lahir hanya boleh berisi huruf dan spasi.</div>
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
                        <div class="input-group input-group-sm">
                            <span class="input-group-text" style="font-size:12px;background:#f0f4f8;color:#374151;font-weight:600">+62</span>
                            <input type="text" class="form-control form-control-sm" id="aeMobilePhone"
                                placeholder="81234567890" inputmode="numeric" maxlength="13"
                                autocomplete="off" />
                        </div>
                        <div class="invalid-feedback d-block" id="aeMobilePhoneFeedback" style="font-size:11.5px;display:none!important"></div>
                        <div class="form-text" style="font-size:11px">Format: 8xxxxxxxxxx (tanpa 0 atau +62)</div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold" style="font-size:12.5px">Email Pribadi</label>
                        <input type="email" class="form-control form-control-sm" id="aePersonalEmail"
                            placeholder="email@domain.com" autocomplete="off" />
                        <div class="invalid-feedback" id="aePersonalEmailFeedback" style="font-size:11.5px">Format email tidak valid.</div>
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
                    style="font-size:12px;text-transform:uppercase;letter-spacing:.05em;color:#eb1c24">
                    <i class="bi bi-wallet2 me-1"></i>Bank &amp; BPJS
                </p>
                <div class="row g-2 mb-3">
                    <div class="col-md-4">
                        <label class="form-label fw-semibold" style="font-size:12.5px">Email Kantor</label>
                        <input type="email" class="form-control form-control-sm" id="aeWorkingEmail"
                            placeholder="email@perusahaan.com" autocomplete="off" />
                        <div class="invalid-feedback" id="aeWorkingEmailFeedback" style="font-size:11.5px">Format email tidak valid.</div>
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
                            placeholder="Nomor rekening bank" inputmode="numeric"
                            autocomplete="off" />
                        <div class="invalid-feedback" id="aeBankAccountFeedback" style="font-size:11.5px">Nomor rekening hanya boleh berisi angka.</div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold" style="font-size:12.5px">Atas Nama Rekening</label>
                        <input type="text" class="form-control form-control-sm" id="aeBankHolder"
                            placeholder="Nama pemilik rekening" autocomplete="off" />
                        <div class="invalid-feedback" id="aeBankHolderFeedback" style="font-size:11.5px">Nama pemilik hanya boleh berisi huruf dan spasi.</div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold" style="font-size:12.5px">BPJS Ketenagakerjaan</label>
                        <input type="text" class="form-control form-control-sm" id="aeBpjsTk"
                            placeholder="Nomor BPJS Ketenagakerjaan" inputmode="numeric"
                            maxlength="16" autocomplete="off" />
                        <div class="invalid-feedback" id="aeBpjsTkFeedback" style="font-size:11.5px">Nomor BPJS Ketenagakerjaan hanya boleh berisi angka.</div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold" style="font-size:12.5px">BPJS Kesehatan</label>
                        <input type="text" class="form-control form-control-sm" id="aeBpjsKes"
                            placeholder="Nomor BPJS Kesehatan" inputmode="numeric"
                            maxlength="13" autocomplete="off" />
                        <div class="invalid-feedback" id="aeBpjsKesFeedback" style="font-size:11.5px">Nomor BPJS Kesehatan hanya boleh berisi angka.</div>
                    </div>
                </div>

                <hr class="my-3" />

                {{-- ============================================================
                     SEKSI 3: STRUKTUR ORGANISASI & PEKERJAAN
                     ============================================================ --}}
                <p class="fw-bold mb-3"
                    style="font-size:12px;text-transform:uppercase;letter-spacing:.05em;color:#eb1c24">
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
                            Employee ID <span class="text-muted fw-normal" style="font-size:11px">(opsional)</span>
                        </label>
                        <input type="text" class="form-control form-control-sm font-monospace" id="aeEmployeeIdPreview"
                            placeholder="Contoh: 202501001"
                            maxlength="50" autocomplete="off"
                            style="letter-spacing:0.04em" />
                        <div class="form-text" style="font-size:11px;color:#6b7280">
                            <i class="bi bi-info-circle me-1"></i>Kosongkan jika ingin di-generate otomatis oleh server.
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
                        style="font-size:12px;text-transform:uppercase;letter-spacing:.05em;color:#eb1c24">
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
                    style="font-size:12px;text-transform:uppercase;letter-spacing:.05em;color:#eb1c24">
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
                    style="background:#eb1c24;border:none" disabled>
                    <span id="aeSpinner" class="spinner-border spinner-border-sm me-1 d-none"
                        role="status" aria-hidden="true"></span>
                    <i class="bi bi-person-plus-fill me-1" id="aeIcon"></i><span id="aeSaveLabel">Simpan Karyawan</span>
                </button>
            </div>

        </div>
    </div>
</div>

<script>
(function () {
    'use strict';

    // ═══════════════════════════════════════════════════════════════════════
    // HELPERS
    // ═══════════════════════════════════════════════════════════════════════

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

    function setValid(el) {
        if (!el) return;
        el.classList.remove('is-invalid');
        el.classList.add('is-valid');
    }

    function setInvalid(el) {
        if (!el) return;
        el.classList.remove('is-valid');
        el.classList.add('is-invalid');
    }

    function clearState(el) {
        if (!el) return;
        el.classList.remove('is-valid', 'is-invalid');
    }

    // ═══════════════════════════════════════════════════════════════════════
    // ELEMENT REFS
    // ═══════════════════════════════════════════════════════════════════════

    var modalEl       = document.getElementById('addEmployeeModal');
    var saveBtn       = document.getElementById('btnAddEmployeeSave');
    var spinner       = document.getElementById('aeSpinner');
    var icon          = document.getElementById('aeIcon');
    var fullNameEl    = document.getElementById('aeFullName');
    var statusEl      = document.getElementById('aeStatusEmployee');
    var outsourceWrap = document.getElementById('aeOutsourceVendorWrap');
    var modalTitle    = document.getElementById('aeModalTitle');
    var modalIcon     = document.getElementById('aeModalIcon');
    var saveLabel     = document.getElementById('aeSaveLabel');

    if (!modalEl || !saveBtn) return;

    // ═══════════════════════════════════════════════════════════════════════
    // VALIDATION STATE — each validator writes here; checkForm reads it
    // ═══════════════════════════════════════════════════════════════════════
    var fieldErrors = {
        fullName:      false,
        birthPlace:    false,
        nik:           false,
        npwp:          false,
        phone:         false,
        personalEmail: false,
        workingEmail:  false,
        bankAccount:   false,
        bankHolder:    false,
        bpjsTk:        false,
        bpjsKes:       false,
    };

    // ═══════════════════════════════════════════════════════════════════════
    // REGEX CONSTANTS (1:1 with apply.blade.php / GAS validateName_)
    // ═══════════════════════════════════════════════════════════════════════
    //
    // Allowed name chars: Unicode Latin basic + extended, spaces, apostrophe,
    // hyphen, period — handles d'Silva, van der Waals, Tri-Wahyu, etc.
    var RX_NAME   = /^[A-Za-zÀ-ÖØ-öø-ÿ\u0100-\u024F '.\-]+$/;
    var RX_DIGITS = /^\d+$/;
    var RX_EMAIL  = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/;
    var NAME_MIN  = 3;

    // ═══════════════════════════════════════════════════════════════════════
    // GENERIC VALIDATORS
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * Validate a name field (letters + allowed punctuation only).
     * @param {string}  elId        element id
     * @param {string}  errorKey    key in fieldErrors
     * @param {boolean} optional    if true, empty value = valid (no error)
     */
    function validateNameField(elId, errorKey, optional) {
        var el = document.getElementById(elId);
        if (!el) return;
        var v = el.value;

        // Auto-sanitize: strip digits and most symbols on every keystroke
        var sanitized = v.replace(/[^A-Za-zÀ-ÖØ-öø-ÿ\u0100-\u024F '.\-]/g, '');
        if (sanitized !== v) {
            el.value = sanitized;
            v = sanitized;
        }

        if (v === '') {
            clearState(el);
            fieldErrors[errorKey] = false;
            checkForm();
            return;
        }

        var valid = v.length >= NAME_MIN && RX_NAME.test(v);
        if (valid || optional) {
            setValid(el);
            fieldErrors[errorKey] = false;
        } else {
            setInvalid(el);
            fieldErrors[errorKey] = true;
        }
        checkForm();
    }

    /**
     * Validate a digits-only field.
     * @param {string}  elId      element id
     * @param {string}  errorKey
     * @param {number}  exactLen  if > 0, enforce exact length
     * @param {boolean} optional
     */
    function validateDigitField(elId, errorKey, exactLen, optional) {
        var el = document.getElementById(elId);
        if (!el) return;

        // Strip non-digits on every keystroke (no letters allowed)
        var stripped = el.value.replace(/\D/g, '');
        if (stripped !== el.value) {
            el.value = stripped;
        }

        var v = el.value;

        if (v === '') {
            clearState(el);
            fieldErrors[errorKey] = false;
            checkForm();
            return;
        }

        var lenOk = (exactLen > 0) ? (v.length === exactLen) : true;
        var valid  = lenOk && RX_DIGITS.test(v);

        if (valid) {
            setValid(el);
            fieldErrors[errorKey] = false;
        } else {
            setInvalid(el);
            fieldErrors[errorKey] = true;
        }
        checkForm();
    }

    /**
     * Validate an email field.
     * @param {string}  elId
     * @param {string}  errorKey
     */
    function validateEmailField(elId, errorKey) {
        var el = document.getElementById(elId);
        if (!el) return;
        var v = el.value.trim();

        if (v === '') {
            clearState(el);
            fieldErrors[errorKey] = false;
            checkForm();
            return;
        }

        if (RX_EMAIL.test(v)) {
            setValid(el);
            fieldErrors[errorKey] = false;
        } else {
            setInvalid(el);
            fieldErrors[errorKey] = true;
        }
        checkForm();
    }

    // ═══════════════════════════════════════════════════════════════════════
    // FIELD-SPECIFIC VALIDATORS
    // ═══════════════════════════════════════════════════════════════════════

    // Nama Lengkap — required, min 3 chars
    function validateFullName() { validateNameField('aeFullName',   'fullName',   false); }

    // Tempat Lahir — optional, letters only
    function validateBirthPlace() { validateNameField('aeBirthPlace', 'birthPlace', true); }

    // NIK — optional, exactly 16 digits
    function validateNik()  { validateDigitField('aeNik',  'nik',  16, true); }

    // NPWP — optional, exactly 16 digits
    function validateNpwp() { validateDigitField('aeNpwp', 'npwp', 16, true); }

    // No. HP — optional, digits 9–13 chars (lokal: 8xx... = 9–12 digit subscriber)
    // Backend normalises to 628xx; UI strips leading 0 or 62/+62 and shows +62 prefix.
    function validatePhone() {
        var el = document.getElementById('aeMobilePhone');
        var fb = document.getElementById('aeMobilePhoneFeedback');
        if (!el) return;

        // Strip non-digits
        var stripped = el.value.replace(/\D/g, '');

        // Normalise: remove leading 0 → subscriber number; strip leading 62
        if (stripped.startsWith('0'))  stripped = stripped.slice(1);
        if (stripped.startsWith('62')) stripped = stripped.slice(2);

        if (stripped !== el.value) el.value = stripped;

        var v = el.value;
        if (v === '') {
            clearState(el);
            if (fb) fb.style.display = 'none';
            fieldErrors.phone = false;
            checkForm();
            return;
        }

        // Valid: starts with 8, length 9–12 (subscriber), all digits
        var valid = /^8\d{8,11}$/.test(v);
        if (valid) {
            setValid(el);
            if (fb) fb.style.display = 'none';
            fieldErrors.phone = false;
        } else {
            setInvalid(el);
            if (fb) {
                fb.textContent = 'Format tidak valid. Ketik 8xxxxxxxxxx (9–12 digit, tanpa 0 atau +62).';
                fb.style.display = '';
            }
            fieldErrors.phone = true;
        }
        checkForm();
    }

    // Email Pribadi — optional
    function validatePersonalEmail() { validateEmailField('aePersonalEmail', 'personalEmail'); }

    // Email Kantor — optional
    function validateWorkingEmail()  { validateEmailField('aeWorkingEmail',  'workingEmail');  }

    // Nomor Rekening — optional, digits only, no fixed length
    function validateBankAccount() { validateDigitField('aeBankAccount', 'bankAccount', 0, true); }

    // Atas Nama Rekening — optional, letters + spaces
    function validateBankHolder()  { validateNameField('aeBankHolder', 'bankHolder', true); }

    // BPJS Ketenagakerjaan — optional, digits only
    function validateBpjsTk()  { validateDigitField('aeBpjsTk',  'bpjsTk',  0, true); }

    // BPJS Kesehatan — optional, digits only
    function validateBpjsKes() { validateDigitField('aeBpjsKes', 'bpjsKes', 0, true); }

    // ═══════════════════════════════════════════════════════════════════════
    // ATTACH LISTENERS
    // ═══════════════════════════════════════════════════════════════════════

    function on(id, fn) {
        var el = document.getElementById(id);
        if (!el) return;
        el.addEventListener('input', fn);
        el.addEventListener('blur',  fn);
    }

    on('aeFullName',     validateFullName);
    on('aeBirthPlace',   validateBirthPlace);
    on('aeNik',          validateNik);
    on('aeNpwp',         validateNpwp);
    on('aeMobilePhone',  validatePhone);
    on('aePersonalEmail',validatePersonalEmail);
    on('aeWorkingEmail', validateWorkingEmail);
    on('aeBankAccount',  validateBankAccount);
    on('aeBankHolder',   validateBankHolder);
    on('aeBpjsTk',       validateBpjsTk);
    on('aeBpjsKes',      validateBpjsKes);

    // Status change always triggers checkForm
    if (statusEl) statusEl.addEventListener('change', checkForm);

    // Vendor input triggers checkForm
    var vendorInputEl = document.getElementById('aeOutsourceVendor');
    if (vendorInputEl) vendorInputEl.addEventListener('input', checkForm);

    // ═══════════════════════════════════════════════════════════════════════
    // CHECK FORM — gate the Save button
    // ═══════════════════════════════════════════════════════════════════════

    function checkForm() {
        var isOutsource = (modalEl.getAttribute('data-mode') === 'outsource');
        var vendorOk    = !isOutsource || val('aeOutsourceVendor') !== '';

        // Any active validation error blocks save
        var hasErrors = Object.values(fieldErrors).some(Boolean);

        var ok = val('aeFullName') !== ''
              && val('aeStatusEmployee') !== ''
              && !hasErrors
              && vendorOk;

        saveBtn.disabled = !ok;
    }

    // ═══════════════════════════════════════════════════════════════════════
    // MODE: 'employee' vs 'outsource'
    // ═══════════════════════════════════════════════════════════════════════

    function applyMode(mode) {
        var isOutsource = (mode === 'outsource');

        if (modalTitle) modalTitle.textContent = isOutsource ? 'Tambah Karyawan Outsource' : 'Tambah Karyawan Baru';
        if (modalIcon)  modalIcon.className    = isOutsource ? 'bi bi-building-fill-gear fs-5' : 'bi bi-person-plus-fill fs-5';
        if (saveLabel)  saveLabel.textContent  = isOutsource ? 'Simpan Outsource' : 'Simpan Karyawan';

        if (statusEl) {
            if (isOutsource) {
                statusEl.value    = 'Outsource';
                statusEl.disabled = true;
                statusEl.style.cssText = 'background:#f0f4f8;cursor:not-allowed;pointer-events:none';
            } else {
                statusEl.value    = 'Contract';
                statusEl.disabled = false;
                statusEl.style.cssText = '';
            }
        }

        syncStatusDependentFields();
        checkForm();
    }

    modalEl.addEventListener('show.bs.modal', function () {
        applyMode(this.getAttribute('data-mode') || 'employee');
    });

    // ═══════════════════════════════════════════════════════════════════════
    // CONTRACT / OUTSOURCE SECTION VISIBILITY
    // ═══════════════════════════════════════════════════════════════════════

    var contractSection = document.getElementById('aeContractSection');

    function syncStatusDependentFields() {
        var status = statusEl ? statusEl.value : '';

        if (contractSection) {
            var isContract = (status === 'Contract');
            contractSection.style.display = isContract ? '' : 'none';
            if (!isContract) {
                var ed = document.getElementById('aeEndDateContract');
                if (ed) ed.value = '';
            }
        }

        if (outsourceWrap) {
            outsourceWrap.style.display = (status === 'Outsource') ? '' : 'none';
            if (status !== 'Outsource') {
                var ov = document.getElementById('aeOutsourceVendor');
                if (ov) ov.value = '';
            }
        }
    }

    if (statusEl) {
        statusEl.addEventListener('change', syncStatusDependentFields);
        syncStatusDependentFields();
    }

    // ═══════════════════════════════════════════════════════════════════════
    // RESET on modal close
    // ═══════════════════════════════════════════════════════════════════════

    modalEl.addEventListener('hidden.bs.modal', function () {
        modalEl.querySelectorAll('.modal-body input, .modal-body select, .modal-body textarea')
            .forEach(function (el) {
                if (el.tagName === 'SELECT') {
                    el.value = (el.id === 'aeStatusEmployee') ? 'Contract' : '';
                } else if (el.id === 'aeBankName') {
                    el.value = 'BCA';
                } else {
                    el.value = '';
                }
                clearState(el);
            });

        // Reset error state map
        Object.keys(fieldErrors).forEach(function (k) { fieldErrors[k] = false; });

        if (outsourceWrap) outsourceWrap.style.display = 'none';
        syncStatusDependentFields();

        var phoneFb = document.getElementById('aeMobilePhoneFeedback');
        if (phoneFb) phoneFb.style.display = 'none';

        saveBtn.disabled = true;
    });

    // ═══════════════════════════════════════════════════════════════════════
    // SUBMIT
    // ═══════════════════════════════════════════════════════════════════════

    saveBtn.addEventListener('click', function () {
        // Run all validators once more to catch un-touched fields
        validateFullName();
        validateBirthPlace();
        validateNik();
        validateNpwp();
        validatePhone();
        validatePersonalEmail();
        validateWorkingEmail();
        validateBankAccount();
        validateBankHolder();
        validateBpjsTk();
        validateBpjsKes();

        var fullName    = val('aeFullName');
        var status      = val('aeStatusEmployee');
        var isOutsource = (modalEl.getAttribute('data-mode') === 'outsource');

        if (!fullName || !status) {
            toast('Nama lengkap dan Status Karyawan wajib diisi.', 'danger');
            return;
        }
        if (isOutsource && !val('aeOutsourceVendor')) {
            toast('Vendor Outsource wajib diisi untuk status Outsource.', 'danger');
            return;
        }
        if (Object.values(fieldErrors).some(Boolean)) {
            toast('Perbaiki field yang tidak valid terlebih dahulu.', 'danger');
            return;
        }

        // ── Build phone value for backend: normalise to 628xx format ──────
        var rawPhone = val('aeMobilePhone');   // already stripped to subscriber (8xx...)
        var phoneForBackend = rawPhone ? '62' + rawPhone : '';

        var payload = {
            employeeId:           val('aeEmployeeIdPreview'),
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
            nikNpwp:              val('aeNik'),
            npwp:                 val('aeNpwp'),
            birthPlace:           val('aeBirthPlace'),
            birthDate:            val('aeBirthDate'),
            bloodType:            val('aeBloodType'),
            gender:               val('aeGender'),
            religion:             val('aeReligion'),
            maritalStatus:        val('aeMarital'),
            ptkpStatus:           val('aePtkp'),
            mobilePhone:          phoneForBackend,
            personalEmail:        val('aePersonalEmail'),
            workingEmail:         val('aeWorkingEmail'),
            citizenIdAddress:     val('aeCitizenAddress'),
            residentialAddress:   val('aeResidentialAddress'),
            bankName:             val('aeBankName'),
            bankAccount:          val('aeBankAccount'),
            bankAccountHolder:    val('aeBankHolder'),
            bpjsKetenagakerjaan:  val('aeBpjsTk'),
            bpjsKesehatan:        val('aeBpjsKes'),
            hrNotes:              val('aeHrNotes'),
        };

        saveBtn.disabled = true;
        if (spinner) spinner.classList.remove('d-none');
        if (icon) icon.classList.add('d-none');

        var storeUrl  = modalEl.getAttribute('data-store-url') || '/hr/employees';
        var csrfToken = document.querySelector('meta[name="csrf-token"]');

        fetch(storeUrl, {
            method: 'POST',
            headers: {
                'Content-Type':    'application/json',
                'Accept':          'application/json',
                'X-CSRF-TOKEN':    csrfToken ? csrfToken.getAttribute('content') : '',
                'X-Requested-With':'XMLHttpRequest',
            },
            body: JSON.stringify(payload),
        })
        .then(function (res) { return res.json().then(function (d) { return { ok: res.ok, data: d }; }); })
        .then(function (result) {
            if (result.ok && result.data.success) {
                toast('Karyawan berhasil ditambahkan. Employee ID: ' + (result.data.employeeId || '-'), 'success');
                bootstrap.Modal.getInstance(modalEl).hide();
                setTimeout(function () { window.location.reload(); }, 900);
            } else {
                var msg = result.data.message || 'Gagal menyimpan data karyawan.';
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
