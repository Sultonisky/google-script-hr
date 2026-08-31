<!-- partials/StatusPageModals.html — MODAL DOKUMEN & STATUS REKRUTMEN (1:1 from GAS) -->

@can('create_offering')
    <!-- 1. MODAL ONBOARDING & KONTRAK PKWT (1:1 from GAS partials/StatusPageModals.html) -->
    <div class="modal fade" id="onboardingModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content" style="border-radius: 16px">
                <div class="modal-header" style="background: #eb1c24; border-radius: 16px 16px 0 0">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-file-earmark-check-fill text-white fs-5"></i>
                        <h6 class="modal-title mb-0 text-white fw-bold">Proses Kontrak PKWT &amp; Onboarding Karyawan</h6>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body p-4">
                    <input type="hidden" id="onboardingRecruitmentId" />
                    <input type="hidden" id="onboardingEmployeeId" />

                    <!-- Step 1: Search kandidat (Accepted + Offering Diterima) -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold" style="font-size: 13px">
                            <i class="bi bi-search me-1"></i>Cari Kandidat
                            <span class="text-muted fw-normal">(Status: Accepted + Offering Diterima)</span>
                        </label>
                        <div class="position-relative">
                            <input type="text" class="form-control" id="onboardingCandSearch"
                                placeholder="Ketik nama atau Recruitment ID..." autocomplete="off"
                                style="font-size: 13px; padding-right: 36px" />
                            <i class="bi bi-x-circle-fill position-absolute" id="onboardingSearchClear"
                                style="right: 10px; top: 50%; transform: translateY(-50%); cursor: pointer; color: #aaa; display: none"></i>
                        </div>
                        <div id="onboardingSearchDropdown" class="border rounded-3 mt-1 shadow-sm"
                            style="display: none; max-height: 200px; overflow-y: auto; background: #fff; z-index: 9999; position: relative">
                        </div>
                    </div>

                    <!-- Info Kandidat terpilih -->
                    <div id="onboardingCandPreview" style="display: none">
                        <div class="p-3 rounded-3 mb-4" style="border: 1px solid #eb1c24">
                            <div class="d-flex align-items-center gap-3">
                                <div class="avatar-sm" id="onboardingCandAvatar"
                                    style="width: 44px; height: 44px; font-size: 16px; flex-shrink: 0; background: var(--color-primary, #eb1c24); color: #fff; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-weight: 800">
                                    ?</div>
                                <div class="flex-grow-1">
                                    <div class="fw-bold text-navy" id="onboardingCandName" style="font-size: 15px">-</div>
                                    <div class="text-muted" id="onboardingCandPosition" style="font-size: 12px">-</div>
                                    <div style="font-size: 11px; color: #888; margin-top: 2px" id="onboardingCandEmail">-
                                    </div>
                                </div>
                                <div class="text-end flex-shrink-0" style="font-size: 11.5px">
                                    <div class="text-muted">Recruitment ID</div>
                                    <div class="fw-semibold" id="onboardingCandRid">-</div>
                                    <div class="text-muted mt-1">Employee ID</div>
                                    <div class="fw-semibold" id="onboardingCandEmpId">-</div>
                                </div>
                            </div>
                        </div>

                        <!-- Form onboarding — field selaras EMPLOYEE_HEADERS -->
                        <div id="onboardingFormSection">
                            <p class="fw-bold mb-3"
                                style="font-size: 12px; text-transform: uppercase; letter-spacing: 0.05em; color: var(--color-primary)">
                                <i class="bi bi-briefcase-fill me-1"></i>Informasi Pekerjaan &amp; Penempatan
                            </p>
                            <div class="row g-3 mb-4">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold" style="font-size: 13px">Branch Name<span
                                            class="text-danger">*</span></label>
                                    <input type="text" class="form-control form-control-sm" id="onbBranchName" disabled
                                        readonly />
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold" style="font-size: 13px">Division</label>
                                    <select class="form-select form-select-sm" id="onbDivision">
                                        <option value="">— Pilih —</option>
                                        <option value="RnD &amp; aftersales">RnD &amp; aftersales</option>
                                        <option value="Commercial Division">Commercial Division</option>
                                        <option value="Sales">Sales</option>
                                        <option value="FAT &amp; GA">FAT &amp; GA</option>
                                        <option value="Manufacture">Manufacture</option>
                                        <option value="E-Commerce">E-Commerce</option>
                                        <option value="IT">IT</option>
                                        <option value="Digital Marketing">Digital Marketing</option>
                                        <option value="Buyer - Import">Buyer - Import</option>
                                        <option value="Marketing">Marketing</option>
                                        <option value="Creative">Creative</option>
                                        <option value="HR &amp; Legal">HR &amp; Legal</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold" style="font-size: 13px">Department <span
                                            class="text-danger">*</span></label>
                                    <select class="form-select form-select-sm" id="onbDepartment">
                                        <option value="">— Pilih —</option>
                                        <option value="Human Resources">Human Resources</option>
                                        <option value="Finance">Finance</option>
                                        <option value="Accounting">Accounting</option>
                                        <option value="Marketing">Marketing</option>
                                        <option value="Digital Marketing">Digital Marketing</option>
                                        <option value="Sales">Sales</option>
                                        <option value="IT">IT</option>
                                        <option value="Engineering">Engineering</option>
                                        <option value="Operations">Operations</option>
                                        <option value="Legal">Legal</option>
                                        <option value="GA">GA</option>
                                        <option value="Warehouse">Warehouse</option>
                                        <option value="Purchasing">Purchasing</option>
                                        <option value="Quality Control">Quality Control</option>
                                        <option value="Customer Service">Customer Service</option>
                                        <option value="Admin">Admin</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold" style="font-size: 13px">Job Position <span
                                            class="text-danger">*</span></label>
                                    <input type="text" class="form-control form-control-sm" id="onbPosition"
                                        placeholder="Contoh: HR Staff" />
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold" style="font-size: 13px">Direct Superior</label>
                                    <input type="text" class="form-control form-control-sm" id="onbDirectSuperior"
                                        placeholder="Nama atasan langsung" />
                                </div>
                            </div>

                            <p class="fw-bold mb-3"
                                style="font-size: 12px; text-transform: uppercase; letter-spacing: 0.05em; color: var(--color-primary)">
                                <i class="bi bi-file-earmark-text-fill me-1"></i>Kontrak PKWT &amp; Upah
                            </p>
                            <div class="row g-3 mb-4">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold" style="font-size: 13px">Tanggal Mulai / Join
                                        Date <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control form-control-sm" id="onbJoinDate" />
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold" style="font-size: 13px">Durasi Kontrak PKWT
                                        <span class="text-danger">*</span></label>
                                    <select class="form-select form-select-sm" id="onbContractDuration">
                                        <option value="">— Pilih —</option>
                                        <option value="3 Bulan">3 Bulan</option>
                                        <option value="6 Bulan">6 Bulan</option>
                                        <option value="12 Bulan" selected>12 Bulan (1 Tahun)</option>
                                        <option value="24 Bulan">24 Bulan (2 Tahun)</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold" style="font-size: 13px">Akhir Kontrak (End
                                        Date)</label>
                                    <input type="date" class="form-control form-control-sm" id="onbContractEnd" />
                                    <div class="form-text" id="onbContractEndHint" style="font-size: 11px">Terisi
                                        otomatis dari durasi kontrak</div>
                                </div>
                                <input type="hidden" id="onbContractNumber" />
                                <!-- Gaji & Tunjangan: hidden, diisi otomatis dari data Offering Letter -->
                                <input type="hidden" id="onbSalaryBasic" />
                                <input type="hidden" id="onbSalaryAllowance" />
                            </div>

                            <p class="fw-bold mb-3"
                                style="font-size: 12px; text-transform: uppercase; letter-spacing: 0.05em; color: var(--color-primary)">
                                <i class="bi bi-calendar2-check-fill me-1"></i>Ketentuan Dokumen &amp; Jadwal Kerja
                            </p>
                            <div class="row g-3 mb-4">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold" style="font-size: 13px">Tanggal Dokumen Kontrak
                                        <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control form-control-sm" id="onbDocDate" />
                                    <div class="form-text" style="font-size: 11px">Bisa dipilih — kontrak boleh dibuat
                                        sebelum/sesudah hari H</div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold" style="font-size: 13px">Ketentuan Jam Masuk
                                        Kerja</label>
                                    <select class="form-select form-select-sm" id="onbJamMasuk">
                                        <option value="">— Pilih —</option>
                                        <option value="mulai pukul 07.00 WIB" selected>Pukul 07.00 WIB</option>
                                        <option
                                            value="mulai pukul 07.00 WIB dan selambat-lambatnya sampai dengan pukul 07.15 WIB">
                                            07.00 WIB (mlm 07.15 WIB)</option>
                                        <option value="mulai pukul 08.00 WIB">Pukul 08.00 WIB</option>
                                        <option
                                            value="mulai pukul 08.00 WIB dan selambat-lambatnya sampai dengan pukul 08.15 WIB">
                                            08.00 WIB (mlm 08.15 WIB)</option>
                                        <option value="sesuai shift penempatan">Sesuai shift penempatan</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold" style="font-size: 13px">Jadwal Waktu
                                        Kerja</label>
                                    <select class="form-select form-select-sm" id="onbWorkSchedule">
                                        <option value="">— Pilih —</option>
                                        <option value="Normal" selected>Normal (5 hari kerja, 8 jam/hari, 40 jam/minggu)
                                        </option>
                                        <option value="Shift Khusus">Shift Khusus (disesuaikan kesepakatan dengan Kepala
                                            Divisi)</option>
                                    </select>
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-semibold" style="font-size: 13px">Klausul Jangka Waktu
                                        <span class="text-muted fw-normal">(free text)</span></label>
                                    <textarea class="form-control form-control-sm" id="onbTenorText" rows="2"
                                        placeholder="PIHAK PERTAMA dengan ini menyatakan persetujuannya untuk mempekerjakan PIHAK KEDUA sebagai Karyawan PIHAK PERTAMA..."></textarea>
                                </div>
                            </div>
                        </div>
                        <!-- /#onboardingFormSection -->
                    </div>
                    <!-- /#onboardingCandPreview -->
                </div>

                <div class="modal-footer">
                    <button class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button class="btn btn-sm text-white fw-semibold" style="background: #eb1c24"
                        id="btnConfirmOnboarding" disabled>
                        <span id="btnOnboardingText"><i class="bi bi-file-earmark-check-fill me-1"></i>Simpan &amp;
                            Generate Kontrak PKWT</span>
                        <span id="btnOnboardingLoading" style="display: none">
                            <span class="spinner-border spinner-border-sm me-1"></span>Memproses...
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- 2. MODAL BUAT OFFERING LETTER (1:1 from GAS partials/StatusPageModals.html) -->
    <div class="modal fade" id="offeringModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content" style="border-radius: 16px">
                <div class="modal-header" style="background: var(--color-primary, #eb1c24); border-radius: 16px 16px 0 0">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-file-earmark-text-fill text-white fs-5"></i>
                        <h6 class="modal-title mb-0 text-white fw-bold">Buat / Generate Offering Letter</h6>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body p-4">
                    <!-- Live Search Kandidat -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold" style="font-size: 13px">
                            <i class="bi bi-search me-1"></i>Cari Kandidat (Status: Accepted)
                        </label>
                        <div class="position-relative">
                            <input type="text" class="form-control" id="offeringCandSearch"
                                placeholder="Ketik nama atau Recruitment ID..." autocomplete="off"
                                style="font-size: 13px; padding-right: 36px" />
                            <i class="bi bi-x-circle-fill position-absolute" id="offeringSearchClear"
                                style="right: 10px; top: 50%; transform: translateY(-50%); cursor: pointer; color: #aaa; display: none"></i>
                        </div>
                        <div id="offeringSearchDropdown" class="border rounded-3 mt-1 shadow-sm"
                            style="display: none; max-height: 200px; overflow-y: auto; background: #fff; z-index: 9999; position: relative">
                        </div>
                    </div>

                    <!-- Preview Info Kandidat -->
                    <div id="offeringCandPreview" style="display: none">
                        <div class="p-3 rounded-3 mb-3" style="background: #fff5f5; border: 1px solid #fed7d7">
                            <div class="d-flex align-items-center gap-3">
                                <div class="avatar-sm" id="offeringCandAvatar"
                                    style="width: 44px; height: 44px; font-size: 16px; flex-shrink: 0; background: var(--color-primary, #eb1c24); color: #fff; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-weight: 800">
                                    ?</div>
                                <div class="flex-grow-1">
                                    <div class="fw-bold text-navy" id="offeringCandName" style="font-size: 15px">-</div>
                                    <div class="text-muted" style="font-size: 12px">
                                        <span id="offeringCandPos">-</span> &bull; <span id="offeringCandEmail">-</span>
                                    </div>
                                </div>
                                <div class="text-end flex-shrink-0" style="font-size: 11.5px">
                                    <div class="text-muted">Ekspektasi Gaji</div>
                                    <div class="fw-semibold text-danger" id="offeringCandExpSal">-</div>
                                </div>
                            </div>
                        </div>

                        <!-- Detail Penawaran — 1:1 from GAS offeringStep2 -->
                        <hr class="my-3" />
                        <p class="fw-semibold mb-3" style="font-size: 13px; color: var(--color-primary, #eb1c24)">
                            <i class="bi bi-pencil-square me-1"></i>Detail Penawaran Kerja
                        </p>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold" style="font-size: 12px">Branch Name <span
                                        class="text-danger">*</span></label>
                                <select class="form-select form-select-sm" id="offerBranchName">
                                    <option value="">— Pilih —</option>
                                    <option value="PT Mahakarya Sukses Indonesia">PT Mahakarya Sukses Indonesia</option>
                                    <option value="PT Stein Perkasa Internasional">PT Stein Perkasa Internasional</option>
                                    <option value="PT Perkasa Injeksi Indonesia">PT Perkasa Injeksi Indonesia</option>
                                    <option value="PT Mitra Elektro Perkasa">PT Mitra Elektro Perkasa</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold" style="font-size: 12px">Job Position <span
                                        class="text-danger">*</span></label>
                                <input type="text" class="form-control form-control-sm" id="offerPosition"
                                    placeholder="Contoh: HR Staff" />
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold" style="font-size: 12px">Division</label>
                                <select class="form-select form-select-sm" id="offerDivision">
                                    <option value="">— Pilih —</option>
                                    <option>RnD &amp; aftersales</option>
                                    <option>Commercial Division</option>
                                    <option>Sales</option>
                                    <option>FAT &amp; GA</option>
                                    <option>Manufacture</option>
                                    <option>E-Commerce</option>
                                    <option>IT</option>
                                    <option>Digital Marketing</option>
                                    <option>Buyer - Import</option>
                                    <option>Marketing</option>
                                    <option>Creative</option>
                                    <option>HR &amp; Legal</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold" style="font-size: 12px">Job Level</label>
                                <select class="form-select form-select-sm" id="offerJobLevel">
                                    <option value="">— Pilih —</option>
                                    <option>Associate</option>
                                    <option>Supervisor</option>
                                    <option>Manager</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold" style="font-size: 12px">Lokasi Kerja</label>
                                <input type="text" class="form-control form-control-sm" id="offerLokasiKerja"
                                    placeholder="Contoh: Jakarta" />
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold" style="font-size: 12px">Join Date <span
                                        class="text-danger">*</span></label>
                                <input type="date" class="form-control form-control-sm" id="offerJoinDate"
                                    value="{{ date('Y-m-d') }}" />
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold" style="font-size: 12px">Status Hubungan
                                    Kerja</label>
                                <select class="form-select form-select-sm" id="offerEmploymentStatus">
                                    <option value="Perjanjian Kerja Waktu Tertentu" selected>PKWT (Kontrak)</option>
                                    <option value="Perjanjian Kerja Waktu Tidak Tertentu">PKWTT (Tetap)</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold" style="font-size: 12px">Masa Kontrak</label>
                                <select class="form-select form-select-sm" id="offerContractDuration">
                                    <option value="3 bulan">3 bulan</option>
                                    <option value="6 bulan">6 bulan</option>
                                    <option value="12 bulan" selected>12 bulan</option>
                                    <option value="24 bulan">24 bulan</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold" style="font-size: 12px">Gaji Pokok (Rp) <span
                                        class="text-danger">*</span></label>
                                <input type="text" class="form-control form-control-sm" id="offerSalaryBasic"
                                    placeholder="Contoh: 4.000.000" />
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold" style="font-size: 12px">Tunjangan Pulsa (Rp)</label>
                                <input type="text" class="form-control form-control-sm" id="offerAllowPulsa"
                                    placeholder="Contoh: 100.000" />
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold" style="font-size: 12px">Tunjangan Transport
                                    (Rp)</label>
                                <input type="text" class="form-control form-control-sm" id="offerAllowTransport"
                                    placeholder="Contoh: 500.000" />
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold" style="font-size: 12px">Jam Kerja</label>
                                <select class="form-select form-select-sm" id="offerWorkingHoursPreset">
                                    <option value="">— Pilih pola jam kerja —</option>
                                    <option value="Senin - Jumat mulai pukul 08.00 - 17.00 WIB" selected>Kantor
                                        (Senin-Jumat 08.00-17.00 WIB)</option>
                                    <option value="Senin - Sabtu mulai pukul 08.00 - 16.30 WIB">Pabrik/Cabang (Senin-Sabtu
                                        08.00-16.30 WIB)</option>
                                    <option value="Shift sesuai penempatan">Shift sesuai penempatan</option>
                                    <option value="custom">Kustom (ketik manual)</option>
                                </select>
                                <input type="text" class="form-control form-control-sm mt-1" id="offerWorkingHours"
                                    value="Senin - Jumat mulai pukul 08.00 - 17.00 WIB" placeholder="Jam kerja..." />
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary btn-sm"
                        data-bs-dismiss="modal">Tutup</button>
                    <button type="button" class="btn btn-sm text-white fw-semibold"
                        style="background: var(--color-primary, #eb1c24)" id="btnSaveOffering">
                        <i class="bi bi-save2 me-1"></i>Simpan Offering
                    </button>
                </div>
            </div>
        </div>
    </div>
