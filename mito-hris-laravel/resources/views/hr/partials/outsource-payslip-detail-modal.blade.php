{{--
  Modal Detail Payslip Outsource. Diisi dari atribut data-payslip-detail
  (nilai sudah diformat di server) pada tombol nama di tabel payslip.
--}}
<div class="modal fade hr-detail-modal payslip-detail-modal" id="outsourcePayslipDetailModal" tabindex="-1"
    aria-labelledby="opdTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">
            <div class="modal-header">
                <div class="min-w-0">
                    <h5 class="modal-title" id="opdTitle">Payslip Outsource</h5>
                </div>
                <div class="payslip-period">
                    <span>PERIODE</span>
                    <strong data-opd="period">-</strong>
                </div>
            </div>

            <div class="modal-body">
                <div class="detail-summary">
                    <div class="d-flex align-items-center gap-3 min-w-0">
                        <div class="detail-avatar" data-opd-initials>-</div>
                        <div class="min-w-0">
                            <div class="detail-candidate-name" data-opd="fullName">-</div>
                            <div class="detail-meta">
                                <span class="detail-mono" data-opd="outsourceId">-</span>
                                &middot; <span data-opd="vendor">-</span>
                            </div>
                        </div>
                    </div>
                    <div class="detail-qty">
                        <span data-opd="hke">-</span>
                        <small>Hari Kerja Efektif</small>
                    </div>
                </div>

                <div class="row g-3">
                    <div class="col-md-12">
                        <section class="detail-section h-100">
                            <h6 class="detail-section-title"><i class="bi bi-wallet2"></i>Pendapatan</h6>
                            <dl class="payslip-lines">
                                <div>
                                    <dt>Gaji Pokok</dt>
                                    <dd data-opd="basicSalary">-</dd>
                                </div>
                                <div class="payslip-line-total">
                                    <dt>Total Pendapatan</dt>
                                    <dd data-opd="basicSalary">-</dd>
                                </div>
                            </dl>
                            <hr class="my-4">
                            <h6 class="detail-section-title"><i class="bi bi-dash-circle"></i>Potongan</h6>
                            <dl class="payslip-lines payslip-deduction">
                                <div>
                                    <dt>BPJS Kesehatan</dt>
                                    <dd data-opd="bpjsKesehatan">-</dd>
                                </div>
                                <div>
                                    <dt>Pinjaman</dt>
                                    <dd data-opd="loan">-</dd>
                                </div>
                                <div class="payslip-line-total">
                                    <dt>Total Potongan</dt>
                                    <dd data-opd="totalDeduction">-</dd>
                                </div>
                            </dl>
                        </section>
                    </div>
                </div>

                <div class="detail-total mt-3">
                    <span>Take Home Pay</span>
                    <strong data-opd="takeHomePay">-</strong>
                </div>

                <div class="payslip-footnote">
                    Payslip ini dibuat pada <span data-opd="createdAt">-</span>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<script nonce="{{ request()->attributes->get('csp_nonce') }}">
    (function() {
        var modalEl = document.getElementById('outsourcePayslipDetailModal');
        if (!modalEl) return;

        function initials(name) {
            var parts = String(name || '').trim().split(/\s+/).filter(Boolean);
            if (!parts.length || parts[0] === '-') return '-';
            return (parts[0].charAt(0) + (parts.length > 1 ? parts[parts.length - 1].charAt(0) : '')).toUpperCase();
        }

        document.querySelectorAll('[data-payslip-detail]').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var data;
                try {
                    data = JSON.parse(btn.getAttribute('data-payslip-detail') || '{}');
                } catch (e) {
                    return;
                }

                modalEl.querySelectorAll('[data-opd]').forEach(function(el) {
                    var value = data[el.getAttribute('data-opd')];
                    el.textContent = value == null || value === '' ? '-' : value;
                });
                modalEl.querySelector('[data-opd-initials]').textContent = initials(data.fullName);

                bootstrap.Modal.getOrCreateInstance(modalEl).show();
            });
        });
    })();
</script>
