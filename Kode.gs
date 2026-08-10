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
//   backend/Employee.gs    — CRUD employee (getEmployeeList, addEmployee, updateEmployee, deleteEmployee, getEmployeeStats)
//   backend/MasterData.gs — Master data kategori (getMasterDataList, getMasterDataGrouped, dll)
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
// SPA Dashboard: semua halaman dashboard (dashboard, recruitment,
// employee, settings, master-data, user-management) menggunakan
// views/Dashboard.html sebagai shell. Client-side router (#hash)
// mengatur konten yang ditampilkan.
//
// ?page=dashboard/recruitment/employee/settings/master-data/user-management
//   -> views/Dashboard (SPA)
// ?type=kandidat / dsb -> Form Pendaftaran (FormPendaftaran)
// ============================================================
function doGet(e) {
  var params = (e && e.parameter) || {};
  var page = params.page || "";
  var type = params.type || "kandidat";

  // --- SPA Dashboard routes (semua ke views/Dashboard) ---
  var spaPages = [
    "dashboard",
    "recruitment",
    "employee",
    "settings",
    "master-data",
    "user-management",
  ];
  if (spaPages.indexOf(page) !== -1) {
    var titles = {
      dashboard: "HR Dashboard",
      recruitment: "Recruitment — ATS",
      employee: "Manajemen Karyawan — MITO HRIS",
      settings: "Pengaturan Portal — MITO HRIS",
      "master-data": "Master Data — MITO HRIS",
      "user-management": "Manajemen Pengguna — MITO HRIS",
    };
    return buildTemplate_("views/Dashboard")
      .evaluate()
      .setTitle(titles[page] || "HR Dashboard")
      .setXFrameOptionsMode(HtmlService.XFrameOptionsMode.ALLOWALL)
      .addMetaTag("viewport", "width=device-width, initial-scale=1");
  }

  // --- Form Outsource ---
  if (type === "outsource") {
    return buildTemplate_("OutsourceForm")
      .evaluate()
      .setTitle("Registrasi Karyawan Outsource — MITO HRIS")
      .setXFrameOptionsMode(HtmlService.XFrameOptionsMode.ALLOWALL)
      .addMetaTag("viewport", "width=device-width, initial-scale=1");
  }

  // --- Access Denied page ---
  if (page === "access-denied") {
    return buildTemplate_("views/AccessDenied")
      .evaluate()
      .setTitle("Akses Ditolak — MITO HRIS")
      .setXFrameOptionsMode(HtmlService.XFrameOptionsMode.ALLOWALL)
      .addMetaTag("viewport", "width=device-width, initial-scale=1");
  }

  // --- Login Page ---
  if (page === "login") {
    return buildTemplate_("views/Login")
      .evaluate()
      .setTitle("Login — MITO HRIS")
      .setXFrameOptionsMode(HtmlService.XFrameOptionsMode.ALLOWALL)
      .addMetaTag("viewport", "width=device-width, initial-scale=1");
  }

  // --- Candidate Landing (info rekrutmen) ---
  if (page === "candidate-landing") {
    return buildTemplate_("views/CandidateLanding")
      .evaluate()
      .setTitle("Informasi Rekrutmen — MITO HRIS")
      .setXFrameOptionsMode(HtmlService.XFrameOptionsMode.ALLOWALL)
      .addMetaTag("viewport", "width=device-width, initial-scale=1");
  }

  // --- Candidate Update Data (kandidat accepted update data sendiri) ---
  if (page === "update-data") {
    return buildTemplate_("views/CandidateUpdateData")
      .evaluate()
      .setTitle("Update Data Karyawan — MITO HRIS")
      .setXFrameOptionsMode(HtmlService.XFrameOptionsMode.ALLOWALL)
      .addMetaTag("viewport", "width=device-width, initial-scale=1");
  }

  // --- Candidate Registration (single-page: landing + form) ---
  // ?type=kandidat or direct link backward compat
  if (type === 'kandidat' && page === '') {
    return buildTemplate_("views/CandidateLanding")
      .evaluate()
      .setTitle("Informasi Rekrutmen — MITO HRIS")
      .setXFrameOptionsMode(HtmlService.XFrameOptionsMode.ALLOWALL)
      .addMetaTag("viewport", "width=device-width, initial-scale=1");
  }

  // --- Public Landing Page (default entry) ---
  if (page === "landing" || page === "") {
    return buildTemplate_("views/Landing")
      .evaluate()
      .setTitle("MITO HRIS")
      .setXFrameOptionsMode(HtmlService.XFrameOptionsMode.ALLOWALL)
      .addMetaTag("viewport", "width=device-width, initial-scale=1");
  }

  // --- Fallback: Form Pendaftaran (outsource, etc.) ---
  var template = buildTemplate_("FormPendaftaran");
  template.tipePendaftar = type;
  return template
    .evaluate()
    .setTitle("Form Pendaftaran Karyawan Baru")
    .setXFrameOptionsMode(HtmlService.XFrameOptionsMode.ALLOWALL)
    .addMetaTag("viewport", "width=device-width, initial-scale=1");
}

