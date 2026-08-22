<div class="drawer-overlay" id="drawerOverlay"></div>
<div class="drawer-panel" id="drawerPanel" data-mode="candidate">
  <div class="drawer-header">
    <div class="drawer-header-top">
      <div class="d-flex align-items-center gap-3">
        <div class="avatar-lg" id="drawerAvatar">?</div>
        <div>
          <h5 class="mb-1" id="drawerCandidateName" style="font-weight:700">Nama</h5>
          <div style="font-size:12.5px;opacity:0.9" id="drawerPosition">-</div>
          <div class="mt-2" id="drawerStatusBadgeWrap"></div>
        </div>
      </div>
      <div class="drawer-actions">
        <button class="drawer-icon-btn" id="btnDrawerPrint" title="Download PDF"><i class="bi bi-file-earmark-pdf-fill"></i></button>
        <button class="drawer-icon-btn" id="btnDrawerEdit" title="Edit" style="display:none"><i class="bi bi-pencil-fill"></i></button>
        <button class="drawer-icon-btn" id="btnDrawerClose" title="Tutup"><i class="bi bi-x-lg"></i></button>
      </div>
    </div>
    <div class="drawer-id-meta" id="drawerIdMeta"></div>
  </div>

  <div class="drawer-body">
    {{-- MODE: CANDIDATE --}}
    <div class="drawer-mode-section active" id="drawerSectionCandidate">
      <div class="cv-section-card">
        <h6><i class="bi bi-person-fill"></i> Informasi Pribadi</h6>
        <div class="cv-grid-2">
          <div class="cv-field"><div class="cv-label">Nama Lengkap</div><div class="cv-value" id="cvFullName">-</div></div>
          <div class="cv-field"><div class="cv-label">Tanggal Lahir</div><div class="cv-value" id="cvBirthDate">-</div></div>
          <div class="cv-field"><div class="cv-label">Usia</div><div class="cv-value" id="cvAge">-</div></div>
          <div class="cv-field"><div class="cv-label">Jenis Kelamin</div><div class="cv-value" id="cvGender">-</div></div>
          <div class="cv-field"><div class="cv-label">Status Pernikahan</div><div class="cv-value" id="cvMaritalStatus">-</div></div>
          <div class="cv-field"><div class="cv-label">NIK</div><div class="cv-value" id="cvNik">-</div></div>
          <div class="cv-field"><div class="cv-label">Email</div><div class="cv-value" id="cvEmail">-</div></div>
          <div class="cv-field"><div class="cv-label">No. HP</div><div class="cv-value" id="cvPhone">-</div></div>
          <div class="cv-field"><div class="cv-label">Kota</div><div class="cv-value" id="cvCity">-</div></div>
        </div>
        <div class="cv-field mb-0"><div class="cv-label">Alamat</div><div class="cv-value" id="cvAddress">-</div></div>
      </div>

      <div class="cv-section-card">
        <h6><i class="bi bi-briefcase-fill"></i> Informasi Pekerjaan</h6>
        <div class="cv-grid-2">
          <div class="cv-field"><div class="cv-label">Posisi Dilamar</div><div class="cv-value" id="cvPosition">-</div></div>
          <div class="cv-field"><div class="cv-label">Pendidikan</div><div class="cv-value" id="cvEducation">-</div></div>
          <div class="cv-field"><div class="cv-label">Pengalaman Kerja</div><div class="cv-value" id="cvExperience">-</div></div>
          <div class="cv-field"><div class="cv-label">Perusahaan Terakhir</div><div class="cv-value" id="cvLastCompany">-</div></div>
          <div class="cv-field"><div class="cv-label">Status Bekerja</div><div class="cv-value" id="cvEmploymentStatus">-</div></div>
          <div class="cv-field"><div class="cv-label">Kesediaan Bergabung</div><div class="cv-value" id="cvAvailability">-</div></div>
          <div class="cv-field"><div class="cv-label">Ekspektasi Gaji</div><div class="cv-value" id="cvSalary">-</div></div>
          <div class="cv-field"><div class="cv-label">Sumber Rekrutmen</div><div class="cv-value" id="cvSource">-</div></div>
        </div>
      </div>

      <div class="cv-section-card">
        <h6><i class="bi bi-chat-square-text-fill"></i> Catatan HR</h6>
        <textarea class="form-control" id="cvHrNotes" rows="5" maxlength="2000" placeholder="Contoh: Komunikasi baik, perlu interview kedua..."></textarea>
        <div class="notes-toolbar">
          <span id="notesCharCounter">0 / 2000 karakter</span>
          <div class="d-flex align-items-center gap-2">
            <span class="notes-save-state" id="notesSaveState">Tersimpan otomatis</span>
            <button class="btn-reset-filter" id="btnSaveNotesManual" type="button" style="height:32px;padding:0 12px;font-size:12px"><i class="bi bi-save2"></i> Simpan</button>
          </div>
        </div>
        <div class="mt-1" style="font-size:11px;color:var(--color-text-soft)">Terakhir diperbarui: <span id="notesLastUpdated">-</span></div>
      </div>

      <div class="cv-section-card mb-0">
        <h6><i class="bi bi-clock-history"></i> Riwayat Aktivitas</h6>
        <div class="timeline" id="drawerTimeline"><div class="timeline-empty">Memuat riwayat...</div></div>
      </div>
    </div>

    {{-- MODE: EMPLOYEE --}}
    <div class="drawer-mode-section" id="drawerSectionEmployee">
      <div class="cv-section-card">
        <h6><i class="bi bi-person-badge-fill"></i> Identitas &amp; Data Pribadi</h6>
        <div class="cv-grid-2">
          <div class="cv-field"><div class="cv-label">Employee ID</div><div class="cv-value" id="empDrEmployeeId">-</div></div>
          <div class="cv-field"><div class="cv-label">NIK (16 Digit)</div><div class="cv-value" id="empDrNik">-</div></div>
          <div class="cv-field"><div class="cv-label">NPWP (16 Digit)</div><div class="cv-value" id="empDrNpwp">-</div></div>
          <div class="cv-field"><div class="cv-label">Nama Lengkap</div><div class="cv-value" id="empDrFullName">-</div></div>
          <div class="cv-field"><div class="cv-label">Tempat Lahir</div><div class="cv-value" id="empDrBirthPlace">-</div></div>
          <div class="cv-field"><div class="cv-label">Tanggal Lahir</div><div class="cv-value" id="empDrBirthDate">-</div></div>
          <div class="cv-field"><div class="cv-label">Golongan Darah</div><div class="cv-value" id="empDrBloodType">-</div></div>
          <div class="cv-field"><div class="cv-label">Jenis Kelamin</div><div class="cv-value" id="empDrGender">-</div></div>
          <div class="cv-field"><div class="cv-label">Agama</div><div class="cv-value" id="empDrReligion">-</div></div>
          <div class="cv-field"><div class="cv-label">Status Pernikahan</div><div class="cv-value" id="empDrMarital">-</div></div>
          <div class="cv-field"><div class="cv-label">Status PTKP</div><div class="cv-value" id="empDrPtkp">-</div></div>
          <div class="cv-field"><div class="cv-label">Status Karyawan</div><div class="cv-value" id="empDrStatusEmployee">-</div></div>
          <div class="cv-field"><div class="cv-label">Email Pribadi</div><div class="cv-value" id="empDrEmail">-</div></div>
          <div class="cv-field"><div class="cv-label">Email Kantor</div><div class="cv-value" id="empDrWorkingEmail">-</div></div>
          <div class="cv-field"><div class="cv-label">No. Handphone</div><div class="cv-value" id="empDrPhone">-</div></div>
          <div class="cv-field"><div class="cv-label">Lokasi Kerja (Kota)</div><div class="cv-value" id="empDrCity">-</div></div>
          <div class="cv-field"><div class="cv-label">Area Kerja</div><div class="cv-value" id="empDrDistrict">-</div></div>
        </div>
        <div class="cv-field"><div class="cv-label">Alamat KTP</div><div class="cv-value" id="empDrAddress">-</div></div>
        <div class="cv-field mb-0"><div class="cv-label">Alamat Domisili</div><div class="cv-value" id="empDrResidentialAddress">-</div></div>
      </div>

      <div class="cv-section-card">
        <h6><i class="bi bi-wallet2"></i> Bank &amp; BPJS</h6>
        <div class="cv-grid-2">
          <div class="cv-field"><div class="cv-label">Nama Bank</div><div class="cv-value" id="empDrBankName">-</div></div>
          <div class="cv-field"><div class="cv-label">Atas Nama Rekening</div><div class="cv-value" id="empDrBankHolder">-</div></div>
          <div class="cv-field"><div class="cv-label">Nomor Rekening</div><div class="cv-value" id="empDrBankAccount">-</div></div>
          <div class="cv-field"><div class="cv-label">Nomor BPJS Ketenagakerjaan</div><div class="cv-value" id="empDrBpjsTk">-</div></div>
          <div class="cv-field"><div class="cv-label">Nomor BPJS Kesehatan</div><div class="cv-value" id="empDrBpjsKes">-</div></div>
          <div class="cv-field"><div class="cv-label">Outsource Vendor</div><div class="cv-value" id="empDrVendor">-</div></div>
        </div>
      </div>

      <div class="cv-section-card">
        <h6><i class="bi bi-briefcase-fill"></i> Struktur Organisasi &amp; Pekerjaan</h6>
        <div class="cv-grid-2">
          <div class="cv-field"><div class="cv-label">Entitas Perusahaan (Branch)</div><div class="cv-value" id="empDrBranch">-</div></div>
          <div class="cv-field"><div class="cv-label">Divisi</div><div class="cv-value" id="empDrDivision">-</div></div>
          <div class="cv-field"><div class="cv-label">Departemen</div><div class="cv-value" id="empDrDept">-</div></div>
          <div class="cv-field"><div class="cv-label">Cost Center</div><div class="cv-value" id="empDrCostCenter">-</div></div>
          <div class="cv-field"><div class="cv-label">Jabatan (Current)</div><div class="cv-value" id="empDrPosition">-</div></div>
          <div class="cv-field"><div class="cv-label">Job Level</div><div class="cv-value" id="empDrJobLevel">-</div></div>
          <div class="cv-field"><div class="cv-label">Grade</div><div class="cv-value" id="empDrGrade">-</div></div>
          <div class="cv-field"><div class="cv-label">Tanggal Masuk</div><div class="cv-value" id="empDrJoinDate">-</div></div>
          <div class="cv-field"><div class="cv-label">Atasan Langsung</div><div class="cv-value" id="empDrDirectSup">-</div></div>
          <div class="cv-field"><div class="cv-label">Atasan Tidak Langsung</div><div class="cv-value" id="empDrIndirectSup">-</div></div>
        </div>
      </div>

      <div class="cv-section-card">
        <h6><i class="bi bi-file-earmark-text-fill"></i> Kontrak &amp; Riwayat Karier</h6>
        <div class="cv-grid-2">
          <div class="cv-field"><div class="cv-label">Tanggal Berakhir Kontrak</div><div class="cv-value" id="empDrContractEnd">-</div></div>
          <div class="cv-field"><div class="cv-label">Jabatan Sebelumnya</div><div class="cv-value" id="empDrFormerPos">-</div></div>
          <div class="cv-field"><div class="cv-label">Jenis Rotasi / Mutasi</div><div class="cv-value" id="empDrRotationType">-</div></div>
          <div class="cv-field"><div class="cv-label">Tanggal Efektif Mutasi</div><div class="cv-value" id="empDrMutasiDate">-</div></div>
          <div class="cv-field"><div class="cv-label">Nomor SK</div><div class="cv-value" id="empDrNoSk">-</div></div>
          <div class="cv-field"><div class="cv-label">Tanggal Resign</div><div class="cv-value" id="empDrResignDate">-</div></div>
          <div class="cv-field"><div class="cv-label">Tipe Offboarding</div><div class="cv-value" id="empDrOffbType">-</div></div>
          <div class="cv-field"><div class="cv-label">Alasan Offboarding</div><div class="cv-value" id="empDrOffbReason">-</div></div>
          <div class="cv-field cv-field-full"><div class="cv-label">Dokumen Offboarding</div><div class="cv-value" id="empDrOffbDocs">-</div></div>
        </div>
      </div>

      <div class="cv-section-card mb-0">
        <h6><i class="bi bi-chat-square-text-fill"></i> Catatan &amp; Riwayat Sistem</h6>
        <div class="cv-grid-2 mb-3">
          <div class="cv-field"><div class="cv-label">Dibuat Oleh</div><div class="cv-value" id="empDrCreatedBy">-</div></div>
          <div class="cv-field"><div class="cv-label">Dibuat Pada</div><div class="cv-value" id="empDrCreated">-</div></div>
          <div class="cv-field"><div class="cv-label">Terakhir Diupdate</div><div class="cv-value" id="empDrUpdated">-</div></div>
        </div>
        <div class="cv-label mb-1">Catatan Umum</div>
        <div class="cv-value mb-3" id="empDrGeneralNotes" style="white-space:pre-wrap;min-height:40px;padding:10px;background:var(--bg-subtle,#f9fafb);border-radius:8px;border:1px solid var(--color-border,#e5e7eb);">-</div>
        <div class="cv-label mb-1">Catatan HR</div>
        <div class="cv-value" id="empDrNotes" style="white-space:pre-wrap;min-height:40px;padding:10px;background:var(--bg-subtle,#f9fafb);border-radius:8px;border:1px solid var(--color-border,#e5e7eb);">-</div>
      </div>
    </div>

    {{-- MODE: OUTSOURCE --}}
    <div class="drawer-mode-section" id="drawerSectionOutsource">
      <div class="cv-section-card">
        <h6><i class="bi bi-person-badge-fill"></i> Identitas &amp; Data Pribadi</h6>
        <div class="cv-grid-2">
          <div class="cv-field"><div class="cv-label">Employee ID</div><div class="cv-value" id="osDrEmployeeId">-</div></div>
          <div class="cv-field"><div class="cv-label">NIK (16 Digit)</div><div class="cv-value" id="osDrNik">-</div></div>
          <div class="cv-field"><div class="cv-label">Nama Lengkap</div><div class="cv-value" id="osDrFullName">-</div></div>
          <div class="cv-field"><div class="cv-label">Tempat Lahir</div><div class="cv-value" id="osDrBirthPlace">-</div></div>
          <div class="cv-field"><div class="cv-label">Tanggal Lahir</div><div class="cv-value" id="osDrBirthDate">-</div></div>
          <div class="cv-field"><div class="cv-label">Jenis Kelamin</div><div class="cv-value" id="osDrGender">-</div></div>
          <div class="cv-field"><div class="cv-label">Status Pernikahan</div><div class="cv-value" id="osDrMarital">-</div></div>
          <div class="cv-field"><div class="cv-label">Status Karyawan</div><div class="cv-value" id="osDrStatusEmployee">-</div></div>
          <div class="cv-field"><div class="cv-label">Email Pribadi</div><div class="cv-value" id="osDrEmail">-</div></div>
          <div class="cv-field"><div class="cv-label">No. Handphone</div><div class="cv-value" id="osDrPhone">-</div></div>
          <div class="cv-field"><div class="cv-label">Lokasi Kerja</div><div class="cv-value" id="osDrCity">-</div></div>
        </div>
        <div class="cv-field"><div class="cv-label">Alamat KTP</div><div class="cv-value" id="osDrAddress">-</div></div>
        <div class="cv-field mb-0"><div class="cv-label">Alamat Domisili</div><div class="cv-value" id="osDrResidentialAddress">-</div></div>
      </div>

      <div class="cv-section-card">
        <h6><i class="bi bi-wallet2"></i> Bank &amp; BPJS</h6>
        <div class="cv-grid-2">
          <div class="cv-field"><div class="cv-label">Nama Bank</div><div class="cv-value" id="osDrBankName">-</div></div>
          <div class="cv-field"><div class="cv-label">Nomor Rekening</div><div class="cv-value" id="osDrBankAccount">-</div></div>
          <div class="cv-field"><div class="cv-label">BPJS Ketenagakerjaan</div><div class="cv-value" id="osDrBpjsTk">-</div></div>
          <div class="cv-field"><div class="cv-label">BPJS Kesehatan</div><div class="cv-value" id="osDrBpjsKes">-</div></div>
          <div class="cv-field"><div class="cv-label">Vendor Outsource</div><div class="cv-value" id="osDrVendor">-</div></div>
        </div>
      </div>

      <div class="cv-section-card">
        <h6><i class="bi bi-briefcase-fill"></i> Struktur Organisasi &amp; Pekerjaan</h6>
        <div class="cv-grid-2">
          <div class="cv-field"><div class="cv-label">Entitas Perusahaan</div><div class="cv-value" id="osDrBranch">-</div></div>
          <div class="cv-field"><div class="cv-label">Divisi</div><div class="cv-value" id="osDrDivision">-</div></div>
          <div class="cv-field"><div class="cv-label">Departemen</div><div class="cv-value" id="osDrDept">-</div></div>
          <div class="cv-field"><div class="cv-label">Jabatan</div><div class="cv-value" id="osDrPosition">-</div></div>
          <div class="cv-field"><div class="cv-label">Job Level</div><div class="cv-value" id="osDrJobLevel">-</div></div>
          <div class="cv-field"><div class="cv-label">Tanggal Masuk</div><div class="cv-value" id="osDrJoinDate">-</div></div>
          <div class="cv-field"><div class="cv-label">Atasan Langsung</div><div class="cv-value" id="osDrDirectSup">-</div></div>
          <div class="cv-field"><div class="cv-label">Atasan Tidak Langsung</div><div class="cv-value" id="osDrIndirectSup">-</div></div>
        </div>
      </div>

      <div class="cv-section-card mb-0">
        <h6><i class="bi bi-chat-square-text-fill"></i> Catatan &amp; Metadata</h6>
        <div class="cv-grid-2">
          <div class="cv-field"><div class="cv-label">Dibuat Oleh</div><div class="cv-value" id="osDrCreatedBy">-</div></div>
          <div class="cv-field"><div class="cv-label">Dibuat Pada</div><div class="cv-value" id="osDrCreated">-</div></div>
          <div class="cv-field"><div class="cv-label">Terakhir Diupdate</div><div class="cv-value" id="osDrUpdated">-</div></div>
        </div>
        <div class="cv-label mb-1 mt-3">Catatan Umum</div>
        <div class="cv-value mb-3" id="osDrGeneralNotes" style="white-space:pre-wrap;min-height:40px;padding:10px;background:var(--bg-subtle,#f9fafb);border-radius:8px;border:1px solid var(--color-border,#e5e7eb);">-</div>
        <div class="cv-label mb-1">Catatan HR</div>
        <div class="cv-value" id="osDrNotes" style="white-space:pre-wrap;min-height:40px;padding:10px;background:var(--bg-subtle,#f9fafb);border-radius:8px;border:1px solid var(--color-border,#e5e7eb);">-</div>
      </div>
    </div>
  </div>

  <div class="drawer-footer" id="drawerFooterCandidate" style="flex-direction:column;gap:8px">
    <div class="status-action-group">
      <button class="btn-status accept" id="btnAccept" type="button"><i class="bi bi-check-lg"></i> Accept</button>
      <button class="btn-status hold" id="btnHold" type="button"><i class="bi bi-pause-fill"></i> Hold</button>
      <button class="btn-status blacklist" id="btnBlacklist" type="button"><i class="bi bi-slash-circle"></i> Blacklist</button>
    </div>
  </div>

  <div class="drawer-footer" id="drawerFooterEntity" style="display:none;flex-direction:column;gap:8px">
    <div id="drawerContractActionsWrap" style="display:none;width:100%">
      <button class="btn btn-sm w-100 fw-semibold text-white" id="btnDrawerPromoteProbation" type="button" style="background:#005BAC;border:none;border-radius:8px;padding:8px 12px;font-size:12.5px"><i class="bi bi-person-up me-1"></i>Ajukan Onboarding Probation</button>
    </div>
    <div class="status-action-group">
      <button class="btn-status accept" id="btnDrawerEntityEdit" type="button"><i class="bi bi-pencil-fill"></i> Edit</button>
      <button class="btn-status blacklist" id="btnDrawerEntityDelete" type="button"><i class="bi bi-trash3-fill"></i> Hapus</button>
    </div>
  </div>
</div>