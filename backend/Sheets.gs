// ============================================================
// backend/Sheets.gs — MANAJEMEN SHEET (buat, pastikan header)
// ============================================================

// Ambil atau buat sheet raw_kandidat dengan header lengkap
function getOrCreateSheet_() {
  var ss    = SpreadsheetApp.getActiveSpreadsheet();
  var sheet = ss.getSheetByName(SHEET_NAME);

  if (!sheet) {
    sheet = ss.insertSheet(SHEET_NAME);
  }

  if (sheet.getLastRow() === 0) {
    sheet.appendRow(SHEET_HEADERS.concat(EXTRA_HEADERS));
    sheet.getRange(1, 1, 1, SHEET_HEADERS.length + EXTRA_HEADERS.length)
         .setFontWeight('bold')
         .setBackground('#005BAC')
         .setFontColor('#FFFFFF');
    sheet.setFrozenRows(1);
    sheet.autoResizeColumns(1, SHEET_HEADERS.length + EXTRA_HEADERS.length);
  } else {
    ensureExtraHeaders_(sheet);
  }

  return sheet;
}

// Tambahkan EXTRA_HEADERS di akhir sheet jika belum ada (aman untuk data lama)
function ensureExtraHeaders_(sheet) {
  var lastCol   = sheet.getLastColumn();
  var headerRow = sheet.getRange(1, 1, 1, lastCol).getValues()[0]
                       .map(function(h){ return String(h).trim(); });

  var missing = EXTRA_HEADERS.filter(function(h){ return headerRow.indexOf(h) === -1; });
  if (missing.length === 0) return;

  var startCol = lastCol + 1;
  sheet.getRange(1, startCol, 1, missing.length).setValues([missing]);
  sheet.getRange(1, startCol, 1, missing.length)
       .setFontWeight('bold')
       .setBackground('#005BAC')
       .setFontColor('#FFFFFF');
}

// Ambil sheet dashboard (raw_kandidat) + pastikan header extra
function getDashboardSheet_() {
  var ss    = SpreadsheetApp.getActiveSpreadsheet();
  var sheet = ss.getSheetByName(DASHBOARD_SHEET_NAME);
  if (sheet) ensureExtraHeaders_(sheet);
  return sheet;
}

// Ambil atau buat sheet Employee dengan header baru
function getOrCreateEmployeeSheet_() {
  var ss    = SpreadsheetApp.getActiveSpreadsheet();
  var sheet = ss.getSheetByName(EMPLOYEE_SHEET_NAME);

  if (!sheet) {
    sheet = ss.insertSheet(EMPLOYEE_SHEET_NAME);
    sheet.appendRow(EMPLOYEE_HEADERS);
    sheet.getRange(1, 1, 1, EMPLOYEE_HEADERS.length)
         .setFontWeight('bold')
         .setBackground('#005BAC')
         .setFontColor('#FFFFFF');
    sheet.setFrozenRows(1);
    sheet.autoResizeColumns(1, EMPLOYEE_HEADERS.length);
  } else {
    ensureEmployeeHeaders_(sheet);
  }

  return sheet;
}

// Pastikan Employee sheet punya semua header terbaru (aman untuk data lama)
function ensureEmployeeHeaders_(sheet) {
  var lastCol   = sheet.getLastColumn();
  var headerRow = sheet.getRange(1, 1, 1, lastCol).getValues()[0]
                       .map(function(h){ return String(h).trim(); });
  var missing = EMPLOYEE_HEADERS.filter(function(h){ return headerRow.indexOf(h) === -1; });
  if (missing.length === 0) return;
  var startCol = lastCol + 1;
  sheet.getRange(1, startCol, 1, missing.length).setValues([missing]);
  sheet.getRange(1, startCol, 1, missing.length)
       .setFontWeight('bold')
       .setBackground('#005BAC')
       .setFontColor('#FFFFFF');
}

// Jalankan sekali dari editor untuk setup awal spreadsheet
function setupSpreadsheet() {
  getOrCreateSheet_();
  getOrCreateEmployeeSheet_();
  if (typeof getOrCreateOutsourceSheet_ === 'function') getOrCreateOutsourceSheet_();
}
