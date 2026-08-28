{{--
  partials/employee-form-modal.blade.php
  Modal Edit Employee (1:1 mapping dari GAS js/employee.html empOpenEdit / empSave)
  Diinclude dari layouts/hr.blade.php — hanya satu kali, tidak per-page.

  Field mapping (sesuai GAS keyMap di backend/Employee.gs updateEmployee):
    Seksi 1: Identitas & Data Pribadi
    Seksi 2: Bank & BPJS
    Seksi 3: Struktur Organisasi & Pekerjaan
    Seksi 4: Kontrak
    Seksi 5: Karier & Mutasi
    Seksi 6: Catatan
--}}
<div class="modal fade" id="empFormModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content" style="border-radius:16px">
            <div class="modal-header" style="background:#eb1c24;border-radius:16px 16px 0 0">
                <div class="d-flex align-items-center gap-2 text-white">
                    <i class="bi bi-person-lines-fill fs-5"></i>
                    <h6 class="modal-title mb-0 fw-bold" id="empFormTitle">Edit Karyawan</h6>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body p-4">
                {{-- Hidden employee ID --}}
                <input type="hidden" id="efId" />

                {{-- Banner preview karyawan --}}
                <div class="p-3 rounded-3 mb-4" id="efPreviewBanner"
                    style="background:#f0f7ff;border:1px solid #eb1c24;display:none">
                    <div class="d-flex align-items-center gap-3">
                        <div style="width:44px;height:44px;font-size:16px;flex-shrink:0;background:#eb1c24;color:#fff;border-radius:12px;display:flex;align-items:center;justify-content:center;font-weight:800"
                            id="efBannerAvatar">?</div>
                        <div class="flex-grow-1">
                            <div class="fw-bold text-navy" id="efBannerName" style="font-size:15px">-</div>
                            <div class="text-muted" style="font-size:12px" id="efBannerMeta">-</div>
                        </div>
                    </div>
                </div>

                {{-- ============================================================
             SEKSI 1: IDENTITAS & DATA PRIBADI
             ============================================================ --}}
                <div class="mb-3">
                    <p class="fw-bold mb-3"
                        style="font-size:12px;text-transform:uppercase;letter-spacing:.05em;color:#eb1c24">
                        <i class="bi bi-person-badge-fill me-1"></i>Identitas & Data Pribadi
                    </p>
                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="font-size:12.5px">Nama Lengkap <span
                                    class="text-danger">*</span></label>
                            <input type="text" class="form-control form-control-sm" id="efName"
                                placeholder="Nama lengkap sesuai KTP" required />
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold" style="font-size:12.5px">NIK (16 Digit)</label>
                            <input type="text" class="form-control form-control-sm" id="efNik" maxlength="16"
                                placeholder="16 digit NIK" />
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold" style="font-size:12.5px">NPWP (16 Digit)</label>
                            <input type="text" class="form-control form-control-sm" id="efNpwp" maxlength="16"
                                placeholder="16 digit NPWP" />
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold" style="font-size:12.5px">Tempat Lahir</label>
                            <input type="text" class="form-control form-control-sm" id="efBirthPlace"
                                placeholder="Kota tempat lahir" />
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold" style="font-size:12.5px">Tanggal Lahir</label>
                            <input type="date" class="form-control form-control-sm" id="efBirthDate" />
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold" style="font-size:12.5px">Golongan Darah</label>
                            <select class="form-select form-select-sm" id="efBloodType">
                                <option value="">— Pilih —</option>
                                <option value="A">A</option>
                                <option value="B">B</option>
                                <option value="AB">AB</option>
                                <option value="O">O</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold" style="font-size:12.5px">Jenis Kelamin</label>
                            <select class="form-select form-select-sm" id="efGender">
                                <option value="">— Pilih —</option>
                                <option value="Laki-laki">Laki-laki</option>
                                <option value="Perempuan">Perempuan</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold" style="font-size:12.5px">Agama</label>
                            <select class="form-select form-select-sm" id="efReligion">
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
                            <select class="form-select form-select-sm" id="efMarital">
                                <option value="">— Pilih —</option>
                                <option value="Belum Menikah">Belum Menikah</option>
                                <option value="Menikah">Menikah</option>
                                <option value="Cerai">Cerai</option>
                                <option value="Duda/Janda">Duda/Janda</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold" style="font-size:12.5px">Status PTKP</label>
                            <select class="form-select form-select-sm" id="efPtkp">
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
                            <label class="form-label fw-semibold" style="font-size:12.5px">Email Pribadi</label>
                            <input type="email" class="form-control form-control-sm" id="efEmail"
                                placeholder="email@domain.com" />
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold" style="font-size:12.5px">No. HP</label>
                            <input type="text" class="form-control form-control-sm" id="efPhone"
                                placeholder="628xxxxxxxxxx" />
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold" style="font-size:12.5px">Alamat KTP</label>
                            <textarea class="form-control form-control-sm" id="efAddress" rows="2" placeholder="Alamat sesuai KTP"></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold" style="font-size:12.5px">Alamat Domisili</label>
                            <textarea class="form-control form-control-sm" id="efResidentialAddress" rows="2"
                                placeholder="Alamat domisili (kosongkan jika sama dengan KTP)"></textarea>
                        </div>
                    </div>
                </div>

                <hr class="my-3" />

                {{-- ============================================================
             SEKSI 2: BANK & BPJS
             ============================================================ --}}
                <div class="mb-3">
                    <p class="fw-bold mb-3"
                        style="font-size:12px;text-transform:uppercase;letter-spacing:.05em;color:#eb1c24">
                        <i class="bi bi-wallet2 me-1"></i>Bank &amp; BPJS
                    </p>
                    <div class="row g-2">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold" style="font-size:12.5px">Email Kantor</label>
                            <input type="email" class="form-control form-control-sm" id="efWorkingEmail"
                                placeholder="email@perusahaan.com" />
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold" style="font-size:12.5px">Nama Bank</label>
                            <input type="text" class="form-control form-control-sm" id="efBankName"
                                value="BCA" readonly style="background:#f8f9fa" />
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold" style="font-size:12.5px">Nomor Rekening</label>
                            <input type="text" class="form-control form-control-sm" id="efBankAccount"
                                placeholder="Nomor rekening bank" />
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold" style="font-size:12.5px">Atas Nama Rekening</label>
                            <input type="text" class="form-control form-control-sm" id="efBankHolder"
                                placeholder="Nama pemilik rekening" />
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold" style="font-size:12.5px">BPJS
                                Ketenagakerjaan</label>
                            <input type="text" class="form-control form-control-sm" id="efBpjsTk"
                                placeholder="Nomor BPJS Ketenagakerjaan" />
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold" style="font-size:12.5px">BPJS Kesehatan</label>
                            <input type="text" class="form-control form-control-sm" id="efBpjsKes"
                                placeholder="Nomor BPJS Kesehatan" />
                        </div>
                    </div>
                </div>

                <hr class="my-3" />

                {{-- ============================================================
             SEKSI 3: STRUKTUR ORGANISASI & PEKERJAAN
             ============================================================ --}}
                <div class="mb-3">
                    <p class="fw-bold mb-3"
                        style="font-size:12px;text-transform:uppercase;letter-spacing:.05em;color:#eb1c24">
                        <i class="bi bi-briefcase-fill me-1"></i>Struktur Organisasi &amp; Pekerjaan
                    </p>
                    <div class="row g-2">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold" style="font-size:12.5px">Status Karyawan</label>
                            <select class="form-select form-select-sm" id="efStatusEmployee">
                                <option value="Contract">Contract / PKWT</option>
                                <option value="Probation">Probation</option>
                                <option value="Permanent">Permanent / PKWTT</option>
                                <option value="Outsource">Outsource</option>
                                <option value="On Leave">On Leave</option>
                                <option value="Resigned">Resigned</option>
                                <option value="Terminated">Terminated</option>
                                <option value="Retired">Retired</option>
                                <option value="Inactive">Inactive</option>
                                <option value="Contract Finished">Contract Finished</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold" style="font-size:12.5px">Entitas / Branch</label>
                            <input type="text" class="form-control form-control-sm" id="efBranch"
                                placeholder="Nama entitas perusahaan" />
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold" style="font-size:12.5px">Divisi</label>
                            <input type="text" class="form-control form-control-sm" id="efDivision"
                                placeholder="Nama divisi" />
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold" style="font-size:12.5px">Departemen</label>
                            <input type="text" class="form-control form-control-sm" id="efDept"
                                placeholder="Nama departemen" />
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold" style="font-size:12.5px">Cost Center</label>
                            <input type="text" class="form-control form-control-sm" id="efCostCenter"
                                placeholder="Kode cost center" />
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold" style="font-size:12.5px">Jabatan (dengan
                                Lokasi)</label>
                            <input type="text" class="form-control form-control-sm" id="efPos"
                                placeholder="Jabatan + lokasi" />
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold" style="font-size:12.5px">Jabatan (tanpa
                                Lokasi)</label>
                            <input type="text" class="form-control form-control-sm" id="efPosNoLoc"
                                placeholder="Jabatan tanpa lokasi" />
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold" style="font-size:12.5px">Job Level</label>
                            <input type="text" class="form-control form-control-sm" id="efJobLevel"
                                placeholder="Staff / Supervisor / Manager..." />
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold" style="font-size:12.5px">Grade</label>
                            <input type="text" class="form-control form-control-sm" id="efGrade"
                                placeholder="Grade / Level" />
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold" style="font-size:12.5px">Area Kerja
                                (Kecamatan)</label>
                            <input type="text" class="form-control form-control-sm" id="efDistrict"
                                placeholder="Area kerja" />
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold" style="font-size:12.5px">Lokasi Kerja (Kota)</label>
                            <input type="text" class="form-control form-control-sm" id="efCity"
                                placeholder="Kota lokasi kerja" />
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold" style="font-size:12.5px">Tanggal Masuk</label>
                            <input type="date" class="form-control form-control-sm" id="efJoin" />
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="font-size:12.5px">Atasan Langsung</label>
                            <input type="text" class="form-control form-control-sm" id="efDirectSup"
                                placeholder="Nama atasan langsung" />
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="font-size:12.5px">Atasan Tidak
                                Langsung</label>
                            <input type="text" class="form-control form-control-sm" id="efIndirectSup"
                                placeholder="Nama atasan tidak langsung" />
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="font-size:12.5px">Vendor Outsource</label>
                            <input type="text" class="form-control form-control-sm" id="efOutsourceVendor"
                                placeholder="Nama vendor (jika outsource)" />
                        </div>
                    </div>
                </div>

                <hr class="my-3" />

                {{-- ============================================================
             SEKSI 4: KONTRAK
             ============================================================ --}}
                <div class="mb-3">
                    <p class="fw-bold mb-3"
                        style="font-size:12px;text-transform:uppercase;letter-spacing:.05em;color:#eb1c24">
                        <i class="bi bi-file-earmark-text-fill me-1"></i>Kontrak
                    </p>
                    <div class="row g-2">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold" style="font-size:12.5px">Tanggal Mulai
                                Kontrak</label>
                            <input type="date" class="form-control form-control-sm" id="efContractStart" />
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold" style="font-size:12.5px">Tanggal Akhir
                                Kontrak</label>
                            <input type="date" class="form-control form-control-sm" id="efContractEnd" />
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold" style="font-size:12.5px">Durasi Kontrak</label>
                            <select class="form-select form-select-sm" id="efContractDuration">
                                <option value="">— Pilih —</option>
                                <option value="1 Bulan">1 Bulan</option>
                                <option value="3 Bulan">3 Bulan</option>
                                <option value="6 Bulan">6 Bulan</option>
                                <option value="1 Tahun">1 Tahun</option>
                                <option value="2 Tahun">2 Tahun</option>
                                <option value="3 Tahun">3 Tahun</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="font-size:12.5px">Nomor Kontrak</label>
                            <input type="text" class="form-control form-control-sm" id="efContractNumber"
                                placeholder="Nomor kontrak PKWT" />
                        </div>
                    </div>
                </div>

                <hr class="my-3" />

                {{-- ============================================================
             SEKSI 5: KARIER & MUTASI
             ============================================================ --}}
                <div class="mb-3">
                    <p class="fw-bold mb-3"
                        style="font-size:12px;text-transform:uppercase;letter-spacing:.05em;color:#eb1c24">
                        <i class="bi bi-arrow-left-right me-1"></i>Karier &amp; Mutasi
                    </p>
                    {{-- Info: field mutasi hanya terisi jika karyawan pernah dimutasi --}}
                    <div class="alert alert-info d-flex align-items-center gap-2 py-2 px-3 mb-3" id="efNoMutasiInfo"
                        style="font-size:12px;border-radius:8px">
                        <i class="bi bi-info-circle-fill"></i>
                        <span>Field Jabatan Sebelumnya, Tipe Rotasi, dan Nomor SK hanya diisi jika karyawan pernah
                            dimutasi/rotasi. Gunakan Fitur <strong>Rotasi</strong> untuk memproses rotasi baru.</span>
                    </div>
                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="font-size:12.5px">Jabatan Sebelumnya</label>
                            <input type="text" class="form-control form-control-sm" id="efFormerPos"
                                placeholder="Jabatan sebelum mutasi" disabled />
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="font-size:12.5px">Tipe Rotasi</label>
                            <input type="text" class="form-control form-control-sm" id="efRotationType"
                                placeholder="Tipe rotasi" disabled />
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="font-size:12.5px">Tanggal Efektif
                                Mutasi</label>
                            <input type="date" class="form-control form-control-sm" id="efMutasiDate" disabled />
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="font-size:12.5px">Nomor SK</label>
                            <input type="text" class="form-control form-control-sm" id="efNoSk"
                                placeholder="Nomor SK / surat keputusan" disabled />
                        </div>
                        <div class="col-md-3" id="efResignDateWrap" style="display:none">
                            <label class="form-label fw-semibold" style="font-size:12.5px">Tanggal Resign / Akhir
                                Kerja</label>
                            <input type="date" class="form-control form-control-sm" id="efResignDate" />
                        </div>
                    </div>
                </div>

                <hr class="my-3" />

                {{-- ============================================================
             SEKSI 6: CATATAN
             ============================================================ --}}
                <div class="mb-1">
                    <p class="fw-bold mb-3"
                        style="font-size:12px;text-transform:uppercase;letter-spacing:.05em;color:#eb1c24">
                        <i class="bi bi-chat-square-text-fill me-1"></i>Catatan
                    </p>
                    <div class="row g-2">
                        <div class="col-12">
                            <label class="form-label fw-semibold" style="font-size:12.5px">Catatan</label>
                            <textarea class="form-control form-control-sm" id="efNotes" rows="4" placeholder="Tulis catatan..."></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary btn-sm"
                    data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-sm text-white fw-semibold" style="background:#eb1c24"
                    id="btnEmpSave">
                    <i class="bi bi-check2-circle me-1"></i>Simpan
                </button>
            </div>
        </div>
    </div>
</div>
