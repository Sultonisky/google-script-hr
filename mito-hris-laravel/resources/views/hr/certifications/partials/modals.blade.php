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
                <div class="modal-body certification-form-body">
                    <div class="cert-form-section">
                        <div class="asset-section-title"><i class="bi bi-info-circle"></i> Identitas Sertifikasi</div>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-12">
                            <label class="form-label fw-semibold">Klasifikasi <span class="text-danger">*</span></label>
                            <select class="form-select" name="cert_type" id="certificationFormType" required>
                                <option value="">-- Pilih --</option>
                                @foreach (\App\Enums\CertType::cases() as $type)
                                    <option value="{{ $type->value }}">{{ $type->label() }} - {{ $type->description() }}</option>
                                @endforeach
                            </select>
                            <div class="form-text">Pilih sesuai objek sertifikasi: produk, perusahaan, atau keselamatan kerja.</div>
                        </div>
                    </div>
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Kode Sertifikasi</label>
                            <div class="input-group">
                                <input type="text" class="form-control" name="cert_code" id="certificationFormCode"
                                    placeholder="Kosongkan untuk auto-generate" maxlength="50">
                                <button class="btn btn-outline-info" type="button" id="btnCertFormGenerateCode"><i
                                        class="bi bi-magic"></i></button>
                            </div>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label fw-semibold">Nama Sertifikasi <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="name" id="certificationFormName"
                                required maxlength="255">
                        </div>
                    </div>
                    <div class="cert-form-section d-none" id="certificationProductFields">
                        <div class="asset-section-title"><i class="bi bi-box-seam"></i> Informasi Produk</div>
                    </div>
                    <div class="row g-3 mb-4 d-none" id="certificationProductFieldsRow">
                        <div class="col-md-8">
                            <label class="form-label fw-semibold" id="certificationProductScopeLabel">Produk / Scope</label>
                            <input type="text" class="form-control" name="product_scope" id="certificationFormScope"
                                maxlength="255" placeholder="Contoh: Rice Cooker atau Quality Management System">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Brand</label>
                            <input type="text" class="form-control" name="brand" id="certificationFormBrand"
                                maxlength="100" placeholder="Contoh: MITO">
                        </div>
                    </div>
                    <div class="cert-form-section d-none" id="certificationCompanyFields">
                        <div class="asset-section-title"><i class="bi bi-buildings"></i> Informasi Perusahaan</div>
                    </div>
                    <div class="row g-3 mb-4 d-none" id="certificationCompanyFieldsRow">
                        <div class="col-12">
                            <label class="form-label fw-semibold">Scope Perusahaan</label>
                            <input type="text" class="form-control" name="company_scope" id="certificationFormCompanyScope"
                                maxlength="255" placeholder="Contoh: Quality Management System">
                            <div class="form-text">Gunakan scope sistem atau area perusahaan, bukan nama produk/brand.</div>
                        </div>
                    </div>
                    <div class="row g-3 mb-4">
                        <div class="col-12"><label class="form-label fw-semibold">Deskripsi</label>
                            <textarea class="form-control" name="description" id="certificationFormDesc" rows="2"></textarea>
                        </div>
                    </div>
                    <div class="cert-form-section"><div class="asset-section-title"><i class="bi bi-building-check"></i> Penerbit</div></div>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6"><label class="form-label fw-semibold">Lembaga Penerbit <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="issuing_organization"
                                id="certificationFormOrg" required maxlength="255">
                        </div>
                        <div class="col-md-6"><label class="form-label fw-semibold">Nomor Sertifikat</label>
                            <input type="text" class="form-control" name="certificate_number"
                                id="certificationFormNumber" maxlength="255">
                        </div>
                    </div>
                    <div class="cert-form-section"><div class="asset-section-title"><i class="bi bi-calendar-check"></i> Masa Berlaku</div></div>
                    <div class="row g-3 mb-4">
                        <div class="col-md-4"><label class="form-label fw-semibold">Tanggal Terbit <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="issue_date"
                                id="certificationFormIssueDate" required>
                        </div>
                        <div class="col-md-4"><label class="form-label fw-semibold">Tanggal Kedaluwarsa</label>
                            <input type="date" class="form-control" name="expiry_date"
                                id="certificationFormExpiryDate">
                        </div>
                        <div class="col-md-4"><label class="form-label fw-semibold">Status</label>
                            <select class="form-select" name="status" id="certificationFormStatus">
                                <option value="">Auto</option>
                                @foreach (\App\Enums\CertStatus::cases() as $s)
                                    <option value="{{ $s->value }}">{{ $s->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="cert-form-section"><div class="asset-section-title"><i class="bi bi-card-text"></i> Catatan</div></div>
                    <div class="row g-3 mb-4">
                        <div class="col-12"><label class="form-label fw-semibold">Catatan Tambahan</label>
                            <textarea class="form-control" name="notes" id="certificationFormNotes" rows="2"></textarea>
                        </div>
                    </div>
                    <div class="cert-form-section"><div class="asset-section-title"><i class="bi bi-paperclip"></i> Dokumen Pendukung</div></div>
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fw-semibold">Foto atau PDF Sertifikat</label>
                            <input type="file" class="form-control" name="attachment" id="certificationFormAttachment"
                                accept="application/pdf,image/jpeg,image/png,image/webp">
                            <div class="form-text">Maksimal 10 MB. Format: PDF, JPG, PNG, atau WEBP.</div>
                            <div id="certificationCurrentAttachment" class="small mt-2 d-none"></div>
                            <div class="form-check mt-2 d-none" id="certificationRemoveAttachmentWrap">
                                <input class="form-check-input" type="checkbox" name="remove_attachment"
                                    value="1" id="certificationRemoveAttachment">
                                <label class="form-check-label" for="certificationRemoveAttachment">Hapus attachment saat disimpan</label>
                            </div>
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
                <p class="mb-3">Tindakan ini <strong>menghapus permanen</strong> sertifikasi dari database dan tidak
                    dapat dibatalkan.</p>
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
                <button type="button" class="btn btn-danger" id="btnDeleteCertConfirm"><i
                        class="bi bi-trash me-1"></i>Hapus Permanen</button>
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
