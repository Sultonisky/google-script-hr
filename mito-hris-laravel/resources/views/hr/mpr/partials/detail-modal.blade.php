<div class="modal fade hr-detail-modal" id="modalMprDetail" tabindex="-1" aria-labelledby="modalMprDetailLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">
            <!-- Header -->
            <div class="modal-header">
                <div class="d-flex align-items-center gap-3 min-w-0">
                    <div class="detail-header-icon"><i class="bi bi-file-earmark-text"></i></div>
                    <div class="min-w-0">
                        <h5 class="modal-title" id="modalMprDetailLabel">Detail Manpower Request</h5>
                        <span class="detail-number" id="detailMprNumber">-</span>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <!-- Body -->
            <div class="modal-body" id="modalMprDetailBody">
                <!-- Loading State -->
                <div class="text-center py-5" id="detailLoading">
                    <div class="spinner-border text-primary" role="status"></div>
                    <div class="mt-2 text-muted small">Memuat data MPR...</div>
                </div>

                <!-- Error State -->
                <div class="text-center text-danger py-5 d-none" id="detailError">
                    <i class="bi bi-exclamation-triangle-fill fs-2 d-block mb-2"></i>
                    <div id="detailErrorMessage">Gagal memuat detail MPR.</div>
                </div>

                <!-- Content -->
                <div id="detailContent" class="d-none">
                    <!-- Ringkasan -->
                    <div class="detail-summary">
                        <div class="min-w-0">
                            <div class="detail-eyebrow">Posisi yang Diminta</div>
                            <div class="detail-position" id="detPosition">-</div>
                            <div class="detail-meta" id="detDeptDiv">-</div>
                        </div>
                        <div class="detail-qty">
                            <span id="detQuantity">-</span>
                            <small>Orang</small>
                        </div>
                    </div>

                    <!-- Informasi Pemohon -->
                    <section class="detail-section">
                        <h6 class="detail-section-title"><i class="bi bi-person-badge"></i>Informasi Pemohon</h6>
                        <div class="row g-3">
                            <div class="col-sm-6 detail-field">
                                <label>Pemohon (Manager)</label>
                                <div class="detail-value" id="detManagerName">-</div>
                                <div class="detail-meta" id="detManagerEmail">-</div>
                                <div class="detail-meta" id="detRequestorPosition">-</div>
                            </div>
                            <div class="col-sm-6 detail-field">
                                <label>Entitas / Perusahaan</label>
                                <div class="detail-value" id="detEntity">-</div>
                                <div class="detail-meta" id="detCreatedBy">Diajukan: -</div>
                            </div>
                        </div>
                    </section>

                    <!-- Detail Posisi -->
                    <section class="detail-section">
                        <h6 class="detail-section-title"><i class="bi bi-briefcase"></i>Detail Posisi & Kebutuhan</h6>
                        <div class="row g-3">
                            <div class="col-sm-6 detail-field">
                                <label>Level Jabatan</label>
                                <div class="detail-value" id="detJobLevel">-</div>
                            </div>
                            <div class="col-sm-6 detail-field">
                                <label>Status Kepegawaian</label>
                                <div class="detail-value" id="detEmpType">-</div>
                            </div>
                            <div class="col-sm-6 detail-field">
                                <label>Lokasi Penempatan</label>
                                <div class="detail-value" id="detLocation">-</div>
                            </div>
                            <div class="col-sm-6 detail-field">
                                <label>Target Join Date</label>
                                <div class="detail-value" id="detJoinDate">-</div>
                            </div>
                        </div>
                    </section>

                    <!-- Waktu Kerja & Benefits -->
                    <section class="detail-section">
                        <h6 class="detail-section-title"><i class="bi bi-clock"></i>Waktu Kerja & Benefits</h6>
                        <div class="row g-3">
                            <div class="col-sm-6 detail-field">
                                <label>Hari Kerja</label>
                                <div class="detail-value" id="detWorkingDays">-</div>
                            </div>
                            <div class="col-sm-6 detail-field">
                                <label>Jam Kerja</label>
                                <div class="detail-value" id="detWorkingHours">-</div>
                            </div>
                            <div class="col-12 detail-field" id="wrapShiftDetail">
                                <label>Detail Shift</label>
                                <div class="detail-value" id="detShiftDetail">-</div>
                            </div>
                            <div class="col-12 detail-field">
                                <label>Benefits / Tunjangan</label>
                                <div class="detail-value" id="detBenefits">-</div>
                            </div>
                        </div>
                    </section>

                    <!-- Kualifikasi Kandidat -->
                    <section class="detail-section">
                        <h6 class="detail-section-title"><i class="bi bi-person-check"></i>Kualifikasi Kandidat</h6>
                        <div class="row g-3">
                            <div class="col-sm-6 detail-field">
                                <label>Latar Belakang Pendidikan</label>
                                <div class="detail-value" id="detEducation">-</div>
                            </div>
                            <div class="col-sm-6 detail-field">
                                <label>Pengalaman Kerja</label>
                                <div class="detail-value" id="detExperience">-</div>
                            </div>
                            <div class="col-sm-6 detail-field" id="wrapSkills">
                                <label>Skills & Kompetensi</label>
                                <div class="detail-value" id="detSkills">-</div>
                            </div>
                            <div class="col-sm-6 detail-field" id="wrapLanguages">
                                <label>Bahasa</label>
                                <div class="detail-value" id="detLanguages">-</div>
                            </div>
                            <div class="col-12 detail-field" id="wrapIndustryRef">
                                <label>Referensi Industri Sejenis</label>
                                <div class="detail-value" id="detIndustryRef">-</div>
                            </div>
                        </div>
                    </section>

                    <!-- Alasan Permintaan -->
                    <section class="detail-section">
                        <h6 class="detail-section-title"><i class="bi bi-info-circle"></i>Alasan Permintaan</h6>
                        <div class="row g-3">
                            <div class="col-12 detail-field">
                                <label>Alasan</label>
                                <div class="detail-value" id="detReason">-</div>
                            </div>
                            <div class="col-12 detail-field" id="wrapReplacement">
                                <label>Menggantikan Karyawan</label>
                                <div class="detail-value" id="detReplacementFor">-</div>
                            </div>
                        </div>
                    </section>

                    <!-- Kualifikasi & Uraian Tugas -->
                    <section class="detail-section">
                        <h6 class="detail-section-title"><i class="bi bi-list-task"></i>Kualifikasi & Uraian Tugas</h6>
                        <div class="row g-3">
                            <div class="col-12 detail-field">
                                <label>Kualifikasi & Persyaratan</label>
                                <div class="mpr-markdown-content" id="detRequirements">-</div>
                            </div>
                            <div class="col-12 detail-field">
                                <label>Uraian Tugas & Tanggung Jawab</label>
                                <div class="mpr-markdown-content" id="detJobDesc">-</div>
                            </div>
                            <div class="col-12 detail-field" id="wrapKeyResults">
                                <label>Key Results / Target Posisi Ini</label>
                                <div class="detail-value" id="detKeyResults">-</div>
                            </div>
                            <div class="col-12 detail-field" id="wrapSpecialNotes">
                                <label>Catatan Khusus MPR</label>
                                <div class="detail-value" id="detSpecialNotes">-</div>
                            </div>
                        </div>
                    </section>

                    <!-- Tanda Tangan (urutan sama dengan PDF: 3 + 2) -->
                    <section class="detail-section">
                        <h6 class="detail-section-title"><i class="bi bi-pen"></i>Tanda Tangan</h6>
                        <div class="row row-cols-2 row-cols-md-3 g-2 justify-content-center">
                            <div class="col">
                                <div class="mpr-sign-card">
                                    <div class="mpr-sign-role">Diajukan oleh (Pemohon)</div>
                                    <div class="mpr-sign-name" id="detSignRequestorName">-</div>
                                    <div class="mpr-sign-position" id="detSignRequestorPosition">-</div>
                                </div>
                            </div>
                            <div class="col">
                                <div class="mpr-sign-card">
                                    <div class="mpr-sign-role">Diperiksa oleh (HRD)</div>
                                    <div class="mpr-sign-name">Hisar Hesti</div>
                                    <div class="mpr-sign-position">HR Manager / Recruiter</div>
                                </div>
                            </div>
                            <div class="col">
                                <div class="mpr-sign-card">
                                    <div class="mpr-sign-role">Disetujui oleh (Divisi)</div>
                                    <div class="mpr-sign-name" id="detApprovalDivision">( ........................................ )</div>
                                    <div class="mpr-sign-position">Pimpinan Divisi</div>
                                </div>
                            </div>
                            <div class="col">
                                <div class="mpr-sign-card">
                                    <div class="mpr-sign-role">Disetujui oleh (COO)</div>
                                    <div class="mpr-sign-name">Frans Arsianto</div>
                                    <div class="mpr-sign-position">COO</div>
                                </div>
                            </div>
                            <div class="col">
                                <div class="mpr-sign-card">
                                    <div class="mpr-sign-role">Disetujui oleh (CEO)</div>
                                    <div class="mpr-sign-name">Jacksen Lie</div>
                                    <div class="mpr-sign-position">CEO</div>
                                </div>
                            </div>
                        </div>
                    </section>
                </div>
            </div>

            <!-- Footer -->
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Tutup</button>
                @can('update_mpr')
                    <button type="button" id="btnEditMpr" class="btn btn-warning btn-sm">
                        <i class="bi bi-pencil-square me-1"></i> Edit MPR
                    </button>
                @endcan
                <a id="btnModalDownloadPdf" href="#" target="_blank" class="btn btn-primary btn-sm">
                    <i class="bi bi-download me-1"></i> Unduh PDF
                </a>
            </div>
        </div>
    </div>
</div>
