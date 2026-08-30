<!-- partials/FloatingActionButton.html — FAB SPEED DIAL (1:1 from GAS) -->
<div class="fab-wrap">
  <div class="fab-actions d-none" id="fabActions">
    <a class="fab-action" href="https://docs.google.com/spreadsheets/d/{{ config('google.spreadsheet_id') }}/edit" target="_blank" rel="noopener noreferrer">
      Buka Spreadsheet
      <i class="bi bi-table"></i>
    </a>
    <button class="fab-action" id="fabRefresh" type="button" data-refresh="page" aria-label="Refresh data" title="Muat ulang data">
      Refresh Data
      <i class="bi bi-arrow-clockwise"></i>
    </button>
  </div>
  <button class="fab-main" id="fabMain" type="button" aria-label="Buka menu aksi cepat" aria-expanded="false">
    <i class="bi bi-plus-lg"></i>
  </button>
</div>
