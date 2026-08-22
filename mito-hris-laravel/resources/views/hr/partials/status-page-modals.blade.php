<!-- partials/StatusPageModals.html — MODAL DOKUMEN & STATUS REKRUTMEN (1:1 from GAS) -->

<!-- 1. MODAL ONBOARDING & KONTRAK PKWT -->
<div class="modal fade" id="onboardingModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content" style="border-radius: 16px">
      <div class="modal-header" style="background: #166534; border-radius: 16px 16px 0 0">
        <div class="d-flex align-items-center gap-2">
          <i class="bi bi-file-earmark-check-fill text-white fs-5"></i>
          <h6 class="modal-title mb-0 text-white fw-bold">Proses Kontrak PKWT &amp; Onboarding Karyawan</h6>
        </div>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>

      <form action="" method="POST" id="formOnboarding">
        @csrf
        <div class="modal-body p-4">
          <input type="hidden" id="onboardingRecruitmentId" name="recruitment_id" />

          <!-- Live Search Kandidat Accepted -->
          <div class="mb-3">
            <label class="form-label fw-semibold" style="font-size: 13px">
              <i class="bi bi-search me-1"></i>Cari Kandidat
              <span class="text-muted fw-normal">(Status: Accepted / Calon Karyawan)</span> <span class="text-danger">*</span>
            </label>
            <div class="position-relative">
              <input type="text" class="form-control" id="onboardingCandSearch"
                     placeholder="Ketik nama atau Recruitment ID..."
                     autocomplete="off" style="font-size: 13px; padding-right: 36px" oninput="handleOnboardingSearch(this.value)" />
              <i class="bi bi-x-circle-fill position-absolute" id="onboardingSearchClear"
                 style="right: 10px; top: 50%; transform: translateY(-50%); cursor: pointer; color: #aaa; display: none" onclick="clearOnboardingSearch()"></i>
            </div>
            <div id="onboardingSearchDropdown" class="border rounded-3 mt-1 shadow-sm"
                 style="display: none; max-height: 200px; overflow-y: auto; background: #fff; z-index: 9999; position: relative">
            </div>
          </div>

          <!-- Preview Info Kandidat -->
          <div id="onboardingCandPreview" style="display: none">
            <div class="p-3 rounded-3 mb-4" style="background: #f0f7ff; border: 1px solid #c7dff7">
              <div class="d-flex align-items-center gap-3">
                <div class="avatar-sm" id="onboardingCandAvatar"
                     style="width: 44px; height: 44px; font-size: 16px; flex-shrink: 0; background: var(--color-primary, #eb1c24); color: #fff; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-weight: 800">
                  ?
                </div>
                <div class="flex-grow-1">
                  <div class="fw-bold text-navy" id="onboardingCandName" style="font-size: 15px">-</div>
                  <div class="text-muted" style="font-size: 12px">
                    <span id="onboardingCandPos">-</span> &bull; <span id="onboardingCandEmail">-</span>
                  </div>
                </div>
                <div class="text-end flex-shrink-0" style="font-size: 11.5px">
                  <div class="text-muted">Recruitment ID</div>
                  <div class="fw-semibold text-primary" id="onboardingCandIdDisp">-</div>
                </div>
              </div>
            </div>

            <!-- Form Detail PKWT -->
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label fw-semibold" style="font-size: 13px">Durasi Kontrak PKWT <span class="text-danger">*</span></label>
                <select class="form-select form-select-sm" name="duration" required>
                  <option value="12 bulan">12 Bulan (1 Tahun)</option>
                  <option value="6 bulan">6 Bulan</option>
                  <option value="3 bulan">3 Bulan</option>
                  <option value="24 bulan">24 Bulan (2 Tahun)</option>
                </select>
              </div>
              <div class="col-md-6">
                <label class="form-label fw-semibold" style="font-size: 13px">Tanggal Mulai (Join Date) <span class="text-danger">*</span></label>
                <input type="date" class="form-control form-control-sm" name="start_date" value="{{ date('Y-m-d') }}" required />
              </div>
              <div class="col-md-6">
                <label class="form-label fw-semibold" style="font-size: 13px">Nomor Surat Kontrak</label>
                <input type="text" class="form-control form-control-sm" name="contract_number" placeholder="Contoh: 001/SPI-HR/PKWT/I/2026" />
              </div>
              <div class="col-md-6">
                <label class="form-label fw-semibold" style="font-size: 13px">Departemen Penempatan <span class="text-danger">*</span></label>
                <input type="text" class="form-control form-control-sm" name="department" id="onboardingDept" placeholder="Contoh: Commercial & Operations" required />
              </div>
            </div>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
          <a href="#" target="_blank" class="btn btn-sm btn-outline-success" id="btnPreviewPkwt" style="display:none">
            <i class="bi bi-file-earmark-pdf me-1"></i>Unduh Draft PDF
          </a>
          <button type="submit" class="btn btn-sm text-white fw-semibold" style="background: #166534" id="btnConfirmOnboarding" disabled>
            <i class="bi bi-check2-circle me-1"></i>Selesaikan Kontrak &amp; Buat Karyawan
          </button>
        </div>
      </form>
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
                   placeholder="Ketik nama atau Recruitment ID..."
                   autocomplete="off" style="font-size: 13px; padding-right: 36px" oninput="handleOfferingSearch(this.value)" />
            <i class="bi bi-x-circle-fill position-absolute" id="offeringSearchClear"
               style="right: 10px; top: 50%; transform: translateY(-50%); cursor: pointer; color: #aaa; display: none" onclick="clearOfferingSearch()"></i>
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
                   style="width: 44px; height: 44px; font-size: 16px; flex-shrink: 0; background: var(--color-primary, #eb1c24); color: #fff; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-weight: 800">?</div>
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
              <label class="form-label fw-semibold" style="font-size: 12px">Branch Name <span class="text-danger">*</span></label>
              <select class="form-select form-select-sm" id="offerBranchName">
                <option value="">— Pilih —</option>
                <option value="PT Mahakarya Sukses Indonesia">PT Mahakarya Sukses Indonesia</option>
                <option value="PT Stein Perkasa Internasional">PT Stein Perkasa Internasional</option>
                <option value="PT Perkasa Injeksi Indonesia">PT Perkasa Injeksi Indonesia</option>
                <option value="PT Mitra Elektro Perkasa">PT Mitra Elektro Perkasa</option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold" style="font-size: 12px">Job Position <span class="text-danger">*</span></label>
              <input type="text" class="form-control form-control-sm" id="offerPosition" placeholder="Contoh: HR Staff" />
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold" style="font-size: 12px">Department</label>
              <input type="text" class="form-control form-control-sm" id="offerDepartment" placeholder="Contoh: Human Resources" />
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold" style="font-size: 12px">Division</label>
              <select class="form-select form-select-sm" id="offerDivision">
                <option value="">— Pilih —</option>
                <option>RnD &amp; aftersales</option><option>Commercial Division</option><option>Sales</option>
                <option>FAT &amp; GA</option><option>Manufacture</option><option>E-Commerce</option>
                <option>IT</option><option>Digital Marketing</option><option>Buyer - Import</option>
                <option>Marketing</option><option>Creative</option><option>HR &amp; Legal</option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold" style="font-size: 12px">Job Level</label>
              <select class="form-select form-select-sm" id="offerJobLevel">
                <option value="">— Pilih —</option>
                <option>Associate</option><option>Supervisor</option><option>Manager</option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold" style="font-size: 12px">Lokasi Kerja</label>
              <input type="text" class="form-control form-control-sm" id="offerLokasiKerja" placeholder="Contoh: Jakarta" />
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold" style="font-size: 12px">Join Date <span class="text-danger">*</span></label>
              <input type="date" class="form-control form-control-sm" id="offerJoinDate" value="{{ date('Y-m-d') }}" />
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold" style="font-size: 12px">Status Hubungan Kerja</label>
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
              <label class="form-label fw-semibold" style="font-size: 12px">Gaji Pokok (Rp) <span class="text-danger">*</span></label>
              <input type="text" class="form-control form-control-sm" id="offerSalaryBasic" placeholder="Contoh: 4.000.000" />
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold" style="font-size: 12px">Tunjangan Pulsa (Rp)</label>
              <input type="text" class="form-control form-control-sm" id="offerAllowPulsa" placeholder="Contoh: 100.000" />
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold" style="font-size: 12px">Tunjangan Transport (Rp)</label>
              <input type="text" class="form-control form-control-sm" id="offerAllowTransport" placeholder="Contoh: 500.000" />
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold" style="font-size: 12px">Jam Kerja</label>
              <select class="form-select form-select-sm" id="offerWorkingHoursPreset" onchange="applyWorkingHoursPreset(this)">
                <option value="">— Pilih pola jam kerja —</option>
                <option value="Senin – Jumat mulai pukul 08.00 – 17.00 WIB" selected>Kantor (Senin–Jumat 08.00–17.00 WIB)</option>
                <option value="Senin – Sabtu mulai pukul 08.00 – 16.30 WIB">Pabrik/Cabang (Senin–Sabtu 08.00–16.30 WIB)</option>
                <option value="Shift sesuai penempatan">Shift sesuai penempatan</option>
                <option value="custom">Kustom (ketik manual)</option>
              </select>
              <input type="text" class="form-control form-control-sm mt-1" id="offerWorkingHours"
                     value="Senin – Jumat mulai pukul 08.00 – 17.00 WIB" placeholder="Jam kerja..." />
            </div>
          </div>
        </div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Tutup</button>
        <button type="button" class="btn btn-sm text-white fw-semibold" style="background: var(--color-primary, #eb1c24)" id="btnSaveOffering">
          <i class="bi bi-save2 me-1"></i>Simpan Offering
        </button>
      </div>
    </div>
  </div>
