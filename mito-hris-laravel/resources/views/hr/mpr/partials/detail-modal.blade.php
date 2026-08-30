<div class="modal fade" id="modalMprDetail" tabindex="-1" aria-labelledby="modalMprDetailLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white">
                <div>
                    <h5 class="modal-title fw-bold" id="modalMprDetailLabel"><i class="bi bi-file-earmark-text me-2"></i>
                        Detail Manpower Request</h5>
                    <small class="text-white" id="detailMprNumber">-</small>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>
            <div class="modal-body p-4" id="modalMprDetailBody">
                <div class="text-center py-5" id="detailLoading">
                    <div class="spinner-border text-primary" role="status"></div>
                    <div class="mt-2 text-muted small">Memuat data MPR...</div>
                </div>
                <div id="detailContent" class="d-none">
                    <div class="card bg-light border-0 mb-3 p-3 rounded-3">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="small text-uppercase text-muted fw-semibold mb-2">Pemohon (Manager)</div>
                                <div class="fw-semibold text-dark" id="detManagerName">-</div>
                                <div class="small text-muted" id="detManagerEmail">-</div>
                            </div>
                            <div class="col-md-6">
                                <div class="small text-uppercase text-muted fw-semibold mb-2">Entitas / Perusahaan</div>
                                <div class="fw-semibold text-dark" id="detCompany">-</div>
                                <div class="small text-muted" id="detBranch">-</div>
                                <div class="small text-muted" id="detCreatedBy">Diajukan: -</div>
                            </div>
                        </div>
                    </div>

                    <h6 class="fw-bold text-primary mb-2">Detail Posisi & Kebutuhan</h6>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <div class="small text-uppercase text-muted fw-semibold mb-2">Posisi / Jabatan</div>
                            <div class="text-primary fw-bold" id="detPosition">-</div>
                        </div>
                        <div class="col-md-6">
                            <div class="small text-uppercase text-muted fw-semibold mb-2">Departemen / Divisi</div>
                            <div class="fw-semibold text-dark" id="detDeptDiv">-</div>
                        </div>
                        <div class="col-md-4">
                            <div class="small text-uppercase text-muted fw-semibold mb-2">Level Jabatan</div>
                            <div class="fw-semibold text-dark" id="detJobLevel">-</div>
                        </div>
                        <div class="col-md-4">
                            <div class="small text-uppercase text-muted fw-semibold mb-2">Status Kepegawaian</div>
                            <div class="fw-semibold text-dark" id="detEmpType">-</div>
                        </div>
                        <div class="col-md-4">
                            <div class="small text-uppercase text-muted fw-semibold mb-2">Lokasi Penempatan</div>
                            <div class="fw-semibold text-dark" id="detLocation">-</div>
                        </div>
                        <div class="col-md-6">
                            <div class="small text-uppercase text-muted fw-semibold mb-2">Jumlah Kebutuhan</div>
                            <div class="fw-semibold text-dark"><span class="badge bg-primary fs-6"
                                    id="detQuantity">-</span> Orang</div>
                        </div>
                        <div class="col-md-6">
                            <div class="small text-uppercase text-muted fw-semibold mb-2">Target Tanggal Masuk (Join
                                Date)</div>
                            <div class="fw-bold text-dark" id="detJoinDate">-</div>
                        </div>
                    </div>

                    <h6 class="fw-bold text-primary mb-2">Alasan Permintaan</h6>
                    <div class="bg-light p-3 rounded-3 mb-3 border">
                        <div class="small text-uppercase text-muted fw-semibold mb-2">Alasan</div>
                        <div class="mb-2 fw-semibold text-dark" id="detReason">-</div>
                        <div id="wrapReplacement" class="d-none">
                            <div class="small text-uppercase text-muted fw-semibold mb-2">Menggantikan Karyawan</div>
                            <div class="fw-semibold text-dark" id="detReplacementFor">-</div>
                        </div>
                    </div>

                    <h6 class="fw-bold text-primary mb-2">Kualifikasi & Deskripsi</h6>
                    <div class="row g-3 mb-3">
                        <div class="col-12">
                            <div class="small text-uppercase text-muted fw-semibold mb-2">Kualifikasi Kandidat</div>
                            <div class="p-2 border rounded bg-white small mpr-markdown-content" id="detRequirements"
                                style="min-height:60px;">-</div>
                        </div>
                        <div class="col-12">
                            <div class="small text-uppercase text-muted fw-semibold mb-2">Uraian Tugas & Tanggung Jawab
                            </div>
                            <div class="p-2 border rounded bg-white small mpr-markdown-content" id="detJobDesc"
                                style="min-height:60px;">-</div>
                        </div>
                        <div class="col-12" id="wrapNotes">
                            <div class="small text-uppercase text-muted fw-semibold mb-2">Catatan Tambahan</div>
                            <div class="p-2 border rounded bg-white small mpr-markdown-content" id="detNotes">-</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light border-top">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Tutup</button>
                @can('update_mpr')
                    <button type="button" id="btnEditMpr" class="btn btn-primary btn-sm fw-semibold">
                        <i class="bi bi-pencil-square me-1"></i> Edit MPR
                    </button>
                @endcan
                <a id="btnModalDownloadPdf" href="#" target="_blank"
                    class="btn btn-danger btn-sm fw-semibold">
                    <i class="bi bi-file-earmark-pdf-fill me-1"></i> Unduh PDF Resmi
                </a>
            </div>
        </div>
    </div>
</div>
