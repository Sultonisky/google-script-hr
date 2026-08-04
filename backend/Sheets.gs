// ============================================================
// backend/Sheets.gs — MANAJEMEN SEMUA SHEET
// Uses Config.gs as single source of truth for all schemas.
// No hardcoded headers or column indexes anywhere.
// ============================================================

// ============= raw_kandidat =============
function getOrCreateSheet_() {
  var ss    = SpreadsheetApp.getActiveSpreadsheet();
  var sheet = ss.getSheetByName(SHEET_NAME);
  if (!sheet) sheet = ss.insertSheet(SHEET_NAME);

  if (sheet.getLastRow() === 0) {
    sheet.appendRow(SHEET_HEADERS.concat(EXTRA_HEADERS));
    sheet.getRange(1, 1, 1, SHEET_HEADERS.length + EXTRA_HEADERS.length)
         .setFontWeight('bold').setBackground('#005BAC').setFontColor('#FFFFFF');
    sheet.setFrozenRows(1);
    sheet.autoResizeColumns(1, SHEET_HEADERS.length + EXTRA_HEADERS.length);
  } else {
    ensureExtraHeaders_(sheet);
  }
  return sheet;
}

function ensureExtraHeaders_(sheet) {
  var lastCol   = sheet.getLastColumn();
  var headerRow = sheet.getRange(1, 1, 1, lastCol).getValues()[0]
                       .map(function(h){ return String(h).trim(); });
  var missing = EXTRA_HEADERS.filter(function(h){ return headerRow.indexOf(h) === -1; });
  if (missing.length === 0) return;
  var startCol = lastCol + 1;
  sheet.getRange(1, startCol, 1, missing.length).setValues([missing]);
  sheet.getRange(1, startCol, 1, missing.length)
       .setFontWeight('bold').setBackground('#005BAC').setFontColor('#FFFFFF');
}

function getDashboardSheet_() {
  var ss    = SpreadsheetApp.getActiveSpreadsheet();
  var sheet = ss.getSheetByName(DASHBOARD_SHEET_NAME);
  if (sheet) ensureExtraHeaders_(sheet);
  return sheet;
}

// ============= Employee =============
function getOrCreateEmployeeSheet_() {
  var ss    = SpreadsheetApp.getActiveSpreadsheet();
  var sheet = ss.getSheetByName(EMPLOYEE_SHEET_NAME);
  if (!sheet) sheet = ss.insertSheet(EMPLOYEE_SHEET_NAME);

  if (sheet.getLastRow() === 0) {
    sheet.appendRow(EMPLOYEE_HEADERS);
    sheet.getRange(1, 1, 1, EMPLOYEE_HEADERS.length)
         .setFontWeight('bold').setBackground('#005BAC').setFontColor('#FFFFFF');
    sheet.setFrozenRows(1);
    sheet.autoResizeColumns(1, EMPLOYEE_HEADERS.length);
  } else {
    ensureEmployeeHeaders_(sheet);
  }
  return sheet;
}

function ensureEmployeeHeaders_(sheet) {
  var lastCol   = sheet.getLastColumn();
  var headerRow = sheet.getRange(1, 1, 1, lastCol).getValues()[0]
                       .map(function(h){ return String(h).trim(); });
  var missing = EMPLOYEE_HEADERS.filter(function(h){ return headerRow.indexOf(h) === -1; });
  if (missing.length === 0) return;
  var startCol = lastCol + 1;
  sheet.getRange(1, startCol, 1, missing.length).setValues([missing]);
  sheet.getRange(1, startCol, 1, missing.length)
       .setFontWeight('bold').setBackground('#005BAC').setFontColor('#FFFFFF');
}

// ============= raw_outsource =============
function getOrCreateOutsourceSheet_() {
  var ss    = SpreadsheetApp.getActiveSpreadsheet();
  var sheet = ss.getSheetByName(OUTSOURCE_SHEET_NAME);
  if (!sheet) sheet = ss.insertSheet(OUTSOURCE_SHEET_NAME);

  if (sheet.getLastRow() === 0) {
    sheet.appendRow(OUTSOURCE_HEADERS);
    sheet.getRange(1, 1, 1, OUTSOURCE_HEADERS.length)
         .setFontWeight('bold').setBackground('#005BAC').setFontColor('#FFFFFF');
    sheet.setFrozenRows(1);
    sheet.autoResizeColumns(1, OUTSOURCE_HEADERS.length);
  } else {
    ensureOutsourceHeaders_(sheet);
  }
  return sheet;
}