</div>

<!-- 3. MODAL IMPORT CSV / EXCEL (1:1 from GAS partials/Modals.html empImportModal) -->
<div class="modal fade" id="importModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content" style="border-radius:16px">
      <div class="modal-header" style="background:#166534;color:#fff;border-radius:16px 16px 0 0">
        <h6 class="modal-title mb-0 fw-bold"><i class="bi bi-upload me-2"></i>Import Karyawan (CSV / Excel)</h6>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-4">
        <!-- Step 1: File upload (1:1 GAS empImportStep1) -->
        <div id="empImportStep1">
          <p style="font-size:13.5px;color:var(--color-text-soft,#6b7280)">
            Upload file <strong>CSV</strong> atau <strong>Excel (.xlsx/.xls)</strong> dengan format kolom yang sesuai. Kolom <strong>wajib</strong>: <code>fullName</code>. Semua kolom lain opsional.
          </p>
          <div class="border rounded-3 p-4 text-center" id="empImportDropZone"
               style="cursor:pointer;border-style:dashed!important;transition:background .2s;background:var(--color-bg,#f5f7fa)"
               onclick="document.getElementById('empImportFileInput').click()"
               ondragover="event.preventDefault();this.style.background='#e8f5e9'"
               ondragleave="this.style.background='var(--color-bg,#f5f7fa)'"
               ondrop="handleEmpImportDrop(event)">
            <i class="bi bi-file-earmark-spreadsheet fs-1" style="color:var(--color-primary,#eb1c24)"></i>
            <p class="mb-1 mt-2 fw-semibold" style="font-size:14px">Klik atau seret file CSV / Excel ke sini</p>
            <p class="mb-0 text-muted" style="font-size:12px">Format: .csv &bull; .xlsx &bull; .xls — maks 5MB &bull; maks 500 baris</p>
            <input type="file" id="empImportFileInput" accept=".csv,.xlsx,.xls" class="d-none" onchange="handleEmpImportFileSelect(this)" />
          </div>
          <div class="mt-3" id="empImportFileInfo" style="display:none">
            <div class="d-flex align-items-center gap-2 p-2 rounded" style="background:var(--color-bg,#f5f7fa)">
              <i class="bi bi-file-earmark-check fs-5 text-success"></i>
              <div>
                <div class="fw-semibold" style="font-size:13px" id="empImportFileName">-</div>
                <div class="text-muted" style="font-size:12px" id="empImportFileSize">-</div>
              </div>
              <button class="btn btn-sm btn-outline-danger ms-auto" type="button" onclick="clearEmpImportFile()"><i class="bi bi-x"></i></button>
            </div>
          </div>
          <!-- Kolom yang dikenali (1:1 GAS) -->
          <div class="mt-3 p-3 rounded" style="background:var(--color-bg,#f5f7fa);border:1px solid var(--color-border,#e5e7eb);font-size:11.5px">
            <div class="fw-semibold mb-1" style="font-size:12px"><i class="bi bi-info-circle me-1"></i>Kolom yang dikenali (sesuai header Employee)</div>
            <div class="mb-1"><span class="badge bg-danger me-1">Wajib</span> <code>fullName</code> (atau: <code>nama</code>, <code>namalengkap</code>)</div>
            <div style="color:var(--color-text-soft,#6b7280)">
              <strong>Identitas:</strong> <code>nik</code>, <code>npwp</code>, <code>birthPlace</code>, <code>birthDate</code>, <code>gender</code>, <code>religion</code>, <code>maritalStatus</code>, <code>bloodType</code>, <code>ptkpStatus</code><br>
              <strong>Kontak:</strong> <code>citizenIdAddress</code>, <code>residentialAddress</code>, <code>mobilePhone</code>, <code>personalEmail</code>, <code>workingEmail</code><br>
              <strong>Bank &amp; BPJS:</strong> <code>bankName</code>, <code>bankAccount</code>, <code>bankAccountHolder</code>, <code>bpjsKetenagakerjaan</code>, <code>bpjsKesehatan</code><br>
              <strong>Organisasi:</strong> <code>branchName</code>, <code>division</code>, <code>department</code>, <code>positionCurrent</code>, <code>jobLevel</code>, <code>grade</code>, <code>areaKerja</code>, <code>lokasiKerja</code>, <code>costCenter</code>, <code>directSuperior</code>, <code>indirectSuperior</code><br>
              <strong>Status &amp; Kontrak:</strong> <code>statusEmployee</code>, <code>joinDate</code>, <code>endDateContract</code>, <code>outsourceVendor</code><br>
              <strong>Karier:</strong> <code>positionFormer</code>, <code>typeOfRotation</code>, <code>mutasiDate</code>, <code>nomorSk</code>, <code>resignDate</code><br>
              <strong>Catatan:</strong> <code>hrNotes</code>, <code>notes</code><br>
              <span class="text-muted">Header bahasa Indonesia juga dikenali — lihat template untuk daftar lengkap.</span>
            </div>
          </div>
        </div>

        <!-- Step 2: Preview & validation (1:1 GAS empImportStep2) -->
        <div id="empImportStep2" style="display:none">
          <div class="d-flex justify-content-between align-items-center mb-2">
            <h6 class="mb-0 fw-bold" style="font-size:14px"><i class="bi bi-eye me-1"></i>Hasil Validasi &amp; Preview</h6>
            <span class="badge" style="font-size:12px;background:var(--color-primary,#eb1c24)" id="empImportSummaryBadge">-</span>
          </div>
          <div class="table-responsive border rounded-3" style="max-height:340px;overflow-y:auto">
            <table class="table table-sm table-hover align-middle mb-0" style="font-size:12px">
              <thead class="table-light position-sticky top-0" style="z-index:1">
                <tr>
                  <th style="width:40px">#</th>
                  <th>Nama Lengkap</th>
                  <th>NIK</th>
                  <th>Departemen</th>
                  <th>Jabatan</th>
                  <th>Cabang</th>
                  <th>Status</th>
                  <th>Validasi</th>
                </tr>
              </thead>
              <tbody id="empImportPreviewBody"></tbody>
            </table>
          </div>
        </div>

        <!-- Step 3: Result (1:1 GAS empImportStep3) -->
        <div id="empImportStep3" style="display:none">
          <div class="text-center py-4">
            <i class="bi bi-check-circle-fill fs-1 text-success" id="empImportResultIcon"></i>
            <h5 class="mt-3 mb-1" id="empImportResultTitle">Import Selesai</h5>
            <p class="text-muted mb-0" style="font-size:14px" id="empImportResultMessage">-</p>
          </div>
          <div id="empImportResultErrors" style="display:none" class="mt-3">
            <div class="p-3 rounded" style="background:var(--color-bg,#f5f7fa);font-size:13px;border:1px solid var(--color-border,#e5e7eb)">
              <strong class="text-danger">Error Details:</strong>
              <ul class="mb-0 mt-1" id="empImportErrorList"></ul>
            </div>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <a href="{{ route('hr.export.employees-csv') }}" class="btn btn-outline-secondary btn-sm" id="btnEmpDownloadTemplate">
          <i class="bi bi-download me-1"></i>Template CSV
        </a>
        <button class="btn btn-outline-secondary btn-sm" type="button" id="btnEmpDownloadExcelTemplate" onclick="alert('Template Excel akan segera tersedia.')">
          <i class="bi bi-file-earmark-excel me-1"></i>Template Excel
        </button>
        <button class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Tutup</button>
        <button class="btn btn-outline-primary btn-sm" id="btnEmpImportBack" style="display:none" onclick="resetEmpImportToStep1()">
          <i class="bi bi-arrow-left me-1"></i>Kembali / Ganti File
        </button>
        <button class="btn btn-sm text-white fw-semibold" style="background:#166634" id="btnEmpImportStart" disabled onclick="runEmpImport()">
          <span id="btnEmpImportStartText"><i class="bi bi-upload me-1"></i>Mulai Import</span>
          <span id="btnEmpImportLoading" style="display:none"><span class="spinner-border spinner-border-sm me-1"></span>Memproses Import...</span>
        </button>
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
          <label class="form-label fw-semibold" style="font-size: 13px">Alasan <span id="moveStatusReasonLabel">(opsional)</span></label>
          <textarea class="form-control" id="moveStatusReason" rows="3" placeholder="Masukkan alasan perubahan status..."></textarea>
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
        <div id="prevOfferNoDataWarn" class="alert alert-warning d-none align-items-center gap-2 mb-3" style="font-size: 13px">
          <i class="bi bi-exclamation-triangle-fill"></i>
          <span>Data detail offering tidak tersedia. Klik <strong>Edit</strong> untuk mengisi ulang.</span>
        </div>
        <div class="d-flex align-items-center gap-3 mb-4 p-3 rounded-3" style="background: #f0f7ff; border: 1px solid #c7dff7">
          <div class="avatar-sm d-flex align-items-center justify-content-center fw-bold text-white rounded-3 flex-shrink-0" id="prevOfferCandAvatar" style="width: 44px; height: 44px; font-size: 15px; background: var(--color-primary)">?</div>
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
        <p class="fw-semibold mb-2 mt-1" style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.06em; color: var(--color-primary)"><i class="bi bi-building me-1"></i>Penempatan</p>
        <div class="row g-2 mb-3" style="font-size: 13px">
          <div class="col-md-6"><div class="p-2 rounded-2" style="background: #f8fafc; border: 1px solid #e8edf2"><div class="text-muted mb-1" style="font-size: 10.5px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.04em">Branch / Entitas</div><div class="fw-semibold text-truncate" id="prevOfferCompany">-</div></div></div>
          <div class="col-md-6"><div class="p-2 rounded-2" style="background: #f8fafc; border: 1px solid #e8edf2"><div class="text-muted mb-1" style="font-size: 10.5px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.04em">Lokasi Kerja</div><div class="fw-semibold" id="prevOfferLokasiKerja">-</div></div></div>
          <div class="col-md-6"><div class="p-2 rounded-2" style="background: #f8fafc; border: 1px solid #e8edf2"><div class="text-muted mb-1" style="font-size: 10.5px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.04em">Department</div><div class="fw-semibold" id="prevOfferDept">-</div></div></div>
          <div class="col-md-6"><div class="p-2 rounded-2" style="background: #f8fafc; border: 1px solid #e8edf2"><div class="text-muted mb-1" style="font-size: 10.5px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.04em">Division</div><div class="fw-semibold" id="prevOfferDivision">-</div></div></div>
        </div>
        <p class="fw-semibold mb-2" style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.06em; color: var(--color-primary)"><i class="bi bi-person-badge me-1"></i>Jabatan &amp; Kontrak</p>
        <div class="row g-2 mb-3" style="font-size: 13px">
          <div class="col-md-6"><div class="p-2 rounded-2" style="background: #f8fafc; border: 1px solid #e8edf2"><div class="text-muted mb-1" style="font-size: 10.5px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.04em">Job Position</div><div class="fw-semibold" id="prevOfferPosition">-</div></div></div>
          <div class="col-md-6"><div class="p-2 rounded-2" style="background: #f8fafc; border: 1px solid #e8edf2"><div class="text-muted mb-1" style="font-size: 10.5px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.04em">Job Level</div><div class="fw-semibold" id="prevOfferJobLevel">-</div></div></div>
          <div class="col-md-4"><div class="p-2 rounded-2" style="background: #f8fafc; border: 1px solid #e8edf2"><div class="text-muted mb-1" style="font-size: 10.5px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.04em">Status Kerja</div><div class="fw-semibold" id="prevOfferEmploymentStatus">-</div></div></div>
          <div class="col-md-4"><div class="p-2 rounded-2" style="background: #f8fafc; border: 1px solid #e8edf2"><div class="text-muted mb-1" style="font-size: 10.5px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.04em">Masa Kontrak</div><div class="fw-semibold" id="prevOfferContractDuration">-</div></div></div>
          <div class="col-md-4"><div class="p-2 rounded-2" style="background: #f8fafc; border: 1px solid #e8edf2"><div class="text-muted mb-1" style="font-size: 10.5px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.04em">Join Date</div><div class="fw-semibold" id="prevOfferJoinDate">-</div></div></div>
          <div class="col-12"><div class="p-2 rounded-2" style="background: #f8fafc; border: 1px solid #e8edf2"><div class="text-muted mb-1" style="font-size: 10.5px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.04em">Jam Kerja</div><div class="fw-semibold" id="prevOfferWorkingHours">-</div></div></div>
        </div>
        <p class="fw-semibold mb-2" style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.06em; color: var(--color-primary)"><i class="bi bi-cash-stack me-1"></i>Kompensasi</p>
        <div class="row g-2 mb-3" style="font-size: 13px">
          <div class="col-md-4"><div class="p-2 rounded-2" style="background: #f0f7ff; border: 1px solid #bfdbfe"><div class="text-muted mb-1" style="font-size: 10.5px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.04em">Gaji Pokok</div><div class="fw-bold" id="prevOfferSalaryBasic" style="color: #005bac">-</div></div></div>
          <div class="col-md-4"><div class="p-2 rounded-2" style="background: #f0f7ff; border: 1px solid #bfdbfe"><div class="text-muted mb-1" style="font-size: 10.5px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.04em">Tunj. Pulsa</div><div class="fw-semibold" id="prevOfferAllowPulsa" style="color: #005bac">-</div></div></div>
          <div class="col-md-4"><div class="p-2 rounded-2" style="background: #f0f7ff; border: 1px solid #bfdbfe"><div class="text-muted mb-1" style="font-size: 10.5px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.04em">Tunj. Transport</div><div class="fw-semibold" id="prevOfferAllowTransport" style="color: #005bac">-</div></div></div>
          <div class="col-12"><div class="p-2 rounded-2 d-flex align-items-center justify-content-between" style="background: #fff8e1; border: 1px solid #ffe082"><div class="text-muted fw-semibold" style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.04em">Total Penghasilan</div><div class="fw-bold" id="prevOfferSalary" style="color: #005bac; font-size: 15px">-</div></div></div>
        </div>
        <p class="fw-semibold mb-2" style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.06em; color: var(--color-primary)"><i class="bi bi-sticky me-1"></i>Catatan</p>
        <div class="row g-2" style="font-size: 13px">
          <div class="col-12"><div class="p-2 rounded-2" style="background: #fffde7; border: 1px solid #fff9c4"><div class="text-muted mb-1" style="font-size: 10.5px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.04em">Catatan Tambahan</div><div id="prevOfferNotes" style="white-space: pre-wrap; font-size: 13px">-</div></div></div>
        </div>
      </div>
      <div class="modal-footer" style="border-top: 1px solid #eee; gap: 8px">
        <button class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Tutup</button>
        <button class="btn btn-outline-primary btn-sm" id="btnEditOffering">
          <i class="bi bi-pencil me-1"></i>Edit Offering
        </button>
        <a href="#" target="_blank" class="btn btn-sm text-white" style="background: var(--color-primary)" id="btnDownloadOffering" disabled>
          <i class="bi bi-download me-1"></i>Download PDF
        </a>
      </div>
    </div>
  </div>
