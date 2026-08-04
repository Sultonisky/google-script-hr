// ============================================================
// backend/Audit.gs — AUDIT LOG
// ============================================================

// Tulis satu baris audit log (buat sheet jika belum ada)
function writeAuditLog_(recruitmentId, action, field, oldValue, newValue) {
  var ss    = SpreadsheetApp.getActiveSpreadsheet();
  var sheet = ss.getSheetByName(AUDIT_LOG_SHEET_NAME);

  if (!sheet) {
    sheet = ss.insertSheet(AUDIT_LOG_SHEET_NAME);
    sheet.appendRow(AUDIT_LOG_HEADERS);
    sheet.getRange(1, 1, 1, AUDIT_LOG_HEADERS.length)
         .setFontWeight('bold')
         .setBackground('#005BAC')
         .setFontColor('#FFFFFF');
    sheet.setFrozenRows(1);
  }

  var user = Session.getActiveUser().getEmail() || 'HR Dashboard';
  sheet.appendRow([
    Utilities.formatDate(new Date(), 'GMT+7', 'yyyy-MM-dd HH:mm:ss'),
    user,
    recruitmentId,
    action,
    field,
    oldValue,
    newValue
  ]);
}

// Ambil riwayat aktivitas untuk satu kandidat (dipakai Activity Timeline)
function getAuditLogForCandidate(recruitmentId) {
  var ss    = SpreadsheetApp.getActiveSpreadsheet();
  var sheet = ss.getSheetByName(AUDIT_LOG_SHEET_NAME);
  if (!sheet || sheet.getLastRow() < 2) return [];

  var values = sheet.getRange(1, 1, sheet.getLastRow(), AUDIT_LOG_HEADERS.length).getValues();
  var result = [];

  for (var r = 1; r < values.length; r++) {
    var row = values[r];
    if (String(row[2]) !== String(recruitmentId)) continue;
    result.push({
      timestamp: row[0] instanceof Date
        ? Utilities.formatDate(row[0], 'GMT+7', 'dd/MM/yyyy HH:mm')
        : String(row[0] || ''),
      user:     String(row[1] || ''),
      action:   String(row[3] || ''),
      field:    String(row[4] || ''),
      oldValue: String(row[5] || ''),
      newValue: String(row[6] || '')
    });
  }

  return result;
}
