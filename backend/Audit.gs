// ============================================================
// backend/Audit.gs — AUDIT LOG
// Uses AUDIT_LOG_HEADERS and AUDIT_SHEET_NAME from Config.gs
// Column order: Recruitment ID | Action | Field | Old Value | New Value | User | Timestamp
// ============================================================

// Tulis satu baris audit log (buat sheet jika belum ada)
function writeAuditLog_(recruitmentId, action, field, oldValue, newValue) {
  var sheet = getOrCreateAuditLogSheet_();

  var user = Session.getActiveUser().getEmail() || "HR Dashboard";
  var now = Utilities.formatDate(new Date(), "GMT+7", "yyyy-MM-dd HH:mm:ss");
  sheet.appendRow([
    recruitmentId, // 1 - Recruitment ID
    action, // 2 - Action
    field, // 3 - Field (e.g. "Status", "HR Notes")
    oldValue, // 4 - Old Value
    newValue, // 5 - New Value
    user, // 6 - User
    now, // 7 - Timestamp
  ]);

  SpreadsheetApp.flush();
}

// ---- Get all audit logs (newest first) ----
function getAllAuditLogs(limit) {
  var sheet = getOrCreateAuditLogSheet_();
  if (!sheet || sheet.getLastRow() < 2) return [];

  var rows = sheet
    .getRange(2, 1, sheet.getLastRow() - 1, AUDIT_LOG_HEADERS.length)
    .getValues();

  // Filter out empty rows and build result
  var result = [];
  for (var i = rows.length - 1; i >= 0; i--) {
    var row = rows[i];
    if (!row[0] && !row[1]) continue;
    result.push({
      recruitmentId: String(row[0] || ""),
      action: String(row[1] || ""),
      field: String(row[2] || ""),
      oldValue: String(row[3] || ""),
      newValue: String(row[4] || ""),
      user: String(row[5] || ""),
      timestamp:
        row[6] instanceof Date
          ? Utilities.formatDate(row[6], "GMT+7", "yyyy-MM-dd HH:mm:ss")
          : String(row[6] || ""),
    });
  }

  return typeof limit === "number" ? result.slice(0, limit) : result;
}

// ---- Get audit logs for one candidate ----
function getAuditLogForCandidate(recruitmentId) {
  var sheet = getOrCreateAuditLogSheet_();
  if (!sheet || sheet.getLastRow() < 2) return [];

  var rows = sheet
    .getRange(2, 1, sheet.getLastRow() - 1, AUDIT_LOG_HEADERS.length)
    .getValues();
  var result = [];

  for (var i = 0; i < rows.length; i++) {
    var row = rows[i];
    if (String(row[0] || "") === recruitmentId) {
      result.push({
        recruitmentId: String(row[0] || ""),
        action: String(row[1] || ""),
        field: String(row[2] || ""),
        oldValue: String(row[3] || ""),
        newValue: String(row[4] || ""),
        user: String(row[5] || ""),
        timestamp:
          row[6] instanceof Date
            ? Utilities.formatDate(row[6], "GMT+7", "yyyy-MM-dd HH:mm:ss")
            : String(row[6] || ""),
      });
    }
  }

  // Sort by timestamp ascending (oldest first for timeline)
  result.sort(function (a, b) {
    return a.timestamp < b.timestamp ? -1 : a.timestamp > b.timestamp ? 1 : 0;
  });

  return result;
}

// ---- Create/recreate Audit_Log sheet with correct headers ----
function getOrCreateAuditLogSheet_() {
  var ss = SpreadsheetApp.getActiveSpreadsheet();
  var sheet = ss.getSheetByName(AUDIT_SHEET_NAME);

  // If sheet exists, verify headers match
  if (sheet) {
    var currentHeaders = sheet
      .getRange(1, 1, 1, AUDIT_LOG_HEADERS.length)
      .getValues()[0];
    var headersMatch = true;
    for (var i = 0; i < AUDIT_LOG_HEADERS.length; i++) {
      if (String(currentHeaders[i] || "").trim() !== AUDIT_LOG_HEADERS[i]) {
        headersMatch = false;
        break;
      }
    }
    if (headersMatch) return sheet;

    // Headers mismatch — fix them without deleting data
    fixAuditLogSheet(sheet);
    return sheet;
  }

  // Create new sheet
  sheet = ss.insertSheet(AUDIT_SHEET_NAME);
  sheet
    .getRange(1, 1, 1, AUDIT_LOG_HEADERS.length)
    .setValues([AUDIT_LOG_HEADERS]);
  sheet
    .getRange(1, 1, 1, AUDIT_LOG_HEADERS.length)
    .setBackground("#005BAC")
    .setFontColor("#FFFFFF")
    .setFontWeight("bold");
  sheet.setFrozenRows(1);
  sheet.setColumnWidths(1, AUDIT_LOG_HEADERS.length, 160);
  return sheet;
}