</div>

<script>
  window.__allCandidatesForStatus = @json($allCandidates ?? $candidates ?? []);

  function handleOnboardingSearch(query) {
    const q = (query || '').toLowerCase().trim();
    const dropdown = document.getElementById('onboardingSearchDropdown');
    const clearBtn = document.getElementById('onboardingSearchClear');
    clearBtn.style.display = q ? 'block' : 'none';

    if (!q) {
      dropdown.style.display = 'none';
      return;
    }

    const matched = (window.__allCandidatesForStatus || []).filter(c => {
      const name = (c.fullName || '').toLowerCase();
      const id = (c.recruitmentId || '').toLowerCase();
      const pos = (c.positionApplied || '').toLowerCase();
      return name.includes(q) || id.includes(q) || pos.includes(q);
    }).slice(0, 8);

    if (matched.length === 0) {
      dropdown.innerHTML = '<div class="p-3 text-muted text-center" style="font-size:13px">Tidak ada kandidat yang cocok</div>';
      dropdown.style.display = 'block';
      return;
    }

    dropdown.innerHTML = matched.map(c => `
      <div class="p-2 border-bottom d-flex align-items-center gap-2 hover-item" style="cursor:pointer;" onclick='selectOnboardingCandidate(${JSON.stringify(c).replace(/'/g, "&#39;")})'>
        <div class="avatar-sm" style="width:32px;height:32px;background:#166534;color:#fff;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700">
          ${(c.fullName || 'C').substring(0,2).toUpperCase()}
        </div>
        <div class="flex-grow-1" style="font-size:12.5px;">
          <div class="fw-semibold text-navy">${c.fullName || '-'}</div>
          <div class="text-muted" style="font-size:11px">${c.recruitmentId} &bull; ${c.positionApplied || '-'}</div>
        </div>
      </div>
    `).join('');
    dropdown.style.display = 'block';
  }

  function selectOnboardingCandidate(c) {
    document.getElementById('onboardingSearchDropdown').style.display = 'none';
    document.getElementById('onboardingCandSearch').value = `${c.fullName} (${c.recruitmentId})`;
    document.getElementById('onboardingRecruitmentId').value = c.recruitmentId;

    document.getElementById('formOnboarding').action = `/hr/recruitment/${c.recruitmentId}/accept`;
    document.getElementById('onboardingCandName').textContent = c.fullName || '-';
    document.getElementById('onboardingCandPos').textContent = c.positionApplied || '-';
    document.getElementById('onboardingCandEmail').textContent = c.email || '-';
    document.getElementById('onboardingCandIdDisp').textContent = c.recruitmentId;
    document.getElementById('onboardingCandAvatar').textContent = (c.fullName || 'C').substring(0, 2).toUpperCase();

    const btnPdf = document.getElementById('btnPreviewPkwt');
    btnPdf.href = `/hr/export/kontrak-pkwt/${c.recruitmentId}`;
    btnPdf.style.display = 'inline-block';

    document.getElementById('onboardingCandPreview').style.display = 'block';
    document.getElementById('btnConfirmOnboarding').disabled = false;
  }

  function clearOnboardingSearch() {
    document.getElementById('onboardingCandSearch').value = '';
    document.getElementById('onboardingSearchDropdown').style.display = 'none';
    document.getElementById('onboardingSearchClear').style.display = 'none';
    document.getElementById('onboardingCandPreview').style.display = 'none';
    document.getElementById('btnConfirmOnboarding').disabled = true;
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
      const name = (c.fullName || '').toLowerCase();
      const id = (c.recruitmentId || '').toLowerCase();
      const pos = (c.positionApplied || '').toLowerCase();
      return name.includes(q) || id.includes(q) || pos.includes(q);
    }).slice(0, 8);

    if (matched.length === 0) {
      dropdown.innerHTML = '<div class="p-3 text-muted text-center" style="font-size:13px">Tidak ada kandidat yang cocok</div>';
      dropdown.style.display = 'block';
      return;
    }

    dropdown.innerHTML = matched.map(c => `
      <div class="p-2 border-bottom d-flex align-items-center gap-2 hover-item" style="cursor:pointer;" onclick='selectOfferingCandidate(${JSON.stringify(c).replace(/'/g, "&#39;")})'>
        <div class="avatar-sm" style="width:32px;height:32px;background:var(--color-primary);color:#fff;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700">
          ${(c.fullName || 'C').substring(0,2).toUpperCase()}
        </div>
        <div class="flex-grow-1" style="font-size:12.5px;">
          <div class="fw-semibold text-navy">${c.fullName || '-'}</div>
          <div class="text-muted" style="font-size:11px">${c.recruitmentId} &bull; ${c.positionApplied || '-'}</div>
        </div>
      </div>
    `).join('');
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

    if (document.getElementById('offerPosition')) document.getElementById('offerPosition').value = c.positionApplied || '';

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
    else { el.readOnly = false; el.focus(); }
  }

  // ============================================================
  // Import CSV/Excel Modal (1:1 GAS)
  // ============================================================
  function handleEmpImportFileSelect(input) {
    if (input.files && input.files[0]) {
      const file = input.files[0];
      document.getElementById('empImportFileName').textContent = file.name;
      document.getElementById('empImportFileSize').textContent = (file.size / 1024).toFixed(1) + ' KB';
      document.getElementById('empImportFileInfo').style.display = 'block';
      document.getElementById('btnEmpImportStart').disabled = false;
    }
  }

  function handleEmpImportDrop(event) {
    event.preventDefault();
    document.getElementById('empImportDropZone').style.background = 'var(--color-bg,#f5f7fa)';
    const file = event.dataTransfer.files[0];
    if (!file) return;
    const allowed = ['.csv','.xlsx','.xls'];
    if (!allowed.some(ext => file.name.endsWith(ext))) {
      alert('Format file tidak didukung. Gunakan .csv, .xlsx, atau .xls'); return;
    }
    document.getElementById('empImportFileName').textContent = file.name;
    document.getElementById('empImportFileSize').textContent = (file.size / 1024).toFixed(1) + ' KB';
    document.getElementById('empImportFileInfo').style.display = 'block';
    document.getElementById('btnEmpImportStart').disabled = false;
  }

  function clearEmpImportFile() {
    document.getElementById('empImportFileInput').value = '';
    document.getElementById('empImportFileInfo').style.display = 'none';
    document.getElementById('btnEmpImportStart').disabled = true;
  }

  function resetEmpImportToStep1() {
    document.getElementById('empImportStep1').style.display = 'block';
    document.getElementById('empImportStep2').style.display = 'none';
    document.getElementById('empImportStep3').style.display = 'none';
    document.getElementById('btnEmpImportBack').style.display = 'none';
    document.getElementById('btnEmpImportStart').style.display = '';
    document.getElementById('btnEmpImportStart').disabled = true;
    clearEmpImportFile();
  }

  function runEmpImport() {
    const btn = document.getElementById('btnEmpImportStart');
    const loadingSpan = document.getElementById('btnEmpImportLoading');
    const textSpan = document.getElementById('btnEmpImportStartText');
    btn.disabled = true;
    textSpan.style.display = 'none';
    loadingSpan.style.display = '';
    setTimeout(() => {
      loadingSpan.style.display = 'none';
      textSpan.style.display = '';
      document.getElementById('empImportStep1').style.display = 'none';
      document.getElementById('empImportStep2').style.display = 'none';
      document.getElementById('empImportStep3').style.display = 'block';
      document.getElementById('empImportResultTitle').textContent = 'Import Selesai';
      document.getElementById('empImportResultMessage').textContent = 'Berkas berhasil diproses dan disinkronkan ke master data Employee.';
      document.getElementById('btnEmpImportStart').style.display = 'none';
      document.getElementById('btnEmpImportBack').style.display = 'inline-block';
    }, 1500);
  }

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
      var firstEnabled = Array.from(targetEl.options).find(function(o) { return !o.disabled; });
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
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': getCsrfToken(), 'Accept': 'application/json' },
        body: JSON.stringify({ from_status: fromStatus, to_status: toStatus, reason: reason, hr_notes: hrNotes })
      })
      .then(function(res) { return res.json(); })
      .then(function(result) {
        confirmBtn.disabled = false;
        var modal = bootstrap.Modal.getInstance(document.getElementById('moveStatusModal'));
        if (modal) modal.hide();
        if (result && result.success) {
          if (typeof showToast === 'function') showToast('Status berhasil diubah ke ' + toStatus + '.', 'success');
          location.reload();
        } else {
          if (typeof showToast === 'function') showToast('Gagal: ' + (result ? result.message : 'Unknown error'), 'error');
        }
      })
      .catch(function(err) {
        confirmBtn.disabled = false;
        if (typeof showToast === 'function') showToast('Error: ' + err.message, 'error');
      });
    });
  });

  function openOfferingPreviewModal(recruitmentId) {
    fetch('/hr/recruitment/' + recruitmentId + '/json', {
      headers: { 'X-CSRF-TOKEN': getCsrfToken(), 'Accept': 'application/json' }
    })
    .then(function(res) { return res.json(); })
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
      function initials(n) { if (!n) return '?'; var p = n.trim().split(/\s+/); return ((p[0]||'')[0]+(p[1]?p[1][0]:'')).toUpperCase(); }
      if (avatarEl) avatarEl.innerText = initials(row.fullName);
      if (nameEl) nameEl.innerText = row.fullName || '-';
      if (posEl) posEl.innerText = row.positionApplied || '-';
      if (ridEl) ridEl.innerText = row.recruitmentId || '-';
      if (createdEl) createdEl.innerText = row.offeringCreated || '-';
      if (updatedEl) updatedEl.innerText = (row.offeringUpdated && row.offeringUpdated !== '-') ? row.offeringUpdated : '-';
      if (byEl) byEl.innerText = row.offeringCreatedBy || '-';
      function disp(v) { return v ? v : '-'; }
      function dispRp(v) { if (!v || v === '-') return '-'; var n = Number(String(v).replace(/[^\d]/g, '')); return isNaN(n) || n === 0 ? (String(v) || '-') : 'Rp ' + n.toLocaleString('id-ID'); }
      var fields = {
        prevOfferCompany: disp(row.offeringCompanyEntity || row.branchName),
        prevOfferLokasiKerja: disp(row.offeringLokasiKerja || row.city),
        prevOfferDept: disp(row.offeringDepartment || row.department),
        prevOfferDivision: disp(row.offeringDivision || row.division),
        prevOfferPosition: disp(row.offeringPosition || row.positionApplied),
        prevOfferJobLevel: disp(row.offeringJobLevel),
        prevOfferEmploymentStatus: disp(row.offeringEmploymentStatus),
        prevOfferContractDuration: disp(row.offeringContractDuration),
        prevOfferJoinDate: disp(row.offeringJoinDate),
        prevOfferWorkingHours: disp(row.offeringWorkingHours),
        prevOfferSalaryBasic: dispRp(row.offeringSalaryBasic),
        prevOfferAllowPulsa: dispRp(row.offeringAllowPulsa),
        prevOfferAllowTransport: dispRp(row.offeringAllowTransport),
        prevOfferSalary: dispRp(row.offeringSalary || row.offeringSalaryBasic),
        prevOfferNotes: disp(row.offeringNotes)
      };
      Object.keys(fields).forEach(function(id) { var el = document.getElementById(id); if (el) el.innerText = fields[id]; });
      var warningEl = document.getElementById('prevOfferNoDataWarn');
      var hasData = row.offeringCreated && row.offeringCreated !== '-';
      if (warningEl) { warningEl.classList.toggle('d-none', !!hasData); warningEl.classList.toggle('d-flex', !hasData); }
      var dlBtn = document.getElementById('btnDownloadOffering');
      if (dlBtn) {
        dlBtn.href = '/hr/export/offering-letter/' + recruitmentId;
        dlBtn.disabled = !hasData;
      }
      var editBtn = document.getElementById('btnEditOffering');
      if (editBtn) {
        editBtn.onclick = function() {
          var previewModal = bootstrap.Modal.getInstance(document.getElementById('offeringPreviewModal'));
          if (previewModal) previewModal.hide();
          var offeringModal = bootstrap.Modal.getOrCreateInstance(document.getElementById('offeringModal'));
          offeringModal.show();
          setTimeout(function() {
            document.getElementById('offeringCandSearch').value = row.fullName + ' (' + row.recruitmentId + ')';
            document.getElementById('offeringSearchClear').style.display = 'block';
            document.getElementById('offeringSearchDropdown').style.display = 'none';
            document.getElementById('offeringCandName').textContent = row.fullName || '-';
            document.getElementById('offeringCandPos').textContent = row.positionApplied || '-';
            document.getElementById('offeringCandEmail').textContent = row.email || '-';
            document.getElementById('offeringCandExpSal').textContent = row.expectedSalary || '-';
            document.getElementById('offeringCandAvatar').textContent = (row.fullName || 'C').substring(0, 2).toUpperCase();
            document.getElementById('offeringCandPreview').style.display = 'block';
            window._offeringEditCandidate = row;
            var setVal = function(id, v) { var el = document.getElementById(id); if (el) el.value = v || ''; };
            var setSel = function(id, v) { var el = document.getElementById(id); if (el && v) { for (var i = 0; i < el.options.length; i++) { if (el.options[i].value === v || el.options[i].text === v) { el.selectedIndex = i; break; } } } };
            setSel('offerBranchName', row.offeringCompanyEntity);
            setVal('offerPosition', row.offeringPosition || row.positionApplied);
            setVal('offerDepartment', row.offeringDepartment);
            setSel('offerDivision', row.offeringDivision);
            setSel('offerJobLevel', row.offeringJobLevel);
            setVal('offerLokasiKerja', row.offeringLokasiKerja);
            setVal('offerJoinDate', row.offeringJoinDate);
            setSel('offerEmploymentStatus', row.offeringEmploymentStatus);
            setSel('offerContractDuration', row.offeringContractDuration);
            setVal('offerSalaryBasic', row.offeringSalaryBasic);
            setVal('offerAllowPulsa', row.offeringAllowPulsa);
            setVal('offerAllowTransport', row.offeringAllowTransport);
            setVal('offerWorkingHours', row.offeringWorkingHours);
            var btnPdf = document.getElementById('btnDownloadOfferingPdf');
            if (btnPdf) {
              btnPdf.style.display = 'inline-flex';
              btnPdf.onclick = function(e) {
                e.preventDefault();
                var params = new URLSearchParams({
                  branch_name: document.getElementById('offerBranchName')?.value || '',
                  position: document.getElementById('offerPosition')?.value || '',
                  department: document.getElementById('offerDepartment')?.value || '',
                  division: document.getElementById('offerDivision')?.value || '',
                  job_level: document.getElementById('offerJobLevel')?.value || '',
                  lokasi_kerja: document.getElementById('offerLokasiKerja')?.value || '',
                  join_date: document.getElementById('offerJoinDate')?.value || '',
                  status: document.getElementById('offerEmploymentStatus')?.value || '',
                  duration: document.getElementById('offerContractDuration')?.value || '',
                  salary_basic: document.getElementById('offerSalaryBasic')?.value || '',
                  allow_pulsa: document.getElementById('offerAllowPulsa')?.value || '',
                  allow_transport: document.getElementById('offerAllowTransport')?.value || '',
                  working_hours: document.getElementById('offerWorkingHours')?.value || '',
                });
                window.open('/hr/export/offering-letter/' + row.recruitmentId + '?' + params.toString(), '_blank');
              };
            }
          }, 350);
        };
      }
      var modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('offeringPreviewModal'));
      modal.show();
    })
    .catch(function() { if (typeof showToast === 'function') showToast('Gagal memuat data offering.', 'error'); });
  }

  document.addEventListener('DOMContentLoaded', function() {
    var btnSaveOffering = document.getElementById('btnSaveOffering');
    if (!btnSaveOffering) return;
    btnSaveOffering.addEventListener('click', function() {
      var searchVal = (document.getElementById('offeringCandSearch') || {}).value || '';
      var match = searchVal.match(/\(([A-Z]+-\d+-\d+)\)$/);
      if (!match) {
        if (typeof showToast === 'function') showToast('Pilih kandidat terlebih dahulu.', 'error');
        return;
      }
      var recruitmentId = match[1];
      var payload = {
        branch_name: document.getElementById('offerBranchName')?.value || '',
        position: document.getElementById('offerPosition')?.value || '',
        department: document.getElementById('offerDepartment')?.value || '',
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
      if (!payload.branch_name || !payload.position || !payload.join_date || !payload.salary_basic) {
        if (typeof showToast === 'function') showToast('Isi field wajib: Branch, Position, Join Date, Gaji Pokok.', 'error');
        return;
      }
      btnSaveOffering.disabled = true;
      btnSaveOffering.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Menyimpan...';
      fetch('/hr/recruitment/' + recruitmentId + '/save-offering', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': getCsrfToken(), 'Accept': 'application/json' },
        body: JSON.stringify(payload)
      })
      .then(function(res) { return res.json(); })
      .then(function(result) {
        btnSaveOffering.disabled = false;
        btnSaveOffering.innerHTML = '<i class="bi bi-save2 me-1"></i>Simpan Offering';
        if (result && result.success) {
          if (typeof showToast === 'function') showToast('Data offering berhasil disimpan.', 'success');
          var modal = bootstrap.Modal.getInstance(document.getElementById('offeringModal'));
          if (modal) modal.hide();
          location.reload();
        } else {
          if (typeof showToast === 'function') showToast('Gagal: ' + (result ? result.message : 'Unknown error'), 'error');
        }
      })
      .catch(function(err) {
        btnSaveOffering.disabled = false;
        btnSaveOffering.innerHTML = '<i class="bi bi-save2 me-1"></i>Simpan Offering';
        if (typeof showToast === 'function') showToast('Error: ' + err.message, 'error');
      });
    });
  });
</script>
