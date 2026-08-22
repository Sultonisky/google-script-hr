<!-- Modal: Update Status -->
<div class="modal fade" id="modalUpdateStatus" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <form id="formUpdateStatus" method="POST">
                @csrf
                <div class="modal-header bg-navy text-white">
                    <h5 class="modal-title fs-6 fw-bold"><i class="bi bi-arrow-repeat me-2"></i> Update Status Kandidat</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Pilih Status Baru <span class="text-danger">*</span></label>
                        <select class="form-select" name="status" id="modalStatusSelect" required>
                            <option value="New">New</option>
                            <option value="Screening">Screening</option>
                            <option value="Interview HR">Interview HR</option>
                            <option value="Interview User">Interview User</option>
                            <option value="Offering">Offering</option>
                            <option value="Rejected">Rejected</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Catatan Tim HR</label>
                        <textarea class="form-control" name="hr_notes" id="modalStatusNotes" rows="3" placeholder="Tambahkan catatan evaluasi atau alasan perubahan status..."></textarea>
                    </div>
                </div>
                <div class="modal-footer border-top">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary fw-semibold px-4">Simpan Status</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Hold Candidate -->
<div class="modal fade" id="modalHold" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <form id="formHold" method="POST">
                @csrf
                <div class="modal-header bg-secondary text-white">
                    <h5 class="modal-title fs-6 fw-bold"><i class="bi bi-pause-circle-fill me-2"></i> Hold Kandidat</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Alasan Penundaan (Hold Reason) <span class="text-danger">*</span></label>
                        <textarea class="form-control" name="reason" rows="3" placeholder="Contoh: Menunggu pembukaan batch cabang baru / Talent pool Q4..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer border-top">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-secondary fw-semibold px-4">Pindahkan ke Hold</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Blacklist Candidate -->
<div class="modal fade" id="modalBlacklist" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <form id="formBlacklist" method="POST">
                @csrf
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title fs-6 fw-bold"><i class="bi bi-slash-circle-fill text-danger me-2"></i> Masukkan ke Daftar Blacklist</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="alert alert-danger border-0 small mb-3">
                        <i class="bi bi-exclamation-triangle-fill me-1"></i> Kandidat yang di-blacklist akan diblokir dari seluruh pendaftaran rekrutmen mendatang.
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Alasan Blacklist <span class="text-danger">*</span></label>
                        <textarea class="form-control" name="reason" rows="3" placeholder="Contoh: Pemalsuan data dokumen / No show saat offering..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer border-top">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger fw-semibold px-4">Konfirmasi Blacklist</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Accept to Employee -->
<div class="modal fade" id="modalAccept" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <form id="formAccept" method="POST">
                @csrf
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title fs-6 fw-bold"><i class="bi bi-person-check-fill me-2"></i> Terima Kandidat</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="alert alert-success border-0 small mb-0">
                        <i class="bi bi-check-circle-fill me-1"></i> Kandidat akan dipindahkan ke status <strong>Accepted</strong> dan masuk ke daftar kandidat yang diterima.
                    </div>
                </div>
                <div class="modal-footer border-top">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success fw-semibold px-4">
                        <i class="bi bi-check2-circle me-1"></i> Terima Kandidat
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
