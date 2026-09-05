{{-- ═══ ADD / EDIT CERTIFICATION MODAL ═══ --}}
<div class="modal fade" id="addCertificationModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title" id="addCertificationModalLabel">Tambah Sertifikasi Baru</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="certificationForm" novalidate>
                @csrf
                <input type="hidden" name="_method" id="certificationFormMethod" value="POST">
                <input type="hidden" name="certification_id" id="certificationFormId" value="">
                <div class="modal-body">
                    <div class="asset-section-title"><i class="bi bi-info-circle"></i> Informasi Umum</div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Kode Sertifikasi</label>
                            <div class="input-group">
                                <input type="text" class="form-control" name="cert_code" id="certificationFormCode" placeholder="Kosongkan untuk auto-generate" maxlength="50">
                                <button class="btn btn-outline-info" type="button" id="btnCertFormGenerateCode"><i class="bi bi-magic"></i></button>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Jenis *</label>
                            <select class="form-select" name="cert_type" id="certificationFormType" required>
                                <option value="">-- Pilih --</option>
                                @foreach(\App\Enums\CertType::cases() as $type)
                                    <option value="{{ $type->value }}">{{ $type->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Nama Sertifikasi *</label>
                            <input type="text" class="form-control" name="name" id="certificationFormName" required maxlength="255">
                        </div>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-12"><label class="form-label fw-semibold">Deskripsi</label>
                            <textarea class="form-control" name="description" id="certificationFormDesc" rows="2"></textarea>
                        </div>
                    </div>
                    <div class="asset-section-title mt-3"><i class="bi bi-person-badge"></i> Informasi Karyawan</div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Cari Karyawan *</label>
                            <input type="text" class="form-control" id="certificationEmployeeSearch" autocomplete="off"
                                placeholder="Ketik nama / ID karyawan...">
                            <input type="hidden" name="employee_id" id="certificationFormEmployeeId">
                            <input type="hidden" name="employee_name" id="certificationFormEmployeeName">
                            <div class="list-group mt-2 d-none" id="certificationEmployeeResults" style="max-height:200px;overflow:auto;position:absolute;z-index:1050;width:calc(100% - 2rem);"></div>
                            <div id="certificationEmployeeError" class="text-danger small mt-1 d-none"></div>
                            <div id="certificationSelectedEmployee" class="d-none mt-2"></div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Division</label>
                            <input type="text" class="form-control" name="division" id="certificationFormDivision" readonly maxlength="255">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Department</label>
                            <input type="text" class="form-control" name="department" id="certificationFormDepartment" readonly maxlength="255">
                        </div>
                    </div>

                    <div class="asset-section-title mt-3"><i class="bi bi-building-check"></i> Informasi Penerbit</div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6"><label class="form-label fw-semibold">Lembaga Penerbit *</label>
                            <input type="text" class="form-control" name="issuing_organization" id="certificationFormOrg" required maxlength="255"></div>
                        <div class="col-md-6"><label class="form-label fw-semibold">Nomor Sertifikat</label>
                            <input type="text" class="form-control" name="certificate_number" id="certificationFormNumber" maxlength="255"></div>
                    </div>
                    <div class="asset-section-title mt-3"><i class="bi bi-calendar-check"></i> Tanggal</div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-4"><label class="form-label fw-semibold">Tanggal Terbit *</label>
                            <input type="date" class="form-control" name="issue_date" id="certificationFormIssueDate" required></div>
                        <div class="col-md-4"><label class="form-label fw-semibold">Tanggal Kedaluwarsa</label>
                            <input type="date" class="form-control" name="expiry_date" id="certificationFormExpiryDate"></div>
                        <div class="col-md-4"><label class="form-label fw-semibold">Status</label>
                            <select class="form-select" name="status" id="certificationFormStatus">
                                <option value="">Auto</option>
                                @foreach(\App\Enums\CertStatus::cases() as $s)
                                    <option value="{{ $s->value }}">{{ $s->label() }}</option>
                                @endforeach
                            </select></div>
                    </div>
                    <div class="row g-3">
                        <div class="col-12"><label class="form-label fw-semibold">Catatan</label>
                            <textarea class="form-control" name="notes" id="certificationFormNotes" rows="2"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary" id="btnCertificationFormSubmit">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>


{{-- DELETE CERTIFICATION CONFIRMATION MODAL --}}
<div class="modal fade" id="deleteCertificationModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title text-danger"><i class="bi bi-trash me-1"></i>Hapus Sertifikasi</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="mb-3">Tindakan ini <strong>menghapus permanen</strong> sertifikasi dari database dan tidak dapat dibatalkan.</p>
                <div class="border rounded p-3 bg-light">
                    <div class="row g-2">
                        <div class="col-4 text-muted small">Kode</div>
                        <div class="col-8 fw-semibold id-mono" id="deleteCertCode">—</div>
                        <div class="col-4 text-muted small">Nama</div>
                        <div class="col-8 fw-semibold" id="deleteCertName">—</div>
                        <div class="col-4 text-muted small">Karyawan</div>
                        <div class="col-8" id="deleteCertEmployee">—</div>
                    </div>
                </div>
                <input type="hidden" id="deleteCertId">
                <div id="deleteCertError" class="alert alert-danger mt-3 mb-0" style="display:none;"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-danger" id="btnDeleteCertConfirm"><i class="bi bi-trash me-1"></i>Hapus Permanen</button>
            </div>
        </div>
    </div>
</div>
{{-- ═══ VIEW CERTIFICATION MODAL ═══ --}}
<div class="modal fade" id="viewCertificationModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title" id="viewCertificationModalLabel">Detail Sertifikasi</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="viewCertificationBody">
                <div class="text-center text-muted py-4">Memuat...</div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>