function ensureOutsourceHeaders_(sheet) {
  var lastCol   = sheet.getLastColumn();
  var headerRow = sheet.getRange(1, 1, 1, lastCol).getValues()[0]
                       .map(function(h){ return String(h).trim(); });
  var missing = OUTSOURCE_HEADERS.filter(function(h){ return headerRow.indexOf(h) === -1; });
  if (missing.length === 0) return;
  var startCol = lastCol + 1;
  sheet.getRange(1, startCol, 1, missing.length).setValues([missing]);
  sheet.getRange(1, startCol, 1, missing.length)
       .setFontWeight('bold').setBackground('#005BAC').setFontColor('#FFFFFF');
}

// ============= Archive =============
function getOrCreateArchiveSheet_() {
  var ss    = SpreadsheetApp.getActiveSpreadsheet();
  var sheet = ss.getSheetByName(ARCHIVE_SHEET_NAME);
  if (!sheet) sheet = ss.insertSheet(ARCHIVE_SHEET_NAME);

  if (sheet.getLastRow() === 0) {
    sheet.appendRow(ARCHIVE_HEADERS);
    sheet.getRange(1, 1, 1, ARCHIVE_HEADERS.length)
         .setFontWeight('bold').setBackground('#005BAC').setFontColor('#FFFFFF');
    sheet.setFrozenRows(1);
    sheet.autoResizeColumns(1, ARCHIVE_HEADERS.length);
  }
  return sheet;
}

// ============= Offboarding =============
function getOrCreateOffboardingSheet_() {
  var ss    = SpreadsheetApp.getActiveSpreadsheet();
  var sheet = ss.getSheetByName(OFFBOARDING_SHEET_NAME);
  if (!sheet) sheet = ss.insertSheet(OFFBOARDING_SHEET_NAME);

  if (sheet.getLastRow() === 0) {
    sheet.appendRow(OFFBOARDING_HEADERS);
    sheet.getRange(1, 1, 1, OFFBOARDING_HEADERS.length)
         .setFontWeight('bold').setBackground('#005BAC').setFontColor('#FFFFFF');
    sheet.setFrozenRows(1);
    sheet.autoResizeColumns(1, OFFBOARDING_HEADERS.length);
  } else {
    ensureOffboardingHeaders_(sheet);
  }
  return sheet;
}

function ensureOffboardingHeaders_(sheet) {
  var lastCol   = sheet.getLastColumn();
  var headerRow = sheet.getRange(1, 1, 1, lastCol).getValues()[0]
                       .map(function(h){ return String(h).trim(); });
  var missing = OFFBOARDING_HEADERS.filter(function(h){ return headerRow.indexOf(h) === -1; });
  if (missing.length === 0) return;
  var startCol = lastCol + 1;
  sheet.getRange(1, startCol, 1, missing.length).setValues([missing]);
  sheet.getRange(1, startCol, 1, missing.length)
       .setFontWeight('bold').setBackground('#005BAC').setFontColor('#FFFFFF');
}

// ============= Audit_Log =============
function getOrCreateAuditLogSheet_() {
  var ss    = SpreadsheetApp.getActiveSpreadsheet();
  var sheet = ss.getSheetByName(AUDIT_SHEET_NAME);
  if (!sheet) sheet = ss.insertSheet(AUDIT_SHEET_NAME);

  if (sheet.getLastRow() === 0) {
    sheet.appendRow(AUDIT_LOG_HEADERS);
    sheet.getRange(1, 1, 1, AUDIT_LOG_HEADERS.length)
         .setFontWeight('bold').setBackground('#005BAC').setFontColor('#FFFFFF');
    sheet.setFrozenRows(1);
    sheet.autoResizeColumns(1, AUDIT_LOG_HEADERS.length);
  }
  return sheet;
}

// ============= Users =============
function getOrCreateUsersSheet_() {
  var ss    = SpreadsheetApp.getActiveSpreadsheet();
  var sheet = ss.getSheetByName(USERS_SHEET_NAME);
  if (!sheet) sheet = ss.insertSheet(USERS_SHEET_NAME);

  if (sheet.getLastRow() === 0) {
    sheet.appendRow(USERS_HEADERS);
    sheet.getRange(1, 1, 1, USERS_HEADERS.length)
         .setFontWeight('bold').setBackground('#005BAC').setFontColor('#FFFFFF');
    sheet.setFrozenRows(1);
    sheet.autoResizeColumns(1, USERS_HEADERS.length);
  }
  return sheet;
}

// ============= Setup All =============
function setupSpreadsheet() {
  getOrCreateSheet_();
  getOrCreateEmployeeSheet_();
  getOrCreateOutsourceSheet_();
  getOrCreateArchiveSheet_();
  getOrCreateOffboardingSheet_();
  getOrCreateAuditLogSheet_();
  getOrCreateUsersSheet_();
}