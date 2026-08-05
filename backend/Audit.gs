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
// Non-destructive: detects whether rows are in OLD or NEW order by inspecting
// the data itself (not just headers), so already-correct data is never lost.
// Old:  Timestamp | User | Recruitment ID | Action | Old Value | New Value | TimeStamp
// New:  Recruitment ID | Action | Field | Old Value | New Value | User | Timestamp
function fixAuditLogSheet(sheet) {
  var lastCol = sheet.getLastColumn();
  var lastRow = sheet.getLastRow();
  var expectedLen = AUDIT_LOG_HEADERS.length;

  // ---- Read current headers ----
  var currentHeaders =
    lastCol > 0 ? sheet.getRange(1, 1, 1, lastCol).getValues()[0] : [];
  var headersTrim = currentHeaders.map(function (h) {
    return String(h || "").trim();
  });

  // Headers already match? Nothing to do.
  var headersCorrect = headersTrim.length >= expectedLen;
  if (headersCorrect) {
    for (var h = 0; h < expectedLen; h++) {
      if (headersTrim[h] !== AUDIT_LOG_HEADERS[h]) {
        headersCorrect = false;
        break;
      }
    }
  }
  if (headersCorrect) {
    return "Audit_Log is already correct. No fix needed.";
  }

  // ---- Read existing data rows ----
  var oldData = [];
  if (lastRow >= 2) {
    oldData = sheet.getRange(2, 1, lastRow - 1, lastCol).getValues();
  }
  var hasData = oldData.length > 0;

  // ---- Detect data orientation by inspecting the first data row ----
  // This is independent of header names, so we never misinterpret
  // already-correct rows and never lose the Field column.
  var dataIsNewOrder = false;
  var dataIsOldOrder = false;
  if (hasData) {
    var sample = oldData[0];
    var s0 = String(sample[0] || "");
    var s1 = String(sample[1] || "");
    var s2 = String(sample[2] || "");
    var s3 = String(sample[3] || "");

    var idPattern = /^(REC-|EMP-|OS-|KD-)/;
    var actionPattern =
      /^(CREATE|STATUS_CHANGE|HR_NOTES_UPDATE|UPDATE|HOLD|BLACKLIST|ACCEPT|CREATE_FROM_RECRUITMENT|DELETE|ARCHIVE|REJECT)/;
    var fieldNames = [
      "Status",
      "HR Notes",
      "Employee",
      "Hold Reason",
      "Blacklist Reason",
      "Notes",
    ];

    // NEW order: col0 = ID, col1 = Action, col2 = Field name
    if (
      idPattern.test(s0) &&
      actionPattern.test(s1) &&
      fieldNames.indexOf(s2) !== -1
    ) {
      dataIsNewOrder = true;
    }
    // OLD order: col0 = Timestamp, col2 = ID, col3 = Action
    if (
      /^\d{4}-\d{2}-\d{2}/.test(s0) &&
      idPattern.test(s2) &&
      actionPattern.test(s3)
    ) {
      dataIsOldOrder = true;
    }
  }

  // ---- Build correctly-ordered rows ----
  var migratedData = [];

  if (dataIsOldOrder && !dataIsNewOrder) {
    // Genuinely OLD data → migrate by position.
    // Old: [Timestamp, User, Recruitment ID, Action, Old Value, New Value, TimeStamp]
    for (var r = 0; r < oldData.length; r++) {
      var row = oldData[r];
      var ts = row[0];
      if (ts instanceof Date) {
        ts = Utilities.formatDate(ts, "GMT+7", "yyyy-MM-dd HH:mm:ss");
      }
      migratedData.push([
        String(row[2] || ""), // 1 - Recruitment ID
        String(row[3] || ""), // 2 - Action
        "Status", // 3 - Field (old format had no Field column)
        String(row[4] || ""), // 4 - Old Value
        String(row[5] || ""), // 5 - New Value
        String(row[1] || ""), // 6 - User
        String(ts || ""), // 7 - Timestamp
      ]);
    }
  } else {
    // Data is already in NEW order (or sheet has no data/unknown) —
    // keep every row untouched, only normalize the headers.
    for (var j = 0; j < oldData.length; j++) {
      var src = oldData[j];
      var out = [];
      for (var k = 0; k < expectedLen; k++) {
        out.push(k < src.length ? src[k] : "");
      }
      if (out[6] instanceof Date) {
        out[6] = Utilities.formatDate(out[6], "GMT+7", "yyyy-MM-dd HH:mm:ss");
      }
      migratedData.push(out);
    }
  }

  // ---- Rewrite the sheet ----
  sheet.clearContents();
  sheet.clearFormats();
  sheet.getRange(1, 1, 1, expectedLen).setValues([AUDIT_LOG_HEADERS]);
  sheet
    .getRange(1, 1, 1, expectedLen)
    .setBackground("#005BAC")
    .setFontColor("#FFFFFF")
    .setFontWeight("bold");
  sheet.setFrozenRows(1);

  if (migratedData.length > 0) {
    sheet
      .getRange(2, 1, migratedData.length, expectedLen)
      .setValues(migratedData);
  }
  sheet.setColumnWidths(1, expectedLen, 160);
  SpreadsheetApp.flush();

  return (
    "Audit_Log fixed: " +
    expectedLen +
    " headers written, " +
    migratedData.length +
    " rows preserved."
  );
}

// ---- Public helper: run from Apps Script editor to repair the sheet ----
// Run: repairAuditLogSheet()
function repairAuditLogSheet() {
  var sheet =
    SpreadsheetApp.getActiveSpreadsheet().getSheetByName(AUDIT_SHEET_NAME);
  if (!sheet) {
    return "Audit_Log sheet not found. Run setupSpreadsheet() first.";
  }
  return fixAuditLogSheet(sheet);
}

// ============================================================
// AUDIT LOG PAGE — server calls (delegates to Audit.gs core)
// ============================================================
function getAuditLogPageData() {
  return getAllAuditLogs(500);
}