// ---- Fix headers & reorder existing data to match AUDIT_LOG_HEADERS ----
function fixAuditLogSheet(sheet) {
  var lastCol = sheet.getLastColumn();
  var lastRow = sheet.getLastRow();
  var currentHeaders =
    lastCol > 0 ? sheet.getRange(1, 1, 1, lastCol).getValues()[0] : [];

  // Map current header names to column indices (0-based)
  var colMap = {};
  for (var c = 0; c < currentHeaders.length; c++) {
    var name = String(currentHeaders[c] || "").trim();
    if (name) colMap[name] = c;
  }

  // Detect old format: has "TimeStamp" (duplicate) but no "Field"
  var hasOldTimestamp = colMap.hasOwnProperty("TimeStamp");
  var hasField = colMap.hasOwnProperty("Field");
  var hasTimestamp = colMap.hasOwnProperty("Timestamp");

  if (lastRow < 2 && !hasOldTimestamp && !hasField) {
    // Empty sheet — just write headers
    if (lastCol > 0) sheet.getRange(1, 1, 1, lastCol).clearContent();
    sheet
      .getRange(1, 1, 1, AUDIT_LOG_HEADERS.length)
      .setValues([AUDIT_LOG_HEADERS]);
    sheet
      .getRange(1, 1, 1, AUDIT_LOG_HEADERS.length)
      .setBackground("#005BAC")
      .setFontColor("#FFFFFF")
      .setFontWeight("bold");
    sheet.setFrozenRows(1);
    return;
  }

  // Read existing data rows (if any)
  var oldData = [];
  if (lastRow >= 2) {
    oldData = sheet.getRange(2, 1, lastRow - 1, lastCol).getValues();
  }

  // Migrate old format → new format
  // Old: Timestamp | User | Recruitment ID | Action | Old Value | New Value | TimeStamp
  // New: Recruitment ID | Action | Field | Old Value | New Value | User | Timestamp
  var migratedData = [];
  for (var r = 0; r < oldData.length; r++) {
    var row = oldData[r];
    if (hasOldTimestamp && !hasField) {
      // Old 7-column format
      migratedData.push([
        row[colMap["Recruitment ID"]] || "", // 1 - Recruitment ID
        row[colMap["Action"]] || "", // 2 - Action
        row[colMap["Status"]] || "Status", // 3 - Field (default "Status" for old records)
        row[colMap["Old Value"]] || "", // 4 - Old Value
        row[colMap["New Value"]] || "", // 5 - New Value
        row[colMap["User"]] || "", // 6 - User
        row[colMap["Timestamp"]] || row[colMap["TimeStamp"]] || "", // 7 - Timestamp
      ]);
    } else {
      // Already new-ish format or unknown — map what we can
      migratedData.push([
        row[colMap["Recruitment ID"]] || "",
        row[colMap["Action"]] || "",
        row[colMap["Field"]] || "Status",
        row[colMap["Old Value"]] || "",
        row[colMap["New Value"]] || "",
        row[colMap["User"]] || "",
        row[colMap["Timestamp"]] || "",
      ]);
    }
  }

  // Clear entire sheet
  sheet.clearContents();
  sheet.clearFormats();

  // Write new headers
  sheet
    .getRange(1, 1, 1, AUDIT_LOG_HEADERS.length)
    .setValues([AUDIT_LOG_HEADERS]);
  sheet
    .getRange(1, 1, 1, AUDIT_LOG_HEADERS.length)
    .setBackground("#005BAC")
    .setFontColor("#FFFFFF")
    .setFontWeight("bold");
  sheet.setFrozenRows(1);

  // Write migrated data
  if (migratedData.length > 0) {
    sheet
      .getRange(2, 1, migratedData.length, AUDIT_LOG_HEADERS.length)
      .setValues(migratedData);
  }

  sheet.setColumnWidths(1, AUDIT_LOG_HEADERS.length, 160);
  SpreadsheetApp.flush();
}

// ============================================================
// AUDIT LOG PAGE — server calls (delegates to Audit.gs core)
// ============================================================
function getAuditLogPageData() {
  return getAllAuditLogs(500);
}
