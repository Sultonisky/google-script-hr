// ============================================================
// Kode.gs — ENTRY POINT & ROUTER
// Satu-satunya file yang mengandung doGet().
// Semua logika bisnis ada di file backend/ terpisah.
//
// Arsitektur modular:
//   backend/Config.gs      — Konstanta global
//   backend/Utilities.gs   — Helper fungsi (findCandidateRow_, dll)
//   backend/Sheets.gs      — Manajemen sheet spreadsheet
//   backend/Validation.gs  — Validasi form server-side
//   backend/Audit.gs       — Audit log & getAuditLogForCandidate
//   backend/Recruitment.gs — CRUD kandidat (simpan, list, update, dll)
//   backend/BulkActions.gs — Bulk update & delete
//   backend/IdGenerator.gs — Generator ID rekrutmen & karyawan
//   backend/Export.gs      — Placeholder ekspor server-side
//   backend/Settings.gs    — getKecamatan & pengaturan server
// ============================================================

// ============================================================
// INCLUDE HELPER — digunakan oleh semua template HTML
// Contoh penggunaan di template:
//   <?!= include('partials/Sidebar'); ?>
//   <?!= include('css/theme'); ?>
//   <?!= include('js/app'); ?>
// ============================================================
function include(filename) {
  return HtmlService.createHtmlOutputFromFile(filename).getContent();
}

// ============================================================
// ROUTER — doGet(e)
// ?page=dashboard       -> HR Dashboard   (views/Dashboard)
// ?page=recruitment     -> Recruitment    (views/DashboardRecruitment)
// ?type=kandidat / dsb  -> Form Pendaftaran (FormPendaftaran — root level)
// ============================================================
function doGet(e) {
  var params = (e && e.parameter) || {};
  var page   = params.page || '';
  var type   = params.type || 'kandidat';

  // --- HR Dashboard ---
  if (page === 'dashboard') {
    return HtmlService.createTemplateFromFile('views/Dashboard').evaluate()
        .setTitle('HR Dashboard')
        .setXFrameOptionsMode(HtmlService.XFrameOptionsMode.ALLOWALL)
        .addMetaTag('viewport', 'width=device-width, initial-scale=1');
  }

  // --- Recruitment standalone page ---
  if (page === 'recruitment') {
    return HtmlService.createTemplateFromFile('views/DashboardRecruitment').evaluate()
        .setTitle('Recruitment — ATS')
        .setXFrameOptionsMode(HtmlService.XFrameOptionsMode.ALLOWALL)
        .addMetaTag('viewport', 'width=device-width, initial-scale=1');
  }

  // --- Default: Formulir Pendaftaran ---
  // FormPendaftaran tetap di root (self-contained, file besar)
  var template = HtmlService.createTemplateFromFile('FormPendaftaran');
  template.tipePendaftar = type;
  return template.evaluate()
      .setTitle('Form Pendaftaran Karyawan Baru')
      .setXFrameOptionsMode(HtmlService.XFrameOptionsMode.ALLOWALL)
      .addMetaTag('viewport', 'width=device-width, initial-scale=1');
}
