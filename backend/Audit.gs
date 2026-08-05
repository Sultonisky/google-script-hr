// ============================================================
// backend/Audit.gs — AUDIT LOG
// Uses AUDIT_LOG_HEADERS and AUDIT_SHEET_NAME from Config.gs
// ============================================================

// Tulis satu baris audit log (buat sheet jika belum ada)
function writeAuditLog_(recruitmentId, action, field, oldValue, newValue) {
  var sheet = getOrCreateAuditLogSheet_();

  var user = Session.getActiveUser().getEmail() || "HR Dashboard";
  sheet.appendRow([
    Utilities.formatDate(new Date(), "GMT+7", "yyyy-MM-dd HH:mm:ss"),
    user,
    recruitmentId,
    action,
    field,
    oldValue,
    newValue,
  ]);
}

// Ambil seluruh audit log (untuk halaman Audit Log dashboard)
function getAllAuditLogs(limit) {
  var ss = SpreadsheetApp.getActiveSpreadsheet();
  var sheet = ss.getSheetByName(AUDIT_SHEET_NAME);
  if (!sheet || sheet.getLastRow() < 2) return [];

  var maxRows = sheet.getLastRow();
  var startRow = 2;
  if (limit && maxRows > limit + 1) {
    startRow = maxRows - limit + 1;
  }
  var rowCount = maxRows - startRow + 1;
  var values = sheet
    .getRange(startRow, 1, rowCount, AUDIT_LOG_HEADERS.length)
    .getValues();
  var result = [];

  for (var r = 0; r < values.length; r++) {
    var row = values[r];
    var ts = row[AUDIT_COL["Timestamp"] - 1];
    result.push({
      timestamp:
        ts instanceof Date
          ? Utilities.formatDate(ts, "GMT+7", "dd/MM/yyyy HH:mm")
          : String(ts || ""),
      user: String(row[AUDIT_COL["User"] - 1] || ""),
      recruitmentId: String(row[AUDIT_COL["Recruitment ID"] - 1] || ""),
      action: String(row[AUDIT_COL["Action"] - 1] || ""),
      field: String(row[AUDIT_COL["Field"] - 1] || ""),
      oldValue: String(row[AUDIT_COL["Old Value"] - 1] || ""),
      newValue: String(row[AUDIT_COL["New Value"] - 1] || ""),
    });
  }

  // Sort by timestamp descending (newest first)
  result.sort(function (a, b) {
    function toTs(s) {
      if (!s) return 0;
      var p = s.split(" "),
        d = p[0].split("/"),
        t = p[1] ? p[1].split(":") : ["00", "00"];
      return new Date(d[2], d[1] - 1, d[0], t[0], t[1]).getTime();
    }
    return toTs(b.timestamp) - toTs(a.timestamp);
  });

  return result;
}

// Ambil riwayat aktivitas untuk satu kandidat (dipakai Activity Timeline)
function getAuditLogForCandidate(recruitmentId) {
  var ss    = SpreadsheetApp.getActiveSpreadsheet();
  var sheet = ss.getSheetByName(AUDIT_SHEET_NAME);
  if (!sheet || sheet.getLastRow() < 2) return [];

  var values = sheet
    .getRange(1, 1, sheet.getLastRow(), AUDIT_LOG_HEADERS.length)
    .getValues();
  var result = [];

  for (var r = 1; r < values.length; r++) {
    var row = values[r];
    if (String(row[AUDIT_COL['Recruitment ID'] - 1]) !== String(recruitmentId)) continue;
    var ts = row[AUDIT_COL['Timestamp'] - 1];
    result.push({
      timestamp: ts instanceof Date
        ? Utilities.formatDate(ts, 'GMT+7', 'dd/MM/yyyy HH:mm')
        : String(ts || ''),
      user:     String(row[AUDIT_COL['User'] - 1] || ''),
      action:   String(row[AUDIT_COL['Action'] - 1] || ''),
      field:    String(row[AUDIT_COL['Field'] - 1] || ''),
      oldValue: String(row[AUDIT_COL['Old Value'] - 1] || ''),
      newValue: String(row[AUDIT_COL['New Value'] - 1] || '')
    });
  }

  return result;
}