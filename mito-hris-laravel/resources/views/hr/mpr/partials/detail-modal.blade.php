<div class="modal fade" id="modalMprDetail" tabindex="-1" aria-labelledby="modalMprDetailLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">
            <!-- Header -->
            <div class="modal-header bg-primary text-white">
                <div>
                    <h5 class="modal-title fw-bold mb-1" id="modalMprDetailLabel">
                        <i class="bi bi-file-earmark-text me-2"></i>Detail Manpower Request
                    </h5>
                    <small class="text-white opacity-90" id="detailMprNumber">-</small>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <!-- Body -->
            <div class="modal-body p-0" id="modalMprDetailBody">
                <!-- Loading State -->
                <div class="text-center py-5" id="detailLoading">
                    <div class="spinner-border text-primary" role="status"></div>
                    <div class="mt-2 text-muted small">Memuat data MPR...</div>
                </div>

                <!-- Content -->
                <div id="detailContent" class="d-none">
                    <!-- Section: Info Pemohon & Entitas -->
                    <div class="p-4 border-bottom">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="small text-uppercase text-muted fw-semibold mb-1">Pemohon (Manager)</label>
                                <div class="fw-semibold" id="detManagerName">-</div>
                                <div class="small text-muted" id="detManagerEmail">-</div>
                                <div class="small text-muted" id="detRequestorPosition">-</div>
                            </div>
                            <div class="col-md-6">
                                <label class="small text-uppercase text-muted fw-semibold mb-1">Entitas / Perusahaan</label>
                                <div class="fw-semibold" id="detEntity">-</div>
                                <div class="small text-muted mt-1" id="detCreatedBy">Diajukan: -</div>
                            </div>
                        </div>
                    </div>

                    <!-- Section: Detail Posisi -->
                    <div class="p-4 border-bottom">
                        <h6 class="fw-bold text-primary mb-3"><i class="bi bi-briefcase me-2"></i>Detail Posisi & Kebutuhan</h6>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="small text-uppercase text-muted fw-semibold mb-1">Posisi / Jabatan</label>
                                <div class="text-primary fw-bold fs-6" id="detPosition">-</div>
                            </div>
                            <div class="col-md-6">
                                <label class="small text-uppercase text-muted fw-semibold mb-1">Departemen / Divisi</label>
                                <div class="fw-semibold" id="detDeptDiv">-</div>
                            </div>
                            <div class="col-md-4">
                                <label class="small text-uppercase text-muted fw-semibold mb-1">Level Jabatan</label>
                                <div class="fw-semibold" id="detJobLevel">-</div>
                            </div>
                            <div class="col-md-4">
                                <label class="small text-uppercase text-muted fw-semibold mb-1">Status Kepegawaian</label>
                                <div class="fw-semibold" id="detEmpType">-</div>
                            </div>
                            <div class="col-md-4">
                                <label class="small text-uppercase text-muted fw-semibold mb-1">Lokasi Penempatan</label>
                                <div class="fw-semibold" id="detLocation">-</div>
                            </div>
                            <div class="col-md-6">
                                <label class="small text-uppercase text-muted fw-semibold mb-1">Jumlah Kebutuhan</label>
                                <div><span class="badge bg-primary" style="font-size: 15px; padding: 6px 12px;" id="detQuantity">-</span> <span class="small text-muted">Orang</span></div>
                            </div>
                            <div class="col-md-6">
                                <label class="small text-uppercase text-muted fw-semibold mb-1">Target Join Date</label>
                                <div class="fw-semibold" id="detJoinDate">-</div>
                            </div>
                        </div>
                    </div>

                    <!-- Section: Waktu Kerja & Benefits -->
                    <div class="p-4 border-bottom">
                        <h6 class="fw-bold text-primary mb-3"><i class="bi bi-clock me-2"></i>Waktu Kerja & Benefits</h6>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="small text-uppercase text-muted fw-semibold mb-1">Hari Kerja</label>
                                <div class="fw-semibold" id="detWorkingDays">-</div>
                            </div>
                            <div class="col-md-6">
                                <label class="small text-uppercase text-muted fw-semibold mb-1">Jam Kerja</label>
                                <div class="fw-semibold" id="detWorkingHours">-</div>
                            </div>
                            <div class="col-12" id="wrapShiftDetail">
                                <label class="small text-uppercase text-muted fw-semibold mb-1">Detail Shift</label>
                                <div class="fw-semibold" id="detShiftDetail">-</div>
                            </div>
                            <div class="col-12">
                                <label class="small text-uppercase text-muted fw-semibold mb-1">Benefits / Tunjangan</label>
                                <div class="fw-semibold" id="detBenefits">-</div>
                            </div>
                        </div>
                    </div>

                    <!-- Section: Kualifikasi Kandidat -->
                    <div class="p-4 border-bottom">
                        <h6 class="fw-bold text-primary mb-3"><i class="bi bi-person-check me-2"></i>Kualifikasi Kandidat</h6>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="small text-uppercase text-muted fw-semibold mb-1">Latar Belakang Pendidikan</label>
                                <div class="fw-semibold" id="detEducation">-</div>
                            </div>
                            <div class="col-md-6">
                                <label class="small text-uppercase text-muted fw-semibold mb-1">Pengalaman Kerja</label>
                                <div class="fw-semibold" id="detExperience">-</div>
                            </div>
                            <div class="col-md-6" id="wrapSkills">
                                <label class="small text-uppercase text-muted fw-semibold mb-1">Skills & Kompetensi</label>
                                <div class="fw-semibold" id="detSkills">-</div>
                            </div>
                            <div class="col-md-6" id="wrapLanguages">
                                <label class="small text-uppercase text-muted fw-semibold mb-1">Bahasa</label>
                                <div class="fw-semibold" id="detLanguages">-</div>
                            </div>
                            <div class="col-12" id="wrapIndustryRef">
                                <label class="small text-uppercase text-muted fw-semibold mb-1">Referensi Industri Sejenis</label>
                                <div class="fw-semibold" id="detIndustryRef">-</div>
                            </div>
                        </div>
                    </div>

                    <!-- Section: Alasan Permintaan -->
                    <div class="p-4 border-bottom">
                        <h6 class="fw-bold text-primary mb-3"><i class="bi bi-info-circle me-2"></i>Alasan Permintaan</h6>
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="small text-uppercase text-muted fw-semibold mb-1">Alasan</label>
                                <div class="fw-semibold" id="detReason">-</div>
                            </div>
                            <div class="col-12" id="wrapReplacement">
                                <label class="small text-uppercase text-muted fw-semibold mb-1">Menggantikan Karyawan</label>
                                <div class="fw-semibold" id="detReplacementFor">-</div>
                            </div>
                        </div>
                    </div>

                    <!-- Section: Kualifikasi & Uraian Tugas -->
                    <div class="p-4 border-bottom">
                        <h6 class="fw-bold text-primary mb-3"><i class="bi bi-list-task me-2"></i>Kualifikasi & Uraian Tugas</h6>
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="small text-uppercase text-muted fw-semibold mb-2">Kualifikasi & Persyaratan</label>
                                <div class="p-3 border rounded small mpr-markdown-content" id="detRequirements" style="min-height:60px; line-height: 1.6;">-</div>
                            </div>
                            <div class="col-12">
                                <label class="small text-uppercase text-muted fw-semibold mb-2">Uraian Tugas & Tanggung Jawab</label>
                                <div class="p-3 border rounded small mpr-markdown-content" id="detJobDesc" style="min-height:60px; line-height: 1.6;">-</div>
                            </div>
                            <div class="col-12" id="wrapKeyResults">
                                <label class="small text-uppercase text-muted fw-semibold mb-1">Key Results / Target Posisi Ini</label>
                                <div class="fw-semibold" id="detKeyResults">-</div>
                            </div>
                            <div class="col-12" id="wrapSpecialNotes">
                                <label class="small text-uppercase text-muted fw-semibold mb-1">Catatan Khusus MPR</label>
                                <div class="fw-semibold" id="detSpecialNotes">-</div>
                            </div>
                        </div>
                    </div>

                    <!-- Section: Tanda Tangan -->
                    <div class="p-4">
                        <h6 class="fw-bold text-primary mb-3"><i class="bi bi-pen me-2"></i>Tanda Tangan</h6>
                        <!-- Row 1: 3 kolom -->
                        <div class="row text-center g-3 mb-4">
                            <div class="col-4">
                                <div class="small fw-semibold text-muted mb-4">Diajukan oleh (Pemohon)</div>
                                <div class="fw-semibold d-inline-block border-top border-dark pt-2 px-3" style="min-width: 140px;" id="detSignRequestorName">-</div>
                                <div class="small text-muted mt-1" id="detSignRequestorPosition">-</div>
                            </div>
                            <div class="col-4">
                                <div class="small fw-semibold text-muted mb-4">Diperiksa oleh (HRD)</div>
                                <div class="fw-semibold d-inline-block border-top border-dark pt-2 px-3" style="min-width: 140px;">Hisar Hesti</div>
                                <div class="small text-muted mt-1">HR Manager / Recruiter</div>
                            </div>
                            <div class="col-4">
                                <div class="small fw-semibold text-muted mb-4">Disetujui oleh (Divisi)</div>
                                <div class="fw-semibold d-inline-block border-top border-dark pt-2 px-3" style="min-width: 140px;" id="detApprovalDivision">( ........................................  )</div>
                                <div class="small text-muted mt-1">Pimpinan Divisi</div>
                            </div>
                        </div>
                        <!-- Row 2: 2 kolom centered -->
                        <div class="row text-center g-3 justify-content-center">
                            <div class="col-4">
                                <div class="small fw-semibold text-muted mb-4">Disetujui oleh (COO)</div>
                                <div class="fw-semibold d-inline-block border-top border-dark pt-2 px-3" style="min-width: 140px;">Frans Arsianto</div>
                                <div class="small text-muted mt-1">COO</div>
                            </div>
                            <div class="col-4">
                                <div class="small fw-semibold text-muted mb-4">Disetujui oleh (CEO)</div>
                                <div class="fw-semibold d-inline-block border-top border-dark pt-2 px-3" style="min-width: 140px;">Jacksen Lie</div>
                                <div class="small text-muted mt-1">CEO</div>
                            </div>
                        </div>
                    </div>
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
