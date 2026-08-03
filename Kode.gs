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
//   backend/Settings.gs       — getKecamatan & pengaturan server
//   backend/PortalSettings.gs — Portal settings CRUD
//   backend/Outsource.gs      — Registrasi karyawan outsource
//   backend/Auth.gs           — Autentikasi & otorisasi pengguna
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

function buildTemplate_(filename) {
  var template = HtmlService.createTemplateFromFile(filename);
  template.appBaseUrl = ScriptApp.getService().getUrl() || "";
  return template;
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
    return buildTemplate_('views/Dashboard')
      .evaluate()
      .setTitle('HR Dashboard')
      .setXFrameOptionsMode(HtmlService.XFrameOptionsMode.ALLOWALL)
      .addMetaTag('viewport', 'width=device-width, initial-scale=1');
  }

  // --- Recruitment standalone page ---
  if (page === 'recruitment') {
    return buildTemplate_('views/DashboardRecruitment')
      .evaluate()
      .setTitle('Recruitment — ATS')
      .setXFrameOptionsMode(HtmlService.XFrameOptionsMode.ALLOWALL)
      .addMetaTag('viewport', 'width=device-width, initial-scale=1');
  }

  // --- Form Outsource ---
  if (type === 'outsource') {
    return buildTemplate_('OutsourceForm')
      .evaluate()
      .setTitle('Registrasi Karyawan Outsource — Mahakarya HRIS')
      .setXFrameOptionsMode(HtmlService.XFrameOptionsMode.ALLOWALL)
      .addMetaTag('viewport', 'width=device-width, initial-scale=1');
  }

  // --- Access Denied page ---
  if (page === 'access-denied') {
    return buildTemplate_('views/AccessDenied')
      .evaluate()
      .setTitle('Akses Ditolak — Mahakarya HRIS')
      .setXFrameOptionsMode(HtmlService.XFrameOptionsMode.ALLOWALL)
      .addMetaTag('viewport', 'width=device-width, initial-scale=1');
  }

  // --- User Management ---
  if (page === 'user-management') {
    return buildTemplate_('views/UserManagement')
      .evaluate()
      .setTitle('Manajemen Pengguna — Mahakarya HRIS')
      .setXFrameOptionsMode(HtmlService.XFrameOptionsMode.ALLOWALL)
      .addMetaTag('viewport', 'width=device-width, initial-scale=1');
  }

  // --- Portal Settings ---
  if (page === 'settings') {
    return buildTemplate_('views/Settings')
      .evaluate()
      .setTitle('Pengaturan Portal — Mahakarya HRIS')
      .setXFrameOptionsMode(HtmlService.XFrameOptionsMode.ALLOWALL)
      .addMetaTag('viewport', 'width=device-width, initial-scale=1');
  }

  // --- Master Data ---
  if (page === 'master-data') {
    return buildTemplate_('views/MasterData')
      .evaluate()
      .setTitle('Master Data — Mahakarya HRIS')
      .setXFrameOptionsMode(HtmlService.XFrameOptionsMode.ALLOWALL)
      .addMetaTag('viewport', 'width=device-width, initial-scale=1');
  }

  // --- Employee Management ---
  if (page === 'employee') {
    return buildTemplate_('views/Employee')
      .evaluate()
      .setTitle('Manajemen Karyawan — Mahakarya HRIS')
      .setXFrameOptionsMode(HtmlService.XFrameOptionsMode.ALLOWALL)
      .addMetaTag('viewport', 'width=device-width, initial-scale=1');
  }

  // --- Login Page ---
  if (page === 'login') {
    return buildTemplate_('views/Login')
      .evaluate()
      .setTitle('Login — Mahakarya HRIS')
      .setXFrameOptionsMode(HtmlService.XFrameOptionsMode.ALLOWALL)
      .addMetaTag('viewport', 'width=device-width, initial-scale=1');
  }

  // --- Candidate Landing (info rekrutmen) ---
  if (page === 'candidate-landing') {
    return buildTemplate_('views/CandidateLanding')
      .evaluate()
      .setTitle('Informasi Rekrutmen — Mahakarya HRIS')
      .setXFrameOptionsMode(HtmlService.XFrameOptionsMode.ALLOWALL)
      .addMetaTag('viewport', 'width=device-width, initial-scale=1');
  }

  // --- Public Landing Page (default entry) ---
  if (page === 'landing' || page === '') {
    return buildTemplate_('views/Landing')
      .evaluate()
      .setTitle('Mahakarya HRIS')
      .setXFrameOptionsMode(HtmlService.XFrameOptionsMode.ALLOWALL)
      .addMetaTag('viewport', 'width=device-width, initial-scale=1');
  }

  // --- Default: Formulir Pendaftaran Kandidat (direct link backward compat) ---
  var template = buildTemplate_('FormPendaftaran');
  template.tipePendaftar = type;
  return template
    .evaluate()
    .setTitle('Form Pendaftaran Karyawan Baru')
    .setXFrameOptionsMode(HtmlService.XFrameOptionsMode.ALLOWALL)
    .addMetaTag('viewport', 'width=device-width, initial-scale=1');
}