@endcan

{{--
  NOTE: Modal import karyawan (empImportModal) untuk halaman Employee sudah ada di entity-modals.blade.php.
  Modal di sini (spImportModal) adalah alias yang digunakan oleh tombol Import di halaman
  Recruitment / Master Data lain yang mungkin juga membutuhkan import karyawan.
  Semua elemen DOM menggunakan prefix "spImp" untuk menghindari collision dengan empImportModal.
  JS-nya meneruskan ke endpoint yang sama: hr.employees.import.preview & hr.employees.import
--}}
<div class="modal fade" id="importModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content" style="border-radius:16px">
            <div class="modal-header" style="background:#eb1c24;color:#fff;border-radius:16px 16px 0 0">
                <h6 class="modal-title mb-0 fw-bold"><i class="bi bi-upload me-2"></i>Import Karyawan (CSV / Excel)
                </h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">

                {{-- Step 1: File select --}}
                <div id="spImpStep1">
                    <p style="font-size:13px;color:var(--color-text-soft,#6b7280)">
                        Upload file <strong>CSV</strong> atau <strong>Excel (.xlsx/.xls)</strong>. Kolom
                        <strong>wajib</strong>: <code>Full Name</code>. Semua kolom lain opsional.
                    </p>
                    <div class="border rounded-3 p-4 text-center" id="spImpDropZone"
                        style="cursor:pointer;border-style:dashed!important;transition:background .2s;background:var(--color-bg,#f5f7fa)">
                        <i class="bi bi-file-earmark-spreadsheet fs-1 text-success"></i>
                        <p class="mb-1 mt-2 fw-semibold" style="font-size:14px">Klik atau seret file CSV / Excel ke
                            sini</p>
                        <p class="mb-0 text-muted" style="font-size:12px">.csv &bull; .xlsx &bull; .xls — maks 10 MB
                            &bull; maks 500 baris</p>
                        <input type="file" id="spImpFileInput" accept=".csv,.xlsx,.xls" class="d-none" />
                    </div>
                    <div class="mt-3 d-none" id="spImpFileInfo">
                        <div class="d-flex align-items-center gap-2 p-2 rounded"
                            style="background:var(--color-bg,#f5f7fa)">
                            <i class="bi bi-file-earmark-check fs-5 text-success"></i>
                            <div class="flex-grow-1">
                                <div class="fw-semibold" style="font-size:13px" id="spImpFileName">-</div>
                                <div class="text-muted" style="font-size:12px" id="spImpFileSize">-</div>
                            </div>
                            <button class="btn btn-sm btn-outline-danger" type="button" id="spImpClearBtn"><i
                                    class="bi bi-x"></i></button>
                        </div>
                    </div>
                </div>

                {{-- Step 2: Preview table --}}
                <div id="spImpStep2" class="d-none">
                    <div class="d-flex flex-wrap gap-2 align-items-center mb-3">
                        <span class="badge bg-secondary" style="font-size:13px" id="spImpBadgeTotal">Total: 0</span>
                        <span class="badge bg-success" style="font-size:13px" id="spImpBadgeNew">Baru: 0</span>
                        <span class="badge bg-warning text-dark" style="font-size:13px" id="spImpBadgeExist">Sudah
                            Ada: 0</span>
                        <span class="badge bg-danger" style="font-size:13px" id="spImpBadgeInvalid">Invalid: 0</span>
                        <span class="badge bg-info text-dark" style="font-size:13px" id="spImpBadgeDup">Duplikat
                            File: 0</span>
                    </div>
                    <div id="spImpPreviewLoading" class="text-center py-4 d-none">
                        <span class="spinner-border spinner-border-sm text-success me-2"></span>
                        <span style="font-size:13px">Memvalidasi data...</span>
                    </div>
                    <div class="table-responsive" id="spImpTableWrap" style="max-height:360px;overflow-y:auto">
                        <table class="table table-sm table-bordered align-middle mb-0" style="font-size:12px">
                            <thead class="table-light sticky-top">
                                <tr>
                                    <th style="width:40px">No</th>
                                    <th>ID Karyawan Import</th>
                                    <th>Nama Karyawan Import</th>
                                    <th>Departemen Karyawan</th>
                                    <th>Jabatan Karyawan</th>
                                    <th>Status Kontrak</th>
                                    <th>Tanggal Bergabung</th>
                                    <th style="width:160px">Hasil Validasi Import</th>
                                </tr>
                            </thead>
                            <tbody id="spImpPreviewBody"></tbody>
                        </table>
                    </div>
                    <div id="spImpNoNewAlert" class="alert alert-warning mt-3 d-none" style="font-size:13px">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>Tidak ada baris baru yang dapat diimport.
                    </div>
                </div>

                {{-- Step 3: Result --}}
                <div id="spImpStep3" class="d-none text-center py-3">
                    <i id="spImpResultIcon" class="bi bi-check-circle-fill fs-1 text-success"></i>
                    <h6 id="spImpResultTitle" class="mt-3 mb-1">Import Selesai</h6>
                    <p id="spImpResultMsg" class="text-muted" style="font-size:13px"></p>
                    <div id="spImpResultErrors" class="text-start d-none mt-3">
                        <div class="fw-semibold mb-1" style="font-size:12px;color:#991b1b"><i
                                class="bi bi-exclamation-circle me-1"></i>Baris yang dilewati:</div>
                        <ul id="spImpErrorList" class="list-unstyled mb-0"
                            style="font-size:12px;max-height:200px;overflow-y:auto;background:#fff5f5;padding:8px 12px;border-radius:8px;border:1px solid #fecaca">
                        </ul>
                    </div>
                </div>

            </div>
            <div class="modal-footer" id="spImpFooter">
                {{-- Step 1 --}}
                <div id="spImpFooter1" class="d-flex gap-2 w-100">
                    <a href="{{ route('hr.employees.import.template') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-download me-1"></i>Download Template CSV
                    </a>
                    <button type="button" class="btn btn-outline-secondary btn-sm ms-auto"
                        data-bs-dismiss="modal">Tutup</button>
                    <button type="button" class="btn btn-sm text-white fw-semibold" style="background:#eb1c24"
                        id="spImpPreviewBtn" disabled>
                        <span id="spImpPreviewText"><i class="bi bi-eye me-1"></i>Preview &amp; Validasi</span>
                        <span id="spImpPreviewLoad" class="d-none"><span
                                class="spinner-border spinner-border-sm me-1"></span>Memvalidasi...</span>
                    </button>
                </div>
                {{-- Step 2 --}}
                <div id="spImpFooter2" class="d-flex gap-2 w-100 d-none">
                    <button type="button" class="btn btn-outline-secondary btn-sm" id="spImpBackBtn"><i
                            class="bi bi-arrow-left me-1"></i>Kembali</button>
                    <button type="button" class="btn btn-outline-secondary btn-sm ms-auto"
                        data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-sm text-white fw-semibold" style="background:#eb1c24"
                        id="spImpConfirmBtn" disabled>
                        <span id="spImpImportText"><i class="bi bi-cloud-arrow-up-fill me-1"></i>Import <span
                                id="spImpNewCount">0</span> Karyawan Baru</span>
                        <span id="spImpImportLoad" class="d-none"><span
                                class="spinner-border spinner-border-sm me-1"></span>Mengimport...</span>
                    </button>
                </div>
                {{-- Step 3 --}}
                <div id="spImpFooter3" class="d-flex gap-2 w-100 d-none">
                    <button type="button" class="btn btn-sm text-white fw-semibold ms-auto"
                        style="background:#eb1c24" data-bs-dismiss="modal">
                        <i class="bi bi-check me-1"></i>Selesai
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>


