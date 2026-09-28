<!-- partials/FloatingActionButton.html — FAB SPEED DIAL (1:1 from GAS) -->
@php
  $canSyncSheets = \App\Support\HrisDataDriver::usesPgsql()
    && config('google.enabled')
    && \App\Support\Rbac::normalizeRole(session('hr_user.role')) === \App\Support\Rbac::normalizeRole('Super Admin')
    && \Illuminate\Support\Facades\Gate::allows('manage_settings');
@endphp
<div class="fab-wrap">
  <div class="fab-actions d-none" id="fabActions">
    <a class="fab-action" href="https://docs.google.com/spreadsheets/d/{{ config('google.spreadsheet_id') }}/edit" target="_blank" rel="noopener noreferrer">
      Buka Spreadsheet
      <i class="bi bi-table"></i>
    </a>
    @if ($canSyncSheets)
    <button class="fab-action" id="fabSyncSheets" type="button" data-sync-sheets
      aria-label="Sinkron ke Spreadsheet" title="Salin data terbaru dari database ke Spreadsheet"
      data-refresh-idle-label="Sinkron ke Spreadsheet" data-refresh-idle-title="Salin data terbaru dari database ke Spreadsheet"
      data-refresh-loading-label="Menyinkronkan ke Spreadsheet" data-refresh-loading-title="Menyinkronkan ke Spreadsheet...">
      Sinkron ke Spreadsheet
      <i class="bi bi-cloud-upload"></i>
    </button>
    @endif
    <button class="fab-action" id="fabRefresh" type="button" data-refresh="page" aria-label="Refresh data" title="Muat ulang data">
      Refresh Data
      <i class="bi bi-arrow-repeat"></i>
    </button>
  </div>
  <button class="fab-main" id="fabMain" type="button" aria-label="Buka menu aksi cepat" aria-expanded="false">
    <i class="bi bi-plus-lg"></i>
  </button>
</div>

@if ($canSyncSheets)
{{-- SYNC DB → SPREADSHEET CONFIRMATION MODAL --}}
<div class="modal fade" id="syncSheetsModal" tabindex="-1" aria-labelledby="syncSheetsModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h6 class="modal-title text-primary" id="syncSheetsModalLabel"><i class="bi bi-cloud-upload me-1"></i>Sinkron ke Spreadsheet</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
      </div>
      <div class="modal-body">
        <p class="mb-3">Data terbaru dari <strong>database HRIS</strong> akan disalin ke Spreadsheet arsip. Proses ini bisa memakan waktu beberapa saat.</p>
        <div class="border rounded p-3">
          <div class="row g-2">
            <div class="col-4 text-muted small">Arah</div>
            <div class="col-8 fw-semibold">Database <i class="bi bi-arrow-right mx-1"></i> Spreadsheet</div>
            <div class="col-4 text-muted small">Spreadsheet</div>
            <div class="col-8">Perubahan manual akan <strong class="text-danger">tertimpa</strong></div>
            <div class="col-4 text-muted small">Database</div>
            <div class="col-8">Tidak berubah</div>
          </div>
        </div>
        <div id="syncSheetsError" class="alert alert-danger mt-3 mb-0 d-none" role="alert"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
        <button type="button" class="btn btn-primary" id="btnSyncSheetsConfirm"><i class="bi bi-cloud-upload me-1"></i>Sinkron Sekarang</button>
      </div>
    </div>
  </div>
</div>
@endif
