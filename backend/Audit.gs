// ============================================================
// backend/Audit.gs — AUDIT LOG
// ============================================================

// Tulis satu baris audit log (buat sheet jika belum ada)
function writeAuditLog_(recruitmentId, action, oldValue, newValue) {
  var ss    = SpreadsheetApp.getActiveSpreadsheet();
  var sheet = ss.getSheetByName(AUDIT_SHEET_NAME);

  if (!sheet) {
    sheet = ss.insertSheet(AUDIT_SHEET_NAME);
    sheet.appendRow(['Timestamp', 'User', 'Recruitment ID', 'Action', 'Old Value', 'New Value']);
    sheet.getRange(1, 1, 1, 6)
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
    oldValue,
    newValue
  ]);
}

// Ambil riwayat aktivitas untuk satu kandidat (dipakai Activity Timeline)
function getAuditLogForCandidate(recruitmentId) {
  var ss    = SpreadsheetApp.getActiveSpreadsheet();
  var sheet = ss.getSheetByName(AUDIT_SHEET_NAME);
  if (!sheet || sheet.getLastRow() < 2) return [];

  var values = sheet.getRange(1, 1, sheet.getLastRow(), 6).getValues();
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
      oldValue: String(row[4] || ''),
      newValue: String(row[5] || '')
    });
  }

  return result;
}