<!-- Modal Ubah Status dari Hold/Accepted/Blacklist -->
<div class="modal fade" id="moveStatusModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 16px">
            <div class="modal-header">
                <h6 class="modal-title mb-0" id="moveStatusModalTitle">Ubah Status Kandidat</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="moveStatusRecruitmentId" value="" />
                <input type="hidden" id="moveStatusFromStatus" value="" />
                <div class="mb-3">
                    <label class="form-label fw-semibold" style="font-size: 13px">Pindahkan ke Status</label>
                    <select class="form-select" id="moveStatusTarget">
                        <option value="Hold">Hold</option>
                        <option value="Accepted">Accepted</option>
                        <option value="Blacklist">Blacklist</option>
                    </select>
                </div>
                <div class="mb-3" id="moveStatusReasonWrap">
                    <label class="form-label fw-semibold" style="font-size: 13px">Alasan <span
                            id="moveStatusReasonLabel">(opsional)</span></label>
                    <textarea class="form-control" id="moveStatusReason" rows="3"
                        placeholder="Masukkan alasan perubahan status..."></textarea>
                </div>
                <div class="mb-0">
                    <label class="form-label fw-semibold" style="font-size: 13px">Catatan HR (opsional)</label>
                    <textarea class="form-control" id="moveStatusHrNotes" rows="2" placeholder="Catatan tambahan..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                <button class="btn text-white" style="background: var(--color-primary)" id="btnConfirmMoveStatus">
                    <i class="bi bi-arrow-repeat me-1"></i>Simpan Perubahan
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Offering Letter Preview Modal -->
<div class="modal fade" id="offeringPreviewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <div class="modal-content" style="border-radius: 16px">
            <div class="modal-header" style="background: var(--color-primary); border-radius: 16px 16px 0 0">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-file-earmark-check-fill text-white fs-5"></i>
                    <h6 class="modal-title mb-0 text-white fw-bold">Preview Offering Letter</h6>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div id="prevOfferNoDataWarn" class="alert alert-warning d-none align-items-center gap-2 mb-3"
                    style="font-size: 13px">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    <span>Data detail offering tidak tersedia. Klik <strong>Edit</strong> untuk mengisi ulang.</span>
                </div>
                <div class="d-flex align-items-center gap-3 mb-4 p-3 rounded-3"
                    style="background: #f0f7ff; border: 1px solid #c7dff7">
                    <div class="avatar-sm d-flex align-items-center justify-content-center fw-bold text-white rounded-3 flex-shrink-0"
                        id="prevOfferCandAvatar"
                        style="width: 44px; height: 44px; font-size: 15px; background: var(--color-primary)">?</div>
                    <div class="flex-grow-1 min-w-0">
                        <div class="fw-bold text-truncate" id="prevOfferCandName" style="font-size: 15px">-</div>
                        <div class="text-muted text-truncate" id="prevOfferCandPos" style="font-size: 12px">-</div>
                        <div style="font-size: 11px; color: #999" id="prevOfferCandRid">-</div>
                    </div>
                    <div class="text-end flex-shrink-0" style="font-size: 11px; color: #666; line-height: 1.8">
                        <div>Dibuat: <strong id="prevOfferCreated">-</strong></div>
                        <div>Oleh: <strong id="prevOfferCreatedBy">-</strong></div>
                        <div>Diperbarui: <strong id="prevOfferUpdated">-</strong></div>
                    </div>
                </div>
                <p class="fw-semibold mb-2 mt-1"
                    style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.06em; color: var(--color-primary)">
                    <i class="bi bi-building me-1"></i>Penempatan
                </p>
                <div class="row g-2 mb-3" style="font-size: 13px">
                    <div class="col-md-6">
                        <div class="p-2 rounded-2" style="background: #f8fafc; border: 1px solid #e8edf2">
                            <div class="text-muted mb-1"
                                style="font-size: 10.5px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.04em">
                                Branch / Entitas</div>
                            <div class="fw-semibold text-truncate" id="prevOfferCompany">-</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="p-2 rounded-2" style="background: #f8fafc; border: 1px solid #e8edf2">
                            <div class="text-muted mb-1"
                                style="font-size: 10.5px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.04em">
                                Lokasi Kerja</div>
                            <div class="fw-semibold" id="prevOfferLokasiKerja">-</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="p-2 rounded-2" style="background: #f8fafc; border: 1px solid #e8edf2">
                            <div class="text-muted mb-1"
                                style="font-size: 10.5px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.04em">
                                Division</div>
                            <div class="fw-semibold" id="prevOfferDivision">-</div>
                        </div>
                    </div>
                </div>
                <p class="fw-semibold mb-2"
                    style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.06em; color: var(--color-primary)">
                    <i class="bi bi-person-badge me-1"></i>Jabatan &amp; Kontrak
                </p>
                <div class="row g-2 mb-3" style="font-size: 13px">
                    <div class="col-md-6">
                        <div class="p-2 rounded-2" style="background: #f8fafc; border: 1px solid #e8edf2">
                            <div class="text-muted mb-1"
                                style="font-size: 10.5px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.04em">
                                Job Position</div>
                            <div class="fw-semibold" id="prevOfferPosition">-</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="p-2 rounded-2" style="background: #f8fafc; border: 1px solid #e8edf2">
                            <div class="text-muted mb-1"
                                style="font-size: 10.5px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.04em">
                                Job Level</div>
                            <div class="fw-semibold" id="prevOfferJobLevel">-</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-2 rounded-2" style="background: #f8fafc; border: 1px solid #e8edf2">
                            <div class="text-muted mb-1"
                                style="font-size: 10.5px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.04em">
                                Status Kerja</div>
                            <div class="fw-semibold" id="prevOfferEmploymentStatus">-</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-2 rounded-2" style="background: #f8fafc; border: 1px solid #e8edf2">
                            <div class="text-muted mb-1"
                                style="font-size: 10.5px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.04em">
                                Masa Kontrak</div>
                            <div class="fw-semibold" id="prevOfferContractDuration">-</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-2 rounded-2" style="background: #f8fafc; border: 1px solid #e8edf2">
                            <div class="text-muted mb-1"
                                style="font-size: 10.5px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.04em">
                                Join Date</div>
                            <div class="fw-semibold" id="prevOfferJoinDate">-</div>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="p-2 rounded-2" style="background: #f8fafc; border: 1px solid #e8edf2">
                            <div class="text-muted mb-1"
                                style="font-size: 10.5px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.04em">
                                Jam Kerja</div>
                            <div class="fw-semibold" id="prevOfferWorkingHours">-</div>
                        </div>
                    </div>
                </div>
                <p class="fw-semibold mb-2"
                    style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.06em; color: var(--color-primary)">
                    <i class="bi bi-cash-stack me-1"></i>Kompensasi
                </p>
                <div class="row g-2 mb-3" style="font-size: 13px">
                    <div class="col-md-4">
                        <div class="p-2 rounded-2" style="background: #f8fafc; border: 1px solid #e8edf2">
                            <div class="text-muted mb-1"
                                style="font-size: 10.5px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.04em">
                                Gaji Pokok</div>
                            <div class="fw-bold" id="prevOfferSalaryBasic">-</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-2 rounded-2" style="background: #f8fafc; border: 1px solid #e8edf2">
                            <div class="text-muted mb-1"
                                style="font-size: 10.5px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.04em">
                                Tunj. Pulsa</div>
                            <div class="fw-semibold" id="prevOfferAllowPulsa">-</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-2 rounded-2" style="background: #f8fafc; border: 1px solid #e8edf2">
                            <div class="text-muted mb-1"
                                style="font-size: 10.5px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.04em">
                                Tunj. Transport</div>
                            <div class="fw-semibold" id="prevOfferAllowTransport">-</div>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="p-2 rounded-2 d-flex align-items-center justify-content-between"
                            style="border: 1px solid #ff8282">
                            <div class="text-muted fw-semibold"
                                style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.04em">Total
                                Penghasilan</div>
                            <div class="fw-bold" id="prevOfferSalary" style="font-size: 15px">-</div>
                        </div>
                    </div>
                </div>
                <p class="fw-semibold mb-2"
                    style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.06em; color: var(--color-primary)">
                    <i class="bi bi-sticky me-1"></i>Catatan
                </p>
                <div class="row g-2" style="background: #f8fafc; border: 1px solid #e8edf2; font-size: 13px">
                    <div class="col-12">
                        <div class="p-2 rounded-2">
                            <div class="text-muted mb-1"
                                style="font-size: 10.5px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.04em">
                                Catatan Tambahan</div>
                            <div id="prevOfferNotes" style="white-space: pre-wrap; font-size: 13px">-</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer" style="border-top: 1px solid #eee; gap: 8px">
                <button class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Tutup</button>
                <button class="btn btn-outline-primary btn-sm" id="btnEditOffering">
                    <i class="bi bi-pencil me-1"></i>Edit Offering
                </button>
                <a href="#" target="_blank" class="btn btn-sm text-white"
                    style="background: var(--color-primary)" id="btnDownloadOffering" disabled>
                    <i class="bi bi-download me-1"></i>Download PDF
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Offering Response Modal (1:1 from GAS partials/StatusPageModals.html) -->
<div class="modal fade" id="offeringResponseModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 16px">
            <div class="modal-header">
                <div>
                    <h6 class="modal-title mb-0 fw-bold">Update Respons Offering</h6>
                    <small class="text-muted" id="offerRespCandName">-</small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="offerRespRecruitmentId" />
                <input type="hidden" id="offerRespValue" value="" />

                <label class="form-label fw-semibold mb-2" style="font-size: 13px">Respons Kandidat</label>
                <div class="d-flex flex-column gap-2 mb-3" id="offerRespOptions">
                    <!-- Opsi: Menunggu -->
                    <div class="offer-resp-opt p-3 rounded-3" data-value="Menunggu"
                        style="border: 2px solid #e5e7eb; cursor: pointer; transition: border-color 0.15s, background 0.15s">
                        <div class="d-flex align-items-center gap-3">
                            <div class="offer-resp-dot"
                                style="width: 20px; height: 20px; border-radius: 50%; border: 2px solid #d1d5db; flex-shrink: 0; display: flex; align-items: center; justify-content: center; transition: border-color 0.15s, background 0.15s">
                                <div class="offer-resp-dot-inner"
                                    style="width: 8px; height: 8px; border-radius: 50%; background: transparent; transition: background 0.15s">
                                </div>
                            </div>
                            <div>
                                <div class="fw-semibold" style="font-size: 13px"><i
                                        class="bi bi-hourglass-split me-1" style="color: #8a6100"></i>Menunggu</div>
                                <div class="text-muted" style="font-size: 11px">Offering sudah dikirim, belum ada
                                    konfirmasi dari kandidat</div>
                            </div>
                        </div>
                    </div>

                    <!-- Opsi: Diterima -->
                    <div class="offer-resp-opt p-3 rounded-3" data-value="Diterima"
                        style="border: 2px solid #e5e7eb; cursor: pointer; transition: border-color 0.15s, background 0.15s">
                        <div class="d-flex align-items-center gap-3">
                            <div class="offer-resp-dot"
                                style="width: 20px; height: 20px; border-radius: 50%; border: 2px solid #d1d5db; flex-shrink: 0; display: flex; align-items: center; justify-content: center; transition: border-color 0.15s, background 0.15s">
                                <div class="offer-resp-dot-inner"
                                    style="width: 8px; height: 8px; border-radius: 50%; background: transparent; transition: background 0.15s">
                                </div>
                            </div>
                            <div>
                                <div class="fw-semibold" style="font-size: 13px"><i
                                        class="bi bi-check-circle-fill me-1" style="color: #eb1c24"></i>Diterima</div>
                                <div class="text-muted" style="font-size: 11px">Kandidat setuju dan siap bergabung
                                    sesuai tanggal yang ditentukan</div>
                            </div>
                        </div>
                    </div>

                    <!-- Opsi: Ditolak -->
                    <div class="offer-resp-opt p-3 rounded-3" data-value="Ditolak"
                        style="border: 2px solid #e5e7eb; cursor: pointer; transition: border-color 0.15s, background 0.15s">
                        <div class="d-flex align-items-center gap-3">
                            <div class="offer-resp-dot"
                                style="width: 20px; height: 20px; border-radius: 50%; border: 2px solid #d1d5db; flex-shrink: 0; display: flex; align-items: center; justify-content: center; transition: border-color 0.15s, background 0.15s">
                                <div class="offer-resp-dot-inner"
                                    style="width: 8px; height: 8px; border-radius: 50%; background: transparent; transition: background 0.15s">
                                </div>
                            </div>
                            <div>
                                <div class="fw-semibold" style="font-size: 13px"><i class="bi bi-x-circle-fill me-1"
                                        style="color: #991b1b"></i>Ditolak</div>
                                <div class="text-muted" style="font-size: 11px">Kandidat menolak tawaran — proses
                                    rekrutmen selesai</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mb-0">
                    <label class="form-label fw-semibold" style="font-size: 13px">Catatan <span
                            class="text-muted fw-normal">(opsional)</span></label>
                    <textarea class="form-control" id="offerRespNotes" rows="3"
                        placeholder="Contoh: Kandidat menolak karena gaji tidak sesuai, negosiasi gagal..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                <button class="btn text-white" style="background: var(--color-primary)" id="btnConfirmOfferResp"
                    disabled>
                    <i class="bi bi-check-lg me-1"></i>Simpan Respons
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    window.__allCandidatesForStatus = @json($allCandidates ?? ($candidates ?? []));

    // ============================================================
    // PROSES KONTRAK PKWT & ONBOARDING (1:1 from GAS js/statusPages.html)
    // Eligibility: Accepted + Offering Response "Diterima" + belum onboarding
    // ============================================================
    var _activeOnboardingRow = null;

    function _onbInitials(name) {
        if (!name) return '?';
        var p = String(name).trim().split(/\s+/);
        return ((p[0] || '')[0] + (p[1] ? p[1][0] : '')).toUpperCase();
    }

    // Angka → kata (Indonesia) untuk klausul jangka waktu
    function _onbNumToWords(n) {
        var ones = ['', 'satu', 'dua', 'tiga', 'empat', 'lima', 'enam', 'tujuh', 'delapan', 'sembilan',
            'sepuluh', 'sebelas', 'dua belas', 'tiga belas', 'empat belas', 'lima belas',
            'enam belas', 'tujuh belas', 'delapan belas', 'sembilan belas'
        ];
        var tens = ['', 'sepuluh', 'dua puluh', 'tiga puluh', 'empat puluh', 'lima puluh'];
        n = parseInt(n, 10) || 0;
        if (n < 20) return ones[n] || String(n);
        if (n < 60) {
            var t = Math.floor(n / 10),
                o = n % 10;
            return tens[t] + (o ? ' ' + ones[o] : '');
        }
        return String(n);
    }

    function _onbFmtDateLong(dstr) {
        if (!dstr) return '-';
        var d = new Date(dstr);
        if (isNaN(d.getTime())) return dstr;
        var bulan = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober',
            'November', 'Desember'
        ];
        return d.getDate() + ' ' + bulan[d.getMonth()] + ' ' + d.getFullYear();
    }

    // Pre-fill data dari Offering Letter (fallback ke field kandidat) — port GAS _normalizeOfferData
    function _onbNormalizeOffer(row) {
        row = row || {};
        var branchName = row.offeringCompanyEntity || row.offeringBranchName || '';
        return {
            branchName: branchName,
            division: row.offeringDivision || '',
            department: row.department || '',
            position: row.offeringPosition || row.positionApplied || '',
            jobLevel: row.offeringJobLevel || '',
            lokasiKerja: row.offeringLokasiKerja || row.city || '',
            salaryBasic: row.offeringSalaryBasic || row.offeringSalary || '',
            allowTransport: row.offeringAllowTransport || '',
            allowPulsa: row.offeringAllowPulsa || '',
            contractDuration: row.offeringContractDuration || '12 Bulan',
            joinDate: row.offeringJoinDate || '',
        };
    }

    function _onbSetSelectOrValue(el, value) {
        if (!el || value == null || value === '') return;
        var val = String(value);
        if (el.tagName === 'SELECT') {
            var exists = Array.prototype.some.call(el.options, function(o) {
                return o.value === val;
            });
            if (!exists) {
                var opt = document.createElement('option');
                opt.value = val;
                opt.textContent = val;
                el.appendChild(opt);
            }
        }
        el.value = val;
    }

    function _onbToDateInput(v) {
        if (!v) return '';
        var d = new Date(v);
        if (isNaN(d.getTime())) return '';
        return d.toISOString().substring(0, 10);
    }

    function handleOnboardingSearch(query) {
        const q = (query || '').toLowerCase().trim();
        const dropdown = document.getElementById('onboardingSearchDropdown');
        const clearBtn = document.getElementById('onboardingSearchClear');
        clearBtn.style.display = q ? 'block' : 'none';
        _activeOnboardingRow = null;
        _fillOnboardingCandPreview(null);
        _updateOnboardingConfirmBtn();

        if (!q) {
            dropdown.style.display = 'none';
            return;
        }

        // Filter: Accepted + Offering Diterima + belum onboarding (1:1 GAS)
        const matched = (window.__allCandidatesForStatus || []).filter(c => {
            const eligible = c.offeringResponse === 'Diterima';
            const notYet = !c.onboardingStatus || c.onboardingStatus === '' || c.onboardingStatus ===
                'Belum Onboarding';
            const name = (c.fullName || '').toLowerCase();
            const id = (c.recruitmentId || '').toLowerCase();
            return eligible && notYet && (name.includes(q) || id.includes(q));
        }).slice(0, 8);

        if (matched.length === 0) {
            dropdown.innerHTML =
                '<div class="px-3 py-2 text-muted" style="font-size:13px">Tidak ada kandidat yang memenuhi syarat (harus: Accepted + Offering Diterima + belum kontrak PKWT)</div>';
            dropdown.style.display = 'block';
            return;
        }

        dropdown.innerHTML = matched.map(function(c) {
            return '<div class="p-2 border-bottom d-flex align-items-center gap-2 hover-item onboarding-cand-item" style="cursor:pointer;" data-rec-id="' +
                (c.recruitmentId || '') + '">' +
                '<div class="avatar-sm" style="width:32px;height:32px;background:#eb1c24;color:#fff;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700">' +
                (c.fullName || 'C').substring(0, 2).toUpperCase() +
                '</div>' +
                '<div class="flex-grow-1" style="font-size:12.5px;">' +
                '<div class="fw-semibold text-navy">' + (c.fullName || '-') + '</div>' +
                '<div class="text-muted" style="font-size:11px">' + (c.positionApplied || '-') + ' &middot; ' +
                (c.recruitmentId || '') +
                ' &middot; <span style="color:#eb1c24">Offering Diterima</span></div>' +
                '</div></div>';
        }).join('');
        dropdown.querySelectorAll('.onboarding-cand-item').forEach(function(item) {
            item.addEventListener('click', function() {
                var recId = item.getAttribute('data-rec-id');
                var found = (window.__allCandidatesForStatus || []).find(function(x) {
                    return x.recruitmentId === recId;
                });
                if (found) selectOnboardingCandidate(found);
            });
        });
        dropdown.style.display = 'block';
    }

    function selectOnboardingCandidate(c) {
        _activeOnboardingRow = c;
        document.getElementById('onboardingSearchDropdown').style.display = 'none';
        document.getElementById('onboardingCandSearch').value = `${c.fullName} (${c.recruitmentId})`;
        document.getElementById('onboardingSearchClear').style.display = 'block';
        _fillOnboardingCandPreview(c);
        _updateOnboardingConfirmBtn();
    }

    function _fillOnboardingCandPreview(row) {
        var previewEl = document.getElementById('onboardingCandPreview');
        if (!row) {
            if (previewEl) previewEl.style.display = 'none';
            return;
        }
        if (previewEl) previewEl.style.display = 'block';

        document.getElementById('onboardingCandAvatar').textContent = _onbInitials(row.fullName);
        document.getElementById('onboardingCandName').textContent = row.fullName || '-';
        document.getElementById('onboardingCandPosition').textContent = row.positionApplied || '-';
        document.getElementById('onboardingCandEmail').textContent = row.email || '-';
        document.getElementById('onboardingCandRid').textContent = row.recruitmentId || '-';
        document.getElementById('onboardingCandEmpId').textContent = row.employeeId || '-';
        document.getElementById('onboardingRecruitmentId').value = row.recruitmentId || '';
        document.getElementById('onboardingEmployeeId').value = row.employeeId || '';

        // Pre-fill dari Offering Letter
        var offer = _onbNormalizeOffer(row);
        _onbSetSelectOrValue(document.getElementById('onbBranchName'), offer.branchName);
        _onbSetSelectOrValue(document.getElementById('onbDivision'), offer.division);
        _onbSetSelectOrValue(document.getElementById('onbDepartment'), offer.department);

        var posInput = document.getElementById('onbPosition');
        if (posInput) posInput.value = offer.position || '';

        var joinInput = document.getElementById('onbJoinDate');
        if (joinInput) {
            var normJoin = _onbToDateInput(offer.joinDate);
            joinInput.value = normJoin || joinInput.value || new Date().toISOString().substring(0, 10);
        }

        var durSel = document.getElementById('onbContractDuration');
        if (durSel && offer.contractDuration) _onbSetSelectOrValue(durSel, offer.contractDuration);

        var docDateEl = document.getElementById('onbDocDate');
        if (docDateEl && !docDateEl.value) docDateEl.value = new Date().toISOString().substring(0, 10);

        var jamEl = document.getElementById('onbJamMasuk');
        if (jamEl && !jamEl.value) jamEl.value =
            'mulai pukul 07.00 WIB dan selambat-lambatnya sampai dengan pukul 07.15 WIB';

        var wsEl = document.getElementById('onbWorkSchedule');
        if (wsEl && !wsEl.value) wsEl.value = 'Normal';

        var salInput = document.getElementById('onbSalaryBasic');
        if (salInput) salInput.value = offer.salaryBasic ? String(offer.salaryBasic).replace(/[^\d]/g, '') : '';
        var allowInput = document.getElementById('onbSalaryAllowance');
        if (allowInput) allowInput.value = offer.allowTransport || offer.allowPulsa || '';

        _calcOnboardingEndDate();

        // Klausul jangka waktu — di-generate setelah end date terhitung
        var tenorEl = document.getElementById('onbTenorText');
        if (tenorEl && !tenorEl.value) {
            var joinV = (document.getElementById('onbJoinDate') || {}).value || '';
            var durV = (durSel && durSel.value) || '12 Bulan';
            var endV = (document.getElementById('onbContractEnd') || {}).value || '';
            var monthsNum = 12;
            var mm = String(durV).match(/^(\d+)\s*bulan/i);
            var yy = String(durV).match(/^(\d+)\s*tahun/i);
            if (mm) monthsNum = parseInt(mm[1], 10);
            else if (yy) monthsNum = parseInt(yy[1], 10) * 12;
            tenorEl.value =
                'PIHAK PERTAMA dengan ini menyatakan persetujuannya untuk mempekerjakan PIHAK KEDUA sebagai Karyawan PIHAK PERTAMA dengan jangka waktu ' +
                monthsNum + ' (' + _onbNumToWords(monthsNum) + ') bulan terhitung sejak tanggal ' + _onbFmtDateLong(
                    joinV) +
                ' sampai dengan tanggal ' + _onbFmtDateLong(endV) + '.';
        }

        setTimeout(_updateOnboardingConfirmBtn, 0);
    }

    function _calcOnboardingEndDate() {
        var startEl = document.getElementById('onbJoinDate');
        var durationEl = document.getElementById('onbContractDuration');
        var endEl = document.getElementById('onbContractEnd');
        var hintEl = document.getElementById('onbContractEndHint');
        if (!startEl || !durationEl || !endEl || !startEl.value || !durationEl.value) return;
        var months = 12;
        var durVal = durationEl.value.toLowerCase();
        var monthMatch = durVal.match(/^(\d+)\s*bulan/i);
        var yearMatch = durVal.match(/^(\d+)\s*tahun/i);
        if (monthMatch) months = parseInt(monthMatch[1], 10);
        else if (yearMatch) months = parseInt(yearMatch[1], 10) * 12;

        var startDate = new Date(startEl.value);
        startDate.setMonth(startDate.getMonth() + months);
        startDate.setDate(startDate.getDate() - 1); // akhir periode kontrak
        var yyyy = startDate.getFullYear();
        var mm = String(startDate.getMonth() + 1).padStart(2, '0');
        var dd = String(startDate.getDate()).padStart(2, '0');
        endEl.value = yyyy + '-' + mm + '-' + dd;
        if (hintEl) hintEl.innerText = 'Auto-hitung dari durasi ' + durationEl.value;
    }

    function _updateOnboardingConfirmBtn() {
        var confirmBtn = document.getElementById('btnConfirmOnboarding');
        if (!confirmBtn) return;
        var hasCand = !!_activeOnboardingRow;
        var hasBranch = !!((document.getElementById('onbBranchName') || {}).value);
        var hasDept = !!((document.getElementById('onbDepartment') || {}).value);
        var hasPos = !!((document.getElementById('onbPosition') || {}).value);
        var hasDuration = !!((document.getElementById('onbContractDuration') || {}).value);
        var hasJoin = !!((document.getElementById('onbJoinDate') || {}).value);
        confirmBtn.disabled = !(hasCand && hasBranch && hasDept && hasPos && hasDuration && hasJoin);
    }

    function clearOnboardingSearch() {
        document.getElementById('onboardingCandSearch').value = '';
        document.getElementById('onboardingSearchDropdown').style.display = 'none';
        document.getElementById('onboardingSearchClear').style.display = 'none';
        document.getElementById('onboardingCandPreview').style.display = 'none';
        _activeOnboardingRow = null;
        _updateOnboardingConfirmBtn();
    }

    function handleOfferingSearch(query) {
        const q = (query || '').toLowerCase().trim();
        const dropdown = document.getElementById('offeringSearchDropdown');
        const clearBtn = document.getElementById('offeringSearchClear');
        clearBtn.style.display = q ? 'block' : 'none';

        if (!q) {
            dropdown.style.display = 'none';
            return;
        }

        const matched = (window.__allCandidatesForStatus || []).filter(c => {
            // Kandidat yang sudah memiliki offering tidak boleh dibuatkan offering baru —
            // revisi dilakukan lewat tombol Edit (preview offering). (1:1 GAS)
            const hasOffer = c.offeringCreated && c.offeringCreated !== '-' && c.offeringCreated !== '';
            if (hasOffer) return false;
            const name = (c.fullName || '').toLowerCase();
            const id = (c.recruitmentId || '').toLowerCase();
            return name.includes(q) || id.includes(q);
        }).slice(0, 8);

        if (matched.length === 0) {
            dropdown.innerHTML =
                '<div class="p-3 text-muted text-center" style="font-size:13px">Tidak ada kandidat ditemukan</div>';
            dropdown.style.display = 'block';
            return;
        }

        dropdown.innerHTML = matched.map(function(c) {
            return '<div class="p-2 border-bottom d-flex align-items-center gap-2 hover-item offering-cand-item" style="cursor:pointer;" data-rec-id="' +
                (c.recruitmentId || '') + '">' +
                '<div class="avatar-sm" style="width:32px;height:32px;background:var(--color-primary);color:#fff;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700">' +
                (c.fullName || 'C').substring(0, 2).toUpperCase() +
                '</div>' +
                '<div class="flex-grow-1" style="font-size:12.5px;">' +
                '<div class="fw-semibold text-navy">' + (c.fullName || '-') + '</div>' +
                '<div class="text-muted" style="font-size:11px">' + (c.recruitmentId || '') + ' &bull; ' + (c
                    .positionApplied || '-') + '</div>' +
                '</div></div>';
        }).join('');
        dropdown.querySelectorAll('.offering-cand-item').forEach(function(item) {
            item.addEventListener('click', function() {
                var recId = item.getAttribute('data-rec-id');
                var found = (window.__allCandidatesForStatus || []).find(function(x) {
                    return x.recruitmentId === recId;
                });
                if (found) selectOfferingCandidate(found);
            });
        });
        dropdown.style.display = 'block';
    }

    function selectOfferingCandidate(c) {
        document.getElementById('offeringSearchDropdown').style.display = 'none';
        document.getElementById('offeringCandSearch').value = `${c.fullName} (${c.recruitmentId})`;

        document.getElementById('offeringCandName').textContent = c.fullName || '-';
        document.getElementById('offeringCandPos').textContent = c.positionApplied || '-';
        document.getElementById('offeringCandEmail').textContent = c.email || '-';
        document.getElementById('offeringCandExpSal').textContent = c.expectedSalary || '-';
        document.getElementById('offeringCandAvatar').textContent = (c.fullName || 'C').substring(0, 2).toUpperCase();

        if (document.getElementById('offerPosition')) document.getElementById('offerPosition').value = c
            .positionApplied || '';

        document.getElementById('offeringCandPreview').style.display = 'block';
    }

    function clearOfferingSearch() {
        document.getElementById('offeringCandSearch').value = '';
        document.getElementById('offeringSearchDropdown').style.display = 'none';
        document.getElementById('offeringSearchClear').style.display = 'none';
        document.getElementById('offeringCandPreview').style.display = 'none';
    }

    // ============================================================
    // Offering Letter: working hours preset
    // ============================================================
    function applyWorkingHoursPreset(sel) {
        const el = document.getElementById('offerWorkingHours');
        if (!el) return;
        if (sel.value !== 'custom') el.value = sel.value;
        else el.value = '';
        if (sel.value !== 'custom') el.readOnly = true;
        else {
            el.readOnly = false;
            el.focus();
        }
    }

    // ============================================================
    // spImportModal — Import CSV/Excel (status-page-modals instance)
    // Shares the same backend endpoints as empImportModal in entity-modals.blade.php.
    // All DOM IDs use "spImp" prefix to avoid collision.
    // ============================================================
    (function() {
        'use strict';
        var _spFile = null,
            _spParsed = [],
            _spPreview = [],
            _spNewCount = 0;

        function spEl(id) {
            return document.getElementById(id);
        }

        function spShow(id) {
            var e = spEl(id);
            if (e) e.classList.remove('d-none');
        }

        function spHide(id) {
            var e = spEl(id);
            if (e) e.classList.add('d-none');
        }

        function spText(id, t) {
            var e = spEl(id);
            if (e) e.textContent = t;
        }

        function spEsc(s) {
            return String(s || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g,
                '&quot;');
        }

        function csrfToken() {
            var m = document.querySelector('meta[name="csrf-token"]');
            return m ? m.content : '';
        }

        function spShowStep(n) {
            [1, 2, 3].forEach(function(s) {
                var b = spEl('spImpStep' + s),
                    f = spEl('spImpFooter' + s);
                if (b) {
                    if (s === n) b.classList.remove('d-none');
                    else b.classList.add('d-none');
                }
                if (f) {
                    if (s === n) f.classList.remove('d-none');
                    else f.classList.add('d-none');
                }
            });
        }

        function spReset() {
            _spFile = null;
            _spParsed = [];
            _spPreview = [];
            _spNewCount = 0;
            var fi = spEl('spImpFileInput');
            if (fi) fi.value = '';
            spHide('spImpFileInfo');
            spShow('spImpDropZone');
            var pb = spEl('spImpPreviewBtn');
            if (pb) pb.disabled = true;
            var tbody = spEl('spImpPreviewBody');
            if (tbody) tbody.innerHTML = '';
            spHide('spImpNoNewAlert');
            spHide('spImpResultErrors');
            spShowStep(1);
        }

        // Re-use field map + CSV/Excel parsers from empImportModal's IIFE
        // (they are defined in entity-modals.blade.php as local vars inside IIFE,
        //  so we replicate the minimal subset needed here)
        var SP_FIELD_MAP = {
            'fullname': 'fullName',
            'namalengkap': 'fullName',
            'nama': 'fullName',
            'name': 'fullName',
            'employeename': 'fullName',
            'namakaryawan': 'fullName',
            'karyawan': 'fullName',
            'employeeid': 'employeeId',
            'idkaryawan': 'employeeId',
            'nik': 'nik',
            'niknpwp16digit': 'nik',
            'niknpwp': 'nik',
            'nomorinduk': 'nik',
            'ktp': 'nik',
            'npwp': 'npwp',
            'tanggallahir': 'birthDate',
            'birthdate': 'birthDate',
            'tempatlahir': 'birthPlace',
            'birthplace': 'birthPlace',
            'jeniskelamin': 'gender',
            'gender': 'gender',
            'agama': 'religion',
            'religion': 'religion',
            'statuspernikahan': 'maritalStatus',
            'maritalstatus': 'maritalStatus',
            'alamatktp': 'citizenIdAddress',
            'citizenidaddress': 'citizenIdAddress',
            'address': 'citizenIdAddress',
            'alamat': 'citizenIdAddress',
            'alamatdomisili': 'residentialAddress',
            'residentialaddress': 'residentialAddress',
            'nohp': 'mobilePhone',
            'mobilephone': 'mobilePhone',
            'hp': 'mobilePhone',
            'phone': 'mobilePhone',
            'emailpribadi': 'personalEmail',
            'personalemail': 'personalEmail',
            'email': 'personalEmail',
            'emailkantor': 'workingEmail',
            'workingemail': 'workingEmail',
            'namabank': 'bankName',
            'bankname': 'bankName',
            'bank': 'bankName',
            'nomorrekening': 'bankAccount',
            'bankaccount': 'bankAccount',
            'rekening': 'bankAccount',
            'bpjsketenagakerjaan': 'bpjsKetenagakerjaan',
            'bpjstk': 'bpjsKetenagakerjaan',
            'bpjskesehatan': 'bpjsKesehatan',
            'bpjskes': 'bpjsKesehatan',
            'cabang': 'branchName',
            'branchname': 'branchName',
            'branch': 'branchName',
            'divisi': 'division',
            'division': 'division',
            'departemen': 'department',
            'department': 'department',
            'dept': 'department',
            'bagian': 'department',
            'jabatan': 'positionCurrent',
            'jobpositionlocation': 'positionCurrent',
            'positioncurrent': 'positionCurrent',
            'posisi': 'positionCurrent',
            'jobposition': 'positionNoLocCurrent',
            'position': 'positionNoLocCurrent',
            'joblevel': 'jobLevel',
            'level': 'jobLevel',
            'grade': 'grade',
            'areakerja': 'areaKerja',
            'lokasikerja': 'lokasiKerja',
            'city': 'lokasiKerja',
            'statuskaryawan': 'statusEmployee',
            'statusemployee': 'statusEmployee',
            'status': 'statusEmployee',
            'employmentstatus': 'statusEmployee',
            'tanggalmasuk': 'joinDate',
            'joindate': 'joinDate',
            'akhirkontrak': 'endDateContract',
            'enddatecontract': 'endDateContract',
            'outsourcevendor': 'outsourceVendor',
            'vendor': 'outsourceVendor',
            'catatan': 'hrNotes',
            'notes': 'hrNotes',
            'hrnotes': 'hrNotes',
        };

        function spNorm(h) {
            return String(h || '').trim().replace(/^["']|["']$/g, '').toLowerCase().replace(/[\s_\-\/\.\(\)]/g, '');
        }

        function spRowsFromMatrix(matrix) {
            if (!matrix || matrix.length < 2) return [];
            var hdrs = (matrix[0] || []).map(spNorm);
            var rows = [];
            for (var i = 1; i < matrix.length; i++) {
                var vals = matrix[i] || [];
                if (!vals.some(function(v) {
                        return v !== '' && v !== null && v !== undefined;
                    })) continue;
                var row = {};
                for (var j = 0; j < hdrs.length; j++) {
                    var key = SP_FIELD_MAP[hdrs[j]] || hdrs[j];
                    if (!key) continue;
                    row[key] = String(vals[j] == null ? '' : vals[j]).trim().replace(/^["']|["']$/g, '');
                }
                rows.push(row);
            }
            return rows;
        }

        function spDetectDelim(lines) {
            var f = lines[0] || '';
            var c = (f.match(/,/g) || []).length,
                s = (f.match(/;/g) || []).length,
                t = (f.match(/\t/g) || []).length;
            if (s > c && s > t) return ';';
            if (t > c && t > s) return '\t';
            return ',';
        }

        function spSplitLine(line, d) {
            var res = [],
                cur = '',
                inQ = false;
            for (var i = 0; i < line.length; i++) {
                var ch = line[i];
                if (ch === '"') {
                    if (inQ && line[i + 1] === '"') {
                        cur += '"';
                        i++;
                    } else {
                        inQ = !inQ;
                    }
                } else if (ch === d && !inQ) {
                    res.push(cur);
                    cur = '';
                } else {
                    cur += ch;
                }
            }
            res.push(cur);
            return res;
        }

        function spParseCSV(text) {
            text = (text || '').replace(/^\uFEFF/, '');
            var lines = text.split(/\r?\n/).filter(function(l) {
                return l.trim() !== '';
            });
            if (lines.length < 2) return [];
            var d = spDetectDelim(lines);
            return spRowsFromMatrix(lines.map(function(l) {
                return spSplitLine(l, d);
            }));
        }

        function spParseExcel(file, cb) {
            if (typeof XLSX === 'undefined') {
                var s = document.createElement('script');
                s.src = 'https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js';
                document.head.appendChild(s);
                s.onload = function() {
                    spParseExcel(file, cb);
                };
                s.onerror = function() {
                    if (cb) cb([]);
                };
                return;
            }
            var fr = new FileReader();
            fr.onload = function(e) {
                try {
                    var wb = XLSX.read(new Uint8Array(e.target.result), {
                        type: 'array',
                        raw: false
                    });
                    var sn = wb.SheetNames[0];
                    var ws = wb.Sheets[sn];
                    if (!ws) {
                        if (cb) cb([]);
                        return;
                    }
                    var mx = XLSX.utils.sheet_to_json(ws, {
                        header: 1,
                        raw: false,
                        defval: ''
                    });
                    mx = mx.map(function(row) {
                        return row.map(function(c) {
                            return String(c == null ? '' : c).replace(/^'+/, '');
                        });
                    });
                    if (cb) cb(spRowsFromMatrix(mx));
                } catch (err) {
                    if (typeof showToast === 'function') showToast('Gagal membaca Excel: ' + err.message,
                        'error');
                    if (cb) cb([]);
                }
            };
            fr.onerror = function() {
                if (cb) cb([]);
            };
            fr.readAsArrayBuffer(file);
        }

        function spHandleFile(file) {
            if (!file) return;
            var name = (file.name || '').toLowerCase();
            if (!name.endsWith('.csv') && !name.endsWith('.xlsx') && !name.endsWith('.xls')) {
                if (typeof showToast === 'function') showToast('Hanya file CSV atau Excel yang didukung.', 'error');
                return;
            }
            if (file.size > 10 * 1024 * 1024) {
                if (typeof showToast === 'function') showToast('Ukuran file maksimal 10 MB.', 'error');
                return;
            }
            _spFile = file;
            spText('spImpFileName', file.name);
            spText('spImpFileSize', (file.size / 1024).toFixed(1) + ' KB');
            spHide('spImpDropZone');
            spShow('spImpFileInfo');
            if (name.endsWith('.csv')) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    try {
                        _spParsed = spParseCSV(e.target.result);
                        spAfterParse();
                    } catch (err) {
                        if (typeof showToast === 'function') showToast('Gagal proses CSV: ' + err.message,
                            'error');
                        spReset();
                    }
                };
                reader.onerror = function() {
                    if (typeof showToast === 'function') showToast('Gagal membaca file CSV.', 'error');
                    spReset();
                };
                reader.readAsText(file, 'UTF-8');
            } else {
                spParseExcel(file, function(rows) {
                    _spParsed = rows;
                    spAfterParse();
                });
            }
        }

        function spAfterParse() {
            if (!_spParsed || _spParsed.length === 0) {
                if (typeof showToast === 'function') showToast('File kosong atau format tidak dikenali.', 'error');
                spReset();
                return;
            }
            if (_spParsed.length > 500) {
                if (typeof showToast === 'function') showToast('Maksimal 500 baris. File memiliki ' + _spParsed
                    .length + ' baris.', 'error');
                spReset();
                return;
            }
            var pb = spEl('spImpPreviewBtn');
            if (pb) pb.disabled = false;
        }

        function spRunPreview() {
            var pb = spEl('spImpPreviewBtn'),
                pt = spEl('spImpPreviewText'),
                pl = spEl('spImpPreviewLoad');
            if (pb) pb.disabled = true;
            if (pt) pt.classList.add('d-none');
            if (pl) pl.classList.remove('d-none');
            spShowStep(2);
            spShow('spImpPreviewLoading');
            spHide('spImpTableWrap');
            fetch('{{ route('hr.employees.import.preview') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': csrfToken()
                    },
                    body: JSON.stringify({
                        employees: _spParsed
                    })
                })
                .then(function(r) {
                    return r.json();
                })
                .then(function(res) {
                    if (pb) pb.disabled = false;
                    if (pt) pt.classList.remove('d-none');
                    if (pl) pl.classList.add('d-none');
                    spHide('spImpPreviewLoading');
                    spShow('spImpTableWrap');
                    if (!res || !res.success) {
                        if (typeof showToast === 'function') showToast('Preview gagal: ' + ((res && res
                            .message) || 'Error'), 'error');
                        spShowStep(1);
                        return;
                    }
                    _spPreview = res.rows || [];
                    _spNewCount = res.new || 0;
                    spText('spImpBadgeTotal', 'Total: ' + (res.total || 0));
                    spText('spImpBadgeNew', 'Baru: ' + (res.new || 0));
                    spText('spImpBadgeExist', 'Sudah Ada: ' + (res.existing || 0));
                    spText('spImpBadgeInvalid', 'Invalid: ' + (res.invalid || 0));
                    spText('spImpBadgeDup', 'Duplikat File: ' + (res.duplicate_internal || 0));
                    var tbody = spEl('spImpPreviewBody');
                    if (tbody) {
                        tbody.innerHTML = _spPreview.map(function(r) {
                            var sc, sl, rc;
                            if (r.status === 'new') {
                                sc = 'bg-success';
                                sl = 'BARU';
                                rc = '';
                            } else if (r.status === 'duplicate_existing') {
                                sc = 'bg-warning text-dark';
                                sl = 'SUDAH ADA';
                                rc = 'table-warning';
                            } else if (r.status === 'duplicate_internal') {
                                sc = 'bg-info text-dark';
                                sl = 'DUPLIKAT';
                                rc = 'table-info';
                            } else {
                                sc = 'bg-danger';
                                sl = 'INVALID';
                                rc = 'table-danger';
                            }
                            var iss = (r.issues && r.issues.length) ?
                                '<br><span style="font-size:10px;color:#888">' + spEsc(r.issues.join(
                                    ' | ')) + '</span>' : '';
                            return '<tr class="' + rc + '"><td>' + r.row +
                                '</td><td class="id-mono" style="font-size:11px">' + spEsc(r
                                    .employeeId || '(auto)') + '</td><td class="fw-semibold">' + spEsc(r
                                    .fullName || '-') + '</td><td style="font-size:11px">' + spEsc(r
                                    .department || '-') + '</td><td style="font-size:11px">' + spEsc(r
                                    .jobPosition || '-') + '</td><td style="font-size:11px">' + spEsc(r
                                    .statusEmployee || 'Contract') +
                                '</td><td style="font-size:11px">' + spEsc(r.joinDate || '-') +
                                '</td><td><span class="badge ' + sc + '" style="font-size:10px">' + sl +
                                '</span>' + iss + '</td></tr>';
                        }).join('');
                    }
                    var cb = spEl('spImpConfirmBtn');
                    if (cb) cb.disabled = (_spNewCount === 0);
                    spText('spImpNewCount', _spNewCount);
                    if (_spNewCount === 0) {
                        spShow('spImpNoNewAlert');
                    } else {
                        spHide('spImpNoNewAlert');
                    }
                })
                .catch(function(err) {
                    if (pb) pb.disabled = false;
                    if (pt) pt.classList.remove('d-none');
                    if (pl) pl.classList.add('d-none');
                    spHide('spImpPreviewLoading');
                    if (typeof showToast === 'function') showToast('Error: ' + (err ? err.message :
                        'Network error'), 'error');
                    spShowStep(1);
                });
        }

        function spRunImport() {
            var newRows = (_spPreview || []).filter(function(r) {
                return r.status === 'new';
            }).map(function(r) {
                return _spParsed[r.row - 1] || {};
            });
            if (!newRows.length) {
                if (typeof showToast === 'function') showToast('Tidak ada data baru.', 'error');
                return;
            }
            var cb = spEl('spImpConfirmBtn'),
                it = spEl('spImpImportText'),
                il = spEl('spImpImportLoad'),
                bb = spEl('spImpBackBtn');
            if (cb) cb.disabled = true;
            if (it) it.classList.add('d-none');
            if (il) il.classList.remove('d-none');
            if (bb) bb.disabled = true;
            fetch('{{ route('hr.employees.import') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': csrfToken()
                    },
                    body: JSON.stringify({
                        employees: newRows
                    })
                })
                .then(function(r) {
                    return r.json();
                })
                .then(function(res) {
                    if (it) it.classList.remove('d-none');
                    if (il) il.classList.add('d-none');
                    if (bb) bb.disabled = false;
                    spShowStep(3);
                    var icon = spEl('spImpResultIcon'),
                        title = spEl('spImpResultTitle'),
                        msg = spEl('spImpResultMsg');
                    if (res && res.success) {
                        if (icon) icon.className = 'bi bi-check-circle-fill fs-1 text-success';
                        if (title) {
                            title.textContent = 'Import Berhasil!';
                            title.className = 'mt-3 mb-1 text-success';
                        }
                        if (typeof showToast === 'function') showToast(res.message || 'Import selesai.',
                            'success', 6000);
                    } else {
                        if (icon) icon.className = 'bi bi-exclamation-triangle-fill fs-1 text-warning';
                        if (title) title.textContent = 'Import Selesai dengan Catatan';
                        if (typeof showToast === 'function') showToast((res && res.message) ||
                            'Import selesai dengan catatan.', 'warning', 6000);
                    }
                    var imported = (res && res.imported) ? res.imported : 0;
                    var errors = (res && res.errors && res.errors.length) ? res.errors : [];
                    if (msg) msg.textContent = 'Berhasil diimport: ' + imported + ' karyawan.' + (errors
                        .length ? ' ' + errors.length + ' baris dilewati.' : '');
                    if (errors.length) {
                        spShow('spImpResultErrors');
                        var el = spEl('spImpErrorList');
                        if (el) el.innerHTML = errors.map(function(e) {
                            return '<li class="py-1 border-bottom"><i class="bi bi-x-circle text-danger me-1"></i>' +
                                spEsc(e) + '</li>';
                        }).join('');
                    }
                    setTimeout(function() {
                        window.location.reload();
                    }, 2500);
                })
                .catch(function(err) {
                    if (it) it.classList.remove('d-none');
                    if (il) il.classList.add('d-none');
                    if (cb) cb.disabled = false;
                    if (bb) bb.disabled = false;
                    if (typeof showToast === 'function') showToast('Error: ' + (err ? err.message :
                        'Network error'), 'error');
                });
        }

        document.addEventListener('DOMContentLoaded', function() {
            var modal = document.getElementById('importModal');
            var dz = spEl('spImpDropZone'),
                fi = spEl('spImpFileInput'),
                clr = spEl('spImpClearBtn');
            var pb = spEl('spImpPreviewBtn'),
                bb = spEl('spImpBackBtn'),
                cb = spEl('spImpConfirmBtn');
            if (modal) modal.addEventListener('hidden.bs.modal', spReset);
            if (dz) {
                dz.addEventListener('click', function() {
                    if (fi) fi.click();
                });
                dz.addEventListener('dragover', function(e) {
                    e.preventDefault();
                    dz.style.background = '#e8f5e9';
                });
                dz.addEventListener('dragleave', function() {
                    dz.style.background = '';
                });
                dz.addEventListener('drop', function(e) {
                    e.preventDefault();
                    dz.style.background = '';
                    if (e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files.length)
                        spHandleFile(e.dataTransfer.files[0]);
                });
            }
            if (fi) fi.addEventListener('change', function() {
                if (fi.files && fi.files.length) spHandleFile(fi.files[0]);
            });
            if (clr) clr.addEventListener('click', function(e) {
                e.stopPropagation();
                spReset();
            });
            if (pb) pb.addEventListener('click', function() {
                if (_spParsed && _spParsed.length) spRunPreview();
            });
            if (bb) bb.addEventListener('click', function() {
                _spPreview = [];
                _spNewCount = 0;
                spShowStep(1);
            });
            if (cb) cb.addEventListener('click', function() {
                if (_spNewCount > 0) spRunImport();
            });
        });
    })();

    function openMoveStatusModal(recruitmentId, fromStatus) {
        var idEl = document.getElementById('moveStatusRecruitmentId');
        var fromEl = document.getElementById('moveStatusFromStatus');
        var titleEl = document.getElementById('moveStatusModalTitle');
        var targetEl = document.getElementById('moveStatusTarget');
        var reasonEl = document.getElementById('moveStatusReason');
        var notesEl = document.getElementById('moveStatusHrNotes');
        if (idEl) idEl.value = recruitmentId;
        if (fromEl) fromEl.value = fromStatus;
        if (titleEl) titleEl.innerText = 'Ubah Status: ' + recruitmentId;
        if (reasonEl) reasonEl.value = '';
        if (notesEl) notesEl.value = '';
        if (targetEl) {
            Array.from(targetEl.options).forEach(function(opt) {
                opt.disabled = opt.value === fromStatus;
            });
            var firstEnabled = Array.from(targetEl.options).find(function(o) {
                return !o.disabled;
            });
            if (firstEnabled) targetEl.value = firstEnabled.value;
        }
        var modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('moveStatusModal'));
        modal.show();
    }

    document.addEventListener('DOMContentLoaded', function() {
        var confirmBtn = document.getElementById('btnConfirmMoveStatus');
        if (!confirmBtn) return;
        confirmBtn.addEventListener('click', function() {
            var recruitmentId = (document.getElementById('moveStatusRecruitmentId') || {}).value;
            var fromStatus = (document.getElementById('moveStatusFromStatus') || {}).value;
            var toStatus = (document.getElementById('moveStatusTarget') || {}).value;
            var reason = (document.getElementById('moveStatusReason') || {}).value;
            var hrNotes = (document.getElementById('moveStatusHrNotes') || {}).value;
            if (!recruitmentId || !fromStatus || !toStatus) return;
            confirmBtn.disabled = true;
            fetch('/hr/recruitment/' + recruitmentId + '/move-status', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': getCsrfToken(),
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        from_status: fromStatus,
                        to_status: toStatus,
                        reason: reason,
                        hr_notes: hrNotes
                    })
                })
                .then(function(res) {
                    return res.json();
                })
                .then(function(result) {
                    confirmBtn.disabled = false;
                    var modal = bootstrap.Modal.getInstance(document.getElementById(
                        'moveStatusModal'));
                    if (modal) modal.hide();
                    if (result && result.success) {
                        if (typeof showToast === 'function') showToast(
                            'Status berhasil diubah ke ' + toStatus + '.', 'success');
                        location.reload();
                    } else {
                        if (typeof showToast === 'function') showToast('Gagal: ' + (result ? result
                            .message : 'Unknown error'), 'error');
                    }
                })
                .catch(function(err) {
                    confirmBtn.disabled = false;
                    if (typeof showToast === 'function') showToast('Error: ' + err.message,
                        'error');
                });
        });
    });

    function openOfferingPreviewModal(recruitmentId) {
        fetch('/hr/recruitment/' + recruitmentId + '/json?preview=1', {
                headers: {
                    'X-CSRF-TOKEN': getCsrfToken(),
                    'Accept': 'application/json'
                }
            })
            .then(function(res) {
                return res.json();
            })
            .then(function(data) {
                if (!data.success) return;
                var row = data.candidate;
                var avatarEl = document.getElementById('prevOfferCandAvatar');
                var nameEl = document.getElementById('prevOfferCandName');
                var posEl = document.getElementById('prevOfferCandPos');
                var ridEl = document.getElementById('prevOfferCandRid');
                var createdEl = document.getElementById('prevOfferCreated');
                var updatedEl = document.getElementById('prevOfferUpdated');
                var byEl = document.getElementById('prevOfferCreatedBy');

                function initials(n) {
                    if (!n) return '?';
                    var p = n.trim().split(/\s+/);
                    return ((p[0] || '')[0] + (p[1] ? p[1][0] : '')).toUpperCase();
                }
                if (avatarEl) avatarEl.innerText = initials(row.fullName);
                if (nameEl) nameEl.innerText = row.fullName || '-';
                if (posEl) posEl.innerText = row.positionApplied || '-';
                if (ridEl) ridEl.innerText = row.recruitmentId || '-';
                if (createdEl) createdEl.innerText = row.offeringCreated || '-';
                if (updatedEl) updatedEl.innerText = (row.offeringUpdated && row.offeringUpdated !== '-') ? row
                    .offeringUpdated : '-';
                if (byEl) byEl.innerText = row.offeringCreatedBy || '-';

                function disp(v) {
                    return v ? v : '-';
                }

                function dispRp(v) {
                    if (!v || v === '-') return '-';
                    var n = Number(String(v).replace(/[^\d]/g, ''));
                    return isNaN(n) || n === 0 ? (String(v) || '-') : 'Rp ' + n.toLocaleString('id-ID');
                }
                var fields = {
                    prevOfferCompany: disp(row.offeringCompanyEntity || row.branchName),
                    prevOfferLokasiKerja: disp(row.offeringLokasiKerja || row.city),
                    prevOfferDivision: disp(row.offeringDivision || row.division),
                    prevOfferPosition: disp(row.offeringPosition || row.positionApplied),
                    prevOfferJobLevel: disp(row.offeringJobLevel),
                    prevOfferEmploymentStatus: disp(row.offeringEmploymentStatus),
                    prevOfferContractDuration: disp(row.offeringContractDuration),
                    prevOfferJoinDate: disp(row.offeringJoinDate),
                    prevOfferWorkingHours: disp(row.offeringWorkingHours),
                    prevOfferSalaryBasic: dispRp(row.offeringSalaryBasic),
                    prevOfferAllowPulsa: dispRp(row.offeringAllowPulsa || row.allowPulsa),
                    prevOfferAllowTransport: dispRp(row.offeringAllowTransport),
                    prevOfferSalary: dispRp(row.offeringSalary || row.offeringSalaryBasic),
                    prevOfferNotes: disp(row.offeringNotes)
                };
                Object.keys(fields).forEach(function(id) {
                    var el = document.getElementById(id);
                    if (el) el.innerText = fields[id];
                });
                var warningEl = document.getElementById('prevOfferNoDataWarn');
                var hasData = row.offeringCreated && row.offeringCreated !== '-';
                if (warningEl) {
                    warningEl.classList.toggle('d-none', !!hasData);
                    warningEl.classList.toggle('d-flex', !hasData);
                }
                var dlBtn = document.getElementById('btnDownloadOffering');
                if (dlBtn) {
                    dlBtn.href = '/hr/export/offering-letter/' + recruitmentId;
                    dlBtn.disabled = !hasData;
                }
                var editBtn = document.getElementById('btnEditOffering');
                if (editBtn) {
                    editBtn.onclick = function() {
                        var previewModal = bootstrap.Modal.getInstance(document.getElementById(
                            'offeringPreviewModal'));
                        if (previewModal) previewModal.hide();
                        var offeringModal = bootstrap.Modal.getOrCreateInstance(document.getElementById(
                            'offeringModal'));
                        offeringModal.show();
                        setTimeout(function() {
                            document.getElementById('offeringCandSearch').value = row.fullName + ' (' +
                                row.recruitmentId + ')';
                            document.getElementById('offeringSearchClear').style.display = 'block';
                            document.getElementById('offeringSearchDropdown').style.display = 'none';
                            document.getElementById('offeringCandName').textContent = row.fullName ||
                                '-';
                            document.getElementById('offeringCandPos').textContent = row
                                .positionApplied || '-';
                            document.getElementById('offeringCandEmail').textContent = row.email || '-';
                            document.getElementById('offeringCandExpSal').textContent = row
                                .expectedSalary || '-';
                            document.getElementById('offeringCandAvatar').textContent = (row.fullName ||
                                'C').substring(0, 2).toUpperCase();
                            document.getElementById('offeringCandPreview').style.display = 'block';
                            window._offeringEditCandidate = row;
                            var setVal = function(id, v) {
                                var el = document.getElementById(id);
                                if (el) el.value = v || '';
                            };
                            var setSel = function(id, v) {
                                var el = document.getElementById(id);
                                if (el && v) {
                                    for (var i = 0; i < el.options.length; i++) {
                                        if (el.options[i].value === v || el.options[i].text === v) {
                                            el.selectedIndex = i;
                                            break;
                                        }
                                    }
                                }
                            };
                            setSel('offerBranchName', row.offeringCompanyEntity);
                            setVal('offerPosition', row.offeringPosition || row.positionApplied);
                            setSel('offerDivision', row.offeringDivision);
                            setSel('offerJobLevel', row.offeringJobLevel);
                            setVal('offerLokasiKerja', row.offeringLokasiKerja);
                            setVal('offerJoinDate', row.offeringJoinDate);
                            setSel('offerEmploymentStatus', row.offeringEmploymentStatus);
                            setSel('offerContractDuration', row.offeringContractDuration);
                            setVal('offerSalaryBasic', row.offeringSalaryBasic);
                            setVal('offerAllowPulsa', row.offeringAllowPulsa || row.allowPulsa);
                            setVal('offerAllowTransport', row.offeringAllowTransport);
                            setVal('offerWorkingHours', row.offeringWorkingHours);
                            var btnPdf = document.getElementById('btnDownloadOfferingPdf');
                            if (btnPdf) {
                                btnPdf.style.display = 'inline-flex';
                                btnPdf.onclick = function(e) {
                                    e.preventDefault();
                                    var params = new URLSearchParams({
                                        branch_name: document.getElementById(
                                            'offerBranchName')?.value || '',
                                        position: document.getElementById('offerPosition')
                                            ?.value || '',
                                        division: document.getElementById('offerDivision')
                                            ?.value || '',
                                        job_level: document.getElementById('offerJobLevel')
                                            ?.value || '',
                                        lokasi_kerja: document.getElementById(
                                            'offerLokasiKerja')?.value || '',
                                        join_date: document.getElementById('offerJoinDate')
                                            ?.value || '',
                                        status: document.getElementById(
                                            'offerEmploymentStatus')?.value || '',
                                        duration: document.getElementById(
                                            'offerContractDuration')?.value || '',
                                        salary_basic: document.getElementById(
                                            'offerSalaryBasic')?.value || '',
                                        allow_pulsa: document.getElementById(
                                            'offerAllowPulsa')?.value || '',
                                        allow_transport: document.getElementById(
                                            'offerAllowTransport')?.value || '',
                                        working_hours: document.getElementById(
                                            'offerWorkingHours')?.value || '',
                                    });
                                    window.open('/hr/export/offering-letter/' + row.recruitmentId +
                                        '?' + params.toString(), '_blank');
                                };
                            }
                        }, 350);
                    };
                }
                var modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('offeringPreviewModal'));
                modal.show();
            })
            .catch(function() {
                if (typeof showToast === 'function') showToast('Gagal memuat data offering.', 'error');
            });
    }

    document.addEventListener('DOMContentLoaded', function() {
        var btnSaveOffering = document.getElementById('btnSaveOffering');
        if (!btnSaveOffering) return;
        btnSaveOffering.addEventListener('click', function() {
            var searchVal = (document.getElementById('offeringCandSearch') || {}).value || '';
            var match = searchVal.match(/\(([A-Z]+-\d+-\d+)\)$/);
            if (!match) {
                if (typeof showToast === 'function') showToast('Pilih kandidat terlebih dahulu.',
                    'error');
                return;
            }
            var recruitmentId = match[1];
            var payload = {
                branch_name: document.getElementById('offerBranchName')?.value || '',
                position: document.getElementById('offerPosition')?.value || '',
                division: document.getElementById('offerDivision')?.value || '',
                job_level: document.getElementById('offerJobLevel')?.value || '',
                lokasi_kerja: document.getElementById('offerLokasiKerja')?.value || '',
                join_date: document.getElementById('offerJoinDate')?.value || '',
                employment_status: document.getElementById('offerEmploymentStatus')?.value || '',
                contract_duration: document.getElementById('offerContractDuration')?.value || '',
                salary_basic: document.getElementById('offerSalaryBasic')?.value || '',
                allow_pulsa: document.getElementById('offerAllowPulsa')?.value || '',
                allow_transport: document.getElementById('offerAllowTransport')?.value || '',
                working_hours: document.getElementById('offerWorkingHours')?.value || ''
            };
            if (!payload.branch_name || !payload.position || !payload.join_date || !payload
                .salary_basic) {
                if (typeof showToast === 'function') showToast(
                    'Isi field wajib: Branch, Position, Join Date, Gaji Pokok.', 'error');
                return;
            }
            btnSaveOffering.disabled = true;
            btnSaveOffering.innerHTML =
                '<span class="spinner-border spinner-border-sm me-1"></span>Menyimpan...';
            fetch('/hr/recruitment/' + recruitmentId + '/save-offering', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': getCsrfToken(),
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(payload)
                })
                .then(function(res) {
                    return res.json();
                })
                .then(function(result) {
                    btnSaveOffering.disabled = false;
                    btnSaveOffering.innerHTML = '<i class="bi bi-save2 me-1"></i>Simpan Offering';
                    if (result && result.success) {
                        if (typeof showToast === 'function') showToast(
                            'Data offering berhasil disimpan.', 'success');
                        if (result.pdf_url) {
                            window.open(result.pdf_url, '_blank');
                        }
                        var modal = bootstrap.Modal.getInstance(document.getElementById(
                            'offeringModal'));
                        if (modal) modal.hide();
                        location.reload();
                    } else {
                        if (typeof showToast === 'function') showToast('Gagal: ' + (result ? result
                            .message : 'Unknown error'), 'error');
                    }
                })
                .catch(function(err) {
                    btnSaveOffering.disabled = false;
                    btnSaveOffering.innerHTML = '<i class="bi bi-save2 me-1"></i>Simpan Offering';
                    if (typeof showToast === 'function') showToast('Error: ' + err.message,
                        'error');
                });
        });
    });

    // ============================================================
    // OFFERING RESPONSE MODAL (1:1 from GAS js/statusPages.html)
    // Status: Menunggu (default) → Diterima / Ditolak
    // ============================================================
    var _activeOfferRespRow = null;

    window.openOfferingResponseModal = function(row) {
        _activeOfferRespRow = row;
        var idEl = document.getElementById('offerRespRecruitmentId');
        var nameEl = document.getElementById('offerRespCandName');
        if (idEl) idEl.value = row.recruitmentId || '';
        if (nameEl) nameEl.innerText = (row.fullName || '-') + ' · ' + (row.recruitmentId || '');

        // Default "Menunggu" jika belum ada response
        var currentResp = row.offeringResponse || '';
        if (!currentResp || currentResp === '-') currentResp = 'Menunggu';

        var hiddenEl = document.getElementById('offerRespValue');
        if (hiddenEl) hiddenEl.value = currentResp;
        _updateOfferRespOptionStyles();

        var notesEl = document.getElementById('offerRespNotes');
        if (notesEl) notesEl.value = row.offeringResponseNotes || '';

        var confirmBtn = document.getElementById('btnConfirmOfferResp');
        if (confirmBtn) confirmBtn.disabled = false;

        var modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('offeringResponseModal'));
        modal.show();
    };

    function _updateOfferRespOptionStyles() {
        var colorMap = {
            'Menunggu': {
                border: '#d97706',
                bg: '#fffbeb'
            },
            'Diterima': {
                border: '#eb1c24',
                bg: '#f0fdf4'
            },
            'Ditolak': {
                border: '#991b1b',
                bg: '#fff1f2'
            }
        };
        var selectedVal = (document.getElementById('offerRespValue') || {}).value || '';
        document.querySelectorAll('.offer-resp-opt').forEach(function(opt) {
            var val = opt.getAttribute('data-value');
            var dot = opt.querySelector('.offer-resp-dot');
            var inner = opt.querySelector('.offer-resp-dot-inner');
            var isSelected = val === selectedVal;
            var colors = colorMap[val] || {
                border: '#e5e7eb',
                bg: ''
            };
            opt.style.borderColor = isSelected ? colors.border : '#e5e7eb';
            opt.style.background = isSelected ? colors.bg : '';
            if (dot) {
                dot.style.borderColor = isSelected ? colors.border : '#d1d5db';
                dot.style.background = isSelected ? colors.border : 'transparent';
            }
            if (inner) inner.style.background = isSelected ? '#fff' : 'transparent';
        });
    }

    document.addEventListener('DOMContentLoaded', function() {
        // Click handler untuk custom card-style option selector
        document.querySelectorAll('.offer-resp-opt').forEach(function(opt) {
            opt.addEventListener('click', function() {
                var val = opt.getAttribute('data-value');
                var hiddenEl = document.getElementById('offerRespValue');
                if (hiddenEl) hiddenEl.value = val;
                _updateOfferRespOptionStyles();
                var confirmBtn = document.getElementById('btnConfirmOfferResp');
                if (confirmBtn) confirmBtn.disabled = false;
            });
        });

        var confirmBtn = document.getElementById('btnConfirmOfferResp');
        if (!confirmBtn) return;
        confirmBtn.addEventListener('click', function() {
            var recruitmentId = (document.getElementById('offerRespRecruitmentId') || {}).value;
            var response = (document.getElementById('offerRespValue') || {}).value || '';
            var notes = (document.getElementById('offerRespNotes') || {}).value || '';
            if (!recruitmentId || !response) return;

            var origBtnHtml = confirmBtn.innerHTML;
            confirmBtn.disabled = true;
            confirmBtn.innerHTML =
                '<span class="spinner-border spinner-border-sm me-1"></span>Menyimpan...';

            fetch('/hr/recruitment/' + recruitmentId + '/save-offering-response', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': getCsrfToken(),
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        response: response,
                        notes: notes
                    })
                })
                .then(function(res) {
                    return res.json();
                })
                .then(function(result) {
                    confirmBtn.disabled = false;
                    confirmBtn.innerHTML = origBtnHtml;
                    var modal = bootstrap.Modal.getInstance(document.getElementById(
                        'offeringResponseModal'));
                    if (modal) modal.hide();
                    if (result && result.success) {
                        // Update object row di memory agar modal berikutnya pre-select nilai terbaru
                        if (_activeOfferRespRow) {
                            _activeOfferRespRow.offeringResponse = response;
                            _activeOfferRespRow.offeringResponseNotes = notes;
                        }
                        // Sync window.__allCandidatesForStatus so PKWT search reflects the new offeringResponse
                        // immediately without requiring a page reload.
                        if (Array.isArray(window.__allCandidatesForStatus) && _activeOfferRespRow) {
                            var updatedRid = (_activeOfferRespRow.recruitmentId || '');
                            window.__allCandidatesForStatus = window.__allCandidatesForStatus.map(
                                function(c) {
                                    if (c.recruitmentId === updatedRid) {
                                        return Object.assign({}, c, {
                                            offeringResponse: response,
                                            offeringResponseNotes: notes
                                        });
                                    }
                                    return c;
                                });
                        }
                        if (typeof showToast === 'function') showToast(
                            'Respons offering berhasil disimpan: ' + response, 'success');

                        // Update badge respons di drawer header secara live tanpa reload penuh
                        if (typeof window.renderOfferingRespBadge === 'function') {
                            window.renderOfferingRespBadge(response);
                        }
                    } else {
                        if (typeof showToast === 'function') showToast('Gagal: ' + (result ? result
                            .message : 'Error'), 'error');
                    }
                })
                .catch(function(err) {
                    confirmBtn.disabled = false;
                    confirmBtn.innerHTML = origBtnHtml;
                    if (typeof showToast === 'function') showToast('Error: ' + (err ? err.message :
                        'Unknown'), 'error');
                });
        });
    });

    // ============================================================
    // KONTRAK PKWT — live validation, auto end-date, submit + auto PDF
    // ============================================================
    document.addEventListener('DOMContentLoaded', function() {
        // Live validation field wajib
        ['onbBranchName', 'onbDepartment', 'onbPosition', 'onbContractDuration', 'onbJoinDate'].forEach(
            function(id) {
                var el = document.getElementById(id);
                if (el) {
                    el.addEventListener('input', _updateOnboardingConfirmBtn);
                    el.addEventListener('change', _updateOnboardingConfirmBtn);
                }
            });

        // Auto-hitung end date saat durasi / join date berubah
        var durationEl = document.getElementById('onbContractDuration');
        var joinDateEl = document.getElementById('onbJoinDate');
        if (durationEl) durationEl.addEventListener('change', function() {
            _calcOnboardingEndDate();
            _updateOnboardingConfirmBtn();
        });
        if (joinDateEl) joinDateEl.addEventListener('change', function() {
            _calcOnboardingEndDate();
            _updateOnboardingConfirmBtn();
        });

        // Reset saat modal ditutup
        var modalEl = document.getElementById('onboardingModal');
        if (modalEl) {
            modalEl.addEventListener('hidden.bs.modal', function() {
                _activeOnboardingRow = null;
                var searchEl = document.getElementById('onboardingCandSearch');
                if (searchEl) searchEl.value = '';
                var clearEl = document.getElementById('onboardingSearchClear');
                if (clearEl) clearEl.style.display = 'none';
                var dropEl = document.getElementById('onboardingSearchDropdown');
                if (dropEl) dropEl.style.display = 'none';
                var previewEl = document.getElementById('onboardingCandPreview');
                if (previewEl) previewEl.style.display = 'none';
                ['onbBranchName', 'onbDivision', 'onbDepartment', 'onbPosition', 'onbDirectSuperior',
                    'onbJoinDate', 'onbContractNumber', 'onbContractDuration', 'onbContractEnd',
                    'onbSalaryBasic', 'onbSalaryAllowance', 'onbDocDate', 'onbJamMasuk',
                    'onbWorkSchedule', 'onbTenorText'
                ].forEach(function(fid) {
                    var el = document.getElementById(fid);
                    if (el) el.value = '';
                });
                var hintEl = document.getElementById('onbContractEndHint');
                if (hintEl) hintEl.innerText = 'Terisi otomatis dari durasi kontrak';
                var textEl = document.getElementById('btnOnboardingText');
                var loadEl = document.getElementById('btnOnboardingLoading');
                if (textEl) textEl.style.display = 'inline-flex';
                if (loadEl) loadEl.style.display = 'none';
                var confirmBtn = document.getElementById('btnConfirmOnboarding');
                if (confirmBtn) confirmBtn.disabled = true;
            });
        }

        // Submit Kontrak PKWT → simpan + auto-generate PDF
        var confirmBtn = document.getElementById('btnConfirmOnboarding');
        if (!confirmBtn) return;
        confirmBtn.addEventListener('click', function() {
            var recruitmentId = (document.getElementById('onboardingRecruitmentId') || {}).value;
            if (!recruitmentId) {
                if (typeof showToast === 'function') showToast('Pilih kandidat terlebih dahulu.',
                    'error');
                return;
            }

            var payload = {
                employee_id: (document.getElementById('onboardingEmployeeId') || {}).value || '',
                branch_name: (document.getElementById('onbBranchName') || {}).value || '',
                division: (document.getElementById('onbDivision') || {}).value || '',
                department: (document.getElementById('onbDepartment') || {}).value || '',
                position: (document.getElementById('onbPosition') || {}).value || '',
                direct_superior: (document.getElementById('onbDirectSuperior') || {}).value || '',
                join_date: (document.getElementById('onbJoinDate') || {}).value || '',
                contract_number: (document.getElementById('onbContractNumber') || {}).value || '',
                contract_duration: (document.getElementById('onbContractDuration') || {}).value ||
                    '',
                contract_end: (document.getElementById('onbContractEnd') || {}).value || '',
                salary_basic: (document.getElementById('onbSalaryBasic') || {}).value || '',
                salary_allowance: (document.getElementById('onbSalaryAllowance') || {}).value || '',
                doc_date: (document.getElementById('onbDocDate') || {}).value || '',
                jam_masuk: (document.getElementById('onbJamMasuk') || {}).value || '',
                work_schedule: (document.getElementById('onbWorkSchedule') || {}).value || '',
                tenor_text: (document.getElementById('onbTenorText') || {}).value || '',
            };

            var textEl = document.getElementById('btnOnboardingText');
            var loadEl = document.getElementById('btnOnboardingLoading');
            confirmBtn.disabled = true;
            if (textEl) textEl.style.display = 'none';
            if (loadEl) loadEl.style.display = 'inline-flex';

            fetch('/hr/recruitment/' + recruitmentId + '/save-contract', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': getCsrfToken(),
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(payload)
                })
                .then(function(res) {
                    return res.json();
                })
                .then(function(result) {
                    confirmBtn.disabled = false;
                    if (textEl) textEl.style.display = 'inline-flex';
                    if (loadEl) loadEl.style.display = 'none';

                    if (result && result.success) {
                        var modal = bootstrap.Modal.getInstance(document.getElementById(
                            'onboardingModal'));
                        if (modal) modal.hide();

                        if (typeof showToast === 'function') {
                            showToast('Kontrak PKWT berhasil diproses! Membuat PDF...', 'success');
                        }

                        // ── Auto-generate & download PDF Kontrak PKWT (1:1 GAS behavior) ──
                        // Gunakan Fetch API + Blob agar PDF langsung didownload ke komputer
                        // user tanpa popup blocker dan tanpa membuka tab baru.
                        var pdfParams = new URLSearchParams({
                            branch_name: payload.branch_name,
                            position: payload.position,
                            department: payload.department,
                            division: payload.division,
                            direct_superior: payload.direct_superior,
                            contract_number: result.contractNumber || payload
                                .contract_number,
                            doc_date: payload.doc_date,
                            contract_duration: payload.contract_duration,
                            join_date: payload.join_date,
                            contract_end: payload.contract_end,
                            tenor_text: payload.tenor_text,
                            jam_masuk: payload.jam_masuk,
                            work_schedule: payload.work_schedule,
                            // recruitmentId sebagai fallback lookup di ExportController
                            // (race condition: employee baru mungkin belum ter-cache di sheet)
                            recruitment_id: recruitmentId,
                        });

                        // Gunakan employeeId jika tersedia; fallback ke recruitmentId
                        var pdfId = (result.employeeId && result.employeeId !== '') ? result
                            .employeeId : recruitmentId;
                        var pdfUrl = '/hr/export/kontrak-pkwt/' + pdfId + '?' + pdfParams
                            .toString();

                        // Fetch sebagai Blob → trigger download langsung ke local computer
                        fetch(pdfUrl, {
                                headers: {
                                    'X-CSRF-TOKEN': getCsrfToken(),
                                    'Accept': 'application/pdf',
                                }
                            })
                            .then(function(pdfRes) {
                                if (!pdfRes.ok) {
                                    // Coba baca body sebagai teks untuk error message
                                    return pdfRes.text().then(function(errText) {
                                        throw new Error('Gagal generate PDF (' + pdfRes
                                            .status + '): ' + errText.substring(0,
                                                200));
                                    });
                                }
                                var contentType = pdfRes.headers.get('content-type') || '';
                                if (!contentType.includes('pdf')) {
                                    throw new Error('Response bukan PDF (content-type: ' +
                                        contentType + '). Cek log server.');
                                }
                                return pdfRes.blob();
                            })
                            .then(function(blob) {
                                // Buat filename sesuai employeeId dan nama kandidat (1:1 GAS)
                                var safeName = (result.employeeId || recruitmentId).replace(
                                    /[^a-zA-Z0-9_-]/g, '_');
                                var filename = 'Kontrak_PKWT_' + safeName + '.pdf';

                                var blobUrl = window.URL.createObjectURL(blob);
                                var dlLink = document.createElement('a');
                                dlLink.href = blobUrl;
                                dlLink.download = filename;
                                document.body.appendChild(dlLink);
                                dlLink.click();
                                document.body.removeChild(dlLink);
                                window.URL.revokeObjectURL(blobUrl);

                                if (typeof showToast === 'function') {
                                    showToast('Kontrak PKWT berhasil diproses! PDF diunduh: ' +
                                        filename, 'success');
                                }

                                setTimeout(function() {
                                    location.reload();
                                }, 1800);
                            })
                            .catch(function(pdfErr) {
                                // PDF gagal — tetap reload karena kontrak sudah tersimpan,
                                // tapi tampilkan error agar user tahu PDF tidak terunduh
                                if (typeof showToast === 'function') {
                                    showToast(
                                        'Kontrak tersimpan, tetapi PDF gagal diunduh: ' + (
                                            pdfErr ? pdfErr.message : 'Unknown error') +
                                        '. Gunakan tombol Export PDF secara manual.',
                                        'error'
                                    );
                                }
                                setTimeout(function() {
                                    location.reload();
                                }, 3000);
                            });

                    } else {
                        if (typeof showToast === 'function') showToast('Gagal proses kontrak: ' + (
                            result ? result.message : 'Error'), 'error');
                    }
                })
                .catch(function(err) {
                    confirmBtn.disabled = false;
                    if (textEl) textEl.style.display = 'inline-flex';
                    if (loadEl) loadEl.style.display = 'none';
                    if (typeof showToast === 'function') showToast('Error: ' + (err ? err.message :
                        'Unknown'), 'error');
                });
        });
    });
</script>
