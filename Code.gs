// ============================================================
// Code.gs — ENTRY POINT & ROUTER
// Satu-satunya file yang mengandung doGet().
// Semua logika bisnis ada di file backend/ terpisah.
// ============================================================

// ============================================================
// INCLUDE HELPER — digunakan oleh semua template HTML
// <?!= include('partials/Sidebar'); ?>
// ============================================================
function include(filename) {
  return HtmlService.createHtmlOutputFromFile(filename).getContent();
}

// ============================================================
// ROUTER — doGet(e)
// ?page=dashboard       -> HR Dashboard (views/Dashboard)
// ?page=recruitment     -> Recruitment view (views/DashboardRecruitment)
// ?type=kandidat / dsb  -> Formulir pendaftaran (views/FormPendaftaran)
// ============================================================
function doGet(e) {
  var params = (e && e.parameter) || {};
  var page   = params.page || '';
  var type   = params.type || 'kandidat';

  if (page === 'dashboard') {
    return HtmlService.createTemplateFromFile('views/Dashboard').evaluate()
        .setTitle('HR Dashboard')
        .setXFrameOptionsMode(HtmlService.XFrameOptionsMode.ALLOWALL)
        .addMetaTag('viewport', 'width=device-width, initial-scale=1');
  }

  if (page === 'recruitment') {
    return HtmlService.createTemplateFromFile('views/DashboardRecruitment').evaluate()
        .setTitle('Recruitment — ATS')
        .setXFrameOptionsMode(HtmlService.XFrameOptionsMode.ALLOWALL)
        .addMetaTag('viewport', 'width=device-width, initial-scale=1');
  }

  // FormPendaftaran stays at root level (self-contained, large file)
  var template = HtmlService.createTemplateFromFile('FormPendaftaran');
  template.tipePendaftar = type;
  return template.evaluate()
      .setTitle('Form Pendaftaran Karyawan Baru')
      .setXFrameOptionsMode(HtmlService.XFrameOptionsMode.ALLOWALL)
      .addMetaTag('viewport', 'width=device-width, initial-scale=1');
}
